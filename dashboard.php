<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/app/config/database.php';

$database = new Database();
$db = $database->conectar();

$isAdministrador = $_SESSION['usuario']['tipo'] === 'Administrador';

/*
|--------------------------------------------------------------------------
| DASHBOARD INDIVIDUAL DO BARBEIRO
|--------------------------------------------------------------------------
*/

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

if ($tipoUsuario === 'Barbeiro') {

    $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | LOCALIZAR O BARBEIRO LOGADO
    |--------------------------------------------------------------------------
    */

$sql = "SELECT
            b.id_barbeiro,
            u.nome AS barbeiro_nome
        FROM barbeiros b
        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario
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
            '/logout.php'
        );

        exit;
    }

    $idBarbeiro = (int) $barbeiro['id_barbeiro'];


    /*
    |--------------------------------------------------------------------------
    | DATA ATUAL
    |--------------------------------------------------------------------------
    */

    $hoje = date('Y-m-d');

    $inicioMes = date('Y-m-01');

    $fimMes = date(
        'Y-m-d',
        strtotime('last day of this month')
    );


    /*
    |--------------------------------------------------------------------------
    | AGENDAMENTOS DE HOJE
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM agendamentos
            WHERE id_barbeiro = :id_barbeiro
            AND data_agendamento = CURDATE()
            AND status <> 'Cancelado'";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $agendamentosHojeBarbeiro =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | AGENDAMENTOS CONFIRMADOS
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM agendamentos
            WHERE id_barbeiro = :id_barbeiro
            AND status = 'Confirmado'
            AND data_agendamento >= CURDATE()";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $agendamentosConfirmados =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ATENDIMENTOS CONCLUÍDOS HOJE
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM atendimentos at
            INNER JOIN agendamentos a
                ON a.id_agendamento = at.id_agendamento
            WHERE a.id_barbeiro = :id_barbeiro
            AND at.data_atendimento = CURDATE()";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $atendimentosHojeBarbeiro =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ATENDIMENTOS CONCLUÍDOS NO MÊS
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM atendimentos at
            INNER JOIN agendamentos a
                ON a.id_agendamento = at.id_agendamento
            WHERE a.id_barbeiro = :id_barbeiro
            AND at.data_atendimento BETWEEN
                :inicio_mes AND :fim_mes";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':inicio_mes',
        $inicioMes
    );

    $stmt->bindValue(
        ':fim_mes',
        $fimMes
    );

    $stmt->execute();

    $atendimentosMesBarbeiro =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | CLIENTES ATENDIDOS NO MÊS
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(DISTINCT a.id_cliente)
            FROM atendimentos at
            INNER JOIN agendamentos a
                ON a.id_agendamento = at.id_agendamento
            WHERE a.id_barbeiro = :id_barbeiro
            AND at.data_atendimento BETWEEN
                :inicio_mes AND :fim_mes";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':inicio_mes',
        $inicioMes
    );

    $stmt->bindValue(
        ':fim_mes',
        $fimMes
    );

    $stmt->execute();

    $clientesMesBarbeiro =
        (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PRÓXIMOS AGENDAMENTOS DO BARBEIRO
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                a.id_agendamento,
                a.data_agendamento,
                a.hora_inicio,
                a.hora_fim,
                a.status,

                c.nome AS cliente,

                GROUP_CONCAT(
                    DISTINCT COALESCE(
                        sm.nome,
                        sb.nome
                    )
                    ORDER BY COALESCE(
                        sm.nome,
                        sb.nome
                    )
                    SEPARATOR ', '
                ) AS servicos

            FROM agendamentos a

            INNER JOIN clientes c
                ON c.id_cliente = a.id_cliente

            LEFT JOIN servicos sb
                ON sb.id_servico = a.id_servico

            LEFT JOIN agendamento_servicos ags
                ON ags.id_agendamento = a.id_agendamento

            LEFT JOIN servicos sm
                ON sm.id_servico = ags.id_servico

            WHERE a.id_barbeiro = :id_barbeiro

            AND a.status <> 'Cancelado'

            AND (
                a.data_agendamento > CURDATE()
                OR (
                    a.data_agendamento = CURDATE()
                    AND a.hora_inicio >= CURTIME()
                )
            )

            GROUP BY
                a.id_agendamento,
                a.data_agendamento,
                a.hora_inicio,
                a.hora_fim,
                a.status,
                c.nome

            ORDER BY
                a.data_agendamento ASC,
                a.hora_inicio ASC

            LIMIT 5";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $proximosAgendamentosBarbeiro =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | ÚLTIMOS ATENDIMENTOS DO BARBEIRO
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                at.id_atendimento,
                at.data_atendimento,
                at.valor_pago,
                at.forma_pagamento,

                c.nome AS cliente,

                GROUP_CONCAT(
                    DISTINCT COALESCE(
                        sm.nome,
                        sb.nome
                    )
                    ORDER BY COALESCE(
                        sm.nome,
                        sb.nome
                    )
                    SEPARATOR ', '
                ) AS servicos

            FROM atendimentos at

            INNER JOIN agendamentos a
                ON a.id_agendamento = at.id_agendamento

            INNER JOIN clientes c
                ON c.id_cliente = a.id_cliente

            LEFT JOIN servicos sb
                ON sb.id_servico = a.id_servico

            LEFT JOIN agendamento_servicos ags
                ON ags.id_agendamento = a.id_agendamento

            LEFT JOIN servicos sm
                ON sm.id_servico = ags.id_servico

            WHERE a.id_barbeiro = :id_barbeiro

            GROUP BY
                at.id_atendimento,
                at.data_atendimento,
                at.valor_pago,
                at.forma_pagamento,
                c.nome

            ORDER BY
                at.data_atendimento DESC,
                at.id_atendimento DESC

            LIMIT 5";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $ultimosAtendimentosBarbeiro =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD DO BARBEIRO
    |--------------------------------------------------------------------------
    */

    include __DIR__ . '/includes/header.php';

    ?>

    <style>

        .barbeiro-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .barbeiro-dashboard-header h1 {
            margin-bottom: 6px;
        }

        .barbeiro-dashboard-header p {
            color: #64748B;
            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------
        | CARDS
        |--------------------------------------------------------------
        */

        .barbeiro-dashboard-cards {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 20px;
            margin-bottom: 25px;
        }

        .barbeiro-dashboard-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px;
        }

        .barbeiro-dashboard-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            font-size: 20px;
        }

        .barbeiro-icon-blue {
            background: #DBEAFE;
            color: #2563EB;
        }

        .barbeiro-icon-green {
            background: #DCFCE7;
            color: #15803D;
        }

        .barbeiro-icon-purple {
            background: #F3E8FF;
            color: #7E22CE;
        }

        .barbeiro-icon-orange {
            background: #FFEDD5;
            color: #C2410C;
        }

        .barbeiro-dashboard-card span {
            display: block;
            color: #64748B;
            font-size: 13px;
        }

        .barbeiro-dashboard-card strong {
            display: block;
            margin-top: 4px;

            color: #0F172A;

            font-size: 27px;
            line-height: 1.2;
        }


        /*
        |--------------------------------------------------------------
        | GRID PRINCIPAL
        |--------------------------------------------------------------
        */

        .barbeiro-dashboard-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(0, 1fr);

            gap: 20px;
        }


        /*
        |--------------------------------------------------------------
        | AGENDAMENTOS
        |--------------------------------------------------------------
        */

        .barbeiro-agenda-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .barbeiro-agenda-item {
            display: grid;

            grid-template-columns:
                70px
                minmax(0, 1fr)
                auto;

            align-items: center;

            gap: 15px;

            padding: 15px;

            border: 1px solid #E2E8F0;
            border-radius: 12px;

            background: #FFFFFF;
        }

        .barbeiro-agenda-hora {
            color: #2563EB;
            font-size: 16px;
            font-weight: 700;
        }

        .barbeiro-agenda-cliente {
            color: #1E293B;
            font-size: 14px;
            font-weight: 700;
        }

        .barbeiro-agenda-servico {
            margin-top: 4px;

            color: #64748B;
            font-size: 12px;

            line-height: 1.4;
        }


        /*
        |--------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------
        */

        .barbeiro-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 700;

            white-space: nowrap;
        }

        .barbeiro-status-confirmado {
            background: #DCFCE7;
            color: #15803D;
        }

        .barbeiro-status-atendimento {
            background: #DBEAFE;
            color: #1D4ED8;
        }

        .barbeiro-status-pendente {
            background: #FEF3C7;
            color: #B45309;
        }

        .barbeiro-status-concluido {
            background: #E0E7FF;
            color: #4338CA;
        }


        /*
        |--------------------------------------------------------------
        | ATENDIMENTOS RECENTES
        |--------------------------------------------------------------
        */

        .barbeiro-atendimento-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .barbeiro-atendimento-item {
            padding: 14px;

            border: 1px solid #E2E8F0;
            border-radius: 12px;

            background: #FFFFFF;
        }

        .barbeiro-atendimento-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .barbeiro-atendimento-cliente {
            color: #1E293B;
            font-size: 14px;
            font-weight: 700;
        }

        .barbeiro-atendimento-data {
            color: #64748B;
            font-size: 12px;
        }

        .barbeiro-atendimento-servico {
            margin-top: 5px;

            color: #64748B;
            font-size: 12px;
        }

        .barbeiro-atendimento-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;

            margin-top: 10px;
            padding-top: 10px;

            border-top: 1px solid #F1F5F9;
        }

        .barbeiro-atendimento-valor {
            color: #15803D;
            font-size: 14px;
            font-weight: 700;
        }

        .barbeiro-atendimento-pagamento {
            color: #64748B;
            font-size: 11px;
        }


        /*
        |--------------------------------------------------------------
        | ESTADO VAZIO
        |--------------------------------------------------------------
        */

        .barbeiro-dashboard-empty {
            padding: 35px 20px;

            text-align: center;

            color: #64748B;
        }

        .barbeiro-dashboard-empty i {
            display: block;

            margin-bottom: 10px;

            color: #CBD5E1;

            font-size: 30px;
        }

        .barbeiro-dashboard-empty span {
            font-size: 13px;
        }


        /*
        |--------------------------------------------------------------
        | RESPONSIVIDADE
        |--------------------------------------------------------------
        */

        @media (max-width: 1100px) {

            .barbeiro-dashboard-cards {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .barbeiro-dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .barbeiro-dashboard-header {
                flex-direction: column;
                align-items: stretch;
            }

            .barbeiro-dashboard-header .btn {
                width: 100%;
                justify-content: center;
            }

            .barbeiro-dashboard-cards {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .barbeiro-agenda-item {
                grid-template-columns: 60px minmax(0, 1fr);
            }

            .barbeiro-agenda-item .barbeiro-status {
                grid-column: 2;
                justify-self: start;
            }

        }


        @media (max-width: 480px) {

            .barbeiro-dashboard-card {
                padding: 18px;
            }

            .barbeiro-dashboard-icon {
                width: 46px;
                height: 46px;
                min-width: 46px;
            }

            .barbeiro-dashboard-card strong {
                font-size: 24px;
            }

            .barbeiro-agenda-item {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .barbeiro-agenda-item .barbeiro-status {
                grid-column: auto;
            }

            .barbeiro-atendimento-top,
            .barbeiro-atendimento-footer {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>


    <div class="app">

        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main">

            <?php include __DIR__ . '/includes/navbar.php'; ?>

            <main class="content">


                <!-- CABEÇALHO -->

                <div class="barbeiro-dashboard-header">

                    <div>

                        <h1>
                            Olá,
                            <?= htmlspecialchars(
                                $barbeiro['barbeiro_nome']
                            ); ?>! 👋
                        </h1>

                        <p>
                            Aqui está um resumo da sua agenda e dos seus atendimentos.
                        </p>

                    </div>

                    <a
                        href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-calendar-days"></i>
                        Ver minha agenda
                    </a>

                </div>


                <!-- CARDS INDIVIDUAIS -->

                <div class="barbeiro-dashboard-cards">


                    <div class="card barbeiro-dashboard-card">

                        <div class="
                            barbeiro-dashboard-icon
                            barbeiro-icon-blue
                        ">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>

                        <div>

                            <span>
                                Minha agenda hoje
                            </span>

                            <strong>
                                <?= $agendamentosHojeBarbeiro ?>
                            </strong>

                        </div>

                    </div>


                    <div class="card barbeiro-dashboard-card">

                        <div class="
                            barbeiro-dashboard-icon
                            barbeiro-icon-green
                        ">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>

                        <div>

                            <span>
                                Confirmados
                            </span>

                            <strong>
                                <?= $agendamentosConfirmados ?>
                            </strong>

                        </div>

                    </div>


                    <div class="card barbeiro-dashboard-card">

                        <div class="
                            barbeiro-dashboard-icon
                            barbeiro-icon-purple
                        ">
                            <i class="fa-solid fa-scissors"></i>
                        </div>

                        <div>

                            <span>
                                Atendimentos hoje
                            </span>

                            <strong>
                                <?= $atendimentosHojeBarbeiro ?>
                            </strong>

                        </div>

                    </div>


                    <div class="card barbeiro-dashboard-card">

                        <div class="
                            barbeiro-dashboard-icon
                            barbeiro-icon-orange
                        ">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>

                            <span>
                                Clientes no mês
                            </span>

                            <strong>
                                <?= $clientesMesBarbeiro ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- CONTEÚDO -->

                <div class="barbeiro-dashboard-grid">


                    <!-- MINHA AGENDA -->

                    <div class="card">

                        <div class="card-header">

                            <div>

                                <h2>
                                    Minha agenda
                                </h2>

                                <span>
                                    Próximos horários reservados para você.
                                </span>

                            </div>

                            <a
                                href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                                class="btn btn-sm btn-secondary"
                            >
                                Ver agenda
                            </a>

                        </div>


                        <?php if (
                            empty($proximosAgendamentosBarbeiro)
                        ): ?>

                            <div class="barbeiro-dashboard-empty">

                                <i class="
                                    fa-regular
                                    fa-calendar-xmark
                                "></i>

                                <span>
                                    Você não possui próximos agendamentos.
                                </span>

                            </div>

                        <?php else: ?>

                            <div class="barbeiro-agenda-list">

                                <?php foreach (
                                    $proximosAgendamentosBarbeiro
                                    as $agendamento
                                ): ?>

                                    <?php

                                    $status =
                                        $agendamento['status'];

                                    $classeStatus =
                                        'barbeiro-status-pendente';

                                    if (
                                        $status === 'Confirmado'
                                    ) {

                                        $classeStatus =
                                            'barbeiro-status-confirmado';

                                    } elseif (
                                        $status === 'Em atendimento'
                                    ) {

                                        $classeStatus =
                                            'barbeiro-status-atendimento';

                                    } elseif (
                                        $status === 'Concluído'
                                    ) {

                                        $classeStatus =
                                            'barbeiro-status-concluido';
                                    }

                                    ?>

                                    <div class="barbeiro-agenda-item">

                                        <div class="barbeiro-agenda-hora">

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento[
                                                        'hora_inicio'
                                                    ]
                                                )
                                            ); ?>

                                        </div>

                                        <div>

                                            <div class="
                                                barbeiro-agenda-cliente
                                            ">
                                                <?= htmlspecialchars(
                                                    $agendamento['cliente']
                                                ); ?>
                                            </div>

                                            <div class="
                                                barbeiro-agenda-servico
                                            ">
                                                <?= htmlspecialchars(
                                                    $agendamento['servicos']
                                                    ?: 'Serviço não informado'
                                                ); ?>
                                            </div>

                                        </div>

                                        <span class="
                                            barbeiro-status
                                            <?= $classeStatus ?>
                                        ">
                                            <?= htmlspecialchars(
                                                $status
                                            ); ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- ÚLTIMOS ATENDIMENTOS -->

                    <div class="card">

                        <div class="card-header">

                            <div>

                                <h2>
                                    Meus atendimentos
                                </h2>

                                <span>
                                    Seus atendimentos mais recentes.
                                </span>

                            </div>

                            <a
                                href="<?= BASE_URL ?>/modules/atendimentos/index.php"
                                class="btn btn-sm btn-secondary"
                            >
                                Ver todos
                            </a>

                        </div>


                        <?php if (
                            empty($ultimosAtendimentosBarbeiro)
                        ): ?>

                            <div class="barbeiro-dashboard-empty">

                                <i class="
                                    fa-solid
                                    fa-scissors
                                "></i>

                                <span>
                                    Você ainda não possui atendimentos registrados.
                                </span>

                            </div>

                        <?php else: ?>

                            <div class="barbeiro-atendimento-list">

                                <?php foreach (
                                    $ultimosAtendimentosBarbeiro
                                    as $atendimento
                                ): ?>

                                    <div class="
                                        barbeiro-atendimento-item
                                    ">

                                        <div class="
                                            barbeiro-atendimento-top
                                        ">

                                            <span class="
                                                barbeiro-atendimento-cliente
                                            ">
                                                <?= htmlspecialchars(
                                                    $atendimento['cliente']
                                                ); ?>
                                            </span>

                                            <span class="
                                                barbeiro-atendimento-data
                                            ">
                                                <?= date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $atendimento[
                                                            'data_atendimento'
                                                        ]
                                                    )
                                                ); ?>
                                            </span>

                                        </div>

                                        <div class="
                                            barbeiro-atendimento-servico
                                        ">
                                            <?= htmlspecialchars(
                                                $atendimento['servicos']
                                                ?: 'Serviço não informado'
                                            ); ?>
                                        </div>

                                        <div class="
                                            barbeiro-atendimento-footer
                                        ">

                                            <span class="
                                                barbeiro-atendimento-valor
                                            ">

                                                R$

                                                <?= number_format(
                                                    (float) $atendimento[
                                                        'valor_pago'
                                                    ],
                                                    2,
                                                    ',',
                                                    '.'
                                                ); ?>

                                            </span>

                                            <span class="
                                                barbeiro-atendimento-pagamento
                                            ">
                                                <?= htmlspecialchars(
                                                    $atendimento[
                                                        'forma_pagamento'
                                                    ]
                                                ); ?>
                                            </span>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- RESUMO DO MÊS -->

                <div
                    class="card"
                    style="margin-top:20px;"
                >

                    <div class="card-header">

                        <div>

                            <h2>
                                Meu resumo do mês
                            </h2>

                            <span>
                                Indicadores baseados exclusivamente nos seus atendimentos.
                            </span>

                        </div>

                    </div>


                    <div class="barbeiro-dashboard-cards">


                        <div class="card barbeiro-dashboard-card">

                            <div class="
                                barbeiro-dashboard-icon
                                barbeiro-icon-purple
                            ">
                                <i class="fa-solid fa-scissors"></i>
                            </div>

                            <div>

                                <span>
                                    Atendimentos realizados
                                </span>

                                <strong>
                                    <?= $atendimentosMesBarbeiro ?>
                                </strong>

                            </div>

                        </div>


                        <div class="card barbeiro-dashboard-card">

                            <div class="
                                barbeiro-dashboard-icon
                                barbeiro-icon-orange
                            ">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <div>

                                <span>
                                    Clientes atendidos
                                </span>

                                <strong>
                                    <?= $clientesMesBarbeiro ?>
                                </strong>

                            </div>

                        </div>


                    </div>

                </div>

            </main>

        </div>

    </div>


    <?php include __DIR__ . '/includes/footer.php'; ?>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| DASHBOARD DO CLIENTE
|--------------------------------------------------------------------------
|
| Cliente possui dashboard próprio.
|
*/

if ($tipoUsuario === 'Cliente') {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/cliente/index.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| INDICADORES
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TOTAL DE CLIENTES
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM clientes";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalClientes = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE BARBEIROS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM barbeiros";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalBarbeiros = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE SERVIÇOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM servicos";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalServicos = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE AGENDAMENTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM agendamentos";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalAgendamentos = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| AGENDAMENTOS DE HOJE
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM agendamentos
        WHERE data_agendamento = CURDATE()
        AND status <> 'Cancelado'";

$stmt = $db->prepare($sql);
$stmt->execute();

$agendamentosHoje = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE PRODUTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM produtos";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalProdutos = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| ESTOQUE BAIXO
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*)
        FROM produtos
        WHERE quantidade <= estoque_minimo
        AND status = 'Ativo'";

$stmt = $db->prepare($sql);
$stmt->execute();

$estoqueBaixo = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE ENTRADAS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COALESCE(SUM(valor), 0)
        FROM lancamentos_financeiros
        WHERE tipo = 'Entrada'";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalEntradas = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL DE SAÍDAS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COALESCE(SUM(valor), 0)
        FROM lancamentos_financeiros
        WHERE tipo = 'Saída'";

$stmt = $db->prepare($sql);
$stmt->execute();

$totalSaidas = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| SALDO
|--------------------------------------------------------------------------
*/

$saldo = $totalEntradas - $totalSaidas;


/*
|--------------------------------------------------------------------------
| MOVIMENTAÇÃO FINANCEIRA - PERÍODO SELECIONADO
|--------------------------------------------------------------------------
*/

$periodoFinanceiro = $_GET['periodo'] ?? 'semanal';

if (!in_array(
    $periodoFinanceiro,
    ['semanal', 'mensal', 'anual'],
    true
)) {
    $periodoFinanceiro = 'semanal';
}

$mesFinanceiro = $_GET['mes'] ?? date('Y-m');
$anoFinanceiro = $_GET['ano'] ?? date('Y');


/*
|--------------------------------------------------------------------------
| VALIDAR MÊS
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^\d{4}-\d{2}$/',
        $mesFinanceiro
    )
) {
    $mesFinanceiro = date('Y-m');
}


/*
|--------------------------------------------------------------------------
| VALIDAR ANO
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^\d{4}$/',
        $anoFinanceiro
    )
) {
    $anoFinanceiro = date('Y');
}


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO DO PERÍODO
|--------------------------------------------------------------------------
*/

$graficoDatas = [];
$graficoEntradas = [];
$graficoSaidas = [];


/*
|--------------------------------------------------------------------------
| PERÍODO SEMANAL
|--------------------------------------------------------------------------
*/

if ($periodoFinanceiro === 'semanal') {

    $dataInicio = date(
        'Y-m-d',
        strtotime('-6 days')
    );

    $dataFim = date('Y-m-d');

    $sql = "SELECT
                DATE(data_lancamento) AS data,
                tipo,
                COALESCE(SUM(valor), 0) AS total
            FROM lancamentos_financeiros
            WHERE DATE(data_lancamento)
                  BETWEEN :data_inicio
                  AND :data_fim
            GROUP BY
                DATE(data_lancamento),
                tipo
            ORDER BY
                DATE(data_lancamento) ASC";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':data_inicio',
        $dataInicio
    );

    $stmt->bindValue(
        ':data_fim',
        $dataFim
    );

    $stmt->execute();

    $movimentacaoFinanceira =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    for ($i = 6; $i >= 0; $i--) {

        $data = date(
            'Y-m-d',
            strtotime("-{$i} days")
        );

        $graficoDatas[] = date(
            'd/m',
            strtotime($data)
        );

        $graficoEntradas[$data] = 0;
        $graficoSaidas[$data] = 0;
    }


    foreach (
        $movimentacaoFinanceira
        as $movimento
    ) {

        $data = $movimento['data'];

        $valor = (float) $movimento['total'];


        if (
            $movimento['tipo'] === 'Entrada' &&
            isset($graficoEntradas[$data])
        ) {

            $graficoEntradas[$data] = $valor;
        }


        if (
            $movimento['tipo'] === 'Saída' &&
            isset($graficoSaidas[$data])
        ) {

            $graficoSaidas[$data] = $valor;
        }
    }
}


