<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$erro = '';

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

/*
|--------------------------------------------------------------------------
| CONTROLE DE ACESSO
|--------------------------------------------------------------------------
|
| Apenas Administrador e Barbeiro podem registrar atendimentos.
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
| VALORES PADRÃO
|--------------------------------------------------------------------------
*/

$id_agendamento = $_GET['id_agendamento'] ?? '';
$valor_pago = '';
$forma_pagamento = '';
$data_atendimento = date('Y-m-d');
$observacao = '';

/*
|--------------------------------------------------------------------------
| IDENTIFICAR BARBEIRO LOGADO
|--------------------------------------------------------------------------
|
| Administrador:
|   $idBarbeiro = null
|   Pode trabalhar com todos os barbeiros.
|
| Barbeiro:
|   $idBarbeiro recebe o barbeiro vinculado ao usuário logado.
|
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
| BUSCAR AGENDAMENTOS DISPONÍVEIS
|--------------------------------------------------------------------------
|
| Mostra somente:
|
| - Agendamentos Confirmados
| - Que ainda não possuem atendimento
|
| Administrador:
|   todos os barbeiros.
|
| Barbeiro:
|   somente seus próprios agendamentos.
|
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,

            c.nome AS cliente,

            u.nome AS barbeiro,

            s.nome AS servico,

            s.preco

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

        AND at.id_atendimento IS NULL";


/*
|--------------------------------------------------------------------------
| FILTRO DO BARBEIRO
|--------------------------------------------------------------------------
*/

