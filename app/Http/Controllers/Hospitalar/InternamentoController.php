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
            'PressaoArterialBE' => $request->PressaoArterialBE,
            'FrequenciaCardioca' => $request->FrequenciaCardioca,
            'PulsoBE' => $request->PulsoBE,
            'FrequenciaRespiratoria' => $request->FrequenciaRespiratoria,
            'SituacaoOxigenio' => $request->SituacaoOxigenio,
            'Obs' => $request->Obs,
            'Imferemiro' => Auth::user()->name ?? 'Enfermeiro',
            'Estado' => 'Ativo',
            'CREATED_AT' => now()
        ]);

        return redirect()->back()->with('message', 'Sinais vitais registrados!');
    }

    public function darAlta(Request $request, $id)
    {
        $request->validate([
            'Operado' => 'nullable|string',
            'Complicacoes' => 'nullable|string',
            'Repouso' => 'nullable|string',
            'Obs' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            // Atualiza Agendamento
            DB::table('tb_agendamento')
                ->where('Codigo', $id)
                ->update(['Situacao' => 'Alta']);

            // Grava na tb_alta
            DB::table('tb_alta')->insert([
                'IdAgenda' => $id,
                'DataAlta' => now(),
                'Operado' => $request->Operado,
                'Complicacoes' => $request->Complicacoes,
                'Repouso' => $request->Repouso,
                'Obs' => $request->Obs,
                'Medico' => auth()->user()->name,
                'IdMedico' => auth()->user()->id,
            ]);
        });

        return redirect()->back()->with('message', 'Alta processada com sucesso');
    }

    public function imprimirProcesso($id)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
            ->leftJoin('tb_entidade as e', 'p.IdEntidade', '=', 'e.Codigo')
            ->leftJoin('tb_tipoentidade as m', 'tb_agendamento.IdMedico', '=', 'm.Codigo')
            ->select(
                'tb_agendamento.*', 
                'p.Nome as PacienteNome', 
                'm.Nome as MedicoNome', 
                'e.Genero as Sexo', 
                'e.DataNascimento as Nascimento', 
                'p.Telefone', 
                'p.Rua as Morada'
            )
            ->where('tb_agendamento.Codigo', $id)
            ->first();

        if (!$agendamento) abort(404);

        $prescricoes = DB::table('tb_prescricao')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('DataInternamento', 'desc')
            ->get();

        $atosMedicos = DB::table('tb_atos_medicos')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('DataAto', 'desc')
            ->get();

        $atosEnfermagem = DB::table('tb_atos_enfermagem')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('DataAto', 'desc')
            ->get();

        $sinaisVitais = DB::table('tb_triagem')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('CREATED_AT', 'desc')
            ->get();

        $alta = DB::table('tb_alta')
            ->where('IdAgenda', $id)
            ->first();

        $empresa = DB::table('tb_empresa')->first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.processo_clinico', [
            'agendamento' => $agendamento,
            'prescricoes' => $prescricoes,
            'atosMedicos' => $atosMedicos,
            'atosEnfermagem' => $atosEnfermagem,
            'sinaisVitais' => $sinaisVitais,
            'alta' => $alta,
            'empresa' => $empresa
        ]);

        return $pdf->stream("Processo_Clinico_{$id}.pdf");
    }

    public function imprimirAtosEnfermagem($id)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
            ->select('tb_agendamento.*', 'p.Nome as PacienteNome')
            ->where('tb_agendamento.Codigo', $id)
            ->first();

        $atosEnfermagem = DB::table('tb_atos_enfermagem')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('DataAto', 'desc')
            ->get();

        $empresa = DB::table('tb_empresa')->first();
        if ($empresa && isset($empresa->IMAGEM)) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.atos_enfermagem', [
            'agendamento' => $agendamento,
            'atosEnfermagem' => $atosEnfermagem,
            'empresa' => $empresa
        ]);

        return $pdf->stream("Atos_Enfermagem_{$id}.pdf");
    }

    public function imprimirVitais($id)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
            ->select('tb_agendamento.*', 'p.Nome as PacienteNome')
            ->where('tb_agendamento.Codigo', $id)
            ->first();

        $sinaisVitais = DB::table('tb_triagem')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->orderBy('CREATED_AT', 'desc')
            ->get();

        $empresa = DB::table('tb_empresa')->first();
        if ($empresa && isset($empresa->IMAGEM)) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.triagem_report', [
            'agendamento' => $agendamento,
            'sinaisVitais' => $sinaisVitais,
            'empresa' => $empresa
        ]);

        return $pdf->stream("Controlo_Vitais_{$id}.pdf");
    }
}
