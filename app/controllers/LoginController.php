<?php

require_once __DIR__ . '/../services/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

$auth = new AuthService();

if ($auth->login($email, $senha)) {

    $tipoUsuario = $_SESSION['usuario']['tipo'] ?? '';

    if ($tipoUsuario === 'Cliente') {
        header('Location: ../../modules/cliente/index.php');
        exit;
    }

    header('Location: ../../dashboard.php');
    exit;
}

header('Location: ../../login.php?erro=1&senha_incorreta=1');
exit;