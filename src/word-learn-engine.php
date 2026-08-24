<?php
/**
 * src/word-learn-engine.php
 * 
 * Modular Evaluation Orchestrator Class
 */
declare(strict_types=1);

require_once __DIR__ . '/engine-common.php';
require_once __DIR__ . '/engine-col2.php';
require_once __DIR__ . '/engine-col1.php';

class WordLearnEngine {
    protected array $dictionary = [];
    protected array $globalPercentages = [];
    protected bool $globalPercentagesCompiled = false;

    public function __construct(string $dictPath) {
        if (is_readable($dictPath)) {
            $this->dictionary = file($dictPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $this->dictionary = array_map('strtolower', array_map('trim', $this->dictionary));
        }

        if (!$this->globalPercentagesCompiled && !empty($this->dictionary)) {
            if (engine_compile_percentages($this->dictionary, $this->globalPercentages)) {
                $this->globalPercentagesCompiled = true;
            }
        }
    }

    public function isValidWord(string $word): bool {
        return in_array(strtolower(trim($word)), $this->dictionary, true);
    }

    // Retained for absolute backward compatibility if called externally
    public function compileWordListPercentages(array $wordsSource, array &$resultMatrix): bool {
        return engine_compile_percentages($wordsSource, $resultMatrix);
    }

    public function evaluateBoard(string $greenPattern, array $yellowSlots, string $grayString, int $turnsRemaining): array {
        $green = strtolower(trim($greenPattern));
        $grays = strtolower(trim($grayString));
        $yellows = array_map('strtolower', $yellowSlots);

        // 1. Core Column 2 Pipeline
        $col2Candidates = engine_build_column2($this->dictionary, $green, $yellows, $grays);

        // 2. Core Column 1 Pipeline
        $col1Probes = engine_build_column1($this->dictionary, $col2Candidates, $this->globalPercentages, $green, $yellows, $grays, $turnsRemaining);

        // Slices the top 15 results while preserving both the keys (words) and values (scores)
        return [
            'probes'  => array_slice($col1Probes, 0, 15, true),
            'answers' => array_slice($col2Candidates, 0, 15, true),
            'totalLeft' => count($col2Candidates)
        ];
    }
}

