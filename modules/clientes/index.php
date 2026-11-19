
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

// ==============================
// PERMISSÕES DO USUÁRIO
// ==============================

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

$ehAdministrador = in_array(
    $tipoUsuario,
    ['Admin', 'Administrador'],
    true
);

$podeCadastrar = in_array(
    $tipoUsuario,
    ['Admin', 'Administrador', 'Barbeiro'],
    true
);

// ==============================
// BUSCAR CLIENTES
// ==============================

$busca = $_GET['busca'] ?? '';

$sql = "SELECT
            id_cliente,
            nome,
            email,
            telefone,
            data_nascimento,
            observacoes
        FROM clientes
        WHERE nome LIKE :busca
           OR email LIKE :busca
           OR telefone LIKE :busca
        ORDER BY nome ASC";

$stmt = $db->prepare($sql);

$stmt->bindValue(':busca', '%' . $busca . '%');

$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <div class="page-header">

                <div>
                    <h1>Clientes</h1>

                    <p>
                        Gerencie os clientes cadastrados na barbearia.
                    </p>
                </div>

                <?php if ($podeCadastrar): ?>

                    <a
                        href="<?= BASE_URL ?>/modules/clientes/criar.php"
                        class="btn btn-primary">

                        <i class="fa-solid fa-user-plus"></i>
                        Novo Cliente

                    </a>

                <?php endif; ?>

            </div>

            <!-- PESQUISA -->

            <div class="card">

                <form method="GET">

                    <div class="form-group">

                        <label for="busca">
                            Pesquisar cliente
                        </label>

                        <div class="search-box">

                            <input
                                type="text"
                                id="busca"
                                name="busca"
                                value="<?= htmlspecialchars($busca) ?>"
                                placeholder="Nome, e-mail ou telefone...">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa-solid fa-magnifying-glass"></i>
                                Pesquisar

                            </button>

                        </div>

                    </div>

                </form>

            </div>

            <!-- TABELA DE CLIENTES -->

            <div class="card">

                <div class="card-header">

                    <div>
                        <h2>Clientes cadastrados</h2>

                        <span>
                            <?= count($clientes) ?> cliente(s)
                        </span>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Data de nascimento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($clientes)): ?>

                                <tr>
                                    <td colspan="6" class="text-center">
                                        Nenhum cliente encontrado.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($clientes as $indice => $cliente): ?>

                                    <tr class="<?= $indice >= 3 ? 'cliente-oculto' : '' ?>">

                                        <td>
                                            <?= $cliente['id_cliente'] ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($cliente['nome']) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= $cliente['email']
                                                ? htmlspecialchars($cliente['email'])
                                                : '—'
                                            ?>
                                        </td>

                                        <td>
                                            <?= $cliente['telefone']
                                                ? htmlspecialchars($cliente['telefone'])
                                                : '—'
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            if (!empty($cliente['data_nascimento'])) {
                                                echo date(
                                                    'd/m/Y',
                                                    strtotime($cliente['data_nascimento'])
                                                );
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>

                                        <td>

                                            <?php if ($ehAdministrador): ?>

                                                <div class="table-actions">

                                                    <a
                                                        href="<?= BASE_URL ?>/modules/clientes/editar.php?id=<?= $cliente['id_cliente'] ?>"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Editar">

                                                        <i class="fa-solid fa-pen"></i>

                                                    </a>

                                                    <a
                                                        href="<?= BASE_URL ?>/modules/clientes/excluir.php?id=<?= $cliente['id_cliente'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        title="Excluir"
                                                        onclick="return confirm('Deseja realmente excluir este cliente?');">

                                                        <i class="fa-solid fa-trash"></i>

                                                    </a>

                                                </div>

                                            <?php else: ?>

                                                <span>Consulta</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if (count($clientes) > 3): ?>

                    <div class="text-center" style="margin-top: 15px;">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="btnTodosClientes">

                            <i class="fa-solid fa-chevron-down"></i>
                            Ver todos os clientes

                        </button>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>

<style>
    .cliente-oculto {
        display: none;
    }

    .cliente-oculto.visivel {
        display: table-row;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const botao = document.getElementById('btnTodosClientes');

        if (!botao) {
            return;
        }

        let expandido = false;

        botao.addEventListener('click', function () {

            expandido = !expandido;

            document.querySelectorAll('.cliente-oculto').forEach(function (linha) {
                linha.classList.toggle('visivel', expandido);
            });

            if (expandido) {

                botao.innerHTML =
                    '<i class="fa-solid fa-chevron-up"></i> Mostrar menos';

            } else {

                botao.innerHTML =
                    '<i class="fa-solid fa-chevron-down"></i> Ver todos os clientes';

            }

        });

    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
