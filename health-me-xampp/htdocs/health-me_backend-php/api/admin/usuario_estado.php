<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/cors.php';
header('Content-Type: application/json; charset=utf-8');

$idAdmin = requerirAdminJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$idObjetivo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
$activo     = ($_POST['activo'] ?? '') === '1' ? 1 : 0;

if (!$idObjetivo) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario inválido.']);
    exit;
}
if ($idObjetivo === $idAdmin && $activo === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE usuario SET activo = :a WHERE id = :id');
    $stmt->execute([':a' => $activo, ':id' => $idObjetivo]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo actualizar el usuario.']);
}