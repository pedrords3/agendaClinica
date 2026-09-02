<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Controllers\PublicBookingController;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\AppointmentRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\ClientRepository;
use App\Repositories\CompanyRepository;
use App\Services\AuthService;
use App\Services\AvailabilityService;
use App\Services\RegistrationService;

session_save_path(BASE_PATH . '/storage/sessions');
session_start();
$_SESSION = [];
$passed = 0;
$failed = 0;

function test(string $name, callable $callback): void
{
    global $passed, $failed;
    try {
        if ($callback() !== true) {
            throw new RuntimeException('A condição esperada não foi atendida.');
        }
        $passed++;
        echo "[OK] {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        echo "[FALHOU] {$name}: {$exception->getMessage()}" . PHP_EOL;
    }
}

$utc = new \DateTimeZone('UTC');
test('Conflito detecta sobreposição parcial à esquerda', fn() => AvailabilityService::overlaps(new \DateTimeImmutable('2026-01-01 09:45',$utc),new \DateTimeImmutable('2026-01-01 10:15',$utc),new \DateTimeImmutable('2026-01-01 10:00',$utc),new \DateTimeImmutable('2026-01-01 11:00',$utc)));
test('Conflito detecta intervalo contido', fn() => AvailabilityService::overlaps(new \DateTimeImmutable('2026-01-01 10:10',$utc),new \DateTimeImmutable('2026-01-01 10:30',$utc),new \DateTimeImmutable('2026-01-01 10:00',$utc),new \DateTimeImmutable('2026-01-01 11:00',$utc)));
test('Intervalos adjacentes não conflitam', fn() => !AvailabilityService::overlaps(new \DateTimeImmutable('2026-01-01 09:00',$utc),new \DateTimeImmutable('2026-01-01 10:00',$utc),new \DateTimeImmutable('2026-01-01 10:00',$utc),new \DateTimeImmutable('2026-01-01 11:00',$utc)));

$token = Csrf::token();
test('CSRF aceita o token da sessão', fn() => Csrf::valid($token));
test('CSRF rejeita token ausente ou alterado', fn() => !Csrf::valid(null) && !Csrf::valid($token . 'x'));
test('Slug normaliza acentos e espaços', fn() => RegistrationService::slug('Salão da Júlia') === 'salao-da-julia');

$company = (new CompanyRepository())->findPublic('studio-demo');
if (!$company) {
    echo '[FALHOU] Seed Studio Demo não encontrado. Execute database/seed.php.' . PHP_EOL;
    exit(1);
}
$catalog = new CatalogRepository();
$professionals = $catalog->professionals((int)$company['id'], true);
$services = $catalog->services((int)$company['id'], true);
$ana = $professionals[0];
$corte = current(array_filter($services, fn($item) => $item['nome'] === 'Corte'));
$massagem = current(array_filter($services, fn($item) => $item['nome'] === 'Massagem'));

test('Tenant diferente não acessa profissional por ID', fn() => $catalog->professional((int)$company['id'] + 9999, (int)$ana['id']) === null);
test('Senha de bypass antiga não autentica', fn() => !(new AuthService())->attempt('admin@studio-demo.test', 'password', '127.0.0.250'));
$scopedClients = (new ClientRepository())->all((int)$company['id'], '', (int)$ana['id']);
test('Profissional lista somente clientes ligados à própria agenda', fn() => count($scopedClients) === 1 && $scopedClients[0]['nome'] === 'Marina Souza');

$localTz = new \DateTimeZone($company['timezone']);
$businessDay = new \DateTimeImmutable('tomorrow', $localTz);
while ((int)$businessDay->format('N') > 5 || $businessDay->format('Y-m-d') === (new \DateTimeImmutable('tomorrow',$localTz))->format('Y-m-d')) {
    $businessDay = $businessDay->modify('+1 day');
}
$date = $businessDay->format('Y-m-d');
$availability = new AvailabilityService();
$shortSlots = $availability->slots($company, (int)$ana['id'], (int)$corte['id'], $date);
$longSlots = $availability->slots($company, (int)$ana['id'], (int)$massagem['id'], $date);
test('Serviço curto oferece mais horários que serviço longo', fn() => count($shortSlots) > count($longSlots) && count($longSlots) > 0);

