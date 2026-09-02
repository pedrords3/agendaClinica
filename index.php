<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap/app.php';
$router = require __DIR__ . '/routes/web.php';
$router->dispatch(App\Core\Request::capture());
