@if(!isset($is_economico))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receita Médica</title>
@endif
@if(!isset($is_economico))
    <style>
@else
    <style>
@endif
        body { font-family: 'Helvetica', sans-serif; font-size: 13px; color: #333; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 100px; height: 100px; float: left; margin-right: 20px; }
        .company-info { float: left; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .recipe-title { text-align: center; font-size: 22px; font-weight: bold; text-transform: uppercase; margin: 30px 0; border-bottom: 3px double #000; padding-bottom: 10px; }
        .patient-info { margin-bottom: 30px; font-size: 14px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { background: #f0f0f0; padding: 10px; border: 1px solid #ccc; text-align: left; text-transform: uppercase; font-size: 11px; }
        .items-table td { padding: 12px 10px; border: 1px solid #eee; }
        .farmaco { font-weight: bold; text-transform: uppercase; font-size: 14px; }
        .dosagem { font-style: italic; color: #555; }
        .footer { margin-top: 100px; text-align: center; }
        .signature-line { width: 300px; border-bottom: 1px solid #000; margin: 0 auto 5px; }
        .clear { clear: both; }
        .date { text-align: right; margin-bottom: 20px; font-weight: bold; }
        
        @if(isset($is_economico))
        body { font-size: 10px; }
        .recipe-title { font-size: 16px; margin: 10px 0; padding-bottom: 5px; }
        .header { padding-bottom: 5px; margin-bottom: 10px; }
        .logo { width: 50px; height: 50px; }
        .company-name { font-size: 12px; }
        .patient-info { margin-bottom: 15px; font-size: 11px; }
        .items-table { margin-bottom: 20px; }
        .items-table th { padding: 5px; font-size: 9px; }
        .items-table td { padding: 5px; }
        @endif
    </style>
@if(!isset($is_economico))
</head>
<body>
@endif
    <div class="header">
        @if($empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo">
        @endif
        <div class="company-info">
            <p class="company-name">{{ $empresa->DESCRICAO }}</p>
            <p>NIF: {{ $empresa->NIF }}</p>
            <p>Tel: {{ $empresa->TELEFONE }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="recipe-title">Receita Médica</div>

    <div class="date">{{ $dataExtenso }}</div>

    <div class="patient-info">
        <p><strong>Paciente:</strong> {{ $paciente->PacienteNome }}</p>
        <p><strong>Processo:</strong> {{ $paciente->Codigo }}</p>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="40%">Fármaco</th>
                <th width="45%">Posologia / Dosagem</th>
                <th width="15%" style="text-align: center;">Duração</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            <tr>
                <td class="farmaco">{{ $item->Farmaco }}</td>
                <td class="dosagem">{{ $item->Dosagem }}</td>
                <td style="text-align: center;">{{ $item->Dias }} dias</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section" style="margin-top: 40px;">
        <p style="font-weight: bold; text-decoration: underline;">Observações:</p>
        <p>Em caso de reações adversas, interrompa o uso e contacte o seu médico.</p>
    </div>

    <div class="footer">
        <div class="signature-line"></div>
        <p><strong>Dr(a). {{ $paciente->MedicoNome }}</strong></p>
        <p style="font-size: 11px;">Médico(a) Responsável</p>
    </div>
@if(!isset($is_economico))
</body>
</html>
@endif
