<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

/*
|--------------------------------------------------------------------------
| Proteção da área do Cliente
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['tipo'] !== 'Cliente'
) {
    header(
        'Location: ' .
        BASE_URL .
        '/dashboard.php?erro=acesso_negado'
    );
    exit;
}

$database = new Database();
$db = $database->conectar();

$idCliente = $_SESSION['usuario']['id_cliente'] ?? null;

if (!$idCliente) {
    header(
        'Location: ' .
        BASE_URL .
        '/logout.php'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Dados do cliente
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_cliente,
            nome,
            email,
            telefone
        FROM clientes
        WHERE id_cliente = :id_cliente
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header(
        'Location: ' .
        BASE_URL .
        '/logout.php'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Próximos agendamentos
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            s.nome AS servico,
            u.nome AS barbeiro
        FROM agendamentos a

        INNER JOIN servicos s
            ON s.id_servico = a.id_servico

        INNER JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        WHERE a.id_cliente = :id_cliente
        AND a.status <> 'Cancelado'
        AND CONCAT(
            a.data_agendamento,
            ' ',
            a.hora_inicio
        ) >= NOW()

        ORDER BY
            a.data_agendamento ASC,
            a.hora_inicio ASC

        LIMIT 3";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$proximosAgendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Quantidade de agendamentos
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM agendamentos
        WHERE id_cliente = :id_cliente";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$totalAgendamentos = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total de atendimentos
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM atendimentos a

        INNER JOIN agendamentos ag
            ON ag.id_agendamento = a.id_agendamento

        WHERE ag.id_cliente = :id_cliente";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$totalAtendimentos = (int) $stmt->fetchColumn();

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<style>

/*
|--------------------------------------------------------------------------
| Dashboard do Cliente
|--------------------------------------------------------------------------
*/

.cliente-dashboard-cards {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(220px, 1fr)
    );
    gap: 20px;
    margin-bottom: 25px;
}

.cliente-card-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.cliente-card-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 20px;
    flex-shrink: 0;
}

.cliente-card-icon-blue {
    background: #DBEAFE;
    color: #2563EB;
}

.cliente-card-icon-green {
    background: #DCFCE7;
    color: #15803D;
}

.cliente-card-icon-purple {
    background: #F3E8FF;
    color: #7E22CE;
}

.cliente-card-label {
    color: #64748B;
    font-size: 14px;
}

.cliente-card-number {
    display: block;
    font-size: 28px;
    margin-top: 3px;
}

.cliente-card-profile {
    display: block;
    font-size: 16px;
    margin-top: 7px;
}

.cliente-empty {
    padding: 35px 20px;
    text-align: center;
    color: #64748B;
}

.cliente-empty-icon {
    font-size: 40px;
    margin-bottom: 12px;
}

.cliente-empty-text {
    margin-bottom: 18px;
}

.cliente-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.cliente-table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

.cliente-table thead tr {
    border-bottom: 2px solid #E2E8F0;
    text-align: left;
}

.cliente-table th,
.cliente-table td {
    padding: 14px;
    white-space: nowrap;
}

.cliente-table tbody tr {
    border-bottom: 1px solid #E2E8F0;
    cursor: pointer;
    transition: background 0.2s ease;
}

.cliente-table tbody tr:hover {
    background: #F8FAFC;
}

.cliente-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
    white-space: nowrap;
}

.cliente-status-confirmado {
    background: #DCFCE7;
    color: #15803D;
}

.cliente-status-atendimento {
    background: #FEF3C7;
    color: #B45309;
}

.cliente-status-pendente {
    background: #DBEAFE;
    color: #1D4ED8;
}


