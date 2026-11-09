<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'school_schedule',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'app' => [
        'url' => getenv('APP_URL') ?: 'http://localhost',
        'name' => getenv('APP_NAME') ?: 'Gestion Emploi du Temps',
        'env' => getenv('APP_ENV') ?: 'development',
    ],
    'mail' => [
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port' => getenv('MAIL_PORT') ?: 587,
        'username' => getenv('MAIL_USERNAME'),
        'password' => getenv('MAIL_PASSWORD'),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_address' => getenv('MAIL_FROM_ADDRESS'),
        'from_name' => getenv('MAIL_FROM_NAME'),
    ]
]; 