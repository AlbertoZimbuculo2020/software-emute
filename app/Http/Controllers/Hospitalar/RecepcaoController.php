<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecepcaoController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d'));
        $endDate = $request->get('endDate', date('Y-m-d'));

        $medicos = DB::table('tb_medico')
            ->join('tb_tipoentidade', 'tb_medico.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
            ->select('tb_medico.IdTipoEntidade as Id', 'tb_tipoentidade.Nome')
            ->whereIn('tb_tipoentidade.TipoEntidade', ['Medico', 'Medicos'])
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
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome', 'medico.Nome as MedicoNome')
            ->whereBetween('tb_agendamento.DataAgendamento', [$startDate, $endDate])
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        return Inertia::render('Hospitalar/Recepcao', [
            'medicos' => $medicos,
            'consultas' => $consultas,
            'seguradoras' => $seguradoras,
            'agendamentos' => $agendamentos,
            'filters' => [
                'startDate' => $startDate,
                'endDate' => $endDate
            ]
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
            'IdPaciente' => 'nullable',
            'nome' => 'required_without:IdPaciente|string|max:250',
            'IdMedico' => 'required',
            'IdConsulta' => 'required',
            'DataAgendamento' => 'required|date',
            'IdSeguradora' => 'nullable',
            // Patient details for auto-reg
            'filiacao_pai' => 'nullable|string|max:150',
            'filiacao_mae' => 'nullable|string|max:150',
            'data_nascimento' => 'nullable|date',
            'sexo' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:200',
        ]);

        $idPaciente = $request->IdPaciente;

        // Auto-register patient if not exists
        if (!$idPaciente) {
            $lastPaciente = DB::table('tb_paciente')
                ->join('tb_tipoentidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_tipoentidade.Codigo')
                ->where('tb_tipoentidade.TipoEntidade', 'Paciente')
                ->orderBy('tb_paciente.Id', 'desc')
                ->first();

            $newIdNumber = 1;
            if ($lastPaciente && $lastPaciente->IdTipoEntidade && preg_match('/PC(\d+)/', $lastPaciente->IdTipoEntidade, $matches)) {
                $newIdNumber = intval($matches[1]) + 1;
            }
            
            $newCodigo = 'PC' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

            DB::beginTransaction();
            try {
                DB::table('tb_entidade')->insert([
                    'Codigo' => $newCodigo,
                    'Contribuente' => '999999999',
                    'Tipo' => 'SINGULAR',
                    'DataNascimento' => $request->data_nascimento,
                    'Genero' => $request->sexo,
                ]);

                DB::table('tb_tipoentidade')->insert([
                    'Codigo' => $newCodigo,
                    'IdEntidade' => $newCodigo,
                    'Nome' => strtoupper($request->nome),
                    'Telefone' => $request->telefone,
                    'TipoEntidade' => 'Paciente',
                    'Pais' => 'Angola',
                    'Rua' => $request->endereco,
                    'Estado' => 'Ativo'
                ]);

                DB::table('tb_paciente')->insert([
                    'IdTipoEntidade' => $newCodigo,
                    'Pai' => $request->filiacao_pai,
                    'Mae' => $request->filiacao_mae,
                    'Estado' => 'Ativo'
                ]);

                $idPaciente = $newCodigo;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => 'Falha ao cadastrar paciente automaticamente: ' . $e->getMessage()]);
            }
        }

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
            'IdPaciente' => $idPaciente,
            'IdMedico' => $request->IdMedico,
            'IdConsulta' => $request->IdConsulta,
            'Consulta' => $nomeConsulta,
            'IdSeguradora' => $request->IdSeguradora,
            'Seguradora' => $seguradoraNome,
            'DataAgendamento' => $request->DataAgendamento,
            'Valor' => $valor,
            'Situacao' => $request->situacao ?? 'Agendada',
            'Estado' => 'Ativo',
        ]);

        return redirect()->back()->with('message', 'Paciente admitido com sucesso!');
    }

    public function enviarTriagem(Request $request)
    {
        $request->validate(['codigo' => 'required']);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->codigo)
            ->update(['Situacao' => 'Triagem']);

        return redirect()->back()->with('message', 'Paciente enviado para a triagem!');
    }
}
