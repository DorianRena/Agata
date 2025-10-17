<?php
if (!defined('ALLOW_ACCESS')) {
    die('Accès interdit');
}
    define('DB_SERVER', getenv('DB_SERVER') ?: 'db');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'agata_db');
    define('DB_USER', getenv('DB_USER') ?: 'agata');
    define('DB_PWD', getenv('DB_PWD') ?: 'agata123');
?>
