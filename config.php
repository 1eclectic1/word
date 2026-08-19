<?php
/**
 * config.php - Application configuration
 */

declare(strict_types=1);

// Base paths (absolute)
define('BASE_PATH', __DIR__);
define('SRC_PATH', BASE_PATH . '/src');
define('VIEWS_PATH', BASE_PATH . '/views');
define('DATA_PATH', BASE_PATH . '/data');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Data files
define('WORDLIST_FILE', DATA_PATH . '/wordle.txt');
define('COMMON_WORDLIST_FILE', DATA_PATH . '/wordle-common.txt');
define('HELP_FILE', DATA_PATH . '/help.txt');

// Application settings
define('MAX_TURNS', 6);
define('WORD_LENGTH', 5);
define('DEFAULT_SECRET', 'slate');   // ultimate fallback

// Display
define('CSS_VERSION', time());       // cache-busting; change to a fixed string in production
