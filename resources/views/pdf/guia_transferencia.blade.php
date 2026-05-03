<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guia de Transferência</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 13px; color: #000; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .logo { max-width: 100px; max-height: 100px; margin-bottom: 5px; }
        .clinic-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; }
        .info-box { border: 1px solid #000; padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #fafafa; }
        .info-row { margin-bottom: 10px; }
        .info-row strong { display: inline-block; width: 150px; }
        .content-box { border: 1px solid #000; padding: 15px; min-height: 200px; margin-bottom: 30px; border-radius: 5px; }
        .content-title { font-weight: bold; text-decoration: underline; margin-bottom: 15px; }
        .signature { text-align: center; margin-top: 60px; }
        .signature-line { border-bottom: 1px solid #000; width: 250px; margin: 0 auto 10px; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        @if($empresa && $empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo"><br>
        @endif
        <div class="clinic-name">{{ $empresa->Nome ?? 'LUDAL, DESENVOLVIMENTO E PROGRESSO' }}</div>
    </div>

    <div class="title">GUIA DE TRANSFERÊNCIA DE DOENTE</div>

    <div class="info-box">
        <div class="info-row"><strong>Nome do Paciente:</strong> {{ $paciente->PacienteNome }}</div>
        <div class="info-row"><strong>Idade:</strong> {{ $idade }}</div>
        <div class="info-row"><strong>Sexo:</strong> {{ $paciente->Genero }}</div>
        <div class="info-row"><strong>Nº da Consulta:</strong> {{ $paciente->Codigo }}</div>
        <div class="info-row"><strong>Data:</strong> {{ date('d/m/Y', strtotime($paciente->DataAgendamento)) }}</div>
    </div>

    <div class="content-box">
        <div class="content-title">Motivo da Transferência / Resumo Clínico:</div>
        <br><br><br><br><br><br><br><br>
    </div>
    
    <div class="content-box" style="min-height: 100px;">
        <div class="content-title">Unidade de Destino (Sugestão):</div>
        <br><br>
    </div>

    <div class="signature">
        <div class="signature-line"></div>
        <strong>Dr(a). {{ $paciente->MedicoNome ?? '__________________________________' }}</strong><br>
        Médico(a) Assistente
    </div>

    <div class="footer">
        {{ $empresa->Endereco ?? 'CACUACO, ECOCAMPO, 4 DE FEVEREIRO' }} | 
        Tel: {{ $empresa->Telefone ?? '924358803' }} | 
        NIF: {{ $empresa->NIF ?? '5401150954' }}
    </div>
</body>
</html>
