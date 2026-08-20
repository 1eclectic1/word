<?php
/**
 * word-learn-engine.php - Wordle Processing & Scoring Engine
 * Restructured MVC Logic Block
 */

declare(strict_types=1);

class WordLearnEngine {
    public array $wordlist = [];

    /**
     * Initializes engine and maps file pool into memory cache
     */
    public function __construct(string $filePath) {
        if (!file_exists($filePath)) {
            return;
        }
        $rawWords = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($rawWords as $word) {
            $clean = strtolower(trim($word));
            if (strlen($clean) === 5 && ctype_alpha($clean)) {
                $this->wordlist[] = $clean;
            }
        }
    }

    /**
     * Confirms string existence in master reference dictionary
     */
    public function isValidWord(string $word): bool {
        return in_array(strtolower($word), $this->wordlist, true);
    }

    /**
     * Orchestrates board scoring matrices
     * Builds Column 2 (Answers) first, extracts board clues, then processes Column 1 (Probes)
     */
    public function evaluateBoard(
        string $greenPattern, 
        array $yellowSlots, 
        string $grayString, 
        int $turnsRemaining = 4
    ): array {
        
        // --- TURN 1 INSTANT SAFETY SHORT-CIRCUIT ---
        // If it's a pristine game board, return empty suggestion sets immediately
        // to bypass computing all 15,000 database combinations on page initialization.
        if ($turnsRemaining >= 6 || ($greenPattern === '.....' && $grayString === '')) {
            return [
                'answers' => [],
                'probes'  => []
            ];
        }

        // 1. Normalize all inputs to lowercase
        $greenPattern = strtolower($greenPattern);
        $grayString = strtolower($grayString);
        $normalizedYellows = $this->normalizeYellowSlots($yellowSlots);
        $requiredYellows = $this->extractRequiredYellows($normalizedYellows);

        // -------------------------------------------------------------
        // PHASE 1: BUILD COLUMN 2 (POSSIBLE SOLUTIONS)
        // -------------------------------------------------------------
        $filteredList = array_filter($this->wordlist, function (string $word) use (
            $greenPattern, $normalizedYellows, $grayString, $requiredYellows
        ): bool {
            return $this->isValidSolutionCandidate($word, $greenPattern, $normalizedYellows, $grayString, $requiredYellows);
        });
        
        $filteredList = array_values($filteredList); // Reset indices safely

        // Quick Exit: If no viable answers remain, return blank structures
        if (empty($filteredList)) {
            return ['answers' => [], 'probes' => []];
        }

        // Calculate letter frequencies inside the remaining viable answers
        $frequencies = $this->calculateCharacterFrequencies($filteredList);

        // Score and sort Column 2
        $answerScores = [];
        foreach ($filteredList as $word) {
            $answerScores[$word] = $this->scoreWordByFrequency($word, $frequencies);
        }
        arsort($answerScores);

        // -------------------------------------------------------------
        // PHASE 2: BUILD COLUMN 1 (STRATEGIC PROBES)
        // -------------------------------------------------------------
        // Absolute Lockdown Condition: Force columns to match on the final guess
        if ($turnsRemaining <= 1) {
            return [
                'answers' => $answerScores,
                'probes'  => $answerScores
            ];
        }

        // Identify the exact letters that are currently competing in the open slots
        $targetLetters = $this->extractCompetingWildcardLetters($filteredList, $greenPattern);

        // Score and sort Column 1 using global dictionary pool
        $probeScores = [];
        $greenLetters = array_filter(str_split($greenPattern), function($char) { return $char !== '.'; });
        $totalKnownCount = count($greenLetters) + count($requiredYellows);

        foreach ($this->wordlist as $guessWord) {
            $probeScores[$guessWord] = $this->calculateProbeScore(
                $guessWord, 
                $targetLetters, 
                $frequencies, 
                $greenLetters, 
                $requiredYellows, 
                $totalKnownCount, 
                $turnsRemaining
            );
        }
        arsort($probeScores);

        return [
            'answers' => $answerScores,
            'probes'  => $probeScores
        ];
    }

    // =========================================================================
    // INTERNAL COMPONENT HELPERS (SHARED UTILITIES)
    // =========================================================================

    /**
     * Normalizes yellow layout slots array ensuring standard dot fallback formatting
     */
    private function normalizeYellowSlots(array $yellowSlots): array {
        $normalized = [];
        for ($i = 0; $i < 5; $i++) {
            $normalized[$i] = isset($yellowSlots[$i]) ? strtolower((string) $yellowSlots[$i]) : '.';
        }
        return $normalized;
    }

