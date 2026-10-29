<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/cors.php';
header('Content-Type: application/json; charset=utf-8');

requerirAdminJson();

try {
    $stmt = $pdo->query(
        'SELECT id, nombre, apellido, email, dni, rol, nivel, activo, ultima_conexion
         FROM usuario
         ORDER BY apellido, nombre'
    );
    echo json_encode(['ok' => true, 'usuarios' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo obtener el listado.']);
}