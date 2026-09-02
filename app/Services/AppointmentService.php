<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Repositories\AppointmentRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\ClientRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

final class AppointmentService
{
    private PDO $db;
    private AvailabilityService $availability;
    private CatalogRepository $catalog;
    private ClientRepository $clients;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->availability = new AvailabilityService();
        $this->catalog = new CatalogRepository();
        $this->clients = new ClientRepository();
    }

    public function create(array $company, array $data, ?int $userId, string $ip): int
    {
        $tenantId = (int) $company['id'];
        $professionalId = (int) $data['profissional_id'];
        $serviceId = (int) $data['servico_id'];
        $date = substr((string) $data['inicio'], 0, 10);
        $lockName = sprintf('agenda:%d:%d:%s', $tenantId, $professionalId, str_replace('-', '', $date));
        $lock = $this->db->prepare('SELECT GET_LOCK(:nome, 5)');
        $lock->execute(['nome'=>$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new ValidationException(['inicio'=>'A agenda está sendo atualizada. Tente novamente em alguns segundos.']);
        }

        try {
            $this->db->beginTransaction();
            $slots = $this->availability->slots($company, $professionalId, $serviceId, $date);
            $selected = null;
            foreach ($slots as $slot) {
                if ($slot['value'] === $data['inicio']) {
                    $selected = $slot;
                    break;
                }
            }
            if (!$selected) {
                throw new ValidationException(['inicio'=>'Este horário não está mais disponível. Escolha outro.']);
            }
            $service = $this->catalog->service($tenantId, $serviceId);
            if (!$service) {
                throw new ValidationException(['servico_id'=>'Serviço inválido.']);
            }
            $clientId = !empty($data['cliente_id'])
                ? (int) $data['cliente_id']
                : $this->clients->findOrCreate($tenantId, ['nome'=>$data['cliente_nome'],'telefone'=>$data['cliente_telefone'],'email'=>$data['cliente_email'] ?? '']);
            if (!$this->clients->find($tenantId, $clientId)) {
                throw new ValidationException(['cliente_id'=>'Cliente inválido.']);
            }
            $timezone = new DateTimeZone((string) $company['timezone']);
            $start = new DateTimeImmutable($data['inicio'], $timezone);
            $end = $start->modify('+' . (int) $service['duracao_minutos'] . ' minutes');
            $status = (bool) $company['confirmar_automaticamente'] ? 'confirmado' : 'pendente';
            if (!empty($data['status']) && $userId !== null && in_array($data['status'], ['pendente','confirmado'], true)) {
                $status = $data['status'];
            }
            $statement = $this->db->prepare('INSERT INTO agendamentos (empresa_id,cliente_id,profissional_id,servico_id,inicio_at,fim_at,duracao_minutos,preco_registrado,origem,status,observacoes,criado_por) VALUES (:empresa,:cliente,:profissional,:servico,:inicio,:fim,:duracao,:preco,:origem,:status,:observacoes,:usuario)');
            $statement->execute([
                'empresa'=>$tenantId,'cliente'=>$clientId,'profissional'=>$professionalId,'servico'=>$serviceId,
                'inicio'=>$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'fim'=>$end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'duracao'=>$service['duracao_minutos'],'preco'=>$service['preco'],'origem'=>$data['origem'] ?? 'interno',
                'status'=>$status,'observacoes'=>trim((string) ($data['observacoes'] ?? '')) ?: null,'usuario'=>$userId,
            ]);
            $id = (int) $this->db->lastInsertId();
            (new AuditService())->record($tenantId, $userId, 'agendamento.criado', 'agendamento', $id, ['origem'=>$data['origem'] ?? 'interno','status'=>$status], $ip);
            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        } finally {
            $this->db->prepare('SELECT RELEASE_LOCK(:nome)')->execute(['nome'=>$lockName]);
        }
    }

    public function updateStatus(int $tenantId, int $appointmentId, string $status, int $userId, ?int $professionalScope, ?string $reason, string $ip): void
    {
        $allowed = [
            'pendente'=>['confirmado','cancelado'],
            'confirmado'=>['em_atendimento','concluido','nao_compareceu','cancelado'],
            'em_atendimento'=>['concluido','cancelado'],
            'concluido'=>[], 'cancelado'=>[], 'nao_compareceu'=>[],
        ];
        $repository = new AppointmentRepository();
        $appointment = $repository->findAuthorized($tenantId, $appointmentId, $professionalScope);
        if (!$appointment) {
            throw new ValidationException(['agendamento'=>'Agendamento não encontrado ou não autorizado.']);
        }
        if (!in_array($status, $allowed[$appointment['status']] ?? [], true)) {
            throw new ValidationException(['status'=>'Essa mudança de status não é permitida.']);
        }
        $sql = 'UPDATE agendamentos SET status=:status';
        $params = ['status'=>$status,'id'=>$appointmentId,'empresa'=>$tenantId];
        if ($status === 'cancelado') {
            $sql .= ',cancelado_por=:usuario,cancelado_at=UTC_TIMESTAMP(),motivo_cancelamento=:motivo,origem_cancelamento=\'interno\'';
            $params['usuario'] = $userId;
            $params['motivo'] = $reason ?: null;
        }
        $sql .= ' WHERE id=:id AND empresa_id=:empresa';
        if ($professionalScope !== null) {
            $sql .= ' AND profissional_id=:profissional';
            $params['profissional'] = $professionalScope;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new ValidationException(['agendamento'=>'Agendamento não encontrado ou não autorizado.']);
        }
        (new AuditService())->record($tenantId, $userId, $status === 'cancelado' ? 'agendamento.cancelado' : 'agendamento.status_alterado', 'agendamento', $appointmentId, ['de'=>$appointment['status'],'para'=>$status], $ip);
    }
}

