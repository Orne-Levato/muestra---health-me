<?php

//pagina con seguridad para registrar un usuario ;P

// Conexión con BDD
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';


// Respuesta JSON para que login.js pueda interpretar el resultado.
header('Content-Type: application/json; charset=utf-8');

// Solo se aceptan envíos por POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

// Rechazar arreglos enviados en lugar de strings.
$campos = ['nombre', 'apellido', 'dni', 'email', 'password', 'confirmPassword'];
$entrada = [];
foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? null;
    if (!is_string($valor)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Completa todos los campos con datos válidos.']);
        exit;
    }
    $entrada[$campo] = $valor;
}

// Datos recibidos desde login.js (coinciden con los name de registerForm)
$nombre          = trim($_POST['nombre'] ?? '');
$apellido        = trim($_POST['apellido'] ?? '');
$dni             = trim($_POST['dni'] ?? '');
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

// Comprobar existencia, formato y tamaño de los campos.
if ($nombre === '' || $apellido === '' || $dni === '' || $email === ''
    || $password === '' || $confirmPassword === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Completa todos los campos con datos válidos.']);
    exit;
}



// DNI argentino: aceptar únicamente 7 u 8 dígitos, sin puntos ni guiones.
// Si el formulario actual permite otra clase de identificación, adaptar esta regla.
if (!ctype_digit($dni) || strlen($dni) < 7 || strlen($dni) > 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'El DNI debe tener 7 u 8 dígitos, sin puntos ni guiones.']);
    exit;
}




// Limitar la longitud de los campos para evitar abusos y ataques de denegación de servicio.
if (mb_strlen($nombre, 'UTF-8') > 100 || mb_strlen($apellido, 'UTF-8') > 100
    || mb_strlen($email, 'UTF-8') > 150) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Alguno de los campos supera el largo permitido.']);
    exit;
}



// No imponer límites solo desde HTML: se validan también en el servidor.
if (mb_strlen($password, 'UTF-8') < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}

// PASSWORD_DEFAULT puede usar bcrypt, que solo considera los primeros 72 bytes.
if (strlen($password) > 72) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'La contraseña es demasiado larga (máximo 72 bytes).']);
    exit;
}

// Las dos contraseñas deben coincidir

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
    exit;
}


$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Conexión con BDD: inserta el nuevo usuario en la tabla usuario
    // Consulta preparada: evita concatenar entradas del usuario dentro del SQL.
    $stmt = $pdo->prepare(
        'INSERT INTO usuario (nombre, apellido, dni, email, password_hash)
         VALUES (:nombre, :apellido, :dni, :email, :password_hash)'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':dni' => $dni,
        ':email' => $email,
        ':password_hash' => $passwordHash,
    ]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    // El código SQLSTATE 23000 suele indicar una clave única duplicada.
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ese correo o DNI ya está registrado.']);
    } else {
        error_log('Error en registrar_usuario.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'No pudimos crear la cuenta.']);
    }
}

