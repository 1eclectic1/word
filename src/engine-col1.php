<?php
// src/engine-col1.php
declare(strict_types=1);

/**
 * Builds the strategic information-hunting probe suggestions.
 */

function engine_build_column1(
    array $dictionary,
    array $col2List,
    array $globalPercentages,
    string $greenPattern,
    array $yellowSlots,
    string $grayString,
    int $turnsRemaining
): array {

    // ----------------------------------------------------------
    // 1. OPENING BOOK – blank board only
    // ----------------------------------------------------------
    $isBlankBoard = ($greenPattern === '.....'
        && $grayString === ''
        && implode('', $yellowSlots) === '');

    if ($isBlankBoard) {
        $starters = [
            'slate', 'crane', 'trace', 'crate', 'slant',
            'soare', 'roate', 'raise', 'arise', 'stare',
            'irate', 'adieu', 'audio', 'tears', 'least',
            'steal', 'stone', 'store', 'snare', 'carte'
        ];
        $scored = [];
        foreach ($starters as $i => $w) {
            if (in_array($w, $dictionary, true)) {
                $scored[$w] = 100.0 - $i;   // stable rank
            }
        }
        arsort($scored);
        return $scored;
    }

    // ----------------------------------------------------------
    // 2. Letters already “known” (green or yellow)
    // ----------------------------------------------------------
    $knownLetters = [];
    for ($i = 0; $i < 5; $i++) {
        if ($greenPattern[$i] !== '.') {
            $knownLetters[$greenPattern[$i]] = true;
        }
    }
    foreach ($yellowSlots as $slot) {
        foreach (str_split($slot) as $ch) {
            if ($ch !== '') $knownLetters[$ch] = true;
        }
    }
    foreach (str_split($grayString) as $ch) {
        if ($ch !== '') $knownLetters[$ch] = true; // eliminated too
    }

    // Letters that still appear in possible answers
    $stillPossible = [];
    foreach (array_keys($col2List) as $ans) {
        foreach (str_split((string)$ans) as $ch) {
            if (!isset($knownLetters[$ch])) {
                $stillPossible[$ch] = true;
            }
        }
    }

    $scored = [];
    $earlyGame = ($turnsRemaining >= 4);   // after 1st (and maybe 2nd) guess

    foreach ($dictionary as $word) {
        // Hard gray ban
        $skip = false;
        for ($i = 0; $i < 5; $i++) {
            if (str_contains($grayString, $word[$i])) { $skip = true; break; }
        }
        if ($skip) continue;

        $unique = array_unique(str_split($word));
        $score  = 0.0;

        // Base positional frequency (kept weak)
        for ($i = 0; $i < 5; $i++) {
            $score += ($globalPercentages[$word[$i]][$i] ?? 0.0) * 0.35;
        }

        // Prefer words that are NOT already possible answers
        // (true information probes)
        if (!array_key_exists($word, $col2List)) {
            $score += 25.0;
        }

// Early-game: score almost only by coverage of still-possible letters
if ($earlyGame) {
    $newUseful = 0;
    foreach ($unique as $ch) {
        if (isset($stillPossible[$ch])) {
            $newUseful++;
            $score += 22.0;              // strong per useful new letter
        } else {
            $score -= 8.0;               // penalize letters already decided / gray
        }
    }
    if ($newUseful >= 4) $score += 25.0;
    if (count($unique) === 5) $score += 12.0;

    // Kill residual “ends in Y” bias when Y isn’t even needed
    if ($word[4] === 'y' && !isset($stillPossible['y'])) {
        $score -= 30.0;
    }
} else {
    // late game: keep a bit of frequency
    for ($i = 0; $i < 5; $i++) {
        $score += ($globalPercentages[$word[$i]][$i] ?? 0.0) * 0.35;
    }
}
        $scored[$word] = $score;
    }

    arsort($scored);
    return $scored;
}
