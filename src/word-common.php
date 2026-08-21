<?php
// src/word-common.php
declare(strict_types=1);

function init_wordle_platform(): WordLearnEngine {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['history']) || !is_array($_SESSION['history'])) {
        $_SESSION['history'] = [];
    }

    if (!defined('MAX_TURNS')) define('MAX_TURNS', 6);
    if (!defined('WORD_LENGTH')) define('WORD_LENGTH', 5);
    if (!defined('VIEWS_PATH')) define('VIEWS_PATH', __DIR__ . '/../views');

    global $userUid, $secretWord, $userDistribution, $maxCount, $totalUserGames, $totalUserWins, $globalTotalGames, $globalTotalWins, $errorMsg, $successMsg, $validationError, $dbError;

    $errorMsg = null;
    $successMsg = null;
    $validationError = null;
    $dbError = null;
    $userDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 'loss' => 0];
    $maxCount = 1;
    $totalUserGames = 0;
    $totalUserWins = 0;
    $globalTotalGames = 0;
    $globalTotalWins = 0;

    // Cookie Tracking Identification Management
    $cookieName = 'word_learn_uid';
    $userUid = $_COOKIE[$cookieName] ?? null;
    if (!$userUid) {
        $userUid = bin2hex(random_bytes(16));
        setcookie($cookieName, $userUid, [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/word/',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    // Handle Universal Application Clear / Reset Requests
    if ((isset($_GET['action']) && $_GET['action'] === 'clear') || (isset($_POST['action']) && $_POST['action'] === 'clear')) {
        $_SESSION['history'] = [];
        $_SESSION['word_learn_greens'] = '.....';
        $_SESSION['word_learn_yellows'] = ['', '', '', '', ''];
        $_SESSION['word_learn_grays'] = '';
        unset($_SESSION['solve_solved']);
        unset($_SESSION['word_learn_secret']);
        $fallbackMode = $_GET['mode'] ?? $_SESSION['word_learn_mode'] ?? 'learn';
        header('Location: index.php?mode=' . $fallbackMode);
        exit;
    }

    // Assign Secret Word
    if (!isset($_SESSION['word_learn_secret']) || empty($_SESSION['word_learn_secret'])) {
        $commonWords = file(__DIR__ . '/../data/wordle-common.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $_SESSION['word_learn_secret'] = strtolower(trim($commonWords[array_rand($commonWords)]));
    }
    $secretWord = $_SESSION['word_learn_secret'];

    // Collect Analytics Aggregations
    compile_telemetry_analytics($userUid);

    return new WordLearnEngine(__DIR__ . '/../data/wordle.txt');
}

function compile_telemetry_analytics(string $userUid): void {
    global $userDistribution, $maxCount, $totalUserGames, $totalUserWins, $globalTotalGames, $globalTotalWins, $dbError;
    $dbPath = __DIR__ . '/../data/telemetry.sqlite';
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT outcome, turns_taken, COUNT(*) as qty FROM game_history WHERE cookie_uid = ? GROUP BY outcome, turns_taken");
        $stmt->execute([$userUid]);
        $userRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($userRows as $row) {
            $qty = (int)$row['qty'];
            if ($row['outcome'] === 'loss') {
                $userDistribution['loss'] += $qty;
            } else {
                $turnNum = (int)$row['turns_taken'];
                if ($turnNum >= 1 && $turnNum <= 6) {
                    $userDistribution[$turnNum] = $qty;
                    $totalUserWins += $qty;
                }
            }
            $totalUserGames += $qty;
            if ($qty > $maxCount) { $maxCount = $qty; }
        }
        
        $globalRows = $db->query("SELECT outcome, COUNT(*) as qty FROM game_history GROUP BY outcome")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($globalRows as $row) {
            $qty = (int)$row['qty'];
            if ($row['outcome'] === 'win') { $globalTotalWins += $qty; }
            $globalTotalGames += $qty;
        }
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

function process_board_colors(string $incomingGuess, string $secretWord): void {
    $currentHint = array_fill(0, 5, 'gray');
    $secretLetters = str_split($secretWord);
    $guessLetters = str_split($incomingGuess);
    
    for ($i = 0; $i < 5; $i++) {
        if ($guessLetters[$i] === $secretLetters[$i]) {
            $currentHint[$i] = 'green';
            $secretLetters[$i] = null;
            $guessLetters[$i] = null;
        }
    }
    for ($i = 0; $i < 5; $i++) {
        if ($guessLetters[$i] === null) continue;
        $index = array_search($guessLetters[$i], $secretLetters);
        if ($index !== false) {
            $currentHint[$i] = 'yellow';
            $secretLetters[$index] = null;
        }
    }
    
    $_SESSION['history'][] = [
        'word' => $incomingGuess,
        'colors' => $currentHint
    ];
    
    $greens = str_split('.....');
    $yellows = ['', '', '', '', ''];
    $grays = '';
    
    foreach ($_SESSION['history'] as $turn) {
        $w = $turn['word'];
        $h = $turn['colors'];
        for ($i = 0; $i < 5; $i++) {
            if ($h[$i] === 'green') {
                $greens[$i] = $w[$i];
            } elseif ($h[$i] === 'yellow' && !str_contains($yellows[$i], $w[$i])) {
                $yellows[$i] .= $w[$i];
            } elseif ($h[$i] === 'gray' && !str_contains($grays, $w[$i])) {
                $grays .= $w[$i];
            }
        }
    }
    $_SESSION['word_learn_greens'] = implode('', $greens);
    $_SESSION['word_learn_yellows'] = $yellows; $_SESSION['word_learn_grays'] = $grays;
}
function record_game_telemetry(string $userUid, string $secret, string $outcome, int $turnsTaken): void {
    global $dbError;
    // Never record the same secret twice for this user
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/../data/telemetry.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $check = $db->prepare(
            "SELECT COUNT(*) FROM game_history WHERE cookie_uid = ? AND secret_word = ?"
        );
        $check->execute([$userUid, $secret]);
        if ((int)$check->fetchColumn() > 0) {
            return; // already recorded
        }
        
        $stmt = $db->prepare(
            "INSERT INTO game_history (cookie_uid, secret_word, outcome, turns_taken) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userUid, $secret, $outcome, $turnsTaken]);

        // CRUCIAL SYNC FIX: Immediately recalculate global statistical metrics
        // after a new database write completes. This ensures the text elements inside
        // views/stats.php align perfectly with the updated chart bars on this frame.
        compile_telemetry_analytics($userUid);

    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

