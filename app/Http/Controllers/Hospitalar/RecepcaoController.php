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
            ->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.IdEntidade')
            ->select('tb_medico.Id', 'tb_tipoentidade.Nome')
            ->where('tb_medico.Estado', 'Ativo')
            ->get();

        $consultas = DB::table('tb_consulta')
            ->where('Estado', 'Ativo')
            ->get();

        $seguradoras = DB::table('tb_seguradora')
            ->join('tb_tipoentidade', 'tb_seguradora.IdTipoEntidade', '=', 'tb_tipoentidade.IdEntidade')
            ->select('tb_seguradora.Id', 'tb_tipoentidade.Nome')
            ->where('tb_seguradora.Estado', 'Ativo')
            ->get();

        $agendamentos = DB::table('tb_agendamento')
            ->join('tb_paciente', 'tb_agendamento.IdPaciente', '=', 'tb_paciente.Id')
            ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.IdEntidade')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome')
            ->where('tb_agendamento.DataAgendamento', date('Y-m-d'))
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
            ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.IdEntidade')
            ->where('tb_tipoentidade.Nome', 'LIKE', "%{$term}%")
            ->orWhere('tb_paciente.Id', $term)
            ->select('tb_paciente.*', 'tb_tipoentidade.Nome', 'tb_tipoentidade.Telefone', 'tb_tipoentidade.Rua as Endereco')
            ->get();

        return response()->json($pacientes);
    }

    public function store(Request $request)
    {
        // Logic to save appointment
        $data = $request->validate([
            'IdPaciente' => 'required',
            'IdMedico' => 'required',
            'IdConsulta' => 'required',
            'DataAgendamento' => 'required|date',
            'IdSeguradora' => 'nullable',
        ]);

        $id = DB::table('tb_agendamento')->insertGetId($data);

        return redirect()->back()->with('message', 'Agendamento realizado com sucesso!');
    }
}
