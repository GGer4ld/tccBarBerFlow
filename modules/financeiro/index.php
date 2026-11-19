<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$filtro = $_GET['tipo'] ?? '';

$dataInicial = $_GET['data_inicial'] ?? '';

$dataFinal = $_GET['data_final'] ?? '';



/*
|--------------------------------------------------------------------------
| VALIDAR TIPO
|--------------------------------------------------------------------------
*/

if (
    $filtro !== 'Entrada' &&
    $filtro !== 'Saída'
) {

    $filtro = '';

}



/*
|--------------------------------------------------------------------------
| CONDIÇÕES DOS FILTROS
|--------------------------------------------------------------------------
*/

$condicoes = [];

$parametros = [];



if ($filtro !== '') {

    $condicoes[] = "tipo = :tipo";

    $parametros[':tipo'] = $filtro;

}



if ($dataInicial !== '') {

    $condicoes[] = "DATE(data_lancamento) >= :data_inicial";

    $parametros[':data_inicial'] = $dataInicial;

}



if ($dataFinal !== '') {

    $condicoes[] = "DATE(data_lancamento) <= :data_final";

    $parametros[':data_final'] = $dataFinal;

}



/*
|--------------------------------------------------------------------------
| MONTAR WHERE
|--------------------------------------------------------------------------
*/

$where = '';



if (!empty($condicoes)) {

    $where = ' WHERE ' . implode(
        ' AND ',
        $condicoes
    );

}



/*
|--------------------------------------------------------------------------
| TOTAL DE ENTRADAS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COALESCE(SUM(valor), 0)

        FROM lancamentos_financeiros

        WHERE tipo = 'Entrada'";



if ($dataInicial !== '') {

    $sql .= " AND DATE(data_lancamento) >= :data_inicial";

}



if ($dataFinal !== '') {

    $sql .= " AND DATE(data_lancamento) <= :data_final";

}



$stmt = $db->prepare($sql);



if ($dataInicial !== '') {

    $stmt->bindValue(
        ':data_inicial',
        $dataInicial
    );

}



if ($dataFinal !== '') {

    $stmt->bindValue(
        ':data_final',
        $dataFinal
    );

}



$stmt->execute();



$totalEntradas = $stmt->fetchColumn();



/*
|--------------------------------------------------------------------------
| TOTAL DE SAÍDAS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COALESCE(SUM(valor), 0)

        FROM lancamentos_financeiros

        WHERE tipo = 'Saída'";



if ($dataInicial !== '') {

    $sql .= " AND DATE(data_lancamento) >= :data_inicial";

}



if ($dataFinal !== '') {

    $sql .= " AND DATE(data_lancamento) <= :data_final";

}



$stmt = $db->prepare($sql);



if ($dataInicial !== '') {

    $stmt->bindValue(
        ':data_inicial',
        $dataInicial
    );

}



if ($dataFinal !== '') {

    $stmt->bindValue(
        ':data_final',
        $dataFinal
    );

}



$stmt->execute();



$totalSaidas = $stmt->fetchColumn();



/*
|--------------------------------------------------------------------------
| SALDO
|--------------------------------------------------------------------------
*/

$saldo = $totalEntradas - $totalSaidas;



/*
|--------------------------------------------------------------------------
| BUSCAR LANÇAMENTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT

            id_lancamento,
            id_atendimento,
            tipo,
            categoria,
            descricao,
            valor,
            data_lancamento

        FROM lancamentos_financeiros"

        . $where .

        " ORDER BY data_lancamento DESC";



$stmt = $db->prepare($sql);



foreach ($parametros as $parametro => $valor) {

    $stmt->bindValue(
        $parametro,
        $valor
    );

}



$stmt->execute();



$lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);



/*
|--------------------------------------------------------------------------
| URL DO RELATÓRIO
|--------------------------------------------------------------------------
*/

$urlRelatorio =
    BASE_URL .
    '/modules/financeiro/relatorio.php';



$parametrosRelatorio = [];



if ($filtro !== '') {

    $parametrosRelatorio['tipo'] = $filtro;

}



if ($dataInicial !== '') {

    $parametrosRelatorio['data_inicial'] = $dataInicial;

}



if ($dataFinal !== '') {

    $parametrosRelatorio['data_final'] = $dataFinal;

}



