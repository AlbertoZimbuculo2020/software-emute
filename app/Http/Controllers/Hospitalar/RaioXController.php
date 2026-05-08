<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RaioXController extends Controller
{
    public function index()
    {
        // Pacientes aguardando Raio-X
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'medico.Nome as MedicoNome'
            )
            ->where('tb_agendamento.Situacao', 'RAIO X')
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        return Inertia::render('Hospitalar/RaioX', [
            'aguardando' => $aguardando
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

        if (!$agendamento) {
            return response()->json(['error' => 'Agendamento não encontrado'], 404);
        }

        $exames = DB::table('tb_resultado_exame')
            ->where('IdAgenda', $idAgenda)
            ->get();

        $historico = DB::table('tb_resultado_exame')
            ->join('tb_agendamento', 'tb_resultado_exame.IdAgenda', '=', 'tb_agendamento.Codigo')
            ->where('tb_agendamento.IdPaciente', $agendamento->IdPaciente)
            ->where('tb_resultado_exame.Estado', 'Finalizado')
            ->select('tb_resultado_exame.*', 'tb_agendamento.DataAgendamento')
            ->orderBy('tb_resultado_exame.Id', 'desc')
            ->limit(20)
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
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->idExame)
            ->update([
                'Resultado' => $request->resultado,
                'Utilizador' => Auth::user()->name ?? 'Imagiologia',
                'Estado' => 'Finalizado'
            ]);

        return redirect()->back()->with('message', 'Laudo de Raio-X registrado com sucesso!');
    }

    public function finalizarAtendimento($idAgenda)
    {
        DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->update(['Situacao' => 'Finalizado']);

        return redirect()->back()->with('message', 'Processo de Imagiologia finalizado!');
    }
}
