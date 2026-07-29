<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SenhaSeeder extends Seeder
{
    public function run(): void
    {
        $senhas = [
            ['Codigo' => 'G001', 'Tipo' => 'Geral',       'Estado' => 'Pendente', 'Guiche' => null],
            ['Codigo' => 'G002', 'Tipo' => 'Geral',       'Estado' => 'Pendente', 'Guiche' => null],
            ['Codigo' => 'P001', 'Tipo' => 'Preferencial','Estado' => 'Pendente', 'Guiche' => null],
            ['Codigo' => 'T001', 'Tipo' => 'Triagem',     'Estado' => 'Pendente', 'Guiche' => null],
            ['Codigo' => 'E001', 'Tipo' => 'Exame',       'Estado' => 'Pendente', 'Guiche' => null],
        ];

        foreach ($senhas as $s) {
            $s['DataCriacao'] = now()->toDateString();
            $s['created_at'] = now();
            $s['updated_at'] = now();
            DB::table('tb_senha')->insert($s);
        }

        $this->command->info('Senhas de exemplo inseridas: ' . count($senhas));
    }
}