if (!empty($parametrosRelatorio)) {

    $urlRelatorio .= '?' .
        http_build_query(
            $parametrosRelatorio
        );

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

                    <h1>
                        Financeiro
                    </h1>


                    <p>
                        Controle de entradas e saídas financeiras.
                    </p>

                </div>



                <div
                    style="
                        display:flex;
                        gap:10px;
                        flex-wrap:wrap;
                    ">


                    <a
                        href="<?= htmlspecialchars($urlRelatorio) ?>"
                        target="_blank"
                        class="btn btn-secondary">

                        <i class="fa-solid fa-file-pdf"></i>

                        Gerar Relatório PDF

                    </a>



                    <a
                        href="<?= BASE_URL ?>/modules/financeiro/criar.php"
                        class="btn btn-primary">

                        <i class="fa-solid fa-plus"></i>

                        Novo lançamento

                    </a>


                </div>


            </div>



            <!--
            |--------------------------------------------------------------------------
            | CARDS
            |--------------------------------------------------------------------------
            -->


            <div class="cards-grid">


                <div class="card dashboard-card">


                    <div class="dashboard-icon green">

                        <i class="fa-solid fa-arrow-trend-up"></i>

                    </div>


                    <div>

                        <span>
                            Entradas
                        </span>


                        <h2>

                            R$

                            <?= number_format(
                                $totalEntradas,
                                2,
                                ',',
                                '.'
                            ) ?>

                        </h2>

                    </div>


                </div>




                <div class="card dashboard-card">


                    <div class="dashboard-icon red">

                        <i class="fa-solid fa-arrow-trend-down"></i>

                    </div>


                    <div>

                        <span>
                            Saídas
                        </span>


                        <h2>

                            R$

                            <?= number_format(
                                $totalSaidas,
                                2,
                                ',',
                                '.'
                            ) ?>

                        </h2>

                    </div>


                </div>




                <div class="card dashboard-card">


                    <div class="dashboard-icon blue">

                        <i class="fa-solid fa-wallet"></i>

                    </div>


                    <div>

                        <span>
                            Saldo
                        </span>


                        <h2>

                            R$

                            <?= number_format(
                                $saldo,
                                2,
                                ',',
                                '.'
                            ) ?>

                        </h2>

                    </div>


                </div>


            </div>



            <!--
            |--------------------------------------------------------------------------
            | FILTROS
            |--------------------------------------------------------------------------
            -->


            <div class="card">


                <div class="card-header">


                    <div>

                        <h2>
                            Filtros financeiros
                        </h2>


                        <span>
                            Consulte os lançamentos por tipo e período.
                        </span>

                    </div>


                </div>



                <form method="GET">


                    <div
                        class="form-grid"
                        style="align-items:end;">



                        <div class="form-group">


                            <label>
                                Tipo
                            </label>


                            <select name="tipo">


                                <option value="">
                                    Todos os lançamentos
                                </option>


                                <option
                                    value="Entrada"
                                    <?= $filtro === 'Entrada' ? 'selected' : '' ?>>

                                    Entradas

                                </option>


                                <option
                                    value="Saída"
                                    <?= $filtro === 'Saída' ? 'selected' : '' ?>>

                                    Saídas

                                </option>


                            </select>


                        </div>



                        <div class="form-group">


                            <label>
                                Data inicial
                            </label>


                            <input
                                type="date"
                                name="data_inicial"
                                value="<?= htmlspecialchars($dataInicial) ?>"
                            >


                        </div>



                        <div class="form-group">


                            <label>
                                Data final
                            </label>


                            <input
                                type="date"
                                name="data_final"
                                value="<?= htmlspecialchars($dataFinal) ?>"
                            >


                        </div>



                        <div class="form-actions">


                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa-solid fa-filter"></i>

                                Filtrar

                            </button>



                            <a
                                href="<?= BASE_URL ?>/modules/financeiro/index.php"
                                class="btn btn-secondary">

                                <i class="fa-solid fa-xmark"></i>

                                Limpar

                            </a>


                        </div>


                    </div>


                </form>


            </div>



            <!--
            |--------------------------------------------------------------------------
            | LANÇAMENTOS
            |--------------------------------------------------------------------------
            -->


            <div class="card">


                <div class="card-header">


                    <div>

                        <h2>
                            Lançamentos financeiros
                        </h2>


                        <span>
                            Histórico de entradas e saídas.
                        </span>

                    </div>


                </div>



                <div class="table-container">


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

                                <th>
                                    Ações
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                            <?php if (empty($lancamentos)): ?>


                                <tr>


                                    <td
                                        colspan="6"
                                        style="text-align:center;">

                                        Nenhum lançamento encontrado.

                                    </td>


                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $lancamentos
                                    as $lancamento
                                ): ?>


                                    <tr>


                                        <td>

                                            <?= date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $lancamento[
                                                        'data_lancamento'
                                                    ]
                                                )
                                            ) ?>

                                        </td>



                                        <td>


                                            <?php if (
                                                $lancamento['tipo']
                                                === 'Entrada'
                                            ): ?>


                                                <span
                                                    class="status status-success">

                                                    Entrada

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="status status-danger">

                                                    Saída

                                                </span>


                                            <?php endif; ?>


                                        </td>



                                        <td>

                                            <?= htmlspecialchars(
                                                $lancamento[
                                                    'categoria'
                                                ] ?? ''
                                            ) ?>

                                        </td>



                                        <td>

                                            <?= htmlspecialchars(
                                                $lancamento[
                                                    'descricao'
                                                ] ?? ''
                                            ) ?>

                                        </td>



                                        <td>


                                            <strong>

                                                R$

                                                <?= number_format(
                                                    $lancamento['valor'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>


                                            </strong>


                                        </td>



                                        <td>


                                            <div
                                                class="table-actions">


                                                <a
                                                    href="<?= BASE_URL ?>/modules/financeiro/editar.php?id=<?= $lancamento['id_lancamento'] ?>"
                                                    class="btn btn-sm btn-secondary"
                                                    title="Editar">

                                                    <i
                                                        class="fa-solid fa-pen">
                                                    </i>

                                                </a>



                                                <a
                                                    href="<?= BASE_URL ?>/modules/financeiro/excluir.php?id=<?= $lancamento['id_lancamento'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    title="Excluir"
                                                    onclick="return confirm('Deseja realmente excluir este lançamento?');">

                                                    <i
                                                        class="fa-solid fa-trash">
                                                    </i>

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