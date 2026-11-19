<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();

$erro = '';

/*
|--------------------------------------------------------------------------
| VALORES DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$nome = $_POST['nome'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$quantidade = $_POST['quantidade'] ?? '0';
$estoque_minimo = $_POST['estoque_minimo'] ?? '5';
$preco_compra = $_POST['preco_compra'] ?? '';
$preco_venda = $_POST['preco_venda'] ?? '';
$status = $_POST['status'] ?? 'Ativo';


/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if (
        trim($nome) === '' ||
        trim($categoria) === '' ||
        $preco_compra === '' ||
        $preco_venda === ''
    ) {

        $erro = 'Preencha todos os campos obrigatórios.';

    } elseif ((float) $quantidade < 0) {

        $erro = 'A quantidade não pode ser negativa.';

    } elseif ((int) $estoque_minimo < 0) {

        $erro = 'O estoque mínimo não pode ser negativo.';

    } elseif ((float) $preco_compra < 0) {

        $erro = 'O preço de compra não pode ser negativo.';

    } elseif ((float) $preco_venda < 0) {

        $erro = 'O preço de venda não pode ser negativo.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CADASTRAR PRODUTO
            |--------------------------------------------------------------------------
            */

            $sql = "INSERT INTO produtos (
                        nome,
                        categoria,
                        quantidade,
                        estoque_minimo,
                        preco_compra,
                        preco_venda,
                        status
                    )

                    VALUES (
                        :nome,
                        :categoria,
                        :quantidade,
                        :estoque_minimo,
                        :preco_compra,
                        :preco_venda,
                        :status
                    )";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':nome',
                trim($nome)
            );

            $stmt->bindValue(
                ':categoria',
                trim($categoria)
            );

            $stmt->bindValue(
                ':quantidade',
                (int) $quantidade,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':estoque_minimo',
                (int) $estoque_minimo,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ':preco_compra',
                $preco_compra
            );

            $stmt->bindValue(
                ':preco_venda',
                $preco_venda
            );

            $stmt->bindValue(
                ':status',
                $status
            );

            $stmt->execute();


            /*
            |--------------------------------------------------------------------------
            | REDIRECIONAR
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                BASE_URL .
                '/modules/estoque/index.php?sucesso=1'
            );

            exit;

        } catch (PDOException $e) {

            $erro = 'Ocorreu um erro ao cadastrar o produto.';
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

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <h1>Novo Produto</h1>

                    <p>
                        Cadastre um novo produto no estoque da barbearia.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/estoque/index.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar
                </a>

            </div>


            <!-- ERRO -->

            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <!-- FORMULÁRIO -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Dados do produto</h2>

                        <span>
                            Preencha as informações abaixo.
                        </span>

                    </div>

                </div>


                <form method="POST">

                    <div class="form-grid">


                        <!-- NOME -->

                        <div class="form-group">

                            <label for="nome">
                                Nome do produto *
                            </label>

                            <input
                                type="text"
                                name="nome"
                                id="nome"
                                value="<?= htmlspecialchars($nome) ?>"
                                placeholder="Ex.: Pomada modeladora"
                                maxlength="100"
                                required
                            >

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
                                placeholder="Ex.: Finalização"
                                maxlength="50"
                                required
                            >

                        </div>


                        <!-- QUANTIDADE -->

                        <div class="form-group">

                            <label for="quantidade">
                                Quantidade
                            </label>

                            <input
                                type="number"
                                name="quantidade"
                                id="quantidade"
                                value="<?= htmlspecialchars($quantidade) ?>"
                                min="0"
                                step="1"
                            >

                        </div>


                        <!-- ESTOQUE MÍNIMO -->

                        <div class="form-group">

                            <label for="estoque_minimo">
                                Estoque mínimo
                            </label>

                            <input
                                type="number"
                                name="estoque_minimo"
                                id="estoque_minimo"
                                value="<?= htmlspecialchars($estoque_minimo) ?>"
                                min="0"
                                step="1"
                            >

                        </div>


                        <!-- PREÇO DE COMPRA -->

                        <div class="form-group">

                            <label for="preco_compra">
                                Preço de compra *
                            </label>

                            <input
                                type="number"
                                name="preco_compra"
                                id="preco_compra"
                                value="<?= htmlspecialchars($preco_compra) ?>"
                                min="0"
                                step="0.01"
                                placeholder="0,00"
                                required
                            >

                        </div>


                        <!-- PREÇO DE VENDA -->

                        <div class="form-group">

                            <label for="preco_venda">
                                Preço de venda *
                            </label>

                            <input
                                type="number"
                                name="preco_venda"
                                id="preco_venda"
                                value="<?= htmlspecialchars($preco_venda) ?>"
                                min="0"
                                step="0.01"
                                placeholder="0,00"
                                required
                            >

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
                                    value="Ativo"
                                    <?= $status === 'Ativo' ? 'selected' : '' ?>
                                >
                                    Ativo
                                </option>

                                <option
                                    value="Inativo"
                                    <?= $status === 'Inativo' ? 'selected' : '' ?>
                                >
                                    Inativo
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- AÇÕES -->

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/estoque/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="fa-solid fa-box"></i>

                            Salvar Produto

                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>