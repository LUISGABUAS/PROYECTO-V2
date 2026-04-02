<?php
function registrarAuditoria($pdo, $id_usuario, $nombre_usuario, $accion, $tabla = null, $id_registro = null, $detalle = null) {
    try {
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO tb_auditoria (id_usuario, nombre_usuario, accion, tabla, id_registro, detalle, ip, fecha_hora) VALUES (:id_usuario, :nombre_usuario, :accion, :tabla, :id_registro, :detalle, :ip, :fecha_hora)");
        $stmt->execute([
            ':id_usuario'    => $id_usuario,
            ':nombre_usuario'=> $nombre_usuario,
            ':accion'        => $accion,
            ':tabla'         => $tabla,
            ':id_registro'   => $id_registro,
            ':detalle'       => $detalle,
            ':ip'            => $ip,
            ':fecha_hora'    => $fecha
        ]);
    } catch (Exception $e) {
        // silencioso para no romper el flujo
    }
}
