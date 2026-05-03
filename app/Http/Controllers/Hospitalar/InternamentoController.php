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
            ->where('Estado', 'Ativo')
            ->orderBy('DataInternamento', 'desc')
            ->get();

        $atosMedicos = DB::table('tb_atos_medicos')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', 'Ativo')
            ->orderBy('DataAto', 'desc')
            ->get();

        $atosEnfermagem = DB::table('tb_atos_enfermagem')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', 'Ativo')
            ->orderBy('DataAto', 'desc')
            ->get();

        $sinaisVitais = DB::table('tb_triagem')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', 'Ativo')
            ->orderBy('CREATED_AT', 'desc')
            ->get();
            
        $alta = DB::table('tb_alta')
            ->where('IdAgenda', $idAgenda)
            ->first();

        return response()->json([
            'prescricoes' => $prescricoes,
            'atosMedicos' => $atosMedicos,
            'atosEnfermagem' => $atosEnfermagem,
            'sinaisVitais' => $sinaisVitais,
            'alta' => $alta
        ]);
    }

    public function storePrescricao(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
            'Descricao' => 'required|string',
            'Tipo' => 'required|in:Internamento,Observacao',
        ]);

        DB::table('tb_prescricao')->insert([
            'IdAgenda' => $request->IdAgenda,
            'DataInternamento' => now(),
            'Descricao' => $request->Descricao,
            'Observacao' => $request->Observacao,
            'Tipo' => $request->Tipo,
            'Medico' => Auth::user()->name ?? 'Médico',
            'Estado' => 'Ativo',
            'Cumprimento' => 'False',
            'Cumprimento1' => 'False',
            'Cumprimento2' => 'False',
            'Cumprimento3' => 'False',
        ]);

        return redirect()->back()->with('message', 'Prescrição gravada com sucesso!');
    }

    public function toggleCumprimento(Request $request, $id)
    {
        $request->validate([
            'campo' => 'required|in:Cumprimento,Cumprimento1,Cumprimento2,Cumprimento3',
            'valor' => 'required|boolean'
        ]);

        $update = [
            $request->campo => $request->valor ? 'True' : 'False'
        ];

        if ($request->valor) {
            $update['Infermeiro'] = Auth::user()->name ?? 'Enfermeiro';
        }

        DB::table('tb_prescricao')->where('Id', $id)->update($update);

        return response()->json(['message' => 'Status atualizado']);
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
            $data['Medico'] = Auth::user()->name ?? 'Médico';
        } else {
            $data['Enfermeiro'] = Auth::user()->name ?? 'Enfermeiro';
        }

        DB::table($table)->insert($data);

        return redirect()->back()->with('message', 'Ato registrado com sucesso!');
    }

    public function storeSinaisVitais(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
        ]);

        $agendamento = DB::table('tb_agendamento')->where('Codigo', $request->IdAgenda)->first();

        DB::table('tb_triagem')->insert([
            'IdAgenda' => $request->IdAgenda,
            'IdPaciente' => $agendamento->IdPaciente,
            'Peso' => $request->Peso,
            'Temperatura' => $request->Temperatura,
            'PressaoArterial' => $request->PressaoArterial,
            'FrequenciaCardioca' => $request->FrequenciaCardioca,
            'FrequenciaRespiratoria' => $request->FrequenciaRespiratoria,
            'SituacaoOxigenio' => $request->SituacaoOxigenio,
            'Obs' => $request->Obs,
            'Imferemiro' => Auth::user()->name ?? 'Enfermeiro',
            'Estado' => 'Ativo',
            'CREATED_AT' => now()
        ]);

        return redirect()->back()->with('message', 'Sinais vitais registrados!');
    }

    public function darAlta(Request $request, $idAgenda)
    {
        $request->validate([
            'Operado' => 'nullable|string',
            'Complicacoes' => 'nullable|string',
            'Repouso' => 'nullable|string',
            'Obs' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $idAgenda) {
            // Atualiza o agendamento
            DB::table('tb_agendamento')
                ->where('Codigo', $idAgenda)
                ->update(['Situacao' => 'Alta']);

            // Insere na tabela de alta
            DB::table('tb_alta')->updateOrInsert(
                ['IdAgenda' => $idAgenda],
                [
                    'Codigo' => 'ALTA-' . time(), // Gerando um código simples
                    'Idmedico' => Auth::id() ?? 1,
                    'Assinatura' => Auth::user()->name ?? 'Médico',
                    'Operado' => $request->Operado,
                    'Complicacoes' => $request->Complicacoes,
                    'Repouso' => $request->Repouso,
                    'Obs' => $request->Obs,
                    'Estado' => 'Ativo',
                    'CREATED_AT' => now()
                ]
            );
        });

        return redirect()->back()->with('message', 'Alta registrada com sucesso!');
    }
}
