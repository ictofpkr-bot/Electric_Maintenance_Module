<?php
function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = getenv('EMS_DB_DSN') ?: 'odbc:DRIVER={IBM INFORMIX ODBC DRIVER};HOST=localhost;SERVER=ol_informix;DATABASE=ems_db;PROTOCOL=onsoctcp;PORT=9088;';
    $username = getenv('EMS_DB_USER') ?: 'informix';
    $password = getenv('EMS_DB_PASSWORD') ?: 'secret';

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}
