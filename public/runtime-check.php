<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo 'PHP runtime is working', PHP_EOL;
echo 'PHP version: ', PHP_VERSION, PHP_EOL;
echo 'SAPI: ', PHP_SAPI, PHP_EOL;
echo 'PDO: ', extension_loaded('pdo') ? 'loaded' : 'missing', PHP_EOL;
echo 'PDO MySQL: ', extension_loaded('pdo_mysql') ? 'loaded' : 'missing', PHP_EOL;
echo 'Fileinfo: ', extension_loaded('fileinfo') ? 'loaded' : 'missing', PHP_EOL;
echo 'Session: ', extension_loaded('session') ? 'loaded' : 'missing', PHP_EOL;
