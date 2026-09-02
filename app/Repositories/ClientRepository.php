<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ClientRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function all(int $tenantId, string $search = '', ?int $professionalScope = null): array
    {
        $sql = 'SELECT c.*, COUNT(a.id) AS total_agendamentos, MAX(a.inicio_at) AS ultimo_agendamento FROM clientes c LEFT JOIN agendamentos a ON a.cliente_id=c.id AND a.empresa_id=c.empresa_id WHERE c.empresa_id=:empresa AND c.deleted_at IS NULL';
        $params = ['empresa'=>$tenantId];
        if ($professionalScope !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM agendamentos ax WHERE ax.empresa_id=c.empresa_id AND ax.cliente_id=c.id AND ax.profissional_id=:escopo_profissional)';
            $params['escopo_profissional'] = $professionalScope;
        }
        if ($search !== '') {
            $sql .= ' AND (c.nome LIKE :busca OR c.telefone LIKE :busca OR c.email LIKE :busca)';
            $params['busca'] = '%' . $search . '%';
        }
        $sql .= ' GROUP BY c.id ORDER BY c.nome LIMIT 200';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $tenantId, int $id, ?int $professionalScope = null): ?array
    {
        $sql = 'SELECT * FROM clientes c WHERE c.id=:id AND c.empresa_id=:empresa AND c.deleted_at IS NULL';
        $params = ['id'=>$id,'empresa'=>$tenantId];
        if ($professionalScope !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM agendamentos ax WHERE ax.empresa_id=c.empresa_id AND ax.cliente_id=c.id AND ax.profissional_id=:escopo_profissional)';
            $params['escopo_profissional'] = $professionalScope;
        }
        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function history(int $tenantId, int $id, ?int $professionalScope = null): array
    {
        $sql = 'SELECT a.*,p.nome AS profissional_nome,s.nome AS servico_nome FROM agendamentos a JOIN profissionais p ON p.id=a.profissional_id AND p.empresa_id=a.empresa_id JOIN servicos s ON s.id=a.servico_id AND s.empresa_id=a.empresa_id WHERE a.empresa_id=:empresa AND a.cliente_id=:cliente';
        $params = ['empresa'=>$tenantId,'cliente'=>$id];
        if ($professionalScope !== null) { $sql .= ' AND a.profissional_id=:escopo_profissional'; $params['escopo_profissional']=$professionalScope; }
        $statement = $this->db->prepare($sql . ' ORDER BY a.inicio_at DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function create(int $tenantId, array $data): int
    {
        $statement = $this->db->prepare('INSERT INTO clientes (empresa_id,nome,telefone,whatsapp,email,data_nascimento) VALUES (:empresa,:nome,:telefone,:whatsapp,:email,:nascimento)');
        $statement->execute(['empresa'=>$tenantId,'nome'=>$data['nome'],'telefone'=>$data['telefone'],'whatsapp'=>$data['whatsapp'] ?: null,'email'=>$data['email'] ?: null,'nascimento'=>$data['data_nascimento'] ?: null]);
        return (int) $this->db->lastInsertId();
    }

    public function findOrCreate(int $tenantId, array $data): int
    {
        $statement = $this->db->prepare('SELECT id FROM clientes WHERE empresa_id=:empresa AND deleted_at IS NULL AND (telefone=:telefone OR (:email_check IS NOT NULL AND email=:email_value)) ORDER BY id LIMIT 1');
        $email = $data['email'] ?: null;
        $statement->execute(['empresa'=>$tenantId,'telefone'=>$data['telefone'],'email_check'=>$email,'email_value'=>$email]);
        $id = $statement->fetchColumn();
        return $id ? (int) $id : $this->create($tenantId, $data + ['whatsapp'=>$data['telefone'],'data_nascimento'=>null]);
    }
}
