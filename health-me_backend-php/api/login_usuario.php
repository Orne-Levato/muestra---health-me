<?php
// 1. Detección y validación dinámica del origen (la raíz del dominio que envía el navegador)
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
$permitidos = [
    'https://lucaromano-int.github.io'
];

if (in_array($origen, $permitidos, true)) {
    header("Access-Control-Allow-Origin: $origen");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Vary: Origin');
}

header('Content-Type: application/json; charset=utf-8');

// 2. Respuesta inmediata para solicitudes de pre-vuelo (OPTIONS)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 3. Validación estricta del método HTTP permitido
if (($_SERVER['REQUEST_METHOD'] ?? '') != 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

// 4. Inclusión de dependencias y configuración de base de datos
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

// 5. Recepción, saneamiento y validación de credenciales
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

$emailRecibido    = $datos['email'] ?? null;
$passwordRecibida = $datos['password'] ?? null;

if (!is_string($emailRecibido) || !is_string($passwordRecibida)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos.']);
    exit;
}

$email    = trim($emailRecibido);
$password = $passwordRecibida;

if ($email === '' || $password === '' || strlen($email) > 254
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) > 4096) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Completa los campos con datos válidos.']);
    exit;
}

// 6. Autenticación de usuario y consulta en base de datos
try {
    $stmt = $pdo->prepare(
        'SELECT id, nombre, apellido, password_hash, rol, nivel, activo
         FROM usuario
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensaje' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // Verificación de estado activo y gestión de sesión
    if ((int) $usuario['activo'] != 1) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'Tu cuenta está desactivada. Contacta a soporte.']);
        exit;
    }

    session_regenerate_id(true);

    $pdo->prepare('UPDATE usuario SET ultima_conexion = NOW() WHERE id = :id')
        ->execute([':id' => $usuario['id']]);

    $_SESSION['id_usuario'] = (int) $usuario['id'];
    $_SESSION['nombre']     = $usuario['nombre'];
    $_SESSION['apellido']   = $usuario['apellido'];
    $_SESSION['rol']        = $usuario['rol'];
    $_SESSION['nivel']      = $usuario['nivel'];

    $destino = ($usuario['nivel'] === 'administrador') ? 'admin.php' : 'user.php';
    echo json_encode(['ok' => true, 'destino' => $destino]);

// Control de excepciones y errores de servidor
} catch (PDOException $e) {
    error_log('Error en login_usuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No pudimos iniciar sesión. Intenta nuevamente.']);
}