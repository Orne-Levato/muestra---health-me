<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$id = requerirSesionJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$actual    = $_POST['password_actual'] ?? '';
$nueva     = $_POST['password_nueva'] ?? '';
$confirmar = $_POST['password_confirmar'] ?? '';

if ($nueva !== $confirmar) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden.']);
    exit;
}
if (($error = validarPassword($nueva)) !== null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => $error]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM usuario WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila || !password_verify($actual, $fila['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensaje' => 'La contraseña actual es incorrecta.']);
        exit;
    }

    $pdo->prepare('UPDATE usuario SET password_hash = :h WHERE id = :id')
        ->execute([':h' => password_hash($nueva, PASSWORD_DEFAULT), ':id' => $id]);

    session_regenerate_id(true);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No pudimos cambiar la contraseña.']);
}