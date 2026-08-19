<?php
/**
 * help.php - Help panel
 */
?>
<div class="help-display-panel">
    <div class="help-text-content">
        <?php foreach ($helpContent as $paragraph): ?>
            <p class="help-paragraph"><?= $paragraph ?></p>
        <?php endforeach; ?>
    </div>

    <a href="index.php?mode=learn" class="action-btn back-to-game">Back to Game</a>
</div>
