<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

$database = new Database();
$db = $database->conectar();

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['tipo'] !== 'Administrador'
) {
    header('Location: ../../dashboard.php');
    exit;
}

$erros = [];

$nome = '';
$email = '';
$telefone = '';
$tipo_usuario = 'Barbeiro';
$status = 'Ativo';
$id_cliente = '';

try {

    $database = new Database();
    $db = $database->conectar();

    /*
     * Carrega os clientes disponíveis para vinculação.
     *
     * Só serão exibidos clientes que ainda não possuem
     * uma conta de usuário vinculada.
     */
    $sql = "SELECT
                c.id_cliente,
                c.nome,
                c.email,
                c.telefone
            FROM clientes c
            LEFT JOIN usuarios u
                ON u.id_cliente = c.id_cliente
            WHERE u.id_usuario IS NULL
            ORDER BY c.nome ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $clientesDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $clientesDisponiveis = [];
    $erros[] = 'Não foi possível carregar os clientes.';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? 'Barbeiro';
    $status = $_POST['status'] ?? 'Ativo';
    $id_cliente = $_POST['id_cliente'] ?? '';

    /*
     * Validações
     */

    if ($nome === '') {
        $erros[] = 'Informe o nome do usuário.';
    }

    if ($email === '') {

        $erros[] = 'Informe o e-mail.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erros[] = 'Informe um e-mail válido.';
    }

    if ($senha === '') {

        $erros[] = 'Informe uma senha.';

    } elseif (strlen($senha) < 6) {

        $erros[] = 'A senha deve possuir pelo menos 6 caracteres.';
    }

    if ($senha !== $confirmar_senha) {
        $erros[] = 'As senhas não coincidem.';
    }

    if (
        !in_array(
            $tipo_usuario,
            ['Administrador', 'Barbeiro', 'Cliente'],
            true
        )
    ) {
        $erros[] = 'Tipo de usuário inválido.';
    }

    if (
        !in_array(
            $status,
            ['Ativo', 'Inativo'],
            true
        )
    ) {
        $erros[] = 'Status inválido.';
    }

    /*
     * Cliente precisa obrigatoriamente estar
     * vinculado a um cadastro existente.
     */
    if ($tipo_usuario === 'Cliente') {

        if (
            $id_cliente === '' ||
            !filter_var($id_cliente, FILTER_VALIDATE_INT)
        ) {

            $erros[] =
                'Selecione o cliente que será vinculado à conta.';

        } elseif ((int) $id_cliente <= 0) {

            $erros[] =
                'O cliente selecionado é inválido.';
        }

    } else {

        /*
         * Administrador e Barbeiro não possuem
         * vínculo com a tabela clientes.
         */
        $id_cliente = null;
    }


    /*
     * Se não houver erros, salva no banco.
     */

    if (empty($erros)) {

        try {

            /*
             * Verifica e-mail duplicado.
             */
            $sql = "SELECT
                        id_usuario
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

                $erros[] =
                    'Já existe um usuário cadastrado com este e-mail.';

            } else {

                /*
                 * Se for Cliente, verifica novamente
                 * se o cliente existe e se ainda não
                 * possui uma conta.
                 */
                if ($tipo_usuario === 'Cliente') {

                    $sql = "SELECT
                                c.id_cliente,
                                c.nome
                            FROM clientes c
                            LEFT JOIN usuarios u
                                ON u.id_cliente = c.id_cliente
                            WHERE c.id_cliente = :id_cliente
                            AND u.id_usuario IS NULL
                            LIMIT 1";

                    $stmt = $db->prepare($sql);

                    $stmt->bindValue(
                        ':id_cliente',
                        (int) $id_cliente,
                        PDO::PARAM_INT
                    );

                    $stmt->execute();

                    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$cliente) {

                        $erros[] =
                            'O cliente selecionado não existe ou já possui uma conta.';
                    }
                }


                if (empty($erros)) {

                    /*
                     * Criptografa a senha.
                     */
                    $senha_hash = password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );

                    /*
                     * Cria o usuário.
                     */
                    $sql = "INSERT INTO usuarios
                            (
                                id_cliente,
                                nome,
                                email,
                                telefone,
                                senha,
                                tipo_usuario,
                                status
                            )
                            VALUES
                            (
                                :id_cliente,
                                :nome,
                                :email,
                                :telefone,
                                :senha,
                                :tipo_usuario,
                                :status
                            )";

                    $stmt = $db->prepare($sql);

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
                        ':senha',
                        $senha_hash
                    );

                    $stmt->bindValue(
                        ':tipo_usuario',
                        $tipo_usuario
                    );

                    $stmt->bindValue(
                        ':status',
                        $status
                    );

                    $stmt->execute();

                    header(
                        'Location: index.php?sucesso=1'
                    );

                    exit;
                }
            }

        } catch (PDOException $e) {

            $erros[] =
                'Não foi possível cadastrar o usuário.';
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

                    <h1>Novo usuário</h1>

                    <p>
                        Cadastre um novo usuário para acessar o BarberFlow.
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

                        <i class="fa-solid fa-user"></i>

                        <div>

                            <h2>Dados do usuário</h2>

                            <p>
                                Informações básicas de acesso.
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
                                placeholder="(00) 00000-0000"
                            >

                        </div>


                        <div class="form-group">

                            <label for="senha">
                                Senha
                            </label>

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Mínimo de 6 caracteres"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="confirmar_senha">
                                Confirmar senha
                            </label>

                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                placeholder="Digite a senha novamente"
                                required
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
                                            — <?= htmlspecialchars($cliente['telefone']) ?>
                                        <?php endif; ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if (empty($clientesDisponiveis)): ?>

                                <small style="display:block; margin-top:8px;">
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
                        <i class="fa-solid fa-check"></i>
                        Cadastrar usuário
                    </button>

                </div>

            </form>

        </main>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const tipoUsuario =
        document.getElementById('tipo_usuario');

    const clienteContainer =
        document.getElementById('cliente-container');

    const clienteSelect =
        document.getElementById('id_cliente');


    function atualizarCliente() {

        if (tipoUsuario.value === 'Cliente') {

            clienteContainer.style.display = '';

            clienteSelect.required = true;

        } else {

            clienteContainer.style.display = 'none';

            clienteSelect.required = false;

            clienteSelect.value = '';
        }
    }


    tipoUsuario.addEventListener(
        'change',
        atualizarCliente
    );


    atualizarCliente();

});

</script>

<?php include '../../includes/footer.php'; ?>