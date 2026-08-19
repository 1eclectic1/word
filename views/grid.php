<?php
/**
 * game.php - Main game form (grid + controls + keyboard + suggestions)
 */

$isGameOver = (!empty($successMsg) || !empty($errorMsg));
?>

<!-- Dictionary Validation Error Banner -->
<?php if (isset($validationError) && $validationError !== null): ?>
    <div class="error-banner" style="background-color: #feb2b2; color: #9b2c2c; padding: 1rem; margin-bottom: 1.5rem; border: 3px solid #9b2c2c; font-weight: bold; font-size: 1.15rem; text-align: center;">
        <?= htmlspecialchars($validationError) ?>
    </div>
<?php endif; ?>

<form method="POST" id="wordle-form" action="index.php?mode=<?= htmlspecialchars($mode) ?>">
    <input type="hidden" name="action" id="form-action" value="submit">
    <input type="hidden" name="current-guess" id="current-guess" value="">

    <?php if ($isGameOver): ?>
        <!-- 🧠 THE REPLACEMENT HACK: Stats card displays exactly where the grid used to live -->
        <?php require VIEWS_PATH . '/stats.php'; ?>
    <?php else: ?>
        <!-- Keep the regular board active if the game is ongoing -->
        <?php require VIEWS_PATH . '/grid.php'; ?>
    <?php endif; ?>

    <div class="control-container">
        <button type="button" class="action-btn clear" onclick="triggerReset()">Restart Game</button>
        
        <?php if (!$isGameOver): ?>
            <button type="button" id="toggle-suggestions-btn" class="toggle-btn">Hide Suggestions</button>
            <?php if ($currentRowIndex < MAX_TURNS && empty($successMsg)): ?>
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

<?php require VIEWS_PATH . '/keyboard.php'; ?>

<!-- 🤫 HIDE SUGGESTIONS: Only load the prediction panel if the game is active -->
<?php if (!$isGameOver): ?>
    <?php require VIEWS_PATH . '/suggestions.php'; ?>
<?php endif; ?>

