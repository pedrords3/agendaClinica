<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function invalidate(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

