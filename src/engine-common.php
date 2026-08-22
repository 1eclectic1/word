<?php
// src/engine-common.php
declare(strict_types=1);

/**
 * Compiles a granular 26x5 positional percentage matrix from any given word list.
 */
function engine_compile_percentages(array $wordsSource, array &$resultMatrix): bool {
    $totalWords = count($wordsSource);
    if ($totalWords === 0) return false;

    $countsMatrix = [];
    foreach (range('a', 'z') as $letter) {
        $countsMatrix[$letter] = array_fill(0, 5, 0);
    }

    foreach ($wordsSource as $word) {
        if (strlen($word) === 5) {
            for ($i = 0; $i < 5; $i++) {
                $char = $word[$i];
                if (isset($countsMatrix[$char][$i])) {
                    $countsMatrix[$char][$i]++;
                }
            }
        }
    }

        $resultMatrix = [];
    foreach ($countsMatrix as $letter => $slots) {
        $resultMatrix[$letter] = [
            0 => round(($slots[0] / $totalWords) * 100, 1),
            1 => round(($slots[1] / $totalWords) * 100, 1),
            2 => round(($slots[2] / $totalWords) * 100, 1),
            3 => round(($slots[3] / $totalWords) * 100, 1),
            4 => round(($slots[4] / $totalWords) * 100, 1)
        ];
    }

    return true;
}

/**
 * Boolean verification helper that manages Wordle matching pattern matrices.
 */
function engine_fits_constraints(string $word, string $greenPattern, array $yellowSlots, string $grayString): bool {
    for ($i = 0; $i < 5; $i++) {
        if ($greenPattern[$i] !== '.' && $word[$i] !== $greenPattern[$i]) {
            return false;
        }
    }

    for ($i = 0; $i < 5; $i++) {
        $char = $word[$i];
        if (str_contains($grayString, $char)) {
            if ($greenPattern[$i] === $char) continue;
            foreach ($yellowSlots as $slot) {
                if (str_contains($slot, $char)) continue 2;
            }
            return false;
        }
    }

    for ($i = 0; $i < 5; $i++) {
        $yellowLetters = $yellowSlots[$i];
        if (!empty($yellowLetters)) {
            if (str_contains($yellowLetters, $word[$i])) return false;
            foreach (str_split($yellowLetters) as $yChar) {
                if (!str_contains($word, $yChar)) return false;
            }
        }
    }
    return true;
}

