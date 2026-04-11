<?php
// Listar tickets según permisos:
// perm 37 = ve TODOS | perm 35 = solo los propios

$puede_gestionar = in_array(37, $_SESSION['permisos'] ?? []);
$id_usuario_sesion = $_SESSION['id_usuario_sesion'] ?? 0;

if ($puede_gestionar) {
    $stmt = $pdo->prepare("
        SELECT t.*,
               u.nombres AS nombre_usuario,
               tec.nombres AS nombre_tecnico,
               COUNT(a.id_archivo) AS total_archivos
        FROM tb_tickets t
        LEFT JOIN tb_usuario u   ON u.id  = t.id_usuario
        LEFT JOIN tb_usuario tec ON tec.id = t.id_tecnico
        LEFT JOIN tb_tickets_archivos a ON a.id_ticket = t.id_ticket
        GROUP BY t.id_ticket
        ORDER BY
            FIELD(t.estado,'pendiente','en_progreso','resuelto'),
            FIELD(t.importancia,'critica','alta','media','baja'),
            t.fecha_creacion DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT t.*,
               u.nombres AS nombre_usuario,
               tec.nombres AS nombre_tecnico,
               COUNT(a.id_archivo) AS total_archivos
        FROM tb_tickets t
        LEFT JOIN tb_usuario u   ON u.id  = t.id_usuario
        LEFT JOIN tb_usuario tec ON tec.id = t.id_tecnico
        LEFT JOIN tb_tickets_archivos a ON a.id_ticket = t.id_ticket
        WHERE t.id_usuario = ?
        GROUP BY t.id_ticket
        ORDER BY
            FIELD(t.estado,'pendiente','en_progreso','resuelto'),
            FIELD(t.importancia,'critica','alta','media','baja'),
            t.fecha_creacion DESC
    ");
    $stmt->execute([$id_usuario_sesion]);
}

$tickets = $stmt->fetchAll();
