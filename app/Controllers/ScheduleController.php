<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CatalogRepository;
use App\Repositories\CompanyRepository;
use App\Services\AuditService;
use App\Services\ScheduleService;

final class ScheduleController extends Controller
{
    public function index(Request $request): string
    {
        $professionals=(new CatalogRepository())->professionals(Auth::tenantId(),true);
        $selected=Auth::role()==='profissional'?Auth::professionalId():(int)$request->input('profissional_id',($professionals[0]['id']??0));
        if($selected && !(new CatalogRepository())->professional(Auth::tenantId(),$selected)){Response::abort(404);}
        $service=new ScheduleService();
        return view('schedules/index',['professionals'=>$professionals,'selected'=>$selected,'periods'=>$selected?$service->periods(Auth::tenantId(),$selected):[]]);
    }
    public function store(Request $request): never
    {
        $professional=Auth::role()==='profissional'?(int)Auth::professionalId():(int)$request->input('profissional_id');
        $this->run($request,'/horarios?profissional_id='.$professional,function()use($request,$professional):void{
            $id=(new ScheduleService())->add(Auth::tenantId(),$professional,(int)$request->input('dia_semana'),(string)$request->input('hora_inicio'),(string)$request->input('hora_fim'));
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'horario.criado','horario_profissional',$id,[],$request->ip());
            Session::flash('success','Período adicionado.');
        });
    }
    public function remove(Request $request): never
    {
        $scope=Auth::role()==='profissional'?Auth::professionalId():null;
        (new ScheduleService())->remove(Auth::tenantId(),(int)($request->params['id']??0),$scope);
        Session::flash('success','Período removido.'); Response::redirect('/horarios');
    }
    public function blocks(Request $request): string
    {
        $scope=Auth::role()==='profissional'?Auth::professionalId():null;
        return view('blocks/index',['company'=>(new CompanyRepository())->find(Auth::tenantId()),'professionals'=>(new CatalogRepository())->professionals(Auth::tenantId(),true),'blocks'=>(new ScheduleService())->blocks(Auth::tenantId(),$scope),'scope'=>$scope]);
    }
    public function storeBlock(Request $request): never
    {
        $this->run($request,'/bloqueios',function()use($request):void{
            $company=(new CompanyRepository())->find(Auth::tenantId());
            $id=(new ScheduleService())->addBlock($company,(int)$request->input('profissional_id'),(string)$request->input('inicio'),(string)$request->input('fim'),(string)$request->input('motivo'),(bool)$request->input('dia_inteiro'),Auth::id(),Auth::role()==='profissional'?Auth::professionalId():null);
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'bloqueio.criado','bloqueio_agenda',$id,[],$request->ip());
            Session::flash('success','Bloqueio adicionado.');
        });
    }
}

