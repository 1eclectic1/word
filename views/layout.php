<?php
/**
 * layout.php - Main HTML shell
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WORD-LEarn Platform</title>
    <link rel="stylesheet" href="assets/css/word-learn.css?v=<?= CSS_VERSION ?>">
</head>
<body<?= !empty($_SESSION['solve_solved']) ? ' data-solved="1"' : '' ?>>

<header>
    <h1>WORD-LEarn</h1>
    <h2>Vocabulary Enhancer</h2>

    <div class="nav-container">
        <a href="index.php?mode=learn&reset=1"
           class="nav-btn <?= $mode === 'learn' ? 'active' : '' ?>">Learn Mode</a>
        <a href="index.php?mode=solve&reset=1"
           class="nav-btn <?= $mode === 'solve' ? 'active' : '' ?>">Solver Mode</a>
        <a href="index.php?mode=help"
           class="nav-btn <?= $mode === 'help' ? 'active' : '' ?>">Help Guide</a>
    </div>
</header>

<?php if ($errorMsg): ?>
    <div class="msg-box msg-error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<?php if ($successMsg): ?>
    <div class="msg-box msg-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<?php if ($mode === 'help'): ?>
    <?php require VIEWS_PATH . '/help.php'; ?>
<?php else: ?>
    <?php require VIEWS_PATH . '/game.php'; ?>
<?php endif; ?>
<!-- Hidden template token allowing javascript to evaluate victory logic -->
<span id="secret-word-display" style="display: none;"><?= htmlspecialchars($secretWord) ?></span>

<script src="assets/js/word-learn.js?v=<?= CSS_VERSION ?>"></script>
</body>
</html>
