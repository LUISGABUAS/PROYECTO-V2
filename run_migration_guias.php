<?php
require_once 'app/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array(24, $_SESSION['permisos'] ?? [])) die('Sin permisos.');

$resultados = [];

// 1. Insertar permiso 40 si no existe
$existe = (bool)$pdo->query("SELECT COUNT(*) FROM permisos WHERE id_permiso = 40")->fetchColumn();
if (!$existe) {
    try {
        $pdo->exec("INSERT INTO permisos (id_permiso, nombre, seccion) VALUES (40, 'Gestionar Ventas Foráneas', 'Guías')");
        $resultados[] = ['ok', 'Permiso 40 (Gestionar Ventas Foráneas) creado'];
    } catch (Exception $e) {
        $resultados[] = ['error', 'Permiso 40: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['ok', 'Permiso 40 ya existía'];
}

// 2. Crear rol GUIAS si no existe
$id_rol_guias = null;
$existe_rol = $pdo->query("SELECT id_rol FROM tb_roles WHERE rol = 'GUIAS' LIMIT 1")->fetch();
if (!$existe_rol) {
    try {
        $pdo->exec("INSERT INTO tb_roles (rol, fyh_creacion) VALUES ('GUIAS', NOW())");
        $id_rol_guias = $pdo->lastInsertId();
        $resultados[] = ['ok', "Rol GUIAS creado (ID: $id_rol_guias)"];
    } catch (Exception $e) {
        $resultados[] = ['error', 'Rol GUIAS: ' . $e->getMessage()];
    }
} else {
    $id_rol_guias = $existe_rol['id_rol'];
    $resultados[] = ['ok', "Rol GUIAS ya existía (ID: $id_rol_guias)"];
}

// 3. Asignar permisos al rol GUIAS
// Permiso 40: Gestionar Ventas Foráneas (acceso completo a foraneos)
if ($id_rol_guias) {
    $permisos_guias = [40, 20]; // Gestionar Foráneas + Ver Ventas
    foreach ($permisos_guias as $p) {
        $existe_p = $pdo->prepare("SELECT COUNT(*) FROM tb_roles_permisos WHERE id_rol = ? AND id_permiso = ?");
        $existe_p->execute([$id_rol_guias, $p]);
        if (!(bool)$existe_p->fetchColumn()) {
            try {
                $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (?, ?)")->execute([$id_rol_guias, $p]);
                $resultados[] = ['ok', "Permiso $p asignado al rol GUIAS"];
            } catch (Exception $e) {
                $resultados[] = ['error', "Permiso $p: " . $e->getMessage()];
            }
        } else {
            $resultados[] = ['ok', "Permiso $p ya estaba asignado al rol GUIAS"];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Migración Rol GUIAS</title>
  <style>
    body { font-family: sans-serif; max-width: 620px; margin: 60px auto; }
    .ok    { color: green; }
    .error { color: red; }
    li { margin: 8px 0; font-size: 15px; }
    .btn { display:inline-block; margin-top:20px; padding:10px 20px;
           background:#28a745; color:#fff; text-decoration:none; border-radius:5px; }
    .info { background:#f0f8ff; border:1px solid #bee3f8; padding:15px; border-radius:6px; margin-top:20px; font-size:14px; }
  </style>
</head>
<body>
  <h2>Migración: Rol GUIAS</h2>
  <ul>
    <?php foreach ($resultados as [$tipo, $msg]): ?>
      <li class="<?= $tipo ?>"><?= $tipo === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?></li>
    <?php endforeach; ?>
  </ul>
  <div class="info">
    <strong>Permisos del rol GUIAS:</strong><br>
    • <b>20</b> — Ver Ventas<br>
    • <b>40</b> — Gestionar Ventas Foráneas (ver, editar, borrar guías y ventas foráneas)<br><br>
    Al iniciar sesión, este rol será redirigido automáticamente a <b>Ventas Foráneas</b>.
  </div>
  <a class="btn" href="<?= $URL ?>">Volver al sistema</a>
  <p style="color:#999;font-size:12px;margin-top:30px;">Puedes eliminar este archivo después de correr la migración.</p>
</body>
</html>
