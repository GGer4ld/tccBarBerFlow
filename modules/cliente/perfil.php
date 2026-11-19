<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

if (($_SESSION['usuario']['tipo'] ?? '') !== 'Cliente') {
    header('Location: ' . BASE_URL . '/dashboard.php?erro=acesso_negado');
    exit();
}

$idCliente = $_SESSION['usuario']['id_cliente'] ?? null;

if (!$idCliente) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit();
}

$database = new Database();
$db = $database->conectar();

$mensagem = '';
$tipoMensagem = '';

/*
|--------------------------------------------------------------------------
| Atualização do perfil
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($nome === '' || $email === '') {

        $mensagem = 'Nome e e-mail são obrigatórios.';
        $tipoMensagem = 'erro';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = 'Informe um e-mail válido.';
        $tipoMensagem = 'erro';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Verifica se o e-mail já está sendo usado por outro usuário
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT id_usuario
                FROM usuarios
                WHERE email = :email
                AND id_usuario != :id_usuario
                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':email', $email);
        $stmt->bindValue(
            ':id_usuario',
            $_SESSION['usuario']['id'] ?? 0,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $emailUsuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        /*
        |--------------------------------------------------------------------------
        | Verifica se o e-mail pertence a outro cliente
        |--------------------------------------------------------------------------
        */

        $sql = "SELECT id_cliente
                FROM clientes
                WHERE email = :email
                AND id_cliente != :id_cliente
                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':email', $email);
        $stmt->bindValue(
            ':id_cliente',
            $idCliente,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $emailClienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emailUsuarioExistente) {

            $mensagem = 'Este e-mail já está sendo utilizado por outro usuário.';
            $tipoMensagem = 'erro';

        } elseif ($emailClienteExistente) {

            $mensagem = 'Este e-mail já está cadastrado para outro cliente.';
            $tipoMensagem = 'erro';

        } else {

            try {

                $db->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | Atualiza cliente
                |--------------------------------------------------------------------------
                */

                $sql = "UPDATE clientes
                        SET
                            nome = :nome,
                            email = :email,
                            telefone = :telefone,
                            data_nascimento = :data_nascimento,
                            observacoes = :observacoes
                        WHERE id_cliente = :id_cliente";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':nome', $nome);
                $stmt->bindValue(':email', $email);

                $stmt->bindValue(
                    ':telefone',
                    $telefone !== '' ? $telefone : null,
                    $telefone !== ''
                        ? PDO::PARAM_STR
                        : PDO::PARAM_NULL
                );

                $stmt->bindValue(
                    ':data_nascimento',
                    $dataNascimento !== '' ? $dataNascimento : null,
                    $dataNascimento !== ''
                        ? PDO::PARAM_STR
                        : PDO::PARAM_NULL
                );

                $stmt->bindValue(
                    ':observacoes',
                    $observacoes !== '' ? $observacoes : null,
                    $observacoes !== ''
                        ? PDO::PARAM_STR
                        : PDO::PARAM_NULL
                );

                $stmt->bindValue(
                    ':id_cliente',
                    $idCliente,
                    PDO::PARAM_INT
                );

                $stmt->execute();

                /*
                |--------------------------------------------------------------------------
                | Mantém usuário sincronizado
                |--------------------------------------------------------------------------
                */

                $sql = "UPDATE usuarios
                        SET
                            nome = :nome,
                            email = :email,
                            telefone = :telefone
                        WHERE id_usuario = :id_usuario
                        AND id_cliente = :id_cliente
                        AND tipo_usuario = 'Cliente'";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(':nome', $nome);
                $stmt->bindValue(':email', $email);

                $stmt->bindValue(
                    ':telefone',
                    $telefone !== '' ? $telefone : null,
                    $telefone !== ''
                        ? PDO::PARAM_STR
                        : PDO::PARAM_NULL
                );

                $stmt->bindValue(
                    ':id_usuario',
                    $_SESSION['usuario']['id'],
                    PDO::PARAM_INT
                );

                $stmt->bindValue(
                    ':id_cliente',
                    $idCliente,
                    PDO::PARAM_INT
                );

                $stmt->execute();

                $db->commit();

                /*
                |--------------------------------------------------------------------------
                | Atualiza sessão
                |--------------------------------------------------------------------------
                */

                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['email'] = $email;

                $mensagem = 'Perfil atualizado com sucesso.';
                $tipoMensagem = 'sucesso';

            } catch (PDOException $e) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $mensagem = 'Não foi possível atualizar o perfil.';
                $tipoMensagem = 'erro';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Busca dados atuais do cliente
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_cliente,
            nome,
            email,
            telefone,
            data_nascimento,
            observacoes
        FROM clientes
        WHERE id_cliente = :id_cliente
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_cliente',
    $idCliente,
    PDO::PARAM_INT
);

