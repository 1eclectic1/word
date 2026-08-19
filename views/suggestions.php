<?php
/**
 * suggestions.php - Dual column suggestions
 * Left = Best probes / information words
 * Right = Possible answers only
 */
if (empty($recommendations) || empty($recommendations['answers'])) {
    return;
}

$probes = $recommendations['probes'] ?? [];
$answers = $recommendations['answers'] ?? [];
$topProbes = array_slice($probes, 0, 15, true);
$topAnswers = array_slice($answers, 0, 15, true);
$totalLeft = count($answers);
?>

<div class="suggestions-panel" id="predictions">
    <!-- Toggleable Inner Content Area -->
    <div id="predictions-content">
        <h3>Suggestions</h3>
        <div class="columns-wrapper">
            <!-- Column 1: Best probes -->
            <div class="suggestion-column">
                <div class="column-header">Best Probes</div>
                <?php foreach ($topProbes as $word => $score): ?>
                    <div class="suggestion-row">
                        <span class="word-text"><?= htmlspecialchars($word) ?></span>
                        <span class="word-score">(<?= $score ?>)</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Column 2: Possible answers only -->
            <div class="suggestion-column">
                <div class="column-header">Possible Answers</div>
                <?php foreach ($topAnswers as $word => $score): ?>
                    <div class="suggestion-row">
                        <span class="word-text"><?= htmlspecialchars($word) ?></span>
                        <span class="word-score">(<?= $score ?>)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="summary-text">
            Total possible remaining words: <?= $totalLeft ?>
        </div>
    </div>
</div>

