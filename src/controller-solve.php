<?php
// src/controller-solve.php
declare(strict_types=1);

global $engine, $validationError;

// 1. Ingest standard incoming textual guess token
$incomingGuess = isset($_POST['current-guess']) ? strtolower(trim($_POST['current-guess'])) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $incomingGuess) {
    if (strlen($incomingGuess) === 5) {
        if (!$engine->isValidWord($incomingGuess)) {
            $validationError = "The word '" . strtoupper($incomingGuess) . "' is not in the dictionary list!";
        } else {
            // 2. Identify the active turn row offset index
            $currentTurn = count($_SESSION['history']);
            
            // 3. Extract the manual colors selected by the user via the JS grid interface
            $manualColors = array_fill(0, 5, 'gray');
            for ($i = 0; $i < 5; $i++) {
                // Reads matching color-R-C hidden element keys submitted by the DOM form
                $formKey = "color-{$currentTurn}-{$i}";
                if (isset($_POST[$formKey])) {
                    $manualColors[$i] = strtolower(trim($_POST[$formKey]));
                }
            }
            
            // 4. Inject the data cleanly straight into the active session array frame
            $_SESSION['history'][] = [
                'word'   => $incomingGuess,
                'colors' => $manualColors
            ];
            
            // 5. Re-compile character elimination metrics across all active board states
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