if ($tipoUsuario === 'Barbeiro') {

    $sql .= "
        AND a.id_barbeiro = :id_barbeiro
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


$stmt = $db->prepare($sql);


/*
|--------------------------------------------------------------------------
| BIND DO BARBEIRO
|--------------------------------------------------------------------------
*/

if ($tipoUsuario === 'Barbeiro') {

    $stmt->bindValue(
        ':id_barbeiro',
        $idBarbeiro,
        PDO::PARAM_INT
    );
}


$stmt->execute();

$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SE VEIO DE UM AGENDAMENTO CONFIRMADO
|--------------------------------------------------------------------------
|
| Quando o usuário clica em "Registrar atendimento",
| o agendamento fica selecionado.
|
| A consulta também verifica o barbeiro.
|
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' &&
    $id_agendamento !== ''
) {

    $sql = "SELECT
                a.id_agendamento,
                a.data_agendamento,
                a.status,

                c.nome AS cliente,

                u.nome AS barbeiro,

                s.nome AS servico,

                s.preco

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

            WHERE a.id_agendamento = :id_agendamento

            AND a.status = 'Confirmado'

            AND at.id_atendimento IS NULL";


    /*
    |--------------------------------------------------------------------------
    | PROTEGER ACESSO DIRETO DO BARBEIRO
    |--------------------------------------------------------------------------
    */

    if ($tipoUsuario === 'Barbeiro') {

        $sql .= "
            AND a.id_barbeiro = :id_barbeiro
        ";
    }


    $sql .= "
            LIMIT 1
    ";


    $stmt = $db->prepare($sql);


    $stmt->bindValue(
        ':id_agendamento',
        $id_agendamento,
        PDO::PARAM_INT
    );


    if ($tipoUsuario === 'Barbeiro') {

        $stmt->bindValue(
            ':id_barbeiro',
            $idBarbeiro,
            PDO::PARAM_INT
        );
    }


    $stmt->execute();

    $agendamentoSelecionado =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if ($agendamentoSelecionado) {

        $valor_pago =
            $agendamentoSelecionado['preco'];

        $data_atendimento =
            $agendamentoSelecionado['data_agendamento'];

    } else {

        $id_agendamento = '';

        if ($tipoUsuario === 'Barbeiro') {

            $erro =
                'O agendamento selecionado não pertence a você, não está confirmado, já possui atendimento ou não existe.';

        } else {

            $erro =
                'O agendamento selecionado não está confirmado, já possui atendimento ou não existe.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_agendamento =
        $_POST['id_agendamento'] ?? '';

    $valor_pago =
        $_POST['valor_pago'] ?? '';

    $forma_pagamento =
        $_POST['forma_pagamento'] ?? '';

    $data_atendimento =
        $_POST['data_atendimento'] ?? '';

    $observacao =
        $_POST['observacao'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if (
        !$id_agendamento ||
        !$valor_pago ||
        !$forma_pagamento ||
        !$data_atendimento
    ) {

        $erro =
            'Preencha todos os campos obrigatórios.';

    } elseif (!is_numeric($valor_pago)) {

        $erro =
            'Informe um valor válido.';

    } elseif ($valor_pago <= 0) {

        $erro =
            'O valor pago deve ser maior que zero.';

    } elseif (
        !in_array(
            $forma_pagamento,
            ['Dinheiro', 'Pix', 'Cartão'],
            true
        )
    ) {

        $erro =
            'Selecione uma forma de pagamento válida.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR AGENDAMENTO
            |--------------------------------------------------------------------------
            |
            | O atendimento só pode ser criado para:
            |
            | - agendamento existente;
            | - status Confirmado;
            | - barbeiro correto;
            | - sem atendimento anterior.
            |
            */

            $sql = "SELECT
                        a.id_agendamento,
                        a.status,
                        a.data_agendamento,

                        c.nome AS cliente,

                        u.nome AS barbeiro,

                        s.nome AS servico,

                        s.preco

                    FROM agendamentos a

                    INNER JOIN clientes c
                        ON c.id_cliente = a.id_cliente

                    INNER JOIN barbeiros b
                        ON b.id_barbeiro = a.id_barbeiro

                    INNER JOIN usuarios u
                        ON u.id_usuario = b.id_usuario

                    INNER JOIN servicos s
                        ON s.id_servico = a.id_servico

                    WHERE a.id_agendamento = :id_agendamento

                    AND a.status = 'Confirmado'";


            /*
            |--------------------------------------------------------------------------
            | PROTEGER POST DO BARBEIRO
            |--------------------------------------------------------------------------
            */

            if ($tipoUsuario === 'Barbeiro') {

                $sql .= "
                    AND a.id_barbeiro = :id_barbeiro
                ";
            }


            $sql .= "
                    LIMIT 1
            ";


            $stmt = $db->prepare($sql);


            $stmt->bindValue(
                ':id_agendamento',
                $id_agendamento,
                PDO::PARAM_INT
            );


            if ($tipoUsuario === 'Barbeiro') {

                $stmt->bindValue(
                    ':id_barbeiro',
                    $idBarbeiro,
                    PDO::PARAM_INT
                );
            }


            $stmt->execute();

            $agendamento =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$agendamento) {

                if ($tipoUsuario === 'Barbeiro') {

                    $erro =
                        'Este agendamento não pertence a você, não está confirmado ou não existe.';

                } else {

                    $erro =
                        'O agendamento precisa estar confirmado para ser registrado como atendimento.';
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR SE JÁ EXISTE ATENDIMENTO
                |--------------------------------------------------------------------------
                */

                $sql = "SELECT
                            id_atendimento

                        FROM atendimentos

                        WHERE id_agendamento = :id_agendamento

                        LIMIT 1";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(
                    ':id_agendamento',
                    $id_agendamento,
                    PDO::PARAM_INT
                );

                $stmt->execute();

                $atendimento_existente =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if ($atendimento_existente) {

                    $erro =
                        'Este agendamento já possui um atendimento registrado.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | INICIAR TRANSAÇÃO
                    |--------------------------------------------------------------------------
                    */

                    $db->beginTransaction();


                    /*
                    |--------------------------------------------------------------------------
                    | CRIAR ATENDIMENTO
                    |--------------------------------------------------------------------------
                    */

                    $sql = "INSERT INTO atendimentos
                            (
                                id_agendamento,
                                valor_pago,
                                forma_pagamento,
                                data_atendimento,
                                observacao
                            )

                            VALUES
                            (
                                :id_agendamento,
                                :valor_pago,
                                :forma_pagamento,
                                :data_atendimento,
                                :observacao
                            )";

                    $stmt = $db->prepare($sql);


                    $stmt->bindValue(
                        ':id_agendamento',
                        $id_agendamento,
                        PDO::PARAM_INT
                    );


                    $stmt->bindValue(
                        ':valor_pago',
                        $valor_pago
                    );


                    $stmt->bindValue(
                        ':forma_pagamento',
                        $forma_pagamento
                    );


                    $stmt->bindValue(
                        ':data_atendimento',
                        $data_atendimento
                    );


                    $stmt->bindValue(
                        ':observacao',
                        $observacao
                    );


                    $stmt->execute();


                    /*
                    |--------------------------------------------------------------------------
                    | PEGAR ID DO ATENDIMENTO
                    |--------------------------------------------------------------------------
                    */

                    $id_atendimento =
                        $db->lastInsertId();


                    /*
                    |--------------------------------------------------------------------------
                    | CRIAR LANÇAMENTO FINANCEIRO AUTOMÁTICO
                    |--------------------------------------------------------------------------
                    */

                    $descricaoFinanceira =
                        'Atendimento de ' .
                        $agendamento['cliente'] .
                        ' - ' .
                        $agendamento['servico'] .
                        ' - ' .
                        $forma_pagamento;


                    $sql = "INSERT INTO lancamentos_financeiros
                            (
                                id_atendimento,
                                tipo,
                                categoria,
                                descricao,
                                valor
                            )

                            VALUES
                            (
                                :id_atendimento,
                                'Entrada',
                                'Atendimento',
                                :descricao,
                                :valor
                            )";

                    $stmt = $db->prepare($sql);


                    $stmt->bindValue(
                        ':id_atendimento',
                        $id_atendimento,
                        PDO::PARAM_INT
                    );


                    $stmt->bindValue(
                        ':descricao',
                        $descricaoFinanceira
                    );


                    $stmt->bindValue(
                        ':valor',
                        $valor_pago
                    );


                    $stmt->execute();


                    /*
                    |--------------------------------------------------------------------------
                    | ATUALIZAR STATUS DO AGENDAMENTO
                    |--------------------------------------------------------------------------
                    */

                    $sql = "UPDATE agendamentos

                            SET status = 'Concluído'

                            WHERE id_agendamento = :id_agendamento";

                    $stmt = $db->prepare($sql);


                    $stmt->bindValue(
                        ':id_agendamento',
                        $id_agendamento,
                        PDO::PARAM_INT
                    );


                    $stmt->execute();


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
                        '/modules/atendimentos/index.php?sucesso=1'
                    );

                    exit;
                }
            }

        } catch (PDOException $e) {

            if ($db->inTransaction()) {

                $db->rollBack();
            }

            $erro =
                'Ocorreu um erro ao registrar o atendimento.';
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

                    <h1>

                        <?= $tipoUsuario === 'Barbeiro'
                            ? 'Registrar Atendimento'
                            : 'Novo Atendimento'
                        ?>

                    </h1>

                    <p>

                        <?= $tipoUsuario === 'Barbeiro'
                            ? 'Registre um atendimento realizado por você.'
                            : 'Registre um atendimento realizado.'
                        ?>

                    </p>

                </div>


                <a
                    href="<?= BASE_URL ?>/modules/atendimentos/index.php"
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

                        <h2>
                            Dados do atendimento
                        </h2>

                        <span>

                            Preencha as informações abaixo.

                        </span>

                    </div>

                </div>


                <form method="POST">


                    <div class="form-grid">


                        <!-- AGENDAMENTO -->

                        <div class="form-group form-group-full">

                            <label for="id_agendamento">

                                Agendamento *

                            </label>


                            <select
                                name="id_agendamento"
                                id="id_agendamento"
                                required
                            >

                                <option value="">

                                    Selecione o agendamento

                                </option>


                                <?php foreach (
                                    $agendamentos
                                    as $agendamento
                                ): ?>


                                    <option
                                        value="<?= $agendamento['id_agendamento'] ?>"
                                        <?= $id_agendamento == $agendamento['id_agendamento']
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $agendamento['cliente']
                                        ) ?>

                                        -

                                        <?= htmlspecialchars(
                                            $agendamento['servico']
                                        ) ?>

                                        |

                                        Barbeiro:

                                        <?= htmlspecialchars(
                                            $agendamento['barbeiro']
                                        ) ?>

                                        |

                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $agendamento[
                                                    'data_agendamento'
                                                ]
                                            )
                                        ) ?>

                                        às

                                        <?= substr(
                                            $agendamento[
                                                'hora_inicio'
                                            ],
                                            0,
                                            5
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>


                        <!-- VALOR -->

                        <div class="form-group">

                            <label for="valor_pago">

                                Valor pago *

                            </label>


                            <input
                                type="number"
                                name="valor_pago"
                                id="valor_pago"
                                step="0.01"
                                min="0.01"
                                value="<?= htmlspecialchars(
                                    $valor_pago
                                ) ?>"
                                placeholder="0,00"
                                required
                            >

                        </div>


                        <!-- FORMA DE PAGAMENTO -->

                        <div class="form-group">

                            <label for="forma_pagamento">

                                Forma de pagamento *

                            </label>


                            <select
                                name="forma_pagamento"
                                id="forma_pagamento"
                                required
                            >

                                <option value="">

                                    Selecione

                                </option>


                                <option
                                    value="Dinheiro"
                                    <?= $forma_pagamento === 'Dinheiro'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Dinheiro

                                </option>


                                <option
                                    value="Pix"
                                    <?= $forma_pagamento === 'Pix'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Pix

                                </option>


                                <option
                                    value="Cartão"
                                    <?= $forma_pagamento === 'Cartão'
                                        ? 'selected'
                                        : '' ?>
                                >

                                    Cartão

                                </option>


                            </select>

                        </div>


                        <!-- DATA -->

                        <div class="form-group">

                            <label for="data_atendimento">

                                Data do atendimento *

                            </label>


                            <input
                                type="date"
                                name="data_atendimento"
                                id="data_atendimento"
                                value="<?= htmlspecialchars(
                                    $data_atendimento
                                ) ?>"
                                required
                            >

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
                            ><?= htmlspecialchars(
                                $observacao
                            ) ?></textarea>

                        </div>


                    </div>


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

                            <i class="fa-solid fa-floppy-disk"></i>

                            Registrar Atendimento

                        </button>


                    </div>


                </form>


            </div>


        </main>


    </div>


</div>


<?php include __DIR__ . '/../../includes/footer.php'; ?>