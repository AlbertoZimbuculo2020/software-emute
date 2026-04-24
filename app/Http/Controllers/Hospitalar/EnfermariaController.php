<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnfermariaController extends Controller
{
    public function index()
    {
        // Pacientes com solicitações pendentes (Internado, Laboratório, etc.)
        $solicitacoes = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'medico.Nome as MedicoNome',
                DB::raw("(SELECT COUNT(*) FROM tb_resultado_exame WHERE Codigo = tb_agendamento.Codigo AND Estado = 'Ativo') as QTD_SERVICOS")
            )
            ->whereIn('tb_agendamento.Situacao', ['Laboratorio', 'RAIO X', 'Internado'])
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        $depositos = DB::table('tb_deposito')->where('ESTADO', 'Ativo')->get();
        $farmacos = DB::table('tb_artigo')->where('TIPO', 'PRODUTO')->where('ESTADO', 'Activado')->get();

        return Inertia::render('Hospitalar/Enfermaria', [
            'solicitacoes' => $solicitacoes,
            'depositos' => $depositos,
            'farmacos' => $farmacos
        ]);
    }

    public function getDetails($idAgenda)
    {
        // Buscar exames solicitados
        $exames = DB::table('tb_resultado_exame')
            ->where('Codigo', $idAgenda)
            ->get();

        // Buscar fármacos prescritos
        $prescricoes = DB::table('tb_receita')
            ->where('IdAgenda', $idAgenda)
            ->get();

        return response()->json([
            'exames' => $exames,
            'prescricoes' => $prescricoes
        ]);
    }

    public function storeFarmaco(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
            'IdProduto' => 'required',
            'Farmaco' => 'required',
            'Dosagem' => 'nullable|string',
            'Dias' => 'nullable|string',
        ]);

        DB::table('tb_receita')->insert([
            'IdAgenda' => $request->IdAgenda,
            'IdProduto' => $request->IdProduto,
            'Farmaco' => $request->Farmaco,
            'Dosagem' => $request->Dosagem,
            'Dias' => $request->Dias,
            'Estado' => 'Ativo'
        ]);

        return redirect()->back()->with('message', 'Fármaco adicionado com sucesso!');
    }

    public function salvarResultado(Request $request)
    {
        $request->validate([
            'idExame' => 'required',
            'resultado' => 'required|string'
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->idExame)
            ->update([
                'Resultado' => $request->resultado,
                'Utilizador' => Auth::user()->name ?? 'Sistema',
                'Estado' => 'Finalizado'
            ]);

        return redirect()->back()->with('message', 'Resultado registrado com sucesso!');
    }

    public function finalizarAtendimento($idAgenda)
    {
        DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->update(['Situacao' => 'Finalizado']);

        return redirect()->back()->with('message', 'Atendimento de enfermaria finalizado!');
    }
}
