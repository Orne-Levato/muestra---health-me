<?php
/**
 * Inicia la sesión con opciones de cookie más seguras.
 * Usar este archivo antes de session_start() en los endpoints que manejan sesiones.
 */
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Evita aceptar IDs de sesión no inicializados y evita IDs en la URL.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    // En producción con HTTPS, la cookie queda marcada como Secure.
    // En localhost por HTTP, Secure debe ser false para que funcione en desarrollo.
    $usaHttps = isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $usaHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
