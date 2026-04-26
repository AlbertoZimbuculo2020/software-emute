<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::take(10)->get();
foreach ($users as $u) {
    echo "USER: " . $u->NOME_UTILIZADOR . "\n";
    echo "HASH: " . $u->SENHA . "\n";
    echo "LENGTH: " . strlen($u->SENHA) . "\n\n";
}
