@if(!isset($only_content) && !isset($only_styles) && !isset($is_economico))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Justificativo Médico</title>
@endif
@if(!isset($only_content))
    @if(!isset($is_economico))
    <style>
    @endif
        body { font-family: 'Helvetica', sans-serif; font-size: 14px; color: #000; margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .logo { max-width: 120px; max-height: 120px; margin-bottom: 10px; }
        .clinic-name { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 40px; text-transform: uppercase; }
        .content { line-height: 2; text-align: justify; margin-bottom: 50px; }
        .date { text-align: right; margin-bottom: 60px; }
        .signature { text-align: center; margin-top: 80px; }
        .signature-line { border-bottom: 1px solid #000; width: 300px; margin: 0 auto 10px; }
        .footer { text-align: center; font-size: 10px; color: #555; margin-top: 30px; }
@if(!isset($is_economico))
    </style>
@endif
@endif
@if(!isset($only_styles))
@if(!isset($only_content) && !isset($is_economico))
</head>
<body>
@endif
    <div class="header">
        @if($empresa && $empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo">
        @endif
        <div class="clinic-name">{{ $empresa->Nome ?? 'LUDAL, DESENVOLVIMENTO E PROGRESSO' }}</div>
        <div>{{ $empresa->Descricao ?? 'CLÍNICA TUAMAMICO' }}</div>
    </div>

    <div class="title">DECLARAÇÃO / JUSTIFICATIVO MÉDICO</div>

    <div class="content">
        Para os devidos efeitos, declara-se que o(a) Sr(a). <strong>{{ $paciente->PacienteNome }}</strong>, 
        @if($familiar) acompanhante de <strong>{{ $familiar }}</strong>, @endif
        foi observado(a) em consulta médica nesta unidade de saúde no dia 
        <strong>{{ date('d/m/Y', strtotime($paciente->DataAgendamento)) }}</strong>, 
        sob o número de registo/processo <strong>{{ $paciente->Codigo }}</strong>.
        @if($data_internado) <br>Esteve internado(a) desde o dia <strong>{{ date('d/m/Y', strtotime($data_internado)) }}</strong>. @endif
        <br><br>
        Encontra-se incapacitado(a) para as suas atividades habituais no período de 
        <strong>{{ $data_inicio ? date('d/m/Y', strtotime($data_inicio)) : '_____/_____/_______' }}</strong> a 
        <strong>{{ $data_fim ? date('d/m/Y', strtotime($data_fim)) : '_____/_____/_______' }}</strong>, por motivos de doença.
    </div>

    <div class="date">
        Luanda, {{ date('d') }} de {{ \Carbon\Carbon::now()->locale('pt')->translatedFormat('F') }} de {{ date('Y') }}
    </div>

    <div class="signature">
        <div class="signature-line"></div>
        <strong>Dr(a). {{ $paciente->MedicoNome ?? '__________________________________' }}</strong><br>
        Médico(a) Assistente
    </div>

    <div class="footer">
        {{ $empresa->RUA ?? 'CACUACO, ECOCAMPO, 4 DE FEVEREIRO' }}{{ $empresa->CIDADE ? ', ' . $empresa->CIDADE : '' }} | 
        Tel: {{ $empresa->TELEFONE ?? '924358803' }} | 
        NIF: {{ $empresa->NIF ?? '5401150954' }}
    </div>
</body>
</html>
@endif
