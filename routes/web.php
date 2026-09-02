<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProfessionalController;
use App\Controllers\PublicBookingController;
use App\Controllers\RegistrationController;
use App\Controllers\ScheduleController;
use App\Controllers\ServiceController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Core\Router;

$router=new Router();
$router->get('/',[HomeController::class,'index']);
$router->get('/login',[AuthController::class,'form'],['guest']);
$router->post('/login',[AuthController::class,'login'],['guest','csrf']);
$router->get('/cadastro',[RegistrationController::class,'form'],['guest']);
$router->post('/cadastro',[RegistrationController::class,'store'],['guest','csrf']);
$router->post('/logout',[AuthController::class,'logout'],['auth','csrf']);

$router->get('/dashboard',[DashboardController::class,'index'],['auth']);
$router->get('/profissionais',[ProfessionalController::class,'index'],['auth','role:proprietario,administrador']);
$router->post('/profissionais',[ProfessionalController::class,'store'],['auth','role:proprietario,administrador','csrf']);
$router->post('/profissionais/{id}/status',[ProfessionalController::class,'toggle'],['auth','role:proprietario,administrador','csrf']);
$router->get('/servicos',[ServiceController::class,'index'],['auth','role:proprietario,administrador']);
$router->post('/servicos',[ServiceController::class,'store'],['auth','role:proprietario,administrador','csrf']);
$router->post('/servicos/{id}/status',[ServiceController::class,'toggle'],['auth','role:proprietario,administrador','csrf']);
$router->get('/clientes',[ClientController::class,'index'],['auth']);
$router->post('/clientes',[ClientController::class,'store'],['auth','role:proprietario,administrador','csrf']);
$router->get('/clientes/{id}',[ClientController::class,'show'],['auth']);
$router->get('/horarios',[ScheduleController::class,'index'],['auth']);
$router->post('/horarios',[ScheduleController::class,'store'],['auth','csrf']);
$router->post('/horarios/{id}/remover',[ScheduleController::class,'remove'],['auth','csrf']);
$router->get('/bloqueios',[ScheduleController::class,'blocks'],['auth']);
$router->post('/bloqueios',[ScheduleController::class,'storeBlock'],['auth','csrf']);
$router->get('/agendamentos/novo',[AppointmentController::class,'create'],['auth']);
$router->post('/agendamentos',[AppointmentController::class,'store'],['auth','csrf']);
$router->get('/agendamentos/{id}',[AppointmentController::class,'show'],['auth']);
$router->post('/agendamentos/{id}/status',[AppointmentController::class,'status'],['auth','csrf']);
$router->get('/configuracoes',[SettingsController::class,'edit'],['auth','role:proprietario']);
$router->post('/configuracoes',[SettingsController::class,'update'],['auth','role:proprietario','csrf']);
$router->get('/usuarios',[UserController::class,'index'],['auth','role:proprietario']);
$router->post('/usuarios',[UserController::class,'store'],['auth','role:proprietario','csrf']);

$router->get('/api/profissionais',[ApiController::class,'professionals'],['auth']);
$router->get('/api/servicos',[ApiController::class,'services'],['auth']);
$router->get('/api/disponibilidade',[ApiController::class,'availability'],['auth']);
$router->get('/api/calendario',[ApiController::class,'calendar'],['auth']);

$router->get('/agendar/{slug}',[PublicBookingController::class,'show']);
$router->post('/agendar/{slug}',[PublicBookingController::class,'store'],['csrf']);
$router->get('/api/publico/{slug}/profissionais',[PublicBookingController::class,'professionals']);
$router->get('/api/publico/{slug}/disponibilidade',[PublicBookingController::class,'availability']);

return $router;
