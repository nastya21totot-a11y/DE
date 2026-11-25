<?php
return [
    'host' => getenv('RPD_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('RPD_DB_PORT') ?: '3306',
    'database' => 'rpd_portal',
    'username' => getenv('RPD_DB_USER') ?: 'root',
    'password' => getenv('RPD_DB_PASSWORD') ?: '',
    'storage' => getenv('RPD_STORAGE') ?: dirname(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) . '/rpd-storage',
];
