<?php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../includes/session.php';
exigirAdministrador();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| Buscar cliente
|--------------------------------------------------------------------------
*/

$sql = "SELECT *
        FROM clientes
        WHERE id_cliente = :id
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header('Location: index.php?erro=cliente_nao_encontrado');
    exit;
}

/*
|--------------------------------------------------------------------------
| Atualizar cliente
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($nome === '') {
        $erro = 'O nome do cliente é obrigatório.';
    } else {

        $sql = "UPDATE clientes
                SET nome = :nome,
                    email = :email,
                    telefone = :telefone,
                    data_nascimento = :data_nascimento,
                    observacoes = :observacoes
                WHERE id_cliente = :id";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email !== '' ? $email : null);
        $stmt->bindValue(':telefone', $telefone !== '' ? $telefone : null);
        $stmt->bindValue(
            ':data_nascimento',
            $data_nascimento !== '' ? $data_nascimento : null
        );
        $stmt->bindValue(
            ':observacoes',
            $observacoes !== '' ? $observacoes : null
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            header('Location: index.php?sucesso=editado');
            exit;

        } else {

            $erro = 'Não foi possível atualizar o cliente.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Manter os dados digitados no formulário em caso de erro
    |--------------------------------------------------------------------------
    */

    $cliente['nome'] = $nome;
    $cliente['email'] = $email;
    $cliente['telefone'] = $telefone;
    $cliente['data_nascimento'] = $data_nascimento;
    $cliente['observacoes'] = $observacoes;
}

?>

<?php include '../../includes/header.php'; ?>

<div class="app">

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include '../../includes/navbar.php'; ?>

        <main class="content">

            <div class="page-header">

                <div>
                    <h1>Editar Cliente</h1>

                    <p>
                        Atualize os dados do cliente cadastrado.
                    </p>
                </div>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar
                </a>

            </div>

            <?php if (isset($erro)): ?>

                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <div class="card">

                <form method="POST">

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="nome">
                                Nome *
                            </label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                value="<?= htmlspecialchars($cliente['nome']) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="email">
                                E-mail
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($cliente['email'] ?? '') ?>"
                            >

                        </div>

                        <div class="form-group">

                            <label for="telefone">
                                Telefone
                            </label>

                            <input
                                type="text"
                                id="telefone"
                                name="telefone"
                                value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>"
                            >

                        </div>

                        <div class="form-group">

                            <label for="data_nascimento">
                                Data de nascimento
                            </label>

                            <input
                                type="date"
                                id="data_nascimento"
                                name="data_nascimento"
                                value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>"
                            >

                        </div>

                        <div class="form-group form-group-full">

                            <label for="observacoes">
                                Observações
                            </label>

                            <textarea
                                id="observacoes"
                                name="observacoes"
                                rows="5"
                            ><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            Salvar alterações
                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>