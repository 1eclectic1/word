<?php
/**
 * keyboard.php - Virtual on-screen keyboard
 */
$keyboardRows = [
    ['q','w','e','r','t','y','u','i','o','p'],
    ['a','s','d','f','g','h','j','k','l'],
    ['enter','z','x','c','v','b','n','m','delete']
];
?>
<div class="keyboard-container">
    <?php foreach ($keyboardRows as $rowIndex => $rowKeys): ?>
        <div class="keyboard-row">
            <?php if ($rowIndex === 1 || $rowIndex === 2): ?>
                <div class="keyboard-spacer"></div>
            <?php endif; ?>

            <?php foreach ($rowKeys as $key):
                $keyState     = $keyboardStates[$key] ?? '';
                $isAction     = ($key === 'enter' || $key === 'delete');
                $displayLabel = ($key === 'delete') ? '⌫' : $key;
                $classModifier = $isAction ? 'key-action' : '';
            ?>
                <span class="key <?= $keyState ?> <?= $classModifier ?>"
                      data-key="<?= $key ?>">
                    <?= strtoupper($displayLabel) ?>
                </span>
            <?php endforeach; ?>

            <?php if ($rowIndex === 1 || $rowIndex === 2): ?>
                <div class="keyboard-spacer"></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

