<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';

// Load project-level .env for local configuration.
// Existing server/host environment variables take precedence.
load_env_file(dirname(__DIR__) . '/.env', false);

date_default_timezone_set('Africa/Accra');

return [
    'env' => 'development'
];
