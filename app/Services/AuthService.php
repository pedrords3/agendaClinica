<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\UserRepository;
use PDO;

final class AuthService
{
    private PDO $db;
    private UserRepository $users;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->users = new UserRepository();
    }

    public function attempt(string $email, string $password, string $ip): bool
    {
        $email = mb_strtolower(trim($email));
        $emailHash = hash('sha256', $email);
        $limit = $this->db->prepare("SELECT COUNT(*) FROM tentativas_login WHERE email_hash=:email AND ip=:ip AND sucesso=0 AND created_at>=UTC_TIMESTAMP()-INTERVAL 15 MINUTE");
        $limit->execute(['email'=>$emailHash,'ip'=>$ip]);
        if ((int) $limit->fetchColumn() >= 5) {
            return false;
        }

        $user = $this->users->findForLogin($email);
        $success = $user && password_verify($password, (string) $user['senha_hash']);
        $record = $this->db->prepare('INSERT INTO tentativas_login (empresa_id,email_hash,ip,sucesso) VALUES (:empresa,:email,:ip,:sucesso)');
        $record->execute(['empresa'=>$user['empresa_id'] ?? null,'email'=>$emailHash,'ip'=>$ip,'sucesso'=>$success ? 1 : 0]);
        if (!$success) {
            password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            return false;
        }
        if (password_needs_rehash((string) $user['senha_hash'], PASSWORD_DEFAULT)) {
            $this->db->prepare('UPDATE usuarios SET senha_hash=:senha WHERE id=:id')->execute(['senha'=>password_hash($password, PASSWORD_DEFAULT),'id'=>$user['id']]);
        }
        Auth::login($user);
        $this->users->markLogin((int) $user['id']);
        return true;
    }

    public function isBlocked(string $email, string $ip): bool
    {
        $statement = $this->db->prepare("SELECT COUNT(*) FROM tentativas_login WHERE email_hash=:email AND ip=:ip AND sucesso=0 AND created_at>=UTC_TIMESTAMP()-INTERVAL 15 MINUTE");
        $statement->execute(['email'=>hash('sha256', mb_strtolower(trim($email))),'ip'=>$ip]);
        return (int) $statement->fetchColumn() >= 5;
    }
}

