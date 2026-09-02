<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Session;

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return rtrim((string) Env::get('APP_URL', ''), '/') . '/' . ltrim($path, '/'); }
function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return url('/public/assets/' . $relative) . $version;
}

function view(string $name, array $data = []): string
{
    $file = BASE_PATH . '/resources/views/' . str_replace('.', '/', $name) . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View não encontrada: {$name}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">'; }
function auth(): ?array { return Auth::user(); }
function flash(string $key): mixed { return Session::pull($key); }
function old(string $key, mixed $default = ''): mixed { return $_SESSION['_old'][$key] ?? $default; }
function selected(mixed $current, mixed $expected): string { return (string) $current === (string) $expected ? 'selected' : ''; }
function local_datetime(string $utc, string $timezone, string $format = 'd/m/Y H:i'): string
{
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($timezone))->format($format);
}
function money(mixed $value): string { return $value === null || $value === '' ? '—' : 'R$ ' . number_format((float) $value, 2, ',', '.'); }
