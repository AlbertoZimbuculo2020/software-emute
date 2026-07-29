<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UtilizadorWeb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UtilizadoresWebSeeder extends Seeder
{
    public function run(): void
    {
        $passwordPadrao = '1234';

        // =================================================================
        // PASSO 1: Garantir que todas as permissões existem na tb_perfil_itens
        // =================================================================
        $this->garantirPermissoesPadrao();

        // =================================================================
        // PASSO 2: Garantir que a tabela utilizadores_web TEM TODAS as colunas
        //         da tabela original utilizador (mantendo sincronismo)
        // =================================================================
        $this->garantirEstruturaTabelaWeb();

        // =================================================================
        // PASSO 3: Limpar tabela web e repovoar a partir da tabela original
        // =================================================================
        try {
            Schema::disableForeignKeyConstraints();
            DB::table('utilizadores_web')->truncate();
            DB::statement('ALTER TABLE utilizadores_web AUTO_INCREMENT = 1');
            Schema::enableForeignKeyConstraints();
        } catch (\Throwable $e) {
            DB::table('utilizadores_web')->delete();
        }

        $colunasOriginais = Schema::getColumnListing('utilizador');

        $utilizadores = User::all();
        $copiados = 0;

        foreach ($utilizadores as $u) {
            $token = $u->REMEMBER_TOKEN ?: User::generateToken();
            $senhaHasheada = User::hashPassword($passwordPadrao, $token);

            $dados = [];
            foreach ($colunasOriginais as $col) {
                $colLower = strtolower($col);
                if ($colLower === 'senha') {
                    $dados[$col] = $senhaHasheada;
                    continue;
                }
                if ($colLower === 'remember_token') {
                    $dados[$col] = $token;
                    continue;
                }
                if (isset($u->{$col})) {
                    $dados[$col] = $u->{$col};
                }
            }

            // Garantir valores padrão caso falte algum campo
            $dados['ACESSO']     = $dados['ACESSO']     ?? 'NAO';
            $dados['ESTADO']     = $dados['ESTADO']     ?? 'Activado';
            $dados['CREATED_AT'] = $dados['CREATED_AT'] ?? now();

            try {
                DB::table('utilizadores_web')->insert($dados);
                $copiados++;
            } catch (\Throwable $e) {
                $this->command->warn(
                    "Falhou utilizador ID={$u->ID_UTILIZADOR} ({$u->NOME_UTILIZADOR}): " . $e->getMessage()
                );
            }
        }

        $this->command->info('Utilizadores restaurados: ' . $copiados . '/' . $utilizadores->count() . ' (senha padrão: "' . $passwordPadrao . '")');

        // =================================================================
        // PASSO 4: Relatório
        // =================================================================
        $perfisAtivos = DB::table('tb_perfil')->count();
        $totalPermissoes = DB::table('tb_perfil_itens')->where('ESTADO', 'True')->count();
        $this->command->info("Perfis ativos: {$perfisAtivos} | Permissões ativas em tb_perfil_itens: {$totalPermissoes}");

        $adminCount = DB::table('utilizadores_web')->where('ACESSO', 'SIM')->count();
        $medCount   = DB::table('utilizadores_web as u')
            ->leftJoin('tb_tipoentidade as te', 'u.ID_PESSOA', '=', 'te.Codigo')
            ->where('te.TipoEntidade', 'like', '%edico%')
            ->count();
        $this->command->info("Admin (ACESSO=SIM): {$adminCount} | Médicos (TipoEntidade Médicos): {$medCount}");
    }

    protected function garantirPermissoesPadrao(): void
    {
        $perfis = [
            1 => [ // RECECAO
                'btnCliente','accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionExames','accordionConsultas','accordionServicos',
                'btnRecepcao','accordionTriagem','accordionEnfermaria','accordionInternamento','accordionConsultorio','accordionLaboratorio','accordionRaioX',
                'accordionDocumentos','accordionRelatorios',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas','dashProdutividadeMedica',
            ],
            2 => [ // TRIAGEM
                'accordionTriagem','accordionEnfermaria','accordionInternamento','accordionConsultorio','accordionLaboratorio','accordionRaioX',
                'btnCliente','accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionExames','accordionConsultas','accordionServicos',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas',
            ],
            3 => [ // CONSULTORIO (Médicos)
                'accordionConsultorio','accordionExames','accordionConsultas','accordionServicos',
                'accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionTriagem','accordionEnfermaria','accordionInternamento','accordionLaboratorio','accordionRaioX',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas','dashProdutividadeMedica',
            ],
            4 => [ // LABORATORIO
                'accordionLaboratorio','accordionRaioX','accordionExames','accordionServicos',
                'accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionConsultas','accordionConsultorio',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas','dashProdutividadeMedica',
            ],
            5 => [ // RAIO X
                'accordionRaioX','accordionExames','accordionServicos',
                'accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionConsultas','accordionLaboratorio','accordionConsultorio',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas','dashProdutividadeMedica',
            ],
            6 => [ // GERAL (Staff)
                'btnEmpresa','accordionUtilizadores','accordionDefinicoes','accordionPermissoes','accordionBackup',
                'btnCliente','accordionPacientes','accordionMedicos','accordionSeguradora',
                'accordionExames','accordionConsultas','accordionServicos',
                'btnRecepcao','accordionTriagem','accordionEnfermaria','accordionInternamento','accordionConsultorio','accordionLaboratorio','accordionRaioX',
                'accordionProdutos','accordionDepositos','accordionEntrada','accordionBaixa','accordionDocumentos','accordionRelatorios',
                'dashVerResumo','dashConsultasAndamento','dashVerGraficos','dashVerTopListas','dashProdutividadeMedica',
            ],
        ];

        $criadas = 0; $ativadas = 0;
        foreach ($perfis as $idPerfil => $nomes) {
            // Garante que o perfil existe antes de inserir
            $existePerfil = DB::table('tb_perfil')->where('ID', $idPerfil)->exists();
            if (!$existePerfil) continue;

            foreach ($nomes as $nome) {
                $row = DB::table('tb_perfil_itens')
                    ->where('ID_PERFIL', $idPerfil)
                    ->where('NOME', $nome)
                    ->first();
                if (!$row) {
                    DB::table('tb_perfil_itens')->insert([
                        'ID_PERFIL' => $idPerfil,
                        'NOME'      => $nome,
                        'ESTADO'    => 'True',
                    ]);
                    $criadas++;
                } elseif ($row->ESTADO !== 'True') {
                    DB::table('tb_perfil_itens')
                        ->where('ID_PERFIL', $idPerfil)
                        ->where('NOME', $nome)
                        ->update(['ESTADO' => 'True']);
                    $ativadas++;
                }
            }
        }

        if ($criadas || $ativadas) {
            $this->command->info("Permissões: {$criadas} novas | {$ativadas} reativadas");
        }
    }

    protected function garantirEstruturaTabelaWeb(): void
    {
        if (!Schema::hasTable('utilizadores_web')) {
            DB::statement('CREATE TABLE utilizadores_web LIKE utilizador');
            $this->command->info('Tabela utilizadores_web criada (estrutura copiada de utilizador).');
            return;
        }

        $colunasOrig = Schema::getColumnListing('utilizador');
        $colunasWeb  = Schema::getColumnListing('utilizadores_web');

        $faltam = array_diff($colunasOrig, $colunasWeb);
        foreach ($faltam as $col) {
            try {
                $tipo = DB::selectOne("SHOW COLUMNS FROM utilizador WHERE Field = ?", [$col]);
                if ($tipo) {
                    $null = $tipo->Null === 'YES' ? 'NULL' : 'NOT NULL';
                    $default = $tipo->Default !== null
                        ? "DEFAULT '" . addslashes($tipo->Default) . "'"
                        : ($tipo->Null === 'YES' ? 'DEFAULT NULL' : '');
                    $extra = $tipo->Extra ?? '';
                    DB::statement("ALTER TABLE utilizadores_web ADD COLUMN `{$col}` {$tipo->Type} {$null} {$default} {$extra}");
                    $this->command->warn("Coluna adicionada em utilizadores_web: {$col} ({$tipo->Type})");
                }
            } catch (\Throwable $e) {
                $this->command->warn("Não foi possível adicionar coluna {$col}: " . $e->getMessage());
            }
        }
    }
}
