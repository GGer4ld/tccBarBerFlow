<?php

/**
 * Configurações da autenticação com Google.
 *
 * IMPORTANTE:
 * Não compartilhe este arquivo publicamente.
 * Não publique o Client Secret em repositórios.
 */

return [
    'client_id' => '536466512748-5p3jd0vs9th8t05k6huv7ttm57tgtfok.apps.googleusercontent.com',

    'client_secret' => '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',

    'redirect_uri' =>
        'http://localhost:8080/TCCBARBERFLOW/app/controllers/GoogleCallbackController.php',

    'scopes' => [
        'openid',
        'email',
        'profile'
    ]
];