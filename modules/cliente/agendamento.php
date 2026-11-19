<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';


/*
|--------------------------------------------------------------------------
| VERIFICAR PERFIL
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['usuario']['tipo']) ||
    $_SESSION['usuario']['tipo'] !== 'Cliente'
) {

    header(
        'Location: ' .
        BASE_URL .
        '/dashboard.php?erro=acesso_negado'
    );

    exit;
}


$id_cliente =
    $_SESSION['usuario']['id_cliente'] ?? null;


if (!$id_cliente) {

    header(
        'Location: ' .
        BASE_URL .
        '/logout.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CONEXÃO
|--------------------------------------------------------------------------
*/

$database = new Database();
$db = $database->conectar();

$erro = '';


/*
|--------------------------------------------------------------------------
| HORÁRIO DE FUNCIONAMENTO
|--------------------------------------------------------------------------
*/

$horaAbertura = '08:00';
$horaFechamento = '20:00';
$intervaloMinutos = 30;


/*
|--------------------------------------------------------------------------
| GERAR HORÁRIOS DE INÍCIO
|--------------------------------------------------------------------------
*/

$horariosDisponiveis = [];

$inicio = strtotime($horaAbertura);
$fim = strtotime($horaFechamento);

while ($inicio < $fim) {

    $horariosDisponiveis[] =
        date('H:i', $inicio);

    $inicio +=
        $intervaloMinutos * 60;
}


/*
|--------------------------------------------------------------------------
| BUSCAR DADOS DO CLIENTE
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_cliente,
            nome
        FROM clientes
        WHERE id_cliente = :id_cliente
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $id_cliente,
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
| BUSCAR BARBEIROS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            b.id_barbeiro,
            u.nome
        FROM barbeiros b

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        WHERE u.status = 'Ativo'

        ORDER BY u.nome ASC";

$stmt = $db->prepare($sql);
$stmt->execute();

$barbeiros = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BUSCAR SERVIÇOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_servico,
            nome,
            preco,
            duracao
        FROM servicos

        WHERE status = 'Ativo'

        ORDER BY nome ASC";

$stmt = $db->prepare($sql);
$stmt->execute();

$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VALORES DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$id_barbeiro =
    $_POST['id_barbeiro'] ?? '';


$id_servicos =
    $_POST['id_servicos'] ?? [];


if (!is_array($id_servicos)) {
    $id_servicos = [$id_servicos];
}


$id_servicos = array_values(
    array_unique(
        array_filter(
            array_map('intval', $id_servicos),
            function ($valor) {
                return $valor > 0;
            }
        )
    )
);


$data_agendamento =
    $_POST['data_agendamento'] ?? '';


$hora_inicio =
    $_POST['hora_inicio'] ?? '';


$observacao =
    trim($_POST['observacao'] ?? '');


$hora_fim = '';

$total_preco = 0;
$total_duracao = 0;


/*
|--------------------------------------------------------------------------
| CALCULAR TOTAL DOS SERVIÇOS
|--------------------------------------------------------------------------
*/

if (!empty($id_servicos)) {

    foreach ($servicos as $servico) {

        if (
            in_array(
                (int) $servico['id_servico'],
                $id_servicos,
                true
            )
        ) {

            $total_preco +=
                (float) $servico['preco'];

            $total_duracao +=
                (int) $servico['duracao'];
        }
    }
}


/*
|--------------------------------------------------------------------------
| CALCULAR HORÁRIO FINAL
|--------------------------------------------------------------------------
*/

if (
    !empty($id_servicos) &&
    $hora_inicio !== '' &&
    $total_duracao > 0
) {

    $inicioTimestamp =
        strtotime($hora_inicio);

    if ($inicioTimestamp !== false) {

        $fimTimestamp =
            $inicioTimestamp +
            ($total_duracao * 60);

        $hora_fim =
            date('H:i', $fimTimestamp);
    }
}


