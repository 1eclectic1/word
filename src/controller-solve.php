<?php
// src/controller-solve.php
declare(strict_types=1);

global $engine, $validationError;

// ---------- TEMP DEBUG (remove once stable) ----------
$debugLog = __DIR__ . '/../debug-solve.log';
function solve_log(string $msg) : void {
    global $debugLog;
    file_put_contents($debugLog, date('H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}
// -----------------------------------------------------

$incomingGuess = isset($_POST['current-guess'])
    ? strtolower(trim((string)$_POST['current-guess']))
    : null;

solve_log('POST received. guess=[' . ($incomingGuess ?? 'NULL') . ']  history-before=' . count($_SESSION['history'] ?? []));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $incomingGuess !== null && $incomingGuess !== '') {

    if (strlen($incomingGuess) !== 5) {
        $validationError = 'Guess must be exactly 5 letters.';
        solve_log('REJECT: length != 5');
    }
    elseif (!$engine->isValidWord($incomingGuess)) {
        $validationError = "The word '" . strtoupper($incomingGuess) . "' is not in the dictionary list!";
        solve_log('REJECT: not in dictionary');
    }
    else {
        $currentTurn = count($_SESSION['history'] ?? []);

        // Read the colours the user actually clicked
        // Form fields are named grid_colors[r][c]  (see views/grid.php)
        $manualColors = array_fill(0, 5, 'gray');

        if (isset($_POST['grid_colors'][$currentTurn]) && is_array($_POST['grid_colors'][$currentTurn])) {
            for ($i = 0; $i < 5; $i++) {
                if (isset($_POST['grid_colors'][$currentTurn][$i])) {
                    $c = strtolower(trim((string)$_POST['grid_colors'][$currentTurn][$i]));
                    if (in_array($c, ['gray', 'yellow', 'green'], true)) {
                        $manualColors[$i] = $c;
                    }
                }
            }
        } else {
            solve_log('WARNING: grid_colors[' . $currentTurn . '] missing from POST');
        }

        solve_log('colors for turn ' . $currentTurn . ' = ' . implode(',', $manualColors));

        // Append the completed row
        $_SESSION['history'][] = [
            'word'   => $incomingGuess,
            'colors' => $manualColors,
        ];

        // Re-build the cumulative constraint strings
        $greens  = str_split('.....');
        $yellows = ['', '', '', '', ''];
        $grays   = '';

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

        // ---------- All-green = solved ----------
        $isAllGreen = !in_array('gray', $manualColors, true)
                   && !in_array('yellow', $manualColors, true);

        if ($isAllGreen) {
            $_SESSION['solve_solved'] = true;
            solve_log('ALL GREEN – marked as solved');
        }

        solve_log('SUCCESS. history-after=' . count($_SESSION['history']));
    }
}
