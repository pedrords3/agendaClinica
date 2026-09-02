<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\CatalogRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class AvailabilityService
{
    private PDO $db;
    private CatalogRepository $catalog;
    public function __construct() { $this->db = Database::connection(); $this->catalog = new CatalogRepository(); }

    public static function overlaps(DateTimeImmutable $startA, DateTimeImmutable $endA, DateTimeImmutable $startB, DateTimeImmutable $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    public function slots(array $company, int $professionalId, int $serviceId, string $date): array
    {
        $tenantId = (int) $company['id'];
        $professional = $this->catalog->professional($tenantId, $professionalId);
        $service = $this->catalog->service($tenantId, $serviceId);
        if (!$professional || !$service || !(bool) $professional['ativo'] || !(bool) $service['ativo']) {
            return [];
        }
        $allowed = array_column($this->catalog->professionalServices($tenantId, $professionalId), 'id');
        if (!in_array($serviceId, array_map('intval', $allowed), true)) {
            return [];
        }

        $timezone = new DateTimeZone((string) $company['timezone']);
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        if (!$day || $day->format('Y-m-d') !== $date) {
            return [];
        }
        $now = new DateTimeImmutable('now', $timezone);
        $minimum = $now->modify('+' . (int) $company['antecedencia_minima_minutos'] . ' minutes');
        if ($day > $now->setTime(0,0)->modify('+' . (int) $company['maximo_dias_futuros'] . ' days')) {
            return [];
        }

        $scheduleStatement = $this->db->prepare('SELECT hora_inicio,hora_fim FROM horarios_profissional WHERE empresa_id=:empresa AND profissional_id=:profissional AND dia_semana=:dia ORDER BY hora_inicio');
        $scheduleStatement->execute(['empresa'=>$tenantId,'profissional'=>$professionalId,'dia'=>(int) $day->format('w')]);
        $periods = $scheduleStatement->fetchAll();
        if (!$periods) {
            return [];
        }

        $dayStartUtc = $day->setTimezone(new DateTimeZone('UTC'));
        $dayEndUtc = $day->modify('+1 day')->setTimezone(new DateTimeZone('UTC'));
        $appointments = $this->ranges('SELECT DATE_SUB(a.inicio_at, INTERVAL s.intervalo_antes MINUTE) AS inicio_at, DATE_ADD(a.fim_at, INTERVAL s.intervalo_depois MINUTE) AS fim_at FROM agendamentos a JOIN servicos s ON s.id=a.servico_id AND s.empresa_id=a.empresa_id WHERE a.empresa_id=:empresa AND a.profissional_id=:profissional AND a.status<>\'cancelado\' AND DATE_SUB(a.inicio_at, INTERVAL s.intervalo_antes MINUTE)<:fim AND DATE_ADD(a.fim_at, INTERVAL s.intervalo_depois MINUTE)>:inicio', $tenantId, $professionalId, $dayStartUtc, $dayEndUtc);
        $blocks = $this->ranges('SELECT inicio_at,fim_at FROM bloqueios_agenda WHERE empresa_id=:empresa AND profissional_id=:profissional AND canceled_at IS NULL AND inicio_at<:fim AND fim_at>:inicio', $tenantId, $professionalId, $dayStartUtc, $dayEndUtc);
        $occupied = [...$appointments, ...$blocks];
        $duration = (int) $service['duracao_minutos'];
        $before = (int) $service['intervalo_antes'];
        $after = (int) $service['intervalo_depois'];
        $step = max(5, (int) $company['intervalo_padrao']);
        $slots = [];

        foreach ($periods as $period) {
            $periodStart = new DateTimeImmutable($date . ' ' . $period['hora_inicio'], $timezone);
            $periodEnd = new DateTimeImmutable($date . ' ' . $period['hora_fim'], $timezone);
            for ($serviceStart = $periodStart->modify("+{$before} minutes"); ; $serviceStart = $serviceStart->modify("+{$step} minutes")) {
                $serviceEnd = $serviceStart->modify("+{$duration} minutes");
                $blockStart = $serviceStart->modify("-{$before} minutes")->setTimezone(new DateTimeZone('UTC'));
                $blockEnd = $serviceEnd->modify("+{$after} minutes")->setTimezone(new DateTimeZone('UTC'));
                if ($serviceEnd->modify("+{$after} minutes") > $periodEnd) {
                    break;
                }
                if ($serviceStart < $minimum) {
                    continue;
                }
                $conflict = false;
                foreach ($occupied as [$occupiedStart, $occupiedEnd]) {
                    if (self::overlaps($blockStart, $blockEnd, $occupiedStart, $occupiedEnd)) {
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    $slots[] = ['value'=>$serviceStart->format('Y-m-d\TH:i'),'label'=>$serviceStart->format('H:i'),'end'=>$serviceEnd->format('H:i')];
                }
            }
        }
        return $slots;
    }

    private function ranges(string $sql, int $tenantId, int $professionalId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute(['empresa'=>$tenantId,'profissional'=>$professionalId,'inicio'=>$start->format('Y-m-d H:i:s'),'fim'=>$end->format('Y-m-d H:i:s')]);
        return array_map(static fn(array $row): array => [new DateTimeImmutable($row['inicio_at'], new DateTimeZone('UTC')), new DateTimeImmutable($row['fim_at'], new DateTimeZone('UTC'))], $statement->fetchAll());
    }
}
