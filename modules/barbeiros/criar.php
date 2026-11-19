<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();

$erro = '';

$nome = '';
$email = '';
$telefone = '';
$especialidade = '';
$percentual_comissao = '40.00';
$status = 'Ativo';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $especialidade = trim($_POST['especialidade'] ?? '');
    $percentual_comissao = $_POST['percentual_comissao'] ?? '40.00';
    $status = $_POST['status'] ?? 'Ativo';


    // ==============================
    // VALIDAÇÕES
    // ==============================

    if ($nome === '') {

        $erro = 'Informe o nome do barbeiro.';

    } elseif ($email === '') {

        $erro = 'Informe o e-mail do barbeiro.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Informe um e-mail válido.';

    } elseif ($senha === '') {

        $erro = 'Informe uma senha.';

    } elseif (strlen($senha) < 6) {

        $erro = 'A senha deve possuir pelo menos 6 caracteres.';

    } elseif (
        !is_numeric($percentual_comissao) ||
        $percentual_comissao < 0 ||
        $percentual_comissao > 100
    ) {

        $erro = 'Informe um percentual de comissão entre 0 e 100.';

    } else {

        try {

            // Verifica se o e-mail já existe

            $sql = "SELECT id_usuario
                    FROM usuarios
                    WHERE email = :email
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();

            if ($stmt->fetch()) {

                $erro = 'Este e-mail já está cadastrado.';

            } else {

                // ==============================
                // INICIAR TRANSAÇÃO
                // ==============================

                $db->beginTransaction();


                // ==============================
                // CADASTRAR USUÁRIO
                // ==============================

                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );


                $sql = "INSERT INTO usuarios
                            (
                                nome,
                                email,
                                senha,
                                telefone,
                                tipo_usuario,
                                status
                            )
                        VALUES
                            (
                                :nome,
                                :email,
                                :senha,
                                :telefone,
                                'Barbeiro',
                                :status
                            )";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':nome', $nome);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':senha', $senha_hash);
                $stmt->bindValue(':telefone', $telefone !== '' ? $telefone : null);
                $stmt->bindValue(':status', $status);

                $stmt->execute();


                // Recupera o ID do usuário criado

                $id_usuario = $db->lastInsertId();


                // ==============================
                // CADASTRAR BARBEIRO
                // ==============================

                $sql = "INSERT INTO barbeiros
                            (
                                id_usuario,
                                especialidade,
                                percentual_comissao
                            )
                        VALUES
                            (
                                :id_usuario,
                                :especialidade,
                                :percentual_comissao
                            )";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':id_usuario', $id_usuario);
                $stmt->bindValue(
                    ':especialidade',
                    $especialidade !== '' ? $especialidade : null
                );
                $stmt->bindValue(
                    ':percentual_comissao',
                    $percentual_comissao
                );

                $stmt->execute();


                // ==============================
                // FINALIZAR
                // ==============================

                $db->commit();

                header(
                    'Location: ' .
                    BASE_URL .
                    '/modules/barbeiros/index.php?sucesso=1'
                );

                exit;
            }

        } catch (PDOException $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $erro = 'Não foi possível cadastrar o barbeiro.';

            // Durante o desenvolvimento podemos visualizar o erro.
            // Removeremos isso na versão final.
            $erro .= ' ' . $e->getMessage();
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

                    <h1>Novo Barbeiro</h1>

                    <p>
                        Cadastre um novo barbeiro no BarberFlow.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/barbeiros/index.php"
                    class="btn btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar
                </a>

            </div>


            <?php if ($erro): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>


            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Dados do barbeiro</h2>

                        <span>
                            Preencha as informações abaixo.
                        </span>

                    </div>

                </div>


                <form method="POST">


                    <!-- DADOS PESSOAIS -->

                    <div class="form-section">

                        <h3>Informações pessoais</h3>


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="nome">
                                    Nome completo *
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    value="<?= htmlspecialchars($nome) ?>"
                                    maxlength="100"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="email">
                                    E-mail *
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    maxlength="100"
                                    required
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
                                    maxlength="20"
                                >

                            </div>


                            <div class="form-group">

                                <label for="senha">
                                    Senha *
                                </label>

                                <input
                                    type="password"
                                    id="senha"
                                    name="senha"
                                    minlength="6"
                                    required
                                >

                                <small>
                                    Mínimo de 6 caracteres.
                                </small>

                            </div>


                        </div>

                    </div>


                    <!-- DADOS PROFISSIONAIS -->

                    <div class="form-section">

                        <h3>Informações profissionais</h3>


                        <div class="form-grid">


                            <div class="form-group">

                                <label for="especialidade">
                                    Especialidade
                                </label>

                                <input
                                    type="text"
                                    id="especialidade"
                                    name="especialidade"
                                    value="<?= htmlspecialchars($especialidade) ?>"
                                    maxlength="100"
                                    placeholder="Ex.: Corte masculino"
                                >

                            </div>


                            <div class="form-group">

                                <label for="percentual_comissao">
                                    Percentual de comissão *
                                </label>

                                <input
                                    type="number"
                                    id="percentual_comissao"
                                    name="percentual_comissao"
                                    value="<?= htmlspecialchars($percentual_comissao) ?>"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                >

                            </div>


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

                    </div>


                    <!-- BOTÕES -->

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/barbeiros/index.php"
                            class="btn btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            Cadastrar Barbeiro
                        </button>

                    </div>


                </form>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>