$weekend = $businessDay;
while ((int)$weekend->format('N') !== 6) { $weekend = $weekend->modify('+1 day'); }
test('Jornada impede agendamento em dia sem expediente', fn() => $availability->slots($company,(int)$ana['id'],(int)$corte['id'],$weekend->format('Y-m-d')) === []);

$pdo = Database::connection();
$pdo->beginTransaction();
try {
    $blockStart = new \DateTimeImmutable($date . ' 09:00', $localTz);
    $blockEnd = new \DateTimeImmutable($date . ' 10:00', $localTz);
    $userId = (int)$pdo->query('SELECT id FROM usuarios WHERE empresa_id='.(int)$company['id'].' ORDER BY id LIMIT 1')->fetchColumn();
    $statement = $pdo->prepare('INSERT INTO bloqueios_agenda (empresa_id,profissional_id,inicio_at,fim_at,motivo,criado_por) VALUES (?,?,?,?,?,?)');
    $statement->execute([$company['id'],$ana['id'],$blockStart->setTimezone($utc)->format('Y-m-d H:i:s'),$blockEnd->setTimezone($utc)->format('Y-m-d H:i:s'),'Teste automatizado',$userId]);
    $blockedSlots = $availability->slots($company,(int)$ana['id'],(int)$corte['id'],$date);
    test('Bloqueio remove horários sobrepostos', fn() => count($blockedSlots) < count($shortSlots) && !in_array($date.'T09:00',array_column($blockedSlots,'value'),true));
} finally {
    $pdo->rollBack();
}

$appointments = $pdo->query('SELECT id,profissional_id FROM agendamentos WHERE empresa_id='.(int)$company['id'].' ORDER BY id')->fetchAll();
if (count($appointments) >= 2) {
    $target = $appointments[0];
    $otherProfessional = (int)($appointments[1]['profissional_id'] === $target['profissional_id'] ? ($professionals[1]['id'] ?? 0) : $appointments[1]['profissional_id']);
    test('Profissional não acessa agendamento de outro profissional', fn() => (new AppointmentRepository())->findAuthorized((int)$company['id'],(int)$target['id'],$otherProfessional) === null);
}

$request = new Request('GET','/agendar/studio-demo',[],[],[]);
$request->params = ['slug'=>'studio-demo'];
$publicHtml = (new PublicBookingController())->show($request);
test('Página pública não contém nomes de clientes existentes', fn() => !str_contains($publicHtml,'Marina Souza') && !str_contains($publicHtml,'Rafael Lima'));

$registrationSlug = 'empresa-teste-' . bin2hex(random_bytes(4));
$registrationEmail = $registrationSlug . '@example.test';
$registeredCompany = null;
try {
    (new RegistrationService())->register(['nome'=>'Empresa Automatizada','nome_fantasia'=>'Empresa Automatizada','slug'=>$registrationSlug,'segmento'=>'Testes','telefone'=>'(11) 3000-0000','whatsapp'=>'','proprietario_nome'=>'Pessoa de Teste','email'=>$registrationEmail,'senha'=>'TesteSeguro!2026'], '127.0.0.251');
    $registeredCompany = (new CompanyRepository())->findPublic($registrationSlug);
    $registeredUser = (new \App\Repositories\UserRepository())->findForLogin($registrationEmail);
    test('Onboarding cria empresa isolada e usuário proprietário', fn() => $registeredCompany && $registeredUser && (int)$registeredUser['empresa_id']===(int)$registeredCompany['id'] && $registeredUser['perfil']==='proprietario');
} finally {
    if ($registeredCompany) {
        $registeredId=(int)$registeredCompany['id'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM logs_auditoria WHERE empresa_id=:empresa')->execute(['empresa'=>$registeredId]);
            $pdo->prepare('DELETE FROM configuracoes_empresa WHERE empresa_id=:empresa')->execute(['empresa'=>$registeredId]);
            $pdo->prepare('DELETE FROM usuarios WHERE empresa_id=:empresa')->execute(['empresa'=>$registeredId]);
            $pdo->prepare('DELETE FROM empresas WHERE id=:empresa')->execute(['empresa'=>$registeredId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}

echo PHP_EOL . "Resultado: {$passed} passou, {$failed} falhou." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
