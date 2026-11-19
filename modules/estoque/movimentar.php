<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


$database = new Database();
$db = $database->conectar();


$erro = '';



/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_produto,
            nome,
            quantidade

        FROM produtos

        WHERE status = 'Ativo'

        ORDER BY nome ASC";


$stmt = $db->prepare($sql);

$stmt->execute();


$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);




/*
|--------------------------------------------------------------------------
| VALORES DO FORMULÁRIO
|--------------------------------------------------------------------------
*/


$id_produto = $_POST['id_produto'] 
    ?? $_GET['id_produto'] 
    ?? '';

$tipo_movimento = $_POST['tipo_movimento'] ?? '';

$quantidade = $_POST['quantidade'] ?? '';

$descricao = $_POST['descricao'] ?? '';




/*
|--------------------------------------------------------------------------
| PROCESSAR
|--------------------------------------------------------------------------
*/


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (
        !$id_produto ||
        !$tipo_movimento ||
        !$quantidade
    ) {


        $erro = 'Preencha todos os campos obrigatórios.';


    } elseif ((int)$quantidade <= 0) {


        $erro = 'A quantidade deve ser maior que zero.';


    } else {


        try {


            /*
            |--------------------------------------------------------------------------
            | BUSCAR ESTOQUE ATUAL
            |--------------------------------------------------------------------------
            */


            $sql = "SELECT quantidade

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


                $erro = 'Produto não encontrado.';


            } else {


                $estoqueAtual = (int)$produto['quantidade'];



                if (
                    $tipo_movimento === 'Saída'
                    &&
                    $quantidade > $estoqueAtual
                ) {


                    $erro =
                    'Quantidade de saída maior que o estoque atual.';



                } else {



                    /*
                    |--------------------------------------------------------------------------
                    | CALCULAR NOVO ESTOQUE
                    |--------------------------------------------------------------------------
                    */


                    if ($tipo_movimento === 'Entrada') {


                        $novoEstoque =
                        $estoqueAtual + $quantidade;


                    } else {


                        $novoEstoque =
                        $estoqueAtual - $quantidade;

                    }




                    /*
                    |--------------------------------------------------------------------------
                    | ATUALIZAR PRODUTO
                    |--------------------------------------------------------------------------
                    */


                    $sql = "UPDATE produtos

                            SET quantidade = :quantidade

                            WHERE id_produto = :id_produto";


                    $stmt = $db->prepare($sql);


                    $stmt->bindValue(
                        ':quantidade',
                        $novoEstoque,
                        PDO::PARAM_INT
                    );


                    $stmt->bindValue(
                        ':id_produto',
                        $id_produto,
                        PDO::PARAM_INT
                    );


                    $stmt->execute();





                    /*
                    |--------------------------------------------------------------------------
                    | REGISTRAR MOVIMENTAÇÃO
                    |--------------------------------------------------------------------------
                    */


                    $sql = "INSERT INTO movimentacoes_estoque
                    (
                        id_produto,
                        tipo_movimento,
                        quantidade,
                        descricao
                    )

                    VALUES
                    (
                        :id_produto,
                        :tipo_movimento,
                        :quantidade,
                        :descricao
                    )";



                    $stmt = $db->prepare($sql);



                    $stmt->bindValue(
                        ':id_produto',
                        $id_produto,
                        PDO::PARAM_INT
                    );


                    $stmt->bindValue(
                        ':tipo_movimento',
                        $tipo_movimento
                    );


                    $stmt->bindValue(
                        ':quantidade',
                        $quantidade,
                        PDO::PARAM_INT
                    );


                    $stmt->bindValue(
                        ':descricao',
                        $descricao
                    );


                    $stmt->execute();




header(
    'Location: ' .
    BASE_URL .
    '/modules/estoque/dashboard.php?sucesso=4'
);


                    exit;


                }

            }



        } catch(PDOException $e) {


            $erro =
            'Erro ao registrar movimentação.';


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

<h1>Movimentação de Estoque</h1>

<p>
Registre entradas e saídas de produtos.
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

<h2>Nova movimentação</h2>

<span>
Atualize o estoque do produto.
</span>

</div>




<form method="POST">


<div class="form-grid">



<div class="form-group">


<label>
Produto *
</label>


<select
name="id_produto"
required
>


<option value="">
Selecione o produto
</option>


<?php foreach($produtos as $produto): ?>


<option

value="<?= $produto['id_produto'] ?>"

<?= $id_produto == $produto['id_produto'] ? 'selected' : '' ?>

>


<?= htmlspecialchars($produto['nome']) ?>

(Estoque:
<?= $produto['quantidade'] ?>
)


</option>


<?php endforeach; ?>


</select>


</div>





<div class="form-group">


<label>
Tipo *
</label>


<select
name="tipo_movimento"
required
>


<option value="">
Selecione
</option>


<option 
value="Entrada"
<?= $tipo_movimento === 'Entrada' ? 'selected' : '' ?>
>
Entrada
</option>


<option 
value="Saída"
<?= $tipo_movimento === 'Saída' ? 'selected' : '' ?>
>
Saída
</option>


</select>


</div>





<div class="form-group">


<label>
Quantidade *
</label>


<input

type="number"

name="quantidade"

value="<?= htmlspecialchars($quantidade) ?>"

min="1"

required

>


</div>





<div class="form-group form-group-full">


<label>
Descrição
</label>


<textarea

name="descricao"

rows="4"

placeholder="Ex.: Compra fornecedor, uso em atendimento..."

><?= htmlspecialchars($descricao) ?></textarea>


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

<i class="fa-solid fa-boxes-stacked"></i>

Salvar Movimentação

</button>


</div>



</form>


</div>



</main>


</div>


</div>


<?php include __DIR__ . '/../../includes/footer.php'; ?>