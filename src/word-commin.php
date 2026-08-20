<?php
// src/word-common.php

/**
 * Ensures sessions and core game tracking structures are initialized safely.
 */
function init_wordle_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['history']) || !is_array($_SESSION['history'])) {
        $_SESSION['history'] = [];
    }
}

/**
 * Validates word format integrity (5 alphanumeric letters).
 */
function validate_guess_format($word) {
    $clean = trim((string)$word);
    return (strlen($clean) === 5 && ctype_alpha($clean));
}

/**
 * Commits completed game metrics using secure prepared statements.
 */
function record_telemetry_secure($dbPath, $cookieUid, $secretWord, $outcome, $turns) {
    try {
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "INSERT INTO game_history (cookie_uid, secret_word, outcome, turns_taken) 
                VALUES (:uid, :secret, :outcome, :turns)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid'     => $cookieUid,
            ':secret'  => strtoupper($secretWord),
            ':outcome' => $outcome, // 'win' or 'loss'
            ':turns'   => (int)$turns
        ]);
        return true;
    } catch (PDOException $e) {
        // Silently log or handle database lock exceptions safely
        error_log("Telemetry DB Error: " . $e->getMessage());
        return false;
    }
}

