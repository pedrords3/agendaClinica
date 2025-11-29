<?php
include 'includes/auth.php';
redirectIfNotLoggedIn();

include 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Definir timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Processar mudança de status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mudar_status'])) {
    $consulta_id = $_POST['consulta_id'];
    $novo_status = $_POST['novo_status'];
    
    $query = "UPDATE consultas SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $novo_status);
    $stmt->bindParam(':id', $consulta_id);
    
    if ($stmt->execute()) {
        $success = "Status da consulta atualizado com sucesso!";
        // Recarregar a página para mostrar as mudanças
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Erro ao atualizar status da consulta.";
    }
}

// Filtros
$filtro_data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$filtro_todas = isset($_GET['todas']) ? true : false;

// Buscar consultas - com filtros
if ($filtro_todas) {
    // Todas as consultas dos próximos 3 meses
    $primeiro_dia = date('Y-m-01');
    $ultimo_dia = date('Y-m-t', strtotime('+2 months'));
    $condicao_data = "c.data_consulta BETWEEN :inicio AND :fim";
    $parametros_data = [':inicio' => $primeiro_dia, ':fim' => $ultimo_dia];
} else {
    // Apenas consultas do dia selecionado
    $condicao_data = "c.data_consulta = :data_filtro";
    $parametros_data = [':data_filtro' => $filtro_data];
}

