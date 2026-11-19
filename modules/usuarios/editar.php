<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['tipo'] !== 'Administrador'
) {
    header('Location: ../../dashboard.php');
    exit;
}


// Verifica se recebeu o ID
$id_usuario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_usuario) {
    header('Location: index.php');
    exit;
}


$erros = [];

$nome = '';
$email = '';
$telefone = '';
$tipo_usuario = '';
$status = '';
$id_cliente = null;

$clientesDisponiveis = [];


try {

    $database = new Database();
    $db = $database->conectar();


    /*
     * Busca o usuário.
     */

    $sql = "SELECT *
            FROM usuarios
            WHERE id_usuario = :id
            LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id',
        $id_usuario,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$usuario) {

        header('Location: index.php');
        exit;
    }


    /*
     * Preenche os dados atuais.
     */

    $nome = $usuario['nome'];
    $email = $usuario['email'];
    $telefone = $usuario['telefone'] ?? '';
    $tipo_usuario = $usuario['tipo_usuario'];
    $status = $usuario['status'];
    $id_cliente = $usuario['id_cliente'];


    /*
     * Busca clientes disponíveis.
     *
     * Inclui:
     * - clientes sem conta;
     * - o cliente atualmente vinculado
     *   a este usuário.
     */

    $sql = "SELECT
                c.id_cliente,
                c.nome,
                c.email,
                c.telefone
            FROM clientes c
            LEFT JOIN usuarios u
                ON u.id_cliente = c.id_cliente
                AND u.id_usuario != :id_usuario
            WHERE u.id_usuario IS NULL
            ORDER BY c.nome ASC";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_usuario',
        $id_usuario,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $clientesDisponiveis =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die('Erro ao carregar usuário.');

}


