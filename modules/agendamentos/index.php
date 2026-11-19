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
| Apenas Administrador e Barbeiro podem acessar este módulo.
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

$status = $_GET['status'] ?? '';

$data = $_GET['data'] ?? '';


/*
|--------------------------------------------------------------------------
| CONSULTA
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            a.observacao,

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

        WHERE (
            c.nome LIKE :busca
            OR u.nome LIKE :busca
            OR s.nome LIKE :busca
        )";


/*
|--------------------------------------------------------------------------
| FILTRO INDIVIDUAL DO BARBEIRO
|--------------------------------------------------------------------------
|
| Administrador:
|   vê todos.
|
| Barbeiro:
|   vê somente os próprios agendamentos.
|
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
| FILTRO POR STATUS
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= "
        AND a.status = :status
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
| EXECUTAR
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


if ($status !== '') {

    $stmt->bindValue(
        ':status',
        $status
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


            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <h1>
                        <?= $tipoUsuario === 'Barbeiro'
                            ? 'Meus Agendamentos'
                            : 'Agendamentos'
                        ?>
                    </h1>

                    <p>

                        <?=
                        $tipoUsuario === 'Barbeiro'
                            ? 'Visualize e acompanhe seus horários de atendimento.'
                            : 'Gerencie os horários e atendimentos da barbearia.'
                        ?>

                    </p>

                </div>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/criar.php"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-calendar-plus"></i>

                    Novo Agendamento

                </a>

            </div>


            <!-- PESQUISA -->

            <div class="card">

                <form method="GET">

                    <div class="form-group">

                        <label for="busca">

                            Pesquisar agendamento

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


                            <select name="status">

                                <option value="">
                                    Todos os status
                                </option>


                                <option
                                    value="Pendente"
                                    <?= $status === 'Pendente'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Pendente
                                </option>


                                <option
                                    value="Confirmado"
                                    <?= $status === 'Confirmado'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Confirmado
                                </option>


                                <option
                                    value="Em atendimento"
                                    <?= $status === 'Em atendimento'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Em atendimento
                                </option>


                                <option
                                    value="Concluído"
                                    <?= $status === 'Concluído'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Concluído
                                </option>


                                <option
                                    value="Cancelado"
                                    <?= $status === 'Cancelado'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Cancelado
                                </option>

                            </select>


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


            <!-- FILTROS RÁPIDOS -->

            <div class="status-filters">


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                    class="status-filter <?= $status === ''
                        ? 'active'
                        : ''
                    ?>"
                >
                    Todos
                </a>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php?status=Pendente"
                    class="status-filter <?= $status === 'Pendente'
                        ? 'active'
                        : ''
                    ?>"
                >
                    Pendentes
                </a>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php?status=Confirmado"
                    class="status-filter <?= $status === 'Confirmado'
                        ? 'active'
                        : ''
                    ?>"
                >
                    Confirmados
                </a>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php?status=Em%20atendimento"
                    class="status-filter <?= $status === 'Em atendimento'
                        ? 'active'
                        : ''
                    ?>"
                >
                    Em atendimento
                </a>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php?status=Concluído"
                    class="status-filter <?= $status === 'Concluído'
                        ? 'active'
                        : ''
                    ?>"
                >
                    Concluídos
                </a>


                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php?status=Cancelado"
                    class="status-filter <?= $status === 'Cancelado'
                        ? 'active'
                        : ''
                    ?>"
                >
                    Cancelados
                </a>


            </div>


            <!-- TABELA -->

            <div class="card">


                <div class="card-header">

                    <div>

                        <h2>

                            <?=
                            $tipoUsuario === 'Barbeiro'
                                ? 'Meus agendamentos'
                                : 'Agendamentos cadastrados'
                            ?>

                        </h2>


                        <span>

                            <?= count($agendamentos) ?>

                            agendamento(s)

                        </span>

                    </div>

                </div>


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Data</th>

                                <th>Horário</th>

                                <th>Cliente</th>

                                <?php if (
                                    $tipoUsuario === 'Administrador'
                                ): ?>

                                    <th>Barbeiro</th>

                                <?php endif; ?>

                                <th>Serviço</th>

                                <th>Status</th>

                                <th>Ações</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($agendamentos)): ?>


                                <tr>

                                    <td
                                        colspan="<?= $tipoUsuario === 'Administrador'
                                            ? '8'
                                            : '7'
                                        ?>"
                                        class="text-center"
                                    >

                                        <?=
                                        $tipoUsuario === 'Barbeiro'
                                            ? 'Você não possui agendamentos para os filtros selecionados.'
                                            : 'Nenhum agendamento encontrado.'
                                        ?>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $agendamentos
                                    as $agendamento
                                ): ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <?= $agendamento[
                                                'id_agendamento'
                                            ] ?>

                                        </td>


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

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento[
                                                        'hora_inicio'
                                                    ]
                                                )
                                            ) ?>

                                            -

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento[
                                                        'hora_fim'
                                                    ]
                                                )
                                            ) ?>

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


                                        <!-- STATUS -->

                                        <td>


                                            <?php

                                            $classeStatus =
                                                'badge-secondary';


                                            if (
                                                $agendamento['status']
                                                === 'Pendente'
                                            ) {

                                                $classeStatus =
                                                    'badge-warning';

                                            }


                                            if (
                                                $agendamento['status']
                                                === 'Confirmado'
                                            ) {

                                                $classeStatus =
                                                    'badge-success';

                                            }


                                            if (
                                                $agendamento['status']
                                                === 'Em atendimento'
                                            ) {

                                                $classeStatus =
                                                    'badge-primary';

                                            }


                                            if (
                                                $agendamento['status']
                                                === 'Concluído'
                                            ) {

                                                $classeStatus =
                                                    'badge-success';

                                            }


                                            if (
                                                $agendamento['status']
                                                === 'Cancelado'
                                            ) {

                                                $classeStatus =
                                                    'badge-danger';

                                            }

                                            ?>


                                            <span
                                                class="
                                                    badge
                                                    <?= $classeStatus ?>
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $agendamento[
                                                        'status'
                                                    ]
                                                ) ?>

                                            </span>


                                        </td>


                                        <!-- AÇÕES -->

                                        <td>

                                            <div class="table-actions">


                                                <a
                                                    href="<?= BASE_URL ?>/modules/agendamentos/editar.php?id=<?= $agendamento['id_agendamento'] ?>"
                                                    class="btn btn-sm btn-secondary"
                                                    title="Editar"
                                                >

                                                    <i
                                                        class="fa-solid fa-pen"
                                                    ></i>

                                                </a>


                                                <a
                                                    href="<?= BASE_URL ?>/modules/agendamentos/cancelar.php?id=<?= $agendamento['id_agendamento'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    title="Cancelar"
                                                    onclick="return confirm('Deseja realmente cancelar este agendamento?');"
                                                >

                                                    <i
                                                        class="fa-solid fa-ban"
                                                    ></i>

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