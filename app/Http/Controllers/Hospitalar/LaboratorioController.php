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
                'ent.DataNascimento',
                'ent.Genero',
                'ent.Morada'
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

        return response()->json([
            'paciente' => $agendamento,
            'exames' => $exames,
            'historico' => $historico
        ]);
    }

    public function salvarResultado(Request $request)
    {
        $request->validate([
            'idExame' => 'required',
            'resultado' => 'required|string',
            'nrAmostra' => 'nullable|string'
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->idExame)
            ->update([
                'Resultado' => $request->resultado,
                'Referencia' => $request->nrAmostra, // Usando Referencia para Nº Amostra
                'Utilizador' => Auth::user()->name ?? 'Laboratório',
                'Estado' => 'Finalizado'
            ]);

        return redirect()->back()->with('message', 'Resultado do exame registrado com sucesso!');
    }

    public function finalizarAtendimento($idAgenda)
    {
        DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->update(['Situacao' => 'Finalizado']);

        return redirect()->back()->with('message', 'Processo laboratorial finalizado!');
    }
}
