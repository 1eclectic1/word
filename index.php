<?php
/**
 * index.php - Core Application Controller
 * Hardened Pre-Flight Initializer & Route Guard
 */

declare(strict_types=1);

// 1. Boot global configurations and directory paths first
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/word-learn-engine.php';

if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

$engine = new WordLearnEngine(__DIR__ . '/data/wordle.txt');

// 2. CORE VIEW VARIABLE INITIALIZATION GUARD (Guarantees safe fallbacks for empty games)
$errorMsg        = null;
$successMsg      = null;
$validationError = null;
$dbError         = null;

$userDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 'loss' => 0];
$maxCount         = 1; // Seeded at 1 to prevent division-by-zero or math scale clipping
$totalUserGames   = 0;
$totalUserWins    = 0;
$globalTotalGames = 0;
$globalTotalWins  = 0;

// -------------------------------------------------------------------------
// ANONYMOUS COOKIE TELEMETRY TRACKER
// -------------------------------------------------------------------------
$cookieName = 'word_learn_uid';
$userUid = $_COOKIE[$cookieName] ?? null;

if (!$userUid) {
   $userUid = bin2hex(random_bytes(16));
   setcookie($cookieName, $userUid, [
      'expires' => time() + (365 * 24 * 60 * 60), 
      'path'    => '/word/',
      'httponly' => true,                 
      'samesite' => 'Strict'              
   ]);
}

// -------------------------------------------------------------------------
// AIR-TIGHT RESET ROUTE (Triggers via button or link query params)
// -------------------------------------------------------------------------
if ((isset($_GET['action']) && $_GET['action'] === 'clear') || (isset($_POST['action']) && $_POST['action'] === 'clear')) {
   $_SESSION['history']            = []; 
   $_SESSION['word_learn_greens']  = '.....';
   $_SESSION['word_learn_yellows'] = ['', '', '', '', ''];
   $_SESSION['word_learn_grays']   = '';
   unset($_SESSION['word_learn_secret']);

   $fallbackMode = $_GET['mode'] ?? $_SESSION['word_learn_mode'] ?? 'learn';
   header('Location: index.php?mode=' . $fallbackMode);
   exit;
}

// Enforce clean session state array allocation mapping on first init
if (!isset($_SESSION['history']) || !is_array($_SESSION['history'])) {
   $_SESSION['history'] = [];
}

if (isset($_GET['mode'])) {
   $_SESSION['word_learn_mode'] = trim($_GET['mode']);
}
$mode = $_SESSION['word_learn_mode'] ?? 'learn';

// Help view context map override matching core navigation tabs
if (isset($_GET['action']) && $_GET['action'] === 'help') {
   $mode = 'help';
   $_SESSION['word_learn_mode'] = 'help';
}

