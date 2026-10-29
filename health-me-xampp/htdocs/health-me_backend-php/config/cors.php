<?php
// Orígenes permitidos (incluyendo el subdirectorio de tu repositorio en GitHub Pages)
$permitidos = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:5500',   // Live Server de VS Code
];

$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origen, $permitidos, true)) {
    header("Access-Control-Allow-Origin: $origen");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Vary: Origin');
}

// Pre-vuelo del navegador (OPTIONS): responde y detiene la ejecución inmediatamente
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}