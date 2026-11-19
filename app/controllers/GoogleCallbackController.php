```php
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$googleConfig = require __DIR__ . '/../config/google.php';

/*
|--------------------------------------------------------------------------
| 1. Verificar se o Google retornou um erro
|--------------------------------------------------------------------------
*/

if (isset($_GET['error'])) {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. Verificar o código de autorização
|--------------------------------------------------------------------------
*/

$code = $_GET['code'] ?? '';

if ($code === '') {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. Validar o state
|--------------------------------------------------------------------------
*/

$state = $_GET['state'] ?? '';

if (
    !isset($_SESSION['google_oauth_state']) ||
    !hash_equals($_SESSION['google_oauth_state'], $state)
) {
    unset($_SESSION['google_oauth_state']);

    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

unset($_SESSION['google_oauth_state']);

/*
|--------------------------------------------------------------------------
| 4. Trocar o authorization code pelo access token
|--------------------------------------------------------------------------
*/

$tokenData = [
    'code' => $code,
    'client_id' => $googleConfig['client_id'],
    'client_secret' => $googleConfig['client_secret'],
    'redirect_uri' => $googleConfig['redirect_uri'],
    'grant_type' => 'authorization_code'
];

$ch = curl_init('https://oauth2.googleapis.com/token');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 15
]);

$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($tokenResponse === false || $httpCode !== 200) {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

$token = json_decode($tokenResponse, true);

if (
    !is_array($token) ||
    empty($token['access_token'])
) {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. Buscar informações do usuário no Google
|--------------------------------------------------------------------------
*/

$ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token['access_token']
    ],
    CURLOPT_TIMEOUT => 15
]);

$userResponse = curl_exec($ch);
$userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($userResponse === false || $userHttpCode !== 200) {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

$googleUser = json_decode($userResponse, true);

if (
    !is_array($googleUser) ||
    empty($googleUser['sub']) ||
    empty($googleUser['email'])
) {
    header('Location: ' . BASE_URL . '/login.php?google_erro=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| 6. Dados recebidos do Google
|--------------------------------------------------------------------------
*/

$googleId = $googleUser['sub'];
$email = strtolower(trim($googleUser['email']));
$nome = trim($googleUser['name'] ?? '');

if ($nome === '') {
    $nome = $email;
}

/*
|--------------------------------------------------------------------------
| 7. Conectar ao banco
|--------------------------------------------------------------------------
*/

$database = new Database();
$db = $database->conectar();

/*
|--------------------------------------------------------------------------
| 8. Procurar usuário pelo Google ID
|--------------------------------------------------------------------------
*/

$sql = "SELECT *
        FROM usuarios
        WHERE google_id = :google_id
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bindValue(':google_id', $googleId);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 9. Se não encontrou pelo Google ID,
|    procurar pelo e-mail
|--------------------------------------------------------------------------
*/

if (!$usuario) {

    $sql = "SELECT *
            FROM usuarios
            WHERE email = :email
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| 10. Usuário existente
|--------------------------------------------------------------------------
*/

if ($usuario) {

    /*
     * Não permitir login social em conta inativa.
     */
    if ($usuario['status'] !== 'Ativo') {
        header('Location: ' . BASE_URL . '/login.php?google_erro=inativo');
        exit;
    }

    /*
     * Conta existente deve ser Cliente para
     * ser vinculada ao login social público.
     */
    if ($usuario['tipo_usuario'] !== 'Cliente') {
        header('Location: ' . BASE_URL . '/login.php?google_erro=tipo');
        exit;
    }

    /*
     * Vincular Google à conta existente.
     */
    if (empty($usuario['google_id'])) {

        $sql = "UPDATE usuarios
                SET google_id = :google_id
                WHERE id_usuario = :id_usuario";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':google_id', $googleId);
        $stmt->bindValue(':id_usuario', $usuario['id_usuario'], PDO::PARAM_INT);
        $stmt->execute();

        $usuario['google_id'] = $googleId;
    }
}

/*
|--------------------------------------------------------------------------
| 11. Criar novo Cliente
|--------------------------------------------------------------------------
*/

if (!$usuario) {

    try {

        $db->beginTransaction();

        /*
         * Criar primeiro o registro do cliente.
         */
        $sql = "INSERT INTO clientes
                (
                    nome,
                    email
                )
                VALUES
                (
                    :nome,
                    :email
                )";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $idCliente = $db->lastInsertId();

        /*
         * Criar usuário BarberFlow.
         *
         * A senha recebe um hash aleatório porque
         * o login será feito pelo Google.
         */
        $senhaAleatoria = password_hash(
            bin2hex(random_bytes(32)),
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO usuarios
                (
                    id_cliente,
                    google_id,
                    nome,
                    email,
                    senha,
                    tipo_usuario,
                    status
                )
                VALUES
                (
                    :id_cliente,
                    :google_id,
                    :nome,
                    :email,
                    :senha,
                    'Cliente',
                    'Ativo'
                )";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(
            ':id_cliente',
            $idCliente,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':google_id',
            $googleId
        );

        $stmt->bindValue(
            ':nome',
            $nome
        );

        $stmt->bindValue(
            ':email',
            $email
        );

        $stmt->bindValue(
            ':senha',
            $senhaAleatoria
        );

        $stmt->execute();

        $idUsuario = $db->lastInsertId();

        $db->commit();

        /*
         * Montar o usuário recém-criado.
         */
        $usuario = [
            'id_usuario' => $idUsuario,
            'id_cliente' => $idCliente,
            'nome' => $nome,
            'email' => $email,
            'tipo_usuario' => 'Cliente'
        ];

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        header('Location: ' . BASE_URL . '/login.php?google_erro=1');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 12. Criar sessão do BarberFlow
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['usuario'] = [
    'id' => $usuario['id_usuario'],
    'id_cliente' => $usuario['id_cliente'],
    'nome' => $usuario['nome'],
    'email' => $usuario['email'],
    'tipo' => $usuario['tipo_usuario']
];

/*
|--------------------------------------------------------------------------
| 13. Redirecionar para a área do Cliente
|--------------------------------------------------------------------------
*/

header(
    'Location: ' .
    BASE_URL .
    '/modules/cliente/index.php'
);

exit;