<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Laboratório</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000080; padding-bottom: 10px; }
        .header h1 { color: #000080; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 3px 0; color: #555; }
        .patient-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: #f8f9fa; }
        .patient-info th, .patient-info td { padding: 8px; border: 1px solid #dee2e6; text-align: left; }
        .patient-info th { background-color: #e9ecef; width: 20%; color: #495057; }
        .exam-section { margin-bottom: 30px; page-break-inside: avoid; }
        .exam-title { background-color: #000080; color: white; padding: 8px; margin: 0 0 10px 0; font-size: 14px; font-weight: bold; }
        .exam-table { width: 100%; border-collapse: collapse; }
        .exam-table th, .exam-table td { padding: 6px; border-bottom: 1px dashed #ccc; text-align: left; }
        .exam-table th { font-weight: bold; color: #555; text-transform: uppercase; font-size: 11px; }
        .exam-obs { margin-top: 10px; font-style: italic; color: #666; font-size: 11px; padding: 10px; background-color: #fcfcfc; border-left: 3px solid #000080; }
        .footer { text-align: center; margin-top: 50px; font-size: 10px; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
        .signature-line { width: 250px; border-top: 1px solid #000; margin: 40px auto 10px auto; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Análises Clínicas</h1>
        <p>Departamento de Laboratório - Emute ERP Hospitalar</p>
        <p>Data de Emissão: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table class="patient-info">
        <tr>
            <th>Paciente</th>
            <td>{{ $paciente->PacienteNome }} (Cod: {{ $paciente->Codigo }})</td>
            <th>Data Nasc. / Idade</th>
            <td>{{ $paciente->DataNascimento ?? 'N/D' }}</td>
        </tr>
        <tr>
            <th>Médico Solicitante</th>
            <td>{{ $paciente->MedicoNome ?? 'N/A' }}</td>
            <th>Sexo</th>
            <td>{{ $paciente->Genero ?? 'N/D' }}</td>
        </tr>
        <tr>
            <th>Consulta</th>
            <td>{{ $paciente->DescricaoConsulta ?? 'Geral' }}</td>
            <th>Asseguradora</th>
            <td>{{ $paciente->Asseguradora ?? 'Particular' }}</td>
        </tr>
    </table>

    @foreach($exames as $exame)
        @if($exame->Estado === 'Finalizado' || !empty($exame->Resultado))
            <div class="exam-section">
                <div class="exam-title">{{ $exame->Descricao }}</div>
                
                @if(!empty($exame->Filhos))
                    @php
                        $filhos = array_filter(array_map('trim', explode('|', $exame->Filhos)));
                        $resultados = array_filter(array_map('trim', explode('|', $exame->Resultado ?? '')));
                        $referencias = array_filter(array_map('trim', explode('|', $exame->Referencia ?? '')));
                    @endphp
                    
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Parâmetro</th>
                                <th style="width: 30%;">Resultado</th>
                                <th style="width: 30%;">Referência</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filhos as $idx => $filho)
                                @php
                                    $res = rtrim($resultados[$idx] ?? '', ' ');
                                    $ref = rtrim($referencias[$idx] ?? '', ' ');
                                @endphp
                                <tr>
                                    <td>{{ $filho }}</td>
                                    <td><strong>{{ $res }}</strong></td>
                                    <td>{{ $ref }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>{{ $exame->Resultado ?? 'Sem Resultado Registrado' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @endif

                @if(!empty($exame->Obs))
                    <div class="exam-obs">
                        <strong>Observações / Sugestões:</strong><br>
                        {{ $exame->Obs }}
                    </div>
                @endif
            </div>
        @endif
    @endforeach

    <div style="text-align: center; margin-top: 60px;">
        <div class="signature-line"></div>
        <strong>O Técnico de Laboratório</strong><br>
        <span style="font-size: 11px;">{{ auth()->user()->name ?? 'Emute Lab' }}</span>
    </div>

    <div class="footer">
        Este documento foi gerado digitalmente e é válido sem assinatura física.<br>
        Processado por Zimbuia Emute ERP
    </div>

</body>
</html>
