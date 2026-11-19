<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();


// ==========================================================
// VALIDAR ID
// ==========================================================

$id_barbeiro = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_barbeiro) {
    header("Location: " . BASE_URL . "/modules/barbeiros/index.php");
    exit();
}


// ==========================================================
// BUSCAR BARBEIRO
// ==========================================================

$sql = "SELECT
            b.id_barbeiro,
            b.id_usuario,
            b.especialidade,
            b.percentual_comissao,
            u.nome,
            u.email,
            u.telefone,
            u.status
        FROM barbeiros b
        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario
        WHERE b.id_barbeiro = :id_barbeiro
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_barbeiro',
    $id_barbeiro,
    PDO::PARAM_INT
);

$stmt->execute();

$barbeiro = $stmt->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// BARBEIRO NÃO ENCONTRADO
// ==========================================================

if (!$barbeiro) {

    header("Location: " . BASE_URL . "/modules/barbeiros/index.php");
    exit();

}


// ==========================================================
// VARIÁVEIS
// ==========================================================

$erros = [];

$nome = $barbeiro['nome'];
$email = $barbeiro['email'];
$telefone = $barbeiro['telefone'];
$especialidade = $barbeiro['especialidade'];
$percentual_comissao = $barbeiro['percentual_comissao'];
$status = $barbeiro['status'];


// ==========================================================
// PROCESSAR FORMULÁRIO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $especialidade = trim($_POST['especialidade'] ?? '');
    $percentual_comissao = $_POST['percentual_comissao'] ?? '';
    $status = $_POST['status'] ?? 'Ativo';


    // ======================================================
    // VALIDAÇÕES
    // ======================================================

    if ($nome === '') {
        $erros[] = 'Informe o nome do barbeiro.';
    }

    if ($email === '') {
        $erros[] = 'Informe o e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if ($percentual_comissao === '') {
        $erros[] = 'Informe o percentual de comissão.';
    } elseif (
        !is_numeric($percentual_comissao) ||
        $percentual_comissao < 0 ||
        $percentual_comissao > 100
    ) {
        $erros[] = 'A comissão deve estar entre 0% e 100%.';
    }

    if (!in_array($status, ['Ativo', 'Inativo'], true)) {
        $erros[] = 'Status inválido.';
    }

    if ($senha !== '' && strlen($senha) < 6) {
        $erros[] = 'A senha deve possuir no mínimo 6 caracteres.';
    }


    // ======================================================
    // VERIFICAR E-MAIL DUPLICADO
    // ======================================================

    if (empty($erros)) {

        $sql = "SELECT id_usuario
                FROM usuarios
                WHERE email = :email
                  AND id_usuario <> :id_usuario
                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':email', $email);
        $stmt->bindValue(
            ':id_usuario',
            $barbeiro['id_usuario'],
            PDO::PARAM_INT
        );

        $stmt->execute();

        if ($stmt->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado para outro usuário.';
        }
    }


    // ======================================================
    // ATUALIZAR
    // ======================================================

    if (empty($erros)) {

        try {

            $db->beginTransaction();


            // --------------------------------------------------
            // ATUALIZAR USUÁRIO
            // --------------------------------------------------

            if ($senha !== '') {

                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $sql = "UPDATE usuarios
                        SET nome = :nome,
                            email = :email,
                            telefone = :telefone,
                            senha = :senha,
                            status = :status
                        WHERE id_usuario = :id_usuario";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':nome', $nome);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':telefone', $telefone);
                $stmt->bindValue(':senha', $senha_hash);
                $stmt->bindValue(':status', $status);

                $stmt->bindValue(
                    ':id_usuario',
                    $barbeiro['id_usuario'],
                    PDO::PARAM_INT
                );

                $stmt->execute();

            } else {

                $sql = "UPDATE usuarios
                        SET nome = :nome,
                            email = :email,
                            telefone = :telefone,
                            status = :status
                        WHERE id_usuario = :id_usuario";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':nome', $nome);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':telefone', $telefone);
                $stmt->bindValue(':status', $status);

                $stmt->bindValue(
                    ':id_usuario',
                    $barbeiro['id_usuario'],
                    PDO::PARAM_INT
                );

                $stmt->execute();
            }


            // --------------------------------------------------
            // ATUALIZAR BARBEIRO
            // --------------------------------------------------

            $sql = "UPDATE barbeiros
                    SET especialidade = :especialidade,
                        percentual_comissao = :percentual_comissao
                    WHERE id_barbeiro = :id_barbeiro";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':especialidade',
                $especialidade !== '' ? $especialidade : null,
                $especialidade !== ''
                    ? PDO::PARAM_STR
                    : PDO::PARAM_NULL
            );

            $stmt->bindValue(
                ':percentual_comissao',
                $percentual_comissao
            );

            $stmt->bindValue(
                ':id_barbeiro',
                $id_barbeiro,
                PDO::PARAM_INT
            );

            $stmt->execute();


            // --------------------------------------------------
            // FINALIZAR TRANSAÇÃO
            // --------------------------------------------------

            $db->commit();


            header(
                "Location: " .
                BASE_URL .
                "/modules/barbeiros/index.php?sucesso=2"
            );

            exit();


        } catch (Exception $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $erros[] = 'Não foi possível atualizar o barbeiro.';
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

                    <h1>Editar Barbeiro</h1>

                    <p>
                        Atualize os dados do barbeiro no BarberFlow.
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


            <!-- ERROS -->

            <?php if (!empty($erros)): ?>

                <div class="alert alert-danger">

                    <strong>
                        Não foi possível salvar as alterações.
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


            <!-- FORMULÁRIO -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Dados do barbeiro</h2>

                        <span>
                            Altere as informações necessárias.
                        </span>

                    </div>

                </div>


                <form method="POST">


                    <!-- DADOS PESSOAIS -->

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
                                value="<?= htmlspecialchars($telefone ?? '') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="senha">
                                Nova senha
                            </label>

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Deixe vazio para manter a atual"
                            >

                            <small>
                                Preencha somente se quiser alterar a senha.
                            </small>

                        </div>

                    </div>


                    <!-- DADOS PROFISSIONAIS -->

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
                                value="<?= htmlspecialchars($especialidade ?? '') ?>"
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
                                Status *
                            </label>

                            <select
                                id="status"
                                name="status"
                                required
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
                            Salvar alterações
                        </button>

                    </div>


                </form>

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>