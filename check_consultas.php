<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$consultas = DB::table('tb_consulta')->limit(1)->get();
foreach ($consultas as $c) {
    var_dump($c->IdProduto);
}
