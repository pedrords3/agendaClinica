<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento - Clínica Saúde Total</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c7fb8;
            --secondary-color: #7fcdbb;
            --accent-color: #edf8b1;
            --dark-color: #253237;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background-color: white;
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .sidebar .nav-link {
            color: var(--dark-color);
            padding: 12px 20px;
            border-radius: 0;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-primary {
            border-top: 4px solid var(--primary-color);
        }
        
        .card-success {
            border-top: 4px solid #28a745;
        }
        
        .card-warning {
            border-top: 4px solid #ffc107;
        }
        
        .card-info {
            border-top: 4px solid var(--secondary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #23679c;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .calendar-day {
            border: 1px solid #e9ecef;
            height: 120px;
            padding: 5px;
            overflow-y: auto;
        }
        
        .calendar-day.today {
            background-color: var(--accent-color);
        }
        
        .appointment-badge {
            font-size: 0.7rem;
            margin-bottom: 2px;
            display: block;
        }
        
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-clinic-medical me-2"></i>
                Clínica Saúde Total
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> Dr. Silva
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 d-md-block sidebar collapse">
                <div class="d-flex flex-column flex-shrink-0 p-3">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-calendar-plus"></i> Agendar Consulta
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-list-alt"></i> Consultas
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-user-injured"></i> Pacientes
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-user-md"></i> Médicos
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-chart-bar"></i> Relatórios
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-cog"></i> Configurações
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <main class="col-lg-10 col-md-9 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">Hoje</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Semana</button>
                            <button type="button" class="btn btn-sm btn-outline-primary">Mês</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Nova Consulta
                        </button>
                    </div>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-primary h-100">
                            <div class="card-body stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <h5 class="card-title">Consultas Hoje</h5>
                                <h2 class="text-primary">12</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-success h-100">
                            <div class="card-body stat-card">
                                <i class="fas fa-user-injured"></i>
                                <h5 class="card-title">Pacientes</h5>
                                <h2 class="text-success">248</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-warning h-100">
                            <div class="card-body stat-card">
                                <i class="fas fa-user-md"></i>
                                <h5 class="card-title">Médicos</h5>
                                <h2 class="text-warning">8</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-info h-100">
                            <div class="card-body stat-card">
                                <i class="fas fa-clock"></i>
                                <h5 class="card-title">Em Espera</h5>
                                <h2 class="text-info">3</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Próximas Consultas -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Próximas Consultas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Paciente</th>
                                                <th>Horário</th>
                                                <th>Médico</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="patient-avatar me-3">MS</div>
                                                        <div>Maria Santos</div>
                                                    </div>
                                                </td>
                                                <td>14:00 - 14:30</td>
                                                <td>Dr. Silva</td>
                                                <td><span class="badge bg-success">Confirmada</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="patient-avatar me-3">JO</div>
                                                        <div>João Oliveira</div>
                                                    </div>
                                                </td>
                                                <td>14:30 - 15:00</td>
                                                <td>Dra. Costa</td>
                                                <td><span class="badge bg-warning">Em Espera</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="patient-avatar me-3">AP</div>
                                                        <div>Ana Pereira</div>
                                                    </div>
                                                </td>
                                                <td>15:00 - 15:30</td>
                                                <td>Dr. Silva</td>
                                                <td><span class="badge bg-success">Confirmada</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="patient-avatar me-3">CR</div>
                                                        <div>Carlos Rodrigues</div>
                                                    </div>
                                                </td>
                                                <td>16:00 - 16:30</td>
                                                <td>Dra. Costa</td>
                                                <td><span class="badge bg-secondary">Agendada</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Calendário -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Calendário</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                                    <span class="mx-2" id="currentMonth">Junho 2023</span>
                                    <button class="btn btn-sm btn-outline-primary" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col text-center text-primary">Dom</div>
                                    <div class="col text-center">Seg</div>
                                    <div class="col text-center">Ter</div>
                                    <div class="col text-center">Qua</div>
                                    <div class="col text-center">Qui</div>
                                    <div class="col text-center">Sex</div>
                                    <div class="col text-center text-primary">Sáb</div>
                                </div>
                                <div class="row" id="calendarDays">
                                    <!-- Dias do calendário serão gerados via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Atividades Recentes -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Atividades Recentes</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline">
                                    <div class="activity-item d-flex">
                                        <div class="activity-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-calendar-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>Nova consulta agendada</h6>
                                            <p class="mb-1">Maria Santos - 14:00</p>
                                            <small class="text-muted">Há 10 minutos</small>
                                        </div>
                                    </div>
                                    <div class="activity-item d-flex mt-3">
                                        <div class="activity-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>Consulta confirmada</h6>
                                            <p class="mb-1">João Oliveira - 14:30</p>
                                            <small class="text-muted">Há 30 minutos</small>
                                        </div>
                                    </div>
                                    <div class="activity-item d-flex mt-3">
                                        <div class="activity-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user-injured"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>Novo paciente registrado</h6>
                                            <p class="mb-1">Carlos Rodrigues</p>
                                            <small class="text-muted">Há 1 hora</small>
                                        </div>
                                    </div>
                                    <div class="activity-item d-flex mt-3">
                                        <div class="activity-icon bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-calendar-times"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>Consulta cancelada</h6>
                                            <p class="mb-1">Ana Lima - 15:30</p>
                                            <small class="text-muted">Há 2 horas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estatísticas Rápidas -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Estatísticas Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Consultas por Especialidade</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Clínica Geral</span>
                                        <span>42%</span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar" role="progressbar" style="width: 42%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Cardiologia</span>
                                        <span>28%</span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 28%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Ortopedia</span>
                                        <span>15%</span>
                                    </div>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 15%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Pediatria</span>
                                        <span>10%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 10%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simulação de dados para o calendário
        document.addEventListener('DOMContentLoaded', function() {
            // Gerar calendário
            generateCalendar();
            
            // Navegação do calendário
            document.getElementById('prevMonth').addEventListener('click', function() {
                alert('Mês anterior - funcionalidade será implementada com backend');
            });
            
            document.getElementById('nextMonth').addEventListener('click', function() {
                alert('Próximo mês - funcionalidade será implementada com backend');
            });
        });
        
        function generateCalendar() {
            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';
            
            // Dias do mês (simulação)
            const daysInMonth = 30;
            const firstDay = 3; // Quarta-feira (0 = Domingo, 1 = Segunda, etc.)
            
            // Dias vazios no início
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'col calendar-day';
                calendarDays.appendChild(emptyDay);
            }
            
            // Dias do mês
            for (let i = 1; i <= daysInMonth; i++) {
                const day = document.createElement('div');
                day.className = 'col calendar-day';
                
                // Marcar o dia atual (simulação: dia 15)
                if (i === 15) {
                    day.classList.add('today');
                }
                
                day.innerHTML = `<div class="fw-bold">${i}</div>`;
                
                // Adicionar algumas consultas de exemplo
                if (i === 15) {
                    day.innerHTML += `<span class="badge appointment-badge bg-primary">Maria - 14:00</span>`;
                    day.innerHTML += `<span class="badge appointment-badge bg-success">João - 14:30</span>`;
                }
                
                if (i === 16) {
                    day.innerHTML += `<span class="badge appointment-badge bg-info">Ana - 10:00</span>`;
                }
                
                if (i === 20) {
                    day.innerHTML += `<span class="badge appointment-badge bg-warning">Carlos - 16:00</span>`;
                }
                
                calendarDays.appendChild(day);
            }
        }
    </script>
</body>
</html>