<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\AppointmentRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\ClientRepository;
use App\Repositories\CompanyRepository;
use App\Services\AppointmentService;

final class AppointmentController extends Controller
{
    public function create(Request $request): string
    {
        $scope=Auth::role()==='profissional'?Auth::professionalId():null;
        return view('appointments/create',['company'=>(new CompanyRepository())->find(Auth::tenantId()),'clients'=>(new ClientRepository())->all(Auth::tenantId(),'',$scope),'professionals'=>(new CatalogRepository())->professionals(Auth::tenantId(),true),'services'=>(new CatalogRepository())->services(Auth::tenantId(),true)]);
    }
    public function store(Request $request): never
    {
        $this->run($request,'/agendamentos/novo',function()use($request):void{
            $professionalId=Auth::role()==='profissional'?(int)Auth::professionalId():(int)$request->input('profissional_id');
            (new Validator())->integer(['profissional_id'=>$professionalId],'profissional_id')->integer($request->body,'servico_id')->required($request->body,'inicio','Horário',20)->oneOf($request->body,'origem',['interno','telefone','whatsapp','recepcao'])->oneOf($request->body,'status',['pendente','confirmado'])->throw();
            if(!(int)$request->input('cliente_id')){(new Validator())->required($request->body,'cliente_nome','Nome do cliente',150)->required($request->body,'cliente_telefone','Telefone',30)->email($request->body,'cliente_email')->throw();}
            if((int)$request->input('cliente_id') && !(new ClientRepository())->find(Auth::tenantId(),(int)$request->input('cliente_id'),Auth::role()==='profissional'?Auth::professionalId():null)){throw new \App\Core\ValidationException(['cliente_id'=>'Cliente inválido ou não autorizado.']);}
            (new AppointmentService())->create((new CompanyRepository())->find(Auth::tenantId()),[
                'cliente_id'=>(int)$request->input('cliente_id'),'cliente_nome'=>trim((string)$request->input('cliente_nome')),'cliente_telefone'=>trim((string)$request->input('cliente_telefone')),'cliente_email'=>trim((string)$request->input('cliente_email')),
                'servico_id'=>(int)$request->input('servico_id'),'profissional_id'=>$professionalId,'inicio'=>(string)$request->input('inicio'),'origem'=>(string)$request->input('origem','interno'),'status'=>(string)$request->input('status','pendente'),'observacoes'=>trim((string)$request->input('observacoes')),
            ],Auth::id(),$request->ip());
            Session::flash('success','Agendamento criado com segurança.');
        });
    }
    public function show(Request $request): string
    {
        $appointment=(new AppointmentRepository())->findAuthorized(Auth::tenantId(),(int)($request->params['id']??0),Auth::role()==='profissional'?Auth::professionalId():null);
        if(!$appointment){Response::abort(404);}
        return view('appointments/show',['appointment'=>$appointment,'company'=>(new CompanyRepository())->find(Auth::tenantId())]);
    }
    public function status(Request $request): never
    {
        $id=(int)($request->params['id']??0);
        $this->run($request,'/agendamentos/'.$id,function()use($request,$id):void{
            (new AppointmentService())->updateStatus(Auth::tenantId(),$id,(string)$request->input('status'),Auth::id(),Auth::role()==='profissional'?Auth::professionalId():null,trim((string)$request->input('motivo')),$request->ip());
            Session::flash('success','Status atualizado.');
        });
    }
}
