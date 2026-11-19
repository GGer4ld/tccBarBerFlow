<?php

require_once __DIR__ . '/app/config/database.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if (
        $nome === '' ||
        $email === '' ||
        $telefone === '' ||
        $senha === '' ||
        $confirmarSenha === ''
    ) {

        $erro = 'Preencha todos os campos.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } elseif (strlen($senha) < 6) {

        $erro = 'A senha deve possuir pelo menos 6 caracteres.';

    } elseif ($senha !== $confirmarSenha) {

        $erro = 'As senhas não coincidem.';

    } else {

        try {

            $database = new Database();
            $db = $database->conectar();

            /*
            |--------------------------------------------------------------------------
            | VERIFICA E-MAIL DUPLICADO
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT id_usuario
                    FROM usuarios
                    WHERE email = :email
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':email',
                $email
            );

            $stmt->execute();

            if ($stmt->fetch()) {

                $erro = 'Este e-mail já está cadastrado.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | INICIA TRANSAÇÃO
                |--------------------------------------------------------------------------
                */

                $db->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | CRIA CLIENTE
                |--------------------------------------------------------------------------
                */

                $sqlCliente = "INSERT INTO clientes
                    (
                        nome,
                        email,
                        telefone
                    )
                    VALUES
                    (
                        :nome,
                        :email,
                        :telefone
                    )";

                $stmtCliente = $db->prepare($sqlCliente);

                $stmtCliente->bindValue(
                    ':nome',
                    $nome
                );

                $stmtCliente->bindValue(
                    ':email',
                    $email
                );

                $stmtCliente->bindValue(
                    ':telefone',
                    $telefone
                );

                $stmtCliente->execute();

                $idCliente = $db->lastInsertId();

                /*
                |--------------------------------------------------------------------------
                | CRIA USUÁRIO
                |--------------------------------------------------------------------------
                */

                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $sqlUsuario = "INSERT INTO usuarios
                    (
                        id_cliente,
                        nome,
                        email,
                        senha,
                        telefone,
                        tipo_usuario,
                        status
                    )
                    VALUES
                    (
                        :id_cliente,
                        :nome,
                        :email,
                        :senha,
                        :telefone,
                        'Cliente',
                        'Ativo'
                    )";

                $stmtUsuario = $db->prepare($sqlUsuario);

                $stmtUsuario->bindValue(
                    ':id_cliente',
                    $idCliente,
                    PDO::PARAM_INT
                );

                $stmtUsuario->bindValue(
                    ':nome',
                    $nome
                );

                $stmtUsuario->bindValue(
                    ':email',
                    $email
                );

                $stmtUsuario->bindValue(
                    ':senha',
                    $senhaHash
                );

                $stmtUsuario->bindValue(
                    ':telefone',
                    $telefone
                );

                $stmtUsuario->execute();

                /*
                |--------------------------------------------------------------------------
                | CONFIRMA TRANSAÇÃO
                |--------------------------------------------------------------------------
                */

                $db->commit();

                /*
                |--------------------------------------------------------------------------
                | REDIRECIONA PARA LOGIN
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: login.php?cadastro=sucesso'
                );

                exit();
            }

        } catch (PDOException $e) {

            if (
                isset($db) &&
                $db->inTransaction()
            ) {

                $db->rollBack();
            }

            $erro = 'Não foi possível realizar o cadastro. Tente novamente.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cadastro | BarberFlow</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/login.css">

    <style>

        .cadastro-card {

            width: 100%;
            max-width: 480px;

        }

        .cadastro-card h2 {

            margin-bottom: 8px;

        }

        .cadastro-subtitle {

            margin-bottom: 25px;

            color: #64748B;

            font-size: 14px;

        }

        .cadastro-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;

        }

        .cadastro-grid .input-group:first-child {

            grid-column: 1 / -1;

        }

        .cadastro-button {

            width: 100%;

            margin-top: 8px;

        }

        .erro-cadastro {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background: #FEE2E2;

            color: #B91C1C;

            font-size: 13px;

        }

        .voltar-login {

            margin-top: 20px;

            text-align: center;

            font-size: 13px;

            color: #64748B;

        }

        .voltar-login a {

            color: #7C3AED;

            font-weight: 600;

            text-decoration: none;

        }

        .voltar-login a:hover {

            color: #6D28D9;

            text-decoration: underline;

        }

        @media (max-width: 600px) {

            .cadastro-grid {

                grid-template-columns: 1fr;

            }

            .cadastro-grid .input-group:first-child {

                grid-column: auto;

            }

        }

    </style>

</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-left">

            <h1>BarberFlow</h1>

            <p>
                Sistema profissional de gerenciamento para barbearias.
            </p>

        </div>

        <div class="login-right">

            <?php if ($erro !== ''): ?>

                <div class="erro-cadastro">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro); ?>

                </div>

            <?php endif; ?>

            <form
                class="login-card cadastro-card"
                action="cadastro.php"
                method="POST">

                <h2>Criar conta</h2>

                <p class="cadastro-subtitle">
                    Cadastre-se para acessar sua área de cliente.
                </p>

                <div class="cadastro-grid">

                    <div class="input-group">

                        <label>Nome completo</label>

                        <input
                            type="text"
                            name="nome"
                            placeholder="Digite seu nome"
                            value="<?= htmlspecialchars($_POST['nome'] ?? ''); ?>"
                            required>

                    </div>

                    <div class="input-group">

                        <label>E-mail</label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Digite seu e-mail"
                            value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required>

                    </div>

                    <div class="input-group">

                        <label>Telefone</label>

                        <input
                            type="text"
                            name="telefone"
                            placeholder="(00) 00000-0000"
                            value="<?= htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                            required>

                    </div>

                    <div class="input-group">

                        <label>Senha</label>

                        <input
                            type="password"
                            name="senha"
                            placeholder="Mínimo de 6 caracteres"
                            required>

                    </div>

                    <div class="input-group">

                        <label>Confirmar senha</label>

                        <input
                            type="password"
                            name="confirmar_senha"
                            placeholder="Digite a senha novamente"
                            required>

                    </div>

                </div>

                <button
                    type="submit"
                    class="cadastro-button">

                    Criar minha conta

                </button>

                <div class="voltar-login">

                    Já possui uma conta?

                    <a href="login.php">
                        Entrar
                    </a>

                </div>

            </form>

        </div>

    </div>

</body>

</html>