<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();


/*
|--------------------------------------------------------------------------
| RECEBER ID DO PRODUTO
|--------------------------------------------------------------------------
*/

$id_produto = $_GET['id'] ?? null;


if (!$id_produto) {

    header(
        'Location: ' .
        BASE_URL .
        '/modules/estoque/index.php'
    );

    exit;
}


try {


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR MOVIMENTAÇÕES
    |--------------------------------------------------------------------------
    */


    $sql = "SELECT id_movimento

        FROM movimentacoes_estoque

        WHERE id_produto = :id_produto

        LIMIT 1";


    $stmt = $db->prepare($sql);


    $stmt->bindValue(
        ':id_produto',
        $id_produto,
        PDO::PARAM_INT
    );


    $stmt->execute();


    $movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);



    if ($movimentacao) {


        /*
        |--------------------------------------------------------------------------
        | POSSUI HISTÓRICO
        |--------------------------------------------------------------------------
        */


        header(
            'Location: ' .
            BASE_URL .
            '/modules/estoque/index.php?erro=excluir'
        );


        exit;


    }



    /*
    |--------------------------------------------------------------------------
    | EXCLUIR PRODUTO
    |--------------------------------------------------------------------------
    */


    $sql = "DELETE FROM produtos

            WHERE id_produto = :id_produto";


    $stmt = $db->prepare($sql);


    $stmt->bindValue(
        ':id_produto',
        $id_produto,
        PDO::PARAM_INT
    );


    $stmt->execute();



    header(
        'Location: ' .
        BASE_URL .
        '/modules/estoque/index.php?sucesso=3'
    );


    exit;



} catch(PDOException $e) {


    header(
        'Location: ' .
        BASE_URL .
        '/modules/estoque/index.php?erro=excluir'
    );


    exit;

}