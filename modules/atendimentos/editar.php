<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header(
        'Location: ' .
        BASE_URL .
        '/modules/atendimentos/index.php'
    );
    exit;
}

$erros = [];

/*
|--------------------------------------------------------------------------
| BUSCAR ATENDIMENTO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            at.id_atendimento,
            at.id_agendamento,
            at.valor_pago,
            at.forma_pagamento,
            at.data_atendimento,
            at.observacao,

            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,

            c.nome AS cliente_nome,

            u.nome AS barbeiro_nome,

            s.nome AS servico_nome,
            s.preco AS servico_preco

        FROM atendimentos at

        INNER JOIN agendamentos a
            ON a.id_agendamento = at.id_agendamento

        INNER JOIN clientes c
            ON c.id_cliente = a.id_cliente

        INNER JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        INNER JOIN servicos s
            ON s.id_servico = a.id_servico

        WHERE at.id_atendimento = :id";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id',
    $id,
    PDO::PARAM_INT
);

$stmt->execute();

$atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atendimento) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/atendimentos/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $valor_pago = trim(
        $_POST['valor_pago'] ?? ''
    );

    $forma_pagamento = trim(
        $_POST['forma_pagamento'] ?? ''
    );

    $data_atendimento = trim(
        $_POST['data_atendimento'] ?? ''
    );

    $observacao = trim(
        $_POST['observacao'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO VALOR
    |--------------------------------------------------------------------------
    */

    if ($valor_pago === '') {

        $erros[] = 'Informe o valor pago.';

    } elseif (!is_numeric($valor_pago)) {

        $erros[] = 'O valor pago deve ser numérico.';

    } elseif ((float) $valor_pago < 0) {

        $erros[] = 'O valor pago não pode ser negativo.';
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA FORMA DE PAGAMENTO
    |--------------------------------------------------------------------------
    */

    $formas_validas = [
        'Dinheiro',
        'Pix',
        'Cartão'
    ];

    if ($forma_pagamento === '') {

        $erros[] = 'Selecione a forma de pagamento.';

    } elseif (
        !in_array(
            $forma_pagamento,
            $formas_validas,
            true
        )
    ) {

        $erros[] = 'Forma de pagamento inválida.';
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA DATA
    |--------------------------------------------------------------------------
    */

    if ($data_atendimento === '') {

        $erros[] = 'Informe a data do atendimento.';
    }


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR
    |--------------------------------------------------------------------------
    */

    if (empty($erros)) {

        try {

            $sqlUpdate = "UPDATE atendimentos

                          SET valor_pago = :valor_pago,
                              forma_pagamento = :forma_pagamento,
                              data_atendimento = :data_atendimento,
                              observacao = :observacao

                          WHERE id_atendimento = :id";

            $stmtUpdate = $db->prepare($sqlUpdate);

            $stmtUpdate->bindValue(
                ':valor_pago',
                (float) $valor_pago
            );

            $stmtUpdate->bindValue(
                ':forma_pagamento',
                $forma_pagamento
            );

            $stmtUpdate->bindValue(
                ':data_atendimento',
                $data_atendimento
            );

            $stmtUpdate->bindValue(
                ':observacao',
                $observacao
            );

            $stmtUpdate->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmtUpdate->execute();


            /*
            |--------------------------------------------------------------------------
            | GARANTIR STATUS CONCLUÍDO
            |--------------------------------------------------------------------------
            */

            $sqlStatus = "UPDATE agendamentos

                          SET status = 'Concluído'

                          WHERE id_agendamento = :id_agendamento";

            $stmtStatus = $db->prepare($sqlStatus);

            $stmtStatus->bindValue(
                ':id_agendamento',
                $atendimento['id_agendamento'],
                PDO::PARAM_INT
            );

            $stmtStatus->execute();


            /*
            |--------------------------------------------------------------------------
            | REDIRECIONAR
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                BASE_URL .
                '/modules/atendimentos/index.php?sucesso=2'
            );

            exit;

        } catch (PDOException $e) {

            $erros[] =
                'Não foi possível atualizar o atendimento.';
        }


        /*
        |--------------------------------------------------------------------------
        | MANTER VALORES DIGITADOS
        |--------------------------------------------------------------------------
        */

        $atendimento['valor_pago'] =
            $valor_pago;

        $atendimento['forma_pagamento'] =
            $forma_pagamento;

        $atendimento['data_atendimento'] =
            $data_atendimento;

        $atendimento['observacao'] =
            $observacao;
    }
}

?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>


    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>


        <main class="content">


            <!-- =====================================================
                 CABEÇALHO
            ====================================================== -->

            <div class="page-header">

                <div>

                    <h1>Editar Atendimento</h1>

                    <p>
                        Atualize as informações do atendimento realizado.
                    </p>

                </div>


                <a
                    href="<?= BASE_URL ?>/modules/atendimentos/index.php"
                    class="btn btn-secondary"
                >
                    ← Voltar
                </a>

            </div>


            <!-- =====================================================
                 MENSAGENS DE ERRO
            ====================================================== -->

            <?php if (!empty($erros)): ?>

                <div class="alert alert-danger">

                    <strong>
                        Verifique os seguintes erros:
                    </strong>

                    <ul>

                        <?php foreach ($erros as $erro): ?>

                            <li>
                                <?= htmlspecialchars($erro) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 FORMULÁRIO
            ====================================================== -->

            <div class="card">

                <div class="card-header">

                    <h2>
                        Dados do atendimento
                    </h2>

                </div>


                <form method="POST">


                    <div class="form-grid">


                        <!-- =================================================
                             AGENDAMENTO
                        ================================================== -->

                        <div class="form-group">

                            <label>
                                Agendamento
                            </label>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(
                                    $atendimento['cliente_nome'] .
                                    ' - ' .
                                    $atendimento['servico_nome'] .
                                    ' - ' .
                                    date(
                                        'd/m/Y',
                                        strtotime(
                                            $atendimento['data_agendamento']
                                        )
                                    ) .
                                    ' às ' .
                                    substr(
                                        $atendimento['hora_inicio'],
                                        0,
                                        5
                                    )
                                ) ?>"
                                disabled
                            >

                            <small>
                                O agendamento não pode ser alterado.
                            </small>

                        </div>


                        <!-- =================================================
                             BARBEIRO
                        ================================================== -->

                        <div class="form-group">

                            <label>
                                Barbeiro
                            </label>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(
                                    $atendimento['barbeiro_nome']
                                ) ?>"
                                disabled
                            >

                        </div>


                        <!-- =================================================
                             SERVIÇO
                        ================================================== -->

                        <div class="form-group">

                            <label>
                                Serviço
                            </label>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(
                                    $atendimento['servico_nome']
                                ) ?>"
                                disabled
                            >

                        </div>


                        <!-- =================================================
                             VALOR PAGO
                        ================================================== -->

                        <div class="form-group">

                            <label for="valor_pago">
                                Valor Pago *
                            </label>

                            <input
                                type="number"
                                id="valor_pago"
                                name="valor_pago"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars(
                                    $atendimento['valor_pago']
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- =================================================
                             FORMA DE PAGAMENTO
                        ================================================== -->

                        <div class="form-group">

                            <label for="forma_pagamento">
                                Forma de Pagamento *
                            </label>

                            <select
                                id="forma_pagamento"
                                name="forma_pagamento"
                                required
                            >

                                <option value="">
                                    Selecione
                                </option>


                                <option
                                    value="Dinheiro"
                                    <?= $atendimento['forma_pagamento'] === 'Dinheiro'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Dinheiro
                                </option>


                                <option
                                    value="Pix"
                                    <?= $atendimento['forma_pagamento'] === 'Pix'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pix
                                </option>


                                <option
                                    value="Cartão"
                                    <?= $atendimento['forma_pagamento'] === 'Cartão'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Cartão
                                </option>

                            </select>

                        </div>


                        <!-- =================================================
                             DATA
                        ================================================== -->

                        <div class="form-group">

                            <label for="data_atendimento">
                                Data do Atendimento *
                            </label>

                            <input
                                type="date"
                                id="data_atendimento"
                                name="data_atendimento"
                                value="<?= htmlspecialchars(
                                    $atendimento['data_atendimento']
                                ) ?>"
                                required
                            >

                        </div>


                    </div>


                    <!-- =====================================================
                         OBSERVAÇÃO
                    ====================================================== -->

                    <div class="form-group">

                        <label for="observacao">
                            Observação
                        </label>

                        <textarea
                            id="observacao"
                            name="observacao"
                            rows="4"
                            placeholder="Digite uma observação, se necessário..."
                        ><?= htmlspecialchars(
                            $atendimento['observacao'] ?? ''
                        ) ?></textarea>

                    </div>


                    <!-- =====================================================
                         BOTÕES
                    ====================================================== -->

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/atendimentos/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Salvar Alterações
                        </button>

                    </div>


                </form>

            </div>


        </main>

    </div>

</div>


<?php include __DIR__ . '/../../includes/footer.php'; ?>