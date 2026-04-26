<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InternamentoController extends Controller
{
    public function index()
    {
        // Pacientes internados no momento
        $internados = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'paciente.Nome as PacienteNome',
                'medico.Nome as MedicoNome',
                DB::raw("(SELECT MIN(DataInternamento) FROM tb_prescricao WHERE IdAgenda = tb_agendamento.Codigo AND Tipo = 'Internamento') as DataInternamento")
            )
            ->where('tb_agendamento.Situacao', 'Internado')
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        // Histórico de pacientes que já tiveram alta
        $historico = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as paciente', 'tb_agendamento.IdPaciente', '=', 'paciente.Codigo')
            ->select('tb_agendamento.*', 'paciente.Nome as PacienteNome')
            ->where('tb_agendamento.Situacao', 'Alta')
            ->limit(50)
            ->get();

        return Inertia::render('Hospitalar/Internamento', [
            'internados' => $internados,
            'historico' => $historico
        ]);
    }

    public function getDetails($idAgenda)
    {
        $prescricoes = DB::table('tb_prescricao')
            ->where('IdAgenda', $idAgenda)
            ->get();

        $atosMedicos = DB::table('tb_atos_medicos')
            ->where('IdAgenda', $idAgenda)
            ->get();

        $atosEnfermagem = DB::table('tb_atos_enfermagem')
            ->where('IdAgenda', $idAgenda)
            ->get();

        $sinaisVitais = DB::table('tb_triagem')
            ->where('IdAgenda', $idAgenda)
            ->get();

        return response()->json([
            'prescricoes' => $prescricoes,
            'atosMedicos' => $atosMedicos,
            'atosEnfermagem' => $atosEnfermagem,
            'sinaisVitais' => $sinaisVitais
        ]);
    }

    public function storeAto(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
            'tipo' => 'required|in:medico,enfermagem',
            'descricao' => 'required|string',
        ]);

        $table = $request->tipo === 'medico' ? 'tb_atos_medicos' : 'tb_atos_enfermagem';
        
        $agendamento = DB::table('tb_agendamento')->where('Codigo', $request->IdAgenda)->first();
        $paciente = DB::table('tb_tipoentidade')->where('Codigo', $agendamento->IdPaciente)->value('Nome');

        $data = [
            'IdAgenda' => $request->IdAgenda,
            'DataAto' => now(),
            'Descricao' => $request->descricao,
            'ID_UTILIZADOR' => Auth::id() ?? 1,
            'Paciente' => $paciente ?? 'N/D',
            'Estado' => 'Ativo'
        ];

        if ($request->tipo === 'medico') {
            $data['Medico'] = Auth::user()->NOME_UTILIZADOR ?? 'Médico';
        } else {
            $data['Enfermeiro'] = Auth::user()->NOME_UTILIZADOR ?? 'Enfermeiro';
        }

        DB::table($table)->insert($data);

        return redirect()->back()->with('message', 'Ato registrado com sucesso!');
    }

    public function darAlta($idAgenda)
    {
        DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->update(['Situacao' => 'Alta']);

        return redirect()->back()->with('message', 'Alta registrada com sucesso!');
    }
}
