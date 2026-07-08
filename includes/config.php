<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'almas_hospital');

define('SITE_URL', 'http://localhost/Almas');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('SITE_NAME', 'Almas Hospital');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
