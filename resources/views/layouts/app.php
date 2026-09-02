<?php $current=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:''; $success=flash('success'); $error=flash('error'); $errors=flash('errors')?:[]; ?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?= e(App\Core\Csrf::token()) ?>">
    <title><?= e($pageTitle??'Painel') ?> — <?= e(auth()['empresa_nome']??'Agenda Fácil') ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body data-base-url="<?= e(rtrim(url('/'),'/')) ?>" style="--brand:<?= e(auth()['empresa_cor']??'#5b5bd6') ?>">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/dashboard')) ?>"><span class="brand-mark"><i class="bi bi-calendar2-check"></i></span><span><strong><?= e(auth()['empresa_nome']??'Agenda Fácil') ?></strong><small>Gestão de agenda</small></span></a>
        <nav class="nav-list" aria-label="Navegação principal">
            <a class="<?= str_contains($current,'/dashboard')?'active':'' ?>" href="<?= e(url('/dashboard')) ?>"><i class="bi bi-grid-1x2"></i>Visão geral</a>
            <a class="<?= str_contains($current,'/agendamentos')?'active':'' ?>" href="<?= e(url('/agendamentos/novo')) ?>"><i class="bi bi-calendar-plus"></i>Novo agendamento</a>
            <a class="<?= str_contains($current,'/clientes')?'active':'' ?>" href="<?= e(url('/clientes')) ?>"><i class="bi bi-people"></i>Clientes</a>
            <?php if(App\Core\Auth::canManage()): ?>
            <span class="nav-label">Operação</span>
            <a class="<?= str_contains($current,'/profissionais')?'active':'' ?>" href="<?= e(url('/profissionais')) ?>"><i class="bi bi-person-badge"></i>Profissionais</a>
            <a class="<?= str_contains($current,'/servicos')?'active':'' ?>" href="<?= e(url('/servicos')) ?>"><i class="bi bi-stars"></i>Serviços</a>
            <?php endif; ?>
            <a class="<?= str_contains($current,'/horarios')?'active':'' ?>" href="<?= e(url('/horarios')) ?>"><i class="bi bi-clock"></i>Horários</a>
            <a class="<?= str_contains($current,'/bloqueios')?'active':'' ?>" href="<?= e(url('/bloqueios')) ?>"><i class="bi bi-calendar-x"></i>Bloqueios</a>
            <?php if(App\Core\Auth::role()==='proprietario'): ?>
            <span class="nav-label">Administração</span>
            <a class="<?= str_contains($current,'/usuarios')?'active':'' ?>" href="<?= e(url('/usuarios')) ?>"><i class="bi bi-shield-check"></i>Usuários</a>
            <a class="<?= str_contains($current,'/configuracoes')?'active':'' ?>" href="<?= e(url('/configuracoes')) ?>"><i class="bi bi-sliders"></i>Configurações</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr(auth()['nome']??'U',0,1))) ?></span><span><strong><?= e(auth()['nome']??'') ?></strong><small><?= e(ucfirst(auth()['perfil']??'')) ?></small></span><form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button class="icon-button" title="Sair" aria-label="Sair"><i class="bi bi-box-arrow-right"></i></button></form></div>
    </aside>
    <div class="app-main">
        <header class="topbar"><button class="icon-button menu-toggle" type="button" data-sidebar-toggle aria-label="Abrir menu"><i class="bi bi-list"></i></button><div><strong><?= e($pageTitle??'Painel') ?></strong><small><?= e(date('l, d/m/Y')) ?></small></div><button class="icon-button" type="button" data-theme-toggle title="Alternar tema"><i class="bi bi-moon-stars"></i></button></header>
        <main class="content">
            <?php if($success): ?><div class="notice success"><i class="bi bi-check-circle"></i><span><?= e($success) ?></span><button data-dismiss>&times;</button></div><?php endif; ?>
            <?php if($error): ?><div class="notice error"><i class="bi bi-exclamation-circle"></i><span><?= e($error) ?></span><button data-dismiss>&times;</button></div><?php endif; ?>
            <?php if($errors): ?><div class="notice error"><i class="bi bi-exclamation-circle"></i><span><?= e(implode(' ',array_values($errors))) ?></span><button data-dismiss>&times;</button></div><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<div class="sidebar-backdrop" data-sidebar-toggle></div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body></html>

