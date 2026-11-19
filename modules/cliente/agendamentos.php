<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

if (
    !isset($_SESSION['usuario']['tipo']) ||
    $_SESSION['usuario']['tipo'] !== 'Cliente'
) {
    header('Location: ' . BASE_URL . '/dashboard.php?erro=acesso_negado');
    exit();
}

$idCliente = $_SESSION['usuario']['id_cliente'] ?? null;

if (!$idCliente) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit();
}

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| Buscar agendamentos do cliente
|--------------------------------------------------------------------------
| O agendamento é buscado uma única vez.
| Os serviços são agrupados posteriormente para evitar duplicação.
*/

$sql = "SELECT
            a.id_agendamento,
            a.data_agendamento,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            a.observacao,
            u.nome AS barbeiro
        FROM agendamentos a

        INNER JOIN barbeiros b
            ON b.id_barbeiro = a.id_barbeiro

        INNER JOIN usuarios u
            ON u.id_usuario = b.id_usuario

        WHERE a.id_cliente = :id_cliente

        ORDER BY
            a.data_agendamento DESC,
            a.hora_inicio DESC";

$stmt = $db->prepare($sql);
$stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
$stmt->execute();

$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Buscar serviços de cada agendamento
|--------------------------------------------------------------------------
*/

$servicosPorAgendamento = [];

if (!empty($agendamentos)) {

    $idsAgendamentos = array_column(
        $agendamentos,
        'id_agendamento'
    );

    $placeholders = [];

    foreach ($idsAgendamentos as $index => $idAgendamento) {
        $placeholders[] = ':id_agendamento_' . $index;
    }

    $sqlServicos = "SELECT
                        ags.id_agendamento,
                        ags.id_servico,
                        ags.preco,
                        s.nome AS servico_nome

                    FROM agendamento_servicos ags

                    INNER JOIN servicos s
                        ON s.id_servico = ags.id_servico

                    WHERE ags.id_agendamento IN (
                        " . implode(', ', $placeholders) . "
                    )

                    ORDER BY
                        s.nome ASC";

    $stmtServicos = $db->prepare($sqlServicos);

    foreach ($idsAgendamentos as $index => $idAgendamento) {
        $stmtServicos->bindValue(
            ':id_agendamento_' . $index,
            $idAgendamento,
            PDO::PARAM_INT
        );
    }

    $stmtServicos->execute();

    $servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($servicos as $servico) {

        $idAgendamento = $servico['id_agendamento'];

        if (!isset($servicosPorAgendamento[$idAgendamento])) {
            $servicosPorAgendamento[$idAgendamento] = [
                'itens' => [],
                'total' => 0
            ];
        }

        $servicosPorAgendamento[$idAgendamento]['itens'][] = $servico;

        $servicosPorAgendamento[$idAgendamento]['total'] +=
            (float) $servico['preco'];
    }
}

/*
|--------------------------------------------------------------------------
| Formatação
|--------------------------------------------------------------------------
*/

function formatarDataCliente($data)
{
    if (!$data) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

function formatarHoraCliente($hora)
{
    if (!$hora) {
        return '-';
    }

    return date('H:i', strtotime($hora));
}

function classeStatusCliente($status)
{
    switch ($status) {

        case 'Confirmado':
            return 'cliente-status cliente-status-confirmado';

        case 'Em atendimento':
            return 'cliente-status cliente-status-atendimento';

        case 'Pendente':
            return 'cliente-status cliente-status-pendente';

        case 'Concluído':
            return 'cliente-status cliente-status-concluido';

        case 'Cancelado':
            return 'cliente-status cliente-status-cancelado';

        default:
            return 'cliente-status';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content">

            <div class="page-header">

                <div>
                    <h1>Meus agendamentos</h1>

                    <p>
                        Consulte seus agendamentos e serviços selecionados.
                    </p>
                </div>

                <a
                    href="<?= BASE_URL; ?>/modules/cliente/agendamento.php"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Novo agendamento
                </a>

            </div>

            <?php if (empty($agendamentos)): ?>

                <div class="card">

                    <div class="cliente-empty">

                        <div class="cliente-empty-icon">
                            <i class="fa-regular fa-calendar-xmark"></i>
                        </div>

                        <div class="cliente-empty-text">
                            <strong>Nenhum agendamento encontrado</strong>

                            <span>
                                Você ainda não possui agendamentos.
                            </span>
                        </div>

                        <a
                            href="<?= BASE_URL; ?>/modules/cliente/agendamento.php"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-calendar-plus"></i>
                            Fazer agendamento
                        </a>

                    </div>

                </div>

            <?php else: ?>

                <div class="cliente-agendamentos-list">

                    <?php foreach ($agendamentos as $agendamento): ?>

                        <?php

                        $idAgendamento =
                            (int) $agendamento['id_agendamento'];

                        $servicosAgendamento =
                            $servicosPorAgendamento[$idAgendamento]
                            ?? [
                                'itens' => [],
                                'total' => 0
                            ];

                        $totalAgendamento =
                            (float) $servicosAgendamento['total'];

                        ?>

                        <div class="card cliente-agendamento-card">

                            <div class="cliente-agendamento-header">

                                <div>

                                    <div class="cliente-agendamento-date">

                                        <i class="fa-regular fa-calendar"></i>

                                        <?= formatarDataCliente(
                                            $agendamento['data_agendamento']
                                        ); ?>

                                    </div>

                                    <div class="cliente-agendamento-time">

                                        <i class="fa-regular fa-clock"></i>

                                        <?= formatarHoraCliente(
                                            $agendamento['hora_inicio']
                                        ); ?>

                                        até

                                        <?= formatarHoraCliente(
                                            $agendamento['hora_fim']
                                        ); ?>

                                    </div>

                                </div>

                                <span
                                    class="<?= classeStatusCliente(
                                        $agendamento['status']
                                    ); ?>"
                                >
                                    <?= htmlspecialchars(
                                        $agendamento['status']
                                    ); ?>
                                </span>

                            </div>

                            <div class="cliente-agendamento-body">

                                <div class="cliente-agendamento-section">

                                    <span class="cliente-section-title">
                                        <i class="fa-solid fa-scissors"></i>
                                        Serviços
                                    </span>

                                    <?php if (!empty($servicosAgendamento['itens'])): ?>

                                        <div class="cliente-servicos-list">

                                            <?php foreach (
                                                $servicosAgendamento['itens']
                                                as $servico
                                            ): ?>

                                                <div class="cliente-servico-item">

                                                    <div>
                                                        <strong>
                                                            <?= htmlspecialchars(
                                                                $servico['servico_nome']
                                                            ); ?>
                                                        </strong>
                                                    </div>

                                                    <span>
                                                        R$
                                                        <?= number_format(
                                                            (float) $servico['preco'],
                                                            2,
                                                            ',',
                                                            '.'
                                                        ); ?>
                                                    </span>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="cliente-sem-observacao">
                                            Serviço não informado.
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="cliente-agendamento-info-grid">

                                    <div class="cliente-info-item">

                                        <span class="cliente-info-label">
                                            <i class="fa-solid fa-user-tie"></i>
                                            Barbeiro
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $agendamento['barbeiro']
                                            ); ?>
                                        </strong>

                                    </div>

                                    <div class="cliente-info-item">

                                        <span class="cliente-info-label">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                            Total
                                        </span>

                                        <strong class="cliente-total">
                                            R$
                                            <?= number_format(
                                                $totalAgendamento,
                                                2,
                                                ',',
                                                '.'
                                            ); ?>
                                        </strong>

                                    </div>

                                </div>

                                <?php if (
                                    !empty($agendamento['observacao'])
                                ): ?>

                                    <div class="cliente-observacao">

                                        <span class="cliente-info-label">
                                            <i class="fa-regular fa-note-sticky"></i>
                                            Observação
                                        </span>

                                        <p>
                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $agendamento['observacao']
                                                )
                                            ); ?>
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </main>

    </div>

