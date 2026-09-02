<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($status) ?> — Agenda Fácil</title></head>
<body style="font-family:system-ui;background:#f5f6fa;color:#27273a;display:grid;place-items:center;min-height:100vh;margin:0">
<main style="max-width:560px;padding:2rem;text-align:center"><div style="font-size:4rem;font-weight:800;color:#5b5bd6"><?= e($status) ?></div><h1><?= e($message) ?></h1><p><a href="<?= e(url(auth() ? '/dashboard' : '/login')) ?>">Voltar</a></p></main>
</body></html>

