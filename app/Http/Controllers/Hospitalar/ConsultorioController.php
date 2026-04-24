<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ConsultorioController extends Controller
{
    public function index()
    {
        // Pacientes aguardando consulta ou reconsulta
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->select(
                'tb_agendamento.*', 
                'tb_tipoentidade.Nome as PacienteNome',
                'tb_tipoentidade.Telefone',
                'tb_entidade.DataNascimento',
                'tb_entidade.Genero'
            )
            ->whereIn('tb_agendamento.Situacao', ['Consultorio', 'Reconsulta'])
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        return Inertia::render('Hospitalar/Consultorio', [
            'aguardando' => $aguardando
        ]);
    }

    public function getPatientData($idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->first();

        if (!$agendamento) return response()->json(['error' => 'Agendamento não encontrado'], 404);

        $triagem = DB::table('tb_triagem')
            ->where('IdAgenda', $idAgenda)
            ->first();

        $historico = DB::table('tb_agendamento')
            ->where('IdPaciente', $agendamento->IdPaciente)
            ->where('Codigo', '!=', $idAgenda)
            ->orderBy('DataAgendamento', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'triagem' => $triagem,
            'historico' => $historico
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Codigo' => 'required',
            'qp' => 'nullable|string',
            'hda' => 'nullable|string',
            'obj' => 'nullable|string',
            'complementares' => 'nullable|string',
            'recomendacoes' => 'nullable|string',
            'situacao' => 'required|string',
        ]);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->Codigo)
            ->update([
                'QP' => $request->qp,
                'HDA' => $request->hda,
                'OBJ' => $request->obj,
                'COMPLEMENTARES' => $request->complementares,
                'RECOMENDACOES' => $request->recomendacoes,
                'Situacao' => $request->situacao,
            ]);

        // Se houver receita, poderíamos salvar aqui também (em tb_receita)
        // Se houver exames solicitados, salvaríamos em tb_resultado_exame com status 'Pendente'

        return redirect()->back()->with('message', 'Dados clínicos salvos com sucesso!');
    }
}
