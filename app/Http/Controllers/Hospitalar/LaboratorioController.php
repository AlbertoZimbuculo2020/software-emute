<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LaboratorioController extends Controller
{
    public function index()
    {
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'medico.Nome as MedicoNome',
                DB::raw('(SELECT COUNT(*) FROM tb_resultado_exame WHERE tb_resultado_exame.IdAgenda = tb_agendamento.Codigo AND tb_resultado_exame.Estado NOT IN (\'Finalizado\', \'Removido\')) as TotalExames')
            )
            ->where('tb_agendamento.Estado', 'Ativo')
            ->where(function($query) {
                $query->whereIn('tb_agendamento.Situacao', ['Laboratorio', 'RAIO X'])
                      ->orWhere(function($q) {
                          $q->where('tb_agendamento.Situacao', 'Internado')
                            ->whereExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('tb_resultado_exame')
                                    ->whereColumn('tb_resultado_exame.IdAgenda', 'tb_agendamento.Codigo')
                                    ->whereNotIn('tb_resultado_exame.Estado', ['Finalizado', 'Removido']);
                            });
                      });
            })
            ->orderBy('tb_agendamento.Id', 'desc')
            ->get();

        $depositos = DB::table('tb_deposito')->where('ESTADO', 'Ativo')->get();
        $materiais = DB::table('tb_artigo')
            ->where('TIPO', 'PRODUTO')
            ->where('ESTADO', 'Activado')
            ->select('CODIGO', 'DESCRICAO', 'PV')
            ->get();

        return Inertia::render('Hospitalar/Laboratorio', [
            'aguardando' => $aguardando,
            'depositos' => $depositos,
            'materiais' => $materiais
        ]);
    }

    public function getDetails($idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_entidade as ent', 'paciente.IdEntidade', '=', 'ent.Codigo')
            ->leftJoin('tb_paciente as p', 'paciente.Codigo', '=', 'p.IdTipoEntidade')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'paciente.Telefone',
                'paciente.Rua as Morada',
                'ent.DataNascimento',
                'ent.Genero',
                'p.IdSegura'
            )
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$agendamento) {
            return response()->json(['error' => 'Agendamento não encontrado'], 404);
        }

        if ($agendamento->IdSegura) {
            $agendamento->Seguradora = DB::table('tb_tipoentidade')
                ->where('Codigo', $agendamento->IdSegura)
                ->value('Nome');
        }

        $exames = DB::table('tb_resultado_exame')
            ->leftJoin('tb_exames', 'tb_resultado_exame.CodExame', '=', 'tb_exames.Codigo')
            ->where('tb_resultado_exame.IdAgenda', $idAgenda)
            ->where('tb_resultado_exame.Estado', '!=', 'Removido')
            ->select(
                'tb_resultado_exame.*',
                'tb_exames.Filhos as MetaFilhos',
                'tb_exames.Referencia as MetaReferencia',
                'tb_exames.Tipo as MetaTipo'
            )
            ->get();

        $historico = DB::table('tb_resultado_exame')
            ->join('tb_agendamento', 'tb_resultado_exame.IdAgenda', '=', 'tb_agendamento.Codigo')
            ->where('tb_agendamento.IdPaciente', $agendamento->IdPaciente)
            ->where('tb_resultado_exame.Estado', 'Finalizado')
            ->select('tb_resultado_exame.*', 'tb_agendamento.DataAgendamento')
            ->orderBy('tb_resultado_exame.Id', 'desc')
            ->limit(20)
            ->get();

        $materiaisUsados = DB::table('tb_carrinho_hospitalar')
            ->where('ID_AGENDA', $idAgenda)
            ->where('TIPO', 'Laboratorio')
            ->get();

        return response()->json([
            'paciente' => $agendamento,
            'exames' => $exames,
            'historico' => $historico,
            'materiaisUsados' => $materiaisUsados
        ]);
    }

    public function salvarResultado(Request $request)
    {
        $request->validate([
            'idExame' => 'required',
            'resultado' => 'nullable|string',
            'obs' => 'nullable|string'
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->idExame)
            ->update([
                'Resultado' => $request->resultado ?? '',
                'Obs' => $request->obs,
                'Utilizador' => Auth::user()->name ?? 'Laboratório',
                'DataExame' => now(),
                'Estado' => 'Finalizado'
            ]);

        return response()->json(['message' => 'Resultado gravado com sucesso!']);
    }

    public function storeMaterial(Request $request)
    {
        $request->validate([
            'idAgenda' => 'required',
            'idPaciente' => 'required',
            'produto' => 'required',
            'descricao' => 'required',
            'quantidade' => 'required|integer|min:1',
            'preco' => 'required|numeric'
        ]);

        DB::table('tb_carrinho_hospitalar')->insert([
            'Produto' => $request->produto,
            'Descricao' => $request->descricao,
            'Quantidade' => $request->quantidade,
            'Preco' => $request->preco,
            'Total' => $request->quantidade * $request->preco,
            'Iva' => 0,
            'Desconto' => 0,
            'ID_AGENDA' => $request->idAgenda,
            'ID_ENTIDADE' => $request->idPaciente,
            'Data_' => now(),
            'Tipo' => 'Laboratorio',
            'ESTADO' => 'Ativo'
        ]);

        return redirect()->back()->with('message', 'Material adicionado com sucesso!');
    }

    public function destroyMaterial($id)
    {
        DB::table('tb_carrinho_hospitalar')->where('Id', $id)->delete();
        return redirect()->back()->with('message', 'Material removido!');
    }

    public function finalizarAtendimento(Request $request, $idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')->where('Codigo', $idAgenda)->first();

        if (!$agendamento) {
            return redirect()->back()->with('error', 'Agendamento não encontrado.');
        }

        // Passo 5: Validação - Verificar se todos os exames foram preenchidos
        // Ignoramos os exames removidos e focamos nos que ainda não estão 'Finalizado'
        $examesPendentes = DB::table('tb_resultado_exame')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', '!=', 'Finalizado')
            ->where('Estado', '!=', 'Removido')
            ->count();

        if ($examesPendentes > 0) {
            return redirect()->back()->withErrors(['error' => 'Ainda existem ' . $examesPendentes . ' exame(s) pendente(s) de resultado para este paciente.']);
        }

        // Passo 5: Mudança de Estado do Paciente
        $novaSituacao = 'Consultorio';
        if ($agendamento->Situacao == 'Internado') {
            $novaSituacao = 'Internado';
        }

        DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->update(['Situacao' => $novaSituacao]);

        // Passo 4: Consumo de Material Hospitalar (Saída de Stock)
        $materiais = DB::table('tb_carrinho_hospitalar')
            ->where('ID_AGENDA', $idAgenda)
            ->where('Tipo', 'Laboratorio')
            ->where('ESTADO', 'Ativo')
            ->get();

        if ($materiais->count() > 0) {
            $total = $materiais->sum('Total');
            
            // Criar Documento de Saída (Fatura SD)
            $faturaId = DB::table('tb_fatura')->insertGetId([
                'IdCliente' => $agendamento->IdPaciente,
                'Total' => $total,
                'Data' => now(),
                'Tipo' => 'SD', // Saída de Produto
                'Estado' => 'Fechado',
                'NOME_DOCUMENTO' => 'SAIDA DE PRODUTO',
                'DESCRICAO' => 'Saída de material laboratorial',
                'Utilizador' => Auth::user()->name ?? 'Laboratório'
            ]);

            foreach ($materiais as $item) {
                // Passo 4: Baixar Estoque real das quantidades
                $deposito = $request->input('deposito', '1');

                DB::table('tb_armazem')
                    ->where('CodigoProduto', $item->Produto)
                    ->where('CodigoDeposito', $deposito)
                    ->decrement('Existencia', $item->Quantidade);

                // Marcar carrinho como processado
                DB::table('tb_carrinho_hospitalar')
                    ->where('Id', $item->Id)
                    ->update(['ESTADO' => 'Finalizado']);
            }
        }

        return redirect()->back()->with('message', 'Processo laboratorial finalizado e paciente encaminhado!');
    }

    public function imprimirPDF($idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->leftJoin('tb_entidade as ent', 'paciente.IdEntidade', '=', 'ent.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'paciente.Codigo as CodigoPaciente',
                'medico.Nome as MedicoNome',
                'ent.DataNascimento',
                'ent.Genero'
            )
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$agendamento) {
            abort(404, 'Paciente não encontrado');
        }

        $exames = DB::table('tb_resultado_exame')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', '!=', 'Removido')
            ->get();

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $view = 'pdf.laboratorio_resultado';
        $data = [
            'paciente' => $agendamento,
            'exames' => $exames,
            'empresa' => $empresa
        ];

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $data['is_duplicate'] = request('duplicado') !== '0';
            $data['original_view'] = $view;
            $data['data'] = $data;
            return view('pdf.layout_economico', $data);
        }

        return view($view, $data);
    }

}
