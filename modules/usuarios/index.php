<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

// Apenas Administrador pode acessar este módulo
if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['tipo'] !== 'Administrador'
) {
    header('Location: ../../dashboard.php');
    exit;
}


$database = new Database();
$db = $database->conectar();


$sql = "SELECT
            id_usuario,
            nome,
            email,
            telefone,
            tipo_usuario,
            status,
            data_cadastro
        FROM usuarios
        ORDER BY id_usuario DESC";

$stmt = $db->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../includes/header.php'; ?>

<div class="app">

    <?php include '../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include '../../includes/navbar.php'; ?>

        <?php if (isset($_GET['sucesso'])): ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    Usuário cadastrado com sucesso!
                </span>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['editado'])): ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    Usuário atualizado com sucesso!
                </span>

            </div>

        <?php endif; ?>

        <main class="content">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:30px;
            ">

                <div>
                    <h1>Usuários</h1>

                    <p style="color:#64748B; margin-top:5px;">
                        Gerencie os usuários que possuem acesso ao BarberFlow.
                    </p>
                </div>

                <a
                    href="criar.php"
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:8px;
                        padding:12px 18px;
                        background:#2563EB;
                        color:white;
                        border-radius:10px;
                        font-weight:600;
                    ">
                    <i class="fa-solid fa-plus"></i>
                    Novo usuário
                </a>

            </div>


            <div style="
                background:white;
                border-radius:16px;
                padding:25px;
                box-shadow:0 10px 30px rgba(0,0,0,.06);
                overflow-x:auto;
            ">

                <table style="
                    width:100%;
                    border-collapse:collapse;
                ">

                    <thead>

                        <tr style="
                            border-bottom:2px solid #E2E8F0;
                            text-align:left;
                        ">

                            <th style="padding:14px;">Nome</th>
                            <th style="padding:14px;">E-mail</th>
                            <th style="padding:14px;">Telefone</th>
                            <th style="padding:14px;">Perfil</th>
                            <th style="padding:14px;">Status</th>
                            <th style="padding:14px;">Cadastro</th>
                            <th style="padding:14px;">Ações</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($usuarios)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    style="
                                        padding:30px;
                                        text-align:center;
                                        color:#64748B;
                                    ">
                                    Nenhum usuário cadastrado.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($usuarios as $usuario): ?>

                                <tr style="
                                    border-bottom:1px solid #E2E8F0;
                                ">

                                    <td style="padding:14px;">
                                        <?= htmlspecialchars($usuario['nome']) ?>
                                    </td>

                                    <td style="padding:14px;">
                                        <?= htmlspecialchars($usuario['email']) ?>
                                    </td>

                                    <td style="padding:14px;">
                                        <?= htmlspecialchars($usuario['telefone'] ?? '-') ?>
                                    </td>

                                    <td style="padding:14px;">

                                        <?php if ($usuario['tipo_usuario'] === 'Administrador'): ?>

                                            <span style="
        background:#DBEAFE;
        color:#1D4ED8;
        padding:5px 10px;
        border-radius:20px;
        font-size:13px;
        font-weight:600;
    ">
                                                <i class="fa-solid fa-shield-halved"></i>
                                                Administrador
                                            </span>

                                        <?php elseif ($usuario['tipo_usuario'] === 'Barbeiro'): ?>

                                            <span style="
        background:#DCFCE7;
        color:#15803D;
        padding:5px 10px;
        border-radius:20px;
        font-size:13px;
        font-weight:600;
    ">
                                                <i class="fa-solid fa-scissors"></i>
                                                Barbeiro
                                            </span>

                                        <?php elseif ($usuario['tipo_usuario'] === 'Cliente'): ?>

                                            <span style="
        background:#F3E8FF;
        color:#7E22CE;
        padding:5px 10px;
        border-radius:20px;
        font-size:13px;
        font-weight:600;
    ">
                                                <i class="fa-solid fa-user"></i>
                                                Cliente
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td style="padding:14px;">

                                        <?php if ($usuario['status'] === 'Ativo'): ?>

                                            <span style="
                                                color:#15803D;
                                                font-weight:600;
                                            ">
                                                Ativo
                                            </span>

                                        <?php else: ?>

                                            <span style="
                                                color:#DC2626;
                                                font-weight:600;
                                            ">
                                                Inativo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td style="padding:14px;">

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($usuario['data_cadastro'])
                                        ) ?>

                                    </td>

                                    <td style="padding:14px;">

                                        <a
                                            href="editar.php?id=<?= $usuario['id_usuario'] ?>"
                                            style="
                                                color:#2563EB;
                                                font-weight:600;
                                            ">
                                            <i class="fa-solid fa-pen"></i>
                                            Editar
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </main>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>