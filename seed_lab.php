<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$pats = ['MARIA SILVA', 'JOAO MANUEL', 'ANA PAULA', 'CARLOS ALBERTO', 'ZILDA BUMBA'];
$medico = DB::table('tb_tipoentidade')->where('TipoEntidade', 'Medico')->first();
$medId = $medico ? $medico->Codigo : 'MED001';

foreach ($pats as $i => $name) {
    $code = 'PC00' . ($i + 5);
    DB::table('tb_tipoentidade')->updateOrInsert(['Codigo' => $code], [
        'IdEntidade' => $code,
        'Nome' => $name,
        'TipoEntidade' => 'Paciente',
        'Estado' => 'Ativo',
        'Cidade' => 'Luanda',
        'Rua' => 'Rua Principal',
        'Pais' => 'Angola'
    ]);
    
    $agendaCode = 'AG_TEST_' . ($i + 1);
    DB::table('tb_agendamento')->updateOrInsert(['Codigo' => $agendaCode], [
        'IdPaciente' => $code,
        'IdMedico' => $medId,
        'DataAgendamento' => now()->format('Y-m-d'),
        'Situacao' => 'Laboratorio',
        'Estado' => 'Ativo',
        'Consulta' => 'Check-up Geral',
        'Valor' => 0
    ]);

    // Add normal exam
    DB::table('tb_resultado_exame')->insert([
        'Codigo' => $agendaCode,
        'CodExame' => 'EXM1',
        'Descricao' => 'GINECOLOGIA',
        'Estado' => 'Ativo',
        'Valor' => 200
    ]);

    // Add complex exam
    DB::table('tb_resultado_exame')->insert([
        'Codigo' => $agendaCode,
        'CodExame' => 'HEM001',
        'Descricao' => 'Hemograma Completo',
        'Estado' => 'Ativo',
        'Valor' => 1500,
        'Filhos' => 'Hemoglobina|Glóbulos brancos|Leucograma|Hematócrito|Plaquetas|VGM|HGM',
        'Referencia' => '12-16 g/dL|4-11 mil/mm3|...|150-450 mil|...|...|...'
    ]);
}

// Ensure the complex exam exists in metadata
DB::table('tb_exames')->updateOrInsert(['Codigo' => 'HEM001'], [
    'Id' => 999,
    'Descricao' => 'Hemograma Completo',
    'Filhos' => 'Hemoglobina|Glóbulos brancos|Leucograma|Hematócrito|Plaquetas|VGM|HGM',
    'Referencia' => '12-16 g/dL|4-11 mil/mm3|...|150-450 mil|...|...|...',
    'Tipo' => 'NORMAL',
    'Valor' => 1500,
    'Estado' => 'Ativo'
]);

echo "Laboratório populado com sucesso!";
