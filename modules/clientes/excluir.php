<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?erro=id_invalido');
    exit;
}

$id = (int) $_GET['id'];

$database = new Database();
$db = $database->conectar();

$sql = "DELETE FROM clientes
        WHERE id_cliente = :id";

$stmt = $db->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    header('Location: index.php?sucesso=excluido');
    exit;
}

header('Location: index.php?erro=exclusao');
exit;