<?php
// All files are included relative to this root folder
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/word-common.php';
require_once __DIR__ . '/src/word-learn-engine.php';

init_wordle_session();

$mode = $_REQUEST['mode'] ?? 'play';

if ($mode === 'solve') {
    require_once __DIR__ . '/src/controller-solve.php';
} else {
    require_once __DIR__ . '/src/controller-play.php';
}

// Loads the main layout container relatively
require_once __DIR__ . '/views/game.php';