/*
|--------------------------------------------------------------------------
| PERÍODO MENSAL
|--------------------------------------------------------------------------
*/

elseif ($periodoFinanceiro === 'mensal') {

    $dataInicio = $mesFinanceiro . '-01';

    $dataFim = date(
        'Y-m-t',
        strtotime($dataInicio)
    );


    $sql = "SELECT
                DATE(data_lancamento) AS data,
                tipo,
                COALESCE(SUM(valor), 0) AS total
            FROM lancamentos_financeiros
            WHERE DATE(data_lancamento)
                  BETWEEN :data_inicio
                  AND :data_fim
            GROUP BY
                DATE(data_lancamento),
                tipo
            ORDER BY
                DATE(data_lancamento) ASC";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':data_inicio',
        $dataInicio
    );

    $stmt->bindValue(
        ':data_fim',
        $dataFim
    );

    $stmt->execute();

    $movimentacaoFinanceira =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    $diasNoMes = (int) date(
        't',
        strtotime($dataInicio)
    );


    for (
        $dia = 1;
        $dia <= $diasNoMes;
        $dia++
    ) {

        $data =
            $mesFinanceiro .
            '-' .
            str_pad(
                $dia,
                2,
                '0',
                STR_PAD_LEFT
            );

        $graficoDatas[] =
            str_pad(
                $dia,
                2,
                '0',
                STR_PAD_LEFT
            );

        $graficoEntradas[$data] = 0;
        $graficoSaidas[$data] = 0;
    }


    foreach (
        $movimentacaoFinanceira
        as $movimento
    ) {

        $data = $movimento['data'];

        $valor = (float) $movimento['total'];


        if (
            $movimento['tipo'] === 'Entrada' &&
            isset($graficoEntradas[$data])
        ) {

            $graficoEntradas[$data] = $valor;
        }


        if (
            $movimento['tipo'] === 'Saída' &&
            isset($graficoSaidas[$data])
        ) {

            $graficoSaidas[$data] = $valor;
        }
    }
}