/*
|--------------------------------------------------------------------------
| Tablet
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {

    .cliente-dashboard-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
    }

}


/*
|--------------------------------------------------------------------------
| Celular
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .cliente-dashboard-cards {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 18px;
    }

    .cliente-card-content {
        gap: 12px;
    }

    .cliente-card-icon {
        width: 46px;
        height: 46px;
        min-width: 46px;
        font-size: 18px;
    }

    .cliente-card-number {
        font-size: 24px;
    }

    /*
    | Cabeçalho da página
    */

    .page-header {
        flex-direction: column;
        align-items: stretch !important;
        gap: 15px;
    }

    .page-header > div {
        min-width: 0;
    }

    .page-header h1 {
        font-size: 24px;
        line-height: 1.25;
        word-break: break-word;
    }

    .page-header p {
        font-size: 14px;
        line-height: 1.5;
    }

    .page-header .btn {
        width: 100%;
        justify-content: center;
    }

    /*
    | Card de próximos agendamentos
    */

    .card-header {
        flex-direction: column;
        align-items: stretch !important;
        gap: 14px;
    }

    .card-header h2 {
        font-size: 19px;
        line-height: 1.3;
    }

    .card-header > div {
        min-width: 0;
    }

    .card-header .btn {
        width: 100%;
        justify-content: center;
    }

    /*
    | Tabela
    |
    | Em vez de quebrar a página horizontalmente,
    | transformamos cada linha em um card.
    */

    .cliente-table-wrapper {
        overflow-x: visible;
    }

    .cliente-table {
        width: 100%;
        min-width: 0;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .cliente-table thead {
        display: none;
    }

    .cliente-table tbody {
        display: block;
        width: 100%;
    }

    .cliente-table tbody tr {
        display: block;
        width: 100%;
        padding: 12px 14px;
        margin-bottom: 12px;

        background: #FFFFFF;

        border: 1px solid #E2E8F0;
        border-radius: 12px;

        box-sizing: border-box;

        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .cliente-table tbody tr:hover {
        background: #FFFFFF;
    }

    .cliente-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;

        width: 100%;
        min-height: 34px;

        padding: 9px 0;

        border-bottom: 1px solid #F1F5F9;

        white-space: normal;
        text-align: right;

        box-sizing: border-box;
    }

    .cliente-table td:last-child {
        border-bottom: none;
    }

    .cliente-table td::before {
        color: #64748B;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .cliente-table td:nth-child(1)::before {
        content: "Data";
    }

    .cliente-table td:nth-child(2)::before {
        content: "Horário";
    }

    .cliente-table td:nth-child(3)::before {
        content: "Serviço";
    }

    .cliente-table td:nth-child(4)::before {
        content: "Barbeiro";
    }

    .cliente-table td:nth-child(5)::before {
        content: "Status";
    }

    .cliente-table td:nth-child(3),
    .cliente-table td:nth-child(4) {
        overflow-wrap: anywhere;
    }

    .cliente-table td:nth-child(3) {
        color: #1E293B;
        font-weight: 600;
    }

    .cliente-table td:nth-child(4) {
        color: #334155;
    }

    .cliente-table td:last-child {
        justify-content: space-between;
    }

    .cliente-status {
        font-size: 12px;
        padding: 5px 9px;
    }

    /*
    | Estado vazio
    */

    .cliente-empty {
        padding: 30px 15px;
    }

    .cliente-empty-icon {
        font-size: 34px;
    }

}


/*
|--------------------------------------------------------------------------
| Celulares pequenos
|--------------------------------------------------------------------------
*/

@media (max-width: 480px) {

    .cliente-card-content {
        padding: 2px;
    }

    .cliente-card-label {
        font-size: 13px;
    }

    .cliente-card-number {
        font-size: 23px;
    }

    .cliente-card-profile {
        font-size: 15px;
    }

    .page-header h1 {
        font-size: 21px;
    }

    .card-header h2 {
        font-size: 18px;
    }

    .cliente-table tbody tr {
        padding: 10px 12px;
        border-radius: 10px;
    }

    .cliente-table td {
        font-size: 13px;
    }

    .cliente-table td::before {
        font-size: 11px;
    }

    .cliente-status {
        font-size: 11px;
    }

}

