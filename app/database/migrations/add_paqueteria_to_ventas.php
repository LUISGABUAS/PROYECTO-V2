<?php
function migrate_add_paqueteria_to_ventas($pdo) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM tb_ventas LIKE 'paqueteria'");
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_ventas ADD COLUMN paqueteria VARCHAR(100) NULL DEFAULT NULL AFTER guia_pdf");
            error_log("[MIGRATION] ✅ Columna 'paqueteria' agregada a tb_ventas");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error: " . $e->getMessage());
        return false;
    }
}
migrate_add_paqueteria_to_ventas($pdo);
?>
