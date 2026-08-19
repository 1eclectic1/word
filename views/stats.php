<?php
/**
 * stats.php - Lifetime Performance & System Benchmarks Panel
 */
?>
<div class="stats-card">
    <h3>Lifetime Statistics Summary</h3>
    <p class="stats-meta">Games Played: <strong><?= $totalUserGames ?></strong> &bull; Win Rate: <strong><?= $userWinRate ?>%</strong></p>
    
    <div class="distribution-chart">
        <?php foreach ($userDistribution as $key => $count): ?>
            <?php 
                $label = ($key === 'loss') ? 'X' : $key;
                $pctWidth = ($maxCount > 0) ? round(($count / $maxCount) * 100) : 0;
                $isActive = (isset($currentRowIndex) && $currentRowIndex === $key) || ($key === 'loss' && !empty($errorMsg));
            ?>
            <div class="chart-row <?= $isActive ? 'active-row' : '' ?>">
                <span class="row-label"><?= $label ?></span>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= max($pctWidth, 8) ?>%;">
                        <?php if ($count > 0): ?>
                            <span class="inside-count"><?= $count ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Global System Benchmarks Panel -->
    <div class="system-benchmark-panel">
        <h4>Global Platform Benchmark</h4>
        <p>Total Community Games: <strong><?= $globalTotalGames ?></strong></p>
        <p>Community Win Rate Average: <strong><?= $globalWinRate ?>%</strong></p>
        <div class="benchmark-comparison-text">
            <?php if ($userWinRate >= $globalWinRate): ?>
                <span class="benchmark-badge superior">🏆 You are beating the community average!</span>
            <?php else: ?>
                <span class="benchmark-badge trailing">📈 Keep playing to catch the community benchmark!</span>
            <?php endif; ?>
        </div>
    </div>
</div>

