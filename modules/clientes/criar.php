
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

// ==========================================
// PERMISSÃO: ADMINISTRADOR E BARBEIRO
// ==========================================

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

if (!in_array($tipoUsuario, ['Admin', 'Administrador', 'Barbeiro'], true)) {
    http_response_code(403);
    exit('Você não tem permissão para cadastrar clientes.');
}

$erros = [];

$nome = '';
$email = '';
$telefone = '';
$data_nascimento = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = trim($_POST['data_nascimento'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    // ==============================
    // VALIDAÇÕES
    // ==============================

    if ($nome === '') {
        $erros[] = 'Informe o nome do cliente.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if ($data_nascimento !== '') {

        $data = DateTime::createFromFormat('Y-m-d', $data_nascimento);

        if (!$data || $data->format('Y-m-d') !== $data_nascimento) {
            $erros[] = 'Informe uma data de nascimento válida.';
        }

    }

    // ==============================
    // CADASTRO
    // ==============================

    if (empty($erros)) {

        try {

            $database = new Database();
            $db = $database->conectar();

            $sql = "INSERT INTO clientes
                    (
                        nome,
                        email,
                        telefone,
                        data_nascimento,
                        observacoes
                    )
                    VALUES
                    (
                        :nome,
                        :email,
                        :telefone,
                        :data_nascimento,
                        :observacoes
                    )";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(':nome', $nome);

            $stmt->bindValue(
                ':email',
                $email !== '' ? $email : null,
                $email !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $stmt->bindValue(
                ':telefone',
                $telefone !== '' ? $telefone : null,
                $telefone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $stmt->bindValue(
                ':data_nascimento',
                $data_nascimento !== '' ? $data_nascimento : null,
                $data_nascimento !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $stmt->bindValue(
                ':observacoes',
                $observacoes !== '' ? $observacoes : null,
                $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );

            $stmt->execute();

            header('Location: index.php?sucesso=1');
            exit;

        } catch (PDOException $e) {

            $erros[] = 'Não foi possível cadastrar o cliente.';

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

                    <h1>Novo cliente</h1>

                    <p>
                        Cadastre um novo cliente da barbearia.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/clientes/index.php"
                    class="btn-secondary"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar

                </a>

            </div>


            <?php if (!empty($erros)): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <div>

                        <?php foreach ($erros as $erro): ?>

                            <p>
                                <?= htmlspecialchars($erro) ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <form method="POST" class="form-card">

                <div class="form-section">

                    <div class="form-section-title">

                        <i class="fa-solid fa-user"></i>

                        <div>

                            <h2>Dados do cliente</h2>

                            <p>
                                Informe os dados básicos do cliente.
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">

                        <div class="form-group form-group-full">

                            <label for="nome">
                                Nome completo
                            </label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                value="<?= htmlspecialchars($nome) ?>"
                                placeholder="Digite o nome completo"
                                maxlength="100"
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
                                value="<?= htmlspecialchars($email) ?>"
                                placeholder="exemplo@email.com"
                                maxlength="100"
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
                                value="<?= htmlspecialchars($telefone) ?>"
                                placeholder="(00) 00000-0000"
                                maxlength="20"
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
                                value="<?= htmlspecialchars($data_nascimento) ?>"
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
                                placeholder="Informações adicionais sobre o cliente..."
                            ><?= htmlspecialchars($observacoes) ?></textarea>

                        </div>

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="<?= BASE_URL ?>/modules/clientes/index.php"
                        class="btn-secondary"
                    >

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >

                        <i class="fa-solid fa-check"></i>

                        Cadastrar cliente

                    </button>

                </div>

            </form>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
