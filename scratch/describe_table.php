<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select('DESCRIBE tb_agendamento');
foreach ($columns as $c) {
    echo $c->Field . ' (' . $c->Type . ')' . PHP_EOL;
}
