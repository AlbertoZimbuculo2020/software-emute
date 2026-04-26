<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Iniciando povoamento do sistema...\n";

try {
    // 1. Cadastrar Especialidades (Consultas)
    $consultas = [
        ['Codigo' => 'CNT001', 'Descricao' => 'MEDICINA GERAL', 'Valor' => 5000, 'Estado' => 'Ativo'],
        ['Codigo' => 'CNT002', 'Descricao' => 'CARDIOLOGIA', 'Valor' => 15000, 'Estado' => 'Ativo'],
        ['Codigo' => 'CNT003', 'Descricao' => 'GINECOLOGIA', 'Valor' => 12000, 'Estado' => 'Ativo'],
        ['Codigo' => 'CNT004', 'Descricao' => 'PEDIATRIA', 'Valor' => 8000, 'Estado' => 'Ativo'],
    ];

    foreach ($consultas as $c) {
        if (!DB::table('tb_consulta')->where('Codigo', $c['Codigo'])->exists()) {
            DB::table('tb_consulta')->insert($c);
            echo "Consulta cadastrada: {$c['Descricao']}\n";
        }
    }

    // 2. Cadastrar Médicos
    $medicos = [
        ['nome' => 'Dr. Alberto Zimbu', 'nif' => '123456789', 'carteira' => 'ORD-1234', 'cidade' => 'Luanda'],
        ['nome' => 'Dra. Maria Antónia', 'nif' => '987654321', 'carteira' => 'ORD-4321', 'cidade' => 'Benguela'],
    ];

    foreach ($medicos as $idx => $m) {
        $codigo = 'MED' . str_pad($idx + 10, 3, '0', STR_PAD_LEFT);
        if (!DB::table('tb_medico')->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')->where('tb_tipoentidade.Nome', strtoupper($m['nome']))->exists()) {
            DB::table('tb_entidade')->insert([
                'Codigo' => $codigo,
                'Contribuente' => $m['nif'],
                'Tipo' => 'SINGULAR',
            ]);
            DB::table('tb_tipoentidade')->insert([
                'Codigo' => $codigo,
                'IdEntidade' => $codigo,
                'Nome' => strtoupper($m['nome']),
                'TipoEntidade' => 'Medico',
                'Cidade' => $m['cidade'],
                'Estado' => 'Ativo'
            ]);
            DB::table('tb_medico')->insert([
                'IdTipoEntidade' => $codigo,
                'CarteiraMedica' => $m['carteira'],
                'Estado' => 'Ativo'
            ]);
            echo "Médico cadastrado: {$m['nome']}\n";
            
            // Associar à Medicina Geral (CNT001)
            $cons = DB::table('tb_consulta')->where('Codigo', 'CNT001')->first();
            if ($cons) {
                DB::table('tb_consulta_medico')->insert([
                    'IdTipoEntidade' => $codigo,
                    'IdConsulta' => $cons->Id,
                    'Descricao' => $cons->Descricao,
                    'Estado' => 'Ativo'
                ]);
            }
        }
    }

    // 3. Cadastrar Pacientes
    $pacientes = [
        ['nome' => 'João Pereira', 'nif' => 'P123', 'genero' => 'Masculino', 'cidade' => 'Luanda'],
        ['nome' => 'Ana Silva', 'nif' => 'P456', 'genero' => 'Feminino', 'cidade' => 'Huambo'],
    ];

    foreach ($pacientes as $idx => $p) {
        $codigo = 'PC' . str_pad($idx + 5, 3, '0', STR_PAD_LEFT);
        if (!DB::table('tb_tipoentidade')->where('Nome', strtoupper($p['nome']))->exists()) {
            DB::table('tb_entidade')->insert([
                'Codigo' => $codigo,
                'Contribuente' => $p['nif'],
                'Tipo' => 'SINGULAR',
                'Genero' => $p['genero'],
            ]);
            DB::table('tb_tipoentidade')->insert([
                'Codigo' => $codigo,
                'IdEntidade' => $codigo,
                'Nome' => strtoupper($p['nome']),
                'TipoEntidade' => 'Paciente',
                'Cidade' => $p['cidade'],
                'Estado' => 'Ativo'
            ]);
            DB::table('tb_paciente')->insert([
                'IdTipoEntidade' => $codigo,
                'Estado' => 'Ativo'
            ]);
            echo "Paciente cadastrado: {$p['nome']}\n";
        }
    }

    // 4. Cadastrar Exames
    $exames = [
        ['Codigo' => 'EXM001', 'Descricao' => 'HEMOGRAMA COMPLETO', 'Valor' => 3500, 'Categoria' => 'SANGUE'],
        ['Codigo' => 'EXM002', 'Descricao' => 'MALÁRIA (GOTA ESPESSA)', 'Valor' => 1500, 'Categoria' => 'SANGUE'],
        ['Codigo' => 'EXM003', 'Descricao' => 'RAIO-X TÓRAX', 'Valor' => 8000, 'Categoria' => 'IMAGEM'],
    ];

    foreach ($exames as $e) {
        if (!DB::table('tb_exames')->where('Codigo', $e['Codigo'])->exists()) {
            DB::table('tb_exames')->insert(array_merge($e, [
                'Tipo' => 'NORMAL',
                'Exame_Fora' => 'False',
                'Estado' => 'Ativo',
                'USER' => 'SISTEMA'
            ]));
            echo "Exame cadastrado: {$e['Descricao']}\n";
        }
    }

    echo "Sistema povoado com sucesso!\n";

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
