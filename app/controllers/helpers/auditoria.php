<?php
/**
 * Sistema de auditoría integrado con Logger
 */

function registrarAuditoria($pdo, $id_usuario, $nombre_usuario, $accion, $tabla = null, $id_registro = null, $detalle = null) {
    try {
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Registrar en BD (tabla de auditoría)
        $stmt = $pdo->prepare("INSERT INTO tb_auditoria (id_usuario, nombre_usuario, accion, tabla, id_registro, detalle, ip, fecha_hora) VALUES (:id_usuario, :nombre_usuario, :accion, :tabla, :id_registro, :detalle, :ip, :fecha_hora)");
        $stmt->execute([
            ':id_usuario'     => $id_usuario,
            ':nombre_usuario' => $nombre_usuario,
            ':accion'         => $accion,
            ':tabla'          => $tabla,
            ':id_registro'    => $id_registro,
            ':detalle'        => $detalle,
            ':ip'             => $ip,
            ':fecha_hora'     => $fecha
        ]);
        
        // Registrar en logger centralizado
        $accion_tipo = 'CREATE';
        if (strpos($accion, 'EDITAR') !== false || strpos($accion, 'ACTUALIZAR') !== false || strpos($accion, 'UPDATE') !== false) {
            $accion_tipo = 'UPDATE';
        } elseif (strpos($accion, 'ELIMINAR') !== false || strpos($accion, 'DELETE') !== false) {
            $accion_tipo = 'DELETE';
        }
        
        Logger::database($accion_tipo, $tabla, $id_registro, $id_usuario, $nombre_usuario, [
            'accion_detalle' => $accion,
            'detalle'        => $detalle
        ]);
        
    } catch (Exception $e) {
        // Log de error silencioso para no romper el flujo
        Logger::error500($e, [
            'ubicacion' => 'registrarAuditoria',
            'tabla'     => $tabla
        ]);
    }
}
