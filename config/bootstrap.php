<?php
// Load .env file if present (simple loader)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Remove surrounding quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Application environment
$appEnv = getenv('APP_ENV') ?: 'development';

if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Basic security headers - can be extended in production
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:;");

// Set a safe session cookie configuration in production
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($appEnv === 'production') {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    // Set secure cookie only when served over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}

// End bootstrap
