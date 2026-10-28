<?php

//pag con seguridad ;) 

/**
 * Portero para las páginas privadas.
 * Este archivo se incluye al principio de user.php, antes de imprimir HTML.
 */
require_once __DIR__ . '/sesion_segura.php';
iniciar_sesion_segura();

// Evita que el navegador reutilice una copia almacenada del panel privado.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// login_usuario.php guarda el ID autenticado en esta clave.
if (!isset($_SESSION['id_usuario']) || !is_scalar($_SESSION['id_usuario'])
    || (string) $_SESSION['id_usuario'] === '') {
    header('Location: https://lucaromano-int.github.io/HEALTH-ME/src/views/login.html');
    exit;
}