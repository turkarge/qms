<?php

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'https://core.example.test');
define('APP_VER', 'test');

require_once BASE_PATH . '/core/helpers.php';

$script = 'modules/users/scripts/view.js';
$url = page_script_url($script);
$expectedVersion = (string) filemtime(BASE_PATH . '/' . $script);

if ($url !== BASE_URL . '/' . $script . '?v=' . rawurlencode($expectedVersion)) {
    fwrite(STDERR, "Page script URL is not versioned correctly: {$url}\n");
    exit(1);
}

fwrite(STDOUT, "Page script version test passed.\n");
