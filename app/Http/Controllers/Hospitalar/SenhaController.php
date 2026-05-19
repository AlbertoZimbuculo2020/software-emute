<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Senha;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SenhaController extends Controller
{
    /**
     * Retorna a lista de senhas do dia para a recepção.
     */
    public function index(Request $request)
    {
        $today = date('Y-m-d');
        
        $pendentes = Senha::where('DataCriacao', $today)
            ->where('Estado', 'Pendente')
            ->orderBy('Id', 'asc')
            ->get();
            
        $chamados = Senha::where('DataCriacao', $today)
            ->where('Estado', 'Chamado')
            ->orderBy('DataUltimaChamada', 'desc')
            ->get();
            
        $atendidos = Senha::where('DataCriacao', $today)
            ->whereIn('Estado', ['Atendido', 'Cancelado'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'pendentes' => $pendentes,
            'chamados' => $chamados,
            'atendidos' => $atendidos
        ]);
    }

    /**
     * Gera uma nova senha térmica baseada no tipo.
     */
    public function gerar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string|in:Geral,Preferencial,Triagem,Exame'
        ]);

        $tipo = $request->tipo;
        $today = date('Y-m-d');

        // Determinar o prefixo
        $prefix = 'G';
        if ($tipo === 'Preferencial') $prefix = 'P';
        elseif ($tipo === 'Triagem') $prefix = 'T';
        elseif ($tipo === 'Exame') $prefix = 'E';

        // Iniciar transação para evitar duplicidade de código de senha concorrente
        return DB::transaction(function () use ($tipo, $prefix, $today) {
            // Contar quantas senhas desse tipo já foram criadas hoje
            $count = Senha::where('DataCriacao', $today)
                ->where('Tipo', $tipo)
                ->count();

            $nextNumber = $count + 1;
            $codigo = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $senha = Senha::create([
                'Codigo' => $codigo,
                'Tipo' => $tipo,
                'Estado' => 'Pendente',
                'DataCriacao' => $today
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Senha gerada com sucesso!',
                'senha' => $senha
            ]);
        });
    }

    /**
     * Chama uma senha (próxima ou específica) atribuindo um guiché.
     */
    public function chamar(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer',
            'guiche' => 'required|string|max:50'
        ]);

        $guiche = $request->guiche;
        $today = date('Y-m-d');

        // Se passar um ID específico, chama aquela senha diretamente
        if ($request->id) {
            $senha = Senha::find($request->id);
        } else {
            // Caso contrário, pega a mais antiga pendente
            // Preferencial tem prioridade, depois Triagem, Exame e Geral
            $senha = Senha::where('DataCriacao', $today)
                ->where('Estado', 'Pendente')
                ->orderByRaw("FIELD(Tipo, 'Preferencial', 'Triagem', 'Exame', 'Geral') ASC")
                ->orderBy('Id', 'asc')
                ->first();
        }

        if (!$senha) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma senha pendente na fila!'
            ], 404);
        }

        $senha->update([
            'Estado' => 'Chamado',
            'Guiche' => $guiche,
            'DataChamada' => $senha->DataChamada ?? now(),
            'DataUltimaChamada' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Senha chamada com sucesso!',
            'senha' => $senha
        ]);
    }

    /**
     * Muda o estado da senha (ex: Atendido, Cancelado).
     */
    public function mudarEstado(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'estado' => 'required|string|in:Pendente,Chamado,Atendido,Cancelado'
        ]);

        $senha = Senha::findOrFail($request->id);
        $senha->update([
            'Estado' => $request->estado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado da senha atualizado!',
            'senha' => $senha
        ]);
    }

    /**
     * Retorna os dados para a tela da TV (Painel de Senhas).
     */
    public function obterDadosPainel(Request $request)
    {
        $today = date('Y-m-d');

        // A senha atualmente ativa na tela é a última a ter DataUltimaChamada
        $atual = Senha::where('DataCriacao', $today)
            ->where('Estado', 'Chamado')
            ->orderBy('DataUltimaChamada', 'desc')
            ->first();

        // O histórico são as outras chamadas hoje, excluindo a atual
        $historicoQuery = Senha::where('DataCriacao', $today)
            ->where('Estado', 'Chamado');

        if ($atual) {
            $historicoQuery->where('Id', '!=', $atual->Id);
        }

        $historico = $historicoQuery->orderBy('DataUltimaChamada', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'atual' => $atual,
            'historico' => $historico
        ]);
    }

    /**
     * Roda a tela pública do Painel de Senhas (TV).
     */
    public function painelPublico()
    {
        return Inertia::render('Hospitalar/PainelSenhas');
    }
}
