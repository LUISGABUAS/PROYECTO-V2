<?php
function migrate_add_id_direccion_to_ventas($pdo) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM tb_ventas LIKE 'id_direccion_entrega'");
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_ventas ADD COLUMN id_direccion_entrega INT NULL DEFAULT NULL AFTER cliente");
            error_log("[MIGRATION] ✅ Columna 'id_direccion_entrega' agregada a tb_ventas");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error agregando columna id_direccion_entrega: " . $e->getMessage());
        return false;
    }
}

migrate_add_id_direccion_to_ventas($pdo);
?>
