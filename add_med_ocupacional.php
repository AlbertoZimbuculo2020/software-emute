<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$exists = DB::table('tb_consulta')->where('Descricao', 'MEDICINA OCUPACIONAL')->exists();

if (!$exists) {
    DB::table('tb_consulta')->insert([
        'Codigo' => 'CNT22',
        'IdProduto' => null,
        'Descricao' => 'MEDICINA OCUPACIONAL',
        'Valor' => 15000.00,
        'Estado' => 'Ativo'
    ]);
    echo "Consulta 'MEDICINA OCUPACIONAL' adicionada com sucesso!\n";
} else {
    echo "A consulta 'MEDICINA OCUPACIONAL' já existe.\n";
}
