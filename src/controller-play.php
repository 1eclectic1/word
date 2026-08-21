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

