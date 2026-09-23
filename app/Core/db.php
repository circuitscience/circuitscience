<?php

require_once __DIR__ . '/config.php';

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!class_exists('PDO')) {
        throw new RuntimeException('The PHP PDO extension is not enabled. Enable PDO and PDO MySQL for this domain.');
    }

    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('The PHP PDO MySQL driver is not enabled. Enable pdo_mysql for this domain.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    return $pdo;
}
