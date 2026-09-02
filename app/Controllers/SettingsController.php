<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\ValidationException;
use App\Core\Validator;
use App\Repositories\CompanyRepository;
use App\Services\AuditService;

final class SettingsController extends Controller
{
    public function edit(Request $request): string { return view('settings/edit',['company'=>(new CompanyRepository())->find(Auth::tenantId())]); }
    public function update(Request $request): never
    {
        $this->run($request,'/configuracoes',function()use($request):void{
            (new Validator())->required($request->body,'nome','Razão social',150)->required($request->body,'nome_fantasia','Nome fantasia',150)->required($request->body,'segmento','Segmento',100)->email($request->body,'email')->integer($request->body,'intervalo_padrao',5,120)->integer($request->body,'antecedencia_minima_minutos',0,43200)->integer($request->body,'maximo_dias_futuros',1,730)->throw();
            $timezone=(string)$request->input('timezone'); try{new \DateTimeZone($timezone);}catch(\Throwable){throw new ValidationException(['timezone'=>'Fuso horário inválido.']);}
            $color=preg_match('/^#[0-9a-fA-F]{6}$/',(string)$request->input('cor_principal'))?$request->input('cor_principal'):'#5b5bd6';
            $data=['nome'=>trim((string)$request->input('nome')),'nome_fantasia'=>trim((string)$request->input('nome_fantasia')),'segmento'=>trim((string)$request->input('segmento')),'telefone'=>trim((string)$request->input('telefone')),'whatsapp'=>trim((string)$request->input('whatsapp')),'email'=>trim((string)$request->input('email')),'endereco'=>trim((string)$request->input('endereco')),'cidade'=>trim((string)$request->input('cidade')),'estado'=>strtoupper(trim((string)$request->input('estado'))),'cor_principal'=>$color,'timezone'=>$timezone,'intervalo_padrao'=>(int)$request->input('intervalo_padrao'),'antecedencia_minima_minutos'=>(int)$request->input('antecedencia_minima_minutos'),'maximo_dias_futuros'=>(int)$request->input('maximo_dias_futuros'),'permitir_agendamento_publico'=>$request->input('permitir_agendamento_publico')?1:0,'confirmar_automaticamente'=>$request->input('confirmar_automaticamente')?1:0];
            (new CompanyRepository())->update(Auth::tenantId(),$data);
            $_SESSION['auth']['empresa_nome']=$data['nome_fantasia']; $_SESSION['auth']['empresa_cor']=$color;
            (new AuditService())->record(Auth::tenantId(),Auth::id(),'empresa.configuracoes_alteradas','empresa',Auth::tenantId(),[],$request->ip());
            Session::flash('success','Configurações atualizadas.');
        });
    }
}

