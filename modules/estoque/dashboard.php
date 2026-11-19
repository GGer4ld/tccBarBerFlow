<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


$database = new Database();
$db = $database->conectar();



// TOTAL DE PRODUTOS

$sql = "SELECT COUNT(*)
        FROM produtos";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalProdutos = $stmt->fetchColumn();




// PRODUTOS COM ESTOQUE BAIXO

$sql = "SELECT COUNT(*)
        FROM produtos

        WHERE quantidade <= estoque_minimo

        AND status = 'Ativo'";


$stmt = $db->prepare($sql);
$stmt->execute();

$estoqueBaixo = $stmt->fetchColumn();




// VALOR TOTAL DO ESTOQUE

$sql = "SELECT 
        SUM(quantidade * preco_compra)

        FROM produtos";


$stmt = $db->prepare($sql);
$stmt->execute();

$valorEstoque = $stmt->fetchColumn();


if (!$valorEstoque) {

    $valorEstoque = 0;
}




// PRODUTOS ATIVOS

$sql = "SELECT COUNT(*)

        FROM produtos

        WHERE status = 'Ativo'";


$stmt = $db->prepare($sql);
$stmt->execute();


$produtosAtivos = $stmt->fetchColumn();

// PRODUTOS COM ESTOQUE BAIXO

$sql = "SELECT 
            nome,
            quantidade,
            estoque_minimo

        FROM produtos

        WHERE quantidade <= estoque_minimo

        AND status = 'Ativo'

        ORDER BY quantidade ASC

        LIMIT 5";


$stmt = $db->prepare($sql);
$stmt->execute();

$produtosBaixoEstoque = $stmt->fetchAll(PDO::FETCH_ASSOC);





// ÚLTIMAS MOVIMENTAÇÕES

$sql = "SELECT

            m.tipo_movimento,
            m.quantidade,
            m.data_movimento,
            p.nome


        FROM movimentacoes_estoque m


        LEFT JOIN produtos p

        ON p.id_produto = m.id_produto


        ORDER BY m.data_movimento DESC


        LIMIT 5";


$stmt = $db->prepare($sql);
$stmt->execute();


$ultimasMovimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>


    <div class="main">


        <?php include __DIR__ . '/../../includes/navbar.php'; ?>



        <main class="content">



            <div class="page-header">

                <div>

                    <h1>
                        Dashboard do Estoque
                    </h1>


                    <p>
                        Visão geral dos produtos cadastrados.
                    </p>


                </div>


            </div>

            <div class="quick-actions">


                <a
                    href="<?= BASE_URL ?>/modules/estoque/criar.php"
                    class="btn btn-primary">

                    <i class="fa-solid fa-plus"></i>

                    Novo Produto

                </a>



                <a
                    href="<?= BASE_URL ?>/modules/estoque/movimentar.php?tipo=Entrada"
                    class="btn btn-success">

                    <i class="fa-solid fa-arrow-down"></i>

                    Registrar Entrada

                </a>



                <a
                    href="<?= BASE_URL ?>/modules/estoque/movimentar.php?tipo=Saída"
                    class="btn btn-warning">

                    <i class="fa-solid fa-arrow-up"></i>

                    Registrar Saída

                </a>



                <a
                    href="<?= BASE_URL ?>/modules/relatorios/estoque.php"
                    class="btn btn-secondary"
                    target="_blank">

                    <i class="fa-solid fa-file-pdf"></i>

                    Relatório

                </a>


            </div>


            <div class="cards-grid">



                <div class="card dashboard-card">


                    <div class="dashboard-icon blue">

                        <i class="fa-solid fa-box"></i>

                    </div>



                    <div>

                        <span>
                            Produtos
                        </span>


                        <h2>
                            <?= $totalProdutos ?>
                        </h2>


                    </div>


                </div>





                <div class="card dashboard-card">


                    <div class="dashboard-icon orange">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>



                    <div>

                        <span>
                            Estoque baixo
                        </span>


                        <h2>
                            <?= $estoqueBaixo ?>
                        </h2>


                    </div>


                </div>






                <div class="card dashboard-card">


                    <div class="dashboard-icon green">

                        <i class="fa-solid fa-dollar-sign"></i>

                    </div>



                    <div>

                        <span>
                            Valor do estoque
                        </span>


                        <h2>

                            R$

                            <?= number_format(
                                $valorEstoque,
                                2,
                                ',',
                                '.'
                            ) ?>

                        </h2>


                    </div>


                </div>






                <div class="card dashboard-card">


                    <div class="dashboard-icon purple">

                        <i class="fa-solid fa-check"></i>

                    </div>



                    <div>

                        <span>
                            Produtos ativos
                        </span>


                        <h2>
                            <?= $produtosAtivos ?>
                        </h2>


                    </div>


                </div>



            </div>


            <!-- PRODUTOS COM ESTOQUE BAIXO -->

            <div class="dashboard-section">


                <div class="card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Produtos com estoque baixo
                            </h2>

                            <span>
                                Produtos que precisam de reposição.
                            </span>

                        </div>


                    </div>



                    <div class="table-container">


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        Produto
                                    </th>


                                    <th>
                                        Quantidade
                                    </th>


                                    <th>
                                        Estoque mínimo
                                    </th>


                                </tr>

                            </thead>



                            <tbody>


                                <?php if (count($produtosBaixoEstoque) > 0): ?>


                                    <?php foreach ($produtosBaixoEstoque as $produto): ?>


                                        <tr>


                                            <td>
                                                <?= htmlspecialchars($produto['nome']) ?>
                                            </td>


                                            <td>

                                                <span class="badge badge-danger">

                                                    <?= $produto['quantidade'] ?>

                                                </span>

                                            </td>


                                            <td>
                                                <?= $produto['estoque_minimo'] ?>
                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <tr>

                                        <td colspan="3">

                                            Nenhum produto com estoque baixo.

                                        </td>

                                    </tr>


                                <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            </div>





            <!-- ÚLTIMAS MOVIMENTAÇÕES -->


            <div class="dashboard-section">


                <div class="card">


                    <div class="card-header">


                        <div>


                            <h2>
                                Últimas movimentações
                            </h2>


                            <span>
                                Entradas e saídas recentes do estoque.
                            </span>


                        </div>


                    </div>




                    <div class="table-container">


                        <table>


                            <thead>


                                <tr>


                                    <th>
                                        Produto
                                    </th>


                                    <th>
                                        Tipo
                                    </th>


                                    <th>
                                        Quantidade
                                    </th>


                                    <th>
                                        Data
                                    </th>


                                </tr>


                            </thead>



                            <tbody>



                                <?php if (count($ultimasMovimentacoes) > 0): ?>


                                    <?php foreach ($ultimasMovimentacoes as $mov): ?>


                                        <tr>


                                            <td>

                                                <?= htmlspecialchars($mov['nome'] ?? 'Produto removido') ?>

                                            </td>



                                            <td>


                                                <?php if ($mov['tipo_movimento'] === 'Entrada'): ?>


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

                                                <?= $mov['quantidade'] ?>

                                            </td>



                                            <td>

                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime($mov['data_movimento'])
                                                ) ?>

                                            </td>


                                        </tr>



                                    <?php endforeach; ?>



                                <?php else: ?>


                                    <tr>

                                        <td colspan="4">

                                            Nenhuma movimentação encontrada.

                                        </td>

                                    </tr>


                                <?php endif; ?>



                            </tbody>


                        </table>


                    </div>


                </div>


            </div>

            <!-- GRÁFICOS -->


            <div class="dashboard-section">


                <div class="cards-grid">


                    <div class="card">


                        <h2>
                            Produtos por categoria
                        </h2>


                        <canvas id="graficoCategoria"></canvas>


                    </div>



                    <div class="card">


                        <h2>
                            Movimentações do estoque
                        </h2>


                        <canvas id="graficoMovimentacao"></canvas>


                    </div>


                </div>


            </div>

        </main>


    </div>


</div>

<script>
    fetch('graficos.php')

        .then(response => response.json())

        .then(dados => {



            // GRÁFICO DE CATEGORIAS


            const categorias =
                dados.categorias.map(
                    item => item.categoria
                );


            const quantidadeCategorias =
                dados.categorias.map(
                    item => item.total
                );



            new Chart(
                document.getElementById('graficoCategoria'), {

                    type: 'doughnut',


                    data: {

                        labels: categorias,


                        datasets: [{

                            data: quantidadeCategorias

                        }]

                    }

                }

            );





            // GRÁFICO DE MOVIMENTAÇÕES


            const tipos =
                dados.movimentacoes.map(
                    item => item.tipo_movimento
                );


            const quantidades =
                dados.movimentacoes.map(
                    item => item.total
                );



            new Chart(

                document.getElementById('graficoMovimentacao'),

                {

                    type: 'bar',


                    data: {


                        labels: tipos,


                        datasets: [{

                            label: 'Quantidade',

                            data: quantidades

                        }]


                    }


                }

            );



        });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>