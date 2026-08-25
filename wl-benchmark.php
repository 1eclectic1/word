<?php
/**
 * WORD-LEarn Automated Benchmark Harness
 * Tailored for Restructured Modular Engine Layout
 */

declare(strict_types=1);

// 1. Force error visibility to capture any initialization anomalies
ini_set('display_errors', '1');
error_reporting(E_ALL);

// 2. Load the newly decoupled Engine structure directly
require_once __DIR__ . '/src/word-learn-engine.php';

$dictionaryFile = __DIR__ . '/data/wordle.txt';
$commonWordsFile = __DIR__ . '/data/wordle-common.txt';

if (!file_exists($dictionaryFile) || !file_exists($commonWordsFile)) {
    die("Error: Data assets missing. Ensure data/wordle.txt and data/wordle-common.txt exist.\n");
}

// 3. Instantiate your clean architecture engine class instance
$engine = new WordLearnEngine($dictionaryFile);

// Load target dictionary subset and normalize everything to standard lowercase
$targetWords = array_map('strtolower', file($commonWordsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

echo "==================================================\n";
echo " WORD-LEarn Engine Solver Benchmark Running...\n";
echo " Core Architecture: Decoupled & Segmented Matrix\n";
echo " Target Pools: " . count($targetWords) . " words.\n";
echo "==================================================\n\n";

// 4. Fire off loops utilizing the fresh modular data pathways
$probeResults  = runSimulation($engine, $targetWords, 'probes');
$answerResults = runSimulation($engine, $targetWords, 'answers');

printResults("STRATEGY 1: COLUMN 1 (RESTRICT-PENALIZED PROBES)", $probeResults);
printResults("STRATEGY 2: COLUMN 2 (PURE VALID ANSWERS)", $answerResults);

/**
 * Simulates complete automated games running strictly on a single targeted matrix column strategy
 */
function runSimulation(WordLearnEngine $engine, array $wordList, string $strategy): array {
    $startTime = microtime(true);
    
    $totalGames = 0;
    $totalTurns = 0;
    $wins = 0;
    $losses = 0;
    $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

    foreach ($wordList as $secretTarget) {
        $turnsSpent = 0;
        $gameWon = false;

        // Initialize state markers tracking the life of this simulated run
        $greenPattern  = '.....';
        $yellowSlots   = ['', '', '', '', ''];
        $grayString    = '';

        while ($turnsSpent < 6) {
            $turnsSpent++;
            $turnsRemaining = 6 - $turnsSpent;

            // Evaluate board parameters matching the new engine signatures
            $matrix = $engine->evaluateBoard($greenPattern, $yellowSlots, $grayString, $turnsRemaining);

            if ($turnsSpent === 1) {
                $chosenWord = 'slate'; // Standard global statistical opener text string
            } else {
                // Safely extract associative array keys from the isolated strategy block
                $chosenWord = (!empty($matrix[$strategy])) 
                    ? (string) array_key_first($matrix[$strategy]) 
                    : (string) array_key_first($matrix['answers']);
            }

            // Grid safety firewall
            if (!$chosenWord) {
                break;
            }

            if ($chosenWord === $secretTarget) {
                $gameWon = true;
                break;
            }

            // Generate next state vector parameters
            $feedback = computeWordleState($chosenWord, $secretTarget);
            
            // Re-map constraints ensuring non-duplicate grays
            $grayString .= $feedback['gray'];
            $grayString = implode('', array_unique(str_split($grayString)));

            // Merge green patterns and update yellow slots strings
            for ($i = 0; $i < 5; $i++) {
                if ($feedback['green'][$i] !== '.') {
                    $greenPattern[$i] = $feedback['green'][$i];
                }
                if ($feedback['yellow'][$i] !== '') {
                    $yellowSlots[$i] .= $feedback['yellow'][$i];
                }
            }
        }

        $totalGames++;
        if ($gameWon) {
            $wins++;
            $totalTurns += $turnsSpent;
            $distribution[$turnsSpent]++;
        } else {
            $losses++;
        }
    }

    $endTime = microtime(true);

    return [
        'runtime'   => $endTime - $startTime,
        'games'     => $totalGames,
        'wins'      => $wins,
        'losses'    => $losses,
        'avg_turns' => $wins > 0 ? round($totalTurns / $wins, 2) : 0,
        'dist'      => $distribution
    ];
}

/**
 * Parses structural letter intersections mapping greens, yellows, and grays
 */
function computeWordleState(string $guess, string $secret): array {
    $green = '.....';
    $yellow = ['', '', '', '', ''];
    $gray = '';

    $secretLetters = str_split($secret);
    $guessLetters  = str_split($guess);

    for ($i = 0; $i < 5; $i++) {
        if ($guessLetters[$i] === $secretLetters[$i]) {
            $green[$i] = $guessLetters[$i];
            $secretLetters[$i] = null;
            $guessLetters[$i]  = null;
        }
    }

    for ($i = 0; $i < 5; $i++) {
        if ($guessLetters[$i] === null) {
            continue;
        }

        $matchIndex = array_search($guessLetters[$i], $secretLetters, true);
        if ($matchIndex !== false) {
            $yellow[$i] = $guessLetters[$i];
            $secretLetters[$matchIndex] = null;
        } else {
            $gray .= $guessLetters[$i];
        }
    }

    return ['green' => $green, 'yellow' => $yellow, 'gray' => $gray];
}

/**
 * Output layout generator rendering clean visualization blocks
 */
function printResults(string $title, array $res): void {
    echo "## " . $title . " ##\n";
    echo "--------------------------------------------------\n";
    echo " Total Execution Time : " . round($res['runtime'], 4) . " seconds\n";
    echo " Microseconds Per Word: " . round(($res['runtime'] / $res['games']) * 1000000, 1) . " us\n";
    echo " Win / Loss Record    : " . $res['wins'] . " Wins / " . $res['losses'] . " Losses\n";
    echo " Win Rate Percentage  : " . round(($res['wins'] / $res['games']) * 100, 1) . "%\n";
    echo " Average Winning Turns: " . $res['avg_turns'] . " guesses\n\n";
    echo " Turn Guess Distribution:\n";
    foreach ($res['dist'] as $turn => $count) {
        $percentage = $res['games'] > 0 ? round(($count / $res['games']) * 100, 1) : 0;
        echo "   [$turn] : " . str_repeat("█", (int)($percentage / 2)) . " $count ($percentage%)\n";
    }
    echo "--------------------------------------------------\n\n";
}

