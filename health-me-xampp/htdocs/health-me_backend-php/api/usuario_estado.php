<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php';
header('Content-Type: application/json; charset=utf-8');

$idAdmin = requerirAdminJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}
requerirAjax();

$idObjetivo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
$valor      = $_POST['activo'] ?? '';

if (!$idObjetivo || ($valor !== '0' && $valor !== '1')) {
    responderJson(400, ['ok' => false, 'mensaje' => 'Datos inválidos.']);
}
$activo = (int) $valor;

// Como un admin no puede desactivarse a sí mismo, siempre queda al menos un admin activo.
if ($idObjetivo === $idAdmin && $activo === 0) {
    responderJson(400, ['ok' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
}

try {
    $existe = $pdo->prepare('SELECT 1 FROM usuario WHERE id = :id');
    $existe->execute([':id' => $idObjetivo]);
    if (!$existe->fetchColumn()) {
        responderJson(404, ['ok' => false, 'mensaje' => 'El usuario no existe.']);
    }

    $pdo->prepare('UPDATE usuario SET activo = :a WHERE id = :id')
        ->execute([':a' => $activo, ':id' => $idObjetivo]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'No se pudo actualizar el usuario.']);
}
