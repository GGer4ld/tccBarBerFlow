<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| PEGAR ID DO AGENDAMENTO
|--------------------------------------------------------------------------
*/

$id_agendamento = $_GET['id'] ?? '';

if (!$id_agendamento || !is_numeric($id_agendamento)) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?erro=agendamento'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SE O AGENDAMENTO EXISTE
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_agendamento,
            status
        FROM agendamentos
        WHERE id_agendamento = :id_agendamento
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_agendamento',
    $id_agendamento,
    PDO::PARAM_INT
);

$stmt->execute();

$agendamento = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| AGENDAMENTO NÃO ENCONTRADO
|--------------------------------------------------------------------------
*/

if (!$agendamento) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?erro=agendamento'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SE JÁ ESTÁ CANCELADO
|--------------------------------------------------------------------------
*/

if ($agendamento['status'] === 'Cancelado') {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?erro=cancelado'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CANCELAR AGENDAMENTO
|--------------------------------------------------------------------------
*/

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


    /*
    |--------------------------------------------------------------------------
    | REDIRECIONAR
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?sucesso=3'
    );

    exit;


} catch (PDOException $e) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/agendamentos/index.php?erro=excluir'
    );

    exit;
}