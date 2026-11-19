<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


$database = new Database();
$db = $database->conectar();


$busca = $_GET['busca'] ?? '';



/*
|--------------------------------------------------------------------------
| BUSCAR MOVIMENTAÇÕES
|--------------------------------------------------------------------------
*/


$sql = "SELECT

            m.id_movimento,
            m.tipo_movimento,
            m.quantidade,
            m.descricao,
            m.data_movimento,

            p.nome AS produto

        FROM movimentacoes_estoque m

        INNER JOIN produtos p
            ON p.id_produto = m.id_produto

        WHERE p.nome LIKE :busca

        ORDER BY m.data_movimento DESC";



$stmt = $db->prepare($sql);


$stmt->bindValue(
    ':busca',
    '%' . $busca . '%'
);


$stmt->execute();


$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


<?php include __DIR__ . '/../../includes/sidebar.php'; ?>


<div class="main">


<?php include __DIR__ . '/../../includes/navbar.php'; ?>


<main class="content">



<div class="page-header">


<div>

<h1>Histórico de Estoque</h1>

<p>
Consulte todas as movimentações realizadas.
</p>

</div>



<a
href="<?= BASE_URL ?>/modules/estoque/index.php"
class="btn btn-secondary"
>

<i class="fa-solid fa-arrow-left"></i>

Voltar

</a>


</div>





<!-- PESQUISA -->


<div class="card">


<form method="GET">


<div class="form-group">


<label>
Pesquisar produto
</label>


<div class="search-box">


<input

type="text"

name="busca"

value="<?= htmlspecialchars($busca) ?>"

placeholder="Digite o nome do produto..."

>


<button

type="submit"

class="btn btn-primary"

>

<i class="fa-solid fa-magnifying-glass"></i>

Pesquisar

</button>


</div>


</div>


</form>


</div>






<!-- TABELA -->


<div class="card">


<div class="card-header">


<h2>
Movimentações realizadas
</h2>


<span>

<?= count($movimentacoes) ?>

registro(s)

</span>


</div>





<div class="table-responsive">


<table class="table">


<thead>


<tr>

<th>ID</th>

<th>Produto</th>

<th>Tipo</th>

<th>Quantidade</th>

<th>Descrição</th>

<th>Data</th>

</tr>


</thead>



<tbody>



<?php if(empty($movimentacoes)): ?>


<tr>


<td colspan="6" class="text-center">

Nenhuma movimentação encontrada.

</td>


</tr>



<?php else: ?>



<?php foreach($movimentacoes as $movimento): ?>


<tr>


<td>

<?= $movimento['id_movimento'] ?>

</td>



<td>

<strong>

<?= htmlspecialchars($movimento['produto']) ?>

</strong>

</td>




<td>


<?php if($movimento['tipo_movimento'] === 'Entrada'): ?>


<span class="badge badge-success">

Entrada

</span>



<?php else: ?>


<span class="badge badge-danger">

Saída

</span>



<?php endif; ?>


</td>





<td>


<?php if($movimento['tipo_movimento'] === 'Entrada'): ?>

+
<?= $movimento['quantidade'] ?>


<?php else: ?>

-
<?= $movimento['quantidade'] ?>


<?php endif; ?>


</td>





<td>


<?= $movimento['descricao']
    ? htmlspecialchars($movimento['descricao'])
    : '—'
?>


</td>






<td>


<?= date(
    'd/m/Y H:i',
    strtotime($movimento['data_movimento'])
) ?>


</td>



</tr>



<?php endforeach; ?>



<?php endif; ?>



</tbody>


</table>


</div>


</div>




</main>


</div>


</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>