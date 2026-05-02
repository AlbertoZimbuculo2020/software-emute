<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha Médica</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 100px; height: 100px; float: left; margin-right: 20px; }
        .company-info { float: left; }
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .report-title { text-align: center; font-size: 20px; font-weight: bold; text-transform: uppercase; margin: 20px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .patient-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .patient-table { width: 100%; border-collapse: collapse; }
        .patient-table td { padding: 5px 0; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; background: #eee; padding: 5px 10px; margin-bottom: 10px; border-left: 4px solid #333; }
        .content { padding: 0 10px; white-space: pre-wrap; line-height: 1.6; }
        .footer { margin-top: 50px; text-align: center; }
        .signature-line { width: 300px; border-bottom: 1px solid #000; margin: 0 auto 5px; }
        .clear { clear: both; }
        .triage-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .triage-grid td { border: 1px solid #eee; padding: 8px; text-align: center; }
        .triage-label { font-size: 9px; color: #777; text-transform: uppercase; }
        .triage-value { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        @if($empresa->IMAGEM)
            <img src="{{ $empresa->IMAGEM }}" class="logo">
        @endif
        <div class="company-info">
            <p class="company-name">{{ $empresa->DESCRICAO }}</p>
            <p>NIF: {{ $empresa->NIF }}</p>
            <p>Endereço: {{ $empresa->RUA }}, {{ $empresa->CIDADE }}</p>
            <p>Tel: {{ $empresa->TELEFONE }} | Email: {{ $empresa->EMAIL }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="report-title">Relatório de Consulta Médica</div>

    <div class="patient-box">
        <table class="patient-table">
            <tr>
                <td width="50%"><strong>Paciente:</strong> {{ $paciente->PacienteNome }}</td>
                <td width="50%"><strong>Nº Processo:</strong> {{ $paciente->Codigo }}</td>
            </tr>
            <tr>
                <td><strong>Idade:</strong> {{ $idade }}</td>
                <td><strong>Gênero:</strong> {{ $paciente->Genero }}</td>
            </tr>
            <tr>
                <td><strong>Data:</strong> {{ date('d/m/Y H:i', strtotime($paciente->DataAgendamento)) }}</td>
                <td><strong>Médico:</strong> {{ $paciente->MedicoNome }}</td>
            </tr>
        </table>
    </div>

    @if($triagem)
    <div class="section">
        <div class="section-title">Sinais Vitais (Triagem)</div>
        <table class="triage-grid">
            <tr>
                <td><div class="triage-label">Peso</div><div class="triage-value">{{ $triagem->Peso ?? '--' }} kg</div></td>
                <td><div class="triage-label">Temp</div><div class="triage-value">{{ $triagem->Temperatura ?? '--' }} °C</div></td>
                <td><div class="triage-label">Pressão</div><div class="triage-value">{{ $triagem->PressaoArterial ?? '--' }}</div></td>
                <td><div class="triage-label">F.C</div><div class="triage-value">{{ $triagem->FrequenciaCardiaca ?? '--' }}</div></td>
                <td><div class="triage-label">Sat.O2</div><div class="triage-value">{{ $triagem->SaturacaoOxigenio ?? '--' }}</div></td>
            </tr>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Queixas Principais (QP)</div>
        <div class="content">{{ $paciente->QP ?: 'Nenhuma registrada' }}</div>
    </div>

    <div class="section">
        <div class="section-title">História da Doença Atual (HDA)</div>
        <div class="content">
            @php 
                $hdaParts = explode('|', $paciente->HDA);
                echo trim($hdaParts[0]);
            @endphp
        </div>
    </div>

    @if(isset($hdaParts[1]) && trim($hdaParts[1]) !== '')
    <div class="section">
        <div class="section-title">Diagnóstico (CID-10)</div>
        <div class="content">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach(explode("\n", trim($hdaParts[1])) as $cid)
                    @if(trim($cid))
                        <li>{{ trim($cid) }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Exame Objectivo (OBJ)</div>
        <div class="content">{{ $paciente->OBJ ?: 'Sem observações' }}</div>
    </div>

    @if($paciente->COMPLEMENTARES)
    <div class="section">
        <div class="section-title">Hipótese de Diagnóstico / Complementares</div>
        <div class="content">{{ $paciente->COMPLEMENTARES }}</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Recomendações e Observações</div>
        <div class="content">{{ $paciente->RECOMENDACOES ?: 'Seguir orientações verbais.' }}</div>
    </div>

    <div class="footer">
        <p>Assinado digitalmente por</p>
        <div class="signature-line"></div>
        <p><strong>Dr(a). {{ $paciente->MedicoNome }}</strong></p>
        <p style="font-size: 10px; color: #888;">Impresso em {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
