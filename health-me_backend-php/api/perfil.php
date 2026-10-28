<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$id = requerirSesionJson();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT nombre, apellido, dni, email, obra_social, rol, nivel
             FROM usuario WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        echo json_encode(['ok' => true, 'perfil' => $stmt->fetch()]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
        exit;
    }

    $nombre      = trim($_POST['nombre'] ?? '');
    $apellido    = trim($_POST['apellido'] ?? '');
    $dni         = trim($_POST['dni'] ?? '');
    $obraSocial  = trim($_POST['obra_social'] ?? '');
    $rol         = $_POST['rol'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100
        || mb_strlen($dni) > 20 || mb_strlen($obraSocial) > 150) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Revisa los datos ingresados.']);
        exit;
    }

    // Vacío => NULL (la columna dni es UNIQUE: dos '' chocarían entre sí)
    $apellido   = $apellido   === '' ? null : $apellido;
    $dni        = $dni        === '' ? null : $dni;
    $obraSocial = $obraSocial === '' ? null : $obraSocial;

    $pdo->prepare(
        'UPDATE usuario
         SET nombre = :nombre, apellido = :apellido, dni = :dni, obra_social = :os
         WHERE id = :id'
    )->execute([
        ':nombre' => $nombre, ':apellido' => $apellido,
        ':dni' => $dni, ':os' => $obraSocial, ':id' => $id,
    ]);

    // El rol solo se puede elegir una vez (si todavía es NULL) y solo un usuario "general"
    if (in_array($rol, ['acompañante', 'afectado'], true)) {
        $pdo->prepare(
            "UPDATE usuario SET rol = :rol
             WHERE id = :id AND rol IS NULL AND nivel = 'general'"
        )->execute([':rol' => $rol, ':id' => $id]);
    }

    $_SESSION['nombre']   = $nombre;
    $_SESSION['apellido'] = $apellido;
    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ese DNI ya está registrado.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'No pudimos guardar los cambios.']);
    }
}