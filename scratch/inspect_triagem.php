<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "--- tb_triagem ---\n";
print_r(DB::select('DESCRIBE tb_triagem'));
echo "\n--- tb_agendamento ---\n";
print_r(DB::select('DESCRIBE tb_agendamento'));
