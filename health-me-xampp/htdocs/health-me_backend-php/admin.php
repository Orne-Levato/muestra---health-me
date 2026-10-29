<?php
require_once __DIR__ . '/api/verificar_sesion.php';
if (($_SESSION['nivel'] ?? '') !== 'administrador') {
    header('Location: user.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HealthMe - Administración</title>
  <link rel="stylesheet" href="/health-me_frontend/src/assets/css/styles.css">
  <style>
    .adm-barra { display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; margin:1rem 0; }
    .adm-barra input, .adm-barra select { padding:.5rem .75rem; border:1px solid #c5ced6; border-radius:8px; font:inherit; }
    .adm-barra input[type="search"] { flex:1 1 240px; }
    #tablaUsuarios th, #tablaUsuarios td { padding:.6rem .75rem; border-bottom:1px solid #e3e8ec; text-align:left; }
    #tablaUsuarios button, #tablaUsuarios select { font:inherit; padding:.3rem .6rem; border-radius:6px; }
    .adm-badge { display:inline-block; padding:.15rem .6rem; border-radius:999px; font-size:.85rem; }
    .adm-badge.on  { background:#e0f5e9; color:#1b6b3a; }
    .adm-badge.off { background:#fde8e8; color:#9b1c1c; }
    .adm-msg.ok  { color:#1b6b3a; }
    .adm-msg.err { color:#9b1c1c; }
    .adm-pie { display:flex; gap:1rem; align-items:center; justify-content:space-between; margin-top:1rem; }
  </style>
</head>
<body>
  <main style="padding:2rem; max-width:1200px; margin:auto;">
    <h1>Gestión de usuarios</h1>
    <p><a href="user.php">Ir a mi panel</a> · <a href="#" id="btnLogout">Cerrar sesión</a></p>

    <div class="adm-barra">
      <input type="search" id="fBusqueda" placeholder="Buscar por nombre, email o DNI" aria-label="Buscar usuarios">
      <select id="fNivel" aria-label="Filtrar por nivel">
        <option value="">Todos los niveles</option>
        <option value="general">General</option>
        <option value="administrador">Administrador</option>
      </select>
      <select id="fEstado" aria-label="Filtrar por estado">
        <option value="">Todos los estados</option>
        <option value="1">Activos</option>
        <option value="0">Desactivados</option>
      </select>
    </div>

    <p id="msgAdmin" class="adm-msg" role="status" aria-live="polite"></p>

    <div style="overflow-x:auto;">
      <table id="tablaUsuarios" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th>Nombre</th><th>Email</th><th>DNI</th><th>Rol</th>
            <th>Nivel</th><th>Última conexión</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="adm-pie">
      <span id="resumen"></span>
      <span>
        <button type="button" id="btnPrev">← Anterior</button>
        <button type="button" id="btnNext">Siguiente →</button>
      </span>
    </div>
  </main>
  <script src="/health-me_frontend/src/assets/js/admin.js"></script>
</body>
</html>