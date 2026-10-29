<?php
if (session_status() === PHP_SESSION_NONE) {
    // Detección robusta de HTTPS considerando proxys y servicios de hosting compartido
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
             (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
             (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => $https ? 'None' : 'Lax',
    ]);
    session_start();
}

function usuarioLogueado(): bool {
    return isset($_SESSION['id_usuario']);
}

function responderJson(int $codigo, array $cuerpo): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo);
    exit;
}

// Para endpoints JSON: corta con 401 si no hay sesión y devuelve el id
function requerirSesionJson(): int {
    if (!usuarioLogueado()) {
        responderJson(401, ['ok' => false, 'mensaje' => 'Inicia sesión.']);
    }
    return (int) $_SESSION['id_usuario'];
}

// Solo administradores. Se valida contra la BDD en cada request (no solo contra la sesión),
// así un admin desactivado o degradado pierde el acceso de inmediato.
// Requiere que conexion.php ya haya sido incluido (usa $pdo).
function requerirAdminJson(): int {
    global $pdo;
    $id = requerirSesionJson();

    $stmt = $pdo->prepare('SELECT nivel, activo FROM usuario WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $u = $stmt->fetch();

    if (!$u || (int) $u['activo'] !== 1) {
        $_SESSION = [];
        session_destroy();
        responderJson(401, ['ok' => false, 'mensaje' => 'Tu sesión ya no es válida.']);
    }
    if ($u['nivel'] !== 'administrador') {
        responderJson(403, ['ok' => false, 'mensaje' => 'No tienes permiso.']);
    }
    $_SESSION['nivel'] = $u['nivel'];
    return $id;
}

// Mitigación CSRF para acciones que modifican datos: un formulario de otro sitio
// no puede enviar este header sin pasar por el pre-vuelo de CORS.
function requerirAjax(): void {
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
        responderJson(403, ['ok' => false, 'mensaje' => 'Solicitud no permitida.']);
    }
}

// Reglas de contraseña: devuelve el mensaje de error o null si es válida
function validarPassword(string $p): ?string {
    if (strlen($p) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
        return 'La contraseña debe incluir letras y números.';
    }
    return null;
}