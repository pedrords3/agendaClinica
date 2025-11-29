<?php
include 'includes/auth.php';
redirectIfNotLoggedIn();

include 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Buscar médicos
$query = "SELECT * FROM medicos WHERE ativo = TRUE ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se for médico, só pode agendar para si mesmo
if (!isAdmin()) {
    // Buscar ID do médico baseado no email do usuário logado
    $query = "SELECT id FROM medicos WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $_SESSION['user_email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $medico = $stmt->fetch(PDO::FETCH_ASSOC);
        $medico_id_fixo = $medico['id'];
    }
}

// Processar agendamento
$form_data = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paciente_nome = $_POST['paciente_nome'];
    $paciente_telefone = $_POST['paciente_telefone'];
    $paciente_email = $_POST['paciente_email'];
    $medico_id = $_POST['medico_id'];
    $data_consulta = $_POST['data_consulta'];
    $hora_consulta = $_POST['hora_consulta'];
    $observacoes = $_POST['observacoes'];
    
    // Salvar dados do formulário para caso haja erro
    $form_data = [
        'paciente_nome' => $paciente_nome,
        'paciente_telefone' => $paciente_telefone,
        'paciente_email' => $paciente_email,
        'medico_id' => $medico_id,
        'data_consulta' => $data_consulta,
        'hora_consulta' => $hora_consulta,
        'observacoes' => $observacoes
    ];
    
    // Verificar se o horário está disponível
    $query = "SELECT id FROM consultas WHERE medico_id = :medico_id AND data_consulta = :data_consulta AND hora_consulta = :hora_consulta";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':medico_id', $medico_id);
    $stmt->bindParam(':data_consulta', $data_consulta);
    $stmt->bindParam(':hora_consulta', $hora_consulta);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = "Este horário já está agendado para este médico. Por favor, escolha outro horário.";
    } else {
        // Inserir consulta
        $query = "INSERT INTO consultas (paciente_nome, paciente_telefone, paciente_email, medico_id, data_consulta, hora_consulta, observacoes) 
                  VALUES (:paciente_nome, :paciente_telefone, :paciente_email, :medico_id, :data_consulta, :hora_consulta, :observacoes)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':paciente_nome', $paciente_nome);
        $stmt->bindParam(':paciente_telefone', $paciente_telefone);
        $stmt->bindParam(':paciente_email', $paciente_email);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':data_consulta', $data_consulta);
        $stmt->bindParam(':hora_consulta', $hora_consulta);
        $stmt->bindParam(':observacoes', $observacoes);
        
        if ($stmt->execute()) {
            $success = "Consulta agendada com sucesso!";
            // Limpar dados do formulário após sucesso
            $form_data = [];
            // Redirecionar para evitar reenvio do formulário
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $error = "Erro ao agendar consulta. Tente novamente.";
        }
    }
}

