<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


$database = new Database();
$db = $database->conectar();


// PRODUTOS POR CATEGORIA

$sql = "SELECT 
            categoria,
            COUNT(*) AS total

        FROM produtos

        GROUP BY categoria

        ORDER BY total DESC";


$stmt = $db->prepare($sql);
$stmt->execute();


$produtosCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);





// MOVIMENTAÇÕES DE ESTOQUE

$sql = "SELECT

            tipo_movimento,
            SUM(quantidade) AS total


        FROM movimentacoes_estoque


        GROUP BY tipo_movimento";


$stmt = $db->prepare($sql);
$stmt->execute();


$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);





// TRANSFORMAR EM JSON PARA O JAVASCRIPT


echo json_encode([

    "categorias" => $produtosCategoria,

    "movimentacoes" => $movimentacoes

]);