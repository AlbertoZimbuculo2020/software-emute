<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guia de Transferência</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            color: #000; 
            margin: 0; 
            padding: 20px; 
        }
        
        .header-container { 
            width: 100%; 
            margin-bottom: 20px; 
        }
        .header-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .header-left { 
            width: 80px; 
        }
        .header-right { 
            line-height: 1.4; 
        }
        .logo { 
            width: 70px; 
            max-height: 70px; 
            margin-bottom: 5px;
        }
        
        .clinic-name { 
            font-size: 13px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .clinic-info { 
            font-size: 11px; 
        }
        
        .title { 
            text-align: center; 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 15px; 
            text-transform: uppercase; 
        }
        
        table.data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
        }
        table.data-table th, table.data-table td { 
            border: 1px solid #000; 
            padding: 5px 8px; 
            text-align: left; 
        }
        table.data-table th { 
            background-color: #d9d9d9; 
            font-weight: bold; 
            text-align: center; 
            font-size: 12px; 
            text-transform: uppercase;
        }
        table.data-table .label { 
            font-weight: bold; 
            width: 35%; 
        }
        
        .signature-area { 
            margin-top: 50px; 
            text-align: center; 
        }
        .signature-text { 
            font-style: italic; 
            margin-bottom: 40px; 
            font-size: 11px; 
        }
        .signature-line { 
            width: 250px; 
            border-bottom: 1px solid #000; 
            margin: 0 auto; 
            margin-bottom: 5px;
        }
        .doctor-name { 
            font-weight: bold; 
            font-size: 11px; 
        }
    </style>
</head>
<body>
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if(isset($empresa) && $empresa->IMAGEM)
                        <img src="{{ $empresa->IMAGEM }}" class="logo" />
                    @endif
                </td>
                <td class="header-right">
                    <div class="clinic-name">{{ $empresa->DESCRICAO ?? 'CLÍNICA' }}</div>
                    <div class="clinic-info">
                        @if(isset($empresa) && $empresa->RUA){{ $empresa->RUA }}@endif
                        @if(isset($empresa) && $empresa->CIDADE), {{ $empresa->CIDADE }}@endif
                        @if(isset($empresa) && $empresa->PROVINCIA) - {{ $empresa->PROVINCIA }}@endif
                        <br>
                        @if(isset($empresa) && $empresa->TELEFONE)Tel: {{ $empresa->TELEFONE }}<br>@endif
                        @if(isset($empresa) && $empresa->EMAIL)Email: {{ $empresa->EMAIL }}<br>@endif
                        @if(isset($empresa) && $empresa->NIF)NIF: {{ $empresa->NIF }}@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="title">GUIA DE TRANSFERÊNCIA</div>

    <table class="data-table">
        <tr>
            <th colspan="2">IDENTIFICAÇÃO DO PACIENTE</th>
        </tr>
        <tr>
            <td class="label">Data da Guia:</td>
            <td>{{ date('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Paciente:</td>
            <td>{{ $paciente->PacienteNome ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Idade:</td>
            <td>{{ $idade ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Género:</td>
            <td>{{ $paciente->Genero ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Data/Hora de Admissão:</td>
            <td>{{ $hora_admissao ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Data/Hora de Saída:</td>
            <td>{{ $hora_saida ?? '___' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <tr>
            <th colspan="2">DADOS CLÍNICOS E SINAIS VITAIS (TRIAGEM)</th>
        </tr>
        <tr>
            <td class="label">Peso:</td>
            <td>{{ $triagem->Peso ?? '0' }} kg</td>
        </tr>
        <tr>
            <td class="label">Temperatura:</td>
            <td>{{ $triagem->Temperatura ?? '0' }} °C</td>
        </tr>
        <tr>
            <td class="label">Tensão Arterial (TA):</td>
            <td>{{ $triagem->PressaoArterial ?? '0' }}</td>
        </tr>
        <tr>
            <td class="label">Frequência Cardíaca (FC):</td>
            <td>{{ $triagem->FrequenciaCardioca ?? '0' }} bpm</td>
        </tr>
        <tr>
            <td class="label">Frequência Respiratória (FR):</td>
            <td>{{ $triagem->FrequenciaRespiratoria ?? '0' }} cpm</td>
        </tr>
        <tr>
            <td class="label">Saturação (SPO2):</td>
            <td>{{ $triagem->SituacaoOxigenio ?? '0' }} %</td>
        </tr>
        <tr>
            <td class="label">Classificação de Risco:</td>
            <td>{{ $triagem->ClassificacaoRisco ?? 'normal' }}</td>
        </tr>
        <tr>
            <td class="label">Observações da Triagem:</td>
            <td>{{ $triagem->Obs ?? 'Sem' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <tr>
            <th colspan="2">INFORMAÇÕES DE TRANSFERÊNCIA</th>
        </tr>
        <tr>
            <td class="label">Correspondente:</td>
            <td>Da {{ $empresa->DESCRICAO ?? 'CLÍNICA' }} Para o Serviço de: {{ $correspondente ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Motivo da Transferência:</td>
            <td>{{ $motivo ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Estado Geral:</td>
            <td>{{ $estado_geral ?? 'Razoavel' }}</td>
        </tr>
        <tr>
            <td class="label">História Clínica / Sintomas:</td>
            <td>{{ $historia_clinica ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Exames Clínicos:</td>
            <td>{{ $exames_realizados ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Análises Complementares:</td>
            <td>{{ $analises ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Diagnóstico:</td>
            <td>{{ $diagnostico ?? '___' }}</td>
        </tr>
        <tr>
            <td class="label">Tratamento Feito:</td>
            <td>
                @if(isset($receita) && count($receita) > 0)
                    @foreach($receita as $item)
                        {{ $item->Farmaco }} ({{ $item->Dosagem }}) - {{ $item->Dias }}<br>
                    @endforeach
                @endif
                {{ $tratamento ?? '' }}
            </td>
        </tr>
    </table>

    <div class="signature-area">
        <div class="signature-text">Assinatura do Médico Assistente</div>
        <div class="signature-line"></div>
        <div class="doctor-name">Dr(a).: {{ $paciente->MedicoNome ?? '_______________________' }}</div>
    </div>
</body>
</html>
