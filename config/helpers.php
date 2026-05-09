<?php
function redirect(string $route): void
{
    header('Location: index.php?route=' . urlencode($route));
    exit;
}

function setFlash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function sanitizeInput($value): string
{
    if ($value === null) {
        return '';
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        $value = (string) $value;
    }

    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }

    if (!is_scalar($value)) {
        return '';
    }

    return trim((string) $value);
}

function sanitize($value): string
{
    $value = sanitizeInput($value);

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
