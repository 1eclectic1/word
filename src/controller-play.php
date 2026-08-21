<?php
// src/controller-play.php
declare(strict_types=1);

global $engine, $secretWord, $userUid, $validationError, $dbError;

// 1. Ingest Human Move
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current-guess'])) {
    $incomingGuess = strtolower(trim($_POST['current-guess']));
    if (strlen($incomingGuess) === 5) {
        if (!$engine->isValidWord($incomingGuess)) {
            $validationError = "The word '" . strtoupper($incomingGuess) . "' is not in the dictionary list!";
        } else {
            process_board_colors($incomingGuess, $secretWord);
        }
    }
}
// 2. Commit Telemetry only when explicitly requested and not already recorded
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['record_game_telemetry'])
    && $_POST['record_game_telemetry'] === '1') {

    $telSecret   = strtolower(trim($_POST['secret_word'] ?? ''));
    $gameOutcome = (($_POST['outcome'] ?? '') === 'win') ? 'win' : 'loss';
    $turnsTaken  = ($gameOutcome === 'win') ? (int)($_POST['turns_taken'] ?? 0) : 0;

    if (strlen($telSecret) === 5) {
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/../data/telemetry.sqlite');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prevent duplicate rows for the same user + secret
            $check = $db->prepare(
                "SELECT COUNT(*) FROM game_history WHERE cookie_uid = ? AND secret_word = ?"
            );
            $check->execute([$userUid, $telSecret]);
            $alreadyExists = (int)$check->fetchColumn();

            if ($alreadyExists === 0) {
                $stmt = $db->prepare(
                    "INSERT INTO game_history (cookie_uid, secret_word, outcome, turns_taken) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$userUid, $telSecret, $gameOutcome, $turnsTaken]);
                compile_telemetry_analytics($userUid);
            }
        } catch (PDOException $e) {
            $dbError = $e->getMessage();
        }
    }
}
