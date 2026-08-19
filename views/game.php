<?php
/**
 * game.php - Main game form structure (Manages active sub-views inclusion)
 */

$isGameOver = (!empty($successMsg) || !empty($errorMsg));
?>

<!-- Dictionary Validation Error Banner Box -->
<?php if (isset($validationError) && !empty($validationError)): ?>
    <div class="error-banner" style="background-color: #feb2b2; color: #9b2c2c; padding: 1rem; margin-bottom: 1.5rem; border: 3px solid #9b2c2c; font-weight: bold; font-size: 1.15rem; text-align: center;">
        <?= htmlspecialchars($validationError) ?>
    </div>
<?php endif; ?>

<form method="POST" id="wordle-form" action="index.php?mode=<?= htmlspecialchars($mode) ?>">
    <!-- CORE FORM ROUTERS (Always present for javascript bindings) -->
    <input type="hidden" name="action" id="form-action" value="submit">
    <input type="hidden" name="current-guess" id="current-guess" value="">

    <!-- TELEMETRY FORM PAYLOAD INJECTIONS -->
    <input type="hidden" id="tel_secret" name="secret_word" value="">
    <input type="hidden" id="tel_outcome" name="outcome" value="">
    <input type="hidden" id="tel_turns" name="turns_taken" value="">
    <input type="hidden" id="record_game_telemetry" name="record_game_telemetry" value="0">

    <!-- DYNAMIC SWAP TRIGGER: Swap play area grid for live summary charts -->
    <?php if ($isGameOver): ?>
        <?php require VIEWS_PATH . '/stats.php'; ?>
    <?php else: ?>
        <?php require VIEWS_PATH . '/grid.php'; ?>
    <?php endif; ?>

    <!-- INTERFACE ACTION BUTTON CONTROLS -->
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

<!-- ASYNC EXTRA TRANSMISSION OVERLAY (Safety Fallback Chassis) -->
<form id="telemetryForm" style="display: none;"></form>

<!-- VIRTUAL KEYBOARD INJECTION -->
<?php require VIEWS_PATH . '/keyboard.php'; ?>

<!-- DUAL SUGGESTIONS PREDICTIONS LAYOUT PANEL -->
<?php if (!$isGameOver): ?>
    <?php require VIEWS_PATH . '/suggestions.php'; ?>
<?php endif; ?>

