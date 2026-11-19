
<?php

require_once __DIR__ . '/../app/config/database.php';

$databaseNavbar = new Database();
$dbNavbar = $databaseNavbar->conectar();

/*
|--------------------------------------------------------------------------
| BUSCAR CONFIGURAÇÕES DA BARBEARIA
|--------------------------------------------------------------------------
*/

$sqlConfiguracaoNavbar = "
    SELECT nome_barbearia, logo
    FROM configuracoes
    ORDER BY id_configuracao ASC
    LIMIT 1
";

$stmtConfiguracaoNavbar = $dbNavbar->prepare($sqlConfiguracaoNavbar);
$stmtConfiguracaoNavbar->execute();

$configuracaoNavbar = $stmtConfiguracaoNavbar->fetch(PDO::FETCH_ASSOC);

$nomeBarbeariaNavbar = $configuracaoNavbar['nome_barbearia'] ?? 'BarberFlow';
$logoBarbeariaNavbar = $configuracaoNavbar['logo'] ?? '';

/*
|--------------------------------------------------------------------------
| DADOS DO USUÁRIO
|--------------------------------------------------------------------------
*/

$tipoUsuarioNavbar = $_SESSION['usuario']['tipo'] ?? '';
$idUsuarioNavbar = (int) ($_SESSION['usuario']['id'] ?? 0);

$notificacoes = [];
$idBarbeiroNavbar = null;

/*
|--------------------------------------------------------------------------
| IDENTIFICAR BARBEIRO
|--------------------------------------------------------------------------
*/

if ($tipoUsuarioNavbar === 'Barbeiro') {

    $sqlBarbeiroNavbar = "
        SELECT id_barbeiro
        FROM barbeiros
        WHERE id_usuario = :id_usuario
        LIMIT 1
    ";

    $stmtBarbeiroNavbar = $dbNavbar->prepare($sqlBarbeiroNavbar);

    $stmtBarbeiroNavbar->bindValue(
        ':id_usuario',
        $idUsuarioNavbar,
        PDO::PARAM_INT
    );

    $stmtBarbeiroNavbar->execute();

    $barbeiroNavbar = $stmtBarbeiroNavbar->fetch(PDO::FETCH_ASSOC);

    if ($barbeiroNavbar) {
        $idBarbeiroNavbar = (int) $barbeiroNavbar['id_barbeiro'];
    }
}

/*
|--------------------------------------------------------------------------
| BUSCAR PRÓXIMOS AGENDAMENTOS
|--------------------------------------------------------------------------
*/

$sqlNotificacoes = "
    SELECT
        a.id_agendamento,
        a.data_agendamento,
        a.hora_inicio,
        a.hora_fim,
        a.status,
        c.nome AS cliente_nome,
        u.nome AS barbeiro_nome,
        s.nome AS servico_nome

    FROM agendamentos a

    INNER JOIN clientes c
        ON c.id_cliente = a.id_cliente

    INNER JOIN barbeiros b
        ON b.id_barbeiro = a.id_barbeiro

    INNER JOIN usuarios u
        ON u.id_usuario = b.id_usuario

    INNER JOIN servicos s
        ON s.id_servico = a.id_servico

    WHERE
        a.data_agendamento >= CURDATE()

        AND a.status NOT IN (
            'Cancelado',
            'Concluído'
        )
";

if (
    $tipoUsuarioNavbar === 'Barbeiro'
    && $idBarbeiroNavbar !== null
) {
    $sqlNotificacoes .= "
        AND a.id_barbeiro = :id_barbeiro
    ";
}

$sqlNotificacoes .= "
    ORDER BY
        a.data_agendamento ASC,
        a.hora_inicio ASC

    LIMIT 5
";

$stmtNotificacoes = $dbNavbar->prepare($sqlNotificacoes);

if (
    $tipoUsuarioNavbar === 'Barbeiro'
    && $idBarbeiroNavbar !== null
) {
    $stmtNotificacoes->bindValue(
        ':id_barbeiro',
        $idBarbeiroNavbar,
        PDO::PARAM_INT
    );
}

$stmtNotificacoes->execute();

$notificacoes = $stmtNotificacoes->fetchAll(PDO::FETCH_ASSOC);

?>

