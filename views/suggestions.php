<?php
/**
 * suggestions.php - Dual column suggestions
 * Left = Best probes / information words
 * Right = Possible answers only
 */

// Handle safe variable extraction mapping
$probes = $recommendations['probes'] ?? [];
$answers = $recommendations['answers'] ?? [];

$topProbes = array_slice($probes, 0, 15, true);
$topAnswers = array_slice($answers, 0, 15, true);
?>

<!-- Wrap in a conditional block instead of using 'return' to prevent page compilation crashes -->
<?php if (!empty($recommendations) && !empty($recommendations['answers'])): ?>
<div class="suggestions-panel" id="predictions">
    <div id="predictions-content">
        <h3>Suggestions</h3>
        <div class="columns-wrapper">

            <!-- Column 1: Best probes -->
            <div class="suggestion-column">
                <div class="column-header">Best Probes</div>
<!-- Example layout snippet for Column 1 Probes inside views/suggestions.php -->
<?php foreach ($recommendations['probes'] as $word => $score): ?>
    <div class="suggestion-item">
        <span class="suggested-word"><?= strtoupper($word)   ?></span>
        <span class="score-badge"> <?= round($score, 1) ?>%</span>
    </div>
<?php endforeach; ?>
   </div>

   <!-- Column 2: Possible answers only -->
   <div class="suggestion-column">
   <div class="column-header">Possible Answers</div>

<!-- Identical layout setup for Column 2 Answers -->
<?php foreach ($recommendations['answers'] as $word => $score): ?>
    <div class="suggestion-item">
        <span class="suggested-word"><?= strtoupper($word)   ?></span>
        <span class="score-badge"> <?= round($score, 1) ?>%</span>
    </div>
<?php endforeach; ?>
</div>

   </div>
   <div class="summary-text">
   Total possible remaining words: <?= $totalLeft ?>
   </div>
   </div>
   </div>
   <?php endif; ?>

