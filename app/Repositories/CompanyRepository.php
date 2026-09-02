<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CompanyRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function find(int $tenantId): ?array
    {
        $statement = $this->db->prepare('SELECT e.*,c.intervalo_padrao,c.antecedencia_minima_minutos,c.maximo_dias_futuros,c.permitir_agendamento_publico,c.confirmar_automaticamente FROM empresas e JOIN configuracoes_empresa c ON c.empresa_id=e.id WHERE e.id=:id LIMIT 1');
        $statement->execute(['id'=>$tenantId]);
        return $statement->fetch() ?: null;
    }

    public function findPublic(string $slug): ?array
    {
        $statement = $this->db->prepare('SELECT e.*,c.intervalo_padrao,c.antecedencia_minima_minutos,c.maximo_dias_futuros,c.permitir_agendamento_publico,c.confirmar_automaticamente FROM empresas e JOIN configuracoes_empresa c ON c.empresa_id=e.id WHERE e.slug=:slug AND e.ativo=1 AND c.permitir_agendamento_publico=1 LIMIT 1');
        $statement->execute(['slug'=>$slug]);
        return $statement->fetch() ?: null;
    }

    public function update(int $tenantId, array $data): void
    {
        $this->db->beginTransaction();
        try {
            $company = $this->db->prepare('UPDATE empresas SET nome=:nome,nome_fantasia=:fantasia,segmento=:segmento,telefone=:telefone,whatsapp=:whatsapp,email=:email,endereco=:endereco,cidade=:cidade,estado=:estado,cor_principal=:cor,timezone=:timezone WHERE id=:id');
            $company->execute(['nome'=>$data['nome'],'fantasia'=>$data['nome_fantasia'],'segmento'=>$data['segmento'],'telefone'=>$data['telefone'] ?: null,'whatsapp'=>$data['whatsapp'] ?: null,'email'=>$data['email'] ?: null,'endereco'=>$data['endereco'] ?: null,'cidade'=>$data['cidade'] ?: null,'estado'=>$data['estado'] ?: null,'cor'=>$data['cor_principal'],'timezone'=>$data['timezone'],'id'=>$tenantId]);
            $config = $this->db->prepare('UPDATE configuracoes_empresa SET intervalo_padrao=:intervalo,antecedencia_minima_minutos=:antecedencia,maximo_dias_futuros=:maximo,permitir_agendamento_publico=:publico,confirmar_automaticamente=:automatico WHERE empresa_id=:empresa');
            $config->execute(['intervalo'=>$data['intervalo_padrao'],'antecedencia'=>$data['antecedencia_minima_minutos'],'maximo'=>$data['maximo_dias_futuros'],'publico'=>$data['permitir_agendamento_publico'],'automatico'=>$data['confirmar_automaticamente'],'empresa'=>$tenantId]);
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}