$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit();
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
                    <h1>Meu perfil</h1>
                    <p>Visualize e atualize seus dados pessoais.</p>
                </div>

            </div>

            <?php if ($mensagem !== ''): ?>

                <div
                    class="alert <?= $tipoMensagem === 'sucesso'
                        ? 'alert-success'
                        : 'alert-danger'; ?>">

                    <i
                        class="fa-solid <?= $tipoMensagem === 'sucesso'
                            ? 'fa-circle-check'
                            : 'fa-circle-exclamation'; ?>">
                    </i>

                    <span>
                        <?= htmlspecialchars($mensagem); ?>
                    </span>

                </div>

            <?php endif; ?>

            <div class="card">

                <div class="card-header">

                    <div>
                        <h2>Dados pessoais</h2>

                        <p>
                            Essas informações serão utilizadas nos seus
                            agendamentos.
                        </p>
                    </div>

                    <div class="profile-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                </div>

                <form method="POST">

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="nome">
                                Nome completo
                            </label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    $cliente['nome'] ?? ''
                                ); ?>"
                                required>

                        </div>

                        <div class="form-group">

                            <label for="email">
                                E-mail
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    $cliente['email'] ?? ''
                                ); ?>"
                                required>

                        </div>

                        <div class="form-group">

                            <label for="telefone">
                                Telefone
                            </label>

                            <input
                                type="text"
                                id="telefone"
                                name="telefone"
                                maxlength="20"
                                value="<?= htmlspecialchars(
                                    $cliente['telefone'] ?? ''
                                ); ?>"
                                placeholder="(11) 99999-9999">

                        </div>

                        <div class="form-group">

                            <label for="data_nascimento">
                                Data de nascimento
                            </label>

                            <input
                                type="date"
                                id="data_nascimento"
                                name="data_nascimento"
                                value="<?= htmlspecialchars(
                                    $cliente['data_nascimento'] ?? ''
                                ); ?>">

                        </div>

                        <div class="form-group form-group-full">

                            <label for="observacoes">
                                Observações
                            </label>

                            <textarea
                                id="observacoes"
                                name="observacoes"
                                rows="5"
                                maxlength="500"
                                placeholder="Alguma informação que deseja registrar..."><?= htmlspecialchars(
                                    $cliente['observacoes'] ?? ''
                                ); ?></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/modules/cliente/index.php"
                            class="btn btn-secondary">

                            <i class="fa-solid fa-arrow-left"></i>

                            Voltar

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

<style>

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 25px;
    }

    .card-header h2 {
        margin: 0 0 6px;
        font-size: 20px;
    }

    .card-header p {
        margin: 0;
        color: #64748B;
        font-size: 14px;
    }

    .profile-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #F3E8FF;
        color: #7E22CE;

        font-size: 20px;
        flex-shrink: 0;
    }

    .form-group-full {
        grid-column: 1 / -1;
    }

    .form-group textarea {
        width: 100%;
        resize: vertical;
        min-height: 120px;
    }

    .alert {
        display: flex;
        align-items: center;
        gap: 10px;

        margin-bottom: 20px;
        padding: 14px 16px;

        border-radius: 10px;
        font-size: 14px;
    }

    .alert-success {
        background: #DCFCE7;
        color: #166534;
    }

    .alert-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    @media (max-width: 768px) {

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-group-full {
            grid-column: auto;
        }

        .card-header {
            align-items: flex-start;
        }

        .form-actions {
            flex-direction: column-reverse;
        }

        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }

    }

</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>