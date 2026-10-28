<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$token     = $_POST['token'] ?? '';
$nueva     = $_POST['password_nueva'] ?? '';
$confirmar = $_POST['password_confirmar'] ?? '';

if ($nueva !== $confirmar) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
    exit;
}
if (($error = validarPassword($nueva)) !== null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => $error]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, id_usuario FROM password_reset
         WHERE token_hash = :h AND usado = 0 AND expira > NOW() LIMIT 1'
    );
    $stmt->execute([':h' => hash('sha256', $token)]);
    $r = $stmt->fetch();

    if (!$r) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'El enlace es inválido o ya venció.']);
        exit;
    }

    $pdo->beginTransaction();
    $pdo->prepare('UPDATE usuario SET password_hash = :h WHERE id = :id')
        ->execute([':h' => password_hash($nueva, PASSWORD_DEFAULT), ':id' => $r['id_usuario']]);
    $pdo->prepare('UPDATE password_reset SET usado = 1 WHERE id = :id')
        ->execute([':id' => $r['id']]);
    $pdo->commit();

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No pudimos restablecer la contraseña.']);
}