// Se for médico, filtrar apenas suas consultas
if (!isAdmin()) {
    $query = "SELECT id FROM medicos WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $_SESSION['user_email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $medico = $stmt->fetch(PDO::FETCH_ASSOC);
        $medico_id = $medico['id'];
        
        $query = "SELECT c.*, m.nome as medico_nome, m.cor as medico_cor
                  FROM consultas c 
                  JOIN medicos m ON c.medico_id = m.id 
                  WHERE $condicao_data 
                  AND c.medico_id = :medico_id
                  ORDER BY c.data_consulta, c.hora_consulta";
        $stmt = $db->prepare($query);
        foreach ($parametros_data as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->execute();
    } else {
        $consultas = [];
    }
} else {
    // Admin vê todas as consultas
    $query = "SELECT c.*, m.nome as medico_nome, m.cor as medico_cor
              FROM consultas c 
              JOIN medicos m ON c.medico_id = m.id 
              WHERE $condicao_data 
              ORDER BY c.data_consulta, c.hora_consulta";
    $stmt = $db->prepare($query);
    foreach ($parametros_data as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
}

$consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar médicos - usar cores do banco
$query_medicos = "SELECT id, nome, cor FROM medicos WHERE ativo = TRUE ORDER BY nome";
$stmt_medicos = $db->prepare($query_medicos);
$stmt_medicos->execute();
$medicos_raw = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

// Processar médicos removendo duplicatas e garantindo cores únicas
$medicos = [];
$nomes_vistos = [];
$cores_usadas = [];

foreach ($medicos_raw as $medico) {
    if (!in_array($medico['nome'], $nomes_vistos)) {
        $nomes_vistos[] = $medico['nome'];
        
        // Se a cor já foi usada, gerar uma nova
        if (in_array($medico['cor'], $cores_usadas)) {
            $medico['cor'] = gerarCorUnica($cores_usadas);
        }
        
        $cores_usadas[] = $medico['cor'];
        $medicos[] = $medico;
    }
}

// Função para gerar cor única
function gerarCorUnica($cores_existentes) {
    $cores_disponiveis = [
        '#2c7fb8', '#e74c3c', '#27ae60', '#f39c12', '#8e44ad',
        '#16a085', '#d35400', '#2980b9', '#c0392b', '#f1c40f',
        '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#7f8c8d'
    ];
    
    foreach ($cores_disponiveis as $cor) {
        if (!in_array($cor, $cores_existentes)) {
            return $cor;
        }
    }
    
    // Se todas as cores foram usadas, gerar uma aleatória
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

// Estatísticas
$hoje = date('Y-m-d');
if (!isAdmin()) {
    $query = "SELECT COUNT(*) as total FROM consultas WHERE data_consulta = :hoje AND medico_id = :medico_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->bindParam(':medico_id', $medico_id);
    $stmt->execute();
} else {
    $query = "SELECT COUNT(*) as total FROM consultas WHERE data_consulta = :hoje";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();
}
$total_hoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

if (!isAdmin()) {
    $query = "SELECT COUNT(*) as total FROM consultas WHERE status = 'agendada' AND medico_id = :medico_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':medico_id', $medico_id);
    $stmt->execute();
} else {
    $query = "SELECT COUNT(*) as total FROM consultas WHERE status = 'agendada'";
    $stmt = $db->prepare($query);
    $stmt->execute();
}
$total_agendadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clínica Saúde Total</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <style>
        :root {
            --primary-color: #2c7fb8;
            --secondary-color: #7fcdbb;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #253237;
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
            color: #253237;
            padding: 12px 20px;
            border-radius: 0;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #edf8b1;
            color: var(--primary-color);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .card-primary {
            border-top: 4px solid var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #23679c;
        }
        
        .fc-daygrid-day-frame {
            min-height: 100px;
        }
        
        .fc-event {
            border: none !important;
            border-radius: 6px !important;
            padding: 4px 6px !important;
            margin: 2px 0 !important;
            font-size: 0.75em !important;
            font-weight: 500 !important;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .fc-event-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }
        
        /* DESTAQUE DO DIA ATUAL - BLOCO INTEIRO */
        .fc-day-today {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%) !important;
            border: 2px solid #28a745 !important;
            border-radius: 8px !important;
        }
        
        .fc-day-today .fc-daygrid-day-number {
            font-weight: bold;
            color: #155724;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
            font-size: 0.9em;
        }
        
        .fc-day-today .fc-daygrid-day-frame {
            background: transparent !important;
        }
        
        .legenda-medicos {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 15px 0;
        }
        
        .legenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85em;
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
        }
        
        .legenda-cor {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            display: inline-block;
            border: 2px solid rgba(0,0,0,0.1);
        }
        
        .table-agenda {
            font-size: 0.9em;
        }
        
        .table-agenda th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .status-badge {
            font-size: 0.8em;
            padding: 4px 8px;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.4em;
            color: #2c7fb8;
            font-weight: 600;
        }
        
        .fc .fc-button {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
        }
        
        .fc .fc-button:hover {
            background-color: #23679c;
            border-color: #23679c;
        }
        
        .fc .fc-daygrid-day-number {
            font-weight: 500;
            padding: 4px;
        }
        
        /* Tooltip customizado - SOMENTE HOVER */
        .custom-tooltip .tooltip-inner {
            background-color: #ffffff !important;
            color: #333333 !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            max-width: 300px !important;
            text-align: left !important;
            padding: 12px !important;
            font-size: 0.85em !important;
            font-weight: normal !important;
        }
        
        .custom-tooltip .tooltip-arrow::before {
            border-top-color: #ffffff !important;
        }
        
        /* Destaque suave para linha do dia atual na tabela */
        .table-agenda tbody tr.table-hoje {
            background-color: #e8f5e8 !important;
            border-left: 4px solid #28a745;
        }
        
        .table-agenda tbody tr.table-hoje td {
            background-color: #e8f5e8 !important;
            font-weight: 500;
        }
        
        /* Botões de ação */
        .btn-acao {
            padding: 4px 8px;
            font-size: 0.8em;
        }
        
        /* Filtros */
        .filtros-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .badge-filtro-ativo {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-lg-10 col-md-9 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Agenda de Consultas
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="agendar.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nova Consulta
                        </a>
                    </div>
                </div>

                <!-- Alertas -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-primary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-2x text-primary mb-3"></i>
                                <h5 class="card-title">Consultas Hoje</h5>
                                <h2 class="text-primary"><?php echo $total_hoje; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-primary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-list-alt fa-2x text-success mb-3"></i>
                                <h5 class="card-title">Total Agendadas</h5>
                                <h2 class="text-success"><?php echo $total_agendadas; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-primary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-md fa-2x text-info mb-3"></i>
                                <h5 class="card-title">Médicos Ativos</h5>
                                <h2 class="text-info"><?php echo count($medicos); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-primary h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-2x text-warning mb-3"></i>
                                <h5 class="card-title">Exibindo</h5>
                                <h2 class="text-warning"><?php echo count($consultas); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legenda dos Médicos -->
                <?php if (isAdmin() && count($medicos) > 0): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-palette me-2"></i>Legenda dos Médicos
                        </h6>
                        <div class="legenda-medicos">
                            <?php foreach ($medicos as $medico): ?>
                                <div class="legenda-item">
                                    <span class="legenda-cor" style="background-color: <?php echo $medico['cor']; ?>"></span>
                                    <span class="fw-medium"><?php echo $medico['nome']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Calendário -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar me-2"></i>
                            Agenda de Consultas
                        </h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary active" id="btnLista">Visualização Lista</button>
                            <button class="btn btn-sm btn-outline-primary" id="btnCalendario">Visualização Calendário</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="filtros-container">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="filtroData" class="form-label">Filtrar por Data</label>
                                    <input type="date" class="form-control" id="filtroData" name="data" value="<?php echo $filtro_data; ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" id="filtroTodas" name="todas" <?php echo $filtro_todas ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="filtroTodas">
                                            Mostrar todas as consultas (próximos 3 meses)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary w-100" onclick="aplicarFiltros()">
                                        <i class="fas fa-filter me-2"></i>Aplicar Filtros
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?php if ($filtro_todas): ?>
                                        Exibindo <span class="badge badge-filtro-ativo">todas as consultas</span> dos próximos 3 meses
                                    <?php else: ?>
                                        Exibindo consultas do dia <span class="badge badge-filtro-ativo"><?php echo date('d/m/Y', strtotime($filtro_data)); ?></span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <!-- Visualização Lista (PADRÃO) -->
                        <div id="visualizacaoLista">
                            <?php if (count($consultas) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-agenda">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Horário</th>
                                                <th>Paciente</th>
                                                <th>Telefone</th>
                                                <?php if (isAdmin()): ?>
                                                    <th>Médico</th>
                                                <?php endif; ?>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Criar mapa de cores para médicos
                                            $medico_cores_map = [];
                                            foreach ($medicos as $medico) {
                                                $medico_cores_map[$medico['id']] = $medico['cor'];
                                            }
                                            
                                            foreach ($consultas as $consulta): 
                                                $eh_hoje = $consulta['data_consulta'] == $hoje;
                                                $medico_id = $consulta['medico_id'];
                                                $cor_medico = isset($medico_cores_map[$medico_id]) ? $medico_cores_map[$medico_id] : '#6c757d';
                                            ?>
                                            <tr class="<?php echo $eh_hoje ? 'table-hoje' : ''; ?>">
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?>
                                                    <?php if ($eh_hoje): ?>
                                                        <span class="badge bg-success status-badge">Hoje</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-medium"><?php echo $consulta['hora_consulta']; ?></td>
                                                <td>
                                                    <strong><?php echo $consulta['paciente_nome']; ?></strong>
                                                    <?php if ($consulta['observacoes']): ?>
                                                        <br><small class="text-muted"><?php echo $consulta['observacoes']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $consulta['paciente_telefone']; ?></td>
                                                <?php if (isAdmin()): ?>
                                                    <td>
                                                        <span class="badge status-badge text-white fw-medium" style="background-color: <?php echo $cor_medico; ?>; border: 1px solid rgba(0,0,0,0.1);">
                                                            <?php echo $consulta['medico_nome']; ?>
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="badge status-badge bg-<?php 
                                                        echo $consulta['status'] == 'agendada' ? 'primary' : 
                                                             ($consulta['status'] == 'confirmada' ? 'success' : 
                                                             ($consulta['status'] == 'realizada' ? 'info' : 
                                                             ($consulta['status'] == 'cancelada' ? 'danger' : 'secondary'))); 
                                                    ?>">
                                                        <?php echo ucfirst($consulta['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($consulta['status'] == 'agendada'): ?>
                                                            <button type="button" class="btn btn-outline-success btn-acao" 
                                                                    onclick="confirmarAcao('confirmar', <?php echo $consulta['id']; ?>, '<?php echo $consulta['paciente_nome']; ?>')"
                                                                    title="Confirmar Consulta">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($consulta['status'], ['agendada', 'confirmada'])): ?>
                                                            <button type="button" class="btn btn-outline-info btn-acao" 
                                                                    onclick="confirmarAcao('realizar', <?php echo $consulta['id']; ?>, '<?php echo $consulta['paciente_nome']; ?>')"
                                                                    title="Marcar como Realizada">
                                                                <i class="fas fa-clipboard-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($consulta['status'], ['agendada', 'confirmada', 'realizada'])): ?>
                                                            <button type="button" class="btn btn-outline-danger btn-acao" 
                                                                    onclick="confirmarAcao('cancelar', <?php echo $consulta['id']; ?>, '<?php echo $consulta['paciente_nome']; ?>')"
                                                                    title="Cancelar Consulta">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Botão Editar -->
                                                        <a href="editar_consulta.php?id=<?php echo $consulta['id']; ?>" 
                                                           class="btn btn-outline-warning btn-acao"
                                                           title="Editar Consulta">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">Nenhuma consulta encontrada</h5>
                                    <p class="text-muted">
                                        <?php if ($filtro_todas): ?>
                                            Não há consultas agendadas para os próximos 3 meses.
                                        <?php else: ?>
                                            Não há consultas agendadas para <?php echo date('d/m/Y', strtotime($filtro_data)); ?>.
                                        <?php endif; ?>
                                    </p>
                                    <a href="agendar.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Agendar Nova Consulta
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Visualização Calendário (OCULTA POR PADRÃO) -->
                        <div id="visualizacaoCalendario" style="display: none;">
                            <div id="calendarioConsultas"></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMensagem">Tem certeza que deseja realizar esta ação?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" id="formConfirmacao" style="display: inline;">
                        <input type="hidden" name="consulta_id" id="confirmacaoConsultaId">
                        <input type="hidden" name="novo_status" id="confirmacaoNovoStatus">
                        <button type="submit" name="mudar_status" class="btn btn-primary">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendarioConsultas');
            
            // Dados das consultas em formato para o FullCalendar
            const events = [
                <?php 
                $medico_cores_map = [];
                foreach ($medicos as $medico) {
                    $medico_cores_map[$medico['id']] = $medico['cor'];
                }
                
                // Buscar TODAS as consultas para o calendário (sem filtro)
                $query_calendario = "SELECT c.*, m.nome as medico_nome, m.cor as medico_cor
                                   FROM consultas c 
                                   JOIN medicos m ON c.medico_id = m.id 
                                   WHERE c.data_consulta BETWEEN :inicio AND :fim 
                                   ORDER BY c.data_consulta, c.hora_consulta";
                $stmt_calendario = $db->prepare($query_calendario);
                $stmt_calendario->bindValue(':inicio', date('Y-m-01'));
                $stmt_calendario->bindValue(':fim', date('Y-m-t', strtotime('+2 months')));
                $stmt_calendario->execute();
                $consultas_calendario = $stmt_calendario->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($consultas_calendario as $consulta): 
                    $medico_id = $consulta['medico_id'];
                    $cor_medico = $consulta['medico_cor'] ?: (isset($medico_cores_map[$medico_id]) ? $medico_cores_map[$medico_id] : '#6c757d');
                    // Tratar observações - remover quebras de linha
                    $observacoes_tratadas = str_replace(["\r", "\n"], ' ', $consulta['observacoes']);
                    $observacoes_tratadas = addslashes($observacoes_tratadas);
                ?>
                {
                    title: '<?php echo $consulta['paciente_nome']; ?> - <?php echo $consulta['hora_consulta']; ?>',
                    start: '<?php echo $consulta['data_consulta']; ?>T<?php echo $consulta['hora_consulta']; ?>',
                    extendedProps: {
                        paciente: '<?php echo $consulta['paciente_nome']; ?>',
                        telefone: '<?php echo $consulta['paciente_telefone']; ?>',
                        medico: '<?php echo $consulta['medico_nome']; ?>',
                        status: '<?php echo $consulta['status']; ?>',
                        observacoes: '<?php echo $observacoes_tratadas; ?>'
                    },
                    color: '<?php echo $cor_medico; ?>',
                    textColor: 'white'
                },
                <?php endforeach; ?>
            ];

            // Configuração do calendário
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                height: 'auto',
                events: events,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,dayGridDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia'
                },
                dayMaxEvents: 4,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                eventDisplay: 'block',
                eventDidMount: function(info) {
                    // Tooltip com informações detalhadas - SOMENTE HOVER
                    const tooltipContent = `
                        <div style="min-width: 250px;">
                            <div class="fw-bold mb-2 text-primary">Detalhes da Consulta</div>
                            <div class="mb-2"><strong class="text-dark">Paciente:</strong> <span class="text-dark">${info.event.extendedProps.paciente}</span></div>
                            <div class="mb-2"><strong class="text-dark">Médico:</strong> <span class="text-dark">${info.event.extendedProps.medico}</span></div>
                            <div class="mb-2"><strong class="text-dark">Horário:</strong> <span class="text-dark">${info.event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</span></div>
                            <div class="mb-2"><strong class="text-dark">Status:</strong> 
                                <span class="badge bg-${getStatusColor(info.event.extendedProps.status)} text-white">
                                    ${info.event.extendedProps.status}
                                </span>
                            </div>
                            ${info.event.extendedProps.telefone ? `<div class="mb-2"><strong class="text-dark">Telefone:</strong> <span class="text-dark">${info.event.extendedProps.telefone}</span></div>` : ''}
                            ${info.event.extendedProps.observacoes ? `<div class="mt-3"><strong class="text-dark">Observações:</strong><br><span class="text-dark">${info.event.extendedProps.observacoes}</span></div>` : ''}
                        </div>
                    `;
                    
                    // Tooltip ao passar mouse - SIMPLES
                    new bootstrap.Tooltip(info.el, {
                        title: tooltipContent,
                        html: true,
                        placement: 'top',
                        trigger: 'hover',
                        customClass: 'custom-tooltip',
                        boundary: 'window'
                    });
                    
                    // Ajustar estilo baseado no status
                    if (info.event.extendedProps.status === 'cancelada') {
                        info.el.style.opacity = '0.6';
                        info.el.style.textDecoration = 'line-through';
                    } else if (info.event.extendedProps.status === 'realizada') {
                        info.el.style.opacity = '0.8';
                    }
                }
            });

            calendar.render();

            // Função auxiliar para cores de status
            function getStatusColor(status) {
                const cores = {
                    'agendada': 'primary',
                    'confirmada': 'success',
                    'realizada': 'info',
                    'cancelada': 'danger'
                };
                return cores[status] || 'secondary';
            }

            // Alternar entre visualização lista e calendário (INVERTIDO)
            document.getElementById('btnLista').addEventListener('click', function() {
                document.getElementById('visualizacaoLista').style.display = 'block';
                document.getElementById('visualizacaoCalendario').style.display = 'none';
                this.classList.add('active');
                document.getElementById('btnCalendario').classList.remove('active');
            });
            
            document.getElementById('btnCalendario').addEventListener('click', function() {
                document.getElementById('visualizacaoLista').style.display = 'none';
                document.getElementById('visualizacaoCalendario').style.display = 'block';
                this.classList.add('active');
                document.getElementById('btnLista').classList.remove('active');
                calendar.updateSize();
            });
        });

        // Função para aplicar filtros
        function aplicarFiltros() {
            const data = document.getElementById('filtroData').value;
            const todas = document.getElementById('filtroTodas').checked;
            
            let url = 'dashboard.php?';
            if (data) url += `data=${data}`;
            if (todas) url += `&todas=1`;
            
            window.location.href = url;
        }

        // Função para confirmar ações
        function confirmarAcao(acao, consultaId, pacienteNome) {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
            const modalTitulo = document.getElementById('modalTitulo');
            const modalMensagem = document.getElementById('modalMensagem');
            const consultaIdInput = document.getElementById('confirmacaoConsultaId');
            const novoStatusInput = document.getElementById('confirmacaoNovoStatus');
            
            let titulo = '';
            let mensagem = '';
            let status = '';
            
            switch(acao) {
                case 'confirmar':
                    titulo = 'Confirmar Consulta';
                    mensagem = `Tem certeza que deseja confirmar a consulta de <strong>${pacienteNome}</strong>?`;
                    status = 'confirmada';
                    break;
                case 'realizar':
                    titulo = 'Marcar como Realizada';
                    mensagem = `Tem certeza que deseja marcar a consulta de <strong>${pacienteNome}</strong> como realizada?`;
                    status = 'realizada';
                    break;
                case 'cancelar':
                    titulo = 'Cancelar Consulta';
                    mensagem = `Tem certeza que deseja cancelar a consulta de <strong>${pacienteNome}</strong>?`;
                    status = 'cancelada';
                    break;
            }
            
            modalTitulo.textContent = titulo;
            modalMensagem.innerHTML = mensagem;
            consultaIdInput.value = consultaId;
            novoStatusInput.value = status;
            
            modal.show();
        }
    </script>
</body>
</html>