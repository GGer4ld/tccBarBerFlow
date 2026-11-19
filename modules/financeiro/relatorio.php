<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

require_once __DIR__ . '/../../vendor/autoload.php';


use Dompdf\Dompdf;
use Dompdf\Options;



$database = new Database();
$db = $database->conectar();



$sql = "SELECT

            tipo,
            categoria,
            descricao,
            valor,
            data_lancamento

        FROM lancamentos_financeiros

        ORDER BY data_lancamento DESC";


$stmt = $db->prepare($sql);

$stmt->execute();


$lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);





/*
|--------------------------------------------------------------------------
| CALCULAR TOTAIS
|--------------------------------------------------------------------------
*/


$totalEntradas = 0;
$totalSaidas = 0;



foreach ($lancamentos as $item) {


    if ($item['tipo'] === 'Entrada') {

        $totalEntradas += $item['valor'];
    } else {

        $totalSaidas += $item['valor'];
    }
}



$saldo = $totalEntradas - $totalSaidas;





/*
|--------------------------------------------------------------------------
| HTML DO PDF
|--------------------------------------------------------------------------
*/


$html = '

<html>

<head>

<style>

body{

    font-family: Arial, sans-serif;

    font-size: 12px;

}


h1{

    text-align:center;

}


.resumo{

    margin-bottom:20px;

}


table{

    width:100%;

    border-collapse:collapse;

}


table, th, td{

    border:1px solid #ccc;

}


th{

    background:#eee;

}


th, td{

    padding:8px;

    text-align:left;

}


.valor{

    text-align:right;

}


</style>


</head>



<body>



<h1>
BarberFlow - Relatório Financeiro
</h1>



<div class="resumo">


<p>
<strong>Total de Entradas:</strong>
R$ ' . number_format($totalEntradas, 2, ',', '.') . '
</p>



<p>
<strong>Total de Saídas:</strong>
R$ ' . number_format($totalSaidas, 2, ',', '.') . '
</p>



<p>
<strong>Saldo:</strong>
R$ ' . number_format($saldo, 2, ',', '.') . '
</p>


</div>




<table>


<thead>

<tr>

<th>
Data
</th>


<th>
Tipo
</th>


<th>
Categoria
</th>


<th>
Descrição
</th>


<th>
Valor
</th>


</tr>


</thead>



<tbody>';



foreach ($lancamentos as $item) {


    $html .= '

<tr>


<td>
' . date(
        'd/m/Y',
        strtotime($item['data_lancamento'])
    ) . '
</td>



<td>
' . $item['tipo'] . '
</td>



<td>
' . $item['categoria'] . '
</td>



<td>
' . $item['descricao'] . '
</td>



<td class="valor">

R$ ' . number_format(
        $item['valor'],
        2,
        ',',
        '.'
    ) . '

</td>



</tr>';
}



/*
|--------------------------------------------------------------------------
| HTML DO PDF
|--------------------------------------------------------------------------
*/


$html = '

<html>

<head>

<style>

body{

    font-family: Arial, sans-serif;

    font-size:12px;

    color:#333;

}


.header{

    text-align:center;

    margin-bottom:30px;

}


.logo{

    font-size:26px;

    font-weight:bold;

    color:#2563eb;

}


.subtitle{

    font-size:14px;

    color:#666;

}



.info{

    margin-bottom:25px;

}


.cards{

    width:100%;

    margin-bottom:25px;

}



.card{

    border:1px solid #ddd;

    padding:12px;

}



.card-title{

    font-size:11px;

    color:#666;

}



.card-value{

    font-size:18px;

    font-weight:bold;

}




table{

    width:100%;

    border-collapse:collapse;

    margin-top:15px;

}



th{

    background:#2563eb;

    color:white;

    padding:10px;

    text-align:left;

}



td{

    border-bottom:1px solid #ddd;

    padding:9px;

}



.valor{

    text-align:right;

}



.entrada{

    color:#16a34a;

    font-weight:bold;

}



.saida{

    color:#dc2626;

    font-weight:bold;

}



.footer{

    margin-top:40px;

    text-align:center;

    font-size:10px;

    color:#777;

}



</style>


</head>


<body>



<div class="header">


<div class="logo">

BarberFlow

</div>


<div class="subtitle">

Relatório Financeiro

</div>


<p>

Gerado em:
' . date('d/m/Y H:i') . '

</p>


</div>




<div class="info">


<table class="cards">


<tr>


<td class="card">


<div class="card-title">

Total de Entradas

</div>


<div class="card-value">

R$ ' . number_format($totalEntradas, 2, ',', '.') . '

</div>


</td>




<td class="card">


<div class="card-title">

Total de Saídas

</div>


<div class="card-value">

R$ ' . number_format($totalSaidas, 2, ',', '.') . '

</div>


</td>




<td class="card">


<div class="card-title">

Saldo

</div>


<div class="card-value">

R$ ' . number_format($saldo, 2, ',', '.') . '

</div>


</td>



</tr>


</table>


</div>





<h3>

Lançamentos Financeiros

</h3>



<table>


<thead>


<tr>


<th>

Data

</th>


<th>

Tipo

</th>


<th>

Categoria

</th>


<th>

Descrição

</th>


<th>

Valor

</th>


</tr>


</thead>



<tbody>';




foreach ($lancamentos as $item) {


    $classe = $item['tipo'] === 'Entrada'
        ? 'entrada'
        : 'saida';



    $html .= '


<tr>


<td>

' . date(
        'd/m/Y',
        strtotime($item['data_lancamento'])
    ) . '

</td>



<td class="' . $classe . '">

' . $item['tipo'] . '

</td>



<td>

' . $item['categoria'] . '

</td>



<td>

' . $item['descricao'] . '

</td>



<td class="valor">

R$ ' . number_format(
        $item['valor'],
        2,
        ',',
        '.'
    ) . '

</td>



</tr>';
}



$html .= '


</tbody>


</table>




<div class="footer">


BarberFlow - Sistema de Gestão para Barbearias


</div>



</body>


</html>';






/*
|--------------------------------------------------------------------------
| GERAR PDF
|--------------------------------------------------------------------------
*/


$options = new Options();

$options->set(
    'defaultFont',
    'Arial'
);



$dompdf = new Dompdf($options);



$dompdf->loadHtml($html);



$dompdf->setPaper(
    'A4',
    'portrait'
);



$dompdf->render();



$dompdf->stream(
    'relatorio_financeiro.pdf',
    [
        'Attachment' => false
    ]
);