/*
|--------------------------------------------------------------------------
| PERÍODO ANUAL
|--------------------------------------------------------------------------
*/

else {

    $dataInicio =
        $anoFinanceiro . '-01-01';

    $dataFim =
        $anoFinanceiro . '-12-31';


    $sql = "SELECT
                YEAR(data_lancamento) AS ano,
                MONTH(data_lancamento) AS mes,
                tipo,
                COALESCE(SUM(valor), 0) AS total
            FROM lancamentos_financeiros
            WHERE DATE(data_lancamento)
                  BETWEEN :data_inicio
                  AND :data_fim
            GROUP BY
                YEAR(data_lancamento),
                MONTH(data_lancamento),
                tipo
            ORDER BY
                MONTH(data_lancamento) ASC";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':data_inicio',
        $dataInicio
    );

    $stmt->bindValue(
        ':data_fim',
        $dataFim
    );

    $stmt->execute();

    $movimentacaoFinanceira =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    $nomesMeses = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez'
    ];


    for (
        $mes = 1;
        $mes <= 12;
        $mes++
    ) {

        $graficoDatas[] =
            $nomesMeses[$mes];

        $chave = sprintf(
            '%s-%02d',
            $anoFinanceiro,
            $mes
        );

        $graficoEntradas[$chave] = 0;
        $graficoSaidas[$chave] = 0;
    }


    foreach (
        $movimentacaoFinanceira
        as $movimento
    ) {

        $chave = sprintf(
            '%s-%02d',
            $movimento['ano'],
            $movimento['mes']
        );

        $valor =
            (float) $movimento['total'];


        if (
            $movimento['tipo'] === 'Entrada' &&
            isset($graficoEntradas[$chave])
        ) {

            $graficoEntradas[$chave] = $valor;
        }


        if (
            $movimento['tipo'] === 'Saída' &&
            isset($graficoSaidas[$chave])
        ) {

            $graficoSaidas[$chave] = $valor;
        }
    }
}


