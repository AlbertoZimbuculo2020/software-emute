<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório de Medicina Ocupacional</title>
    <style>
        body { font-family: 'Courier', sans-serif; font-size: 10px; color: #333; line-height: 1.2; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .logo { max-height: 60px; margin-bottom: 5px; }
        .title { font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 10px 0; background: #eee; padding: 5px; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ccc; margin-bottom: 5px; font-size: 11px; background: #f9f9f9; padding: 2px 5px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .grid td { padding: 3px; border: 1px solid #eee; vertical-align: top; }
        .label { font-weight: bold; color: #666; text-transform: uppercase; font-size: 8px; display: block; }
        .value { font-weight: bold; color: #000; font-size: 10px; }
        .footer { margin-top: 30px; text-align: center; }
        .signature { margin-top: 50px; border-top: 1px solid #000; width: 250px; padding-top: 5px; font-weight: bold; }
        .checkbox-container { margin-right: 10px; }
        .checkbox { border: 1px solid #000; width: 10px; height: 10px; vertical-align: middle; margin-right: 3px; }
        .checked { background: #000; }
        .table-ocupacional { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table-ocupacional th, .table-ocupacional td { border: 1px solid #ccc; padding: 4px; font-size: 9px; }
        .table-ocupacional th { background: #eee; font-weight: bold; text-align: left; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        @if($empresa && $empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo">
        @endif
        <div style="font-weight: bold; font-size: 12px;">{{ $empresa->DESCRICAO ?? 'CLÍNICA MÉDICA' }}</div>
        <div>{{ $empresa->PROVINCIA ?? '' }}, {{ $empresa->CIDADE ?? '' }}, {{ $empresa->RUA ?? '' }} | Tel: {{ $empresa->TELEFONE ?? '' }}</div>
        <div class="title">RELATÓRIO DE EXAME MÉDICO OCUPACIONAL (ASO)</div>
    </div>

    <!-- Dados do Paciente -->
    <div class="section">
        <div class="section-title">I. Identificação do Trabalhador</div>
        <table class="grid">
            <tr>
                <td colspan="2"><span class="label">Nome Completo:</span> <span class="value">{{ $paciente->PacienteNome }}</span></td>
                <td><span class="label">Código:</span> <span class="value">{{ $paciente->Codigo }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Data Nascimento:</span> <span class="value">{{ date('d/m/Y', strtotime($paciente->DataNascimento)) }}</span></td>
                <td><span class="label">Idade:</span> <span class="value">{{ $idade }}</span></td>
                <td><span class="label">Sexo:</span> <span class="value">{{ $paciente->Genero }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Empresa:</span> <span class="value">{{ $dadosOcupacionais->Empresa ?? '---' }}</span></td>
                <td><span class="label">Função:</span> <span class="value">{{ $dadosOcupacionais->Funcao ?? '---' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Tipo de Exame e Riscos -->
    <div class="section">
        <table class="grid">
            <tr>
                <td width="50%">
                    <div class="section-title">II. Natureza do Exame</div>
                    <div style="padding: 5px;">
                        <span class="value">{{ $dadosOcupacionais->TipoExame ?? '---' }}</span>
                    </div>
                </td>
                <td width="50%">
                    <div class="section-title">III. Riscos Ocupacionais</div>
                    <div style="padding: 5px;">
                        <span class="value">{{ $dadosOcupacionais->FactoresRiscos ?? 'Nenhum' }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Histórico Clínico -->
    <div class="section">
        <div class="section-title">IV. Antecedentes Pessoais e Familiares</div>
        <table class="grid">
            <tr>
                <td><span class="label">Infecto-Contagiosas:</span> {{ $dadosOcupacionais->DoencaInfectoContagiosa ?? 'Nada a declarar' }}</td>
                <td><span class="label">Doenças Crónicas:</span> {{ $dadosOcupacionais->DoencaCronica ?? 'Nada a declarar' }}</td>
            </tr>
            <tr>
                <td><span class="label">Alergias:</span> {{ $dadosOcupacionais->Alergia ?? 'Nenhuma' }}</td>
                <td><span class="label">Cirurgias:</span> {{ $dadosOcupacionais->Cirugias ?? 'Nenhuma' }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Histórico Familiar:</span> {{ $dadosOcupacionais->DoencasFamiliares ?? 'Sem antecedentes relevantes' }}</td>
            </tr>
        </table>
    </div>

    <!-- Histórico Ocupacional -->
    <div class="section">
        <div class="section-title">V. Histórico Ocupacional Anterior</div>
        <table class="table-ocupacional">
            <thead>
                <tr>
                    <th>Função Anterior</th>
                    <th>Tempo de Serviço</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historico as $h)
                    <tr>
                        <td>{{ $h->Funcao }}</td>
                        <td>{{ $h->Tempo }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #999;">Nenhum registro anterior informado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <p style="font-size: 9px; margin-top: 5px;"><b>Iniciou a vida laboral aos:</b> {{ $dadosOcupacionais->IdadeInicioTrabalho ?? '---' }} anos.</p>
    </div>

    <!-- Hábitos e Estilo de Vida -->
    <div class="section">
        <div class="section-title">VI. Hábitos e Estilo de Vida</div>
        <table class="grid">
            <tr>
                <td><span class="label">Medicação:</span> {{ $dadosOcupacionais->MedicacaoUso ?? 'Nenhuma' }}</td>
                <td><span class="label">Alimentação:</span> {{ $dadosOcupacionais->HabitosAlimentares ?? 'Normal' }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label">Vícios / Hábitos:</span>
                    @php 
                        $tabaco = json_decode($dadosOcupacionais->Tabaco ?? '{}', true);
                        $alcool = json_decode($dadosOcupacionais->Alcool ?? '{}', true);
                        $drogas = json_decode($dadosOcupacionais->Drogas ?? '{}', true);
                    @endphp
                    Tabaco: {{ ($tabaco['checked'] ?? false) ? "Sim (".$tabaco['qtd']."/dia, ".$tabaco['tempo'].")" : 'Não' }} | 
                    Álcool: {{ ($alcool['checked'] ?? false) ? "Sim (".$alcool['qtd']."/sem, ".$alcool['tempo'].")" : 'Não' }} | 
                    Drogas: {{ ($drogas['checked'] ?? false) ? "Sim (".$drogas['qual'].", ".$drogas['tempo'].")" : 'Não' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Exame Físico (Resumo) -->
    <div class="section">
        <div class="section-title">VII. Exame Físico por Sistemas</div>
        @php $sistemas = json_decode($dadosOcupacionais->ExameFisicoGeral ?? '[]', true); @endphp
        <table class="grid">
            @foreach(['pele' => 'Pele/Faneras', 'respiratorio' => 'Respiratório', 'cardiovascular' => 'CardioVascular', 'digestivo' => 'Digestivo', 'genitoUrinario' => 'Génito Urinário', 'endocrino' => 'Endócrino', 'nervoso' => 'Nervoso', 'osteoarticular' => 'Osteoarticular'] as $k => $l)
            <tr>
                <td width="30%"><span class="label">{{ $l }}</span></td>
                <td width="20%"><span class="value">{{ $sistemas[$k]['estado'] ?? 'Normal' }}</span></td>
                <td><span class="value italic">{{ implode(', ', $sistemas[$k]['alteracoes'] ?? []) }} {{ $sistemas[$k]['obs'] ?? '' }}</span></td>
            </tr>
            @endforeach
        </table>
    </div>

    <!-- Conclusão -->
    <div class="section" style="border: 2px solid #000; padding: 10px;">
        <div class="section-title" style="background: #000; color: #fff;">VIII. Conclusão e Aptidão Médica</div>
        <div style="text-align: center; padding: 15px 0;">
            <span style="font-size: 16px; font-weight: bold; border-bottom: 2px solid #000; padding: 0 20px;">
                {{ strtoupper($dadosOcupacionais->Resultado ?? 'APTO') }}
            </span>
        </div>
        <div style="font-size: 9px;">
            <b>Recomendações:</b> {{ ($dadosOcupacionais->Recomendacoes ?? '') ?: 'Nenhuma específica.' }}<br>
            <b>Encaminhamentos:</b> {{ ($dadosOcupacionais->EncaminhaMedicoEspecialista ?? '') ?: 'Nenhum.' }}
        </div>
    </div>

    <div class="footer">
        <div style="text-align: right; margin-bottom: 20px;">
            {{ $empresa->CIDADE ?? 'Angola' }}, {{ date('d') }} de {{ $mesExtenso }} de {{ date('Y') }}
        </div>
        
        <div class="signature">
            MÉDICO EXAMINADOR<br>
            <span style="font-size: 8px;">{{ $paciente->MedicoNome }}</span>
        </div>
        
        <div style="margin-top: 20px; font-size: 8px; color: #999;">
            Documento gerado eletronicamente pelo Sistema EMUTE ERP
        </div>
    </div>

</body>
</html>
