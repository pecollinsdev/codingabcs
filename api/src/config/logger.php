<?php

return [
    'type' => 'database', // or 'file' for file-based logging
    'filePath' => __DIR__ . '/../logs/app.log',
    'dbConfig' => [
        'host' => $_ENV['LOG_DB_HOST'],
        'dbname' => $_ENV['LOG_DB_NAME'],
        'username' => $_ENV['LOG_DB_USER'],
        'password' => $_ENV['LOG_DB_PASS'],
        'charset' => 'utf8'
    ]
];
