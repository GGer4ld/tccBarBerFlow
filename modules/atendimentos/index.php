<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

/*
|--------------------------------------------------------------------------
| CONTROLE DE ACESSO
|--------------------------------------------------------------------------
|
| Administrador e Barbeiro podem acessar Atendimentos.
|
*/

if (
    $tipoUsuario !== 'Administrador' &&
    $tipoUsuario !== 'Barbeiro'
) {
    header(
        'Location: ' .
        BASE_URL .
        '/dashboard.php?erro=acesso_negado'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| IDENTIFICAR BARBEIRO LOGADO
|--------------------------------------------------------------------------
*/

$idBarbeiro = null;

if ($tipoUsuario === 'Barbeiro') {

    $idUsuario = (int) (
        $_SESSION['usuario']['id'] ?? 0
    );

    $sql = "SELECT
                b.id_barbeiro

            FROM barbeiros b

            WHERE b.id_usuario = :id_usuario

            LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_usuario',
        $idUsuario,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $barbeiro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$barbeiro) {

        header(
            'Location: ' .
            BASE_URL .
            '/dashboard.php?erro=barbeiro_nao_encontrado'
        );

        exit;
    }

    $idBarbeiro = (int) $barbeiro['id_barbeiro'];
}


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$busca = trim(
    $_GET['busca'] ?? ''
);

$data = $_GET['data'] ?? '';


/*
|--------------------------------------------------------------------------
| BUSCAR AGENDAMENTOS CONFIRMADOS
|--------------------------------------------------------------------------
|
| A tela de Atendimentos apresenta os agendamentos confirmados
| que ainda não possuem um atendimento registrado.
|
*/

$sql = "SELECT
            a.id_agendamento,

            a.data_agendamento,

            a.hora_inicio,

            a.hora_fim,

            a.status,

            c.nome AS cliente_nome,

            u.nome AS barbeiro_nome,

            s.nome AS servico_nome,

            s.preco AS servico_preco

        FROM agendamentos a

        INNER JOIN clientes c
            ON c.id_cliente = a.id_cliente

        INNER JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        INNER JOIN servicos s
            ON s.id_servico = a.id_servico

        LEFT JOIN atendimentos at
            ON at.id_agendamento = a.id_agendamento

        WHERE a.status = 'Confirmado'

        AND at.id_atendimento IS NULL

        AND (
            c.nome LIKE :busca
            OR u.nome LIKE :busca
            OR s.nome LIKE :busca
        )";


/*
|--------------------------------------------------------------------------
| FILTRO INDIVIDUAL DO BARBEIRO
|--------------------------------------------------------------------------
*/

if ($tipoUsuario === 'Barbeiro') {

    $sql .= "
        AND a.id_barbeiro = :id_barbeiro
    ";
}


/*
|--------------------------------------------------------------------------
| FILTRO POR DATA
|--------------------------------------------------------------------------
*/

if ($data !== '') {

    $sql .= "
        AND a.data_agendamento = :data
    ";
}


/*
|--------------------------------------------------------------------------
| ORDENAÇÃO
|--------------------------------------------------------------------------
*/

$sql .= "
        ORDER BY
            a.data_agendamento ASC,
            a.hora_inicio ASC
";


/*
|--------------------------------------------------------------------------
| EXECUTAR CONSULTA
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':busca',
    '%' . $busca . '%'
);


if ($tipoUsuario === 'Barbeiro') {

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );
}


if ($data !== '') {

    $stmt->bindValue(
        ':data',
        $data
    );
}


$stmt->execute();

$agendamentos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>


    <div class="main">


        <?php include __DIR__ . '/../../includes/navbar.php'; ?>


        <main class="content">


            <!-- ==================================================
                 CABEÇALHO
                 ================================================== -->

            <div class="page-header">

                <div>

                    <h1>

                        <?=
                        $tipoUsuario === 'Barbeiro'
                            ? 'Meus Atendimentos'
                            : 'Atendimentos'
                        ?>

                    </h1>

                    <p>

                        <?=
                        $tipoUsuario === 'Barbeiro'
                            ? 'Acompanhe os seus próximos atendimentos.'
                            : 'Gerencie os atendimentos pendentes da barbearia.'
                        ?>

                    </p>

                </div>

            </div>


            <!-- ==================================================
                 MENSAGEM DE SUCESSO
                 ================================================== -->

            <?php if (
                isset($_GET['sucesso']) &&
                $_GET['sucesso'] === '1'
            ): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Atendimento registrado com sucesso!

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 PESQUISA
                 ================================================== -->

            <div class="card">

                <form method="GET">

                    <div class="form-group">

                        <label for="busca">

                            Pesquisar

                        </label>


                        <div class="search-box">


                            <input
                                type="text"
                                id="busca"
                                name="busca"
                                value="<?= htmlspecialchars($busca) ?>"
                                placeholder="Cliente, barbeiro ou serviço..."
                            >


                            <input
                                type="date"
                                id="data"
                                name="data"
                                value="<?= htmlspecialchars($data) ?>"
                                title="Filtrar por data"
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


            <!-- ==================================================
                 LISTA
                 ================================================== -->

            <div class="card">


                <div class="card-header">

                    <div>

                        <h2>

                            <?=
                            $tipoUsuario === 'Barbeiro'
                                ? 'Minha fila de atendimentos'
                                : 'Atendimentos pendentes'
                            ?>

                        </h2>

                        <span>

                            <?= count($agendamentos) ?>

                            atendimento(s) pendente(s)

                        </span>

                    </div>

                </div>


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>

                                <th>Data</th>

                                <th>Horário</th>

                                <th>Cliente</th>


                                <?php if (
                                    $tipoUsuario === 'Administrador'
                                ): ?>

                                    <th>Barbeiro</th>

                                <?php endif; ?>


                                <th>Serviço</th>

                                <th>Valor</th>

                                <th>Status</th>

                                <th>Ação</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                empty($agendamentos)
                            ): ?>


                                <tr>

                                    <td
                                        colspan="<?= $tipoUsuario === 'Administrador'
                                            ? '8'
                                            : '7'
                                        ?>"
                                        class="text-center"
                                    >

                                        <div
                                            style="
                                                padding: 30px;
                                                color: #64748B;
                                            "
                                        >

                                            <i
                                                class="
                                                    fa-solid
                                                    fa-scissors
                                                "
                                                style="
                                                    display:block;
                                                    font-size:30px;
                                                    margin-bottom:10px;
                                                    color:#CBD5E1;
                                                "
                                            ></i>


                                            <?=
                                            $tipoUsuario === 'Barbeiro'
                                                ? 'Você não possui atendimentos pendentes.'
                                                : 'Nenhum atendimento pendente encontrado.'
                                            ?>

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $agendamentos
                                    as $agendamento
                                ): ?>


                                    <tr>


                                        <!-- DATA -->

                                        <td>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $agendamento[
                                                        'data_agendamento'
                                                    ]
                                                )
                                            ) ?>

                                        </td>


                                        <!-- HORÁRIO -->

                                        <td>

                                            <strong>

                                                <?= substr(
                                                    $agendamento[
                                                        'hora_inicio'
                                                    ],
                                                    0,
                                                    5
                                                ) ?>

                                                -

                                                <?= substr(
                                                    $agendamento[
                                                        'hora_fim'
                                                    ],
                                                    0,
                                                    5
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- CLIENTE -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $agendamento[
                                                        'cliente_nome'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- BARBEIRO -->

                                        <?php if (
                                            $tipoUsuario === 'Administrador'
                                        ): ?>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $agendamento[
                                                        'barbeiro_nome'
                                                    ]
                                                ) ?>

                                            </td>

                                        <?php endif; ?>


                                        <!-- SERVIÇO -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $agendamento[
                                                    'servico_nome'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- VALOR -->

                                        <td>

                                            R$

                                            <?= number_format(
                                                (float) $agendamento[
                                                    'servico_preco'
                                                ],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="
                                                    badge
                                                    badge-success
                                                "
                                            >

                                                Confirmado

                                            </span>

                                        </td>


                                        <!-- AÇÃO -->

                                        <td>

                                            <a
                                                href="<?= BASE_URL ?>/modules/atendimentos/criar.php?id_agendamento=<?= $agendamento['id_agendamento'] ?>"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-primary
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-scissors
                                                    "
                                                ></i>

                                                Registrar

                                            </a>

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