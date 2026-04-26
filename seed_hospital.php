<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Limpando seeds anteriores (se existirem)...\n";
DB::table('tb_agendamento')->where('Codigo', 'LIKE', 'AGD_S%')->delete();
DB::table('tb_exames')->where('Codigo', 'LIKE', 'EXM_S%')->delete();
DB::table('tb_paciente')->where('IdTipoEntidade', 'LIKE', 'PAC_S%')->delete();
DB::table('tb_medico')->where('IdTipoEntidade', 'LIKE', 'MED_S%')->delete();
DB::table('tb_tipoentidade')->where('Codigo', 'LIKE', 'MED_S%')->orWhere('Codigo', 'LIKE', 'PAC_S%')->delete();
DB::table('tb_entidade')->where('Codigo', 'LIKE', 'MED_S%')->orWhere('Codigo', 'LIKE', 'PAC_S%')->delete();

DB::beginTransaction();
try {
    for ($i = 1; $i <= 30; $i++) {
        $medCode = "MED_S" . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        DB::table('tb_entidade')->insert([
            'Codigo' => $medCode,
            'Contribuente' => '999999999',
            'Genero' => 'Masculino',
            'Tipo' => 'SINGULAR',
            'DataNascimento' => '1980-01-01',
            'EstadoCivil' => 'SOLTEIRO'
        ]);

        DB::table('tb_tipoentidade')->insert([
            'Codigo' => $medCode,
            'IdEntidade' => $medCode,
            'Nome' => "DR. MEDICO " . $i,
            'TipoEntidade' => 'Medico',
            'Estado' => 'Ativo',
            'Cidade' => 'LUANDA',
            'Rua' => 'TESTE',
            'Pais' => 'Angola',
            'Telefone' => '999000000'
        ]);

        DB::table('tb_medico')->insert([
            'IdTipoEntidade' => $medCode,
            'Estado' => 'Ativo'
        ]);

        $pacCode = "PAC_S" . str_pad($i, 3, '0', STR_PAD_LEFT);
        DB::table('tb_entidade')->insert([
            'Codigo' => $pacCode,
            'Contribuente' => '999999999',
            'Genero' => 'Feminino',
            'Tipo' => 'SINGULAR',
            'DataNascimento' => '1995-01-01',
            'EstadoCivil' => 'CASADO'
        ]);

        DB::table('tb_tipoentidade')->insert([
            'Codigo' => $pacCode,
            'IdEntidade' => $pacCode,
            'Nome' => "PACIENTE TESTE " . $i,
            'TipoEntidade' => 'Paciente',
            'Estado' => 'Ativo',
            'Cidade' => 'LUANDA',
            'Rua' => 'TESTE',
            'Pais' => 'Angola',
            'Telefone' => '999111' . str_pad($i, 3, '0', STR_PAD_LEFT)
        ]);

        DB::table('tb_paciente')->insert([
            'IdTipoEntidade' => $pacCode,
            'Estado' => 'Ativo'
        ]);

        DB::table('tb_exames')->insert([
            'Codigo' => "EXM_S" . str_pad($i, 3, '0', STR_PAD_LEFT),
            'Descricao' => "EXAME SEED " . $i,
            'Valor' => 1500 + ($i * 100),
            'Estado' => 'Ativo',
            'Exame_Fora' => 'False',
            'USER' => 'SEED'
        ]);

        DB::table('tb_agendamento')->insert([
            'Codigo' => "AGD_S" . str_pad($i, 3, '0', STR_PAD_LEFT),
            'IdPaciente' => $pacCode,
            'IdMedico' => $medCode,
            'Consulta' => 'CONSULTA GERAL',
            'DataAgendamento' => now()->format('Y-m-d'),
            'Valor' => 5000,
            'Situacao' => 'Triagem',
            'Estado' => 'Ativo'
        ]);
    }
    DB::commit();
    echo "Seed de 30 Médicos, 30 Pacientes, 30 Exames e 30 Consultas concluído.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Falha no seed: " . $e->getMessage() . "\n";
}