/*
|--------------------------------------------------------------------------
| PRÓXIMOS AGENDAMENTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            c.nome AS cliente,
            u.nome AS barbeiro
        FROM agendamentos a
        LEFT JOIN clientes c
            ON c.id_cliente = a.id_cliente
        LEFT JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro
        LEFT JOIN usuarios u
            ON u.id_usuario = b.id_usuario
        WHERE CONCAT(
            a.data_agendamento,
            ' ',
            a.hora_inicio
        ) >= NOW()
        AND a.status <> 'Cancelado'
        ORDER BY
            a.data_agendamento ASC,
            a.hora_inicio ASC
        LIMIT 5";

$stmt = $db->prepare($sql);
$stmt->execute();

$proximosAgendamentos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ÚLTIMOS AGENDAMENTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            c.nome AS cliente,
            u.nome AS barbeiro
        FROM agendamentos a
        LEFT JOIN clientes c
            ON c.id_cliente = a.id_cliente
        LEFT JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro
        LEFT JOIN usuarios u
            ON u.id_usuario = b.id_usuario
        ORDER BY
            a.data_agendamento DESC,
            a.hora_inicio DESC
        LIMIT 5";

$stmt = $db->prepare($sql);
$stmt->execute();

$ultimosAgendamentos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ÚLTIMOS LANÇAMENTOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            tipo,
            categoria,
            valor,
            data_lancamento
        FROM lancamentos_financeiros
        ORDER BY
            data_lancamento DESC
        LIMIT 5";

$stmt = $db->prepare($sql);
$stmt->execute();

$ultimosLancamentos =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| AGENDAMENTOS - ÚLTIMOS 7 DIAS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            DATE(data_agendamento) AS data,
            COUNT(*) AS total
        FROM agendamentos
        WHERE DATE(data_agendamento)
              BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND CURDATE()
        GROUP BY DATE(data_agendamento)
        ORDER BY DATE(data_agendamento) ASC";

$stmt = $db->prepare($sql);
$stmt->execute();

$agendamentosGrafico =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PREPARAR DADOS DO GRÁFICO DE AGENDAMENTOS
|--------------------------------------------------------------------------
*/

