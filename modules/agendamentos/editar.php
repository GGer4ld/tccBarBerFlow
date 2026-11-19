<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';
$idUsuarioLogado = (int)($_SESSION['usuario']['id'] ?? 0);

if (!in_array($tipoUsuario, ['Admin', 'Administrador', 'Barbeiro'], true)) {
    header('Location: ' . BASE_URL . '/dashboard.php?erro=acesso_negado');
    exit;
}

$database = new Database();
$db = $database->conectar();
$erro = '';

$id_agendamento = filter_var(
    $_GET['id'] ?? $_POST['id_agendamento'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$id_agendamento || $id_agendamento <= 0) {
    header('Location: ' . BASE_URL . '/modules/agendamentos/index.php?erro=agendamento');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR AGENDAMENTO
|--------------------------------------------------------------------------
*/
$sql = "SELECT a.*
        FROM agendamentos a
        WHERE a.id_agendamento = :id
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bindValue(':id', $id_agendamento, PDO::PARAM_INT);
$stmt->execute();

$agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agendamento) {
    header('Location: ' . BASE_URL . '/modules/agendamentos/index.php?erro=agendamento');
    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFICAR PERMISSÃO DO BARBEIRO
|--------------------------------------------------------------------------
*/
if ($tipoUsuario === 'Barbeiro') {

    $sql = "SELECT b.id_barbeiro
            FROM barbeiros b
            WHERE b.id_usuario = :id_usuario
              AND b.id_barbeiro = :id_barbeiro
            LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_usuario',
        $idUsuarioLogado,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':id_barbeiro',
        (int)$agendamento['id_barbeiro'],
        PDO::PARAM_INT
    );

    $stmt->execute();

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ' . BASE_URL . '/modules/agendamentos/index.php?erro=acesso_negado');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| BUSCAR SERVIÇOS JÁ VINCULADOS
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare(
    "SELECT id_servico
     FROM agendamento_servicos
     WHERE id_agendamento = :id"
);

$stmt->bindValue(':id', $id_agendamento, PDO::PARAM_INT);
$stmt->execute();

$servicosAtuais = array_map(
    'intval',
    $stmt->fetchAll(PDO::FETCH_COLUMN)
);

/*
|--------------------------------------------------------------------------
| COMPATIBILIDADE COM AGENDAMENTOS ANTIGOS
|--------------------------------------------------------------------------
*/
if (empty($servicosAtuais) && !empty($agendamento['id_servico'])) {
    $servicosAtuais = [(int)$agendamento['id_servico']];
}

/*
|--------------------------------------------------------------------------
| BUSCAR CLIENTES
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare(
    "SELECT id_cliente, nome
     FROM clientes
     ORDER BY nome ASC"
);

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
| Inclui os serviços ativos e também os que já estavam vinculados,
| mesmo que tenham sido inativados posteriormente.
|--------------------------------------------------------------------------
*/
$servicos = [];

if (!empty($servicosAtuais)) {

    $marks = implode(
        ',',
        array_fill(0, count($servicosAtuais), '?')
    );

    $sql = "SELECT
                id_servico,
                nome,
                preco,
                duracao,
                status
            FROM servicos
            WHERE status = 'Ativo'
               OR id_servico IN ($marks)
            ORDER BY nome ASC";

    $stmt = $db->prepare($sql);

    foreach ($servicosAtuais as $i => $idServico) {
        $stmt->bindValue(
            $i + 1,
            $idServico,
            PDO::PARAM_INT
        );
    }

    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    $stmt = $db->prepare(
        "SELECT
            id_servico,
            nome,
            preco,
            duracao,
            status
         FROM servicos
         WHERE status = 'Ativo'
         ORDER BY nome ASC"
    );

    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| VALORES INICIAIS
|--------------------------------------------------------------------------
*/
$id_cliente = (int)$agendamento['id_cliente'];
$id_barbeiro = (int)$agendamento['id_barbeiro'];

$id_servicos = $servicosAtuais;

$data_agendamento = (string)$agendamento['data_agendamento'];

$hora_inicio = substr(
    (string)$agendamento['hora_inicio'],
    0,
    5
);

$hora_fim = substr(
    (string)$agendamento['hora_fim'],
    0,
    5
);

$status = (string)$agendamento['status'];

$observacao = (string)($agendamento['observacao'] ?? '');

/*
|--------------------------------------------------------------------------
| PROTEÇÃO CSRF
|--------------------------------------------------------------------------
*/
$csrfToken = $_SESSION['csrf_agendamento_editar'] ?? '';

if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_agendamento_editar'] = $csrfToken;
}

/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_cliente = (int)($_POST['id_cliente'] ?? 0);

    $id_barbeiro = (int)($_POST['id_barbeiro'] ?? 0);

    $id_servicosPost = $_POST['id_servicos'] ?? [];

    if (!is_array($id_servicosPost)) {
        $id_servicosPost = [$id_servicosPost];
    }

    $id_servicos = array_values(array_unique(array_filter(
        array_map('intval', $id_servicosPost),
        static fn($v) => $v > 0
    )));

    $data_agendamento = trim(
        (string)($_POST['data_agendamento'] ?? '')
    );

    $hora_inicio = trim(
        (string)($_POST['hora_inicio'] ?? '')
    );

    $status = trim(
        (string)($_POST['status'] ?? 'Pendente')
    );

    $observacao = trim(
        (string)($_POST['observacao'] ?? '')
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDAR TOKEN
    |--------------------------------------------------------------------------
    */
    if (!hash_equals(
        $csrfToken,
        (string)($_POST['csrf_token'] ?? '')
    )) {

        $erro = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';

    } elseif (
        !$id_cliente ||
        !$id_barbeiro ||
        empty($id_servicos) ||
        $data_agendamento === '' ||
        $hora_inicio === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios e selecione pelo menos um serviço.';

    } elseif (!in_array($status, [
        'Pendente',
        'Confirmado',
        'Em atendimento',
        'Concluído',
        'Cancelado'
    ], true)) {

        $erro = 'O status selecionado é inválido.';

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_agendamento) ||
        !checkdate(
            (int)substr($data_agendamento, 5, 2),
            (int)substr($data_agendamento, 8, 2),
            (int)substr($data_agendamento, 0, 4)
        )
    ) {

        $erro = 'A data informada é inválida.';

    } elseif (
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora_inicio) ||
        (int)substr($hora_inicio, 3, 2) % 30 !== 0 ||
        $hora_inicio < '08:00' ||
        $hora_inicio > '19:30'
    ) {

        $erro = 'Escolha um horário entre 08:00 e 19:30, em intervalos de 30 minutos.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR CLIENTE
            |--------------------------------------------------------------------------
            */
            $stmt = $db->prepare(
                "SELECT id_cliente
                 FROM clientes
                 WHERE id_cliente = :id
                 LIMIT 1"
            );

            $stmt->bindValue(
                ':id',
                $id_cliente,
                PDO::PARAM_INT
            );

            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $erro = 'O cliente selecionado não foi encontrado.';
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR BARBEIRO
            |--------------------------------------------------------------------------
            */
            if ($erro === '') {

                $sql = "SELECT b.id_barbeiro
                        FROM barbeiros b
                        INNER JOIN usuarios u
                            ON u.id_usuario = b.id_usuario
                        WHERE b.id_barbeiro = :id
                          AND u.status = 'Ativo'
                        LIMIT 1";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(
                    ':id',
                    $id_barbeiro,
                    PDO::PARAM_INT
                );

                $stmt->execute();

                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                    $erro = 'O barbeiro selecionado não existe ou não está ativo.';

                } elseif (
                    $tipoUsuario === 'Barbeiro' &&
                    $id_barbeiro !== (int)$agendamento['id_barbeiro']
                ) {

                    $erro = 'O barbeiro só pode manter o agendamento vinculado a ele próprio.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | BUSCAR E VALIDAR TODOS OS SERVIÇOS
            |--------------------------------------------------------------------------
            */
            if ($erro === '') {

                $marks = implode(
                    ',',
                    array_fill(0, count($id_servicos), '?')
                );

                $sql = "SELECT
                            id_servico,
                            nome,
                            preco,
                            duracao,
                            status
                        FROM servicos
                        WHERE id_servico IN ($marks)
                        ORDER BY nome ASC";

                $stmt = $db->prepare($sql);

                foreach ($id_servicos as $i => $idServico) {

                    $stmt->bindValue(
                        $i + 1,
                        $idServico,
                        PDO::PARAM_INT
                    );
                }

                $stmt->execute();

                $servicosSelecionados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($servicosSelecionados) !== count($id_servicos)) {

                    $erro = 'Um ou mais serviços selecionados não foram encontrados.';

                } else {

                    $totalDuracao = 0;

                    foreach ($servicosSelecionados as $servico) {

                        $idServicoAtual = (int)$servico['id_servico'];

                        $eraDoAgendamento = in_array(
                            $idServicoAtual,
                            $servicosAtuais,
                            true
                        );

                        if (
                            $servico['status'] !== 'Ativo' &&
                            !$eraDoAgendamento
                        ) {

                            $erro = 'Não é possível adicionar um serviço que está inativo.';
                            break;
                        }

                        if (
                            (int)$servico['duracao'] <= 0 ||
                            (float)$servico['preco'] < 0
                        ) {

                            $erro = 'Um dos serviços possui preço ou duração inválidos.';
                            break;
                        }

                        $totalDuracao += (int)$servico['duracao'];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CALCULAR HORÁRIO FINAL
            |--------------------------------------------------------------------------
            */
            if ($erro === '') {

                $inicioTimestamp = strtotime(
                    $data_agendamento . ' ' . $hora_inicio
                );

                if (
                    $inicioTimestamp === false ||
                    $totalDuracao <= 0
                ) {

                    $erro = 'Não foi possível calcular a duração do agendamento.';

                } else {

                    $fimTimestamp = $inicioTimestamp + ($totalDuracao * 60);

                    if (
                        $fimTimestamp >
                        strtotime($data_agendamento . ' 20:00')
                    ) {

                        $erro = 'O horário final ultrapassa o horário de funcionamento das 20:00.';

                    } else {

                        $hora_fim = date('H:i', $fimTimestamp);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | ATUALIZAR AGENDAMENTO
            |--------------------------------------------------------------------------
            */
            if ($erro === '') {

                $db->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR CONFLITO DE HORÁRIO
                |--------------------------------------------------------------------------
                */
                $sql = "SELECT id_agendamento
                        FROM agendamentos
                        WHERE id_barbeiro = :id_barbeiro
                          AND data_agendamento = :data_agendamento
                          AND status <> 'Cancelado'
                          AND id_agendamento <> :id_agendamento
                          AND :hora_inicio < hora_fim
                          AND :hora_fim > hora_inicio
                        LIMIT 1
                        FOR UPDATE";

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
                    ':id_agendamento',
                    $id_agendamento,
                    PDO::PARAM_INT
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

                    $db->rollBack();

                    $erro = 'Este barbeiro já possui um agendamento neste horário.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | ATUALIZAR TABELA PRINCIPAL
                    |--------------------------------------------------------------------------
                    | O primeiro serviço continua em id_servico para compatibilidade.
                    */
                    $sql = "UPDATE agendamentos SET
                                id_cliente = :id_cliente,
                                id_barbeiro = :id_barbeiro,
                                id_servico = :id_servico,
                                data_agendamento = :data_agendamento,
                                hora_inicio = :hora_inicio,
                                hora_fim = :hora_fim,
                                status = :status,
                                observacao = :observacao
                            WHERE id_agendamento = :id_agendamento";

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
                        $id_servicos[0],
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

                    $stmt->bindValue(
                        ':id_agendamento',
                        $id_agendamento,
                        PDO::PARAM_INT
                    );

                    $stmt->execute();

                    /*
                    |--------------------------------------------------------------------------
                    | REMOVER SERVIÇOS ANTIGOS
                    |--------------------------------------------------------------------------
                    */
                    $stmt = $db->prepare(
                        "DELETE FROM agendamento_servicos
                         WHERE id_agendamento = :id"
                    );

                    $stmt->bindValue(
                        ':id',
                        $id_agendamento,
                        PDO::PARAM_INT
                    );

                    $stmt->execute();

                    /*
                    |--------------------------------------------------------------------------
                    | INSERIR SERVIÇOS ATUALIZADOS
                    |--------------------------------------------------------------------------
                    */
                    $sql = "INSERT INTO agendamento_servicos (
                                id_agendamento,
                                id_servico,
                                preco
                            ) VALUES (
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
                            (int)$servico['id_servico'],
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
                    | CONFIRMAR ALTERAÇÕES
                    |--------------------------------------------------------------------------
                    */
                    $db->commit();

                    header(
                        'Location: ' .
                        BASE_URL .
                        '/modules/agendamentos/index.php?sucesso=2'
                    );

                    exit;
                }
            }

        } catch (Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Erro ao editar agendamento: ' . $e->getMessage());

            $erro = 'Ocorreu um erro ao atualizar o agendamento. Verifique os dados e tente novamente.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| CALCULAR TOTAIS PARA EXIBIÇÃO
|--------------------------------------------------------------------------
*/
$totalPrecoExibicao = 0;
$totalDuracaoExibicao = 0;

foreach ($servicos as $servico) {

    if (in_array(
        (int)$servico['id_servico'],
        $id_servicos,
        true
    )) {

        $totalPrecoExibicao += (float)$servico['preco'];

        $totalDuracaoExibicao += (int)$servico['duracao'];
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
                    <h1>Editar Agendamento</h1>

                    <p>
                        Atualize as informações do atendimento.
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
                            Altere as informações abaixo e selecione um ou mais serviços.
                        </span>
                    </div>

                </div>

                <form method="POST">

                    <input
                        type="hidden"
                        name="id_agendamento"
                        value="<?= (int)$id_agendamento ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >

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
                                        value="<?= (int)$cliente['id_cliente'] ?>"
                                        <?= $id_cliente == $cliente['id_cliente'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($cliente['nome']) ?>
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
                                <?= $tipoUsuario === 'Barbeiro' ? 'disabled' : '' ?>
                            >

                                <option value="">
                                    Selecione o barbeiro
                                </option>

                                <?php foreach ($barbeiros as $barbeiro): ?>

                                    <option
                                        value="<?= (int)$barbeiro['id_barbeiro'] ?>"
                                        <?= $id_barbeiro == $barbeiro['id_barbeiro'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($barbeiro['nome']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if ($tipoUsuario === 'Barbeiro'): ?>

                                <input
                                    type="hidden"
                                    name="id_barbeiro"
                                    value="<?= (int)$id_barbeiro ?>"
                                >

                            <?php endif; ?>

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
                                            value="<?= (int)$servico['id_servico'] ?>"
                                            data-preco="<?= htmlspecialchars((string)$servico['preco']) ?>"
                                            data-duracao="<?= (int)$servico['duracao'] ?>"
                                            <?= in_array(
                                                (int)$servico['id_servico'],
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
                                                <?= htmlspecialchars($servico['nome']) ?>
                                            </strong>

                                            <small
                                                style="
                                                    color:#64748B;
                                                    display:block;
                                                "
                                            >
                                                R$ <?= number_format(
                                                    (float)$servico['preco'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>

                                                &nbsp;•&nbsp;

                                                <?= (int)$servico['duracao'] ?> min

                                                <?php if ($servico['status'] !== 'Ativo'): ?>
                                                    &nbsp;•&nbsp; Serviço inativo (já vinculado)
                                                <?php endif; ?>

                                            </small>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <!-- TOTAIS -->
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
                                        R$ <?= number_format(
                                            $totalPrecoExibicao,
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
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
                                        <?= $totalDuracaoExibicao ?> min
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
                                min="08:00"
                                max="19:30"
                                step="1800"
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

                                <?php foreach ([
                                    'Pendente',
                                    'Confirmado',
                                    'Em atendimento',
                                    'Concluído',
                                    'Cancelado'
                                ] as $opcaoStatus): ?>

                                    <option
                                        value="<?= htmlspecialchars($opcaoStatus) ?>"
                                        <?= $status === $opcaoStatus ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($opcaoStatus) ?>
                                    </option>

                                <?php endforeach; ?>

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
                            <i class="fa-solid fa-floppy-disk"></i>
                            Salvar Alterações
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

    const horaInicio = document.getElementById('hora_inicio');
    const horaFim = document.getElementById('hora_fim');
    const totalPreco = document.getElementById('totalPreco');
    const totalDuracao = document.getElementById('totalDuracao');

    function calcularAgendamento() {

        let preco = 0;
        let duracao = 0;

        servicos.forEach(function (servico) {

            if (servico.checked) {

                preco += parseFloat(
                    servico.dataset.preco || 0
                );

                duracao += parseInt(
                    servico.dataset.duracao || 0,
                    10
                );
            }
        });

        totalPreco.textContent = preco.toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL'
            }
        );

        totalDuracao.textContent = duracao + ' min';

        if (!horaInicio.value || duracao <= 0) {
            horaFim.value = '';
            return;
        }

        const partes = horaInicio.value.split(':');

        const data = new Date();

        data.setHours(
            parseInt(partes[0], 10),
            parseInt(partes[1], 10),
            0,
            0
        );

        data.setMinutes(
            data.getMinutes() + duracao
        );

        const hora = String(
            data.getHours()
        ).padStart(2, '0');

        const minuto = String(
            data.getMinutes()
        ).padStart(2, '0');

        horaFim.value = hora + ':' + minuto;
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