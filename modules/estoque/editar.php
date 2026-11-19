<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();

$erro = '';

/*
|--------------------------------------------------------------------------
| RECEBER ID
|--------------------------------------------------------------------------
*/

$id_produto = $_GET['id'] ?? null;


if (!$id_produto) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/estoque/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTO
|--------------------------------------------------------------------------
*/

$sql = "SELECT *
        FROM produtos
        WHERE id_produto = :id_produto";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_produto',
    $id_produto,
    PDO::PARAM_INT
);

$stmt->execute();

$produto = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$produto) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/estoque/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALORES DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$nome = $produto['nome'];
$categoria = $produto['categoria'];
$quantidade = $produto['quantidade'];
$estoque_minimo = $produto['estoque_minimo'];
$preco_compra = $produto['preco_compra'];
$preco_venda = $produto['preco_venda'];
$status = $produto['status'];


/*
|--------------------------------------------------------------------------
| ATUALIZAR PRODUTO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $nome = $_POST['nome'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $estoque_minimo = $_POST['estoque_minimo'] ?? 5;
    $preco_compra = $_POST['preco_compra'] ?? '';
    $preco_venda = $_POST['preco_venda'] ?? '';
    $status = $_POST['status'] ?? 'Ativo';


    if (
        trim($nome) === '' ||
        trim($categoria) === '' ||
        $preco_compra === '' ||
        $preco_venda === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';


    } elseif ((int)$quantidade < 0) {


        $erro = 'A quantidade não pode ser negativa.';


    } elseif ((int)$estoque_minimo < 0) {


        $erro = 'O estoque mínimo não pode ser negativo.';


    } else {


        try {


            $sql = "UPDATE produtos SET

                        nome = :nome,
                        categoria = :categoria,
                        quantidade = :quantidade,
                        estoque_minimo = :estoque_minimo,
                        preco_compra = :preco_compra,
                        preco_venda = :preco_venda,
                        status = :status

                    WHERE id_produto = :id_produto";


            $stmt = $db->prepare($sql);


            $stmt->bindValue(
                ':nome',
                trim($nome)
            );


            $stmt->bindValue(
                ':categoria',
                trim($categoria)
            );


            $stmt->bindValue(
                ':quantidade',
                $quantidade,
                PDO::PARAM_INT
            );


            $stmt->bindValue(
                ':estoque_minimo',
                $estoque_minimo,
                PDO::PARAM_INT
            );


            $stmt->bindValue(
                ':preco_compra',
                $preco_compra
            );


            $stmt->bindValue(
                ':preco_venda',
                $preco_venda
            );


            $stmt->bindValue(
                ':status',
                $status
            );


            $stmt->bindValue(
                ':id_produto',
                $id_produto,
                PDO::PARAM_INT
            );


            $stmt->execute();


            header(
                'Location: ' .
                BASE_URL .
                '/modules/estoque/index.php?sucesso=2'
            );

            exit;


        } catch(PDOException $e) {


            $erro =
            'Ocorreu um erro ao atualizar o produto.';


        }

    }

}


?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


<?php include __DIR__ . '/../../includes/sidebar.php'; ?>


<div class="main">


<?php include __DIR__ . '/../../includes/navbar.php'; ?>


<main class="content">


<div class="page-header">


<div>

<h1>Editar Produto</h1>

<p>
Atualize as informações do produto.
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



<?php if($erro !== ''): ?>

<div class="alert alert-danger">

<i class="fa-solid fa-circle-exclamation"></i>

<?= htmlspecialchars($erro) ?>

</div>

<?php endif; ?>



<div class="card">


<div class="card-header">

<h2>Dados do produto</h2>

<span>
Altere as informações necessárias.
</span>

</div>



<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
Nome do produto *
</label>

<input
type="text"
name="nome"
value="<?= htmlspecialchars($nome) ?>"
required
>

</div>



<div class="form-group">

<label>
Categoria *
</label>

<input
type="text"
name="categoria"
value="<?= htmlspecialchars($categoria) ?>"
required
>

</div>



<div class="form-group">

<label>
Quantidade
</label>

<input
type="number"
name="quantidade"
value="<?= $quantidade ?>"
min="0"
>

</div>



<div class="form-group">

<label>
Estoque mínimo
</label>

<input
type="number"
name="estoque_minimo"
value="<?= $estoque_minimo ?>"
min="0"
>

</div>



<div class="form-group">

<label>
Preço de compra *
</label>

<input
type="number"
name="preco_compra"
value="<?= $preco_compra ?>"
step="0.01"
min="0"
required
>

</div>



<div class="form-group">

<label>
Preço de venda *
</label>

<input
type="number"
name="preco_venda"
value="<?= $preco_venda ?>"
step="0.01"
min="0"
required
>

</div>



<div class="form-group">

<label>
Status
</label>


<select name="status">


<option value="Ativo"
<?= $status === 'Ativo' ? 'selected' : '' ?>
>
Ativo
</option>


<option value="Inativo"
<?= $status === 'Inativo' ? 'selected' : '' ?>
>
Inativo
</option>


</select>


</div>



</div>



<div class="form-actions">


<a
href="<?= BASE_URL ?>/modules/estoque/index.php"
class="btn btn-secondary"
>
Cancelar
</a>



<button
type="submit"
class="btn btn-primary"
>

<i class="fa-solid fa-save"></i>

Salvar Alterações

</button>


</div>



</form>


</div>


</main>


</div>


</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>