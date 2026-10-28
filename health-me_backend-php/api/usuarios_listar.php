<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$idAdmin = requerirAdminJson();

// Filtros opcionales: q (texto), nivel, estado (1/0), pagina, por_pagina
$q         = trim((string) ($_GET['q'] ?? ''));
$nivel     = $_GET['nivel'] ?? '';
$estado    = $_GET['estado'] ?? '';
$pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = min(50, max(5, (int) ($_GET['por_pagina'] ?? 15)));
$offset    = ($pagina - 1) * $porPagina;

$where  = [];
$params = [];

if ($q !== '') {
    $like = '%' . addcslashes(mb_substr($q, 0, 100), '%_\\') . '%';
    $where[] = '(nombre LIKE :q1 OR apellido LIKE :q2 OR email LIKE :q3 OR dni LIKE :q4)';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = $like;
}
if (in_array($nivel, ['general', 'administrador'], true)) {
    $where[] = 'nivel = :nivel';
    $params[':nivel'] = $nivel;
}
if ($estado === '0' || $estado === '1') {
    $where[] = 'activo = :activo';
    $params[':activo'] = (int) $estado;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $c = $pdo->prepare("SELECT COUNT(*) FROM usuario $sqlWhere");
    $c->execute($params);
    $total = (int) $c->fetchColumn();

    // $porPagina y $offset son enteros ya validados
    $stmt = $pdo->prepare(
        "SELECT id, nombre, apellido, email, dni, rol, nivel, activo, ultima_conexion
         FROM usuario $sqlWhere
         ORDER BY apellido, nombre
         LIMIT $porPagina OFFSET $offset"
    );
    $stmt->execute($params);

    echo json_encode([
        'ok'         => true,
        'usuarios'   => $stmt->fetchAll(),
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $porPagina,
        'yo'         => $idAdmin,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'No se pudo obtener el listado.']);
}
