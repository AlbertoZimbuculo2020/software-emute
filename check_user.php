<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('NOME_UTILIZADOR', 'EMUTE')->first();
if ($user) {
    echo "USER: " . $user->NOME_UTILIZADOR . "\n";
    echo "HASH: " . $user->SENHA . "\n";
    echo "LENGTH: " . strlen($user->SENHA) . "\n";

    $passwords = ['admin123'];
    foreach ($passwords as $p) {
        $h_plain = hash('sha512', $p);
        $h_utf16le = hash('sha512', mb_convert_encoding($p, 'UTF-16LE'));
        
        echo "Testing '$p' PLAIN: " . ($h_plain === strtolower($user->SENHA) ? "MATCH!" : "No match") . " ($h_plain)\n";
        echo "Testing '$p' UTF-16LE: " . ($h_utf16le === strtolower($user->SENHA) ? "MATCH!" : "No match") . " ($h_utf16le)\n";
    }
} else {
    echo "User not found\n";
}
