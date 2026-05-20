<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vexo_db');

function get_db_connection(bool $withDatabase = false): mysqli
{
    // Use socket for local XAMPP connection
    @$conn = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        '',
        DB_PORT,
        DB_SOCKET
    );

    if ($conn === false) {
        die('Connection failed: ' . mysqli_connect_error());
    }

    if ($withDatabase) {
        if (!mysqli_select_db($conn, DB_NAME)) {
            die('Database select failed: ' . mysqli_error($conn));
        }
    }

    return $conn;
}
