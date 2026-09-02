<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Repositories\AppointmentRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\CompanyRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): string
    {
        $company = (new CompanyRepository())->find(Auth::tenantId());
        $scope = Auth::role() === 'profissional' ? Auth::professionalId() : null;
        $appointments = new AppointmentRepository();
        return view('dashboard/index', [
            'company'=>$company,
            'metrics'=>$appointments->dashboard(Auth::tenantId(), $scope, $company['timezone']),
            'upcoming'=>$appointments->upcoming(Auth::tenantId(), $scope),
            'professionals'=>(new CatalogRepository())->professionals(Auth::tenantId(), true),
            'services'=>(new CatalogRepository())->services(Auth::tenantId(), true),
        ]);
    }
}

