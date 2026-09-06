<?php

require_once __DIR__ . '/helpers.php';

$dotenv = getDotEnv(__DIR__ . '/../../.env');
foreach ($dotenv as $key => $value) {
    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
    }
}

define('APP_NAME', env('APP_NAME', 'Circuit Science Inc.'));
define('APP_ENV', env('APP_ENV', 'development'));
define('DB_HOST', env('DB_HOST', 'csi-mysql'));
define('DB_NAME', env('MYSQL_DATABASE', 'jerrybil_csi'));
define('DB_USER', env('MYSQL_USER', 'csiuser'));
define('DB_PASSWORD', env('MYSQL_PASSWORD', 'csipassword'));
define('DB_PORT', env('MYSQL_PORT', '3304'));
define('BASE_URL', env('BASE_URL', ''));
define('SESSION_NAME', env('SESSION_NAME', 'CSISESSION'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 1440));
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@localhost'));
