<?php
function migrate_add_pago_pendiente_notas_to_ventas($pdo) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM tb_ventas LIKE 'monto_pendiente'");
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_ventas
                ADD COLUMN monto_pendiente DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total,
                ADD COLUMN metodo_pendiente VARCHAR(20) NULL DEFAULT NULL AFTER monto_pendiente,
                ADD COLUMN notas TEXT NULL DEFAULT NULL AFTER metodo_pendiente
            ");
            error_log("[MIGRATION] ✅ Columnas monto_pendiente, metodo_pendiente, notas agregadas a tb_ventas");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error: " . $e->getMessage());
        return false;
    }
}
migrate_add_pago_pendiente_notas_to_ventas($pdo);
