<?php
/**
 * index.php - Core Application Router
 * Sandboxes Play/Learn and Solve modes into dedicated operational spaces.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/word-common.php';
require_once __DIR__ . '/src/word-learn-engine.php';

// Initialize the global environment
$engine = init_wordle_platform();

// Determine execution channel
$previousMode = $_SESSION['word_learn_mode'] ?? 'learn';
$mode = $previousMode;

if (isset($_GET['mode'])) {
    $requestedMode = trim($_GET['mode']);
    
    // Only treat it as a mode change when the value is actually different
    if ($requestedMode !== $previousMode) {
        $_SESSION['word_learn_mode'] = $requestedMode;
        $mode = $requestedMode;
        
        // Clean slate ONLY when switching from learn → solve
        // and only on a GET request (never on a form POST)
        if ($previousMode === 'learn' 
            && $requestedMode === 'solve' 
            && $_SERVER['REQUEST_METHOD'] === 'GET') {
            
            $_SESSION['history'] = [];
            $_SESSION['word_learn_greens'] = '.....';
            $_SESSION['word_learn_yellows'] = ['', '', '', '', ''];
            $_SESSION['word_learn_grays'] = '';
            unset($_SESSION['word_learn_secret']);
            
            // Optional hard refresh to guarantee clean state
            header('Location: index.php?mode=solve');
            exit;
        }
    } else {
        // Same mode – just make sure the variable is set
        $mode = $requestedMode;
        $_SESSION['word_learn_mode'] = $requestedMode;
    }
}

// -------------------------------------------------------------------------
// EXECUTE MODE CONTROLLER
// -------------------------------------------------------------------------
if ($mode === 'solve') {
    require_once __DIR__ . '/src/controller-solve.php';
} else {
    require_once __DIR__ . '/src/controller-play.php';
}

// -------------------------------------------------------------------------
// REBUILD KEYBOARD STATES & COMPILE VIEW ARGUMENTS
// -------------------------------------------------------------------------
$keyboardStates = [];
foreach ($_SESSION['history'] as $turn) {
    $w = $turn['word'];
    $h = $turn['colors'];
    for ($i = 0; $i < 5; $i++) {
        $char = $w[$i];
        $stateClass = 'state-' . $h[$i];
        if (!isset($keyboardStates[$char])) {
            $keyboardStates[$char] = $stateClass;
        } elseif ($keyboardStates[$char] !== 'state-green') {
            if ($stateClass === 'state-green' || ($stateClass === 'state-yellow' && $keyboardStates[$char] === 'state-gray')) {
                $keyboardStates[$char] = $stateClass;
            }
        }
    }
}
// -------------------------------------------------------------------------
// COMPILE METRICS & CONFIGURATIONS FOR THE VIEW LAYER
// -------------------------------------------------------------------------
$currentRowIndex = count($_SESSION['history']);
$turnsRemaining = 6 - $currentRowIndex;

// 1. Only run win/loss notifications (and telemetry) if we are NOT in Solve Mode
if ($mode !== 'solve' && $currentRowIndex > 0) {
    $lastTurn = end($_SESSION['history']);

    if ($lastTurn['word'] === $secretWord) {
        $successMsg = "Outstanding! You cracked the word in " . $currentRowIndex . " guesses!";

        // Record the win once
        record_game_telemetry($userUid, $secretWord, 'win', $currentRowIndex);

    } elseif ($currentRowIndex >= 6) {
        $errorMsg = "Game Over! The secret word was: " . strtoupper($secretWord);

        // Record the loss once
        record_game_telemetry($userUid, $secretWord, 'loss', 0);
    }
}

// 2. Safely isolate or populate Win Rate variables to satisfy views/stats.php
if ($mode !== 'solve') {
    global $totalUserGames, $totalUserWins, $globalTotalGames, $globalTotalWins;
    $userWinRate   = $totalUserGames > 0 ? round(($totalUserWins / $totalUserGames) * 100, 1) : 0;
    $globalWinRate = $globalTotalGames > 0 ? round(($globalTotalWins / $globalTotalGames) * 100, 1) : 0;
} else {
    $userWinRate   = 0;
    $globalWinRate = 0;
}

// 3. Evaluate dictionary suggestions only if the controller didn't pre-populate them
if (!isset($recommendations)) {
    $greenPattern = $_SESSION['word_learn_greens'] ?? '.....';
    $yellowSlots  = $_SESSION['word_learn_yellows'] ?? ['', '', '', '', ''];
    $grayString   = $_SESSION['word_learn_grays'] ?? '';
    $recommendations = $engine->evaluateBoard($greenPattern, $yellowSlots, $grayString, $turnsRemaining);
}

// 4. Finalize view layout assignments
$view = ($mode === 'help') ? 'help' : 'game';
$page = $view;
$action = $view;
$currentContext = $view;

$helpText = '';
if ($mode === 'help') {
    $helpFile = __DIR__ . '/data/help.txt';
    if (is_readable($helpFile)) {
        $helpText = file_get_contents($helpFile);
    }
}

// 5. Render final HTML wrapper markup cleanly
require_once __DIR__ . '/views/layout.php';

