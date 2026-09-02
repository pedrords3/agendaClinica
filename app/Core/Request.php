<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $server,
        public array $params = [],
    ) {}

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = parse_url((string) Env::get('APP_URL', ''), PHP_URL_PATH) ?: '';
        if ($base !== '' && $base !== '/' && str_starts_with($uri, rtrim($base, '/'))) {
            $uri = substr($uri, strlen(rtrim($base, '/'))) ?: '/';
        }
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), '/' . ltrim(rawurldecode($uri), '/'), $_GET, $_POST, $_SERVER);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function ip(): string
    {
        return substr((string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    public function expectsJson(): bool
    {
        return str_contains((string) ($this->server['HTTP_ACCEPT'] ?? ''), 'application/json') || str_starts_with($this->path, '/api/');
    }
}

