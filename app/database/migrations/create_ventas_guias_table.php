<?php
function migrate_create_ventas_guias_table($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'tb_ventas_guias'");
        if ($check->rowCount() === 0) {
            $pdo->exec("CREATE TABLE tb_ventas_guias (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                id_venta   INT NOT NULL,
                numero     INT NOT NULL DEFAULT 1,
                archivo    VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_venta_guias (id_venta)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            error_log("[MIGRATION] ✅ Tabla 'tb_ventas_guias' creada");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error: " . $e->getMessage());
        return false;
    }
}
migrate_create_ventas_guias_table($pdo);
?>
