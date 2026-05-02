<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Requisição de Exames - {{ $paciente->PacienteNome }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; border-bottom: 2px solid #0054e3; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #0054e3; margin: 0; font-size: 22px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; color: #666; }
        
        .section { margin-bottom: 20px; }
        .section-title { background: #0054e3; color: white; padding: 5px 10px; font-weight: bold; text-transform: uppercase; font-size: 11px; margin-bottom: 10px; }
        
        .patient-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .patient-info td { padding: 5px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; color: #555; width: 120px; }
        
        .exams-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .exams-table th { background: #f0f0f0; padding: 8px; text-align: left; border-bottom: 1px solid #ccc; font-size: 10px; text-transform: uppercase; }
        .exams-table td { padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        
        .footer { margin-top: 50px; text-align: center; }
        .signature { margin-top: 40px; border-top: 1px solid #000; width: 250px; display: inline-block; padding-top: 5px; }
        
        .watermark { position: fixed; top: 40%; left: 25%; font-size: 60px; color: rgba(0, 0, 0, 0.05); transform: rotate(-45deg); z-index: -1; }
    </style>
</head>
<body>
    <div class="watermark">REQUISIÇÃO MÉDICA</div>

    <div class="header">
        <h1>{{ $empresa->Nome ?? 'CENTRO MÉDICO EMUTE' }}</h1>
        <p>{{ $empresa->Endereco ?? 'Endereço da Unidade Hospitalar' }}</p>
        <p>Tel: {{ $empresa->Telefone ?? '---' }} | Email: {{ $empresa->Email ?? '---' }}</p>
    </div>

    <div class="section">
        <div class="section-title">Identificação do Paciente</div>
        <table class="patient-info">
            <tr>
                <td class="label">Paciente:</td>
                <td>{{ $paciente->PacienteNome }}</td>
                <td class="label">Processo:</td>
                <td>{{ $paciente->Codigo }}</td>
            </tr>
            <tr>
                <td class="label">Gênero:</td>
                <td>{{ $paciente->Genero }}</td>
                <td class="label">Data:</td>
                <td>{{ date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Exames Solicitados</div>
        <table class="exams-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição do Exame</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($exames as $exame)
                <tr>
                    <td style="width: 80px;">{{ $exame->CodExame }}</td>
                    <td><strong>{{ $exame->Descricao }}</strong></td>
                    <td style="color: #666; font-style: italic;">{{ $exame->Obs ?? '---' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($agendamento->QP || $agendamento->HDA)
    <div class="section">
        <div class="section-title">Indicação Clínica</div>
        <div style="padding: 10px; background: #fafafa; border: 1px solid #eee; line-height: 1.5;">
            @if($agendamento->QP) <strong>Queixas:</strong> {{ $agendamento->QP }}<br> @endif
            @if($agendamento->HDA) <strong>Resumo Clínico:</strong> {{ explode('|', $agendamento->HDA)[0] }} @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Solicitado por: <strong>{{ auth()->user()->name }}</strong></p>
        <div class="signature">
            Assinatura e Carimbo
        </div>
        <p style="font-size: 9px; margin-top: 20px; color: #999;">Impresso em: {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
