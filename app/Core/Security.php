<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function boot(): void
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0775, true);
        }
        ini_set('session.save_path', $sessionPath);
        session_name('agenda_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $lifetime = max(5, (int) Env::get('SESSION_LIFETIME', 120)) * 60;
        if (isset($_SESSION['_last_activity']) && time() - (int) $_SESSION['_last_activity'] > $lifetime) {
            Session::invalidate();
            session_start();
            Session::flash('error', 'Sua sessão expirou. Entre novamente.');
        }
        $_SESSION['_last_activity'] = time();
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
            header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        }
    }
}
