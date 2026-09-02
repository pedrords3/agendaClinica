<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Repositories\CatalogRepository;
use App\Repositories\CompanyRepository;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use App\Services\RateLimiter;

final class PublicBookingController extends Controller
{
    private function company(Request $request): array
    {
        $company=(new CompanyRepository())->findPublic((string)($request->params['slug']??''));
        if(!$company){Response::abort(404,'Página de agendamento indisponível.');}
        return $company;
    }
    public function show(Request $request): string
    {
        $company=$this->company($request); $catalog=new CatalogRepository();
        return view('public/booking',['company'=>$company,'services'=>$catalog->services((int)$company['id'],true)]);
    }
    public function professionals(Request $request): never
    {
        $company=$this->company($request); $rows=(new CatalogRepository())->serviceProfessionals((int)$company['id'],(int)$request->input('servico_id'));
        Response::json(['data'=>array_map(fn($row)=>['id'=>(int)$row['id'],'nome'=>$row['nome'],'especialidade'=>$row['especialidade']],$rows)]);
    }
    public function availability(Request $request): never
    {
        $company=$this->company($request); $limiter=new RateLimiter(); $key=$request->ip().'|'.$company['slug'];
        if($limiter->tooMany($key,'public_availability',120,10)){Response::json(['error'=>'Muitas consultas. Aguarde alguns minutos.'],429);}
        $limiter->hit($key,'public_availability');
        $serviceId=(int)$request->input('servico_id'); $professionalId=(int)$request->input('profissional_id'); $catalog=new CatalogRepository(); $availability=new AvailabilityService(); $slots=[];
        $professionals=$professionalId?[['id'=>$professionalId,'nome'=>($catalog->professional((int)$company['id'],$professionalId)['nome']??'')]]:$catalog->serviceProfessionals((int)$company['id'],$serviceId);
        foreach($professionals as $professional){foreach($availability->slots($company,(int)$professional['id'],$serviceId,(string)$request->input('data')) as $slot){$slots[]=$slot+['profissional_id'=>(int)$professional['id'],'profissional_nome'=>$professional['nome']];}}
        usort($slots,fn($a,$b)=>[$a['value'],$a['profissional_nome']]<=>[$b['value'],$b['profissional_nome']]);
        Response::json(['data'=>$slots]);
    }
    public function store(Request $request): never
    {
        $company=$this->company($request); $redirect='/agendar/'.$company['slug'];
        $this->run($request,$redirect,function()use($request,$company):void{
            if(trim((string)$request->input('website'))!==''){throw new ValidationException(['form'=>'Não foi possível enviar o formulário.']);}
            $limiter=new RateLimiter();$key=$request->ip().'|'.$company['slug'];
            if($limiter->tooMany($key,'public_booking',5,60)){throw new ValidationException(['limite'=>'Limite de agendamentos atingido. Tente mais tarde.']);}
            (new Validator())->required($request->body,'cliente_nome','Nome',150)->required($request->body,'cliente_telefone','Telefone',30)->email($request->body,'cliente_email')->integer($request->body,'servico_id')->integer($request->body,'profissional_id')->required($request->body,'inicio','Horário',20)->throw();
            $limiter->hit($key,'public_booking');
            (new AppointmentService())->create($company,['cliente_id'=>0,'cliente_nome'=>trim((string)$request->input('cliente_nome')),'cliente_telefone'=>trim((string)$request->input('cliente_telefone')),'cliente_email'=>trim((string)$request->input('cliente_email')),'servico_id'=>(int)$request->input('servico_id'),'profissional_id'=>(int)$request->input('profissional_id'),'inicio'=>(string)$request->input('inicio'),'origem'=>'link_publico','observacoes'=>''],null,$request->ip());
            unset($_SESSION['_old']); Session::flash('public_success','Seu horário foi solicitado com sucesso.');
        });
    }
}

