<?php

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/config.php';

$database = new Database();
$db = $database->conectar();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {

        $erro = 'Informe o seu e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Informe um e-mail válido.';
    } else {

        // ==================================================
        // BUSCAR USUÁRIO
        // ==================================================

        $sql = "SELECT
                    id_usuario,
                    nome,
                    email
                FROM usuarios
                WHERE email = :email
                  AND status = 'Ativo'
                LIMIT 1";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':email', $email);

        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($usuario) {

            // ==============================================
            // INVALIDAR TOKENS ANTERIORES
            // ==============================================

            $sql = "UPDATE recuperacao_senha
                    SET usado = 1
                    WHERE id_usuario = :id_usuario
                      AND usado = 0";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':id_usuario',
                $usuario['id_usuario'],
                PDO::PARAM_INT
            );

            $stmt->execute();


            // ==============================================
            // GERAR TOKEN SEGURO
            // ==============================================

            $token = bin2hex(random_bytes(32));

            // Token válido por 30 minutos
            $expiracao = date(
                'Y-m-d H:i:s',
                time() + (30 * 60)
            );


            // ==============================================
            // SALVAR TOKEN
            // ==============================================

            $sql = "INSERT INTO recuperacao_senha
                    (
                        id_usuario,
                        token,
                        expiracao
                    )
                    VALUES
                    (
                        :id_usuario,
                        :token,
                        :expiracao
                    )";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':id_usuario',
                $usuario['id_usuario'],
                PDO::PARAM_INT
            );

            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':expiracao', $expiracao);

            $stmt->execute();


            // ==============================================
            // LINK DE RECUPERAÇÃO
            // ==============================================

            $link = BASE_URL .
                '/redefinir-senha.php?token=' .
                urlencode($token);


            /*
             * Durante o desenvolvimento/TCC,
             * mostramos o link na tela.
             *
             * Em produção, esse link seria enviado
             * por e-mail.
             */

            $mensagem = $link;
        } else {

            /*
             * Não informamos se o e-mail existe ou não.
             * Isso evita revelar usuários cadastrados.
             */

            $mensagem =
                'Se o e-mail estiver cadastrado e ativo, ' .
                'um link de recuperação será disponibilizado.';
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

    <title>Recuperar senha - BarberFlow</title>

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
        href="<?= BASE_URL ?>/assets/css/variables.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/style.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/auth.css">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-header">

                <div class="auth-icon">

                    <i class="fa-solid fa-key"></i>

                </div>

                <h1>Recuperar senha</h1>

                <p>
                    Informe o e-mail associado à sua conta.
                </p>

            </div>


            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <?php if ($mensagem !== ''): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?php if (filter_var($mensagem, FILTER_VALIDATE_URL)): ?>

                        <strong>Link de recuperação gerado.</strong>

                        <br><br>

                        <a
                            href="<?= htmlspecialchars($mensagem) ?>">
                            Acessar recuperação de senha
                        </a>

                    <?php else: ?>

                        <?= htmlspecialchars($mensagem) ?>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <form method="POST">

                <div class="form-group">

                    <label for="email">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary">

                    <i class="fa-solid fa-paper-plane"></i>

                    Gerar link de recuperação

                </button>

            </form>


            <div class="auth-footer">

                <a href="<?= BASE_URL ?>/login.php">

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar para o login

                </a>

            </div>

        </div>

    </div>

</body>

</html>