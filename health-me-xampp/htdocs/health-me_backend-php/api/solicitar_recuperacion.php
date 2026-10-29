<?php
require_once __DIR__ . '/../config/conexion.php';
$frontend_url = '/health-me_frontend';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
// Siempre la misma respuesta, exista o no el email (no revela qué correos están registrados)
$respuesta = ['ok' => true, 'mensaje' => 'Si el correo existe, te enviamos instrucciones.'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode($respuesta);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM usuario WHERE email = :e AND activo = 1');
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch();

    if ($u) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO password_reset (id_usuario, token_hash, expira)
             VALUES (:u, :h, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute([':u' => $u['id'], ':h' => hash('sha256', $token)]);

        $link = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/health-me_frontend/src/views/recuperar.html?token=' . $token;
        error_log('LINK RECUPERACION: ' . $link);   // XAMPP: logs/php_error_log
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

echo json_encode($respuesta);