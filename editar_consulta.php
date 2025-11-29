<?php
include 'includes/auth.php';
redirectIfNotLoggedIn();

include 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Buscar consulta para edição
$consulta_id = $_GET['id'] ?? null;

if (!$consulta_id) {
    header("Location: dashboard.php");
    exit();
}

// Buscar dados da consulta
$query = "SELECT c.*, m.nome as medico_nome 
          FROM consultas c 
          JOIN medicos m ON c.medico_id = m.id 
          WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $consulta_id);
$stmt->execute();
$consulta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consulta) {
    header("Location: dashboard.php");
    exit();
}

// Buscar médicos
$query_medicos = "SELECT id, nome FROM medicos WHERE ativo = TRUE ORDER BY nome";
$stmt_medicos = $db->prepare($query_medicos);
$stmt_medicos->execute();
$medicos = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paciente_nome = $_POST['paciente_nome'];
    $paciente_telefone = $_POST['paciente_telefone'];
    $paciente_email = $_POST['paciente_email'];
    $medico_id = $_POST['medico_id'];
    $data_consulta = $_POST['data_consulta'];
    $hora_consulta = $_POST['hora_consulta'];
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'];
    
    // Verificar conflito de horário (exceto para a própria consulta)
    $query = "SELECT id FROM consultas 
              WHERE medico_id = :medico_id 
              AND data_consulta = :data_consulta 
              AND hora_consulta = :hora_consulta 
              AND id != :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':medico_id', $medico_id);
    $stmt->bindParam(':data_consulta', $data_consulta);
    $stmt->bindParam(':hora_consulta', $hora_consulta);
    $stmt->bindParam(':id', $consulta_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = "Este horário já está agendado para este médico. Por favor, escolha outro horário.";
    } else {
        // Atualizar consulta
        $query = "UPDATE consultas SET 
                  paciente_nome = :paciente_nome,
                  paciente_telefone = :paciente_telefone,
                  paciente_email = :paciente_email,
                  medico_id = :medico_id,
                  data_consulta = :data_consulta,
                  hora_consulta = :hora_consulta,
                  status = :status,
                  observacoes = :observacoes
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':paciente_nome', $paciente_nome);
        $stmt->bindParam(':paciente_telefone', $paciente_telefone);
        $stmt->bindParam(':paciente_email', $paciente_email);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':data_consulta', $data_consulta);
        $stmt->bindParam(':hora_consulta', $hora_consulta);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $consulta_id);
        
        if ($stmt->execute()) {
            $success = "Consulta atualizada com sucesso!";
        } else {
            $error = "Erro ao atualizar consulta. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Consulta - Clínica Saúde Total</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-lg-10 col-md-9 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-edit me-2"></i>
                        Editar Consulta
                    </h1>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Dados do Paciente</h5>
                                    <div class="mb-3">
                                        <label for="paciente_nome" class="form-label">Nome Completo *</label>
                                        <input type="text" class="form-control" id="paciente_nome" name="paciente_nome" 
                                               value="<?php echo htmlspecialchars($consulta['paciente_nome']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="paciente_telefone" class="form-label">Telefone *</label>
                                        <input type="tel" class="form-control" id="paciente_telefone" name="paciente_telefone" 
                                               value="<?php echo htmlspecialchars($consulta['paciente_telefone']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="paciente_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="paciente_email" name="paciente_email" 
                                               value="<?php echo htmlspecialchars($consulta['paciente_email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Detalhes da Consulta</h5>
                                    <div class="mb-3">
                                        <label for="medico_id" class="form-label">Médico *</label>
                                        <select class="form-select" id="medico_id" name="medico_id" required>
                                            <?php foreach ($medicos as $medico): ?>
                                                <option value="<?php echo $medico['id']; ?>" 
                                                    <?php echo $medico['id'] == $consulta['medico_id'] ? 'selected' : ''; ?>>
                                                    <?php echo $medico['nome']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="data_consulta" class="form-label">Data da Consulta *</label>
                                        <input type="date" class="form-control" id="data_consulta" name="data_consulta" 
                                               value="<?php echo $consulta['data_consulta']; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="hora_consulta" class="form-label">Horário *</label>
                                        <select class="form-select" id="hora_consulta" name="hora_consulta" required>
                                            <?php
                                            for ($h = 8; $h <= 17; $h++) {
                                                for ($m = 0; $m < 60; $m += 30) {
                                                    $horario = sprintf('%02d:%02d', $h, $m);
                                                    $selected = $horario == $consulta['hora_consulta'] ? 'selected' : '';
                                                    echo "<option value='$horario' $selected>$horario</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="agendada" <?php echo $consulta['status'] == 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                                            <option value="confirmada" <?php echo $consulta['status'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                            <option value="realizada" <?php echo $consulta['status'] == 'realizada' ? 'selected' : ''; ?>>Realizada</option>
                                            <option value="cancelada" <?php echo $consulta['status'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($consulta['observacoes']); ?></textarea>
                                <small class="text-muted">Não use quebras de linha (enter)</small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Atualizar Consulta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remover quebras de linha das observações
        document.getElementById('observacoes').addEventListener('input', function(e) {
            this.value = this.value.replace(/\r?\n/g, ' ');
        });
    </script>
</body>
</html>