<?php
// Sidebar compartilhada
?>
<div class="col-lg-2 col-md-3 d-md-block sidebar">
    <div class="d-flex flex-column flex-shrink-0 p-3">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="agendar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'agendar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus"></i> Agendar Consulta
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li>
                <a href="medicos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'medicos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i> Gerenciar Médicos
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>