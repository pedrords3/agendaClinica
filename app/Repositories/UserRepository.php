<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    public function findForLogin(string $email): ?array
    {
        $statement = $this->db->prepare("SELECT u.*, e.nome_fantasia AS empresa_nome, e.cor_principal AS empresa_cor, p.id AS profissional_id FROM usuarios u JOIN empresas e ON e.id=u.empresa_id AND e.ativo=1 LEFT JOIN profissionais p ON p.usuario_id=u.id AND p.empresa_id=u.empresa_id AND p.ativo=1 WHERE u.email=:email AND u.ativo=1 AND u.deleted_at IS NULL LIMIT 1");
        $statement->execute(['email' => strtolower($email)]);
        return $statement->fetch() ?: null;
    }

    public function all(int $tenantId): array
    {
        $statement = $this->db->prepare('SELECT u.id,u.nome,u.email,u.perfil,u.ativo,p.nome AS profissional_nome FROM usuarios u LEFT JOIN profissionais p ON p.usuario_id=u.id AND p.empresa_id=u.empresa_id WHERE u.empresa_id=:empresa AND u.deleted_at IS NULL ORDER BY u.nome');
        $statement->execute(['empresa' => $tenantId]);
        return $statement->fetchAll();
    }

    public function create(int $tenantId, array $data): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO usuarios (empresa_id,nome,email,senha_hash,perfil) VALUES (:empresa,:nome,:email,:senha,:perfil)');
            $statement->execute(['empresa'=>$tenantId,'nome'=>$data['nome'],'email'=>strtolower($data['email']),'senha'=>password_hash($data['senha'], PASSWORD_DEFAULT),'perfil'=>$data['perfil']]);
            $id = (int) $this->db->lastInsertId();
            if ($data['perfil'] === 'profissional') {
                $link = $this->db->prepare('UPDATE profissionais SET usuario_id=:usuario WHERE id=:profissional AND empresa_id=:empresa AND usuario_id IS NULL AND ativo=1 AND deleted_at IS NULL');
                $link->execute(['usuario'=>$id,'profissional'=>(int)($data['profissional_id']??0),'empresa'=>$tenantId]);
                if ($link->rowCount() !== 1) {
                    throw new \App\Core\ValidationException(['profissional_id'=>'Selecione um profissional ativo ainda sem usuário.']);
                }
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $exception;
        }
    }

    public function markLogin(int $id): void
    {
        $this->db->prepare('UPDATE usuarios SET ultimo_login_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$id]);
    }
}