</div>

<style>

.cliente-agendamentos-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cliente-agendamento-card {
    overflow: hidden;
}

.cliente-agendamento-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 24px;
    border-bottom: 1px solid #E2E8F0;
}

.cliente-agendamento-date {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0F172A;
    font-size: 17px;
    font-weight: 700;
}

.cliente-agendamento-date i {
    color: #2563EB;
}

.cliente-agendamento-time {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 6px;
    color: #64748B;
    font-size: 14px;
}

.cliente-agendamento-time i {
    color: #64748B;
}

.cliente-agendamento-body {
    padding: 24px;
}

.cliente-agendamento-section {
    margin-bottom: 22px;
}

.cliente-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #334155;
    font-size: 14px;
    font-weight: 700;
}

.cliente-section-title i {
    color: #2563EB;
}

.cliente-servicos-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cliente-servico-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 12px 14px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.cliente-servico-item strong {
    color: #1E293B;
    font-size: 14px;
}

.cliente-servico-item span {
    color: #2563EB;
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
}

.cliente-agendamento-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.cliente-info-item {
    padding: 15px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
}

.cliente-info-label {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 6px;
    color: #64748B;
    font-size: 12px;
    font-weight: 600;
}

.cliente-info-label i {
    color: #64748B;
}

.cliente-info-item strong {
    color: #1E293B;
    font-size: 14px;
}

.cliente-total {
    color: #15803D !important;
    font-size: 16px !important;
}

.cliente-observacao {
    padding: 15px;
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: 10px;
}

.cliente-observacao .cliente-info-label {
    color: #92400E;
}

.cliente-observacao .cliente-info-label i {
    color: #D97706;
}

.cliente-observacao p {
    margin: 0;
    color: #78350F;
    font-size: 14px;
    line-height: 1.6;
}

.cliente-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.cliente-status-confirmado {
    background: #DCFCE7;
    color: #15803D;
}

.cliente-status-atendimento {
    background: #DBEAFE;
    color: #1D4ED8;
}

.cliente-status-pendente {
    background: #FEF3C7;
    color: #B45309;
}

.cliente-status-concluido {
    background: #E0E7FF;
    color: #4338CA;
}

.cliente-status-cancelado {
    background: #FEE2E2;
    color: #B91C1C;
}

.cliente-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    min-height: 300px;
    padding: 40px;
    text-align: center;
}

.cliente-empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    font-size: 26px;
}

.cliente-empty-text {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.cliente-empty-text strong {
    color: #1E293B;
    font-size: 17px;
}

.cliente-empty-text span {
    color: #64748B;
    font-size: 14px;
}

.cliente-empty .btn {
    margin-top: 8px;
}

.cliente-sem-observacao {
    color: #94A3B8;
    font-size: 13px;
}

@media (max-width: 768px) {

    .cliente-agendamento-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .cliente-agendamento-info-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 480px) {

    .cliente-agendamento-header,
    .cliente-agendamento-body {
        padding: 16px;
    }

    .cliente-servico-item {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

}

</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>