<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/config/config.php';

if (!isset($_SESSION['usuario'])) {

    header("Location: " . BASE_URL . "/login.php");
    exit();
}

/**
 * Verifica se o usuário logado é administrador.
 */
function exigirAdministrador()
{
    if (
        !isset($_SESSION['usuario']['tipo']) ||
        $_SESSION['usuario']['tipo'] !== 'Administrador'
    ) {
        header(
            "Location: " .
            BASE_URL .
            "/dashboard.php?erro=acesso_negado"
        );
        exit();
    }
}