<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecepcaoController extends Controller
{
    public function index()
    {
        $medicos = DB::table('tb_medico')
            ->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->select('tb_medico.IdTipoEntidade as Id', 'tb_tipoentidade.Nome')
            ->where('tb_medico.Estado', 'Ativo')
            ->get();

        $consultas = DB::table('tb_consulta')
            ->where('Estado', 'Ativo')
            ->get();

        $seguradoras = DB::table('tb_seguradora')
            ->join('tb_tipoentidade', 'tb_seguradora.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->select('tb_seguradora.IdTipoEntidade as Id', 'tb_tipoentidade.Nome')
            ->where('tb_seguradora.Estado', 'Ativo')
            ->get();

        $agendamentos = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome')
            ->where('tb_agendamento.DataAgendamento', date('Y-m-d'))
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        return Inertia::render('Hospitalar/Recepcao', [
            'medicos' => $medicos,
            'consultas' => $consultas,
            'seguradoras' => $seguradoras,
            'agendamentos' => $agendamentos
        ]);
    }

    public function searchPaciente(Request $request)
    {
        $term = $request->term;
        
        $pacientes = DB::table('tb_paciente')
            ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->where('tb_tipoentidade.Nome', 'LIKE', "%{$term}%")
            ->orWhere('tb_tipoentidade.Codigo', $term)
            ->select('tb_paciente.*', 'tb_tipoentidade.Nome', 'tb_tipoentidade.Telefone', 'tb_tipoentidade.Rua as Endereco', 'tb_tipoentidade.Codigo')
            ->get();

        return response()->json($pacientes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'IdPaciente' => 'required',
            'IdMedico' => 'required',
            'IdConsulta' => 'required',
            'DataAgendamento' => 'required|date',
            'IdSeguradora' => 'nullable',
        ]);

        $consultaInfo = DB::table('tb_consulta')->where('Id', $request->IdConsulta)->first();
        $valor = $consultaInfo ? $consultaInfo->Valor : 0;
        $nomeConsulta = $consultaInfo ? $consultaInfo->Descricao : '';
        
        $seguradoraNome = '';
        if ($request->IdSeguradora) {
            $segInfo = DB::table('tb_tipoentidade')->where('Codigo', $request->IdSeguradora)->first();
            $seguradoraNome = $segInfo ? $segInfo->Nome : '';
        }

        $lastAgendamento = DB::table('tb_agendamento')->orderBy('Id', 'desc')->first();
        $newIdNumber = 1;
        if ($lastAgendamento && $lastAgendamento->Codigo && preg_match('/AGD(\d+)/', $lastAgendamento->Codigo, $matches)) {
            $newIdNumber = intval($matches[1]) + 1;
        }
        $newCodigo = 'AGD' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

        DB::table('tb_agendamento')->insert([
            'Codigo' => $newCodigo,
            'IdPaciente' => $request->IdPaciente,
            'IdMedico' => $request->IdMedico,
            'IdConsulta' => $request->IdConsulta,
            'Consulta' => $nomeConsulta,
            'IdSeguradora' => $request->IdSeguradora,
            'Seguradora' => $seguradoraNome,
            'DataAgendamento' => $request->DataAgendamento,
            'Valor' => $valor,
            'Situacao' => 'Triagem',
            'Estado' => 'Ativo',
        ]);

        return redirect()->back()->with('message', 'Agendamento/Admissão realizada com sucesso!');
    }
}
