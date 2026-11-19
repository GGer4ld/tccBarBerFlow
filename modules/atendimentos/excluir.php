<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header(
        'Location: ' .
        BASE_URL .
        '/modules/atendimentos/index.php?erro=excluir'
    );
    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SE O ATENDIMENTO EXISTE
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT id_atendimento
            FROM atendimentos
            WHERE id_atendimento = :id
            LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$atendimento) {

        header(
            'Location: ' .
            BASE_URL .
            '/modules/atendimentos/index.php?erro=excluir'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EXCLUIR ATENDIMENTO
    |--------------------------------------------------------------------------
    */

    $sql = "DELETE FROM atendimentos
            WHERE id_atendimento = :id";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id',
        $id,
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
        '/modules/atendimentos/index.php?sucesso=3'
    );

    exit;

} catch (PDOException $e) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/atendimentos/index.php?erro=excluir'
    );

    exit;
}