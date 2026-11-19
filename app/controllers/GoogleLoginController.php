```php
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$googleConfig = require __DIR__ . '/../config/google.php';

$state = bin2hex(random_bytes(32));

$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id' => $googleConfig['client_id'],
    'redirect_uri' => $googleConfig['redirect_uri'],
    'response_type' => 'code',
    'scope' => implode(' ', $googleConfig['scopes']),
    'state' => $state,
    'access_type' => 'online',
    'include_granted_scopes' => 'true',
    'prompt' => 'select_account'
];

$authorizationUrl =
    'https://accounts.google.com/o/oauth2/v2/auth?' .
    http_build_query($params);

header('Location: ' . $authorizationUrl);
exit;
