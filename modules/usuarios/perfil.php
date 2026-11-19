<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';


/*
|--------------------------------------------------------------------------
| IDENTIFICAÇÃO DO USUÁRIO
|--------------------------------------------------------------------------
*/

$idUsuario =
    (int) ($_SESSION['usuario']['id'] ?? 0);

$tipoUsuario =
    $_SESSION['usuario']['tipo'] ?? '';


if ($idUsuario <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        '/login.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CONEXÃO
|--------------------------------------------------------------------------
*/

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| VARIÁVEIS
|--------------------------------------------------------------------------
*/

$erro = '';
$sucesso = '';


$nome = '';
$email = '';
$telefone = '';
$status = '';
$tipo = '';


/*
|--------------------------------------------------------------------------
| BUSCAR USUÁRIO LOGADO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_usuario,
        nome,
        email,
        telefone,
        tipo_usuario,
        status
    FROM usuarios
    WHERE id_usuario = :id_usuario
    LIMIT 1
";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_usuario',
    $idUsuario,
    PDO::PARAM_INT
);

$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$usuario) {

    session_destroy();

    header(
        'Location: ' .
        BASE_URL .
        '/login.php'
    );

    exit;
}


$nome = $usuario['nome'];
$email = $usuario['email'];
$telefone = $usuario['telefone'] ?? '';
$status = $usuario['status'];
$tipo = $usuario['tipo_usuario'];


/*
|--------------------------------------------------------------------------
| ATUALIZAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim(
        $_POST['nome'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $telefone = trim(
        $_POST['telefone'] ?? ''
    );

    $senha = $_POST['senha'] ?? '';

    $confirmarSenha =
        $_POST['confirmar_senha'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($nome === '') {

        $erro =
            'Informe seu nome.';

    } elseif ($email === '') {

        $erro =
            'Informe seu e-mail.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro =
            'Informe um e-mail válido.';

    } elseif (
        $senha !== ''
        && strlen($senha) < 6
    ) {

        $erro =
            'A nova senha deve possuir pelo menos 6 caracteres.';

    } elseif (
        $senha !== ''
        && $senha !== $confirmarSenha
    ) {

        $erro =
            'A confirmação da senha não confere.';

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR E-MAIL
    |--------------------------------------------------------------------------
    */

    if ($erro === '') {

        $sql = "
            SELECT id_usuario
            FROM usuarios
            WHERE email = :email
            AND id_usuario <> :id_usuario
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(
            ':email',
            $email
        );

        $stmt->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $stmt->execute();


        if ($stmt->fetch()) {

            $erro =
                'Este e-mail já está sendo utilizado por outro usuário.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR DADOS
    |--------------------------------------------------------------------------
    */

    if ($erro === '') {

        try {

            if ($senha !== '') {

                $senhaHash =
                    password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );


                $sql = "
                    UPDATE usuarios
                    SET
                        nome = :nome,
                        email = :email,
                        telefone = :telefone,
                        senha = :senha
                    WHERE id_usuario = :id_usuario
                ";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(
                    ':nome',
                    $nome
                );

                $stmt->bindValue(
                    ':email',
                    $email
                );

                $stmt->bindValue(
                    ':telefone',
                    $telefone !== ''
                        ? $telefone
                        : null
                );

                $stmt->bindValue(
                    ':senha',
                    $senhaHash
                );

                $stmt->bindValue(
                    ':id_usuario',
                    $idUsuario,
                    PDO::PARAM_INT
                );

                $stmt->execute();

            } else {

                $sql = "
                    UPDATE usuarios
                    SET
                        nome = :nome,
                        email = :email,
                        telefone = :telefone
                    WHERE id_usuario = :id_usuario
                ";

                $stmt = $db->prepare($sql);

                $stmt->bindValue(
                    ':nome',
                    $nome
                );

                $stmt->bindValue(
                    ':email',
                    $email
                );

                $stmt->bindValue(
                    ':telefone',
                    $telefone !== ''
                        ? $telefone
                        : null
                );

                $stmt->bindValue(
                    ':id_usuario',
                    $idUsuario,
                    PDO::PARAM_INT
                );

                $stmt->execute();

            }


            /*
            |--------------------------------------------------------------------------
            | ATUALIZAR SESSÃO
            |--------------------------------------------------------------------------
            */

            $_SESSION['usuario']['nome'] =
                $nome;

            $_SESSION['usuario']['email'] =
                $email;


            $sucesso =
                'Perfil atualizado com sucesso!';


        } catch (PDOException $e) {

            $erro =
                'Não foi possível atualizar seu perfil.';
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


            <?php if ($sucesso !== ''): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= htmlspecialchars($sucesso) ?>

                </div>

            <?php endif; ?>


            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <div class="page-header">

                <div>

                    <h1>Meu Perfil</h1>

                    <p>
                        Gerencie suas informações pessoais e sua senha.
                    </p>

                </div>

            </div>


            <div class="card">


                <form
                    method="POST"
                    autocomplete="off"
                >


                    <div class="form-grid">


                        <!-- NOME -->

                        <div class="form-group">

                            <label for="nome">
                                Nome *
                            </label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                value="<?= htmlspecialchars($nome) ?>"
                                required
                            >

                        </div>


                        <!-- E-MAIL -->

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


                        <!-- TELEFONE -->

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
                            >

                        </div>


                        <!-- TIPO -->

                        <div class="form-group">

                            <label for="tipo_usuario">
                                Tipo de usuário
                            </label>

                            <input
                                type="text"
                                id="tipo_usuario"
                                value="<?= htmlspecialchars($tipo) ?>"
                                readonly
                            >

                        </div>


                        <!-- STATUS -->

                        <div class="form-group">

                            <label for="status">
                                Status
                            </label>

                            <input
                                type="text"
                                id="status"
                                value="<?= htmlspecialchars($status) ?>"
                                readonly
                            >

                        </div>


                    </div>


                    <hr
                        style="
                            margin: 30px 0;
                            border: 0;
                            border-top: 1px solid #E2E8F0;
                        "
                    >


                    <div style="margin-bottom: 20px;">

                        <h3
                            style="
                                margin-bottom: 6px;
                                font-size: 16px;
                            "
                        >
                            Alterar senha
                        </h3>

                        <p
                            style="
                                color: var(--text-light);
                                font-size: 13px;
                            "
                        >
                            Deixe os campos em branco caso não queira
                            alterar sua senha.
                        </p>

                    </div>


                    <div class="form-grid">


                        <!-- NOVA SENHA -->

                        <div class="form-group">

                            <label for="senha">
                                Nova senha
                            </label>

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                minlength="6"
                                autocomplete="new-password"
                            >

                        </div>


                        <!-- CONFIRMAÇÃO -->

                        <div class="form-group">

                            <label for="confirmar_senha">
                                Confirmar nova senha
                            </label>

                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                minlength="6"
                                autocomplete="new-password"
                            >

                        </div>


                    </div>


                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>/dashboard.php"
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