$graficoAgendamentoDatas = [];
$graficoAgendamentoValores = [];

for ($i = 6; $i >= 0; $i--) {

    $data = date(
        'Y-m-d',
        strtotime("-{$i} days")
    );

    $graficoAgendamentoDatas[] =
        date(
            'd/m',
            strtotime($data)
        );

    $graficoAgendamentoValores[$data] = 0;
}


/*
|--------------------------------------------------------------------------
| PREENCHER VALORES DOS AGENDAMENTOS
|--------------------------------------------------------------------------
*/

foreach (
    $agendamentosGrafico
    as $agendamento
) {

    $data = $agendamento['data'];

    if (
        isset(
            $graficoAgendamentoValores[$data]
        )
    ) {

        $graficoAgendamentoValores[$data] =
            (int) $agendamento['total'];
    }
}

?>

<?php include 'includes/header.php'; ?>

<div class="app">


<?php include 'includes/sidebar.php'; ?>

<div class="main">

    <?php include 'includes/navbar.php'; ?>

    <main class="content">

        <!--
        |--------------------------------------------------------------------------
        | CABEÇALHO
        |--------------------------------------------------------------------------
        -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Bem-vindo de volta,
                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION['usuario']['nome']
                        ); ?>
                    </strong>.
                </p>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | CARDS PRINCIPAIS
        |--------------------------------------------------------------------------
        -->

        <div class="cards-grid">

            <!-- CLIENTES -->

            <a
                href="<?= BASE_URL ?>/modules/clientes/index.php"
                class="card dashboard-card"
                style="text-decoration: none; color: inherit;"
            >

                <div class="dashboard-icon blue">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>

                    <span>
                        Clientes
                    </span>

                    <h2>
                        <?= $totalClientes ?>
                    </h2>

                </div>

            </a>


            <!-- AGENDAMENTOS -->

            <a
                href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                class="card dashboard-card"
                style="text-decoration: none; color: inherit;"
            >

                <div class="dashboard-icon purple">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>

                <div>

                    <span>
                        Agendamentos
                    </span>

                    <h2>
                        <?= $totalAgendamentos ?>
                    </h2>

                </div>

            </a>


            <!-- AGENDAMENTOS DE HOJE -->

            <a
                href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                class="card dashboard-card"
                style="text-decoration: none; color: inherit;"
            >

                <div class="dashboard-icon orange">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div>

                    <span>
                        Hoje
                    </span>

                    <h2>
                        <?= $agendamentosHoje ?>
                    </h2>

                </div>

            </a>


            <!-- BARBEIROS -->

            <?php if ($isAdministrador): ?>

                <a
                    href="<?= BASE_URL ?>/modules/barbeiros/index.php"
                    class="card dashboard-card"
                    style="text-decoration: none; color: inherit;"
                >

                    <div class="dashboard-icon green">
                        <i class="fa-solid fa-scissors"></i>
                    </div>

                    <div>

                        <span>
                            Barbeiros
                        </span>

                        <h2>
                            <?= $totalBarbeiros ?>
                        </h2>

                    </div>

                </a>

            <?php else: ?>

                <div class="card dashboard-card">

                    <div class="dashboard-icon green">
                        <i class="fa-solid fa-scissors"></i>
                    </div>

                    <div>

                        <span>
                            Barbeiros
                        </span>

                        <h2>
                            <?= $totalBarbeiros ?>
                        </h2>

                    </div>

                </div>

            <?php endif; ?>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | CARDS FINANCEIROS
        |--------------------------------------------------------------------------
        -->

        <?php if ($isAdministrador): ?>

            <div class="cards-grid">

                <!-- ENTRADAS -->

                <a
                    href="<?= BASE_URL ?>/modules/financeiro/index.php"
                    class="card dashboard-card"
                    style="text-decoration: none; color: inherit;"
                >

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

                </a>


                <!-- SAÍDAS -->

                <a
                    href="<?= BASE_URL ?>/modules/financeiro/index.php"
                    class="card dashboard-card"
                    style="text-decoration: none; color: inherit;"
                >

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

                </a>


                <!-- SALDO -->

                <a
                    href="<?= BASE_URL ?>/modules/financeiro/index.php"
                    class="card dashboard-card"
                    style="text-decoration: none; color: inherit;"
                >

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

                </a>


                <!-- ESTOQUE BAIXO -->

                <a
                    href="<?= BASE_URL ?>/modules/estoque/index.php"
                    class="card dashboard-card"
                    style="text-decoration: none; color: inherit;"
                >

                    <div class="dashboard-icon orange">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>

                    <div>

                        <span>
                            Estoque baixo
                        </span>

                        <h2>
                            <?= $estoqueBaixo ?>
                        </h2>

                    </div>

                </a>

            </div>


            <!--
            |--------------------------------------------------------------------------
            | GRÁFICO FINANCEIRO
            |--------------------------------------------------------------------------
            -->

            <div
                class="card"
                style="margin-top:20px;"
            >

                <div
                    class="card-header"
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:20px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h2>
                            Resumo financeiro
                        </h2>

                        <span>

                            <?php if ($periodoFinanceiro === 'semanal'): ?>

                                Comparativo entre entradas e saídas dos últimos 7 dias.

                            <?php elseif ($periodoFinanceiro === 'mensal'): ?>

                                Comparativo diário de entradas e saídas do mês selecionado.

                            <?php else: ?>

                                Comparativo mensal de entradas e saídas do ano selecionado.

                            <?php endif; ?>

                        </span>

                    </div>


                    <form
                        method="GET"
                        style="
                            display:flex;
                            align-items:center;
                            gap:10px;
                            flex-wrap:wrap;
                        "
                    >

                        <select
                            name="periodo"
                            id="periodoFinanceiro"
                            onchange="this.form.submit()"
                            style="
                                height:40px;
                                padding:0 12px;
                                border:1px solid #CBD5E1;
                                border-radius:8px;
                                background:#FFFFFF;
                                color:#334155;
                                font-size:14px;
                                cursor:pointer;
                            "
                        >

                            <option
                                value="semanal"
                                <?= $periodoFinanceiro === 'semanal'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Semanal
                            </option>

                            <option
                                value="mensal"
                                <?= $periodoFinanceiro === 'mensal'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Mensal
                            </option>

                            <option
                                value="anual"
                                <?= $periodoFinanceiro === 'anual'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Anual
                            </option>

                        </select>


                        <?php if ($periodoFinanceiro === 'mensal'): ?>

                            <input
                                type="month"
                                name="mes"
                                value="<?= htmlspecialchars(
                                    $mesFinanceiro
                                ); ?>"
                                onchange="this.form.submit()"
                                style="
                                    height:40px;
                                    padding:0 10px;
                                    border:1px solid #CBD5E1;
                                    border-radius:8px;
                                    background:#FFFFFF;
                                    color:#334155;
                                    font-size:14px;
                                "
                            >

                        <?php endif; ?>


                        <?php if ($periodoFinanceiro === 'anual'): ?>

                            <select
                                name="ano"
                                onchange="this.form.submit()"
                                style="
                                    height:40px;
                                    padding:0 12px;
                                    border:1px solid #CBD5E1;
                                    border-radius:8px;
                                    background:#FFFFFF;
                                    color:#334155;
                                    font-size:14px;
                                    cursor:pointer;
                                "
                            >

                                <?php

                                $anoAtual =
                                    (int) date('Y');

                                for (
                                    $ano = $anoAtual;
                                    $ano >= $anoAtual - 5;
                                    $ano--
                                ):

                                ?>

                                    <option
                                        value="<?= $ano; ?>"
                                        <?= (string) $anoFinanceiro ===
                                            (string) $ano
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        <?= $ano; ?>
                                    </option>

                                <?php endfor; ?>

                            </select>

                        <?php endif; ?>

                    </form>

                </div>


                <div
                    style="
                        position:relative;
                        height:300px;
                        width:100%;
                    "
                >

                    <canvas id="graficoFinanceiro"></canvas>

                </div>

            </div>

        <?php endif; ?>


        <!--
        |--------------------------------------------------------------------------
        | GRÁFICO DE AGENDAMENTOS
        |--------------------------------------------------------------------------
        -->

        <div
            class="card"
            style="margin-top:20px;"
        >

            <div class="card-header">

                <div>

                    <h2>
                        Agendamentos
                    </h2>

                    <span>
                        Agendamentos registrados nos últimos 7 dias.
                    </span>

                </div>

            </div>


            <div
                style="
                    position:relative;
                    height:300px;
                    width:100%;
                "
            >

                <canvas id="graficoAgendamentos"></canvas>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | PRÓXIMOS AGENDAMENTOS
        |--------------------------------------------------------------------------
        -->

        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(400px, 1fr));
                gap:20px;
                margin-top:20px;
            "
        >

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>
                            Próximos agendamentos
                        </h2>

                        <span>
                            Próximos atendimentos agendados.
                        </span>

                    </div>


                    <a
                        href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                        class="btn btn-sm btn-secondary"
                    >
                        Ver todos
                    </a>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Data
                                </th>

                                <th>
                                    Horário
                                </th>

                                <th>
                                    Cliente
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

                            <?php if (empty($proximosAgendamentos)): ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        style="text-align:center;"
                                    >
                                        Nenhum próximo agendamento.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $proximosAgendamentos
                                    as $agendamento
                                ): ?>

                                    <tr
                                        onclick="window.location.href='<?= BASE_URL ?>/modules/agendamentos/editar.php?id=<?= (int) $agendamento['id_agendamento'] ?>'"
                                        style="cursor:pointer;"
                                        title="Clique para editar este agendamento"
                                    >

                                        <td>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $agendamento['data_agendamento']
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento['hora_inicio']
                                                )
                                            ) ?>

    

                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $agendamento['hora_fim']
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $agendamento['cliente']
                                                    ?? 'Não informado'
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $agendamento['barbeiro']
                                                ?? 'Não informado'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?php

                                            $classeStatus =
                                                'status';

                                            if (
                                                $agendamento['status']
                                                === 'Pendente'
                                            ) {

                                                $classeStatus =
                                                    'status status-warning';

                                            } elseif (
                                                $agendamento['status']
                                                === 'Confirmado'
                                            ) {

                                                $classeStatus =
                                                    'status status-success';

                                            } elseif (
                                                $agendamento['status']
                                                === 'Em atendimento'
                                            ) {

                                                $classeStatus =
                                                    'status status-primary';

                                            } elseif (
                                                $agendamento['status']
                                                === 'Concluído'
                                            ) {

                                                $classeStatus =
                                                    'status status-success';

                                            } elseif (
                                                $agendamento['status']
                                                === 'Cancelado'
                                            ) {

                                                $classeStatus =
                                                    'status status-danger';
                                            }

                                            ?>

                                            <span
                                                class="<?= $classeStatus ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $agendamento['status']
                                                ) ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>


