<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function noAutorizado(): void {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

if (!usuarioLogueado()) noAutorizado();

$stmt = $pdo->prepare('SELECT nombre, apellido, rol, nivel, activo FROM usuario WHERE id = :id');
$stmt->execute([':id' => $_SESSION['id_usuario']]);
$u = $stmt->fetch();

if (!$u || (int) $u['activo'] !== 1) noAutorizado();

echo json_encode(['ok' => true, 'usuario' => [
    'nombre' => $u['nombre'], 'apellido' => $u['apellido'],
    'rol' => $u['rol'], 'nivel' => $u['nivel'],
]]);