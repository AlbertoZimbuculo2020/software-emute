<?php

namespace App\Http\Controllers\Configuracoes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PermissaoController extends Controller
{
    private $availablePermissions = [
        'Hospitalar' => [
            ['form' => 'Empresa', 'nome' => 'btnEmpresa', 'descricao' => 'Alterar dados da Empresa'],
            ['form' => 'Utilizadores', 'nome' => 'accordionUtilizadores', 'descricao' => 'Utilizadores'],
            ['form' => 'Definições', 'nome' => 'accordionDefinicoes', 'descricao' => 'Definições'],
            ['form' => 'Permissões', 'nome' => 'accordionPermissoes', 'descricao' => 'Permissões'],
            ['form' => 'Backup', 'nome' => 'accordionBackup', 'descricao' => 'Cópia de Segurança'],
            ['form' => 'Clientes', 'nome' => 'btnCliente', 'descricao' => 'Clientes'],
            ['form' => 'Pacientes', 'nome' => 'accordionPacientes', 'descricao' => 'Pacientes'],
            ['form' => 'Médicos', 'nome' => 'accordionMedicos', 'descricao' => 'Médicos'],
            ['form' => 'Exames', 'nome' => 'accordionExames', 'descricao' => 'Exames'],
            ['form' => 'Consultas', 'nome' => 'accordionConsultas', 'descricao' => 'Consultas Médicas'],
            ['form' => 'Serviços', 'nome' => 'accordionServicos', 'descricao' => 'Serviços'],
            ['form' => 'Recepção', 'nome' => 'btnRecepcao', 'descricao' => 'Recepção'],
            ['form' => 'Triagem', 'nome' => 'accordionTriagem', 'descricao' => 'Triagem'],
            ['form' => 'Enfermaria', 'nome' => 'accordionEnfermaria', 'descricao' => 'Enfermaria'],
            ['form' => 'Internamento', 'nome' => 'accordionInternamento', 'descricao' => 'Internamento'],
            ['form' => 'Consultório', 'nome' => 'accordionConsultorio', 'descricao' => 'Consultório'],
            ['form' => 'Laboratório', 'nome' => 'accordionLaboratorio', 'descricao' => 'Laboratório'],
            ['form' => 'Raio X', 'nome' => 'accordionRaioX', 'descricao' => 'Raio X'],
        ],
        'Stock' => [
            ['form' => 'Depósitos', 'nome' => 'accordionDepositos', 'descricao' => 'Depósitos'],
            ['form' => 'Produtos', 'nome' => 'accordionProdutos', 'descricao' => 'Produtos'],
            ['form' => 'Entrada Stock', 'nome' => 'accordionEntrada', 'descricao' => 'Entrada Em Stock'],
            ['form' => 'Baixa Stock', 'nome' => 'accordionBaixa', 'descricao' => 'Baixa de Stock'],
            ['form' => 'Documentos', 'nome' => 'accordionDocumentos', 'descricao' => 'Documentos Emitidos'],
            ['form' => 'Relatórios', 'nome' => 'accordionRelatorios', 'descricao' => 'Relatório e Estatísticas'],
        ]
    ];

    public function index()
    {
        $perfis = DB::table('tb_perfil')->get();
        
        return Inertia::render('Configuracoes/Permissoes', [
            'perfis' => $perfis,
            'availablePermissions' => $availablePermissions ?? $this->availablePermissions
        ]);
    }

    public function getPermissions($profileId)
    {
        $permissions = DB::table('tb_perfil_itens')
            ->where('ID_PERFIL', $profileId)
            ->get();
            
        return response()->json($permissions);
    }

    public function update(Request $request)
    {
        $profileId = $request->input('profileId');
        $permissions = $request->input('permissions'); // Array of {form, nome, estado}

        DB::transaction(function () use ($profileId, $permissions) {
            foreach ($permissions as $perm) {
                DB::table('tb_perfil_itens')->updateOrInsert(
                    ['ID_PERFIL' => $profileId, 'NOME' => $perm['nome']],
                    [
                        'FORM' => $perm['form'],
                        'ESTADO' => $perm['estado'] ? 'True' : 'False'
                    ]
                );
            }
        });

        return redirect()->back()->with('message', 'Permissões atualizadas com sucesso!');
    }
}
