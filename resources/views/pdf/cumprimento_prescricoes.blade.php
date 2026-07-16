<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cumprimento de Prescrições Médicas</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }
        .header-table td { vertical-align: top; padding: 2px; }
        .logo-cell { width: 80px; }
        .report-title { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 20px 0; }
        .patient-info { background: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .patient-info b { text-transform: uppercase; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid th { background: #e0e0e0; border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .grid td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 9px; }
        .check { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .footer { text-align: center; font-size: 8px; color: #777; padding-top: 10px; border-top: 1px solid #eee; margin-top: 20px; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        @if($empresa->IMAGEM)
        <td class="logo-cell">
            <img src="{{ $empresa->IMAGEM }}" style="width: 60px; height: auto;">
        </td>
        @endif
        <td>
            <h1 style="margin: 0; font-size: 13px; font-weight: bold; text-transform: uppercase;">{{ $empresa->DESCRICAO ?? $empresa->NOME_EMPRESA ?? 'CLÍNICA TUAMAMICO' }}</h1>
            <p style="margin: 2px 0; font-size: 10px;">{{ $empresa->PROVINCIA ?? '' }}, {{ $empresa->CIDADE ?? '' }}, {{ $empresa->RUA ?? '' }}</p>
            <p style="margin: 2px 0; font-size: 10px;">Tel: {{ $empresa->TELEFONE ?? '' }} / {{ $empresa->TELEFONE2 ?? '' }}</p>
            <p style="margin: 2px 0; font-size: 10px;">NIF: {{ $empresa->NIF ?? '' }}</p>
        </td>
    </tr>
</table>

<div class="report-title">
    CUMPRIMENTO DE PRESCRIÇÕES MÉDICAS (ENFERMAGEM)
</div>

<div class="patient-info">
    <table width="100%">
        <tr>
            <td><b>Paciente:</b> {{ $agendamento->PacienteNome }}</td>
            <td align="right"><b>Agenda:</b> {{ $agendamento->Codigo }}</td>
        </tr>
        <tr>
            <td><b>Data Internamento:</b> {{ \Carbon\Carbon::parse($agendamento->DataAgendamento ?? $agendamento->CREATED_AT)->format('d/m/Y') }}</td>
            <td align="right"><b>Quarto/Cama:</b> {{ $agendamento->Quarto ?? '---' }} / {{ $agendamento->Cama ?? '---' }}</td>
        </tr>
    </table>
</div>

<table class="grid">
    <thead>
        <tr>
            <th width="15%">Data/Hora</th>
            <th width="40%">Prescrição Médica</th>
            <th width="15%" style="text-align: center;">M / T / N / ...</th>
            <th width="20%">Notas de Enfermagem</th>
            <th width="10%">Resp.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($prescricoes as $p)
        <tr>
            <td>{{ \Carbon\Carbon::parse($p->DataInternamento ?? $agendamento->DataAgendamento ?? $p->CREATED_AT ?? now())->format('d/m/Y H:i') }}</td>
            <td><b>{{ $p->Descricao }}</b></td>
            <td style="text-align: center;">
                <span class="check">{{ $p->Cumprimento == 'True' ? '☑' : '☐' }}</span>
                <span class="check">{{ $p->Cumprimento1 == 'True' ? '☑' : '☐' }}</span>
                <span class="check">{{ $p->Cumprimento2 == 'True' ? '☑' : '☐' }}</span>
                <span class="check">{{ $p->Cumprimento3 == 'True' ? '☑' : '☐' }}</span>
            </td>
            <td>{{ $p->Observacao ?? '' }}</td>
            <td>{{ $p->Infermeiro ?? '---' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Gerado em: {{ date('d/m/Y H:i:s') }} | Emissão: {{ auth()->user()->name }}
</div>

</body>
</html>