if (!isset($_SESSION['word_learn_secret']) || empty($_SESSION['word_learn_secret'])) {
   $commonWords = file(__DIR__ . '/data/wordle-common.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   $_SESSION['word_learn_secret'] = strtolower(trim($commonWords[array_rand($commonWords)]));
}
$secretWord = $_SESSION['word_learn_secret'];

// -------------------------------------------------------------------------
// THE LIVE WORDLE INGESTION INTERCEPT: GRAB USER INCOMING GUESS
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current-guess'])) {
   $incomingGuess = strtolower(trim($_POST['current-guess']));

   if (strlen($incomingGuess) === 5) {
      if (!$engine->isValidWord($incomingGuess)) {
         $validationError = "The word '" . strtoupper($incomingGuess) . "' is not in the dictionary list!";
      } else {
         $currentHint = array_fill(0, 5, 'gray');
         $secretLetters = str_split($secretWord);
         $guessLetters  = str_split($incomingGuess);

         for ($i = 0; $i < 5; $i++) {
            if ($guessLetters[$i] === $secretLetters[$i]) {
               $currentHint[$i] = 'green';
               $secretLetters[$i] = null;
               $guessLetters[$i]  = null;
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
            'word'   => $incomingGuess,
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

         $_SESSION['word_learn_greens']  = implode('', $greens);
         $_SESSION['word_learn_yellows'] = $yellows;
         $_SESSION['word_learn_grays']   = $grays;
      }
   }
}

// -------------------------------------------------------------------------
// POST REGISTRATION: TELEMETRY COMPLETED MATCH METRICS
// -------------------------------------------------------------------------
$dbPath = __DIR__ . '/data/telemetry.sqlite';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_game_telemetry'])) {
   $telSecret  = strtolower(trim($_POST['secret_word'] ?? ''));
   $gameOutcome = (($_POST['outcome'] ?? '') === 'win') ? 'win' : 'loss';
   $turnsTaken  = ($gameOutcome === 'win') ? (int)($_POST['turns_taken'] ?? 0) : 0;

   if (strlen($telSecret) === 5) {
      try {
         $db = new PDO('sqlite:' . $dbPath);
         $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $stmt = $db->prepare("INSERT INTO game_history (cookie_uid, secret_word, outcome, turns_taken) VALUES (?, ?, ?, ?)");
         $stmt->execute([$userUid, $telSecret, $gameOutcome, $turnsTaken]);
      } catch (PDOException $e) {
         $dbError = $e->getMessage();
      }
   }
}

// -------------------------------------------------------------------------
// ANALYTICS COMPILATION: CALCULATE STATS & SYSTEMS BENCHMARKS
// -------------------------------------------------------------------------
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

   $globalStmt = $db->query("SELECT outcome, COUNT(*) as qty FROM game_history GROUP BY outcome");
   $globalRows = $globalStmt->fetchAll(PDO::FETCH_ASSOC);
   foreach ($globalRows as $row) {
      $qty = (int)$row['qty'];
      if ($row['outcome'] === 'win') { $globalTotalWins += $qty; }
      $globalTotalGames += $qty;
   }
} catch (PDOException $e) {
   $dbError = $e->getMessage();
}

$userWinRate   = $totalUserGames > 0 ? round(($totalUserWins / $totalUserGames) * 100, 1) : 0;
$globalWinRate = $globalTotalGames > 0 ? round(($globalTotalWins / $globalTotalGames) * 100, 1) : 0;

// -------------------------------------------------------------------------
// ORCHESTRATE LIVE VIEW PROPERTIES
// -------------------------------------------------------------------------
$currentRowIndex = count($_SESSION['history']);
$turnsRemaining = 6 - $currentRowIndex;

if ($currentRowIndex > 0) {
   $lastTurn = end($_SESSION['history']);
   if ($lastTurn['word'] === $secretWord) {
      $successMsg = "Outstanding! You cracked the word in " . $currentRowIndex . " guesses!";
   } elseif ($currentRowIndex >= 6) {
      $errorMsg = "Game Over! The secret word was: " . strtoupper($secretWord);
   }
}

$greenPattern = $_SESSION['word_learn_greens']  ?? '.....';
$yellowSlots  = $_SESSION['word_learn_yellows'] ?? ['', '', '', '', ''];
$grayString   = $_SESSION['word_learn_grays']   ?? '';

$suggestions = $engine->evaluateBoard($greenPattern, $yellowSlots, $grayString, $turnsRemaining);

// -------------------------------------------------------------------------
// GLOBAL LAYOUT CORE INTERFACE CONFIGURATIONS MAPPING
// -------------------------------------------------------------------------
$view           = ($mode === 'help') ? 'help' : 'game';
$page           = $view;
$action         = $view;
$currentContext = $view;

$recommendations = $suggestions; 

// Rebuild virtual keyboard mapping array configurations
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
}}}}
            if (!defined('MAX_TURNS')) { define('MAX_TURNS', 6); }
if (!defined('WORD_LENGTH')) { define('WORD_LENGTH', 5); }

// FIX: Ensure __DIR__ has double underscores on both sides!
if (!defined('VIEWS_PATH')) { define('VIEWS_PATH', __DIR__ . '/views'); } 

// -------------------------------------------------------------------------
// RENDER MAIN VIEW PLATFORM TEMPLATES
// -------------------------------------------------------------------------
// FIX: Ensure __DIR__ has double underscores here too!
require_once __DIR__ . '/views/layout.php'; 
