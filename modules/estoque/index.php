<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();



/*
|--------------------------------------------------------------------------
| PESQUISA
|--------------------------------------------------------------------------
*/


$busca = $_GET['busca'] ?? '';



/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTOS
|--------------------------------------------------------------------------
*/


$sql = "SELECT

            id_produto,
            nome,
            categoria,
            quantidade,
            estoque_minimo,
            preco_compra,
            preco_venda,
            status

        FROM produtos

        WHERE nome LIKE :busca
           OR categoria LIKE :busca

        ORDER BY nome ASC";


$stmt = $db->prepare($sql);


$stmt->bindValue(
    ':busca',
    '%' . $busca . '%'
);


$stmt->execute();


$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);



/*
|--------------------------------------------------------------------------
| MENSAGENS
|--------------------------------------------------------------------------
*/


$sucesso = $_GET['sucesso'] ?? '';
$erro = $_GET['erro'] ?? '';


$mensagemSucesso = '';


if ($sucesso === '1') {

    $mensagemSucesso = 'Produto cadastrado com sucesso!';
} elseif ($sucesso === '2') {

    $mensagemSucesso = 'Produto atualizado com sucesso!';
} elseif ($sucesso === '3') {

    $mensagemSucesso = 'Produto excluído com sucesso!';
}



?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>



    <div class="main">


        <?php include __DIR__ . '/../../includes/navbar.php'; ?>



        <main class="content">



            <?php if ($mensagemSucesso !== ''): ?>


                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= htmlspecialchars($mensagemSucesso) ?>

                </div>


            <?php endif; ?>




            <?php if ($erro === 'excluir'): ?>


                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Não foi possível excluir este produto.
                    Ele possui movimentações registradas.

                </div>


            <?php endif; ?>





            <div class="page-header">


                <div>

                    <h1>Estoque</h1>


                    <p>
                        Gerencie os produtos e o estoque da barbearia.
                    </p>


                </div>



                <a
                    href="<?= BASE_URL ?>/modules/estoque/criar.php"
                    class="btn btn-primary">


                    <i class="fa-solid fa-box-open"></i>

                    Novo Produto


                </a>


            </div>





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

                                placeholder="Nome ou categoria...">



                            <button

                                type="submit"

                                class="btn btn-primary">


                                <i class="fa-solid fa-magnifying-glass"></i>

                                Pesquisar


                            </button>


                        </div>


                    </div>


                </form>


            </div>






            <div class="card">


                <div class="card-header">


                    <h2>
                        Produtos cadastrados
                    </h2>


                    <span>

                        <?= count($produtos) ?> produto(s)

                    </span>


                </div>





                <div class="table-responsive">


                    <table class="table">


                        <thead>


                            <tr>


                                <th>ID</th>

                                <th>Produto</th>

                                <th>Categoria</th>

                                <th>Quantidade</th>

                                <th>Estoque mínimo</th>

                                <th>Preço compra</th>

                                <th>Preço venda</th>

                                <th>Status</th>

                                <th>Ações</th>


                            </tr>


                        </thead>





                        <tbody>


                            <?php if (empty($produtos)): ?>


                                <tr>


                                    <td colspan="9" class="text-center">

                                        Nenhum produto encontrado.

                                    </td>


                                </tr>



                            <?php else: ?>





                                <?php foreach ($produtos as $produto): ?>


                                    <tr>



                                        <td>

                                            <?= $produto['id_produto'] ?>

                                        </td>





                                        <td>


                                            <strong>

                                                <?= htmlspecialchars($produto['nome']) ?>

                                            </strong>


                                        </td>





                                        <td>

                                            <?= htmlspecialchars($produto['categoria']) ?>

                                        </td>





                                        <td>


                                            <?php


                                            $quantidade = (int)$produto['quantidade'];

                                            $minimo = (int)$produto['estoque_minimo'];


                                            ?>


                                            <?php if ($quantidade == 0): ?>


                                                <span class="badge badge-danger">

                                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                                    0

                                                </span>



                                            <?php elseif ($quantidade <= $minimo): ?>


                                                <span class="badge badge-warning">

                                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                                    <?= $quantidade ?>

                                                </span>



                                            <?php else: ?>


                                                <strong>

                                                    <?= $quantidade ?>

                                                </strong>



                                            <?php endif; ?>


                                        </td>





                                        <td>

                                            <?= $minimo ?>

                                        </td>





                                        <td>

                                            R$

                                            <?= number_format(
                                                $produto['preco_compra'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        </td>





                                        <td>

                                            R$

                                            <?= number_format(
                                                $produto['preco_venda'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        </td>





                                        <td>


                                            <?php if ($produto['status'] == 'Ativo'): ?>


                                                <span class="badge badge-success">

                                                    Ativo

                                                </span>


                                            <?php else: ?>


                                                <span class="badge badge-danger">

                                                    Inativo

                                                </span>


                                            <?php endif; ?>


                                        </td>







                                        <td>


                                            <div class="table-actions">





                                                <a

                                                    href="<?= BASE_URL ?>/modules/estoque/editar.php?id=<?= $produto['id_produto'] ?>"

                                                    class="btn btn-sm btn-secondary"

                                                    title="Editar">

                                                    <i class="fa-solid fa-pen"></i>

                                                </a>






                                                <a

                                                    href="<?= BASE_URL ?>/modules/estoque/movimentar.php?id_produto=<?= $produto['id_produto'] ?>"

                                                    class="btn btn-sm btn-primary"

                                                    title="Movimentar estoque">

                                                    <i class="fa-solid fa-boxes-stacked"></i>

                                                </a>







                                                <a

                                                    href="<?= BASE_URL ?>/modules/estoque/historico.php?id_produto=<?= $produto['id_produto'] ?>"

                                                    class="btn btn-sm btn-secondary"

                                                    title="Histórico">

                                                    <i class="fa-solid fa-clock-rotate-left"></i>

                                                </a>







                                                <a

                                                    href="<?= BASE_URL ?>/modules/estoque/excluir.php?id=<?= $produto['id_produto'] ?>"

                                                    class="btn btn-sm btn-danger"

                                                    title="Excluir"

                                                    onclick="return confirm('Deseja realmente excluir este produto?');">

                                                    <i class="fa-solid fa-trash"></i>

                                                </a>






                                            </div>


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