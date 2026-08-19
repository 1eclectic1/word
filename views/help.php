<div class="help-display-panel">
    <div class="help-text-content">
        <?php if ($helpText !== ''): ?>
            <div class="help-paragraph"><?= nl2br($helpText) ?></div>
        <?php else: ?>
            <p class="help-paragraph">Help content could not be loaded.</p>
        <?php endif; ?>
    </div>
    <a href="index.php?mode=learn" class="action-btn back-to-game">Back to Game</a>
</div>
