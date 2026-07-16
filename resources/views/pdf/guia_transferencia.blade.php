<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guia de Transferência</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .logo { width: 70px; height: 70px; margin-bottom: 3px; }
        .clinic-name { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .clinic-info { font-size: 8px; color: #555; margin-top: 2px; }
        
        .title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 12px; text-transform: uppercase; border: 2px solid #000; padding: 6px; background-color: #f0f0f0; }
        
        .section { border: 1px solid #000; margin-bottom: 8px; }
        .section-title { font-weight: bold; font-size: 9px; text-transform: uppercase; background-color: #e8e8e8; padding: 3px 6px; border-bottom: 1px solid #000; }
        .section-content { padding: 6px; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 2px 4px; vertical-align: top; font-size: 10px; }
        .data-table .label { font-weight: bold; width: 110px; font-size: 9px; }
        
        .vitals-table { width: 100%; border-collapse: collapse; }
        .vitals-table td { border: 1px solid #ccc; padding: 3px 5px; text-align: center; }
        .vital-label { font-size: 7px; font-weight: bold; text-transform: uppercase; color: #555; }
        .vital-value { font-size: 11px; font-weight: bold; }
        
        .times-table { width: 100%; border-collapse: collapse; }
        .times-table td { width: 50%; text-align: center; padding: 5px; border: 1px solid #ccc; }
        .time-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #555; }
        .time-value { font-size: 13px; font-weight: bold; }
        
        .text-block { font-size: 10px; line-height: 1.4; min-height: 15px; }
        
        .treatment-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .treatment-table th { background-color: #e8e8e8; border: 1px solid #000; padding: 2px 4px; text-align: left; font-size: 8px; text-transform: uppercase; }
        .treatment-table td { border: 1px solid #ccc; padding: 2px 4px; }
        
        .note-box { border: 1px solid #ccc; padding: 6px; font-size: 8px; background-color: #fffacd; margin-top: 5px; }
        .note-title { font-weight: bold; text-transform: uppercase; margin-bottom: 3px; font-size: 8px; }
        
        .stamps-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .stamps-table td { width: 50%; vertical-align: top; padding: 0 10px; }
        .stamp-box { border-top: 1px solid #000; padding-top: 6px; text-align: center; }
        .stamp-label { font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .stamp-area { height: 50px; border: 1px dashed #ccc; margin-top: 4px; text-align: center; }
        .stamp-text { font-size: 7px; color: #999; line-height: 50px; }
        
        .footer { text-align: center; font-size: 8px; color: #555; border-top: 1px solid #ddd; padding-top: 4px; margin-top: 15px; }
    </style>
</head>
<body>
    <!-- CABEÇALHO -->
    <div class="header">
        @if($empresa && $empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo" /><br/>
        @endif
        <div class="clinic-name">{{ $empresa->DESCRICAO ?? 'CLÍNICA' }}</div>
        <div class="clinic-info">
            @if($empresa->RUA){{ $empresa->RUA }}@endif
            @if($empresa->CIDADE), {{ $empresa->CIDADE }}@endif
            @if($empresa->PROVINCIA) - {{ $empresa->PROVINCIA }}@endif
            @if($empresa->TELEFONE) | Tel: {{ $empresa->TELEFONE }}@endif
            @if($empresa->NIF) | NIF: {{ $empresa->NIF }}@endif
        </div>
    </div>

    <!-- TÍTULO -->
    <div class="title">GUIA DE TRANSFERÊNCIA</div>

    <!-- DADOS DO PACIENTE -->
    <div class="section">
        <div class="section-title">Dados do Paciente</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="label">Nome:</td>
                    <td>{{ $paciente->PacienteNome }}</td>
                    <td class="label">Idade:</td>
                    <td>{{ $idade }}</td>
                </tr>
                <tr>
                    <td class="label">Sexo:</td>
                    <td>{{ $paciente->Genero ?? '___' }}</td>
                    <td class="label">Nº Consulta:</td>
                    <td>{{ $paciente->Codigo }}</td>
                </tr>
                <tr>
                    <td class="label">Data:</td>
                    <td>{{ date('d/m/Y', strtotime($paciente->DataAgendamento)) }}</td>
                    <td class="label">Médico:</td>
                    <td>Dr(a). {{ $paciente->MedicoNome ?? '___' }}</td>
                </tr>
                <tr>
                    <td class="label">Unid. Destino:</td>
                    <td colspan="3" style="font-weight: bold; border-bottom: 1px solid #000;">{{ $correspondente ?? '___________________________________________' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- SINAIS VITAIS -->
    <div class="section">
        <div class="section-title">Sinais Vitais</div>
        <div class="section-content">
            @if($triagem)
            <table class="vitals-table">
                <tr>
                    <td style="width:25%">
                        <div class="vital-label">Peso</div>
                        <div class="vital-value">{{ $triagem->Peso ?? '___' }} kg</div>
                    </td>
                    <td style="width:25%">
                        <div class="vital-label">Tensão Arterial</div>
                        <div class="vital-value">{{ $triagem->PressaoArterial ?? '___' }}</div>
                    </td>
                    <td style="width:25%">
                        <div class="vital-label">Freq. Cardíaca</div>
                        <div class="vital-value">{{ $triagem->FrequenciaCardioca ?? '___' }} bpm</div>
                    </td>
                    <td style="width:25%">
                        <div class="vital-label">Freq. Respiratória</div>
                        <div class="vital-value">{{ $triagem->FrequenciaRespiratoria ?? '___' }} rpm</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="vital-label">Temperatura</div>
                        <div class="vital-value">{{ $triagem->Temperatura ?? '___' }} °C</div>
                    </td>
                    <td>
                        <div class="vital-label">SpO2</div>
                        <div class="vital-value">{{ $triagem->SituacaoOxigenio ?? '___' }}%</div>
                    </td>
                    <td>
                        <div class="vital-label">Classif. Risco</div>
                        <div class="vital-value">{{ $triagem->ClassificacaoRisco ?? '___' }}</div>
                    </td>
                    <td>
                        <div class="vital-label">Obs. Triagem</div>
                        <div style="font-size:8px; text-align:left;">{{ $triagem->Obs ?? '___' }}</div>
                    </td>
                </tr>
            </table>
            @else
            <div class="text-block" style="color:#999; font-style:italic;">Nenhum registo de triagem encontrado</div>
            @endif
        </div>
    </div>

    <!-- TEMPOS -->
    <div class="section">
        <div class="section-title">Tempos</div>
        <div class="section-content">
            <table class="times-table">
                <tr>
                    <td>
                        <div class="time-label">Hora de Admissão</div>
                        <div class="time-value">{{ $hora_admissao ?: '___:___' }}</div>
                    </td>
                    <td>
                        <div class="time-label">Hora de Saída</div>
                        <div class="time-value">{{ $hora_saida ?: '___:___' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- HISTÓRIA CLÍNICA -->
    <div class="section">
        <div class="section-title">História Clínica</div>
        <div class="section-content">
            <div class="text-block">{{ $historia_clinica ?: '—' }}</div>
        </div>
    </div>

    <!-- MOTIVO DA TRANSFERÊNCIA -->
    <div class="section">
        <div class="section-title">Motivo da Transferência</div>
        <div class="section-content">
            <div class="text-block">{{ $motivo ?: '—' }}</div>
        </div>
    </div>

    <!-- DIAGNÓSTICO -->
    <div class="section">
        <div class="section-title">Diagnóstico / Hipótese Diagnóstica</div>
        <div class="section-content">
            <div class="text-block">{{ $diagnostico ?: '—' }}</div>
        </div>
    </div>

    <!-- EXAMES E ANÁLISES -->
    <div class="section">
        <div class="section-title">Exames e Análises Realizadas</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="label" style="width:50%; border-bottom: 1px solid #000;">Exames:</td>
                    <td class="label" style="width:50%; border-bottom: 1px solid #000;">Análises:</td>
                </tr>
                <tr>
                    <td style="padding-top:4px;">{{ $exames_realizados ?: '—' }}</td>
                    <td style="padding-top:4px;">{{ $analises ?: '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TRATAMENTO -->
    <div class="section">
        <div class="section-title">Tratamento Efetuado</div>
        <div class="section-content">
            @if($receita && count($receita) > 0)
            <table class="treatment-table">
                <thead>
                    <tr>
                        <th style="width:30%">Medicamento</th>
                        <th style="width:20%">Dosagem</th>
                        <th style="width:25%">Horário / Posologia</th>
                        <th style="width:25%">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receita as $item)
                    <tr>
                        <td>{{ $item->Farmaco }}</td>
                        <td>{{ $item->Dosagem }}</td>
                        <td>{{ $item->Dias }}</td>
                        <td>—</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
            @if($tratamento)
            <div style="margin-top:4px;">
                <b style="font-size:8px; text-transform:uppercase;">Tratamento Adicional:</b><br/>
                <div class="text-block" style="margin-top:2px;">{{ $tratamento }}</div>
            </div>
            @endif
            @if((!$receita || count($receita) === 0) && !$tratamento)
            <div class="text-block" style="color:#999; font-style:italic;">Nenhum tratamento registado</div>
            @endif
        </div>
    </div>

    <!-- OBSERVAÇÕES FINAIS -->
    <div class="section">
        <div class="section-title">Observações Finais</div>
        <div class="section-content">
            @if($obs_final)
            <div class="text-block" style="margin-bottom:5px;">{{ $obs_final }}</div>
            @endif
            <div class="note-box">
                <div class="note-title">Nota:</div>
                Os dados do paciente devem estar claros e completos, incluindo sinais vitais e observações pertinentes. O carimbo do médico deve acompanhar a folha de prescrição e o relatório.
            </div>
        </div>
    </div>

    <!-- CARIMBO E ASSINATURA -->
    <table class="stamps-table">
        <tr>
            <td>
                <div class="stamp-box">
                    <div class="stamp-label">Carimbo do Médico</div>
                    <div class="stamp-area">
                        <span class="stamp-text">[ Carimbo ]</span>
                    </div>
                    <div style="margin-top:5px; border-top:1px solid #000; padding-top:4px;">
                        <span style="font-size:9px; font-weight:bold;">Dr(a). {{ $paciente->MedicoNome ?? '__________________________________' }}</span>
                    </div>
                    <div style="font-size:8px; color:#555;">Médico(a) Assistente</div>
                </div>
            </td>
            <td>
                <div class="stamp-box">
                    <div class="stamp-label">Assinatura e Carimbo</div>
                    <div class="stamp-area">
                        <span class="stamp-text">[ Assinatura + Carimbo ]</span>
                    </div>
                    <div style="margin-top:5px; border-top:1px solid #000; padding-top:4px;">
                        <span style="font-size:9px; font-weight:bold;">{{ $empresa->DESCRICAO ?? '__________________________________' }}</span>
                    </div>
                    <div style="font-size:8px; color:#555;">Instituição de Origem</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- RODAPÉ -->
    <div class="footer">
        {{ $empresa->RUA ?? '' }}{{ $empresa->CIDADE ? ', ' . $empresa->CIDADE : '' }}
        @if($empresa->TELEFONE) | Tel: {{ $empresa->TELEFONE }}@endif
        @if($empresa->NIF) | NIF: {{ $empresa->NIF }}@endif
    </div>
</body>
</html>
