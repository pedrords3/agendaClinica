<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class ScheduleService
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function periods(int $tenantId, int $professionalId): array
    {
        $statement = $this->db->prepare('SELECT * FROM horarios_profissional WHERE empresa_id=:empresa AND profissional_id=:profissional ORDER BY dia_semana,hora_inicio');
        $statement->execute(['empresa'=>$tenantId,'profissional'=>$professionalId]);
        return $statement->fetchAll();
    }

    public function add(int $tenantId, int $professionalId, int $day, string $start, string $end): int
    {
        if ($day < 0 || $day > 6 || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end) || $start >= $end) {
            throw new ValidationException(['horario'=>'Informe um período válido.']);
        }
        $owner = $this->db->prepare('SELECT id FROM profissionais WHERE id=:id AND empresa_id=:empresa AND ativo=1 AND deleted_at IS NULL');
        $owner->execute(['id'=>$professionalId,'empresa'=>$tenantId]);
        if (!$owner->fetch()) {
            throw new ValidationException(['profissional'=>'Profissional inválido.']);
        }
        $overlap = $this->db->prepare('SELECT id FROM horarios_profissional WHERE empresa_id=:empresa AND profissional_id=:profissional AND dia_semana=:dia AND hora_inicio<:fim AND hora_fim>:inicio LIMIT 1');
        $overlap->execute(['empresa'=>$tenantId,'profissional'=>$professionalId,'dia'=>$day,'inicio'=>$start,'fim'=>$end]);
        if ($overlap->fetch()) {
            throw new ValidationException(['horario'=>'O período se sobrepõe a outro já cadastrado.']);
        }
        $statement = $this->db->prepare('INSERT INTO horarios_profissional (empresa_id,profissional_id,dia_semana,hora_inicio,hora_fim) VALUES (?,?,?,?,?)');
        $statement->execute([$tenantId,$professionalId,$day,$start,$end]);
        return (int) $this->db->lastInsertId();
    }

    public function remove(int $tenantId, int $id, ?int $professionalScope = null): bool
    {
        $sql = 'DELETE FROM horarios_profissional WHERE id=:id AND empresa_id=:empresa';
        $params = ['id'=>$id,'empresa'=>$tenantId];
        if ($professionalScope !== null) { $sql .= ' AND profissional_id=:profissional'; $params['profissional']=$professionalScope; }
        $statement = $this->db->prepare($sql); $statement->execute($params);
        return $statement->rowCount() === 1;
    }

    public function blocks(int $tenantId, ?int $professionalScope): array
    {
        $sql = 'SELECT b.*,p.nome AS profissional_nome FROM bloqueios_agenda b JOIN profissionais p ON p.id=b.profissional_id AND p.empresa_id=b.empresa_id WHERE b.empresa_id=:empresa AND b.canceled_at IS NULL AND b.fim_at>=UTC_TIMESTAMP()';
        $params = ['empresa'=>$tenantId];
        if ($professionalScope !== null) { $sql .= ' AND b.profissional_id=:profissional'; $params['profissional']=$professionalScope; }
        $sql .= ' ORDER BY b.inicio_at';
        $statement = $this->db->prepare($sql); $statement->execute($params); return $statement->fetchAll();
    }

    public function addBlock(array $company, int $professionalId, string $start, string $end, string $reason, bool $allDay, int $userId, ?int $professionalScope): int
    {
        if ($professionalScope !== null && $professionalId !== $professionalScope) {
            throw new ValidationException(['profissional'=>'Você só pode bloquear sua própria agenda.']);
        }
        $timezone = new DateTimeZone($company['timezone']);
        try { $from = new DateTimeImmutable($start, $timezone); $to = new DateTimeImmutable($end, $timezone); } catch (\Throwable) { throw new ValidationException(['periodo'=>'Informe datas válidas.']); }
        if ($from >= $to) { throw new ValidationException(['periodo'=>'O fim deve ser posterior ao início.']); }
        $owner = $this->db->prepare('SELECT id FROM profissionais WHERE id=:id AND empresa_id=:empresa AND ativo=1');
        $owner->execute(['id'=>$professionalId,'empresa'=>$company['id']]);
        if (!$owner->fetch()) { throw new ValidationException(['profissional'=>'Profissional inválido.']); }
        $statement = $this->db->prepare('INSERT INTO bloqueios_agenda (empresa_id,profissional_id,inicio_at,fim_at,motivo,dia_inteiro,criado_por) VALUES (?,?,?,?,?,?,?)');
        $statement->execute([$company['id'],$professionalId,$from->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$to->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),trim($reason) ?: null,$allDay ? 1 : 0,$userId]);
        return (int) $this->db->lastInsertId();
    }
}

