<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$erro = '';

/*
|--------------------------------------------------------------------------
| BUSCAR CLIENTES
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_cliente,
            nome
        FROM clientes
        ORDER BY nome ASC";

$stmt = $db->prepare($sql);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);


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

$id_cliente = $_POST['id_cliente'] ?? '';
$id_barbeiro = $_POST['id_barbeiro'] ?? '';

$id_servicos = $_POST['id_servicos'] ?? [];

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

$data_agendamento = $_POST['data_agendamento'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fim = $_POST['hora_fim'] ?? '';
$status = $_POST['status'] ?? 'Pendente';
$observacao = $_POST['observacao'] ?? '';

$total_preco = 0;
$total_duracao = 0;


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
        !$id_cliente ||
        !$id_barbeiro ||
        empty($id_servicos) ||
        !$data_agendamento ||
        !$hora_inicio
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SERVIÇOS SELECIONADOS
            |--------------------------------------------------------------------------
            */

            $placeholders = [];

            foreach ($id_servicos as $index => $idServico) {
                $placeholders[] = ':servico_' . $index;
            }

            $sql = "SELECT
                        id_servico,
                        nome,
                        preco,
                        duracao
                    FROM servicos
                    WHERE status = 'Ativo'
                    AND id_servico IN (" .
                    implode(', ', $placeholders) .
                    ")
                    ORDER BY nome ASC";

            $stmt = $db->prepare($sql);

            foreach ($id_servicos as $index => $idServico) {
                $stmt->bindValue(
                    ':servico_' . $index,
                    $idServico,
                    PDO::PARAM_INT
                );
            }

            $stmt->execute();

            $servicosSelecionados = $stmt->fetchAll(PDO::FETCH_ASSOC);


            /*
            |--------------------------------------------------------------------------
            | GARANTIR QUE TODOS OS SERVIÇOS EXISTEM
            |--------------------------------------------------------------------------
            */

            if (count($servicosSelecionados) !== count($id_servicos)) {

                $erro = 'Um ou mais serviços selecionados são inválidos.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | CALCULAR TOTAL
                |--------------------------------------------------------------------------
                */

                foreach ($servicosSelecionados as $servico) {

                    $total_preco += (float) $servico['preco'];

                    $total_duracao += (int) $servico['duracao'];
                }


                /*
                |--------------------------------------------------------------------------
                | CALCULAR HORÁRIO FINAL
                |--------------------------------------------------------------------------
                */

                $inicioTimestamp = strtotime(
                    $data_agendamento . ' ' . $hora_inicio
                );

                if ($inicioTimestamp === false) {

                    $erro = 'O horário inicial informado é inválido.';

                } else {

                    $fimTimestamp =
                        $inicioTimestamp +
                        ($total_duracao * 60);

                    $hora_fim_calculada = date(
                        'H:i',
                        $fimTimestamp
                    );

                    $hora_fim = $hora_fim_calculada;


                    /*
                    |--------------------------------------------------------------------------
                    | LIMITE DO HORÁRIO DE FUNCIONAMENTO
                    |--------------------------------------------------------------------------
                    */

                    $limiteTimestamp = strtotime(
                        $data_agendamento . ' 20:00'
                    );

                    if ($fimTimestamp > $limiteTimestamp) {

                        $erro =
                            'O horário final do agendamento ultrapassa o horário de funcionamento das 20:00.';

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | VERIFICAR CONFLITO DE HORÁRIO
                        |--------------------------------------------------------------------------
                        */

                        $sql = "SELECT
                                    id_agendamento

                                FROM agendamentos

                                WHERE id_barbeiro = :id_barbeiro

                                AND data_agendamento = :data_agendamento

                                AND status <> 'Cancelado'

                                AND (
                                    :hora_inicio < hora_fim
                                    AND
                                    :hora_fim > hora_inicio
                                )

                                LIMIT 1";

                        $stmt = $db->prepare($sql);

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

                        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);


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
                            | Mantemos o primeiro serviço em agendamentos.id_servico
                            | para manter compatibilidade com o sistema atual.
                            |--------------------------------------------------------------------------
                            */

                            $id_servico_principal = $id_servicos[0];


                            /*
                            |--------------------------------------------------------------------------
                            | INSERIR AGENDAMENTO
                            |--------------------------------------------------------------------------
                            */

                            $sql = "INSERT INTO agendamentos (
                                        id_cliente,
                                        id_barbeiro,
                                        id_servico,
                                        data_agendamento,
                                        hora_inicio,
                                        hora_fim,
                                        status,
                                        observacao
                                    )

                                    VALUES (
                                        :id_cliente,
                                        :id_barbeiro,
                                        :id_servico,
                                        :data_agendamento,
                                        :hora_inicio,
                                        :hora_fim,
                                        :status,
                                        :observacao
                                    )";

                            $stmt = $db->prepare($sql);

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
                                ':status',
                                $status
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

                            $id_agendamento = $db->lastInsertId();


                            /*
                            |--------------------------------------------------------------------------
                            | INSERIR TODOS OS SERVIÇOS
                            |--------------------------------------------------------------------------
                            */

                            $sql = "INSERT INTO agendamento_servicos (
                                        id_agendamento,
                                        id_servico,
                                        preco
                                    )

                                    VALUES (
                                        :id_agendamento,
                                        :id_servico,
                                        :preco
                                    )";

                            $stmtServico = $db->prepare($sql);


                            foreach ($servicosSelecionados as $servico) {

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
                                '/modules/agendamentos/index.php?sucesso=1'
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
                'Ocorreu um erro ao cadastrar o agendamento.';
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
                        Cadastre um novo horário para atendimento.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/agendamentos/index.php"
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

                        <h2>Dados do agendamento</h2>

                        <span>
                            Preencha as informações abaixo.
                        </span>

                    </div>

                </div>


                <form method="POST">

                    <div class="form-grid">


                        <!-- CLIENTE -->

                        <div class="form-group">

                            <label for="id_cliente">
                                Cliente *
                            </label>

                            <select
                                name="id_cliente"
                                id="id_cliente"
                                required
                            >

                                <option value="">
                                    Selecione o cliente
                                </option>

                                <?php foreach ($clientes as $cliente): ?>

                                    <option
                                        value="<?= $cliente['id_cliente'] ?>"
                                        <?= $id_cliente == $cliente['id_cliente'] ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $cliente['nome']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


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

                                <?php foreach ($barbeiros as $barbeiro): ?>

                                    <option
                                        value="<?= $barbeiro['id_barbeiro'] ?>"
                                        <?= $id_barbeiro == $barbeiro['id_barbeiro'] ? 'selected' : '' ?>
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

                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
                                    gap:12px;
                                    margin-top:8px;
                                "
                            >

                                <?php foreach ($servicos as $servico): ?>

                                    <label
                                        style="
                                            display:flex;
                                            align-items:flex-start;
                                            gap:12px;
                                            padding:14px;
                                            border:1px solid #E2E8F0;
                                            border-radius:10px;
                                            background:#FFFFFF;
                                            cursor:pointer;
                                        "
                                    >

                                        <input
                                            type="checkbox"
                                            name="id_servicos[]"
                                            value="<?= $servico['id_servico'] ?>"
                                            data-preco="<?= htmlspecialchars($servico['preco']) ?>"
                                            data-duracao="<?= (int) $servico['duracao'] ?>"
                                            <?= in_array(
                                                (int) $servico['id_servico'],
                                                $id_servicos,
                                                true
                                            ) ? 'checked' : '' ?>
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
                                                    margin-bottom:4px;
                                                "
                                            >
                                                <?= htmlspecialchars(
                                                    $servico['nome']
                                                ) ?>
                                            </strong>

                                            <small
                                                style="
                                                    color:#64748B;
                                                    display:block;
                                                "
                                            >

                                                R$

                                                <?= number_format(
                                                    $servico['preco'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>

                                                &nbsp;•&nbsp;

                                                <?= (int) $servico['duracao'] ?>

                                                min

                                            </small>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <div
                                style="
                                    display:flex;
                                    flex-wrap:wrap;
                                    gap:24px;
                                    margin-top:16px;
                                    padding:14px 16px;
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
                                            margin-bottom:3px;
                                        "
                                    >
                                        Total dos serviços
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
                                            margin-bottom:3px;
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
                                value="<?= htmlspecialchars($data_agendamento) ?>"
                                required
                            >

                        </div>


                        <!-- HORA INÍCIO -->

                        <div class="form-group">

                            <label for="hora_inicio">
                                Horário inicial *
                            </label>

                            <input
                                type="time"
                                name="hora_inicio"
                                id="hora_inicio"
                                value="<?= htmlspecialchars($hora_inicio) ?>"
                                required
                            >

                        </div>


                        <!-- HORA FIM -->

                        <div class="form-group">

                            <label for="hora_fim">
                                Horário final
                            </label>

                            <input
                                type="time"
                                name="hora_fim"
                                id="hora_fim"
                                value="<?= htmlspecialchars($hora_fim) ?>"
                                readonly
                                style="background:#F8FAFC;"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:6px;
                                    color:#64748B;
                                "
                            >
                                Calculado automaticamente pela duração dos serviços.
                            </small>

                        </div>


                        <!-- STATUS -->

                        <div class="form-group">

                            <label for="status">
                                Status
                            </label>

                            <select
                                name="status"
                                id="status"
                            >

                                <option
                                    value="Pendente"
                                    <?= $status === 'Pendente' ? 'selected' : '' ?>
                                >
                                    Pendente
                                </option>

                                <option
                                    value="Confirmado"
                                    <?= $status === 'Confirmado' ? 'selected' : '' ?>
                                >
                                    Confirmado
                                </option>

                                <option
                                    value="Em atendimento"
                                    <?= $status === 'Em atendimento' ? 'selected' : '' ?>
                                >
                                    Em atendimento
                                </option>

                                <option
                                    value="Concluído"
                                    <?= $status === 'Concluído' ? 'selected' : '' ?>
                                >
                                    Concluído
                                </option>

                                <option
                                    value="Cancelado"
                                    <?= $status === 'Cancelado' ? 'selected' : '' ?>
                                >
                                    Cancelado
                                </option>

                            </select>

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
                                placeholder="Observações sobre o atendimento..."
                            ><?= htmlspecialchars($observacao) ?></textarea>

                        </div>

                    </div>


                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fa-solid fa-calendar-check"></i>

                            Salvar Agendamento

                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const servicos = document.querySelectorAll(
        'input[name="id_servicos[]"]'
    );

    const horaInicio = document.getElementById(
        'hora_inicio'
    );

    const horaFim = document.getElementById(
        'hora_fim'
    );

    const totalPreco = document.getElementById(
        'totalPreco'
    );

    const totalDuracao = document.getElementById(
        'totalDuracao'
    );


    function calcularAgendamento() {

        let preco = 0;
        let duracao = 0;

        servicos.forEach(function (servico) {

            if (servico.checked) {

                preco += parseFloat(
                    servico.dataset.preco || 0
                );

                duracao += parseInt(
                    servico.dataset.duracao || 0
                );
            }
        });


        totalPreco.textContent =
            preco.toLocaleString(
                'pt-BR',
                {
                    style: 'currency',
                    currency: 'BRL'
                }
            );


        totalDuracao.textContent =
            duracao + ' min';


        calcularHoraFim(duracao);
    }


    function calcularHoraFim(duracao) {

        if (
            !horaInicio.value ||
            duracao <= 0
        ) {

            horaFim.value = '';

            return;
        }


        const partes =
            horaInicio.value.split(':');


        const horas =
            parseInt(partes[0], 10);

        const minutos =
            parseInt(partes[1], 10);


        const data =
            new Date();

        data.setHours(
            horas,
            minutos,
            0,
            0
        );


        data.setMinutes(
            data.getMinutes() + duracao
        );


        const horaFinal =
            String(
                data.getHours()
            ).padStart(2, '0');


        const minutoFinal =
            String(
                data.getMinutes()
            ).padStart(2, '0');


        horaFim.value =
            horaFinal +
            ':' +
            minutoFinal;
    }


    servicos.forEach(function (servico) {

        servico.addEventListener(
            'change',
            calcularAgendamento
        );

    });


    horaInicio.addEventListener(
        'change',
        calcularAgendamento
    );


    calcularAgendamento();

});

</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>