</style>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <!-- Cabeçalho -->

            <div class="page-header">

                <div>

                    <h1>
                        Olá,
                        <?= htmlspecialchars($cliente['nome']) ?>! 👋
                    </h1>

                    <p>
                        Bem-vindo à sua área do BarberFlow.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/cliente/agendamento.php"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-calendar-plus"></i>
                    Novo agendamento
                </a>

            </div>


            <!-- Cards -->

            <div class="cliente-dashboard-cards">

                <!-- Agendamentos -->

                <div class="card">

                    <div class="cliente-card-content">

                        <div class="
                            cliente-card-icon
                            cliente-card-icon-blue
                        ">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>

                        <div>

                            <span class="cliente-card-label">
                                Agendamentos
                            </span>

                            <strong class="cliente-card-number">
                                <?= $totalAgendamentos ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- Atendimentos -->

                <div class="card">

                    <div class="cliente-card-content">

                        <div class="
                            cliente-card-icon
                            cliente-card-icon-green
                        ">
                            <i class="fa-solid fa-scissors"></i>
                        </div>

                        <div>

                            <span class="cliente-card-label">
                                Atendimentos
                            </span>

                            <strong class="cliente-card-number">
                                <?= $totalAtendimentos ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- Perfil -->

                <div class="card">

                    <div class="cliente-card-content">

                        <div class="
                            cliente-card-icon
                            cliente-card-icon-purple
                        ">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>

                            <span class="cliente-card-label">
                                Meu perfil
                            </span>

                            <strong class="cliente-card-profile">
                                Cliente
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Próximos agendamentos -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>
                            Próximos agendamentos
                        </h2>

                        <span>
                            Seus próximos horários marcados.
                        </span>

                    </div>

                    <a
                        href="<?= BASE_URL ?>/modules/cliente/agendamentos.php"
                        class="btn btn-secondary"
                    >
                        Ver todos
                    </a>

                </div>


                <?php if (empty($proximosAgendamentos)): ?>

                    <div class="cliente-empty">

                        <i class="
                            fa-regular
                            fa-calendar-xmark
                            cliente-empty-icon
                        "></i>

                        <p class="cliente-empty-text">
                            Você ainda não possui próximos
                            agendamentos.
                        </p>

                        <a
                            href="<?= BASE_URL ?>/modules/cliente/agendamento.php"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-calendar-plus"></i>
                            Agendar agora
                        </a>

                    </div>

                <?php else: ?>

                    <div class="cliente-table-wrapper">

                        <table class="cliente-table">

                            <thead>

                                <tr>

                                    <th>
                                        Data
                                    </th>

                                    <th>
                                        Horário
                                    </th>

                                    <th>
                                        Serviço
                                    </th>

                                    <th>
                                        Barbeiro
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $proximosAgendamentos
                                    as $agendamento
                                ): ?>

                                    <?php

                                    $status =
                                        $agendamento['status'];

                                    if (
                                        $status === 'Confirmado'
                                    ) {

                                        $statusClass =
                                            'cliente-status-confirmado';

                                    } elseif (
                                        $status === 'Em atendimento'
                                    ) {

                                        $statusClass =
                                            'cliente-status-atendimento';

                                    } else {

                                        $statusClass =
                                            'cliente-status-pendente';
                                    }

                                    ?>

                                    <tr
                                        onclick="
                                            window.location.href=
                                            '<?= BASE_URL ?>/modules/cliente/agendamentos.php'
                                        "
                                        title="
                                            Clique para ver seus agendamentos
                                        "
                                    >

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

                                        <td>

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento[
                                                        'hora_inicio'
                                                    ]
                                                )
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $agendamento['servico']
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $agendamento['barbeiro']
                                            ) ?>

                                        </td>

                                        <td>

                                            <span class="
                                                cliente-status
                                                <?= $statusClass ?>
                                            ">
                                                <?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>