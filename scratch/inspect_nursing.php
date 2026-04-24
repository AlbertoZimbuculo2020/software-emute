<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use Illuminate\Support\Facades\DB;

echo "--- tb_atos_enfermagem ---\n";
foreach(DB::select('DESCRIBE tb_atos_enfermagem') as $f) echo $f->Field . " (" . $f->Type . ")\n";

echo "\n--- tb_encaminhar ---\n";
foreach(DB::select('DESCRIBE tb_encaminhar') as $f) echo $f->Field . " (" . $f->Type . ")\n";
