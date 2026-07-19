<?php
require_once 'app/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array(24, $_SESSION['permisos'] ?? [])) die('Sin permisos.');

$resultados = [];

// 1. Columnas en tb_ventas
$cols = [
    'id_repartidor' => "ALTER TABLE tb_ventas ADD COLUMN id_repartidor INT NULL DEFAULT NULL",
    'estado_reparto' => "ALTER TABLE tb_ventas ADD COLUMN estado_reparto VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'",
    'fecha_entrega'  => "ALTER TABLE tb_ventas ADD COLUMN fecha_entrega DATETIME NULL DEFAULT NULL",
];
foreach ($cols as $col => $sql) {
    $existe = (bool)$pdo->query("SHOW COLUMNS FROM tb_ventas LIKE '$col'")->fetchColumn();
    if (!$existe) {
        try { $pdo->exec($sql); $resultados[] = ['ok', "Columna <b>$col</b> agregada a tb_ventas"]; }
        catch (Exception $e) { $resultados[] = ['error', "$col: " . $e->getMessage()]; }
    } else {
        $resultados[] = ['ok', "Columna <b>$col</b> ya existía"];
    }
}

// 2. Permiso 41
$existe = (bool)$pdo->query("SELECT COUNT(*) FROM permisos WHERE id_permiso = 41")->fetchColumn();
if (!$existe) {
    try {
        $pdo->exec("INSERT INTO permisos (id_permiso, nombre, seccion) VALUES (41, 'Gestionar Repartos', 'Repartos')");
        $resultados[] = ['ok', 'Permiso 41 (Gestionar Repartos) creado'];
    } catch (Exception $e) { $resultados[] = ['error', 'Permiso 41: ' . $e->getMessage()]; }
} else {
    $resultados[] = ['ok', 'Permiso 41 ya existía'];
}

// 3. Rol REPARTIDOR
$existe_rol = $pdo->query("SELECT id_rol FROM tb_roles WHERE rol = 'REPARTIDOR' LIMIT 1")->fetch();
if (!$existe_rol) {
    try {
        $pdo->exec("INSERT INTO tb_roles (rol, fyh_creacion) VALUES ('REPARTIDOR', NOW())");
        $id_rol = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (?, 41)")->execute([$id_rol]);
        $resultados[] = ['ok', "Rol REPARTIDOR creado (ID: $id_rol) con permiso 41"];
    } catch (Exception $e) { $resultados[] = ['error', 'Rol REPARTIDOR: ' . $e->getMessage()]; }
} else {
    $resultados[] = ['ok', "Rol REPARTIDOR ya existía (ID: {$existe_rol['id_rol']})"];
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración Repartos</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto}.ok{color:green}.error{color:red}li{margin:8px 0;font-size:15px}.btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#28a745;color:#fff;text-decoration:none;border-radius:5px}</style>
</head><body>
<h2>Migración: Sistema de Repartos</h2>
<ul><?php foreach($resultados as [$t,$m]): ?><li class="<?=$t?>"><?=$t==='ok'?'✅':'❌'?> <?=$m?></li><?php endforeach; ?></ul>
<a class="btn" href="<?=$URL?>">Volver al sistema</a>
<p style="color:#999;font-size:12px;margin-top:20px">Puedes eliminar este archivo después.</p>
</body></html>
