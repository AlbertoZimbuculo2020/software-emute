<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ConsultorioController extends Controller
{
    public function index()
    {
        $aguardando = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'tb_tipoentidade.Nome as PacienteNome',
                'tb_tipoentidade.Telefone',
                'tb_tipoentidade.Rua',
                'tb_tipoentidade.Cidade',
                'tb_entidade.DataNascimento',
                'tb_entidade.Genero',
                'medico.Nome as MedicoNome'
            )
            ->whereIn('tb_agendamento.Situacao', ['Consultorio', 'Reconsulta'])
            ->where('tb_agendamento.Estado', 'Ativo')
            ->get();

        $catalogoExames = DB::table('tb_exames')
            ->where('Estado', 'Ativo')
            ->select('Id', 'Codigo', 'Descricao', 'Categoria', 'Tipo', 'Exame_Fora')
            ->get();

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return Inertia::render('Hospitalar/Consultorio', [
            'aguardando' => $aguardando,
            'catalogoExames' => $catalogoExames,
            'empresa' => $empresa
        ]);
    }

    public function getPatientData($idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')
            ->where('Codigo', $idAgenda)
            ->first();

        if (!$agendamento) return response()->json(['error' => 'Agendamento não encontrado'], 404);

        $triagem = DB::table('tb_triagem')
            ->where('IdAgenda', $idAgenda)
            ->first();

        $historico = DB::table('tb_agendamento')
            ->where('IdPaciente', $agendamento->IdPaciente)
            ->where('Codigo', '!=', $idAgenda)
            ->orderBy('DataAgendamento', 'desc')
            ->limit(10)
            ->get();

        $exames_solicitados = DB::table('tb_resultado_exame')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', '!=', 'Removido')
            ->get();

        return response()->json([
            'triagem' => $triagem,
            'historico' => $historico,
            'exames_solicitados' => $exames_solicitados
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Codigo' => 'required',
            'qp' => 'nullable|string',
            'hda' => 'nullable|string',
            'obj' => 'nullable|string',
            'complementares' => 'nullable|string',
            'recomendacoes' => 'nullable|string',
            'situacao' => 'required|string',
        ]);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->Codigo)
            ->update([
                'QP' => $request->qp,
                'HDA' => $request->hda,
                'OBJ' => $request->obj,
                'COMPLEMENTARES' => $request->complementares,
                'RECOMENDACOES' => $request->recomendacoes,
                'Situacao' => $request->situacao,
            ]);

        // Se houver receita, poderíamos salvar aqui também (em tb_receita)
        // Se houver exames solicitados, salvaríamos em tb_resultado_exame com status 'Pendente'

        return redirect()->back()->with('message', 'Dados clínicos salvos com sucesso!');
    }
}
