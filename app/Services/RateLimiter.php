<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class RateLimiter
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function tooMany(string $key, string $context, int $maxAttempts, int $minutes): bool
    {
        $hash = hash('sha256', mb_strtolower($key));
        $statement = $this->db->prepare("SELECT COUNT(*) FROM limites_requisicao WHERE chave_hash=:chave AND contexto=:contexto AND created_at>=UTC_TIMESTAMP()-INTERVAL {$minutes} MINUTE");
        $statement->execute(['chave'=>$hash,'contexto'=>$context]);
        return (int) $statement->fetchColumn() >= $maxAttempts;
    }

    public function hit(string $key, string $context): void
    {
        $this->db->prepare('INSERT INTO limites_requisicao (chave_hash,contexto) VALUES (:chave,:contexto)')->execute(['chave'=>hash('sha256', mb_strtolower($key)),'contexto'=>$context]);
        if (random_int(1, 100) === 1) {
            $this->db->exec("DELETE FROM limites_requisicao WHERE created_at<UTC_TIMESTAMP()-INTERVAL 2 DAY");
        }
    }
}

