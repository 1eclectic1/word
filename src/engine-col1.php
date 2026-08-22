<?php
// src/engine-col1.php
declare(strict_types=1);

/**
 * Builds the strategic information-hunting probe suggestions.
 */
function engine_build_column1(array $dictionary, array $col2List, array $globalPercentages, string $greenPattern, array $yellowSlots, string $grayString, int $turnsRemaining): array {
    $scored = [];

// Extract strategic character footprints from the top answers
    $top5Letters = [];
    $top15Letters = [];
    
    foreach (array_slice($col2List, 0, 5, true) as $w => $score) {
        $top5Letters = array_merge($top5Letters, str_split((string)$w));
    }
    foreach (array_slice($col2List, 0, 15, true) as $w => $score) {
        $top15Letters = array_merge($top15Letters, str_split((string)$w));
    }
    $top5Letters = array_unique($top5Letters);
    $top15Letters = array_unique($top15Letters);

    // Evaluate known hint densities
    $knownCount = 0;
    for ($i = 0; $i < 5; $i++) if ($greenPattern[$i] !== '.') $knownCount++;
    foreach ($yellowSlots as $s) $knownCount += strlen($s);
    $hasClues = ($knownCount > 0);

    foreach ($dictionary as $word) {
        // Enforce hard gray avoidance for stable filtering
        for ($i = 0; $i < 5; $i++) {
            if (str_contains($grayString, $word[$i])) continue 2;
        }

        $score = 0.0;
        $uniqueChars = array_unique(str_split($word));

        // Add global matrix frequency score
        for ($i = 0; $i < 5; $i++) {
            $score += $globalPercentages[$word[$i]][$i] ?? 0.0;
        }

        // REWARD: Uprank if not an active option inside Column 2
        if (!array_key_exists($word, $col2List)) {
            $score += 35.0;
        }

        if (!$hasClues) {
            // RULE C: THE SPIFF CASE
            foreach ($uniqueChars as $char) {
                $score += in_array($char, $top5Letters, true) ? -15.0 : 10.0;
            }
        } else {
            // RULE B: TRAP CLEARANCE CASE
            $huntingCount = 0;
            foreach ($uniqueChars as $char) {
                if (!in_array($char, $top15Letters, true) && !str_contains($grayString, $char)) {
                    $huntingCount++;
                }
            }
            if ($huntingCount >= 2) $score *= 1.75;
        }

        // Hard green overlap penalty on early rounds
        if ($turnsRemaining > 3) {
            for ($i = 0; $i < 5; $i++) {
                if ($greenPattern[$i] !== '.' && $word[$i] === $greenPattern[$i]) {
                    $score -= 40.0;
                }
            }
        }
        $scored[$word] = $score;
    }
    arsort($scored);
    return $scored;
}

