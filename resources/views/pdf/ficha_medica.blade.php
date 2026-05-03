@if(!isset($only_content) && !isset($only_styles) && !isset($is_economico))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Geral da Consulta</title>
@endif
@if(!isset($only_content))
    @if(!isset($is_economico))
    <style>
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
            margin-bottom: 20px;
            @if(isset($is_economico))
            margin-top: 5px;
            margin-bottom: 10px;
            @endif
        }
        .report-title { 
            font-size: 14px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        
        @if(isset($is_economico))
        .report-title { font-size: 11px; }
        .section-separator { margin-top: 5px; margin-bottom: 2px; }
        .section-title { font-size: 9px; margin-bottom: 5px; }
        .consulta-info { font-size: 9px; margin-bottom: 5px; }
        @endif

        /* CONSULTA INFO */
        .consulta-info { font-weight: bold; font-size: 11px; line-height: 1.4; margin-bottom: 15px; }

        /* SECTION TITLES */
        .section-separator { border-top: 1px solid #000; margin-top: 10px; margin-bottom: 5px; }
        .section-title { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }

        /* PATIENT TABLE */
        .patient-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
        .patient-table td { padding: 3px 0; }

        /* TRIAGE TABLE */
        .triage-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .triage-table td { border: 1px solid #000; padding: 4px; }
        .triage-label { font-weight: bold; background-color: #f9f9f9; }
        
        .grid-triage { display: block; width: 100%; }
        .grid-triage table { width: 100%; border-collapse: collapse; }

        /* CLINICAL BOXES */
        .clinical-box-title { font-size: 10px; font-weight: bold; margin-bottom: 2px; margin-top: 15px; }
        .clinical-box { border: 1px solid #000; padding: 8px; min-height: 50px; font-size: 10px; margin-bottom: 5px; white-space: pre-wrap; }
        
        @if(isset($is_economico))
        .clinical-box-title { font-size: 8px; margin-top: 5px; }
        .clinical-box { padding: 4px; min-height: 10px; font-size: 8px; margin-bottom: 2px; }
        @endif

        /* RECEITA TABLE */
        .receita-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; text-align: center; }
        .receita-table th { border: 1px solid #000; padding: 6px; background-color: #e6e6e6; font-weight: bold; }
        .receita-table td { border: 1px solid #000; padding: 6px; }

        /* SIGNATURE */
        .signature-section { text-align: center; margin-top: 40px; font-size: 10px; font-weight: bold; }
        .signature-line { width: 250px; border-bottom: 1px solid #000; margin: 30px auto 5px; }

@if(!isset($is_economico))
    </style>
@endif
@endif

@if(!isset($only_styles))
    @if(!isset($only_content) && !isset($is_economico))
    </head>
    <body>
    @endif

@if(!isset($is_economico) || (isset($column_index) && $column_index == 1))
    @if(!isset($is_economico))
    <!-- REPEATING HEADER -->
    <header>
        <table class="header-table">
            <tr>
                <td width="60%">
                    @if($empresa && $empresa->IMAGEM)
                        <img src="{{ $empresa->IMAGEM }}" class="logo">
                    @endif
                    <div class="clinic-name">{{ $empresa->DESCRICAO }}</div>
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
        {{ $empresa->ENDERECO ?? '' }} | Tel: {{ $empresa->TELEFONE ?? '' }} | NIF: {{ $empresa->NIF ?? '' }}
    </footer>
    @endif

    @if(isset($is_economico))
    <!-- MINI HEADER FOR ECONOMIC MODE -->
    <div style="border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 5px;">
        <table width="100%">
            <tr>
                <td width="40px">@if($empresa->IMAGEM)<img src="{{ $empresa->IMAGEM }}" style="width: 35px;">@endif</td>
                <td>
                    <div style="font-size: 8px; font-weight: bold;">{{ $empresa->DESCRICAO }}</div>
                    <div style="font-size: 7px;">NIF: {{ $empresa->NIF }} | Tel: {{ $empresa->TELEFONE }}</div>
                </td>
                <td align="right" style="font-size: 7px;">Relatório de Consulta</td>
            </tr>
        </table>
    </div>
    @endif
@endif

@if(!isset($is_economico))
    <script type="text/php">
        if (isset($pdf)) {
            $x = 520;
            $y = 15;
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->get_font("helvetica", "bold");
            $size = 9;
            $color = array(0,0,0);
            $word_space = 0.0;
            $char_space = 0.0;
            $angle = 0.0;
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
@endif

    <!-- PAGE 1: DADOS GERAIS E TRIAGEM -->
@if(!isset($is_economico) || (isset($column_index) && $column_index == 1))
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
        @if(isset($is_economico))
            <tr>
                <td class="triage-label" width="25%">Peso:</td>
                <td width="25%">{{ $triagem->Peso ?? '0' }}</td>
                <td class="triage-label" width="25%">Temp:</td>
                <td width="25%">{{ $triagem->Temperatura ?? '0' }}º</td>
            </tr>
            <tr>
                <td class="triage-label">Puls:</td>
                <td>{{ $triagem->FrequenciaCardiaca ?? '0' }}</td>
                <td class="triage-label">Resp:</td>
                <td>{{ $triagem->FrequenciaRespiratoria ?? '0' }}</td>
            </tr>
            <tr>
                <td class="triage-label">SatO2:</td>
                <td>{{ $triagem->SaturacaoOxigenio ?? '0' }}%</td>
                <td class="triage-label">P.A:</td>
                <td>{{ $triagem->PressaoArterial ?? '0' }}</td>
            </tr>
            <tr>
                <td class="triage-label">Obs:</td>
                <td colspan="3">{{ $triagem->Observacoes ?? 'Sem' }}</td>
            </tr>
        @else
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
        @endif
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
@endif

    <!-- EXAMES -->
    @php
        $is_split = isset($is_economico) && !($is_duplicate ?? true);
        $midpoint = ceil($exames->count() / 2);
        
        if($is_split) {
            $exames_list = ($column_index == 1) ? $exames->slice(0, $midpoint) : $exames->slice($midpoint);
        } else {
            $exames_list = $exames;
        }
    @endphp

    @if($exames_list->count() > 0)
        <div class="section-separator"></div>
        <div class="section-title">EXAMES E RESULTADOS SOLICITADOS @if($is_split && $column_index == 2) (CONT.) @endif</div>
        @if(isset($is_economico))
            <table width="100%" style="font-size: 7px; border-collapse: collapse;">
                @foreach($exames_list->chunk(2) as $chunk)
                <tr>
                    @foreach($chunk as $ex)
                    <td width="50%" style="padding: 1px 0;">• <strong>{{ $ex->Descricao }}:</strong> {{ $ex->Resultado ?? 'P' }}</td>
                    @endforeach
                    @if($chunk->count() < 2) <td></td> @endif
                </tr>
                @endforeach
            </table>
        @else
            <ul style="font-size: 10px; margin-top: 0; margin-bottom: 20px;">
                @foreach($exames_list as $ex)
                    <li>
                        <strong>{{ $ex->Descricao ?? 'Exame' }}:</strong> 
                        {{ $ex->Resultado ?? 'Pendente' }}
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

@if(!isset($is_economico) || (isset($column_index) && $column_index == 2))
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
        <div class="signature-line"></div>
        Dr(a). {{ $paciente->MedicoNome ?? '' }}<br>
        <span style="font-weight: normal; font-size: 8px;">Médico(a) Assistente</span>
    </div>
@endif

@if(!isset($only_content) && !isset($is_economico))
</body>
</html>
@endif
@endif
