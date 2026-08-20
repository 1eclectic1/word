<?php
// src/controller-play.php
// Note: This script assumes word-common.php and validation states are already loaded.

init_wordle_session();

// Standardize incoming guess key parameters from views/grid.php
$rawGuess = $_POST['current-guess'] ?? $_POST['current_guess'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rawGuess) {
    if (validate_guess_format($rawGuess)) {
        $cleanGuess = strtoupper(trim($rawGuess));
        
        // Compute turn offsets
        $currentTurn = count($_SESSION['history']);
        
        if ($currentTurn < 6) {
            // Placeholder: Call your engine or grid processor to generate color arrays
            // Example structure:
            // $_SESSION['history'][$currentTurn] = [
            //     'word' => $cleanGuess,
            //     'colors' => ['G', 'Y', 'X', 'X', 'G']
            // ];
        }
    }
}

