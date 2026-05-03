<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Processo Clínico de Internamento</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000080; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #000080; font-size: 18px; text-transform: uppercase; }
        .info-section { margin-bottom: 15px; }
        .section-title { background: #f0f0f0; padding: 5px; font-weight: bold; text-transform: uppercase; border-left: 4px solid #000080; margin-bottom: 10px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grid th, .grid td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .grid th { background: #f9f9f9; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; border-top: 1px solid #ddd; padding-top: 5px; }
        .patient-card { border: 1px solid #000080; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .patient-card table { width: 100%; }
        .patient-card td { padding: 2px 5px; }
        .label { font-weight: bold; color: #555; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $empresa->Nome ?? 'CENTRO MÉDICO' }}</h1>
    <p>{{ $empresa->Endereco ?? '' }} | Tel: {{ $empresa->Telefone ?? '' }}</p>
    <div style="font-size: 14px; font-weight: bold; margin-top: 10px;">PROCESSO CLÍNICO DE INTERNAMENTO</div>
</div>

<div class="patient-card">
    <table>
        <tr>
            <td width="15%" class="label">Paciente:</td>
            <td width="50%">{{ $agendamento->PacienteNome }}</td>
            <td width="15%" class="label">Código:</td>
            <td>{{ $agendamento->Codigo }}</td>
        </tr>
        <tr>
            <td class="label">Idade/Sexo:</td>
            <td>
                @if($agendamento->Nascimento)
                    {{ \Carbon\Carbon::parse($agendamento->Nascimento)->age }} anos
                @endif
                / {{ $agendamento->Sexo }}
            </td>
            <td class="label">Entrada:</td>
            <td>{{ \Carbon\Carbon::parse($agendamento->DataAgendamento)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Médico Resp:</td>
            <td>{{ $agendamento->MedicoNome }}</td>
            <td class="label">Quarto/Cama:</td>
            <td>---</td>
        </tr>
    </table>
</div>

@if(count($sinaisVitais) > 0)
<div class="info-section">
    <div class="section-title">Controlo de Sinais Vitais</div>
    <table class="grid">
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Temp (°C)</th>
                <th>Peso (kg)</th>
                <th>Tensão Art.</th>
                <th>Freq. Card.</th>
                <th>Saturação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sinaisVitais as $sv)
            <tr>
                <td>{{ \Carbon\Carbon::parse($sv->CREATED_AT)->format('d/m/Y H:i') }}</td>
                <td>{{ $sv->Temperatura }}</td>
                <td>{{ $sv->Peso }}</td>
                <td>{{ $sv->PressaoArterial }}</td>
                <td>{{ $sv->FrequenciaCardioca }}</td>
                <td>{{ $sv->SituacaoOxigenio }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($prescricoes) > 0)
<div class="info-section">
    <div class="section-title">Prescrições e Plano Terapêutico</div>
    <table class="grid">
        <thead>
            <tr>
                <th>Data</th>
                <th>Médico</th>
                <th>Prescrição / Orientação</th>
                <th>Execução (M/T/N)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prescricoes as $pr)
            <tr>
                <td>{{ \Carbon\Carbon::parse($pr->DataInternamento ?? $pr->CREATED_AT)->format('d/m/Y') }}</td>
                <td>{{ $pr->Medico }}</td>
                <td>
                    <strong>{{ $pr->Descricao }}</strong><br>
                    <small>{{ $pr->Observacao }}</small>
                </td>
                <td style="text-align: center;">
                    [{{ $pr->Cumprimento1 == 'True' ? 'X' : ' ' }}] 
                    [{{ $pr->Cumprimento2 == 'True' ? 'X' : ' ' }}] 
                    [{{ $pr->Cumprimento3 == 'True' ? 'X' : ' ' }}]
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(count($atosMedicos) > 0 || count($atosEnfermagem) > 0)
<div class="info-section">
    <div class="section-title">Diário Clínico (Evolução)</div>
    <table class="grid">
        <thead>
            <tr>
                <th width="20%">Data/Hora</th>
                <th width="20%">Profissional</th>
                <th>Descrição do Ato / Observação Clínica</th>
            </tr>
        </thead>
        <tbody>
            @foreach($atosMedicos as $am)
            <tr>
                <td>{{ \Carbon\Carbon::parse($am->DataAto ?? $am->CREATED_AT)->format('d/m/Y H:i') }}</td>
                <td>Dr. {{ $am->Medico }}</td>
                <td>{{ $am->Descricao }}</td>
            </tr>
            @endforeach
            @foreach($atosEnfermagem as $ae)
            <tr>
                <td>{{ \Carbon\Carbon::parse($ae->DataAto ?? $ae->CREATED_AT)->format('d/m/Y H:i') }}</td>
                <td>Enf. {{ $ae->Infermeiro ?? $ae->Enfermeiro ?? '---' }}</td>
                <td>{{ $ae->Descricao }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($alta)
<div class="info-section">
    <div class="section-title">Resumo de Alta</div>
    <div style="border: 1px solid #ddd; padding: 10px; background: #fff;">
        <p><span class="label">Data da Alta:</span> {{ \Carbon\Carbon::parse($alta->DataAlta)->format('d/m/Y H:i') }}</p>
        <p><span class="label">Procedimento:</span> {{ $alta->Operado ?? 'N/A' }}</p>
        <p><span class="label">Complicações:</span> {{ $alta->Complicacoes ?? 'Nenhuma' }}</p>
        <p><span class="label">Recomendações:</span> {{ $alta->Repouso ?? 'Repouso absoluto' }}</p>
        <p><span class="label">Observações Finais:</span> {{ $alta->Obs ?? 'Sem observações' }}</p>
        <br>
        <div style="text-align: right; margin-top: 20px;">
            _________________________________<br>
            {{ $alta->Medico }}<br>
            <small>Assinatura e Carimbo</small>
        </div>
    </div>
</div>
@endif

<div class="footer">
    Emitido em: {{ date('d/m/Y H:i') }} | Gerado por: {{ auth()->user()->name }}
</div>

</body>
</html>
