<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

final class AuditService
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function record(int $tenantId, ?int $userId, string $action, string $entity, ?int $entityId, array $details = [], ?string $ip = null): void
    {
        unset($details['senha'], $details['password'], $details['senha_hash'], $details['token']);
        $statement = $this->db->prepare('INSERT INTO logs_auditoria (empresa_id,usuario_id,acao,entidade,entidade_id,detalhes,ip) VALUES (:empresa,:usuario,:acao,:entidade,:id,:detalhes,:ip)');
        $statement->execute(['empresa'=>$tenantId,'usuario'=>$userId,'acao'=>$action,'entidade'=>$entity,'id'=>$entityId,'detalhes'=>$details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,'ip'=>$ip]);
    }
}