/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES BÁSICAS
    |--------------------------------------------------------------------------
    */

    if (
        !$id_barbeiro ||
        empty($id_servicos) ||
        !$data_agendamento ||
        !$hora_inicio
    ) {

        $erro =
            'Preencha todos os campos obrigatórios.';

    } elseif (
        !in_array(
            $hora_inicio,
            $horariosDisponiveis,
            true
        )
    ) {

        $erro =
            'Selecione um horário disponível.';

    } elseif (
        $hora_fim === ''
    ) {

        $erro =
            'Não foi possível calcular o horário final.';

    } elseif (
        $hora_fim <= $hora_inicio
    ) {

        $erro =
            'A duração dos serviços ultrapassa o horário permitido.';

    } elseif (
        $hora_fim > $horaFechamento
    ) {

        $erro =
            'O horário final dos serviços ultrapassa o horário de funcionamento da barbearia.';

    } elseif (
        $data_agendamento < date('Y-m-d')
    ) {

        $erro =
            'Não é possível realizar um agendamento para uma data passada.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR BARBEIRO
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT
                        b.id_barbeiro

                    FROM barbeiros b

                    INNER JOIN usuarios u
                        ON u.id_usuario = b.id_usuario

                    WHERE b.id_barbeiro = :id_barbeiro
                    AND u.status = 'Ativo'

                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':id_barbeiro',
                $id_barbeiro,
                PDO::PARAM_INT
            );

            $stmt->execute();

            $barbeiroValido =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$barbeiroValido) {

                $erro =
                    'O barbeiro selecionado não está disponível.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | VALIDAR SERVIÇOS NO BANCO
                |--------------------------------------------------------------------------
                */

                $placeholders = [];

                foreach (
                    $id_servicos
                    as $index => $idServico
                ) {

                    $placeholders[] =
                        ':servico_' . $index;
                }


                $sql = "SELECT
                            id_servico,
                            nome,
                            preco,
                            duracao

                        FROM servicos

                        WHERE status = 'Ativo'

                        AND id_servico IN (" .
                        implode(
                            ', ',
                            $placeholders
                        ) .
                        ")

                        ORDER BY nome ASC";


                $stmt =
                    $db->prepare($sql);


                foreach (
                    $id_servicos
                    as $index => $idServico
                ) {

                    $stmt->bindValue(
                        ':servico_' . $index,
                        $idServico,
                        PDO::PARAM_INT
                    );
                }


                $stmt->execute();


                $servicosSelecionados =
                    $stmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                /*
                |--------------------------------------------------------------------------
                | VERIFICAR SE TODOS OS SERVIÇOS SÃO VÁLIDOS
                |--------------------------------------------------------------------------
                */

                if (
                    count($servicosSelecionados) !==
                    count($id_servicos)
                ) {

                    $erro =
                        'Um ou mais serviços selecionados não estão disponíveis.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | RECALCULAR TOTAL NO SERVIDOR
                    |--------------------------------------------------------------------------
                    */

                    $total_preco = 0;
                    $total_duracao = 0;


                    foreach (
                        $servicosSelecionados
                        as $servico
                    ) {

                        $total_preco +=
                            (float) $servico['preco'];

                        $total_duracao +=
                            (int) $servico['duracao'];
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | RECALCULAR HORÁRIO FINAL NO SERVIDOR
                    |--------------------------------------------------------------------------
                    */

                    $inicioTimestamp =
                        strtotime($hora_inicio);


                    $fimTimestamp =
                        $inicioTimestamp +
                        ($total_duracao * 60);


                    $hora_fim =
                        date(
                            'H:i',
                            $fimTimestamp
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | VERIFICAR LIMITE DE FUNCIONAMENTO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $hora_fim > $horaFechamento
                    ) {

                        $erro =
                            'O horário final dos serviços ultrapassa o horário de funcionamento da barbearia.';

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | VERIFICAR CONFLITO
                        |--------------------------------------------------------------------------
                        */

                        $sql = "SELECT
                                    id_agendamento

                                FROM agendamentos

                                WHERE id_barbeiro = :id_barbeiro

                                AND data_agendamento =
                                    :data_agendamento

                                AND status <> 'Cancelado'

                                AND (
                                    :hora_inicio < hora_fim
                                    AND
                                    :hora_fim > hora_inicio
                                )

                                LIMIT 1";


                        $stmt =
                            $db->prepare($sql);


                        $stmt->bindValue(
                            ':id_barbeiro',
                            $id_barbeiro,
                            PDO::PARAM_INT
                        );


                        $stmt->bindValue(
                            ':data_agendamento',
                            $data_agendamento
                        );


                        $stmt->bindValue(
                            ':hora_inicio',
                            $hora_inicio
                        );


                        $stmt->bindValue(
                            ':hora_fim',
                            $hora_fim
                        );


                        $stmt->execute();


                        $conflito =
                            $stmt->fetch(
                                PDO::FETCH_ASSOC
                            );


                        if ($conflito) {

                            $erro =
                                'Este barbeiro já possui um agendamento neste horário.';

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | INICIAR TRANSAÇÃO
                            |--------------------------------------------------------------------------
                            */

                            $db->beginTransaction();


                            /*
                            |--------------------------------------------------------------------------
                            | PRIMEIRO SERVIÇO
                            |
                            | Mantido em agendamentos.id_servico
                            | para compatibilidade com o sistema atual.
                            |--------------------------------------------------------------------------
                            */

                            $id_servico_principal =
                                $id_servicos[0];


                            /*
                            |--------------------------------------------------------------------------
                            | INSERIR AGENDAMENTO
                            |--------------------------------------------------------------------------
                            */

                            $sql = "INSERT INTO agendamentos
                                    (
                                        id_cliente,
                                        id_barbeiro,
                                        id_servico,
                                        data_agendamento,
                                        hora_inicio,
                                        hora_fim,
                                        status,
                                        observacao
                                    )

                                    VALUES
                                    (
                                        :id_cliente,
                                        :id_barbeiro,
                                        :id_servico,
                                        :data_agendamento,
                                        :hora_inicio,
                                        :hora_fim,
                                        'Pendente',
                                        :observacao
                                    )";


                            $stmt =
                                $db->prepare($sql);


                            $stmt->bindValue(
                                ':id_cliente',
                                $id_cliente,
                                PDO::PARAM_INT
                            );


                            $stmt->bindValue(
                                ':id_barbeiro',
                                $id_barbeiro,
                                PDO::PARAM_INT
                            );


                            $stmt->bindValue(
                                ':id_servico',
                                $id_servico_principal,
                                PDO::PARAM_INT
                            );


                            $stmt->bindValue(
                                ':data_agendamento',
                                $data_agendamento
                            );


                            $stmt->bindValue(
                                ':hora_inicio',
                                $hora_inicio
                            );


                            $stmt->bindValue(
                                ':hora_fim',
                                $hora_fim
                            );


                            $stmt->bindValue(
                                ':observacao',
                                $observacao
                            );


                            $stmt->execute();


                            /*
                            |--------------------------------------------------------------------------
                            | ID DO AGENDAMENTO
                            |--------------------------------------------------------------------------
                            */

                            $id_agendamento =
                                $db->lastInsertId();


                            /*
                            |--------------------------------------------------------------------------
                            | INSERIR TODOS OS SERVIÇOS
                            |--------------------------------------------------------------------------
                            */

                            $sql = "INSERT INTO agendamento_servicos
                                    (
                                        id_agendamento,
                                        id_servico,
                                        preco
                                    )

                                    VALUES
                                    (
                                        :id_agendamento,
                                        :id_servico,
                                        :preco
                                    )";


                            $stmtServico =
                                $db->prepare($sql);


                            foreach (
                                $servicosSelecionados
                                as $servico
                            ) {

                                $stmtServico->bindValue(
                                    ':id_agendamento',
                                    $id_agendamento,
                                    PDO::PARAM_INT
                                );


                                $stmtServico->bindValue(
                                    ':id_servico',
                                    $servico['id_servico'],
                                    PDO::PARAM_INT
                                );


                                $stmtServico->bindValue(
                                    ':preco',
                                    $servico['preco']
                                );


                                $stmtServico->execute();
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | CONFIRMAR TRANSAÇÃO
                            |--------------------------------------------------------------------------
                            */

                            $db->commit();


                            /*
                            |--------------------------------------------------------------------------
                            | REDIRECIONAR
                            |--------------------------------------------------------------------------
                            */

                            header(
                                'Location: ' .
                                BASE_URL .
                                '/modules/cliente/index.php?sucesso=agendamento'
                            );

                            exit;
                        }
                    }
                }
            }

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $erro =
                'Ocorreu um erro ao realizar o agendamento.';
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

                    <h1>Novo Agendamento</h1>

                    <p>
                        Escolha o barbeiro, serviços, data e horário.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/cliente/index.php"
                    class="btn btn-secondary"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar

                </a>

            </div>


            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Agende seu atendimento</h2>

                        <span>

                            Olá,
                            <?= htmlspecialchars($cliente['nome']) ?>.

                            Escolha os serviços e o melhor horário
                            para seu atendimento.

                        </span>

                    </div>

                </div>


                <form method="POST">

                    <div class="form-grid">


                        <!-- BARBEIRO -->

                        <div class="form-group">

                            <label for="id_barbeiro">
                                Barbeiro *
                            </label>

                            <select
                                name="id_barbeiro"
                                id="id_barbeiro"
                                required
                            >

                                <option value="">
                                    Selecione o barbeiro
                                </option>


                                <?php foreach (
                                    $barbeiros
                                    as $barbeiro
                                ): ?>

                                    <option
                                        value="<?= (int) $barbeiro['id_barbeiro'] ?>"
                                        <?= $id_barbeiro ==
                                            $barbeiro['id_barbeiro']
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $barbeiro['nome']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- SERVIÇOS -->

                        <div class="form-group form-group-full">

                            <label>
                                Serviços *
                            </label>

                            <small
                                style="
                                    display:block;
                                    margin-top:5px;
                                    margin-bottom:12px;
                                    color:#64748B;
                                "
                            >
                                Você pode selecionar mais de um serviço.
                            </small>


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:
                                        repeat(
                                            auto-fit,
                                            minmax(240px, 1fr)
                                        );
                                    gap:12px;
                                "
                            >

                                <?php foreach (
                                    $servicos
                                    as $servico
                                ): ?>

                                    <label
                                        style="
                                            display:flex;
                                            align-items:flex-start;
                                            gap:12px;
                                            padding:15px;
                                            border:1px solid #E2E8F0;
                                            border-radius:10px;
                                            background:#FFFFFF;
                                            cursor:pointer;
                                            transition:.2s ease;
                                        "
                                    >

                                        <input
                                            type="checkbox"
                                            name="id_servicos[]"
                                            value="<?= (int) $servico['id_servico'] ?>"
                                            data-preco="<?= htmlspecialchars($servico['preco']) ?>"
                                            data-duracao="<?= (int) $servico['duracao'] ?>"
                                            <?= in_array(
                                                (int) $servico['id_servico'],
                                                $id_servicos,
                                                true
                                            )
                                                ? 'checked'
                                                : '' ?>
                                            style="
                                                width:18px;
                                                height:18px;
                                                margin-top:2px;
                                                flex-shrink:0;
                                            "
                                        >

                                        <span>

                                            <strong
                                                style="
                                                    display:block;
                                                    color:#0F172A;
                                                    margin-bottom:5px;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $servico['nome']
                                                ) ?>

                                            </strong>


                                            <small
                                                style="
                                                    color:#64748B;
                                                "
                                            >

                                                R$

                                                <?= number_format(
                                                    $servico['preco'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>

                                                &nbsp; • &nbsp;

                                                <?= (int) $servico['duracao'] ?>

                                                min

                                            </small>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>


                            <!-- RESUMO DOS SERVIÇOS -->

                            <div
                                style="
                                    display:flex;
                                    flex-wrap:wrap;
                                    gap:25px;
                                    margin-top:16px;
                                    padding:15px 16px;
                                    background:#F8FAFC;
                                    border:1px solid #E2E8F0;
                                    border-radius:10px;
                                "
                            >

                                <div>

                                    <small
                                        style="
                                            display:block;
                                            color:#64748B;
                                            margin-bottom:4px;
                                        "
                                    >
                                        Serviços selecionados
                                    </small>

                                    <strong
                                        id="quantidadeServicos"
                                        style="
                                            color:#0F172A;
                                            font-size:18px;
                                        "
                                    >
                                        0
                                    </strong>

                                </div>


                                <div>

                                    <small
                                        style="
                                            display:block;
                                            color:#64748B;
                                            margin-bottom:4px;
                                        "
                                    >
                                        Valor total
                                    </small>

                                    <strong
                                        id="totalPreco"
                                        style="
                                            color:#2563EB;
                                            font-size:18px;
                                        "
                                    >
                                        R$ 0,00
                                    </strong>

                                </div>


                                <div>

                                    <small
                                        style="
                                            display:block;
                                            color:#64748B;
                                            margin-bottom:4px;
                                        "
                                    >
                                        Duração total
                                    </small>

                                    <strong
                                        id="totalDuracao"
                                        style="
                                            color:#0F172A;
                                            font-size:18px;
                                        "
                                    >
                                        0 min
                                    </strong>

                                </div>

                            </div>

                        </div>


                        <!-- DATA -->

                        <div class="form-group">

                            <label for="data_agendamento">
                                Data *
                            </label>

                            <input
                                type="date"
                                name="data_agendamento"
                                id="data_agendamento"
                                value="<?= htmlspecialchars(
                                    $data_agendamento
                                ) ?>"
                                min="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>


                        <!-- HORÁRIO -->

                        <div class="form-group">

                            <label for="hora_inicio">
                                Horário do atendimento *
                            </label>


                            <select
                                name="hora_inicio"
                                id="hora_inicio"
                                required
                            >

                                <option value="">
                                    Selecione o horário
                                </option>


                                <?php foreach (
                                    $horariosDisponiveis
                                    as $horario
                                ): ?>

                                    <option
                                        value="<?= $horario ?>"
                                        <?= $hora_inicio === $horario
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= $horario ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <small
                                id="duracao-info"
                                style="
                                    display:block;
                                    margin-top:7px;
                                    color:#64748B;
                                    font-size:12px;
                                "
                            >
                                Selecione os serviços para visualizar
                                a duração total.
                            </small>

                        </div>


                        <!-- HORÁRIO FINAL -->

                        <div class="form-group">

                            <label for="hora_fim_visual">
                                Horário final
                            </label>


                            <input
                                type="text"
                                id="hora_fim_visual"
                                value="<?= htmlspecialchars(
                                    $hora_fim
                                ) ?>"
                                placeholder="Calculado automaticamente"
                                readonly
                                style="
                                    background:#F8FAFC;
                                "
                            >


                            <small
                                style="
                                    display:block;
                                    margin-top:7px;
                                    color:#64748B;
                                    font-size:12px;
                                "
                            >
                                Calculado automaticamente pela duração
                                dos serviços.
                            </small>

                        </div>


                        <!-- OBSERVAÇÃO -->

                        <div class="form-group form-group-full">

                            <label for="observacao">
                                Observação
                            </label>


                            <textarea
                                name="observacao"
                                id="observacao"
                                rows="4"
                                placeholder="Alguma observação sobre seu atendimento..."
                            ><?= htmlspecialchars(
                                $observacao
                            ) ?></textarea>

                        </div>

                    </div>


                    <!-- RESUMO -->

                    <div
                        id="resumo-agendamento"
                        style="
                            display:none;
                            margin-top:20px;
                            padding:16px;
                            border-radius:10px;
                            background:#F8FAFC;
                            border:1px solid #E2E8F0;
                        "
                    >

                        <strong
                            style="
                                display:block;
                                margin-bottom:6px;
                                color:#1E293B;
                            "
                        >

                            <i class="fa-solid fa-calendar-check"></i>

                            Resumo do agendamento

                        </strong>


                        <span
                            id="resumo-texto"
                            style="
                                color:#64748B;
                                font-size:14px;
                            "
                        ></span>

                    </div>


                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/cliente/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fa-solid fa-calendar-check"></i>

                            Solicitar agendamento

                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const servicos =
            document.querySelectorAll(
                'input[name="id_servicos[]"]'
            );


        const horarioSelect =
            document.getElementById(
                'hora_inicio'
            );


        const horarioFimVisual =
            document.getElementById(
                'hora_fim_visual'
            );


        const duracaoInfo =
            document.getElementById(
                'duracao-info'
            );


        const resumoAgendamento =
            document.getElementById(
                'resumo-agendamento'
            );


        const resumoTexto =
            document.getElementById(
                'resumo-texto'
            );


        const quantidadeServicos =
            document.getElementById(
                'quantidadeServicos'
            );


        const totalPreco =
            document.getElementById(
                'totalPreco'
            );


        const totalDuracao =
            document.getElementById(
                'totalDuracao'
            );


        const horaFechamento =
            '20:00';


        /*
        |--------------------------------------------------------------------------
        | CALCULAR TOTAL
        |--------------------------------------------------------------------------
        */

        function obterTotais() {

            let quantidade = 0;
            let preco = 0;
            let duracao = 0;


            servicos.forEach(
                function (servico) {

                    if (servico.checked) {

                        quantidade++;


                        preco += parseFloat(
                            servico.dataset.preco || 0
                        );


                        duracao += parseInt(
                            servico.dataset.duracao || 0,
                            10
                        );
                    }
                }
            );


            return {
                quantidade: quantidade,
                preco: preco,
                duracao: duracao
            };
        }


        /*
        |--------------------------------------------------------------------------
        | CALCULAR HORÁRIO FINAL
        |--------------------------------------------------------------------------
        */

        function calcularHorarioFinal() {

            const totais =
                obterTotais();


            quantidadeServicos.textContent =
                totais.quantidade;


            totalPreco.textContent =
                totais.preco.toLocaleString(
                    'pt-BR',
                    {
                        style: 'currency',
                        currency: 'BRL'
                    }
                );


            totalDuracao.textContent =
                totais.duracao +
                ' min';


            if (
                totais.quantidade === 0 ||
                !horarioSelect.value
            ) {

                horarioFimVisual.value = '';

                duracaoInfo.textContent =
                    'Selecione os serviços para visualizar a duração total.';

                resumoAgendamento.style.display =
                    'none';

                atualizarHorarios(
                    totais.duracao
                );

                return;
            }


            duracaoInfo.textContent =
                'Duração total: ' +
                totais.duracao +
                ' minutos.';


            const partes =
                horarioSelect.value.split(':');


            const horas =
                parseInt(
                    partes[0],
                    10
                );


            const minutos =
                parseInt(
                    partes[1],
                    10
                );


            const data =
                new Date();


            data.setHours(
                horas,
                minutos,
                0,
                0
            );


            data.setMinutes(
                data.getMinutes() +
                totais.duracao
            );


            const horaFinal =
                String(
                    data.getHours()
                ).padStart(2, '0') +
                ':' +
                String(
                    data.getMinutes()
                ).padStart(2, '0');


            horarioFimVisual.value =
                horaFinal;


            atualizarHorarios(
                totais.duracao
            );


            if (
                horaFinal > horaFechamento
            ) {

                horarioFimVisual.style.borderColor =
                    '#EF4444';

                horarioFimVisual.style.color =
                    '#B91C1C';


                resumoAgendamento.style.display =
                    'block';


                resumoTexto.textContent =
                    'Os serviços selecionados ultrapassam o horário de funcionamento da barbearia.';


                resumoTexto.style.color =
                    '#B91C1C';


                return;
            }


            horarioFimVisual.style.borderColor =
                '';


            horarioFimVisual.style.color =
                '';


            resumoAgendamento.style.display =
                'block';


            resumoTexto.style.color =
                '#64748B';


            resumoTexto.textContent =
                'Seu atendimento está previsto para ' +
                horarioSelect.value +
                ' até ' +
                horaFinal +
                '. Total: ' +
                totais.preco.toLocaleString(
                    'pt-BR',
                    {
                        style: 'currency',
                        currency: 'BRL'
                    }
                ) +
                '.';
        }


        /*
        |--------------------------------------------------------------------------
        | DESABILITAR HORÁRIOS QUE NÃO COMPORTAM OS SERVIÇOS
        |--------------------------------------------------------------------------
        */

        function atualizarHorarios(
            duracao
        ) {

            Array.from(
                horarioSelect.options
            ).forEach(
                function (option) {

                    if (!option.value) {
                        return;
                    }


                    const partes =
                        option.value.split(':');


                    const hora =
                        parseInt(
                            partes[0],
                            10
                        );


                    const minuto =
                        parseInt(
                            partes[1],
                            10
                        );


                    const inicio =
                        new Date();


                    inicio.setHours(
                        hora,
                        minuto,
                        0,
                        0
                    );


                    const fim =
                        new Date(
                            inicio.getTime()
                        );


                    fim.setMinutes(
                        fim.getMinutes() +
                        duracao
                    );


                    const fechamento =
                        new Date();


                    fechamento.setHours(
                        20,
                        0,
                        0,
                        0
                    );


                    option.disabled =
                        duracao > 0 &&
                        fim > fechamento;
                }
            );


            if (
                horarioSelect.selectedOptions[0] &&
                horarioSelect.selectedOptions[0].disabled
            ) {

                horarioSelect.value = '';

                horarioFimVisual.value = '';

                resumoAgendamento.style.display =
                    'none';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EVENTOS
        |--------------------------------------------------------------------------
        */

        servicos.forEach(
            function (servico) {

                servico.addEventListener(
                    'change',
                    calcularHorarioFinal
                );
            }
        );


        horarioSelect.addEventListener(
            'change',
            calcularHorarioFinal
        );


        /*
        |--------------------------------------------------------------------------
        | INICIALIZAR
        |--------------------------------------------------------------------------
        */

        calcularHorarioFinal();

    }
);

</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>