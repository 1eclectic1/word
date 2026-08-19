<?php
/**
 * grid.php - Standalone 6×5 letter grid component
 */
?>
<div class="grid-container">
    <?php for ($r = 0; $r < MAX_TURNS; $r++): ?>
        <div class="word-row">
            <?php for ($c = 0; $c < WORD_LENGTH; $c++): 
                $letter = '';
                $classState = '';
                $colorVal = 'gray';
                $isEditable = false;

                // Safely read historical rows directly out of your native session layout
                if (isset($_SESSION['history'][$r])) {
                    $letter = $_SESSION['history'][$r]['word'][$c] ?? '';
                    $colorVal = $_SESSION['history'][$r]['colors'][$c] ?? 'gray';
                    $classState = 'state-' . $colorVal;
                } elseif ($r === $currentRowIndex && empty($successMsg) && empty($errorMsg)) {
                    $isEditable = true;
                    if ($mode === 'solve') {
                        $classState = 'state-gray';
                    }
                }
            ?>
                <!-- Individual Character Input Box Component -->
                <input type="text" 
                       name="grid_words[<?= $r ?>][<?= $c ?>]" 
                       class="letter-box <?= $classState ?>" 
                       maxlength="1" 
                       value="<?= strtoupper($letter) ?>" 
                       autocomplete="off" 
                       inputmode="none" 
                       data-row="<?= $r ?>" 
                       data-col="<?= $c ?>" 
                       <?= !$isEditable ? 'readonly' : '' ?> 
                       <?= ($mode === 'solve' && $isEditable) ? 'data-clickable="true"' : '' ?>>
                
                <!-- Matching hidden color tracking array vector -->
                <input type="hidden" 
                       name="grid_colors[<?= $r ?>][<?= $c ?>]" 
                       id="color-<?= $r ?>-<?= $c ?>" 
                       value="<?= $colorVal ?>">
            <?php endfor; ?>
        </div>
    <?php endfor; ?>
</div>

