<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$erro = '';

/*
|--------------------------------------------------------------------------
| VERIFICAR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCAR SERVIÇO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_servico,
            nome,
            descricao,
            duracao,
            preco,
            percentual_comissao,
            status
        FROM servicos
        WHERE id_servico = :id
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id',
    $id,
    PDO::PARAM_INT
);

$stmt->execute();

$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PROCESSAR ALTERAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $duracao = $_POST['duracao'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $percentual_comissao = $_POST['percentual_comissao'] ?? '';
    $status = $_POST['status'] ?? 'Ativo';


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($nome === '') {

        $erro = 'Informe o nome do serviço.';

    } elseif (
        $duracao === '' ||
        !is_numeric($duracao) ||
        (int)$duracao <= 0
    ) {

        $erro = 'Informe uma duração válida.';

    } elseif (
        $preco === '' ||
        !is_numeric($preco) ||
        (float)$preco < 0
    ) {

        $erro = 'Informe um preço válido.';

    } elseif (
        $percentual_comissao === '' ||
        !is_numeric($percentual_comissao) ||
        (float)$percentual_comissao < 0 ||
        (float)$percentual_comissao > 100
    ) {

        $erro = 'Informe uma comissão entre 0% e 100%.';

    } elseif (
        !in_array(
            $status,
            ['Ativo', 'Inativo'],
            true
        )
    ) {

        $erro = 'Status inválido.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR NOME DUPLICADO
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT id_servico
                    FROM servicos
                    WHERE nome = :nome
                    AND id_servico <> :id
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':nome',
                $nome
            );

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            if ($stmt->fetch()) {

                $erro = 'Já existe outro serviço cadastrado com esse nome.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | ATUALIZAR SERVIÇO
                |--------------------------------------------------------------------------
                */

                $sql = "UPDATE servicos
                        SET
                            nome = :nome,
                            descricao = :descricao,
                            duracao = :duracao,
                            preco = :preco,
                            percentual_comissao = :percentual_comissao,
                            status = :status
                        WHERE id_servico = :id";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(
                    ':nome',
                    $nome
                );

                $stmt->bindValue(
                    ':descricao',
                    $descricao !== ''
                        ? $descricao
                        : null,
                    $descricao !== ''
                        ? PDO::PARAM_STR
                        : PDO::PARAM_NULL
                );

                $stmt->bindValue(
                    ':duracao',
                    (int)$duracao,
                    PDO::PARAM_INT
                );

                $stmt->bindValue(
                    ':preco',
                    (float)$preco
                );

                $stmt->bindValue(
                    ':percentual_comissao',
                    (float)$percentual_comissao
                );

                $stmt->bindValue(
                    ':status',
                    $status
                );

                $stmt->bindValue(
                    ':id',
                    $id,
                    PDO::PARAM_INT
                );

                $stmt->execute();


                /*
                |--------------------------------------------------------------------------
                | VOLTAR PARA LISTAGEM
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: ' .
                    BASE_URL .
                    '/modules/servicos/index.php?sucesso=2'
                );

                exit;
            }

        } catch (PDOException $e) {

            $erro = 'Ocorreu um erro ao atualizar o serviço.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | MANTER OS VALORES DIGITADOS NO FORMULÁRIO
    |--------------------------------------------------------------------------
    */

    $servico['nome'] = $nome;
    $servico['descricao'] = $descricao;
    $servico['duracao'] = $duracao;
    $servico['preco'] = $preco;
    $servico['percentual_comissao'] = $percentual_comissao;
    $servico['status'] = $status;
}

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

                    <h1>Editar Serviço</h1>

                    <p>
                        Altere as informações do serviço cadastrado.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/servicos/index.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar
                </a>

            </div>


            <!-- CARD -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Dados do serviço</h2>

                        <span>
                            Atualize as informações abaixo.
                        </span>

                    </div>

                </div>


                <!-- ERRO -->

                <?php if ($erro !== ''): ?>

                    <div class="alert alert-danger">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($erro) ?>

                    </div>

                <?php endif; ?>


                <!-- FORMULÁRIO -->

                <form method="POST">

                    <!-- INFORMAÇÕES PRINCIPAIS -->

                    <div class="form-section">

                        <h3>Informações do serviço</h3>

                        <div class="form-grid">


                            <!-- NOME -->

                            <div class="form-group">

                                <label for="nome">
                                    Nome do serviço *
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    maxlength="100"
                                    value="<?= htmlspecialchars($servico['nome']) ?>"
                                    placeholder="Ex.: Corte masculino"
                                    required
                                >

                            </div>


                            <!-- DURAÇÃO -->

                            <div class="form-group">

                                <label for="duracao">
                                    Duração *
                                </label>

                                <div class="input-with-suffix">

                                    <input
                                        type="number"
                                        id="duracao"
                                        name="duracao"
                                        min="1"
                                        step="1"
                                        value="<?= htmlspecialchars($servico['duracao']) ?>"
                                        placeholder="Ex.: 30"
                                        required
                                    >

                                    <span>min</span>

                                </div>

                            </div>


                            <!-- PREÇO -->

                            <div class="form-group">

                                <label for="preco">
                                    Preço *
                                </label>

                                <div class="input-with-prefix">

                                    <span>R$</span>

                                    <input
                                        type="number"
                                        id="preco"
                                        name="preco"
                                        min="0"
                                        step="0.01"
                                        value="<?= htmlspecialchars($servico['preco']) ?>"
                                        placeholder="0,00"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- COMISSÃO -->

                            <div class="form-group">

                                <label for="percentual_comissao">
                                    Percentual de comissão *
                                </label>

                                <div class="input-with-suffix">

                                    <input
                                        type="number"
                                        id="percentual_comissao"
                                        name="percentual_comissao"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="<?= htmlspecialchars($servico['percentual_comissao']) ?>"
                                        placeholder="40"
                                        required
                                    >

                                    <span>%</span>

                                </div>

                            </div>


                            <!-- STATUS -->

                            <div class="form-group">

                                <label for="status">
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                >

                                    <option
                                        value="Ativo"
                                        <?= $servico['status'] === 'Ativo'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Ativo
                                    </option>

                                    <option
                                        value="Inativo"
                                        <?= $servico['status'] === 'Inativo'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Inativo
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <!-- DESCRIÇÃO -->

                    <div class="form-section">

                        <h3>Descrição</h3>

                        <div class="form-group">

                            <label for="descricao">
                                Descrição do serviço
                            </label>

                            <textarea
                                id="descricao"
                                name="descricao"
                                rows="4"
                                placeholder="Descreva o serviço oferecido..."
                            ><?= htmlspecialchars($servico['descricao'] ?? '') ?></textarea>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/servicos/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-check"></i>
                            Salvar Alterações
                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>