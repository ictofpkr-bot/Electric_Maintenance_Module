<?php
$dsn = 'odbc:DRIVER={IBM INFORMIX ODBC DRIVER};HOST=localhost;SERVER=ol_informix;DATABASE=ems_db;UID=informix;PWD=secret;PROTOCOL=onsoctcp;PORT=9088;';

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    die('Database connection failed.');
}
