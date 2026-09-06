<?php
declare(strict_types=1);

function loadEnvironment(string $path): void
{
    if (!is_readable($path)) {
        throw new RuntimeException('The application environment file is missing.');
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
    }
}

function envValue(string $key, ?string $default = null): string
{
    $value = $_ENV[$key] ?? getenv($key);
    if (($value === false || $value === null || $value === '') && $default === null) {
        throw new RuntimeException("Required environment setting {$key} is missing.");
    }
    return (string) (($value === false || $value === null || $value === '') ? $default : $value);
}

loadEnvironment(dirname(__DIR__) . '/.env');
