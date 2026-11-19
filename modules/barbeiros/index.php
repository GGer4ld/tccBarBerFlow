
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

$database = new Database();
$db = $database->conectar();

$busca = $_GET['busca'] ?? '';

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

        WHERE u.nome LIKE :busca
           OR u.email LIKE :busca
           OR u.telefone LIKE :busca

        ORDER BY u.nome ASC";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':busca',
    '%' . $busca . '%'
);

$stmt->execute();

$barbeiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_GET['sucesso'] ?? null;
$erro = $_GET['erro'] ?? null;

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <!-- MENSAGENS -->

            <?php if ($sucesso === '3'): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    Barbeiro excluído com sucesso.

                </div>

            <?php endif; ?>


            <?php if (!empty($erro)): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <h1>Barbeiros</h1>

                    <p>
                        Gerencie os barbeiros cadastrados na barbearia.
                    </p>

                </div>

                <a
                    href="<?= BASE_URL ?>/modules/barbeiros/criar.php"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-user-plus"></i>

                    Novo Barbeiro

                </a>

            </div>


            <!-- PESQUISA -->

            <div class="card">

                <form method="GET">

                    <div class="form-group">

                        <label for="busca">
                            Pesquisar barbeiro
                        </label>

                        <div class="search-box">

                            <input
                                type="text"
                                id="busca"
                                name="busca"
                                value="<?= htmlspecialchars($busca) ?>"
                                placeholder="Nome, e-mail ou telefone..."
                            >

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fa-solid fa-magnifying-glass"></i>

                                Pesquisar

                            </button>

                        </div>

                    </div>

                </form>

            </div>


            <!-- TABELA DE BARBEIROS -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Barbeiros cadastrados</h2>

                        <span>
                            <?= count($barbeiros) ?> barbeiro(s)
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
                                <th>Especialidade</th>
                                <th>Comissão</th>
                                <th>Status</th>
                                <th>Ações</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (empty($barbeiros)): ?>

                                <tr>

                                    <td colspan="8" class="text-center">

                                        Nenhum barbeiro encontrado.

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($barbeiros as $indice => $barbeiro): ?>

                                    <tr class="<?= $indice >= 3 ? 'barbeiro-oculto' : '' ?>">

                                        <!-- ID -->

                                        <td>
                                            <?= $barbeiro['id_barbeiro'] ?>
                                        </td>


                                        <!-- NOME -->

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars($barbeiro['nome']) ?>
                                            </strong>

                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <?= !empty($barbeiro['email'])
                                                ? htmlspecialchars($barbeiro['email'])
                                                : '—'
                                            ?>

                                        </td>


                                        <!-- TELEFONE -->

                                        <td>

                                            <?= !empty($barbeiro['telefone'])
                                                ? htmlspecialchars($barbeiro['telefone'])
                                                : '—'
                                            ?>

                                        </td>


                                        <!-- ESPECIALIDADE -->

                                        <td>

                                            <?= !empty($barbeiro['especialidade'])
                                                ? htmlspecialchars($barbeiro['especialidade'])
                                                : '—'
                                            ?>

                                        </td>


                                        <!-- COMISSÃO -->

                                        <td>

                                            <strong>

                                                <?= number_format(
                                                    (float) $barbeiro['percentual_comissao'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>%

                                            </strong>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php if ($barbeiro['status'] === 'Ativo'): ?>

                                                <span class="badge badge-success">

                                                    <i class="fa-solid fa-circle-check"></i>

                                                    Ativo

                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-danger">

                                                    <i class="fa-solid fa-circle-xmark"></i>

                                                    Inativo

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- AÇÕES -->

                                        <td>

                                            <div class="table-actions">

                                                <a
                                                    href="<?= BASE_URL ?>/modules/barbeiros/editar.php?id=<?= $barbeiro['id_barbeiro'] ?>"
                                                    class="btn btn-sm btn-secondary"
                                                    title="Editar"
                                                >

                                                    <i class="fa-solid fa-pen"></i>

                                                </a>


                                                <a
                                                    href="<?= BASE_URL ?>/modules/barbeiros/excluir.php?id=<?= $barbeiro['id_barbeiro'] ?>"
                                                    class="btn btn-sm btn-danger"
                                                    title="Excluir"
                                                    onclick="return confirm('Deseja realmente excluir este barbeiro?');"
                                                >

                                                    <i class="fa-solid fa-trash"></i>

                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- BOTÃO VER TODOS -->

                <?php if (count($barbeiros) > 3): ?>

                    <div class="text-center" style="margin-top: 15px;">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="btnTodosBarbeiros"
                        >

                            <i class="fa-solid fa-chevron-down"></i>

                            Ver todos os barbeiros

                        </button>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>


<style>

    .barbeiro-oculto {
        display: none;
    }

    .barbeiro-oculto.visivel {
        display: table-row;
    }

</style>


<script>

    document.addEventListener('DOMContentLoaded', function () {

        const botao = document.getElementById('btnTodosBarbeiros');

        if (!botao) {
            return;
        }

        let expandido = false;

        botao.addEventListener('click', function () {

            expandido = !expandido;

            document.querySelectorAll('.barbeiro-oculto').forEach(function (linha) {

                linha.classList.toggle('visivel', expandido);

            });

            if (expandido) {

                botao.innerHTML =
                    '<i class="fa-solid fa-chevron-up"></i> Mostrar menos';

            } else {

                botao.innerHTML =
                    '<i class="fa-solid fa-chevron-down"></i> Ver todos os barbeiros';

            }

        });

    });

</script>


<?php include __DIR__ . '/../../includes/footer.php'; ?>
