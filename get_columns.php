<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$m = DB::select("SHOW COLUMNS FROM tb_atos_medicos");
$e = DB::select("SHOW COLUMNS FROM tb_atos_enfermagem");
print_r(['medicos' => $m, 'enfermagem' => $e]);
