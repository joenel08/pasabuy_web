<?php

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'pasabuy',
        'user'    => 'u322243889_pasabuy',
        'pass'    => 'Pasabuy_db123',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'  => 'PasaBuy Mo, Pasaya Mo',
        // 'url'   => 'http://192.168.100.4/pasabuy/api',
        // 'url'   => 'http://10.87.229.154/pasabuy/api',

        'url' => 'https://pasabuy.isuc-ccsict.com/api',
        'debug' => true,
    ],
    'fees' => [
        'admin_rate' => 0.02,  
    ],
    'mail' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'username'   => 'anywherefind2@gmail.com',
        'password'   => 'bken kxvu pcsf zhuo',   // Gmail App Password
        'encryption' => 'tls',                    // STARTTLS
        'from_email' => 'anywherefind2@gmail.com',
        'from_name'  => 'PasaBuy Mo, Pasaya Mo',
    ],
    'storage' => [
        'ids'  => __DIR__ . '/../storage/ids',
        'logs' => __DIR__ . '/../storage/logs',
    ],
    'fcm' => [
        'service_account' => __DIR__ . '/firebase-service-account.json',
    ],
];
