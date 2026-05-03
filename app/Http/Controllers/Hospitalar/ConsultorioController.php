<?php

namespace App\Http\Controllers\Hospitalar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsultorioController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $query = DB::table('tb_agendamento')
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
            ->whereIn('tb_agendamento.Situacao', ['Consultorio', 'Reconsulta', 'Laboratorio'])
            ->where('tb_agendamento.Estado', 'Ativo');

        // Restriction requested: Admins who are not doctors shouldn't just browse all patients.
        // If the user has an ID_PESSOA (meaning they are a specialist/doctor),
        // we strictly filter by their appointments.
        if ($user->ID_PESSOA) {
            $query->where('tb_agendamento.IdMedico', $user->ID_PESSOA);
        } else if ($user->ACESSO !== 'SIM') {
            // If not a super-admin and has no ID_PESSOA, show nothing or only public ones (though none are public here)
            $query->where('tb_agendamento.IdMedico', 'PROTECTED');
        }

        $aguardando = $query->get();

        $catalogoExames = DB::table('tb_exames')
            ->where('Estado', 'Ativo')
            ->select('Id', 'Codigo', 'Descricao', 'Categoria', 'Tipo', 'Exame_Fora')
            ->get();

        $catalogoFarmacos = DB::table('tb_farmaco')
            ->where('Estado', 'Ativo')
            ->select('Id', 'Descricao')
            ->get();

        $listaMedicos = DB::table('tb_tipoentidade')
            ->where('TipoEntidade', 'Medico')
            ->where('Estado', 'Ativo')
            ->select('Codigo', 'Nome')
            ->get();

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $catalogoCid = DB::table('tb_ciddez')
            ->where('ESTADO', 'Activado')
            ->select('codigo', 'Indicador', 'Descricao')
            ->get();

        return Inertia::render('Hospitalar/Consultorio', [
            'aguardando'      => $aguardando,
            'catalogoExames'  => $catalogoExames,
            'catalogoFarmacos'=> $catalogoFarmacos,
            'catalogoCid'     => $catalogoCid,
            'listaMedicos'    => $listaMedicos,
            'empresa'         => $empresa
        ]);
    }

    public function getPatientData($idAgenda)
    {
        $agendamento = DB::table('tb_agendamento')->where('Codigo', $idAgenda)->first();
        if (!$agendamento) return response()->json(['error' => 'Agendamento não encontrado'], 404);

        $triagem = DB::table('tb_triagem')->where('IdAgenda', $idAgenda)->first();

        $historico = DB::table('tb_agendamento')
            ->where('IdPaciente', $agendamento->IdPaciente)
            ->orderBy('DataAgendamento', 'desc')
            ->limit(10)
            ->get();

        $exames_solicitados = DB::table('tb_resultado_exame')
            ->where('tb_resultado_exame.IdAgenda', $idAgenda)
            ->where('tb_resultado_exame.Estado', '!=', 'Removido')
            ->leftJoin('tb_exames', 'tb_resultado_exame.CodExame', '=', 'tb_exames.Codigo')
            ->select(
                'tb_resultado_exame.Id',
                'tb_resultado_exame.IdAgenda',
                'tb_resultado_exame.CodExame',
                'tb_resultado_exame.Descricao',
                'tb_resultado_exame.Resultado',
                'tb_resultado_exame.Obs',
                'tb_resultado_exame.Estado',
                'tb_exames.Categoria',
                'tb_exames.Filhos'
            )
            ->get();

        $receita = DB::table('tb_receita')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', '!=', 'Removido')
            ->get();

        return response()->json([
            'triagem'           => $triagem,
            'historico'         => $historico,
            'exames_solicitados'=> $exames_solicitados,
            'receita'           => $receita,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Codigo'        => 'required',
            'qp'            => 'nullable|string',
            'hda'           => 'nullable|string',
            'obj'           => 'nullable|string',
            'complementares'=> 'nullable|string',
            'recomendacoes' => 'nullable|string',
            'situacao'      => 'required|string',
        ]);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->Codigo)
            ->update([
                'QP'             => $request->qp,
                'HDA'            => $request->hda,
                'OBJ'            => $request->obj,
                'COMPLEMENTARES' => $request->complementares,
                'RECOMENDACOES'  => $request->recomendacoes,
                'Situacao'       => $request->situacao,
            ]);

        return redirect()->back()->with('message', 'Dados clínicos gravados com sucesso!');
    }

    public function solicitarExames(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required',
            'exames'   => 'required|array',
        ]);

        $agenda = DB::table('tb_agendamento')->where('Codigo', $request->IdAgenda)->first();
        if (!$agenda) return response()->json(['error' => 'Agendamento não encontrado'], 404);

        $ids = array_map(fn($ex) => str_replace('cat_', '', $ex), $request->exames);
        $exames = DB::table('tb_exames')->whereIn('Id', $ids)->get();

        $utilizador = auth()->user()->NOME_UTILIZADOR ?? 'Medico';
        $userId     = auth()->user()->id ?? 0;

        foreach ($exames as $ex) {
            $exists = DB::table('tb_resultado_exame')
                ->where('IdAgenda', $request->IdAgenda)
                ->where('CodExame', $ex->Codigo)
                ->exists();

            if (!$exists) {
                DB::table('tb_resultado_exame')->insert([
                    'IdAgenda'   => $request->IdAgenda,
                    'Codigo'     => $request->IdAgenda,
                    'CodExame'   => $ex->Codigo,
                    'Descricao'  => $ex->Descricao,
                    'Categoria'  => $ex->Categoria,
                    'Tipo'       => $ex->Tipo,
                    'DataExame'  => now(),
                    'Estado'     => 'Ativo',
                    'Utilizador' => $utilizador,
                ]);

                // Billing: Add to Carrinho Hospitalar
                DB::table('tb_carrinho_hospitalar')->insert([
                    'ID_ENTIDADE'    => $agenda->IdPaciente,
                    'ID_AGENDA'      => $request->IdAgenda,
                    'PRODUTO'        => $ex->IdProduto,
                    'CODIGO'         => $ex->Codigo,
                    'DESCRICAO'      => $ex->Descricao,
                    'QUANTIDADE'     => 1,
                    'PRECO'          => $ex->Valor ?? 0,
                    'TOTAL'          => $ex->Valor ?? 0,
                    'IVA'            => 0,
                    'DESCONTO'       => 0,
                    'ESTADO'         => 'N_PAGO',
                    'TIPO'           => 'Exame',
                    'ID_UTILIZADOR'  => $userId,
                    'DATA_'          => now(),
                ]);
            }
        }

        // Só muda para Laboratório se ainda estiver no Consultório
        DB::table('tb_agendamento')
            ->where('Codigo', $request->IdAgenda)
            ->whereIn('Situacao', ['Consultorio', 'Reconsulta'])
            ->update(['Situacao' => 'Laboratorio']);

        return response()->json(['message' => 'Exames enviados com sucesso!']);
    }

    // ─────────────────────────────────────────────
    //  RECEITA MÉDICA
    // ─────────────────────────────────────────────
    public function storeReceita(Request $request)
    {
        $request->validate([
            'IdAgenda' => 'required|string',
            'itens'    => 'required|array|min:1',
        ]);

        $utilizador = auth()->user()->NOME_UTILIZADOR ?? 'Medico';

        foreach ($request->itens as $item) {
            DB::table('tb_receita')->insert([
                'IdAgenda'  => $request->IdAgenda,
                'Farmaco'   => $item['farmaco'] ?? '',
                'Dosagem'   => $item['dosagem'] ?? '',
                'Dias'      => $item['dias'] ?? '',
                'Estado'    => 'Ativo',
            ]);
        }

        return response()->json(['message' => 'Receita gravada com sucesso!']);
    }

    public function destroyReceitaItem(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        DB::table('tb_receita')->where('Id', $request->id)->update(['Estado' => 'Removido']);
        return response()->json(['message' => 'Item removido!']);
    }

    // ─────────────────────────────────────────────
    //  GRAVAR RESULTADO DE EXAME (do modal)
    // ─────────────────────────────────────────────
    public function gravarResultadoExame(Request $request)
    {
        $request->validate([
            'exameId'   => 'required|integer',
            'resultado' => 'required|string',
            'obs'       => 'nullable|string',
        ]);

        DB::table('tb_resultado_exame')
            ->where('Id', $request->exameId)
            ->update([
                'Resultado' => $request->resultado,
                'Obs'       => $request->obs,
                'Estado'    => 'Concluido',
            ]);

        return response()->json(['message' => 'Resultado gravado com sucesso!']);
    }

    // ─────────────────────────────────────────────
    //  ENCAMINHAR PARA OUTRO MÉDICO
    // ─────────────────────────────────────────────
    public function encaminhar(Request $request)
    {
        $request->validate([
            'IdAgenda'   => 'required|string',
            'IdMedico'   => 'required|string',
            'motivo'     => 'nullable|string',
        ]);

        DB::table('tb_agendamento')
            ->where('Codigo', $request->IdAgenda)
            ->update([
                'IdMedico'   => $request->IdMedico,
                'Situacao'   => 'Consultorio',
                'RECOMENDACOES' => $request->motivo,
            ]);

        return response()->json(['message' => 'Paciente encaminhado com sucesso!']);
    }

    public function imprimirFicha($idAgenda)
    {
        $paciente = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'tb_tipoentidade.Nome as PacienteNome',
                'tb_entidade.DataNascimento',
                'tb_entidade.Genero',
                'medico.Nome as MedicoNome'
            )
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$paciente) return abort(404);

        $triagem = DB::table('tb_triagem')->where('IdAgenda', $idAgenda)->first();
        $exames = DB::table('tb_resultado_exame')->where('IdAgenda', $idAgenda)->where('Estado', '!=', 'Removido')->get();
        $receita = DB::table('tb_receita')->where('IdAgenda', $idAgenda)->where('Estado', '!=', 'Removido')->get();
        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $idade = 'N/D';
        if ($paciente->DataNascimento) {
            $birthDate = new \DateTime($paciente->DataNascimento);
            $idade = (new \DateTime())->diff($birthDate)->y . ' Anos';
        }

        $view = 'pdf.ficha_medica';
        $data = compact('paciente', 'triagem', 'exames', 'receita', 'empresa', 'idade');

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $pdf = Pdf::loadView('pdf.layout_economico', ['original_view' => $view, 'data' => $data])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView($view, $data);
        }
        
        return $pdf->stream("ficha_medica_{$idAgenda}.pdf");
    }

    public function imprimirReceita($idAgenda)
    {
        $paciente = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome', 'medico.Nome as MedicoNome')
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$paciente) return abort(404);

        $itens = DB::table('tb_receita')
            ->where('IdAgenda', $idAgenda)
            ->where('Estado', '!=', 'Removido')
            ->get();

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $dataExtenso = "Luanda, " . date('d') . " de " . $this->mesExtenso(date('m')) . " de " . date('Y');

        $view = 'pdf.receita_medica';
        $data = compact('paciente', 'itens', 'empresa', 'dataExtenso');

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $pdf = Pdf::loadView('pdf.layout_economico', ['original_view' => $view, 'data' => $data])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView($view, $data);
        }
        
        return $pdf->stream("receita_{$idAgenda}.pdf");
    }

    public function imprimirRequisicao($idAgenda)
    {
        $paciente = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select(
                'tb_agendamento.*', 
                'tb_tipoentidade.Nome as PacienteNome', 
                'tb_tipoentidade.Telefone',
                'tb_entidade.DataNascimento',
                'tb_entidade.Genero',
                'medico.Nome as MedicoNome'
            )
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$paciente) return abort(404);

        $requestedExams = request('exames');
        if (!empty($requestedExams)) {
            $ids = array_map('trim', explode(',', $requestedExams));
            $cleanIds = array_map(function($id) {
                return str_replace(['cat_', 'sol_'], '', $id);
            }, $ids);
            
            $examesSolicitados = DB::table('tb_resultado_exame')
                ->where('IdAgenda', $idAgenda)
                ->whereIn('Id', $cleanIds)
                ->get();

            if ($examesSolicitados->count() > 0) {
                $exames = $examesSolicitados;
            } else {
                $exames = DB::table('tb_exames')
                    ->whereIn('Id', $cleanIds)
                    ->get()
                    ->map(function ($ex) {
                        return (object)[
                            'Descricao' => $ex->Descricao,
                            'Categoria' => $ex->Categoria ?? 'LABORATÓRIO GERAL',
                            'Filhos' => $ex->Filhos ?? '',
                            'Resultado' => '',
                            'Referencia' => $ex->Referencia ?? '',
                            'Estado' => 'Pendente'
                        ];
                    });
            }
        } else {
            $exames = DB::table('tb_resultado_exame')
                ->where('IdAgenda', $idAgenda)
                ->where('Estado', '!=', 'Removido')
                ->get();
        }

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $idade = 'N/D';
        if ($paciente->DataNascimento) {
            $birthDate = new \DateTime($paciente->DataNascimento);
            $idade = (new \DateTime())->diff($birthDate)->y . ' Anos';
        }

        $view = 'pdf.requisicao_exames';
        $data = compact('paciente', 'exames', 'empresa', 'idade');

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $pdf = Pdf::loadView('pdf.layout_economico', ['original_view' => $view, 'data' => $data])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView($view, $data);
        }
        
        return $pdf->stream("requisicao_{$idAgenda}.pdf");
    }

    public function imprimirJustificativo($idAgenda)
    {
        $paciente = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome', 'tb_entidade.DataNascimento', 'tb_entidade.Genero', 'medico.Nome as MedicoNome')
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$paciente) return abort(404);

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $view = 'pdf.justificativo_medico';
        $data = compact('paciente', 'empresa');

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $pdf = Pdf::loadView('pdf.layout_economico', ['original_view' => $view, 'data' => $data])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView($view, $data);
        }
        
        return $pdf->stream("justificativo_{$idAgenda}.pdf");
    }

    public function imprimirGuia($idAgenda)
    {
        $paciente = DB::table('tb_agendamento')
            ->join('tb_tipoentidade', 'tb_agendamento.IdPaciente', '=', 'tb_tipoentidade.Codigo')
            ->leftJoin('tb_entidade', 'tb_tipoentidade.IdEntidade', '=', 'tb_entidade.Codigo')
            ->leftJoin('tb_tipoentidade as medico', 'tb_agendamento.IdMedico', '=', 'medico.Codigo')
            ->select('tb_agendamento.*', 'tb_tipoentidade.Nome as PacienteNome', 'tb_entidade.DataNascimento', 'tb_entidade.Genero', 'medico.Nome as MedicoNome')
            ->where('tb_agendamento.Codigo', $idAgenda)
            ->first();

        if (!$paciente) return abort(404);

        $idade = 'N/D';
        if ($paciente->DataNascimento) {
            $birthDate = new \DateTime($paciente->DataNascimento);
            $idade = (new \DateTime())->diff($birthDate)->y . ' Anos';
        }

        $empresa = DB::table('tb_empresa')->where('ID_EMPRESA', 1)->first();
        if ($empresa && $empresa->IMAGEM) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        $view = 'pdf.guia_transferencia';
        $data = compact('paciente', 'idade', 'empresa');

        if (request('modo') === 'economico') {
            $data['is_economico'] = true;
            $pdf = Pdf::loadView('pdf.layout_economico', ['original_view' => $view, 'data' => $data])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView($view, $data);
        }
        
        return $pdf->stream("guia_transferencia_{$idAgenda}.pdf");
    }

    private function mesExtenso($m) {
        $meses = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
            '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
            '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];
        return $meses[$m] ?? '';
    }
}
