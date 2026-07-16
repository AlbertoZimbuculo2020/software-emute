@if(!isset($only_content) && !isset($only_styles) && !isset($is_economico))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha Médica</title>
@endif
@if(!isset($only_content))
    @if(!isset($is_economico))
    <style>
        body {
            font-family: 'Helvetica', sans-serif; 
            font-size: 11px; 
            color: #000; 
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 80px; height: auto; margin-right: 15px; }
        .clinic-name { font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .page-num { text-align: right; font-size: 9px; font-weight: bold; vertical-align: top; }

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
        
        .grid-triage { width: 100%; }
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
    <!-- STANDARD HEADER -->
    <div style="border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                @if($empresa && $empresa->IMAGEM)
                <td width="80" style="vertical-align: middle; padding-bottom: 5px;">
                    <img src="{{ $empresa->IMAGEM }}" style="max-height: 55px; max-width: 75px; display: block;">
                </td>
                @endif
                <td style="vertical-align: middle; padding-left: 10px; padding-bottom: 5px;">
                    <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; line-height: 1.2;">{{ $empresa->DESCRICAO }}</div>
                    <div style="font-size: 8px; color: #555; margin-top: 2px;">
                        NIF: {{ $empresa->NIF ?? '' }} | Tel: {{ $empresa->TELEFONE ?? '' }} | Endereço: {{ $empresa->PROVINCIA ?? '' }}, {{ $empresa->CIDADE ?? '' }}, {{ $empresa->RUA ?? '' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 5px;">
        <span style="font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">FICHA MÉDICA / DADOS CLÍNICOS</span>
    </div>
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
    <div class="consulta-info" style="margin-bottom: 20px; background-color: #f8fafc; border: 1px solid #000; padding: 8px;">
        <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
            <tr>
                <td width="25%"><strong>CONSULTA Nº:</strong></td>
                <td width="25%">{{ $paciente->Codigo }}</td>
                <td width="25%"><strong>DATA DA CONSULTA:</strong></td>
                <td width="25%">{{ date('d/m/Y', strtotime($paciente->DataAgendamento)) }}</td>
            </tr>
            <tr>
                <td><strong>ESPECIALIDADE:</strong></td>
                <td>Clínica Geral</td>
                <td><strong>ESTADO:</strong></td>
                <td><span style="color: #0066cc; font-weight: bold; text-transform: uppercase;">{{ $paciente->Situacao ?? 'Atendido' }}</span></td>
            </tr>
        </table>
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

    @if($medicina)
        <div class="section-separator"></div>
        <div class="section-title">MEDICINA OCUPACIONAL</div>
        
        <table class="patient-table">
            <tr>
                <td width="50%"><strong>Posto de Trabalho:</strong> {{ $medicina->Funcao ?? '—' }}</td>
                <td width="50%"><strong>Empresa:</strong> {{ $medicina->Empresa ?? '—' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Factores de Risco:</strong> {{ $medicina->FactoresRiscos ?? '—' }}</td>
            </tr>
        </table>

        <div class="clinical-box-title">Exame Clínico Ocupacional / Observações</div>
        <div class="clinical-box">{{ $medicina->Resultado ?? '—' }}</div>

        <div style="margin-top: 10px; font-weight: bold; border: 1px solid #000; padding: 10px; background-color: #f0f0f0;">
            ESTADO DE APTIDÃO: <span style="text-transform: uppercase; font-size: 14px;">{{ $medicina->Resultado ?: 'NÃO DEFINIDO' }}</span>
        </div>
    @endif
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
@endif
