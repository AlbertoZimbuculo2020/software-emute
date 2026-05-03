<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Requisição de Exames</title>
    <style>
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 11px; 
            color: #000; 
            margin: 0; 
            padding: 20px;
        }
        
        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-cell {
            width: 80px;
            text-align: left;
        }
        .logo-placeholder {
            width: 60px;
            height: 60px;
            background-color: #eee;
            border: 1px solid #ccc;
            text-align: center;
            line-height: 60px;
            font-size: 10px;
            color: #999;
        }
        .clinic-info {
            padding-left: 10px;
        }
        .clinic-name {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .clinic-details {
            font-size: 10px;
            margin: 2px 0;
            font-weight: bold;
        }

        /* TITLE */
        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 0;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* PATIENT INFO */
        .patient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .patient-table td {
            padding: 3px 0;
            width: 50%;
        }

        /* EXAM CATEGORY */
        .category-title {
            background-color: #d9d9d9;
            font-weight: bold;
            padding: 4px;
            font-size: 12px;
            margin-top: 10px;
            border: 1px solid #999;
        }

        /* EXAM ITEM FULL WIDTH */
        .exam-full {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        
        .exam-header {
            background-color: #8c8c8c;
            color: #000;
            font-weight: bold;
            padding: 4px;
            font-size: 11px;
            border: 1px solid #000;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .result-table th {
            text-align: left;
            padding: 3px;
            border-bottom: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }
        .result-table td {
            padding: 3px;
            border-bottom: 1px solid #ccc;
            font-size: 10px;
        }

        /* EXAM HALF WIDTH (GRID) */
        .grid-container {
            width: 100%;
            margin-bottom: 10px;
        }
        .grid-item {
            width: 48%;
            float: left;
            margin-bottom: 15px;
        }
        .grid-item:nth-child(even) {
            float: right;
        }
        .clear {
            clear: both;
        }

        /* SIGNATURE */
        .signature-section {
            text-align: center;
            margin-top: 60px;
            font-size: 12px;
            font-style: italic;
        }
        .signature-line {
            width: 300px;
            border-top: 1px solid #000;
            margin: 30px auto 5px auto;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($empresa && $empresa->IMAGEM)
                    <img src="{{ $empresa->IMAGEM }}" style="width: 80px; height: auto;">
                @else
                    <div class="logo-placeholder">LOGO</div>
                @endif
            </td>
            <td class="clinic-info">
                <p class="clinic-name">{{ $empresa->DESCRICAO ?? $empresa->Nome ?? 'CLÍNICA TUAMAMICO' }}</p>
                <p class="clinic-details">Contribuinte nº {{ $empresa->NIF ?? '' }}</p>
                <p class="clinic-details">Contacto: {{ $empresa->TELEFONE ?? $empresa->Telefone ?? '' }} / {{ $empresa->TELEFONE2 ?? '' }}</p>
                <p class="clinic-details">{{ $empresa->ENDERECO ?? $empresa->Endereco ?? ($empresa->PROVINCIA ?? '') . ', ' . ($empresa->CIDADE ?? '') . ', ' . ($empresa->RUA ?? '') }}</p>
            </td>
        </tr>
    </table>

    <!-- TITLE -->
    <div class="report-title">
        REQUISIÇÃO DE EXAMES / SERVIÇOS
    </div>

    <!-- PATIENT INFO -->
    <table class="patient-table">
        <tr>
            <td><strong>Nome:</strong> {{ $paciente->PacienteNome }}</td>
            <td><strong>Idade:</strong> {{ $idade ?? (isset($paciente->DataNascimento) ? \Carbon\Carbon::parse($paciente->DataNascimento)->age . ' Anos' : 'N/D') }}</td>
        </tr>
        <tr>
            <td><strong>Sexo:</strong> {{ $paciente->Genero ?? 'MASCULINO' }}</td>
            <td><strong>Nº Processo:</strong> {{ $paciente->CodigoPaciente ?? $paciente->Codigo ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Data do Exame:</strong> {{ date('d/m/Y H:i:s') }}</td>
            <td><strong>Empresa:</strong> {{ $paciente->Asseguradora ?? 'PARTICULAR' }}</td>
        </tr>
    </table>

    @php
        // Separate exams into complex (full width) and simple (half width)
        $complexExams = [];
        $simpleExams = [];

        foreach($exames as $exame) {
            $filhosCount = !empty($exame->Filhos) ? count(explode('|', $exame->Filhos)) : 0;
            if ($filhosCount > 3 || strpos(strtolower($exame->Descricao), 'hemograma') !== false) {
                $complexExams[] = $exame;
            } else {
                $simpleExams[] = $exame;
            }
        }
    @endphp

    <!-- COMPLEX EXAMS (FULL WIDTH) -->
    @foreach($complexExams as $exame)
        <div class="category-title">
            Categoria do Exame: {{ $exame->Categoria ?? 'LABORATÓRIO GERAL' }}
        </div>
        <div class="exam-header">
            Exame: {{ $exame->Descricao }}
        </div>
        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 40%">Dados</th>
                    <th style="width: 30%">Resultados</th>
                    <th style="width: 30%">Referencias</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($exame->Filhos))
                    @php
                        $filhos = array_filter(array_map('trim', explode('|', $exame->Filhos)));
                        $resultados = !empty($exame->Resultado) ? array_filter(array_map('trim', explode('|', $exame->Resultado))) : [];
                        $referencias = !empty($exame->Referencia) ? array_filter(array_map('trim', explode('|', $exame->Referencia))) : [];
                    @endphp
                    @foreach($filhos as $idx => $filho)
                        <tr>
                            <td>{{ $filho }}</td>
                            <td>{{ $resultados[$idx] ?? '' }}</td>
                            <td>{{ $referencias[$idx] ?? '' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $exame->Descricao }}</td>
                        <td>{{ $exame->Resultado ?? '' }}</td>
                        <td>{{ $exame->Referencia ?? '' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    <!-- SIMPLE EXAMS (HALF WIDTH - SIDE BY SIDE) -->
    @if(count($simpleExams) > 0)
        <div class="category-title" style="margin-bottom: 10px;">
            Categoria do Exame:
        </div>
        
        <div class="grid-container">
            @foreach($simpleExams as $idx => $exame)
                <div class="grid-item">
                    <div class="exam-header" style="font-size: 10px; padding: 3px;">
                        Exame: {{ $exame->Descricao }}
                    </div>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th style="width: 33%; font-size: 9px;">Dados</th>
                                <th style="width: 33%; font-size: 9px;">Resultados</th>
                                <th style="width: 33%; font-size: 9px;">Referencias</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($exame->Filhos))
                                @php
                                    $filhos = array_filter(array_map('trim', explode('|', $exame->Filhos)));
                                    $resultados = !empty($exame->Resultado) ? array_filter(array_map('trim', explode('|', $exame->Resultado))) : [];
                                    $referencias = !empty($exame->Referencia) ? array_filter(array_map('trim', explode('|', $exame->Referencia))) : [];
                                @endphp
                                @foreach($filhos as $fidx => $filho)
                                    <tr>
                                        <td>{{ $filho }}</td>
                                        <td>{{ $resultados[$fidx] ?? '' }}</td>
                                        <td>{{ $referencias[$fidx] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>{{ $exame->Descricao }}</td>
                                    <td>{{ $exame->Resultado ?? '' }}</td>
                                    <td>{{ $exame->Referencia ?? '' }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @if(($idx + 1) % 2 == 0)
                    <div class="clear"></div>
                @endif
            @endforeach
            <div class="clear"></div>
        </div>
    @endif

    <!-- SIGNATURE -->
    <div class="signature-section">
        Assinatura do Técnico do Laboratório
        <div class="signature-line"></div>
        Dr(a).: {{ auth()->user()->name ?? '' }}
    </div>

</body>
</html>
