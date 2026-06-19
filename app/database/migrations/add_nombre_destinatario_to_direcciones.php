<?php
function migrate_add_nombre_destinatario_to_direcciones($pdo) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM clientes_direcciones LIKE 'nombre_destinatario'");
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE clientes_direcciones ADD COLUMN nombre_destinatario VARCHAR(255) NULL DEFAULT NULL AFTER id_cliente");
            error_log("[MIGRATION] ✅ Columna 'nombre_destinatario' agregada a clientes_direcciones");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error: " . $e->getMessage());
        return false;
    }
}
migrate_add_nombre_destinatario_to_direcciones($pdo);
?>
