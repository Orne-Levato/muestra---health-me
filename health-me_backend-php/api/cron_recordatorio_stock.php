<?php
// api/cron_recordatorio_stock.php
// Manda un mail por usuario con los medicamentos que están por agotarse.
// Se ejecuta UNA vez por día desde un cron externo (ver instrucciones al final).
// Avisa todos los días hasta que el usuario reponga el stock (la reposición borra ultimo_aviso).

require_once __DIR__ . '/../config/conexion.php'; // deja el objeto PDO en $pdo

date_default_timezone_set('America/Argentina/Buenos_Aires');

// La clave secreta vive en config/config.local.php (que no se sube a GitHub). Agregar ahí:
//   'cron_token' => 'UNA_CLAVE_LARGA_Y_ALEATORIA',
//   'mail_remitente' => 'no-reply@healthme.com',
$config = require __DIR__ . '/../config/config.local.php';
$tokenEsperado = $config['cron_token'] ?? '';
$remitente = $config['mail_remitente'] ?? 'no-reply@healthme.com';

$token = $_GET['token'] ?? '';
if ($tokenEsperado === '' || !is_string($token) || !hash_equals($tokenEsperado, $token)) {
    http_response_code(403);
    exit('Acceso denegado');
}

$hoy = date('Y-m-d');

$st = $pdo->prepare(
    'SELECT i.id, i.usuario_id, i.nombre, i.dosis_por_toma, i.tomas_por_dia, i.stock_base,
            DATEDIFF(?, i.fecha_base) AS dias_pasados, i.umbral_dias,
            u.email, u.nombre AS usuario_nombre
     FROM inventario_medicamentos i
     JOIN usuario u ON u.id = i.usuario_id
     WHERE u.activo = 1 AND (i.ultimo_aviso IS NULL OR i.ultimo_aviso < ?)'
);
$st->execute([$hoy, $hoy]);

$porUsuario = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
    $consumo = $f['dosis_por_toma'] * $f['tomas_por_dia'];
    $restantes = max(0, $f['stock_base'] - max(0, $f['dias_pasados']) * $consumo);
    $dias = $consumo > 0 ? (int) floor($restantes / $consumo) : null;

    if ($restantes > 0 && ($dias === null || $dias > $f['umbral_dias'])) {
        continue; // todavía tiene stock de sobra
    }

    $porUsuario[$f['usuario_id']]['email'] = $f['email'];
    $porUsuario[$f['usuario_id']]['nombre'] = $f['usuario_nombre'];
    $porUsuario[$f['usuario_id']]['ids'][] = $f['id'];
    $porUsuario[$f['usuario_id']]['lineas'][] = $restantes <= 0
        ? '- ' . $f['nombre'] . ': se terminó.'
        : '- ' . $f['nombre'] . ': quedan ' . $restantes . ' pastillas (alcanzan para ' . $dias . ' día/s).';
}

$marcar = $pdo->prepare('UPDATE inventario_medicamentos SET ultimo_aviso = ? WHERE id = ?');
$enviados = 0;

foreach ($porUsuario as $datos) {
    $asunto = 'HealthMe: es momento de comprar tus medicamentos';
    $cuerpo = "Hola " . $datos['nombre'] . ",\n\n"
        . "Estos medicamentos se están por terminar:\n\n"
        . implode("\n", $datos['lineas']) . "\n\n"
        . "Cuando los compres, actualizá el stock en HealthMe para dejar de recibir este aviso.\n";
    $cabeceras = 'From: ' . $remitente . "\r\nContent-Type: text/plain; charset=UTF-8";

    if (mail($datos['email'], $asunto, $cuerpo, $cabeceras)) {
        foreach ($datos['ids'] as $id) {
            $marcar->execute([$hoy, $id]);
        }
        $enviados++;
    } else {
        error_log('cron_recordatorio_stock: falló el envío a usuario con medicamentos ' . implode(',', $datos['ids']));
    }
}

echo "Listo. Mails enviados: $enviados";

/* OPCIONAL: este aviso por mail es un extra. El aviso dentro de la app (globito + cartel)
   ya funciona sin esto.
   - Hoy el proyecto no envía mails (solicitar_recuperacion.php solo escribe el link en el log),
     y mail() no funciona en XAMPP sin configurar. Cuando el equipo defina cómo enviar correos
     (PHPMailer/SMTP, etc.), reemplazar la llamada a mail() de acá por ese método.
   - Programarlo: una vez por día (ej. 09:00) con el Programador de tareas de Windows, un cron
     del hosting, o un servicio como cron-job.org, llamando a:
     https://TU-DOMINIO/api/cron_recordatorio_stock.php?token=LA_CLAVE */
