<?php
// Promover a administrador / volver a usuario general
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
$nivel      = $_POST['nivel'] ?? '';

if (!$idObjetivo || !in_array($nivel, ['general', 'administrador'], true)) {
    responderJson(400, ['ok' => false, 'mensaje' => 'Datos inválidos.']);
}
// Evita quedarse sin administradores
if ($idObjetivo === $idAdmin && $nivel !== 'administrador') {
    responderJson(400, ['ok' => false, 'mensaje' => 'No puedes quitarte tu propio rol de administrador.']);
}

try {
    $stmt = $pdo->prepare('SELECT activo FROM usuario WHERE id = :id');
    $stmt->execute([':id' => $idObjetivo]);
    $u = $stmt->fetch();
    if (!$u) {
        responderJson(404, ['ok' => false, 'mensaje' => 'El usuario no existe.']);
    }
    if ($nivel === 'administrador' && (int) $u['activo'] !== 1) {
        responderJson(400, ['ok' => false, 'mensaje' => 'Activa la cuenta antes de hacerla administrador.']);
    }

    $pdo->prepare('UPDATE usuario SET nivel = :n WHERE id = :id')
        ->execute([':n' => $nivel, ':id' => $idObjetivo]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'No se pudo cambiar el nivel.']);
}
