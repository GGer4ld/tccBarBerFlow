<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| VERIFICAR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SE O SERVIÇO EXISTE
|--------------------------------------------------------------------------
*/

$sql = "SELECT id_servico, nome
        FROM servicos
        WHERE id_servico = :id
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id',
    $id,
    PDO::PARAM_INT
);

$stmt->execute();

$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| EXCLUIR SERVIÇO
|--------------------------------------------------------------------------
*/

try {

    $sql = "DELETE FROM servicos
            WHERE id_servico = :id";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | VOLTAR PARA LISTAGEM
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php?sucesso=3'
    );

    exit;

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | CASO O SERVIÇO ESTEJA SENDO UTILIZADO
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/modules/servicos/index.php?erro=excluir'
    );

    exit;
}