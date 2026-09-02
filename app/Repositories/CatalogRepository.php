<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CatalogRepository
{
    private PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    public function professionals(int $tenantId, bool $activeOnly = false): array
    {
        $sql = "SELECT p.*, GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR ', ') AS servicos FROM profissionais p LEFT JOIN profissional_servico ps ON ps.profissional_id=p.id AND ps.empresa_id=p.empresa_id LEFT JOIN servicos s ON s.id=ps.servico_id AND s.empresa_id=p.empresa_id WHERE p.empresa_id=:empresa AND p.deleted_at IS NULL" . ($activeOnly ? ' AND p.ativo=1' : '') . ' GROUP BY p.id ORDER BY p.nome';
        $statement = $this->db->prepare($sql);
        $statement->execute(['empresa'=>$tenantId]);
        return $statement->fetchAll();
    }

    public function services(int $tenantId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM servicos WHERE empresa_id=:empresa AND deleted_at IS NULL' . ($activeOnly ? ' AND ativo=1' : '') . ' ORDER BY nome';
        $statement = $this->db->prepare($sql);
        $statement->execute(['empresa'=>$tenantId]);
        return $statement->fetchAll();
    }

    public function professionalServices(int $tenantId, int $professionalId): array
    {
        $statement = $this->db->prepare('SELECT s.* FROM servicos s JOIN profissional_servico ps ON ps.servico_id=s.id AND ps.empresa_id=s.empresa_id WHERE s.empresa_id=:empresa AND ps.profissional_id=:profissional AND s.ativo=1 AND s.deleted_at IS NULL ORDER BY s.nome');
        $statement->execute(['empresa'=>$tenantId,'profissional'=>$professionalId]);
        return $statement->fetchAll();
    }

    public function serviceProfessionals(int $tenantId, int $serviceId): array
    {
        $statement = $this->db->prepare('SELECT p.id,p.nome,p.especialidade,p.cor_agenda FROM profissionais p JOIN profissional_servico ps ON ps.profissional_id=p.id AND ps.empresa_id=p.empresa_id WHERE p.empresa_id=:empresa AND ps.servico_id=:servico AND p.ativo=1 AND p.deleted_at IS NULL ORDER BY p.nome');
        $statement->execute(['empresa'=>$tenantId,'servico'=>$serviceId]);
        return $statement->fetchAll();
    }

    public function professional(int $tenantId, int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM profissionais WHERE id=:id AND empresa_id=:empresa AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id'=>$id,'empresa'=>$tenantId]);
        return $statement->fetch() ?: null;
    }

    public function service(int $tenantId, int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM servicos WHERE id=:id AND empresa_id=:empresa AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id'=>$id,'empresa'=>$tenantId]);
        return $statement->fetch() ?: null;
    }

    public function createProfessional(int $tenantId, array $data): int
    {
        $statement = $this->db->prepare('INSERT INTO profissionais (empresa_id,nome,telefone,email,descricao,especialidade,cor_agenda) VALUES (:empresa,:nome,:telefone,:email,:descricao,:especialidade,:cor)');
        $statement->execute(['empresa'=>$tenantId,'nome'=>$data['nome'],'telefone'=>$data['telefone'] ?: null,'email'=>$data['email'] ?: null,'descricao'=>$data['descricao'] ?: null,'especialidade'=>$data['especialidade'] ?: null,'cor'=>$data['cor_agenda']]);
        $id = (int) $this->db->lastInsertId();
        $this->syncProfessionalServices($tenantId, $id, $data['servicos'] ?? []);
        return $id;
    }

    public function createService(int $tenantId, array $data): int
    {
        $statement = $this->db->prepare('INSERT INTO servicos (empresa_id,nome,descricao,duracao_minutos,preco,intervalo_antes,intervalo_depois,cor) VALUES (:empresa,:nome,:descricao,:duracao,:preco,:antes,:depois,:cor)');
        $statement->execute(['empresa'=>$tenantId,'nome'=>$data['nome'],'descricao'=>$data['descricao'] ?: null,'duracao'=>$data['duracao_minutos'],'preco'=>$data['preco'] !== '' ? $data['preco'] : null,'antes'=>$data['intervalo_antes'],'depois'=>$data['intervalo_depois'],'cor'=>$data['cor'] ?: null]);
        return (int) $this->db->lastInsertId();
    }

    public function syncProfessionalServices(int $tenantId, int $professionalId, array $serviceIds): void
    {
        if (!$this->professional($tenantId, $professionalId)) {
            return;
        }
        $this->db->prepare('DELETE FROM profissional_servico WHERE empresa_id=:empresa AND profissional_id=:profissional')->execute(['empresa'=>$tenantId,'profissional'=>$professionalId]);
        $insert = $this->db->prepare('INSERT INTO profissional_servico (empresa_id,profissional_id,servico_id) SELECT :empresa,:profissional,id FROM servicos WHERE id=:servico AND empresa_id=:tenant AND ativo=1 AND deleted_at IS NULL');
        foreach (array_unique(array_map('intval', $serviceIds)) as $serviceId) {
            $insert->execute(['empresa'=>$tenantId,'profissional'=>$professionalId,'servico'=>$serviceId,'tenant'=>$tenantId]);
        }
    }

    public function toggle(string $table, int $tenantId, int $id): bool
    {
        if (!in_array($table, ['profissionais','servicos','clientes','usuarios'], true)) {
            return false;
        }
        $statement = $this->db->prepare("UPDATE {$table} SET ativo=NOT ativo WHERE id=:id AND empresa_id=:empresa AND deleted_at IS NULL");
        $statement->execute(['id'=>$id,'empresa'=>$tenantId]);
        return $statement->rowCount() === 1;
    }
}

