<?php

return [
    'token_url' => 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token',
    'installment' => [
        'order_url' => 'https://installment.bog.ge/v1/installment/checkout',
        'success_url' => 'https://iapi.ge/api/installment/success',
        'fail_url' => 'https://iapi.ge/api/installment/fail',
        'reject_url' => 'https://iapi.ge/api/installment/reject',
        'public_key' => '57315',
        'secret_key' => 'a65MobkuEwVr',
    ],
    'payment' => [
        'api_url' => 'https://api.bog.ge/payments/v1/ecommerce/orders',
        'public_key' => '10003075',
        'secret_key' => 'sziq796zJImm'
    ],
];