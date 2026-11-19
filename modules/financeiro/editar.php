<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?erro=nao_encontrado"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR LANÇAMENTO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_lancamento,
            id_atendimento,
            tipo,
            categoria,
            descricao,
            valor,
            data_lancamento
        FROM lancamentos_financeiros
        WHERE id_lancamento = :id
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lancamento) {
    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?erro=nao_encontrado"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| VARIÁVEIS DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$tipo = $lancamento['tipo'];
$categoria = $lancamento['categoria'];
$descricao = $lancamento['descricao'];
$valor = $lancamento['valor'];

$erro = '';

/*
|--------------------------------------------------------------------------
| ATUALIZAR LANÇAMENTO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = trim($_POST['tipo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = trim($_POST['valor'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if (
        $tipo === '' ||
        $categoria === '' ||
        $descricao === '' ||
        $valor === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';

    } elseif (
        $tipo !== 'Entrada' &&
        $tipo !== 'Saída'
    ) {

        $erro = 'O tipo de lançamento é inválido.';

    } elseif (
        !is_numeric($valor) ||
        (float) $valor <= 0
    ) {

        $erro = 'Informe um valor válido maior que zero.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | ATUALIZAR
            |--------------------------------------------------------------------------
            |
            | O id_atendimento NÃO é alterado.
            | Assim, se este lançamento veio de um Atendimento,
            | o vínculo continua preservado.
            |
            */

            $sql = "UPDATE lancamentos_financeiros
                    SET
                        tipo = :tipo,
                        categoria = :categoria,
                        descricao = :descricao,
                        valor = :valor
                    WHERE id_lancamento = :id";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':tipo',
                $tipo
            );

            $stmt->bindValue(
                ':categoria',
                $categoria
            );

            $stmt->bindValue(
                ':descricao',
                $descricao
            );

            $stmt->bindValue(
                ':valor',
                (float) $valor
            );

            $stmt->bindValue(
                ':id',
                $id,
                PDO::PARAM_INT
            );

            $stmt->execute();

            header(
                "Location: " .
                BASE_URL .
                "/modules/financeiro/index.php?sucesso=2"
            );
            exit;

        } catch (PDOException $e) {

            $erro = 'Não foi possível atualizar o lançamento.';
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

            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <div class="page-header">

                <div>

                    <h1>
                        Editar lançamento
                    </h1>

                    <p>
                        Atualize as informações do lançamento financeiro.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/financeiro/index.php"
                    class="btn btn-secondary">

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar

                </a>

            </div>


            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>
                            Dados do lançamento
                        </h2>

                        <span>
                            ID #<?= (int) $lancamento['id_lancamento'] ?>
                        </span>

                    </div>

                </div>


                <form method="POST">

                    <div class="form-grid">

                        <!-- TIPO -->

                        <div class="form-group">

                            <label for="tipo">
                                Tipo *
                            </label>

                            <select
                                name="tipo"
                                id="tipo"
                                required>

                                <option
                                    value="Entrada"
                                    <?= $tipo === 'Entrada' ? 'selected' : '' ?>>

                                    Entrada

                                </option>

                                <option
                                    value="Saída"
                                    <?= $tipo === 'Saída' ? 'selected' : '' ?>>

                                    Saída

                                </option>

                            </select>

                        </div>


                        <!-- CATEGORIA -->

                        <div class="form-group">

                            <label for="categoria">
                                Categoria *
                            </label>

                            <input
                                type="text"
                                name="categoria"
                                id="categoria"
                                value="<?= htmlspecialchars($categoria) ?>"
                                placeholder="Ex.: Atendimento, Despesa..."
                                required>

                        </div>


                        <!-- VALOR -->

                        <div class="form-group">

                            <label for="valor">
                                Valor *
                            </label>

                            <input
                                type="number"
                                name="valor"
                                id="valor"
                                step="0.01"
                                min="0.01"
                                value="<?= htmlspecialchars($valor) ?>"
                                placeholder="0,00"
                                required>

                        </div>


                        <!-- DESCRIÇÃO -->

                        <div class="form-group">

                            <label for="descricao">
                                Descrição *
                            </label>

                            <input
                                type="text"
                                name="descricao"
                                id="descricao"
                                value="<?= htmlspecialchars($descricao) ?>"
                                placeholder="Descrição do lançamento"
                                required>

                        </div>

                    </div>


                    <?php if (!empty($lancamento['id_atendimento'])): ?>

                        <div
                            class="alert alert-success"
                            style="margin-top: 20px;">

                            <i class="fa-solid fa-link"></i>

                            Este lançamento está vinculado a um atendimento
                            (ID #<?= (int) $lancamento['id_atendimento'] ?>).

                        </div>

                    <?php endif; ?>


                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/financeiro/index.php"
                            class="btn btn-secondary">

                            <i class="fa-solid fa-xmark"></i>

                            Cancelar

                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fa-solid fa-floppy-disk"></i>

                            Salvar alterações

                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>