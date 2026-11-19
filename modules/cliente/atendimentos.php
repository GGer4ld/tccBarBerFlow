<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

if (($_SESSION['usuario']['tipo'] ?? '') !== 'Cliente') {
    header(
        'Location: ' .
        BASE_URL .
        '/dashboard.php?erro=acesso_negado'
    );
    exit();
}

$idCliente = $_SESSION['usuario']['id_cliente'] ?? null;

if (!$idCliente) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit();
}

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| BUSCA OS ATENDIMENTOS DO CLIENTE LOGADO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            at.id_atendimento,
            at.valor_pago,
            at.forma_pagamento,
            at.data_atendimento,
            at.observacao,

            ag.data_agendamento,
            ag.hora_inicio,
            ag.hora_fim,

            s.nome AS servico,

            u.nome AS barbeiro

        FROM atendimentos at

        INNER JOIN agendamentos ag
            ON ag.id_agendamento = at.id_agendamento

        INNER JOIN servicos s
            ON s.id_servico = ag.id_servico

        INNER JOIN barbeiros b
            ON b.id_barbeiro = ag.id_barbeiro

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        WHERE ag.id_cliente = :id_cliente

        ORDER BY
            at.data_atendimento DESC,
            ag.hora_inicio DESC";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <div class="page-header">

                <div>

                    <h1>Meus atendimentos</h1>

                    <p>
                        Consulte o histórico dos serviços realizados.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/cliente/agendamento.php"
                    class="btn btn-primary">

                    <i class="fa-solid fa-calendar-plus"></i>

                    Novo agendamento

                </a>

            </div>


            <div class="card">

                <?php if (empty($atendimentos)): ?>

                    <div class="cliente-empty">

                        <div class="cliente-empty-icon">

                            <i class="fa-solid fa-scissors"></i>

                        </div>

                        <div class="cliente-empty-text">

                            <h3>Nenhum atendimento encontrado</h3>

                            <p>
                                Você ainda não possui atendimentos registrados.
                            </p>

                        </div>

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
                                        Valor
                                    </th>

                                    <th>
                                        Pagamento
                                    </th>

                                    <th>
                                        Observação
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($atendimentos as $atendimento): ?>

                                    <tr>

                                        <td>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $atendimento['data_atendimento']
                                                )
                                            ); ?>

                                        </td>

                                        <td>

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $atendimento['hora_inicio']
                                                )
                                            ); ?>

                                            -

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $atendimento['hora_fim']
                                                )
                                            ); ?>

                                        </td>

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $atendimento['servico']
                                                ); ?>
                                            </strong>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $atendimento['barbeiro']
                                            ); ?>

                                        </td>

                                        <td>

                                            <strong>
                                                R$
                                                <?= number_format(
                                                    (float) $atendimento['valor_pago'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </strong>

                                        </td>

                                        <td>

                                            <span class="cliente-payment">

                                                <i class="fa-solid fa-credit-card"></i>

                                                <?= htmlspecialchars(
                                                    $atendimento['forma_pagamento']
                                                ); ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $atendimento['observacao']
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    $atendimento['observacao']
                                                ); ?>

                                            <?php else: ?>

                                                <span class="cliente-sem-observacao">
                                                    —
                                                </span>

                                            <?php endif; ?>

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


<style>

    .cliente-empty {

        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;

        text-align: center;

        padding: 60px 20px;

    }


    .cliente-empty-icon {

        width: 70px;
        height: 70px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #F3E8FF;
        color: #7E22CE;

        font-size: 28px;

        margin-bottom: 20px;

    }


    .cliente-empty-text h3 {

        margin: 0 0 8px;

        font-size: 18px;
        color: #1E293B;

    }


    .cliente-empty-text p {

        margin: 0;

        color: #64748B;

        font-size: 14px;

    }


    .cliente-table-wrapper {

        width: 100%;

        overflow-x: auto;

    }


    .cliente-table {

        width: 100%;

        border-collapse: collapse;

        min-width: 900px;

    }


    .cliente-table th {

        text-align: left;

        padding: 14px 16px;

        background: #F8FAFC;

        color: #475569;

        font-size: 13px;

        font-weight: 600;

        border-bottom: 1px solid #E2E8F0;

    }


    .cliente-table td {

        padding: 16px;

        border-bottom: 1px solid #E2E8F0;

        color: #475569;

        font-size: 14px;

        vertical-align: middle;

    }


    .cliente-table tbody tr:hover {

        background: #F8FAFC;

    }


    .cliente-table td strong {

        color: #1E293B;

    }


    .cliente-payment {

        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 6px 10px;

        border-radius: 8px;

        background: #EFF6FF;

        color: #2563EB;

        font-size: 13px;

        white-space: nowrap;

    }


    .cliente-sem-observacao {

        color: #94A3B8;

    }


    @media (max-width: 768px) {

        .page-header {

            flex-direction: column;

            align-items: flex-start;

            gap: 15px;

        }

        .page-header .btn {

            width: 100%;

            justify-content: center;

        }

    }

</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>