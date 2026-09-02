<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['auth']['id'], $_SESSION['auth']['empresa_id']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['auth'] : null;
    }

    public static function id(): int { return (int) ($_SESSION['auth']['id'] ?? 0); }
    public static function tenantId(): int { return (int) ($_SESSION['auth']['empresa_id'] ?? 0); }
    public static function role(): string { return (string) ($_SESSION['auth']['perfil'] ?? ''); }

    public static function professionalId(): ?int
    {
        $id = $_SESSION['auth']['profissional_id'] ?? null;
        return $id ? (int) $id : null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['auth'] = [
            'id' => (int) $user['id'], 'empresa_id' => (int) $user['empresa_id'],
            'nome' => $user['nome'], 'email' => $user['email'], 'perfil' => $user['perfil'],
            'profissional_id' => $user['profissional_id'] ?? null,
            'empresa_nome' => $user['empresa_nome'] ?? '', 'empresa_cor' => $user['empresa_cor'] ?? '#5b5bd6',
        ];
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['_last_activity'] = time();
    }

    public static function logout(): void { Session::invalidate(); }
    public static function canManage(): bool { return in_array(self::role(), ['proprietario', 'administrador'], true); }
}
