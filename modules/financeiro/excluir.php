<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?erro=nao_encontrado"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFICAR SE O LANÇAMENTO EXISTE
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_lancamento,
            id_atendimento
        FROM lancamentos_financeiros
        WHERE id_lancamento = :id
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id',
    $id,
    PDO::PARAM_INT
);

$stmt->execute();

$lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lancamento) {
    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?erro=nao_encontrado"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| EXCLUIR LANÇAMENTO
|--------------------------------------------------------------------------
*/

try {

    $sql = "DELETE FROM lancamentos_financeiros
            WHERE id_lancamento = :id";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $stmt->execute();

    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?sucesso=3"
    );
    exit;

} catch (PDOException $e) {

    header(
        "Location: " .
        BASE_URL .
        "/modules/financeiro/index.php?erro=excluir"
    );
    exit;
}