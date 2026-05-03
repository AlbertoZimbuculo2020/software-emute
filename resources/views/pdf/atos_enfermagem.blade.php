<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registo de Visitas e Atos da Enfermagem</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { width: 100%; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }
        .logo { width: 60px; height: auto; float: left; margin-right: 20px; }
        .company-info { float: left; width: 80%; }
        .company-info h1 { margin: 0; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .company-info p { margin: 2px 0; font-size: 10px; }
        .clear { clear: both; }
        .report-title { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 20px 0; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid th { background: #e0e0e0; border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .grid td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #777; padding-top: 10px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="header">
    @if($empresa->IMAGEM)
        <img src="{{ $empresa->IMAGEM }}" class="logo">
    @endif
    <div class="company-info">
        <h1>{{ $empresa->NOME_EMPRESA ?? $empresa->EMPRESA ?? $empresa->Nome ?? 'LUDAL, DESENVOLVIMENTO E PROGRESSO - CLÍNICA TUAMAMICO' }}</h1>
        <p>{{ $empresa->ENDERECO ?? $empresa->CIDADE ?? '' }}, {{ $empresa->RUA ?? '' }}</p>
        <p>Tel: {{ $empresa->TELEFONE ?? '' }} / {{ $empresa->TELEFONE2 ?? '' }}</p>
        <p>Email: {{ $empresa->EMAIL ?? '' }}</p>
        <p>NIF: {{ $empresa->NIF ?? '' }}</p>
    </div>
    <div class="clear"></div>
</div>

<div class="report-title">
    RELATÓRIO DE REGISTO DE VISITAS E ATOS DA ENFERMAGEM
</div>

<table class="grid">
    <thead>
        <tr>
            <th width="15%">AGENDA</th>
            <th width="20%">DATA_HORA</th>
            <th width="30%">DESCRICAO</th>
            <th width="20%">PACIENTE</th>
            <th width="15%">ENFERMEIRO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($atosEnfermagem as $ae)
        <tr>
            <td>{{ $agendamento->Codigo }}</td>
            <td>{{ \Carbon\Carbon::parse($ae->DataAto ?? $ae->CREATED_AT)->format('d/m/Y H:i:s') }}</td>
            <td>{{ $ae->Descricao }}</td>
            <td>{{ $agendamento->PacienteNome }}</td>
            <td>{{ $ae->Infermeiro ?? $ae->Enfermeiro ?? '---' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Gerado em: {{ date('d/m/Y H:i:s') }} | Emissão: {{ auth()->user()->name }}
</div>

</body>
</html>
