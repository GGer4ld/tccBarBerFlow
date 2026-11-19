<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$busca = $_GET['busca'] ?? '';

$sql = "SELECT
            id_servico,
            nome,
            descricao,
            duracao,
            preco,
            percentual_comissao,
            status
        FROM servicos
        WHERE nome LIKE :busca
           OR descricao LIKE :busca
        ORDER BY nome ASC";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':busca',
    '%' . $busca . '%'
);

$stmt->execute();

$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_GET['sucesso'] ?? '';
$erro = $_GET['erro'] ?? '';

$mensagemSucesso = '';

if ($sucesso === '1') {
    $mensagemSucesso = 'Serviço cadastrado com sucesso!';
} elseif ($sucesso === '2') {
    $mensagemSucesso = 'Serviço atualizado com sucesso!';
} elseif ($sucesso === '3') {
    $mensagemSucesso = 'Serviço excluído com sucesso!';
}


?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <?php if ($mensagemSucesso !== ''): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= htmlspecialchars($mensagemSucesso) ?>

                </div>

            <?php endif; ?>


            <?php if ($erro === 'excluir'): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Não foi possível excluir este serviço.
                    Ele pode estar sendo utilizado em algum agendamento.

                </div>

            <?php endif; ?>


            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <h1>Serviços</h1>

                    <p>
                        Gerencie os serviços oferecidos pela barbearia.
                    </p>

                </div>

                <?php if ($_SESSION['usuario']['tipo'] === 'Administrador'): ?>

                    <a
                        href="<?= BASE_URL ?>/modules/servicos/criar.php"
                        class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Novo Serviço
                    </a>

                <?php endif; ?>

            </div>


            <!-- PESQUISA -->

            <div class="card">

                <form method="GET">

                    <div class="form-group">

                        <label for="busca">
                            Pesquisar serviço
                        </label>

                        <div class="search-box">

                            <input
                                type="text"
                                id="busca"
                                name="busca"
                                value="<?= htmlspecialchars($busca) ?>"
                                placeholder="Nome ou descrição...">

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


            <!-- TABELA -->

            <div class="card">

                <div class="card-header">

                    <div>

                        <h2>Serviços cadastrados</h2>

                        <span>
                            <?= count($servicos) ?> serviço(s)
                        </span>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Serviço</th>

                                <th>Descrição</th>

                                <th>Duração</th>

                                <th>Preço</th>

                                <th>Comissão</th>

                                <th>Status</th>

                                <th>Ações</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (empty($servicos)): ?>

                                <tr>

                                    <td
                                        colspan="8"
                                        class="text-center">
                                        Nenhum serviço encontrado.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($servicos as $servico): ?>

                                    <tr>

                                        <td>
                                            <?= $servico['id_servico'] ?>
                                        </td>


                                        <td>

                                            <strong>
                                                <?= htmlspecialchars($servico['nome']) ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <?php if (!empty($servico['descricao'])): ?>

                                                <?= htmlspecialchars($servico['descricao']) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= (int) $servico['duracao'] ?> min

                                        </td>


                                        <td>

                                            R$
                                            <?= number_format(
                                                (float) $servico['preco'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= number_format(
                                                (float) $servico['percentual_comissao'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>%

                                        </td>


                                        <td>

                                            <?php if ($servico['status'] === 'Ativo'): ?>

                                                <span class="badge badge-success">
                                                    Ativo
                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-danger">
                                                    Inativo
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?php if ($_SESSION['usuario']['tipo'] === 'Administrador'): ?>

                                                <div class="table-actions">

                                                    <a
                                                        href="<?= BASE_URL ?>/modules/servicos/editar.php?id=<?= $servico['id_servico'] ?>"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Editar">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <a
                                                        href="<?= BASE_URL ?>/modules/servicos/excluir.php?id=<?= $servico['id_servico'] ?>"
                                                        class="btn btn-sm btn-danger"
                                                        title="Excluir"
                                                        onclick="return confirm('Deseja realmente excluir este serviço?');">
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

            </div>

        </main>

    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>