<?php
function migrate_create_pedidos_table($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'tb_pedidos'");
        if ($check->rowCount() === 0) {
            $pdo->exec("CREATE TABLE tb_pedidos (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente  INT NOT NULL,
                id_usuario  INT NOT NULL,
                fecha       DATE NOT NULL,
                comprobante VARCHAR(500) NULL,
                total       DECIMAL(10,2) NOT NULL DEFAULT 0,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pedido_cliente (id_cliente),
                INDEX idx_pedido_usuario (id_usuario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            error_log("[MIGRATION] ✅ Tabla 'tb_pedidos' creada");
        }

        $check2 = $pdo->query("SHOW COLUMNS FROM tb_ventas LIKE 'id_pedido'");
        if ($check2->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_ventas ADD COLUMN id_pedido INT NULL DEFAULT NULL AFTER id_venta");
            error_log("[MIGRATION] ✅ Columna 'id_pedido' agregada a tb_ventas");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error: " . $e->getMessage());
        return false;
    }
}
migrate_create_pedidos_table($pdo);
?>
