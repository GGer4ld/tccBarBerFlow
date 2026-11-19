<?php

require_once "app/config/database.php";

$db = new Database();
$pdo = $db->conectar();

$nome = "Administrador";
$email = "admin@barberflow.com";
$senha = password_hash("123456", PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios
(nome, email, senha, telefone, tipo_usuario, status)
VALUES
(?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $nome,
    $email,
    $senha,
    "",
    "Administrador",
    "Ativo"
]);

echo "Administrador criado com sucesso!";