// Verificar se veio de um redirecionamento de sucesso
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Consulta agendada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consulta - Clínica Saúde Total</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-lg-10 col-md-9 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Agendar Nova Consulta</h1>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0 text-white">
                            <i class="fas fa-calendar-plus me-2 text-white"></i>Preencha os dados da consulta
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="agendamentoForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 mb-4 bg-light">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-user me-2"></i>Dados do Paciente
                                        </h5>
                                        <div class="mb-3">
                                            <label for="paciente_nome" class="form-label fw-semibold">Nome Completo *</label>
                                            <input type="text" class="form-control form-control-lg" id="paciente_nome" name="paciente_nome" 
                                                   value="<?php echo isset($form_data['paciente_nome']) ? htmlspecialchars($form_data['paciente_nome']) : ''; ?>" 
                                                   required placeholder="Digite o nome completo do paciente">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="paciente_telefone" class="form-label fw-semibold">Telefone/Celular *</label>
                                            <input type="tel" class="form-control form-control-lg" id="paciente_telefone" name="paciente_telefone" 
                                                   value="<?php echo isset($form_data['paciente_telefone']) ? htmlspecialchars($form_data['paciente_telefone']) : ''; ?>" 
                                                   required>
                                            <small class="text-muted">Formato: (11) 99999-9999</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="paciente_email" class="form-label fw-semibold">Email</label>
                                            <input type="email" class="form-control form-control-lg" id="paciente_email" name="paciente_email" 
                                                   value="<?php echo isset($form_data['paciente_email']) ? htmlspecialchars($form_data['paciente_email']) : ''; ?>" 
                                                   placeholder="paciente@exemplo.com">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="border rounded p-3 mb-4 bg-light">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-stethoscope me-2"></i>Detalhes da Consulta
                                        </h5>
                                        <div class="mb-3">
                                            <label for="medico_id" class="form-label fw-semibold">Médico *</label>
                                            <select class="form-select form-select-lg" id="medico_id" name="medico_id" required <?php echo !isAdmin() ? 'disabled' : ''; ?>>
                                                <option value="">Selecione um médico</option>
                                                <?php foreach ($medicos as $medico): ?>
                                                    <option value="<?php echo $medico['id']; ?>" 
                                                        <?php echo (isset($form_data['medico_id']) && $form_data['medico_id'] == $medico['id']) || (!isAdmin() && $medico['id'] == $medico_id_fixo) ? 'selected' : ''; ?>>
                                                        Dr. <?php echo $medico['nome']; ?> - <?php echo $medico['especialidade']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (!isAdmin()): ?>
                                                <input type="hidden" name="medico_id" value="<?php echo $medico_id_fixo; ?>">
                                                <small class="text-muted">Você está agendando para sua própria agenda</small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="data_consulta" class="form-label fw-semibold">Data da Consulta *</label>
                                            <input type="date" class="form-control form-control-lg" id="data_consulta" name="data_consulta" 
                                                   value="<?php echo isset($form_data['data_consulta']) ? htmlspecialchars($form_data['data_consulta']) : ''; ?>" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="hora_consulta" class="form-label fw-semibold">Horário *</label>
                                            <select class="form-select form-select-lg" id="hora_consulta" name="hora_consulta" required>
                                                <option value="">Selecione um horário</option>
                                                <?php
                                                // Gerar horários disponíveis
                                                for ($h = 8; $h <= 17; $h++) {
                                                    for ($m = 0; $m < 60; $m += 30) {
                                                        $horario = sprintf('%02d:%02d', $h, $m);
                                                        $selected = (isset($form_data['hora_consulta']) && $form_data['hora_consulta'] == $horario) ? 'selected' : '';
                                                        echo '<option value="' . $horario . '" ' . $selected . '>' . $horario . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted">Horários disponíveis: 08:00 às 17:30</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border rounded p-3 mb-4 bg-light">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-notes-medical me-2"></i>Informações Adicionais
                                </h5>
                                <div class="mb-3">
                                    <label for="observacoes" class="form-label fw-semibold">Observações</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                              placeholder="Informações relevantes sobre o paciente ou a consulta"><?php echo isset($form_data['observacoes']) ? htmlspecialchars($form_data['observacoes']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg me-2">
                                    <i class="fas fa-times me-2"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-check me-2"></i> Agendar Consulta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para telefone brasileiro
            const phoneInput = document.getElementById('paciente_telefone');
            if (phoneInput) {
                // Inicializar máscara de telefone
                $(phoneInput).mask('(00) 00000-0000');
                
                // Validar formato brasileiro
                phoneInput.addEventListener('blur', function() {
                    const phone = this.value.replace(/\D/g, '');
                    if (phone.length === 11) {
                        this.value = phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    } else if (phone.length === 10) {
                        this.value = phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    }
                });
            }

            // Validação de data futura
            const dataConsulta = document.getElementById('data_consulta');
            if (dataConsulta) {
                const today = new Date().toISOString().split('T')[0];
                dataConsulta.min = today;
                
                dataConsulta.addEventListener('change', function() {
                    if (this.value < today) {
                        alert('A data da consulta não pode ser anterior a hoje.');
                        this.value = today;
                    }
                });
            }

            // Verificar horários disponíveis quando médico e data forem selecionados
            const medicoSelect = document.getElementById('medico_id');
            const dataInput = document.getElementById('data_consulta');
            const horaSelect = document.getElementById('hora_consulta');
            
            function carregarHorariosDisponiveis() {
                if (medicoSelect.value && dataInput.value) {
                    // Aqui você pode implementar uma chamada AJAX para buscar horários disponíveis
                    // Por enquanto, apenas desabilitamos horários passados
                    const hoje = new Date();
                    const dataSelecionada = new Date(dataInput.value);
                    
                    if (dataSelecionada.toDateString() === hoje.toDateString()) {
                        // Se for hoje, desabilitar horários passados
                        const options = horaSelect.options;
                        for (let i = 1; i < options.length; i++) {
                            const [hora, minuto] = options[i].value.split(':').map(Number);
                            const horario = new Date();
                            horario.setHours(hora, minuto, 0, 0);
                            
                            if (horario < hoje) {
                                options[i].disabled = true;
                                if (options[i].selected) {
                                    options[i].selected = false;
                                    horaSelect.value = '';
                                }
                            } else {
                                options[i].disabled = false;
                            }
                        }
                    } else {
                        // Se for data futura, habilitar todos os horários
                        const options = horaSelect.options;
                        for (let i = 1; i < options.length; i++) {
                            options[i].disabled = false;
                        }
                    }
                }
            }
            
            if (medicoSelect) medicoSelect.addEventListener('change', carregarHorariosDisponiveis);
            if (dataInput) dataInput.addEventListener('change', carregarHorariosDisponiveis);

            // Remover quebras de linha das observações
            const form = document.getElementById('agendamentoForm');
            const observacoes = document.getElementById('observacoes');
            
            if (form && observacoes) {
                form.addEventListener('submit', function(e) {
                    if (observacoes.value) {
                        observacoes.value = observacoes.value.replace(/\r?\n/g, ' ');
                    }
                });
            }

            // Auto-focus no primeiro campo
            document.getElementById('paciente_nome').focus();
        });
    </script>

</body>
</html>