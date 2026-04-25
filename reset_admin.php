<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$login = 'EMUTE';
$password = 'admin123';

$user = User::where('NOME_UTILIZADOR', $login)->first();

if ($user) {
    $user->SENHA = hash('sha512', $password);
    $user->save();
    echo "Password for user '$login' reset to '$password' (SHA-512).\n";
} else {
    echo "User '$login' not found.\n";
}
