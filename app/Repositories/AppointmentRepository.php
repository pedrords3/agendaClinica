<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AppointmentRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    private function professionalScope(?int $professionalId, array &$params, string $alias = 'a'): string
    {
        if ($professionalId === null) {
            return '';
        }
        $params['escopo_profissional'] = $professionalId;
        return " AND {$alias}.profissional_id=:escopo_profissional";
    }

    public function dashboard(int $tenantId, ?int $professionalId, string $timezone): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $todayStart = $now->setTime(0,0)->setTimezone(new \DateTimeZone('UTC'));
        $tomorrowStart = $todayStart->modify('+1 day');
        $afterTomorrow = $todayStart->modify('+2 days');
        $monthStart = $now->modify('first day of this month')->setTime(0,0)->setTimezone(new \DateTimeZone('UTC'));
        $monthEnd = $monthStart->modify('+1 month');
        $params = [
            'empresa'=>$tenantId,
            'hoje_inicio'=>$todayStart->format('Y-m-d H:i:s'), 'hoje_fim'=>$tomorrowStart->format('Y-m-d H:i:s'),
            'amanha_inicio'=>$tomorrowStart->format('Y-m-d H:i:s'), 'amanha_fim'=>$afterTomorrow->format('Y-m-d H:i:s'),
            'confirmados_inicio'=>$todayStart->format('Y-m-d H:i:s'), 'pendentes_inicio'=>$todayStart->format('Y-m-d H:i:s'),
            'cancelados_inicio'=>$monthStart->format('Y-m-d H:i:s'), 'cancelados_fim'=>$monthEnd->format('Y-m-d H:i:s'),
            'concluidos_inicio'=>$monthStart->format('Y-m-d H:i:s'), 'concluidos_fim'=>$monthEnd->format('Y-m-d H:i:s'),
        ];
        $scope = $this->professionalScope($professionalId, $params);
        $sql = "SELECT SUM(a.inicio_at>=:hoje_inicio AND a.inicio_at<:hoje_fim AND a.status<>'cancelado') AS hoje, SUM(a.inicio_at>=:amanha_inicio AND a.inicio_at<:amanha_fim AND a.status<>'cancelado') AS amanha, SUM(a.status='confirmado' AND a.inicio_at>=:confirmados_inicio) AS confirmados, SUM(a.status='pendente' AND a.inicio_at>=:pendentes_inicio) AS pendentes, SUM(a.status='cancelado' AND a.inicio_at>=:cancelados_inicio AND a.inicio_at<:cancelados_fim) AS cancelados, SUM(a.status='concluido' AND a.inicio_at>=:concluidos_inicio AND a.inicio_at<:concluidos_fim) AS concluidos_mes FROM agendamentos a WHERE a.empresa_id=:empresa{$scope}";
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: [];
    }

    public function upcoming(int $tenantId, ?int $professionalId, int $limit = 8): array
    {
        $params = ['empresa'=>$tenantId];
        $scope = $this->professionalScope($professionalId, $params);
        $sql = "SELECT a.*,c.nome AS cliente_nome,c.telefone AS cliente_telefone,p.nome AS profissional_nome,p.cor_agenda,s.nome AS servico_nome FROM agendamentos a JOIN clientes c ON c.id=a.cliente_id AND c.empresa_id=a.empresa_id JOIN profissionais p ON p.id=a.profissional_id AND p.empresa_id=a.empresa_id JOIN servicos s ON s.id=a.servico_id AND s.empresa_id=a.empresa_id WHERE a.empresa_id=:empresa AND a.inicio_at>=UTC_TIMESTAMP() AND a.status NOT IN ('cancelado','concluido','nao_compareceu'){$scope} ORDER BY a.inicio_at LIMIT " . max(1, min(50, $limit));
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function calendar(int $tenantId, ?int $professionalScope, string $startUtc, string $endUtc, array $filters = []): array
    {
        $params = ['empresa'=>$tenantId,'inicio'=>$startUtc,'fim'=>$endUtc];
        $scope = $this->professionalScope($professionalScope, $params);
        $extra = '';
        foreach (['profissional_id'=>'profissional','servico_id'=>'servico','status'=>'status'] as $key=>$param) {
            if (!empty($filters[$key]) && !($professionalScope && $key === 'profissional_id')) {
                $extra .= " AND a.{$key}=:{$param}";
                $params[$param] = $filters[$key];
            }
        }
        $statement = $this->db->prepare("SELECT a.id,a.inicio_at,a.fim_at,a.status,a.observacoes,c.nome AS cliente_nome,c.telefone AS cliente_telefone,p.nome AS profissional_nome,p.cor_agenda,s.nome AS servico_nome,a.duracao_minutos FROM agendamentos a JOIN clientes c ON c.id=a.cliente_id AND c.empresa_id=a.empresa_id JOIN profissionais p ON p.id=a.profissional_id AND p.empresa_id=a.empresa_id JOIN servicos s ON s.id=a.servico_id AND s.empresa_id=a.empresa_id WHERE a.empresa_id=:empresa AND a.inicio_at<:fim AND a.fim_at>:inicio{$scope}{$extra} ORDER BY a.inicio_at");
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findAuthorized(int $tenantId, int $id, ?int $professionalScope): ?array
    {
        $params = ['empresa'=>$tenantId,'id'=>$id];
        $scope = $this->professionalScope($professionalScope, $params);
        $statement = $this->db->prepare("SELECT a.*,c.nome AS cliente_nome,c.telefone AS cliente_telefone,c.email AS cliente_email,p.nome AS profissional_nome,s.nome AS servico_nome FROM agendamentos a JOIN clientes c ON c.id=a.cliente_id AND c.empresa_id=a.empresa_id JOIN profissionais p ON p.id=a.profissional_id AND p.empresa_id=a.empresa_id JOIN servicos s ON s.id=a.servico_id AND s.empresa_id=a.empresa_id WHERE a.id=:id AND a.empresa_id=:empresa{$scope} LIMIT 1");
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }
}
