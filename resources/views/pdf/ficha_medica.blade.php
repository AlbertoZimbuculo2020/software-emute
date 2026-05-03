@if(!isset($is_economico))
<!DOCTYPE html>
<html>
<head>
@endif
    <meta charset="utf-8">
    <title>Relatório Geral da Consulta</title>
    <style>
    @if(!isset($is_economico))
        @page {
            margin: 120px 40px 60px 40px; /* Top margin for fixed header */
        }
    @endif
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }
        
        /* FIXED HEADER FOR EVERY PAGE */
        header {
            position: fixed;
            top: -100px;
            left: 0px;
            right: 0px;
            height: 90px;
        }

        /* FIXED FOOTER FOR EVERY PAGE */
        footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 9px;
            color: #0066cc;
            font-weight: bold;
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 80px; height: auto; float: left; margin-right: 15px; }
        .clinic-name { font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 0; padding-top: 15px; }
        .page-num { text-align: right; font-size: 9px; font-weight: bold; vertical-align: top; padding-top: 15px; }

        .report-title-container {
            margin-top: 30px;
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        .report-title { 
            font-size: 16px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }

        /* CONSULTA INFO */
        .consulta-info { font-weight: bold; font-size: 11px; line-height: 1.4; margin-bottom: 15px; }

        /* SECTION TITLES */
        .section-separator { border-top: 1px solid #000; margin-top: 10px; margin-bottom: 5px; }
        .section-title { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }

        /* PATIENT TABLE */
        .patient-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
        .patient-table td { padding: 3px 0; }

        /* TRIAGE TABLE */
        .triage-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
        .triage-table td { border: 1px solid #000; padding: 6px; }
        .triage-label { font-weight: bold; width: 40%; }
        .triage-value { width: 60%; }

        /* CLINICAL BOXES */
        .clinical-box-title { font-size: 10px; font-weight: bold; margin-bottom: 2px; margin-top: 15px; }
        .clinical-box { border: 1px solid #000; padding: 8px; min-height: 50px; font-size: 10px; margin-bottom: 5px; white-space: pre-wrap; }

        /* RECEITA TABLE */
        .receita-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; text-align: center; }
        .receita-table th { border: 1px solid #000; padding: 6px; background-color: #e6e6e6; font-weight: bold; }
        .receita-table td { border: 1px solid #000; padding: 6px; }

        /* SIGNATURE */
        .signature-section { text-align: center; margin-top: 40px; font-size: 10px; font-weight: bold; }
        .signature-line { width: 250px; border-bottom: 1px solid #000; margin: 30px auto 5px; }

        .page-break { page-break-after: always; }
    </style>
@if(!isset($is_economico))
</head>
<body>
@endif

    <!-- REPEATING HEADER -->
    <header>
        <table class="header-table">
            <tr>
                <td width="60%">
                    @if($empresa && $empresa->IMAGEM)
                        <img src="{{ $empresa->IMAGEM }}" class="logo">
                    @endif
                    <div class="clinic-name">{{ $empresa->Nome ?? 'LUDAL, DESENVOLVIMENTO E PROGRESSO - CLÍNICA TUAMAMICO' }}</div>
                </td>
                <td width="40%" class="page-num">
                    página <span class="pagenum"></span>
                </td>
            </tr>
        </table>
        
        <div class="report-title-container">
            <span class="report-title">RELATÓRIO GERAL DA CONSULTA</span>
        </div>
    </header>

    <!-- REPEATING FOOTER -->
    <footer>
        {{ $empresa->Endereco ?? 'CACUACO, ECOCAMPO, 4 DE FEVEREIRO' }}<br>
        {{ $empresa->Telefone ?? '924358803/' }}<br>
        Contribuinte nº {{ $empresa->NIF ?? '5401150954' }}
    </footer>

    <!-- ADD PAGE NUMBERS SCRIPT -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "{PAGE_NUM} / {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("Helvetica", "bold");
            $width = $fontMetrics->getTextWidth($text, $font, $size) / 2;
            $x = $pdf->get_width() - 55;
            $y = 48;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>

    <!-- PAGE 1: DADOS GERAIS E TRIAGEM -->
    <div class="consulta-info">
        CONSULTA Nº {{ $paciente->Codigo }}<br>
        CONSULTA: Clínica Geral<br>
        DATA DA CONSULTA: {{ date('d/m/Y', strtotime($paciente->DataAgendamento)) }}<br>
        ESTADO DA CONSULTA: {{ $paciente->Situacao ?? 'Atendido' }}
    </div>

    <div class="section-separator"></div>
    <div class="section-title">DADOS PESSOAIS DO PACIENTE</div>

    <table class="patient-table">
        <tr>
            <td width="50%"><strong>Nome:</strong> &nbsp;&nbsp;&nbsp;{{ $paciente->PacienteNome }}</td>
            <td width="50%"><strong>Idade:</strong> &nbsp;&nbsp;&nbsp;{{ $idade }}</td>
        </tr>
        <tr>
            <td><strong>Sexo:</strong> &nbsp;&nbsp;&nbsp;{{ $paciente->Genero }}</td>
            <td><strong>Nº Processo:</strong> &nbsp;&nbsp;&nbsp;{{ $paciente->CodigoPaciente ?? $paciente->IdPaciente }}</td>
        </tr>
        <tr>
            <td><strong>Data da Consulta:</strong> &nbsp;&nbsp;&nbsp;{{ date('Y-m-d', strtotime($paciente->DataAgendamento)) }}</td>
            <td><strong>Seguradora:</strong> &nbsp;&nbsp;&nbsp;{{ $paciente->Seguradora ?? 'PARTICULAR' }}</td>
        </tr>
    </table>

    <div class="section-separator"></div>
    <div class="section-title">DADOS DA TRIAGEM</div>

    <table class="triage-table">
        <tr>
            <td class="triage-label">Peso:</td>
            <td class="triage-value">{{ $triagem->Peso ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Temperatura Corporal:</td>
            <td class="triage-value">{{ $triagem->Temperatura ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Frequência cardíaca (pulso):</td>
            <td class="triage-value">{{ $triagem->FrequenciaCardiaca ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Frequência Respiratória:</td>
            <td class="triage-value">{{ $triagem->FrequenciaRespiratoria ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Saturação de oxigênio (oximetria):</td>
            <td class="triage-value">{{ $triagem->SaturacaoOxigenio ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Pressão arterial:</td>
            <td class="triage-value">{{ $triagem->PressaoArterial ?? '0' }}</td>
        </tr>
        <tr>
            <td class="triage-label">Observação:</td>
            <td class="triage-value">{{ $triagem->Observacoes ?? 'Sem' }}</td>
        </tr>
    </table>

    <div class="section-separator"></div>
    <div class="section-title">DADOS CLINICOS</div>

    <div class="clinical-box-title">Queixas Principais</div>
    <div class="clinical-box">{{ $paciente->QP ?? '—' }}</div>

    <div class="clinical-box-title">Histórico da Doença Atual</div>
    @php
        $hdaRaw = $paciente->HDA ?? '';
        $hdaDisplay = str_contains($hdaRaw, '|') ? trim(explode('|', $hdaRaw)[0]) : $hdaRaw;
    @endphp
    <div class="clinical-box">{{ $hdaDisplay ?: '—' }}</div>

    <div class="clinical-box-title">Hipótese de Diagnóstico (CID-10)</div>
    @php
        $cidsDisplay = '';
        if (str_contains($hdaRaw, '|')) {
            $cidPart = trim(explode('|', $hdaRaw)[1] ?? '');
            $cidsDisplay = $cidPart;
        }
        if (!$cidsDisplay) $cidsDisplay = $paciente->COMPLEMENTARES ?? '—';
    @endphp
    <div class="clinical-box">{{ $cidsDisplay }}</div>

    <div class="clinical-box-title">Exames Objectivos</div>
    <div class="clinical-box">{{ $paciente->OBJ ?? '—' }}</div>

    <div class="clinical-box-title">Observações / Recomendações</div>
    <div class="clinical-box">{{ $paciente->RECOMENDACOES ?? '—' }}</div>

    @if($exames && count($exames) > 0)
        <div class="section-separator"></div>
        <div class="section-title">EXAMES E RESULTADOS SOLICITADOS</div>
        <ul style="font-size: 10px; margin-top: 0; margin-bottom: 20px;">
            @foreach($exames as $ex)
                <li>
                    <strong>{{ $ex->Descricao ?? 'Exame' }}:</strong> 
                    {{ $ex->Resultado ?? 'Pendente' }}
                </li>
            @endforeach
        </ul>
    @else
        <div class="section-separator"></div>
        <div class="section-title">EXAMES E RESULTADOS SOLICITADOS</div>
        <div style="font-size: 10px; text-align: center; margin-bottom: 20px;">Nenhum exame solicitado.</div>
    @endif

    <div class="section-separator"></div>
    <div class="section-title">RECEITA MEDICA</div>

    @if($receita && count($receita) > 0)
        <table class="receita-table">
            <thead>
                <tr>
                    <th width="50%">Farmaco</th>
                    <th width="25%">Dosagem</th>
                    <th width="25%">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receita as $item)
                    <tr>
                        <td>{{ $item->Descricao ?? $item->Farmaco ?? '' }}</td>
                        <td>{{ $item->Dosagem ?? '' }}</td>
                        <td>{{ $item->Quantidade ?? $item->Qtd ?? $item->Dias ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="font-size: 10px; text-align: center; padding: 10px;">Nenhuma receita médica registada.</div>
    @endif

    <div class="signature-section">
        Médico(a) Assistente:
        <div class="signature-line"></div>
        Dr.(a): {{ $paciente->MedicoNome ?? '' }}
    </div>

@if(!isset($is_economico))
</body>
</html>
@endif
