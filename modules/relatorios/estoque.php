<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();


require_once __DIR__ . '/../../vendor/autoload.php';




use Dompdf\Dompdf;
use Dompdf\Options;



$database = new Database();
$db = $database->conectar();




// BUSCAR PRODUTOS

$sql = "SELECT

            nome,
            categoria,
            quantidade,
            estoque_minimo,
            preco_compra,
            preco_venda

        FROM produtos

        ORDER BY nome ASC";


$stmt = $db->prepare($sql);

$stmt->execute();


$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);




// RESUMOS

$sql = "SELECT COUNT(*)

        FROM produtos";


$stmt = $db->prepare($sql);
$stmt->execute();

$totalProdutos = $stmt->fetchColumn();



$sql = "SELECT SUM(quantidade * preco_compra)

        FROM produtos";


$stmt = $db->prepare($sql);
$stmt->execute();

$valorEstoque = $stmt->fetchColumn();



if (!$valorEstoque) {

    $valorEstoque = 0;
}




// CRIAR HTML

$html = '

<html>

<head>

<style>

body {

    font-family: Arial, sans-serif;

}


h1 {

    text-align:center;

    color:#1e293b;

}


.info {

    margin-bottom:20px;

}


table {

    width:100%;

    border-collapse:collapse;

}


th {

    background:#2563eb;

    color:white;

    padding:8px;

}


td {

    padding:8px;

    border:1px solid #ddd;

}


.baixo {

    background:#fee2e2;

}


</style>


</head>


<body>


<h1>
BarberFlow
</h1>


<h2>
Relatório de Estoque
</h2>



<div class="info">

<strong>
Data:
</strong>

' . date('d/m/Y') . '

<br>


<strong>
Total de produtos:
</strong>

' . $totalProdutos . '


<br>


<strong>
Valor do estoque:
</strong>

R$ ' . number_format($valorEstoque, 2, ',', '.') . '


</div>



<table>


<tr>

<th>
Produto
</th>


<th>
Categoria
</th>


<th>
Quantidade
</th>


<th>
Mínimo
</th>


<th>
Valor compra
</th>


</tr>';



foreach ($produtos as $produto) {


    $classe = '';


    if ($produto['quantidade'] <= $produto['estoque_minimo']) {

        $classe = 'class="baixo"';
    }



    $html .= '

<tr ' . $classe . '>


<td>
' . $produto['nome'] . '
</td>


<td>
' . $produto['categoria'] . '
</td>


<td>
' . $produto['quantidade'] . '
</td>


<td>
' . $produto['estoque_minimo'] . '
</td>


<td>

R$ ' . number_format(
        $produto['preco_compra'],
        2,
        ',',
        '.'
    ) . '

</td>


</tr>';
}



$html .= '

</table>


</body>

</html>';





// GERAR PDF


$options = new Options();

$options->set('defaultFont', 'Arial');


$dompdf = new Dompdf($options);


$dompdf->loadHtml($html);


$dompdf->setPaper('A4', 'landscape');


$dompdf->render();



$dompdf->stream(
    "relatorio_estoque.pdf",
    [
        "Attachment" => false
    ]
);
