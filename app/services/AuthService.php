<?php

require_once __DIR__ . '/../config/database.php';

class AuthService
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->conectar();
    }

    public function login($email, $senha)
    {
        $sql = "SELECT *
                FROM usuarios
                WHERE email = :email
                AND status = 'Ativo'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':email',
            $email
        );

        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return false;
        }

        if (!password_verify($senha, $usuario['senha'])) {
            return false;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['usuario'] = [
            'id' => $usuario['id_usuario'],
            'id_cliente' => $usuario['id_cliente'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo_usuario']
        ];

        return true;
    }

    public function logout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_destroy();
    }
}