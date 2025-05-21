<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', ''); 

define('AUTHORIZENET_API_LOGIN_ID', '5t8T6g4tS76'); 
define('AUTHORIZENET_TRANSACTION_KEY', '987ZE7TcC5bvp4m4'); 
define('AUTHORIZENET_SANDBOX', true); 

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
