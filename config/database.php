<?php

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'dbname' => getenv('DB_NAME') ?: 'pc_shop',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'timezone' => getenv('DB_TIMEZONE') ?: '+07:00',
    'ssl_mode' => getenv('DB_SSL_MODE') ?: '',
    'ssl_ca_path' => getenv('DB_SSL_CA_PATH') ?: '',
    'ssl_ca_base64' => getenv('DB_SSL_CA_BASE64') ?: '',
    'ssl_verify_server_cert' => getenv('DB_SSL_VERIFY_SERVER_CERT') === 'true',
];