<header class="navbar">

    <div class="navbar-left">

        <button class="menu-toggle" type="button">
            <i class="fa-solid fa-bars"></i>
        </button>

        <?php

        /*
        |--------------------------------------------------------------------------
        | IDENTIFICAR A PÁGINA ATUAL
        |--------------------------------------------------------------------------
        */

        $caminhoAtual = str_replace(
            '\\',
            '/',
            $_SERVER['PHP_SELF']
        );

        $paginaAtual = basename($caminhoAtual);
        $moduloAtual = '';

        /*
         * Identifica a pasta dentro de /modules/
         * Exemplo: /modules/clientes/index.php
         * Resultado: clientes
         */

        if (preg_match('~/modules/([^/]+)/~', $caminhoAtual, $matches)) {
            $moduloAtual = $matches[1];
        }

        /*
        |--------------------------------------------------------------------------
        | TÍTULOS E SUBTÍTULOS DOS MÓDULOS
        |--------------------------------------------------------------------------
        */

        $paginasNavbar = [

            'dashboard' => [
                'titulo' => 'Dashboard',
                'subtitulo' => 'Visão geral do sistema'
            ],

            'agendamentos' => [
                'titulo' => 'Agendamentos',
                'subtitulo' => 'Gerencie os horários agendados'
            ],

            'atendimentos' => [
                'titulo' => 'Atendimentos',
                'subtitulo' => 'Acompanhe os atendimentos pendentes da barbearia'
            ],

            'clientes' => [
                'titulo' => 'Clientes',
                'subtitulo' => 'Gerencie os clientes cadastrados'
            ],

            'servicos' => [
                'titulo' => 'Serviços',
                'subtitulo' => 'Gerencie os serviços da barbearia'
            ],

            'barbeiros' => [
                'titulo' => 'Barbeiros',
                'subtitulo' => 'Gerencie os barbeiros cadastrados'
            ],

            'estoque' => [
                'titulo' => 'Estoque',
                'subtitulo' => 'Controle os produtos e movimentações'
            ],

            'financeiro' => [
                'titulo' => 'Financeiro',
                'subtitulo' => 'Acompanhe receitas e despesas'
            ],

            'relatorios' => [
                'titulo' => 'Relatórios',
                'subtitulo' => 'Consulte os resultados da barbearia'
            ],

            'configuracoes' => [
                'titulo' => 'Configurações',
                'subtitulo' => 'Gerencie as configurações do sistema'
            ],

            'usuarios' => [
                'titulo' => 'Meu Perfil',
                'subtitulo' => 'Gerencie suas informações pessoais'
            ],

            'cliente' => [
                'titulo' => 'Meu Perfil',
                'subtitulo' => 'Gerencie suas informações pessoais'
            ]

        ];

        /*
        |--------------------------------------------------------------------------
        | PÁGINAS FORA DA PASTA MODULES
        |--------------------------------------------------------------------------
        */

        $paginasAvulsasNavbar = [

            'dashboard.php' => [
                'titulo' => 'Dashboard',
                'subtitulo' => 'Visão geral do sistema'
            ],

            'agendamentos.php' => [
                'titulo' => 'Agendamentos',
                'subtitulo' => 'Gerencie os horários agendados'
            ],

            'atendimentos.php' => [
                'titulo' => 'Atendimentos',
                'subtitulo' => 'Acompanhe os atendimentos'
            ],

            'clientes.php' => [
                'titulo' => 'Clientes',
                'subtitulo' => 'Gerencie os clientes cadastrados'
            ],

            'servicos.php' => [
                'titulo' => 'Serviços',
                'subtitulo' => 'Gerencie os serviços da barbearia'
            ],

            'estoque.php' => [
                'titulo' => 'Estoque',
                'subtitulo' => 'Controle os produtos e movimentações'
            ],

            'financeiro.php' => [
                'titulo' => 'Financeiro',
                'subtitulo' => 'Acompanhe receitas e despesas'
            ],

            'relatorios.php' => [
                'titulo' => 'Relatórios',
                'subtitulo' => 'Consulte os resultados da barbearia'
            ],

            'configuracoes.php' => [
                'titulo' => 'Configurações',
                'subtitulo' => 'Configurações do sistema'
            ],

            'perfil.php' => [
                'titulo' => 'Meu Perfil',
                'subtitulo' => 'Gerencie suas informações pessoais'
            ]

        ];

        /*
        |--------------------------------------------------------------------------
        | DEFINIR TÍTULO E SUBTÍTULO
        |--------------------------------------------------------------------------
        */

        if (!empty($moduloAtual) && isset($paginasNavbar[$moduloAtual])) {

            $tituloNavbar = $paginasNavbar[$moduloAtual]['titulo'];
            $subtituloNavbar = $paginasNavbar[$moduloAtual]['subtitulo'];

        } elseif (isset($paginasAvulsasNavbar[$paginaAtual])) {

            $tituloNavbar = $paginasAvulsasNavbar[$paginaAtual]['titulo'];
            $subtituloNavbar = $paginasAvulsasNavbar[$paginaAtual]['subtitulo'];

        } else {

            $tituloNavbar = $nomeBarbeariaNavbar;
            $subtituloNavbar = 'Gestão para Barbearias';

        }

        ?>

        <!-- TÍTULO DA PÁGINA -->

        <div class="page-info">

            <span class="page-title">
                <?= htmlspecialchars($tituloNavbar, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <span class="page-subtitle">
                <?= htmlspecialchars($subtituloNavbar, ENT_QUOTES, 'UTF-8') ?>
            </span>

        </div>

    </div>

    <!-- GRUPO DIREITO DA NAVBAR -->

    <div class="navbar-right">

        <!-- NOTIFICAÇÕES -->

        <div class="notification-wrapper">

            <button
                class="notification-button"
                id="notificationButton"
                type="button"
                aria-label="Notificações"
            >

                <i class="fa-regular fa-bell"></i>

                <?php if (!empty($notificacoes)): ?>
                    <span class="notification-dot"></span>
                <?php endif; ?>

            </button>

            <div class="notification-panel" id="notificationPanel">

                <div class="notification-header">

                    <strong>Notificações</strong>

                    <button type="button" id="clearNotifications">
                        Marcar como lidas
                    </button>

                </div>

                <div class="notification-list" id="notificationList">

                    <?php if (empty($notificacoes)): ?>

                        <div class="notification-empty">

                            <i class="fa-regular fa-bell-slash"></i>

                            <span>
                                Nenhuma notificação no momento.
                            </span>

                        </div>

                    <?php else: ?>

                        <?php foreach ($notificacoes as $notificacao): ?>

                            <div class="notification-item">

                                <div class="notification-icon">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>

                                <div class="notification-content">

                                    <strong>
                                        Próximo agendamento
                                    </strong>

                                    <span>

                                        <?= htmlspecialchars(
                                            $notificacao['cliente_nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        -

                                        <?= htmlspecialchars(
                                            $notificacao['servico_nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                    <span>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($notificacao['data_agendamento'])
                                        ) ?>

                                        às

                                        <?= date(
                                            'H:i',
                                            strtotime($notificacao['hora_inicio'])
                                        ) ?>

                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- MENU DO USUÁRIO -->

        <div class="user-menu-wrapper">

            <button
                type="button"
                class="user-menu"
                id="userMenuButton"
                aria-label="Menu do usuário"
            >

                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="user-info">

                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION['usuario']['nome'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $_SESSION['usuario']['tipo'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </div>

                <i class="fa-solid fa-chevron-down user-arrow"></i>

            </button>

            <div
                class="user-dropdown"
                id="userDropdown"
            >

                <?php

                $tipoUsuarioMenu = $_SESSION['usuario']['tipo'] ?? '';

                if ($tipoUsuarioMenu === 'Cliente') {

                    $linkPerfil = BASE_URL . '/modules/cliente/perfil.php';

                } else {

                    $linkPerfil = BASE_URL . '/modules/usuarios/perfil.php';

                }

                ?>

                <a
                    href="<?= $linkPerfil ?>"
                    class="user-dropdown-item"
                >

                    <i class="fa-regular fa-user"></i>

                    <span>Meu perfil</span>

                </a>

                <div class="user-dropdown-divider"></div>

                <a
                    href="<?= BASE_URL ?>/logout.php"
                    class="user-dropdown-item user-dropdown-logout"
                >

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>Sair</span>

                </a>

            </div>

        </div>

    </div>

</header>

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | NOTIFICAÇÕES
    |--------------------------------------------------------------------------
    */

    const notificationButton = document.getElementById('notificationButton');

    const notificationPanel = document.getElementById('notificationPanel');

    const clearNotifications = document.getElementById('clearNotifications');

    const notificationDot = document.querySelector('.notification-dot');

    if (notificationButton && notificationPanel) {

        notificationButton.addEventListener('click', function (event) {

            event.stopPropagation();

            notificationPanel.classList.toggle('show');

        });

        notificationPanel.addEventListener('click', function (event) {

            event.stopPropagation();

        });

        document.addEventListener('click', function () {

            notificationPanel.classList.remove('show');

        });

    }

    if (clearNotifications) {

        clearNotifications.addEventListener('click', function () {

            notificationPanel.classList.remove('show');

            if (notificationDot) {

                notificationDot.style.display = 'none';

            }

        });

    }

    /*
    |--------------------------------------------------------------------------
    | MENU DO USUÁRIO
    |--------------------------------------------------------------------------
    */

    const userMenuButton = document.getElementById('userMenuButton');

    const userDropdown = document.getElementById('userDropdown');

    if (userMenuButton && userDropdown) {

        userMenuButton.addEventListener('click', function (event) {

            event.stopPropagation();

            userDropdown.classList.toggle('show');

        });

        userDropdown.addEventListener('click', function (event) {

            event.stopPropagation();

        });

        document.addEventListener('click', function () {

            userDropdown.classList.remove('show');

        });

    }

});

</script>
