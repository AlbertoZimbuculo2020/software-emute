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
            width: 50%;
            padding: 15px;
            vertical-align: top;
            box-sizing: border-box;
            font-size: 9px !important; /* Reduzir fonte global no modo econômico */
        }
        .divider {
            position: fixed;
            left: 50%;
            top: 0;
            bottom: 0;
            border-left: 1px dashed #ccc;
            z-index: 100;
        }
        
        /* Ajustes agressivos para forçar o conteúdo a caber */
        .col-report img { max-width: 50px !important; height: auto !important; }
        .col-report table { font-size: 8px !important; }
        .col-report h1, .col-report h2, .col-report h3 { font-size: 12px !important; margin: 5px 0 !important; }
        .col-report .report-title { font-size: 11px !important; }
        .col-report .clinic-name { font-size: 10px !important; }
        .col-report p, .col-report div, .col-report td { line-height: 1.1 !important; margin-bottom: 2px !important; }

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
    <div class="divider"></div>
    <table class="wrapper-table">
        <tr>
            <td class="col-report">
                @include($original_view, $data)
            </td>
            <td class="col-report">
                @include($original_view, $data)
            </td>
        </tr>
    </table>
</body>
</html>
