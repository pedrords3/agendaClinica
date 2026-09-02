<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Core\Env;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$password = (string) Env::get('DEV_ADMIN_PASSWORD', '');
if (strlen($password) < 12) {
    throw new RuntimeException('Defina DEV_ADMIN_PASSWORD no .env com pelo menos 12 caracteres antes do seed.');
}
$email = strtolower((string) Env::get('DEV_ADMIN_EMAIL', 'admin@studio-demo.test'));
$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $exists = $pdo->prepare('SELECT id FROM empresas WHERE slug = :slug');
    $exists->execute(['slug' => 'studio-demo']);
    if ($exists->fetch()) {
        throw new RuntimeException('O seed Studio Demo já foi executado.');
    }

    $pdo->prepare("INSERT INTO empresas (nome,nome_fantasia,slug,segmento,telefone,whatsapp,email,cidade,estado,cor_principal,timezone) VALUES ('Studio Demo','Studio Demo','studio-demo','Serviços','(11) 3000-0000','(11) 99000-0000',:email,'São Paulo','SP','#635bff','America/Sao_Paulo')")->execute(['email' => $email]);
    $empresaId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO configuracoes_empresa (empresa_id,intervalo_padrao,antecedencia_minima_minutos,maximo_dias_futuros,permitir_agendamento_publico,confirmar_automaticamente) VALUES (?,15,30,90,1,0)')->execute([$empresaId]);
    $pdo->prepare("INSERT INTO usuarios (empresa_id,nome,email,senha_hash,perfil) VALUES (?,?,?,?, 'proprietario')")->execute([$empresaId, 'Administrador Demo', $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $professional = $pdo->prepare('INSERT INTO profissionais (empresa_id,nome,telefone,email,especialidade,cor_agenda) VALUES (?,?,?,?,?,?)');
    $professional->execute([$empresaId, 'Ana', '(11) 99999-1001', 'ana@studio-demo.test', 'Especialista', '#635bff']);
    $anaId = (int) $pdo->lastInsertId();
    $professional->execute([$empresaId, 'Carlos', '(11) 99999-1002', 'carlos@studio-demo.test', 'Especialista', '#12a594']);
    $carlosId = (int) $pdo->lastInsertId();

    $service = $pdo->prepare('INSERT INTO servicos (empresa_id,nome,descricao,duracao_minutos,preco,intervalo_depois,cor) VALUES (?,?,?,?,?,?,?)');
    $ids = [];
    foreach ([['Corte','Atendimento de corte',30,40,5,'#635bff'],['Barba','Barba e acabamento',30,35,5,'#12a594'],['Massagem','Sessão de massagem',60,120,10,'#e87938'],['Consulta','Atendimento individual',50,150,10,'#d14d72']] as $row) {
        $service->execute([$empresaId, ...$row]);
        $ids[] = (int) $pdo->lastInsertId();
    }
    $pivot = $pdo->prepare('INSERT INTO profissional_servico (empresa_id,profissional_id,servico_id) VALUES (?,?,?)');
    foreach ([$anaId, $carlosId] as $professionalId) {
        foreach ($ids as $serviceId) {
            $pivot->execute([$empresaId, $professionalId, $serviceId]);
        }
    }
    $schedule = $pdo->prepare('INSERT INTO horarios_profissional (empresa_id,profissional_id,dia_semana,hora_inicio,hora_fim) VALUES (?,?,?,?,?)');
    foreach ([$anaId, $carlosId] as $professionalId) {
        foreach ([1,2,3,4,5] as $day) {
            $schedule->execute([$empresaId, $professionalId, $day, '08:00:00', '12:00:00']);
            $schedule->execute([$empresaId, $professionalId, $day, '13:00:00', '18:00:00']);
        }
    }
    $client = $pdo->prepare('INSERT INTO clientes (empresa_id,nome,telefone,whatsapp,email) VALUES (?,?,?,?,?)');
    $client->execute([$empresaId, 'Marina Souza', '(11) 98888-1001', '(11) 98888-1001', 'marina@example.test']);
    $clientOne = (int) $pdo->lastInsertId();
    $client->execute([$empresaId, 'Rafael Lima', '(11) 98888-1002', '(11) 98888-1002', 'rafael@example.test']);
    $clientTwo = (int) $pdo->lastInsertId();

    $tz = new DateTimeZone('America/Sao_Paulo');
    $nextBusiness = new DateTimeImmutable('tomorrow 09:00', $tz);
    while ((int) $nextBusiness->format('N') > 5) {
        $nextBusiness = $nextBusiness->modify('+1 day');
    }
    $appointment = $pdo->prepare("INSERT INTO agendamentos (empresa_id,cliente_id,profissional_id,servico_id,inicio_at,fim_at,duracao_minutos,preco_registrado,origem,status,criado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    foreach ([[$clientOne,$anaId,$ids[0],$nextBusiness,30,40],[$clientTwo,$carlosId,$ids[2],$nextBusiness->setTime(14,0),60,120]] as [$clientId,$professionalId,$serviceId,$start,$duration,$price]) {
        $startUtc = $start->setTimezone(new DateTimeZone('UTC'));
        $appointment->execute([$empresaId,$clientId,$professionalId,$serviceId,$startUtc->format('Y-m-d H:i:s'),$startUtc->modify("+{$duration} minutes")->format('Y-m-d H:i:s'),$duration,$price,'interno','confirmado',$userId]);
    }
    $pdo->commit();
    echo "Seed concluído. Login: {$email}" . PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

