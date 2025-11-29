<?php
include 'includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

include 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['adicionar_medico'])) {
        $nome = $_POST['nome'];
        $especialidade = $_POST['especialidade'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        
        // Salvar dados do formulário para caso haja erro
        $form_data = [
            'nome' => $nome,
            'especialidade' => $especialidade,
            'email' => $email,
            'telefone' => $telefone
        ];
        
        // Verificar se o email já existe
        $query = "SELECT id FROM medicos WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Este email já está cadastrado para outro médico.";
        } else {
            $query = "INSERT INTO medicos (nome, especialidade, email, telefone) VALUES (:nome, :especialidade, :email, :telefone)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':especialidade', $especialidade);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefone', $telefone);
            
            if ($stmt->execute()) {
                $success = "Médico adicionado com sucesso!";
                // Limpar dados do formulário após sucesso
                $form_data = [];
                // Redirecionar para evitar reenvio do formulário
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                $error = "Erro ao adicionar médico. Tente novamente.";
            }
        }
    }
}

// Verificar se veio de um redirecionamento de sucesso
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Médico adicionado com sucesso!";
}

// Buscar médicos
$query = "SELECT * FROM medicos ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Médicos - Clínica Saúde Total</title>
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
                    <h1 class="h2">Gerenciar Médicos</h1>
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

                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-user-plus me-2 text-white"></i>Adicionar Novo Médico
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="medicoForm">
                                    <div class="mb-3">
                                        <label for="nome" class="form-label fw-semibold">Nome Completo *</label>
                                        <input type="text" class="form-control form-control-lg" id="nome" name="nome" 
                                               value="<?php echo isset($form_data['nome']) ? htmlspecialchars($form_data['nome']) : ''; ?>" 
                                               required placeholder="Digite o nome completo do médico">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="especialidade" class="form-label fw-semibold">Especialidade *</label>
                                        <input type="text" class="form-control form-control-lg" id="especialidade" name="especialidade" 
                                               value="<?php echo isset($form_data['especialidade']) ? htmlspecialchars($form_data['especialidade']) : ''; ?>" 
                                               required placeholder="Ex: Cardiologia, Pediatria, etc.">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Email *</label>
                                        <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                               value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" 
                                               required placeholder="medico@clinica.com">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefone" class="form-label fw-semibold">Telefone</label>
                                        <input type="tel" class="form-control form-control-lg" id="telefone" name="telefone" 
                                               value="<?php echo isset($form_data['telefone']) ? htmlspecialchars($form_data['telefone']) : ''; ?>" 
                                               placeholder="(11) 99999-9999">
                                        <small class="text-muted">Formato: (11) 99999-9999</small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="adicionar_medico" class="btn btn-primary btn-lg">
                                            <i class="fas fa-user-plus me-2"></i> Adicionar Médico
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-user-md me-2 text-white"></i>Médicos Cadastrados
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($medicos) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Especialidade</th>
                                                    <th>Email</th>
                                                    <th>Telefone</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($medicos as $medico): ?>
                                                <tr>
                                                    <td class="fw-semibold">Dr. <?php echo $medico['nome']; ?></td>
                                                    <td><span class="badge bg-info"><?php echo $medico['especialidade']; ?></span></td>
                                                    <td><?php echo $medico['email']; ?></td>
                                                    <td><?php echo $medico['telefone'] ?: 'Não informado'; ?></td>
                                                    <td>
                                                        <?php if ($medico['ativo']): ?>
                                                            <span class="badge bg-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Nenhum médico cadastrado</h5>
                                        <p class="text-muted">Adicione o primeiro médico usando o formulário ao lado.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
            const phoneInput = document.getElementById('telefone');
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

            // Validação de email
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const email = this.value;
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailPattern.test(email)) {
                        this.classList.add('is-invalid');
                        // Adicionar mensagem de erro se não existir
                        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'Por favor, insira um email válido.';
                            this.parentNode.appendChild(errorDiv);
                        }
                    } else {
                        this.classList.remove('is-invalid');
                        // Remover mensagem de erro se existir
                        const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                        if (errorDiv) {
                            errorDiv.remove();
                        }
                    }
                });
            }

            // Auto-focus no primeiro campo
            document.getElementById('nome').focus();
        });
    </script>

</body>
</html>