<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

// Global Configuration Override
return [
    // 'db' => [
    //     'driver'   => 'Pdo_Pgsql',
    //     'hostname' => getenv('DB_HOST'),  
    //     'database' => getenv('DB_NAME'),
    //     'username' => getenv('DB_USER'),
    //     'password' => getenv('DB_PASSWORD'),
    //     'port'     => '5432',
    // ],
    'oracle' => [
        'username'         => 'Sapiens',
        'password'         => 'Sapiens',
        'connection_string' => '192.168.0.5:1521/SSF',
    ],
];

