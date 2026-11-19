<?php

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->conectar();
    }

    /**
     * Busca um usuário pelo e-mail.
     */
    public function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um usuário pelo ID.
     */
    public function buscarPorId($id)
    {
        $sql = "SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
