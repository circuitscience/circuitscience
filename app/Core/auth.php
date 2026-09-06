<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function configureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    if (defined('SESSION_NAME')) {
        session_name(SESSION_NAME);
    }

    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function startSessionIfNeeded(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        configureSession();
        session_start();
    }
}

function loginUser(array $customer): void
{
    configureSession();
    session_start();
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_first_name'] = $customer['first_name'];
}

function logoutUser(): void
{
    startSessionIfNeeded();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
}

function currentUser(): ?array
{
    startSessionIfNeeded();

    if (empty($_SESSION['customer_id'])) {
        return null;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, preferred_contact, status FROM customers WHERE id = :id AND status = "active"');
    $stmt->execute([':id' => $_SESSION['customer_id']]);
    $customer = $stmt->fetch();

    return $customer ?: null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function authenticate(string $email, string $password): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE email = :email AND status = "active"');
    $stmt->execute([':email' => mb_strtolower($email)]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password_hash'])) {
        return $customer;
    }

    return null;
}
