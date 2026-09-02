<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AppointmentRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\CompanyRepository;
use App\Services\AvailabilityService;
use DateTimeImmutable;
use DateTimeZone;

final class ApiController extends Controller
{
    public function professionals(Request $request): never
    {
        $catalog=new CatalogRepository(); $service=(int)$request->input('servico_id');
        $rows=$service?$catalog->serviceProfessionals(Auth::tenantId(),$service):$catalog->professionals(Auth::tenantId(),true);
        if(Auth::role()==='profissional'){$rows=array_values(array_filter($rows,fn($row)=>(int)$row['id']===(int)Auth::professionalId()));}
        Response::json(['data'=>array_map(fn($row)=>['id'=>(int)$row['id'],'nome'=>$row['nome'],'especialidade'=>$row['especialidade']??null],$rows)]);
    }
    public function services(Request $request): never
    {
        $catalog=new CatalogRepository(); $professional=(int)$request->input('profissional_id');
        if(Auth::role()==='profissional'){$professional=(int)Auth::professionalId();}
        $rows=$professional?$catalog->professionalServices(Auth::tenantId(),$professional):$catalog->services(Auth::tenantId(),true);
        Response::json(['data'=>array_map(fn($row)=>['id'=>(int)$row['id'],'nome'=>$row['nome'],'duracao'=>(int)$row['duracao_minutos'],'preco'=>$row['preco']],$rows)]);
    }
    public function availability(Request $request): never
    {
        $professional=(int)$request->input('profissional_id');
        if(Auth::role()==='profissional'){$professional=(int)Auth::professionalId();}
        $company=(new CompanyRepository())->find(Auth::tenantId());
        Response::json(['data'=>(new AvailabilityService())->slots($company,$professional,(int)$request->input('servico_id'),(string)$request->input('data'))]);
    }
    public function calendar(Request $request): never
    {
        $company=(new CompanyRepository())->find(Auth::tenantId()); $tz=new DateTimeZone($company['timezone']);
        try{$start=(new DateTimeImmutable((string)$request->input('start'),$tz))->setTimezone(new DateTimeZone('UTC'));$end=(new DateTimeImmutable((string)$request->input('end'),$tz))->setTimezone(new DateTimeZone('UTC'));}catch(\Throwable){Response::json(['error'=>'Intervalo inválido.'],422);}
        if($end<=$start||$end>$start->modify('+1 year')){Response::json(['error'=>'Intervalo inválido.'],422);}
        $scope=Auth::role()==='profissional'?Auth::professionalId():null;
        $rows=(new AppointmentRepository())->calendar(Auth::tenantId(),$scope,$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s'),['profissional_id'=>(int)$request->input('profissional_id'),'servico_id'=>(int)$request->input('servico_id'),'status'=>(string)$request->input('status')]);
        $statusLabels=['pendente'=>'A confirmar','confirmado'=>'Confirmado','em_atendimento'=>'Em atendimento','concluido'=>'Concluído','cancelado'=>'Cancelado','nao_compareceu'=>'Não compareceu'];
        $events=array_map(function($row)use($tz,$statusLabels){$start=(new DateTimeImmutable($row['inicio_at'],new DateTimeZone('UTC')))->setTimezone($tz);$end=(new DateTimeImmutable($row['fim_at'],new DateTimeZone('UTC')))->setTimezone($tz);return ['id'=>(int)$row['id'],'title'=>$row['cliente_nome'].' · '.$row['servico_nome'],'start'=>$start->format(DATE_ATOM),'end'=>$end->format(DATE_ATOM),'backgroundColor'=>$row['cor_agenda'],'borderColor'=>$row['cor_agenda'],'classNames'=>['calendar-appointment','calendar-status-'.$row['status']],'extendedProps'=>['cliente'=>$row['cliente_nome'],'telefone'=>$row['cliente_telefone'],'profissional'=>$row['profissional_nome'],'servico'=>$row['servico_nome'],'status'=>$row['status'],'statusLabel'=>$statusLabels[$row['status']]??ucfirst(str_replace('_',' ',$row['status'])),'duracao'=>(int)$row['duracao_minutos']]];},$rows);
        Response::json($events);
    }
}
