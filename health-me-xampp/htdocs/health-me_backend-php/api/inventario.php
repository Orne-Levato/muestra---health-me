<?php
// api/inventario.php
// Endpoint único del inventario. Recibe POST con "accion": listar | guardar | stock | eliminar
// Todas las consultas filtran por el usuario de la sesión: nadie ve ni toca el inventario de otro.

require_once __DIR__ . '/../config/cors.php';    // CORS con credenciales (frontend en otro host)
require_once __DIR__ . '/../config/auth.php';    // inicia la sesión y define requerirSesionJson()
require_once __DIR__ . '/../config/conexion.php'; // deja el objeto PDO en $pdo

$usuarioId = requerirSesionJson(); // corta con 401 si no hay sesión; devuelve $_SESSION['id_usuario']

date_default_timezone_set('America/Argentina/Buenos_Aires'); // el servidor puede estar en UTC
header('Content-Type: application/json; charset=utf-8');

function responder($ok, $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responder(false, ['mensaje' => 'Método no permitido.']);
}

$accion = $_POST['accion'] ?? '';
$hoy = date('Y-m-d');

// Valida y devuelve los campos numéricos del formulario o corta con error
function leerDatosMedicamento()
{
    $nombre = trim($_POST['nombre'] ?? '');
    $dosis = (float) ($_POST['dosis_por_toma'] ?? 0);
    $tomas = (int) ($_POST['tomas_por_dia'] ?? 0);
    $stock = (float) ($_POST['stock_actual'] ?? -1);
    $umbral = (int) ($_POST['umbral_dias'] ?? 0);

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        responder(false, ['mensaje' => 'Ingresá un nombre de hasta 100 caracteres.']);
    }
    if ($dosis <= 0 || $dosis > 20) {
        responder(false, ['mensaje' => 'Las pastillas por toma deben ser mayores a 0.']);
    }
    if ($tomas < 1 || $tomas > 12) {
        responder(false, ['mensaje' => 'Las tomas por día deben estar entre 1 y 12.']);
    }
    if ($stock < 0 || $stock > 100000) {
        responder(false, ['mensaje' => 'La cantidad de pastillas no es válida.']);
    }
    if ($umbral < 1 || $umbral > 30) {
        responder(false, ['mensaje' => 'Los días de aviso deben estar entre 1 y 30.']);
    }
    return [$nombre, $dosis, $tomas, $stock, $umbral];
}

try {
    switch ($accion) {
        case 'listar':
            $st = $pdo->prepare(
                'SELECT id, nombre, dosis_por_toma, tomas_por_dia, stock_base, fecha_base, umbral_dias
                 FROM inventario_medicamentos WHERE usuario_id = ? ORDER BY nombre'
            );
            $st->execute([$usuarioId]);
            responder(true, ['medicamentos' => $st->fetchAll(PDO::FETCH_ASSOC), 'hoy' => $hoy]);

        case 'guardar':
            list($nombre, $dosis, $tomas, $stock, $umbral) = leerDatosMedicamento();
            $id = (int) ($_POST['id'] ?? 0);

            if ($id > 0) {
                // Editar: el stock enviado pasa a ser la nueva base desde hoy
                $st = $pdo->prepare(
                    'UPDATE inventario_medicamentos
                     SET nombre = ?, dosis_por_toma = ?, tomas_por_dia = ?, stock_base = ?,
                         fecha_base = ?, umbral_dias = ?, ultimo_aviso = NULL
                     WHERE id = ? AND usuario_id = ?'
                );
                $st->execute([$nombre, $dosis, $tomas, $stock, $hoy, $umbral, $id, $usuarioId]);
            } else {
                $st = $pdo->prepare(
                    'INSERT INTO inventario_medicamentos
                     (usuario_id, nombre, dosis_por_toma, tomas_por_dia, stock_base, fecha_base, umbral_dias)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([$usuarioId, $nombre, $dosis, $tomas, $stock, $hoy, $umbral]);
            }
            responder(true);

        case 'stock':
            // Reposición o corrección de stock: reinicia la base y el control de avisos
            $id = (int) ($_POST['id'] ?? 0);
            $stock = (float) ($_POST['stock_actual'] ?? -1);
            if ($id <= 0 || $stock < 0 || $stock > 100000) {
                responder(false, ['mensaje' => 'Datos inválidos.']);
            }
            $st = $pdo->prepare(
                'UPDATE inventario_medicamentos
                 SET stock_base = ?, fecha_base = ?, ultimo_aviso = NULL
                 WHERE id = ? AND usuario_id = ?'
            );
            $st->execute([$stock, $hoy, $id, $usuarioId]);
            responder(true);

        case 'eliminar':
            $id = (int) ($_POST['id'] ?? 0);
            $st = $pdo->prepare('DELETE FROM inventario_medicamentos WHERE id = ? AND usuario_id = ?');
            $st->execute([$id, $usuarioId]);
            responder(true);

        default:
            responder(false, ['mensaje' => 'Acción desconocida.']);
    }
} catch (PDOException $e) {
    error_log('inventario.php: ' . $e->getMessage());
    http_response_code(500);
    responder(false, ['mensaje' => 'Error del servidor.']);
}
