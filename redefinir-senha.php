<?php

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/config.php';

$database = new Database();
$db = $database->conectar();

$mensagem = '';
$erro = '';
$sucesso = false;

// 1. VERIFICAR SE O TOKEN EXISTE NA URL
$token = trim($_GET['token'] ?? '');

if ($token === '') {
    $erro = 'Token de recuperação inválido ou não fornecido.';
} else {
    // 2. BUSCAR O TOKEN NO BANCO DE DADOS
    $sql = "SELECT 
                r.id_recuperacao,
                r.id_usuario,
                r.expiracao
            FROM recuperacao_senha r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.token = :token 
              AND r.usado = 0 
              AND u.status = 'Ativo'
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $tokenBanco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenBanco) {
        $erro = 'Este link de recuperação é inválido ou já foi utilizado.';
    } else {
        // 3. VERIFICAR SE O TOKEN JÁ EXPIROU
        $agora = date('Y-m-d H:i:s');
        if ($tokenBanco['expiracao'] < $agora) {
            $erro = 'Este link de recuperação expirou (validade de 30 minutos).';
        }
    }
}

// 4. PROCESSAR O FORMULÁRIO DE NOVA SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (strlen($nova_senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = 'As senhas não coincidem. Digite novamente.';
    } else {
        try {
            // Inicia uma transação no banco por segurança
            $db->beginTransaction();

            // Criptografar a nova senha de forma segura (Bcrypt)
            $senha_criptografada = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Atualizar a senha do usuário
            $sql = "UPDATE usuarios 
                    SET senha = :senha 
                    WHERE id_usuario = :id_usuario";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':senha', $senha_criptografada);
            $stmt->bindValue(':id_usuario', $tokenBanco['id_usuario'], PDO::PARAM_INT);
            $stmt->execute();

            // Marcar o token atual como USADO
            $sql = "UPDATE recuperacao_senha
        SET usado = 1
        WHERE id_recuperacao = :id_recuperacao";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':id_recuperacao',
                $tokenBanco['id_recuperacao'],
                PDO::PARAM_INT
            );

            $stmt->execute();

            $db->commit();

            $mensagem = 'Senha redefinida com sucesso! Você já pode fazer login.';
            $sucesso = true;
        } catch (Exception $e) {
            $db->rollBack();
            $erro = 'Ocorreu um erro ao salvar a nova senha. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
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
                <h1>Nova Senha</h1>
                <p>Crie uma nova senha de acesso para sua conta.</p>
            </div>

            <!-- Exibição de Erros -->
            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- Exibição de Sucesso -->
            <?php if ($mensagem !== '' && $sucesso): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <!-- O formulário só aparece se não houver erro de token e se ainda não foi alterado com sucesso -->
            <?php if ($erro === '' && !$sucesso): ?>
                <form method="POST">

                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input
                            type="password"
                            id="nova_senha"
                            name="nova_senha"
                            placeholder="Mínimo 6 caracteres"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="confirma_senha">Confirmar Nova Senha</label>
                        <input
                            type="password"
                            id="confirma_senha"
                            name="confirma_senha"
                            placeholder="Repita a nova senha"
                            required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i>
                        Alterar Senha
                    </button>

                </form>
            <?php endif; ?>

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