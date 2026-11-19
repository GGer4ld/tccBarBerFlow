<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();

$database = new Database();
$db = $database->conectar();


// ==========================================================
// VALIDAR ID
// ==========================================================

$id_barbeiro = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_barbeiro) {

    header(
        "Location: " .
        BASE_URL .
        "/modules/barbeiros/index.php"
    );

    exit();
}


// ==========================================================
// BUSCAR O BARBEIRO
// ==========================================================

$sql = "SELECT
            id_barbeiro,
            id_usuario
        FROM barbeiros
        WHERE id_barbeiro = :id_barbeiro
        LIMIT 1";

$stmt = $db->prepare($sql);

$stmt->bindValue(
    ':id_barbeiro',
    $id_barbeiro,
    PDO::PARAM_INT
);

$stmt->execute();

$barbeiro = $stmt->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// BARBEIRO NÃO ENCONTRADO
// ==========================================================

if (!$barbeiro) {

    header(
        "Location: " .
        BASE_URL .
        "/modules/barbeiros/index.php"
    );

    exit();
}


try {

    $db->beginTransaction();


    // ======================================================
    // VERIFICAR RELACIONAMENTOS COM OUTRAS TABELAS
    // ======================================================

    $sql = "SELECT
                TABLE_NAME,
                COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'barbeiros'
                AND REFERENCED_COLUMN_NAME = 'id_barbeiro'";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $relacionamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $registrosRelacionados = [];


    foreach ($relacionamentos as $relacao) {

        $tabela = $relacao['TABLE_NAME'];
        $coluna = $relacao['COLUMN_NAME'];


        /*
         * Os nomes de tabela e coluna vêm do
         * information_schema, portanto são informações
         * da própria estrutura do banco.
         */

        $sqlCheck = "SELECT COUNT(*)
                     FROM `{$tabela}`
                     WHERE `{$coluna}` = :id_barbeiro";

        $stmtCheck = $db->prepare($sqlCheck);

        $stmtCheck->bindValue(
            ':id_barbeiro',
            $id_barbeiro,
            PDO::PARAM_INT
        );

        $stmtCheck->execute();

        $quantidade = (int) $stmtCheck->fetchColumn();


        if ($quantidade > 0) {

            $registrosRelacionados[] =
                $tabela . ' (' . $quantidade . ' registro(s))';
        }
    }


    // ======================================================
    // NÃO EXCLUIR SE EXISTIREM RELACIONAMENTOS
    // ======================================================

    if (!empty($registrosRelacionados)) {

        $db->rollBack();

        $mensagem =
            'Não é possível excluir este barbeiro porque existem ' .
            'registros relacionados: ' .
            implode(', ', $registrosRelacionados) .
            '.';

        header(
            "Location: " .
            BASE_URL .
            "/modules/barbeiros/index.php?erro=" .
            urlencode($mensagem)
        );

        exit();
    }


    // ======================================================
    // EXCLUIR BARBEIRO
    // ======================================================

    $sql = "DELETE FROM barbeiros
            WHERE id_barbeiro = :id_barbeiro";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_barbeiro',
        $id_barbeiro,
        PDO::PARAM_INT
    );

    $stmt->execute();


    // ======================================================
    // EXCLUIR USUÁRIO ASSOCIADO
    // ======================================================

    $sql = "DELETE FROM usuarios
            WHERE id_usuario = :id_usuario";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(
        ':id_usuario',
        $barbeiro['id_usuario'],
        PDO::PARAM_INT
    );

    $stmt->execute();


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $db->commit();


    header(
        "Location: " .
        BASE_URL .
        "/modules/barbeiros/index.php?sucesso=3"
    );

    exit();


} catch (Exception $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "/modules/barbeiros/index.php?erro=" .
        urlencode(
            'Não foi possível excluir o barbeiro. ' .
            'Verifique se existem registros relacionados.'
        )
    );

    exit();
}