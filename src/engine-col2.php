<?php
// src/engine-col2.php
declare(strict_types=1);

function engine_build_column2(array $dictionary, string $green, array $yellows, string $grays): array {
    $candidates = [];
    foreach ($dictionary as $word) {
        if (engine_fits_constraints($word, $green, $yellows, $grays)) {
            $candidates[] = $word;
        }
    }

    $localPercentages = [];
    engine_compile_percentages($candidates, $localPercentages);

    $scored = [];
    foreach ($candidates as $word) {
        $score = 0.0;
        for ($i = 0; $i < 5; $i++) {
            $score += $localPercentages[$word[$i]][$i] ?? 0.0;
        }
        $scored[$word] = $score;
    }
    
    // Sort high to low but keep the internal score values intact
    arsort($scored);
    return $scored; 
}