</div>

<!--
|--------------------------------------------------------------------------
| CHART.JS
|--------------------------------------------------------------------------
-->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function() {


        /*
        |--------------------------------------------------------------------------
        | DADOS FINANCEIROS
        |--------------------------------------------------------------------------
        */

        const datasFinanceiro =
            <?= json_encode(
                $graficoDatas,
                JSON_UNESCAPED_UNICODE
            ) ?>;


        const entradasFinanceiro =
            <?= json_encode(
                array_values($graficoEntradas),
                JSON_NUMERIC_CHECK
            ) ?>;


        const saidasFinanceiro =
            <?= json_encode(
                array_values($graficoSaidas),
                JSON_NUMERIC_CHECK
            ) ?>;


        /*
        |--------------------------------------------------------------------------
        | DADOS DOS AGENDAMENTOS
        |--------------------------------------------------------------------------
        */

        const datasAgendamentos =
            <?= json_encode(
                $graficoAgendamentoDatas,
                JSON_UNESCAPED_UNICODE
            ) ?>;


        const valoresAgendamentos =
            <?= json_encode(
                array_values($graficoAgendamentoValores),
                JSON_NUMERIC_CHECK
            ) ?>;


        /*
        |--------------------------------------------------------------------------
        | GRÁFICO FINANCEIRO
        |--------------------------------------------------------------------------
        */

        const canvasFinanceiro =
            document.getElementById(
                'graficoFinanceiro'
            );


        if (canvasFinanceiro) {

            new Chart(
                canvasFinanceiro,
                {

                    type: 'line',

                    data: {

                        labels: datasFinanceiro,

                        datasets: [

                            {

                                label: 'Entradas',

                                data:
                                    entradasFinanceiro,

                                borderWidth: 2,

                                tension: 0.35,

                                fill: false

                            },


                            {

                                label: 'Saídas',

                                data:
                                    saidasFinanceiro,

                                borderWidth: 2,

                                tension: 0.35,

                                fill: false

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,


                        interaction: {

                            mode: 'index',

                            intersect: false

                        },


                        plugins: {

                            legend: {

                                display: true

                            },


                            tooltip: {

                                callbacks: {

                                    label:
                                        function(context) {

                                            const valor =
                                                Number(
                                                    context.raw || 0
                                                );

                                            return (
                                                context.dataset.label +
                                                ': R$ ' +
                                                valor.toLocaleString(
                                                    'pt-BR',
                                                    {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }
                                                )
                                            );

                                        }

                                }

                            }

                        },


                        scales: {

                            y: {

                                beginAtZero: true,

                                ticks: {

                                    callback:
                                        function(value) {

                                            return (
                                                'R$ ' +
                                                Number(value)
                                                    .toLocaleString(
                                                        'pt-BR',
                                                        {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2
                                                        }
                                                    )
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | GRÁFICO DE AGENDAMENTOS
        |--------------------------------------------------------------------------
        */

        const canvasAgendamentos =
            document.getElementById(
                'graficoAgendamentos'
            );


        if (canvasAgendamentos) {

            new Chart(
                canvasAgendamentos,
                {

                    type: 'bar',

                    data: {

                        labels:
                            datasAgendamentos,

                        datasets: [

                            {

                                label:
                                    'Agendamentos',

                                data:
                                    valoresAgendamentos,

                                borderWidth: 1

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,


                        plugins: {

                            legend: {

                                display: false

                            }

                        },


                        scales: {

                            y: {

                                beginAtZero: true,

                                ticks: {

                                    precision: 0

                                }

                            }

                        }

                    }

                }
            );

        }

    }

);

</script>

<?php include 'includes/footer.php'; ?>