    /**
     * Extracts unique characters from the current yellow array matrices
     */
    private function extractRequiredYellows(array $normalizedYellows): array {
        $uniqueYellows = [];
        foreach ($normalizedYellows as $slot) {
            if ($slot !== '.') {
                foreach (str_split($slot) as $char) {
                    if (ctype_alpha($char)) {
                        $uniqueYellows[$char] = true;
                    }
                }
            }
        }
        return array_keys($uniqueYellows);
    }

    /**
     * Verifies if a given string matches current green, yellow, and gray vectors
     */
    private function isValidSolutionCandidate(
        string $word, 
        string $greenPattern, 
        array $normalizedYellows, 
        string $grayString, 
        array $requiredYellows
    ): bool {
        // Check Greens
        for ($i = 0; $i < 5; $i++) {
            if ($greenPattern[$i] !== '.' && $word[$i] !== $greenPattern[$i]) {
                return false;
            }
        }

        // Check Grays (ignoring letters that are confirmed elsewhere via Yellows)
        if ($grayString !== '') {
            $chars = str_split($word);
            for ($i = 0; $i < 5; $i++) {
                if ($greenPattern[$i] === '.' && str_contains($grayString, $chars[$i])) {
                    if (!in_array($chars[$i], $requiredYellows, true)) {
                        return false;
                    }
                }
            }
        }

        // Check Yellow existence
        foreach ($requiredYellows as $char) {
            if (!str_contains($word, $char)) {
                return false;
            }
        }

        // Check Yellow positional bans
        for ($i = 0; $i < 5; $i++) {
            $banned = $normalizedYellows[$i];
            if ($banned !== '.' && str_contains($banned, $word[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates a simple frequency map across remaining candidate words
     */
    private function calculateCharacterFrequencies(array $filteredList): array {
        $frequencies = [];
        foreach ($filteredList as $word) {
            foreach (str_split($word) as $char) {
                $frequencies[$char] = ($frequencies[$char] ?? 0) + 1;
            }
        }
        return $frequencies;
    }

    /**
     * Scores a string based on character frequencies (skipping duplicate characters)
     */
    private function scoreWordByFrequency(string $word, array $frequencies): int {
        $score = 0;
        foreach (array_unique(str_split($word)) as $char) {
            $score += $frequencies[$char] ?? 0;
        }
        return $score;
    }

    /**
     * Identifies characters competing for unresolved wildcard slots
     */
    private function extractCompetingWildcardLetters(array $filteredList, string $greenPattern): array {
        $differingLetters = [];
        foreach ($filteredList as $word) {
            $chars = str_split($word);
            for ($i = 0; $i < 5; $i++) {
                if ($greenPattern[$i] === '.') {
                    $differingLetters[$chars[$i]] = true;
                }
            }
        }
        return array_keys($differingLetters);
    }

    /**
     * Computes the score for an individual global dictionary probe word, 
     * factoring in frequency weights, early-game discovery constraints, and trap multipliers
     */
    private function calculateProbeScore(
        string $guessWord,
        array $targetLetters,
        array $frequencies,
        array $greenLetters,
        array $requiredYellows,
        int $totalKnownCount,
        int $turnsRemaining
    ): int {
        $score = 0;
        $seen = [];
        $penalty = 0;

        foreach (str_split($guessWord) as $char) {
            if (isset($seen[$char])) {
                continue; // Do not award duplicate points for double letters in a probe
            }
            $seen[$char] = true;

            // --- EARLY ROUND HEURISTIC PENALTIES (Turns 1 & 2) ---
            if ($turnsRemaining >= 4) {
                // Hard penalty for repeating confirmed static green letters
                if (in_array($char, $greenLetters, true)) {
                    $penalty += 60;
                }
                // Light penalty for repeating fluid yellow letters
                if (in_array($char, $requiredYellows, true)) {
                    $penalty += 25;
                }
            }

            // --- THE SUFFIX TRAP OVERRIDE ACTIVATION ---
            if ($totalKnownCount >= 3) {
                if (in_array($char, $targetLetters, true)) {
                    // Apply 5x scaling factor to crush wildcard anomalies like _IGHT
                    $score += (($frequencies[$char] ?? 1) * 5);
                }
            } else {
                // Standard distribution tracking
                if (in_array($char, $targetLetters, true)) {
                    $score += ($frequencies[$char] ?? 1);
                }
            }
        }

        return $score - $penalty;
    }
}

