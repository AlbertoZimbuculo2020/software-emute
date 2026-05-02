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
        // Pacientes aguardando exames (Situação = 'Laboratorio')
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'medico.Nome as MedicoNome'
            )
            ->where('tb_agendamento.Situacao', 'Laboratorio')
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        $depositos = DB::table('tb_deposito')->where('ESTADO', 'Ativo')->get();
        $materiais = DB::table('tb_artigo')->where('TIPO', 'PRODUTO')->where('ESTADO', 'Activado')->get();

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
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'paciente.Telefone',
                'paciente.Rua as Morada',
                'ent.DataNascimento',
                'ent.Genero'
            )
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        $exames = DB::table('tb_resultado_exame')
            ->where('Codigo', $idAgenda)
            ->get();

        $historico = DB::table('tb_resultado_exame')
            ->join('tb_agendamento', 'tb_resultado_exame.Codigo', '=', 'tb_agendamento.Codigo')
            ->where('tb_agendamento.IdPaciente', $agendamento->IdPaciente)
            ->where('tb_resultado_exame.Estado', 'Finalizado')
            ->select('tb_resultado_exame.*', 'tb_agendamento.DataAgendamento')
            ->orderBy('tb_agendamento.DataAgendamento', 'desc')
            ->get();

        $materiaisUsados = DB::table('tb_carrinho_hospitalar')
            ->where('ID_AGENDA', $idAgenda)
            ->where('Tipo', 'Laboratorio')
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
            'nrAmostra' => 'nullable|string',
            'obs' => 'nullable|string'
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->idExame)
            ->update([
                'Resultado' => $request->resultado ?? '',
                'Referencia' => $request->nrAmostra, 
                'Obs' => $request->obs,
                'Utilizador' => Auth::user()->name ?? 'Laboratório',
                'Estado' => 'Finalizado'
            ]);

        return redirect()->back()->with('message', 'Resultado do exame gravado com sucesso!');
    }

    public function storeMaterial(Request $request)
    {
        $request->validate([
            'idAgenda' => 'required',
            'produto' => 'required',
            'descricao' => 'required',
            'quantidade' => 'required|integer|min:1',
            'preco' => 'required|numeric'
        ]);

        $idEntidade = DB::table('tb_agendamento')->where('Codigo', $request->idAgenda)->value('IdPaciente');

        DB::table('tb_carrinho_hospitalar')->insert([
            'Produto' => $request->produto,
            'Descricao' => $request->descricao,
            'Quantidade' => $request->quantidade,
            'Preco' => $request->preco,
            'Total' => $request->quantidade * $request->preco,
            'Iva' => 0,
            'Desconto' => 0,
            'ID_AGENDA' => $request->idAgenda,
            'ID_ENTIDADE' => $idEntidade,
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
        $examesPendentes = DB::table('tb_resultado_exame')
            ->where('Codigo', $idAgenda)
            ->where('Estado', '<>', 'Finalizado')
            ->count();

        if ($examesPendentes > 0) {
            return redirect()->back()->with('error', 'Não é possível finalizar. Existem ' . $examesPendentes . ' exame(s) pendente(s) de resultado.');
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
            ->where('Codigo', $idAgenda)
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laboratorio_resultado', [
            'paciente' => $agendamento,
            'exames' => $exames
        ]);

        return $pdf->stream('resultado_laboratorio_'.$idAgenda.'.pdf');
    }
}
