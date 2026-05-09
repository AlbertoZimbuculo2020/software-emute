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
                DB::raw("(SELECT COALESCE(MIN(DataInternamento), CURRENT_DATE) FROM tb_prescricao WHERE IdAgenda = tb_agendamento.Codigo AND Tipo = 'Internamento') as DataInternamento")
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
        if ($empresa && isset($empresa->IMAGEM)) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return view('pdf.processo_clinico', [
            'agendamento' => $agendamento,
            'prescricoes' => $prescricoes,
            'atosMedicos' => $atosMedicos,
            'atosEnfermagem' => $atosEnfermagem,
            'sinaisVitais' => $sinaisVitais,
            'alta' => $alta,
            'empresa' => $empresa
        ]);
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

        return view('pdf.atos_enfermagem', [
            'agendamento' => $agendamento,
            'atosEnfermagem' => $atosEnfermagem,
            'empresa' => $empresa
        ]);
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

        return view('pdf.triagem_report', [
            'agendamento' => $agendamento,
            'sinaisVitais' => $sinaisVitais,
            'empresa' => $empresa
        ]);
    }

    // ─── Cumprimento (Enfermagem) ─────────────────────────────────────────────

    public function getDepositos()
    {
        $depositos = DB::table('tb_deposito')
            ->where('ESTADO', 'Ativo')
            ->select('CODIGO', 'DEPOSITO')
            ->get();
        return response()->json($depositos);
    }

    public function getArtigos(Request $request)
    {
        $depositoId = $request->input('deposito', '');
        $search     = $request->input('search', '');

        $query = DB::table('tb_artigo as a')
            ->join('tb_compra_fornecedor as cf', 'a.CODIGO', '=', 'cf.ID_PRODUTO')
            ->select(
                'a.CODIGO',
                'a.DESCRICAO as PRODUTO',
                DB::raw('SUM(cf.QTD_ATUAL) as Stock'),
                'a.PV as PRECO'
            )
            ->where('cf.ESTADO', 'Ativo')
            ->where('a.ESTADO', 'Ativo')
            ->groupBy('a.CODIGO', 'a.DESCRICAO', 'a.PV')
            ->having('Stock', '>', 0);

        if ($depositoId) {
            $query->where('cf.ID_DEPOSITO', $depositoId);
        }
        if ($search) {
            $query->where('a.DESCRICAO', 'like', "%{$search}%");
        }

        return response()->json($query->get());
    }

    public function gravarCumprimento(Request $request)
    {
        $cumprimentos = $request->input('cumprimentos', []);

        foreach ($cumprimentos as $item) {
            DB::table('tb_prescricao')
                ->where('Id', $item['id'])
                ->update([
                    'Cumprimento'  => $item['c0'] ? 'True' : 'False',
                    'Cumprimento1' => $item['c1'] ? 'True' : 'False',
                    'Cumprimento2' => $item['c2'] ? 'True' : 'False',
                    'Cumprimento3' => $item['c3'] ? 'True' : 'False',
                    'Observacao'   => $item['Observacao'] ?? '',
                    'Infermeiro'   => Auth::user()->name,
                ]);
        }

        return response()->json(['success' => true, 'message' => 'Cumprimento gravado com sucesso!']);
    }

    public function imprimirCumprimento($id)
    {
        $agendamento = DB::table('tb_agendamento')
            ->join('tb_tipoentidade as p', 'tb_agendamento.IdPaciente', '=', 'p.Codigo')
            ->select('tb_agendamento.*', 'p.Nome as PacienteNome')
            ->where('tb_agendamento.Codigo', $id)
            ->first();

        $prescricoes = DB::table('tb_prescricao')
            ->where('IdAgenda', $id)
            ->where('Estado', 'Ativo')
            ->get();

        $empresa = DB::table('tb_empresa')->first();
        if ($empresa && isset($empresa->IMAGEM)) {
            $empresa->IMAGEM = 'data:image/jpeg;base64,' . base64_encode($empresa->IMAGEM);
        }

        return view('pdf.cumprimento_prescricoes', [
            'agendamento' => $agendamento,
            'prescricoes' => $prescricoes,
            'empresa' => $empresa
        ]);
    }

    public function finalizarSaidaFarmaco(Request $request)
    {
        $itens      = $request->input('itens', []);
        $depositoId = $request->input('deposito', '');
        $paciente   = $request->input('paciente', '');
        $motivo     = $request->input('motivo', 'Saída de Fármacos - Internamento');

        if (empty($itens)) {
            return response()->json(['success' => false, 'message' => 'Nenhum fármaco adicionado!'], 422);
        }

        $total = collect($itens)->sum(fn($i) => $i['PRECO'] * $i['quantidade']);
        $now   = now();

        $seq = DB::table('tb_sequencial_fatura')->where('TIPO', 'SD')->first();
        $num = ($seq->SEQUENCIAL ?? 0) + 1;
        $codigo = 'SD' . str_pad($num, 6, '0', STR_PAD_LEFT);
        DB::table('tb_sequencial_fatura')->where('TIPO', 'SD')->update(['SEQUENCIAL' => $num]);

        $idFatura = DB::table('tb_fatura')->insertGetId([
            'CODIGO'         => $codigo,
            'TIPO'           => 'SD',
            'ID_UTILIZADOR'  => Auth::id(),
            'ID_MOEDA'       => 'MD000000000',
            'ID_TURNO'       => '1',
            'LUGAR_ENTREGA'  => 'Saída Interna - Enfermagem',
            'DATA_'          => $now->toDateString(),
            'DATAVENCIMENTO' => $now->toDateString(),
            'DATA_ENTREGA'   => $now->toDateString(),
            'VALOR'          => $total,
            'DEBITO'         => $total,
            'CREDITO'        => 0,
            'IVA'            => 0,
            'DESCONTO'       => 0,
            'RETENCAO'       => 0,
            'MOEDA_EXTRANGERA' => 0,
            'CAMBIO'         => 1,
            'PAGAMENTO'      => '',
            'HASH_COD'       => '',
            'CHAVE'          => '',
            'STATU'          => 'Ativo',
            'HORA'           => $now->toTimeString(),
            'PAGO'           => 0,
            'TROCO'          => 0,
            'OBSERVACAO'     => $motivo,
            'IMPRIMIR'       => 'ORIGINAL',
            'NOME_DOCUMENTO' => 'SAIDA DE PRODUTO',
            'USUARIO'        => Auth::user()->name,
            'NIF'            => '999999999',
            'CLIENTE'        => 'Paciente: ' . $paciente,
            'ESTADO'         => 'Ativo',
            'DESCRICAO'      => 'Saida',
            'CREATED_AT'     => $now,
        ]);

        foreach ($itens as $item) {
            DB::table('tb_venda')->insert([
                'CODIGO'        => $item['CODIGO'],
                'PRODUTO'       => $item['PRODUTO'],
                'QUANTIDADE'    => $item['quantidade'],
                'ID_FATURA'     => $idFatura,
                'ESTADO'        => 'Ativo',
                'ID_UTILIZADOR' => Auth::id(),
                'DATA_'         => $now->toDateString(),
                'VALOR'         => $item['PRECO'],
                'MONTANTE'      => $item['PRECO'] * $item['quantidade'],
                'IVA'           => 0,
                'DESCONTO'      => 0,
                'RETENCAO'      => 0,
                'CREATED_AT'    => $now,
            ]);

            // Baixa o stock FIFO por lote
            $qtdRestante = $item['quantidade'];
            $lotes = DB::table('tb_compra_fornecedor')
                ->where('ID_PRODUTO', $item['CODIGO'])
                ->where('ID_DEPOSITO', $depositoId)
                ->where('ESTADO', 'Ativo')
                ->where('QTD_ATUAL', '>', 0)
                ->orderBy('DATA_COMPRA', 'asc')
                ->get();

            foreach ($lotes as $lote) {
                if ($qtdRestante <= 0) break;
                $reduzir = min($qtdRestante, $lote->QTD_ATUAL);
                DB::table('tb_compra_fornecedor')
                    ->where('ID_COMPRA_FORNECEDOR', $lote->ID_COMPRA_FORNECEDOR)
                    ->decrement('QTD_ATUAL', $reduzir);
                $qtdRestante -= $reduzir;
            }
        }

        return response()->json(['success' => true, 'message' => 'Saída registada!', 'codigo' => $codigo]);
    }
}
