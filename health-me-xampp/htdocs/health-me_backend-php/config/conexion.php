<?php
// Conexión local para XAMPP: usuario "root" sin contraseña (valores por defecto).
// Si le pusiste contraseña a MySQL, cambiá $password.
$host     = '127.0.0.1';
$dbname   = 'healthme';
$usuario  = 'root';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $usuario, $password, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    die('Error de conexión con la base de datos. ¿Está MySQL iniciado en XAMPP y importaste database/setup_local.sql?');
}
