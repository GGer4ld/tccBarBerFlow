<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$id_agendamento = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_agendamento) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php'
    );

    exit;
}

try {

    $sql = "UPDATE agendamentos
            SET status = 'Cancelado'
            WHERE id_agendamento = :id_agendamento";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_agendamento',
        $id_agendamento,
        PDO::PARAM_INT
    );

    $stmt->execute();

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?sucesso=cancelado'
    );

    exit;

} catch (PDOException $e) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?erro=cancelar'
    );

    exit;
}