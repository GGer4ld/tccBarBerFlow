<?php

/**
 * ==========================================================
 * BarberFlow - Sistema de Gerenciamento para Barbearias
 * Arquivo: config.php
 * Função: Configurações gerais do sistema
 * ==========================================================
 */

// Impede acesso direto ao arquivo
defined('BARBERFLOW') or define('BARBERFLOW', true);

// ==============================
// CONFIGURAÇÕES GERAIS
// ==============================

// Nome do sistema
define('APP_NAME', 'BarberFlow');

// Versão do sistema
define('APP_VERSION', '1.0.0');

// Ambiente
// development = mostra erros
// production = oculta erros
define('APP_ENV', 'development');

// URL base do projeto
define('BASE_URL', 'http://localhost:8080/TCCBARBERFLOW');

// Caminho físico da aplicação
define('ROOT_PATH', dirname(__DIR__, 2));

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Idioma
setlocale(LC_TIME, 'pt_BR.UTF-8');

// ==============================
// EXIBIÇÃO DE ERROS
// ==============================

if (APP_ENV === 'development') {

    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {

    ini_set('display_errors', 0);
}

