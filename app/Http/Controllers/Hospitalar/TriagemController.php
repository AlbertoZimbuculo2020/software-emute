<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TriagemController extends Controller
{
    public function index()
    {
        // Pacientes aguardando triagem
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->select(
                'tb_agendamento.*', 
                'tb_tipoentidade.Nome as PacienteNome',
                'tb_entidade.DataNascimento'
            )
            ->where('tb_agendamento.Situacao', 'Triagem')
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        // Histórico de triagens (hoje)
        $historico = DB::table('tb_triagem')
            ->join('tb_tipoentidade', 'tb_triagem.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->select('tb_triagem.*', 'tb_tipoentidade.Nome as PacienteNome')
            ->whereDate('tb_triagem.CREATED_AT', date('Y-m-d'))
            ->where('tb_triagem.Estado', 'Ativo')
            ->orderBy('tb_triagem.Id', 'desc')
            ->get();

        return Inertia::render('Hospitalar/Triagem', [
            'aguardando' => $aguardando,
            'historico' => $historico
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
            'IdPaciente' => 'required',
            'peso' => 'nullable|string',
            'temperatura' => 'nullable|string',
            'tensao' => 'nullable|string',
            'pulso' => 'nullable|string',
            'f_respiratoria' => 'nullable|string',
            'oximetria' => 'nullable|string',
            'obs' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Inserir triagem
            DB::table('tb_triagem')->insert([
                'IdAgenda' => $request->IdAgenda,
                'IdPaciente' => $request->IdPaciente,
                'Peso' => $request->peso,
                'Temperatura' => $request->temperatura,
                'PressaoArterial' => $request->tensao,
                'FrequenciaCardioca' => $request->pulso,
                'FrequenciaRespiratoria' => $request->f_respiratoria,
                'SituacaoOxigenio' => $request->oximetria,
                'Obs' => $request->obs,
                'CREATED_AT' => now(),
                'Estado' => 'Ativo'
            ]);

            // Atualizar situação do agendamento para Consultório
            DB::table('tb_agendamento')
                ->where('Codigo', $request->IdAgenda)
                ->update(['Situacao' => 'Consultorio']);

            DB::commit();
            return redirect()->back()->with('message', 'Triagem realizada e paciente enviado para o consultório!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha ao processar triagem: ' . $e->getMessage()]);
        }
    }
}
