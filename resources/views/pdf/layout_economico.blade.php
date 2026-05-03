<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { 
            margin: 0; 
            size: a4 landscape;
        }
        body { 
            margin: 0; 
            padding: 0; 
            font-family: 'Helvetica', sans-serif;
        }
        .wrapper-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .col-report {
            width: 48%;
            padding: 10px;
            box-sizing: border-box;
            font-size: 9px !important;
        }
        .divider-td {
            width: 4%;
            text-align: center;
            vertical-align: middle;
        }
        .divider-line {
            height: 100%;
            border-left: 1px dashed #ccc;
            margin: 0 auto;
        }
        
        /* Ajustes agressivos para forçar o conteúdo a caber */
        .col-report img { max-width: 50px !important; height: auto !important; }
        .col-report table { width: 100% !important; font-size: 8px !important; border-collapse: collapse !important; }
        .col-report h1, .col-report h2, .col-report h3 { font-size: 11px !important; margin: 3px 0 !important; }
        .col-report .report-title { font-size: 11px !important; margin-top: 5px !important; }
        .col-report .clinic-name { font-size: 9px !important; }
        .col-report p, .col-report div, .col-report td { line-height: 1.1 !important; margin-bottom: 1px !important; }

        /* Neutralizar cabeçalhos e rodapés fixos */
        header, footer { 
            position: static !important; 
            display: block !important;
            margin: 0 !important; 
            padding: 0 !important;
            height: auto !important;
            width: 100% !important;
        }
    </style>
</head>
<body>
    <table class="wrapper-table">
        <tr>
            <td class="col-report" style="vertical-align: top;">
                @include($original_view, $data)
            </td>
            
            @if($data['is_duplicate'] ?? true)
                <td class="divider-td">
                    <div class="divider-line"></div>
                </td>
                <td class="col-report" style="vertical-align: top;">
                    @include($original_view, $data)
                </td>
            @else
                <td class="col-report"></td>
            @endif
        </tr>
    </table>
</body>
</html>
