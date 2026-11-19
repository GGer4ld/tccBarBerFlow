
<?php

require_once __DIR__ . '/../app/config/database.php';

/*
|--------------------------------------------------------------------------
| BUSCAR NOME E LOGO DA BARBEARIA
|--------------------------------------------------------------------------
*/

$nomeSistema = 'BarberFlow';
$logoSistema = '';

try {

    $databaseSidebar = new Database();
    $dbSidebar = $databaseSidebar->conectar();

    $sqlSidebar = "SELECT nome_barbearia, logo
                   FROM configuracoes
                   ORDER BY id_configuracao ASC
                   LIMIT 1";

    $stmtSidebar = $dbSidebar->prepare($sqlSidebar);
    $stmtSidebar->execute();

    $dadosSidebar = $stmtSidebar->fetch(PDO::FETCH_ASSOC);

    if ($dadosSidebar) {

        if (!empty($dadosSidebar['nome_barbearia'])) {
            $nomeSistema = $dadosSidebar['nome_barbearia'];
        }

        if (!empty($dadosSidebar['logo'])) {
            $logoSistema = $dadosSidebar['logo'];
        }
    }

} catch (Throwable $e) {

    // Mantém os valores padrão caso não consiga consultar o banco.

}


/*
|--------------------------------------------------------------------------
| TIPO DE USUÁRIO
|--------------------------------------------------------------------------
*/

$tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

$isAdministrador = $tipoUsuario === 'Administrador';

$isBarbeiro = $tipoUsuario === 'Barbeiro';

$isCliente = $tipoUsuario === 'Cliente';


/*
|--------------------------------------------------------------------------
| CAMINHO DA LOGO
|--------------------------------------------------------------------------
*/

$urlLogoSistema = '';

if ($logoSistema !== '') {

    $urlLogoSistema =
        BASE_URL . '/' . ltrim($logoSistema, '/');
}

?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">

        <div class="logo-icon">

            <?php if ($urlLogoSistema !== ''): ?>

                <img
                    src="<?= htmlspecialchars($urlLogoSistema, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Logo da barbearia"
                    style="
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                        border-radius: 8px;
                    "
                >

            <?php else: ?>

                <i class="fa-solid fa-scissors"></i>

            <?php endif; ?>

        </div>

        <div class="logo-text">

            <strong>
                <?= htmlspecialchars($nomeSistema, ENT_QUOTES, 'UTF-8') ?>
            </strong>

            <span>Gestão para Barbearias</span>

        </div>

    </div>


    <nav class="sidebar-menu">

        <?php if ($isCliente): ?>

            <!-- MENU DO CLIENTE -->

            <p class="menu-title">
                MINHA CONTA
            </p>

            <a
                href="<?= BASE_URL ?>/modules/cliente/index.php"
                class="menu-item">

                <i class="fa-solid fa-house"></i>

                <span>Início</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/cliente/agendamento.php"
                class="menu-item">

                <i class="fa-solid fa-calendar-plus"></i>

                <span>Novo agendamento</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/cliente/agendamentos.php"
                class="menu-item">

                <i class="fa-solid fa-calendar-days"></i>

                <span>Meus agendamentos</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/cliente/atendimentos.php"
                class="menu-item">

                <i class="fa-solid fa-scissors"></i>

                <span>Meus atendimentos</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/cliente/perfil.php"
                class="menu-item">

                <i class="fa-solid fa-user"></i>

                <span>Meu perfil</span>

            </a>

        <?php else: ?>

            <!-- MENU ADMINISTRADOR / BARBEIRO -->

            <p class="menu-title">
                MENU PRINCIPAL
            </p>

            <a
                href="<?= BASE_URL ?>/dashboard.php"
                class="menu-item">

                <i class="fa-solid fa-chart-pie"></i>

                <span>Dashboard</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/agendamentos/index.php"
                class="menu-item">

                <i class="fa-solid fa-calendar-days"></i>

                <span>Agendamentos</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/atendimentos/index.php"
                class="menu-item">

                <i class="fa-solid fa-user-check"></i>

                <span>Atendimentos</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/clientes/index.php"
                class="menu-item">

                <i class="fa-solid fa-users"></i>

                <span>Clientes</span>

            </a>

            <a
                href="<?= BASE_URL ?>/modules/servicos/index.php"
                class="menu-item">

                <i class="fa-solid fa-scissors"></i>

                <span>Serviços</span>

            </a>


            <?php if ($isAdministrador): ?>

                <!-- MENU ADMINISTRATIVO -->

                <p class="menu-title">
                    GESTÃO
                </p>

                <a
                    href="<?= BASE_URL ?>/modules/barbeiros/index.php"
                    class="menu-item">

                    <i class="fa-solid fa-user-tie"></i>

                    <span>Barbeiros</span>

                </a>

                <a
                    href="<?= BASE_URL ?>/modules/estoque/index.php"
                    class="menu-item">

                    <i class="fa-solid fa-box"></i>

                    <span>Estoque</span>

                </a>

                <a
                    href="<?= BASE_URL ?>/modules/financeiro/index.php"
                    class="menu-item">

                    <i class="fa-solid fa-wallet"></i>

                    <span>Financeiro</span>

                </a>

                <a
                    href="<?= BASE_URL ?>/modules/relatorios/estoque.php"
                    class="menu-item">

                    <i class="fa-solid fa-chart-line"></i>

                    <span>Relatórios</span>

                </a>


                <!-- SISTEMA -->

                <p class="menu-title">
                    SISTEMA
                </p>

                <a
                    href="<?= BASE_URL ?>/modules/configuracoes/index.php"
                    class="menu-item">

                    <i class="fa-solid fa-gear"></i>

                    <span>Configurações</span>

                </a>

                <a
                    href="<?= BASE_URL ?>/modules/usuarios/index.php"
                    class="menu-item">

                    <i class="fa-solid fa-user-gear"></i>

                    <span>Usuários</span>

                </a>

            <?php endif; ?>

        <?php endif; ?>

    </nav>


    <!-- RODAPÉ -->

    <div class="sidebar-footer">

        <a
            href="<?= BASE_URL ?>/logout.php"
            class="logout-item">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Sair</span>

        </a>

    </div>

</aside>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle =
        document.querySelector('.menu-toggle');

    const sidebar =
        document.getElementById('sidebar');

    if (!menuToggle || !sidebar) {
        return;
    }

    menuToggle.addEventListener('click', function () {

        document.body.classList.toggle(
            'sidebar-collapsed'
        );

    });

});
</script>