// Processamento do formulário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    $senha = $_POST['senha'] ?? '';
    $confirmar_senha =
        $_POST['confirmar_senha'] ?? '';

    $tipo_usuario =
        $_POST['tipo_usuario'] ?? '';

    $status =
        $_POST['status'] ?? '';

    $id_cliente =
        $_POST['id_cliente'] ?? '';


    /*
     * Validações
     */

    if ($nome === '') {

        $erros[] =
            'Informe o nome do usuário.';
    }


    if ($email === '') {

        $erros[] =
            'Informe o e-mail.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erros[] =
            'Informe um e-mail válido.';
    }


    /*
     * Senha é opcional na edição.
     */

    if ($senha !== '') {

        if (strlen($senha) < 6) {

            $erros[] =
                'A nova senha deve possuir pelo menos 6 caracteres.';
        }

        if ($senha !== $confirmar_senha) {

            $erros[] =
                'As senhas não coincidem.';
        }
    }


    /*
     * Tipos permitidos.
     */

    if (
        !in_array(
            $tipo_usuario,
            [
                'Administrador',
                'Barbeiro',
                'Cliente'
            ],
            true
        )
    ) {

        $erros[] =
            'Tipo de usuário inválido.';
    }


    /*
     * Status permitido.
     */

    if (
        !in_array(
            $status,
            [
                'Ativo',
                'Inativo'
            ],
            true
        )
    ) {

        $erros[] =
            'Status inválido.';
    }


    /*
     * Cliente precisa ter um cliente vinculado.
     */

    if ($tipo_usuario === 'Cliente') {

        if (
            $id_cliente === '' ||
            !filter_var(
                $id_cliente,
                FILTER_VALIDATE_INT
            )
        ) {

            $erros[] =
                'Selecione o cliente que será vinculado à conta.';

        } elseif ((int) $id_cliente <= 0) {

            $erros[] =
                'O cliente selecionado é inválido.';
        }

    } else {

        /*
         * Administrador e Barbeiro
         * não possuem cliente vinculado.
         */

        $id_cliente = null;
    }


    /*
     * Se não houver erros,
     * atualiza o usuário.
     */

    if (empty($erros)) {

        try {

            /*
             * Verifica se outro usuário
             * utiliza o mesmo e-mail.
             */

            $sql = "SELECT
                        id_usuario
                    FROM usuarios
                    WHERE email = :email
                    AND id_usuario != :id
                    LIMIT 1";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(
                ':email',
                $email
            );

            $stmt->bindValue(
                ':id',
                $id_usuario,
                PDO::PARAM_INT
            );

            $stmt->execute();


            if ($stmt->fetch()) {

                $erros[] =
                    'Outro usuário já utiliza este e-mail.';

            } else {


                /*
                 * Se for Cliente,
                 * verifica o vínculo.
                 */

                if ($tipo_usuario === 'Cliente') {

                    $sql = "SELECT
                                c.id_cliente
                            FROM clientes c
                            LEFT JOIN usuarios u
                                ON u.id_cliente = c.id_cliente
                                AND u.id_usuario != :id_usuario
                            WHERE c.id_cliente = :id_cliente
                            AND u.id_usuario IS NULL
                            LIMIT 1";

                    $stmt = $db->prepare($sql);

                    $stmt->bindValue(
                        ':id_usuario',
                        $id_usuario,
                        PDO::PARAM_INT
                    );

                    $stmt->bindValue(
                        ':id_cliente',
                        (int) $id_cliente,
                        PDO::PARAM_INT
                    );

                    $stmt->execute();

                    $cliente =
                        $stmt->fetch(PDO::FETCH_ASSOC);


                    if (!$cliente) {

                        $erros[] =
                            'O cliente selecionado não existe ou já possui outra conta.';
                    }
                }


                /*
                 * Continua somente se tudo estiver válido.
                 */

                if (empty($erros)) {


                    /*
                     * Com nova senha.
                     */

                    if ($senha !== '') {

                        $senha_hash =
                            password_hash(
                                $senha,
                                PASSWORD_DEFAULT
                            );


                        $sql = "UPDATE usuarios
                                SET
                                    id_cliente = :id_cliente,
                                    nome = :nome,
                                    email = :email,
                                    telefone = :telefone,
                                    senha = :senha,
                                    tipo_usuario = :tipo_usuario,
                                    status = :status
                                WHERE id_usuario = :id";


                        $stmt =
                            $db->prepare($sql);


                        $stmt->bindValue(
                            ':senha',
                            $senha_hash
                        );

                    } else {


                        /*
                         * Mantém a senha atual.
                         */

                        $sql = "UPDATE usuarios
                                SET
                                    id_cliente = :id_cliente,
                                    nome = :nome,
                                    email = :email,
                                    telefone = :telefone,
                                    tipo_usuario = :tipo_usuario,
                                    status = :status
                                WHERE id_usuario = :id";


                        $stmt =
                            $db->prepare($sql);
                    }


                    /*
                     * ID do cliente.
                     */

                    if ($id_cliente === null) {

                        $stmt->bindValue(
                            ':id_cliente',
                            null,
                            PDO::PARAM_NULL
                        );

                    } else {

                        $stmt->bindValue(
                            ':id_cliente',
                            (int) $id_cliente,
                            PDO::PARAM_INT
                        );
                    }


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
                        $telefone
                    );

                    $stmt->bindValue(
                        ':tipo_usuario',
                        $tipo_usuario
                    );

                    $stmt->bindValue(
                        ':status',
                        $status
                    );

                    $stmt->bindValue(
                        ':id',
                        $id_usuario,
                        PDO::PARAM_INT
                    );


                    $stmt->execute();


                    header(
                        'Location: index.php?editado=1'
                    );

                    exit;
                }
            }


        } catch (PDOException $e) {

            $erros[] =
                'Não foi possível atualizar o usuário.';
        }
    }
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

                    <h1>Editar usuário</h1>

                    <p>
                        Atualize os dados e permissões deste usuário.
                    </p>

                </div>

                <a
                    href="index.php"
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

                        <i class="fa-solid fa-user-pen"></i>

                        <div>

                            <h2>Dados do usuário</h2>

                            <p>
                                Altere as informações necessárias.
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

                        </div>


                        <div class="form-group">

                            <label for="confirmar_senha">
                                Confirmar nova senha
                            </label>

                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                placeholder="Repita a nova senha"
                            >

                        </div>


                        <div class="form-group">

                            <label for="tipo_usuario">
                                Tipo de usuário
                            </label>

                            <select
                                id="tipo_usuario"
                                name="tipo_usuario"
                                required
                            >

                                <option
                                    value="Administrador"
                                    <?= $tipo_usuario === 'Administrador' ? 'selected' : '' ?>
                                >
                                    Administrador
                                </option>

                                <option
                                    value="Barbeiro"
                                    <?= $tipo_usuario === 'Barbeiro' ? 'selected' : '' ?>
                                >
                                    Barbeiro
                                </option>

                                <option
                                    value="Cliente"
                                    <?= $tipo_usuario === 'Cliente' ? 'selected' : '' ?>
                                >
                                    Cliente
                                </option>

                            </select>

                        </div>


                        <div
                            class="form-group"
                            id="cliente-container"
                            style="<?= $tipo_usuario === 'Cliente' ? '' : 'display:none;' ?>"
                        >

                            <label for="id_cliente">
                                Cliente vinculado
                            </label>

                            <select
                                id="id_cliente"
                                name="id_cliente"
                                <?= $tipo_usuario === 'Cliente' ? 'required' : '' ?>
                            >

                                <option value="">
                                    Selecione um cliente
                                </option>

                                <?php foreach ($clientesDisponiveis as $cliente): ?>

                                    <option
                                        value="<?= (int) $cliente['id_cliente'] ?>"
                                        <?= (string) $id_cliente === (string) $cliente['id_cliente'] ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($cliente['nome']) ?>

                                        <?php if (!empty($cliente['telefone'])): ?>

                                            —
                                            <?= htmlspecialchars($cliente['telefone']) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if (empty($clientesDisponiveis)): ?>

                                <small
                                    style="display:block; margin-top:8px;"
                                >
                                    Nenhum cliente disponível para vinculação.
                                </small>

                            <?php endif; ?>

                        </div>


                        <div class="form-group">

                            <label for="status">
                                Status
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

                </div>


                <div class="form-actions">

                    <a
                        href="index.php"
                        class="btn-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Salvar alterações
                    </button>

                </div>

            </form>

        </main>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const tipoUsuario =
            document.getElementById('tipo_usuario');

        const clienteContainer =
            document.getElementById('cliente-container');

        const clienteSelect =
            document.getElementById('id_cliente');


        function atualizarCliente() {

            if (
                tipoUsuario.value === 'Cliente'
            ) {

                clienteContainer.style.display =
                    '';

                clienteSelect.required =
                    true;

            } else {

                clienteContainer.style.display =
                    'none';

                clienteSelect.required =
                    false;

                clienteSelect.value =
                    '';
            }
        }


        tipoUsuario.addEventListener(
            'change',
            atualizarCliente
        );


        atualizarCliente();

    }
);

</script>


<?php include '../../includes/footer.php'; ?>