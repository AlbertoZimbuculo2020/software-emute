<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Controlo de Sinais Vitais</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: sans-serif; font-size: 9px; color: #333; line-height: 1.4; }
        .header { width: 100%; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }
        .logo { width: 60px; height: auto; float: left; margin-right: 20px; }
        .company-info { float: left; width: 80%; }
        .company-info h1 { margin: 0; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .company-info p { margin: 1px 0; font-size: 9px; }
        .clear { clear: both; }
        .report-title { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 15px 0; }
        .patient-info { margin-bottom: 15px; font-size: 11px; font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid th { background: #e0e0e0; border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        .grid td { border: 1px solid #000; padding: 5px; text-align: center; font-size: 8px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #777; padding-top: 5px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="header">
    @if($empresa->IMAGEM)
        <img src="{{ $empresa->IMAGEM }}" class="logo">
    @endif
    <div class="company-info">
        <h1>{{ $empresa->NOME_EMPRESA ?? $empresa->EMPRESA ?? $empresa->Nome ?? 'CENTRO MÉDICO' }}</h1>
        <p>{{ $empresa->ENDERECO ?? $empresa->CIDADE ?? '' }}, {{ $empresa->RUA ?? '' }}</p>
        <p>Tel: {{ $empresa->TELEFONE ?? '' }} / {{ $empresa->TELEFONE2 ?? '' }}</p>
        <p>Email: {{ $empresa->EMAIL ?? '' }} | NIF: {{ $empresa->NIF ?? '' }}</p>
    </div>
    <div class="clear"></div>
</div>

<div class="report-title">
    CONTROLO DE SINAIS VITAIS
</div>

<div class="patient-info">
    NOME COMPLETO: {{ $agendamento->PacienteNome }}
</div>

<table class="grid">
    <thead>
        <tr>
            <th>Data e Hora</th>
            <th>Tensão Art. BD</th>
            <th>Tensão Art. BE</th>
            <th>Pulsação BD</th>
            <th>Pulsação BE</th>
            <th>Respiração</th>
            <th>Temp.</th>
            <th>Peso</th>
            <th>Enfermeiro</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sinaisVitais as $sv)
        <tr>
            <td>{{ \Carbon\Carbon::parse($sv->CREATED_AT)->format('d/m/Y H:i') }}</td>
            <td>{{ $sv->PressaoArterial }}</td>
            <td>{{ $sv->PressaoArterialBE ?? '0' }}</td>
            <td>{{ $sv->FrequenciaCardioca }}</td>
            <td>{{ $sv->PulsoBE ?? '0' }}</td>
            <td>{{ $sv->FrequenciaRespiratoria }}</td>
            <td>{{ $sv->Temperatura }}°C</td>
            <td>{{ $sv->Peso }}kg</td>
            <td>{{ $sv->Imferemiro ?? '---' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Gerado em: {{ date('d/m/Y H:i:s') }} | Página 1 de 1
</div>

</body>
</html>
