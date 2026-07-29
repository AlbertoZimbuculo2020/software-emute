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
            ->orderBy('tb_agendamento.Id', 'desc')
            ->limit(200)
            ->get();

        // Exames por Pagar (Carrinho)
        $examesPendentes = DB::table('tb_resultado_exame')
            ->join('tb_agendamento', 'tb_resultado_exame.IdAgenda', '=', 'tb_agendamento.Codigo')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->select(
                'tb_resultado_exame.IdAgenda as AGENDA',
                'tb_tipoentidade.Nome as PACIENTE',
                'tb_resultado_exame.DataExame as DATA',
                'tb_resultado_exame.Descricao as EXAME',
                'tb_resultado_exame.Valor as VALOR',
                'tb_tipoentidade.Codigo as PROCESSO'
            )
            ->where('tb_resultado_exame.Estado', 'Ativo')
            ->orderBy('tb_resultado_exame.Id', 'desc')
            ->limit(200)
            ->get();

        // Área de Internamento
        $internamentosPendentes = DB::table('tb_prescricao')
            ->join('tb_agendamento', 'tb_prescricao.IdAgenda', '=', 'tb_agendamento.Codigo')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->select(
                'tb_agendamento.Codigo',
                'tb_tipoentidade.Nome as Paciente',
                'tb_agendamento.Consulta',
                'tb_prescricao.DataInternamento',
                'tb_prescricao.Observacao'
            )
            ->where('tb_prescricao.Tipo', 'Internamento')
            ->where('tb_prescricao.Cumprimento', 'False')
            ->where('tb_prescricao.Estado', 'Ativo')
            ->orderBy('tb_prescricao.Id', 'desc')
            ->get();

        return Inertia::render('Hospitalar/Recepcao', [
            'medicos' => $medicos,
            'consultas' => $consultas,
            'seguradoras' => $seguradoras,
            'agendamentos' => $agendamentos,
            'examesPendentes' => $examesPendentes,
            'internamentosPendentes' => $internamentosPendentes,
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
            ->join('tb_entidade', 'tb_paciente.IdTipoEntidade', '=', 'tb_entidade.Codigo')
            ->where('tb_tipoentidade.Nome', 'LIKE', "%{$term}%")
            ->orWhere('tb_tipoentidade.Codigo', $term)
            ->select(
                'tb_paciente.*', 
                'tb_tipoentidade.Nome', 
                'tb_tipoentidade.Telefone', 
                'tb_tipoentidade.Rua as Endereco', 
                'tb_tipoentidade.Cidade',
                'tb_tipoentidade.Codigo',
                'tb_entidade.DataNascimento',
                'tb_entidade.Genero'
            )
            ->limit(10)
            ->get();

        return response()->json($pacientes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'IdPaciente' => 'nullable',
            'nome' => 'required_without:IdPaciente|string|max:250',
            'IdMedico' => 'required_unless:situacao,Laboratorio',
            'IdConsulta' => 'required_unless:situacao,Laboratorio',
            'DataAgendamento' => 'required|date',
            'IdSeguradora' => 'nullable',
            'filiacao_pai' => 'nullable|string|max:150',
            'filiacao_mae' => 'nullable|string|max:150',
            'data_nascimento' => 'nullable|date',
            'sexo' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:200',
            'cidade' => 'nullable|string|max:100',
            'situacao' => 'required|string',
        ]);

        $idPaciente = $request->IdPaciente;

        DB::beginTransaction();
        try {
            // Se o paciente já existe, atualiza os dados (comportamento do frmRecepcao2)
            if ($idPaciente) {
                DB::table('tb_entidade')
                    ->where('Codigo', $idPaciente)
                    ->update([
                        'DataNascimento' => $request->data_nascimento,
                        'Genero' => $request->sexo,
                    ]);

                DB::table('tb_tipoentidade')
                    ->where('Codigo', $idPaciente)
                    ->update([
                        'Nome' => strtoupper($request->nome),
                        'Telefone' => $request->telefone ?? 'SEM',
                        'Rua' => $request->endereco ?? 'SEM',
                        'Cidade' => $request->cidade ?? 'SEM',
                    ]);

                DB::table('tb_paciente')
                    ->where('IdTipoEntidade', $idPaciente)
                    ->update([
                        'Pai' => $request->filiacao_pai,
                        'Mae' => $request->filiacao_mae,
                        'IdSegura' => $request->IdSeguradora,
                    ]);
            } else {
                // Auto-register patient if not exists
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

                DB::table('tb_entidade')->insert([
                    'Codigo' => $newCodigo,
                    'Contribuente' => '999999999',
                    'Tipo' => 'SINGULAR',
                    'DataNascimento' => $request->data_nascimento,
                    'Genero' => $request->sexo,
                    'CREATED_AT' => now(),
                ]);

                DB::table('tb_tipoentidade')->insert([
                    'Codigo' => $newCodigo,
                    'IdEntidade' => $newCodigo,
                    'Nome' => strtoupper($request->nome),
                    'Telefone' => $request->telefone ?? 'SEM',
                    'TipoEntidade' => 'Paciente',
                    'Pais' => 'Angola',
                    'Rua' => $request->endereco ?? 'SEM',
                    'Cidade' => $request->cidade ?? 'SEM',
                    'Estado' => 'Ativo',
                    'CREATED_AT' => now(),
                ]);

                DB::table('tb_paciente')->insert([
                    'IdTipoEntidade' => $newCodigo,
                    'Pai' => $request->filiacao_pai,
                    'Mae' => $request->filiacao_mae,
                    'IdSegura' => $request->IdSeguradora,
                    'Estado' => 'Ativo',
                    'CREATED_AT' => now(),
                ]);

                $idPaciente = $newCodigo;
            }

            // Agendamento
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
            $agdCodigo = 'AGD' . str_pad($newIdNumber, 3, '0', STR_PAD_LEFT);

            DB::table('tb_agendamento')->insert([
                'Codigo' => $agdCodigo,
                'IdPaciente' => $idPaciente,
                'IdMedico' => $request->IdMedico,
                'IdConsulta' => $request->IdConsulta,
                'Consulta' => $nomeConsulta,
                'IdSeguradora' => $request->IdSeguradora,
                'Seguradora' => $seguradoraNome,
                'DataAgendamento' => $request->DataAgendamento,
                'Valor' => $valor,
                'Situacao' => $request->situacao ?? 'Agendada',
                'Area' => strtoupper($request->situacao ?? 'Agendada'),
                'Estado' => 'Ativo',
                'CREATED_AT' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Operação realizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Falha no processamento: ' . $e->getMessage()]);
        }
    }

    public function enviarTriagem(Request $request)
    {
        $request->validate(['codigo' => 'required']);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->codigo)
            ->update([
                'Situacao' => 'Triagem',
                'Area' => 'TRIAGEM'
            ]);

        return redirect()->back()->with('message', 'Paciente enviado para a triagem!');
    }
}
