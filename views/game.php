<?php
/**
 * game.php - Main game form (grid + controls + keyboard + suggestions)
 */

// 1. Enforce rigorous, airtight game-over variable checks
$hasWon  = (isset($successMsg) && !empty($successMsg));
$hasLost = (isset($errorMsg) && !empty($errorMsg));
$isGameOver = ($hasWon || $hasLost);

// 2. Safe default fallback structural mappings
if (!isset($mode)) { $mode = 'learn'; }
if (!isset($currentRowIndex)) { $currentRowIndex = 0; }
?>

<!-- Dictionary Validation Error Banner Box -->
<?php if (isset($validationError) && !empty($validationError)): ?>
    <div class="error-banner" style="background-color: #feb2b2; color: #9b2c2c; padding: 1rem; margin-bottom: 1.5rem; border: 3px solid #9b2c2c; font-weight: bold; font-size: 1.15rem; text-align: center;">
        <?= htmlspecialchars($validationError) ?>
    </div>
<?php endif; ?>

<form method="POST" id="wordle-form" action="index.php?mode=<?= htmlspecialchars($mode) ?>">
    <!-- CORE FORM ROUTERS (Always present for javascript hooks) -->
    <input type="hidden" name="action" id="form-action" value="submit">
    <input type="hidden" name="current-guess" id="current-guess" value="">

    <!-- 🧠 SHIFT CONDITION INSIDE FORM: Swap ONLY the play field grid out -->
    <?php if ($isGameOver): ?>
        <?php require VIEWS_PATH . '/stats.php'; ?>
    <?php else: ?>
        <?php require VIEWS_PATH . '/grid.php'; ?>
    <?php endif; ?>

    <!-- CONTROLS CONTAINER: Always active so buttons never break or disappear -->
    <div class="control-container">
        <button type="button" class="action-btn clear" onclick="window.location.href='index.php?action=clear'">Restart Game</button>
        
        <?php if (!$isGameOver): ?>
            <button type="button" id="toggle-suggestions-btn" class="toggle-btn">Hide Suggestions</button>
            <?php if ($currentRowIndex < 6): ?>
                <button type="submit" class="action-btn submit">
                    <?= $mode === 'learn' ? 'Try' : 'Evaluate' ?>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>

<!-- Hidden Form for Asynchronous or Standard Telemetry Transmission -->
<form id="telemetryForm" style="display: none;">
    <input type="hidden" id="tel_secret" name="secret_word" value="">
    <input type="hidden" id="tel_outcome" name="outcome" value="">
    <input type="hidden" id="tel_turns" name="turns_taken" value="">
    <input type="hidden" name="record_game_telemetry" value="1">
</form>

<!-- VIRTUAL KEYBOARD: Always render below form structures -->
<?php require VIEWS_PATH . '/keyboard.php'; ?>

<!-- DUAL SUGGESTIONS COLUMN: Only load prediction sidebar if game is actively running -->
<?php if (!$isGameOver): ?>
    <?php require VIEWS_PATH . '/suggestions.php'; ?>
<?php endif; ?>

