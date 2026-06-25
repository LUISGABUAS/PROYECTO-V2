<?php
function migrate_create_print_queue_table($pdo) {
    try {
        // Crear tabla si no existe
        $check = $pdo->query("SHOW TABLES LIKE 'print_queue'");
        if ($check->rowCount() === 0) {
            $pdo->exec("CREATE TABLE print_queue (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                zpl        MEDIUMTEXT NOT NULL,
                status     ENUM('pendiente','procesando','completado','error') NOT NULL DEFAULT 'pendiente',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                printed_at DATETIME NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            error_log("[MIGRATION] ✅ Tabla print_queue creada");
        } else {
            // Agregar printed_at si no existe
            $col = $pdo->query("SHOW COLUMNS FROM print_queue LIKE 'printed_at'");
            if ($col->rowCount() === 0) {
                $pdo->exec("ALTER TABLE print_queue ADD COLUMN printed_at DATETIME NULL DEFAULT NULL");
                error_log("[MIGRATION] ✅ Columna printed_at agregada a print_queue");
            }
            // Resetear jobs atorados (procesando > 10 min)
            $pdo->exec("UPDATE print_queue SET status = 'pendiente'
                        WHERE status = 'procesando'
                        AND created_at < NOW() - INTERVAL 10 MINUTE");
        }
        return true;
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error print_queue: " . $e->getMessage());
        return false;
    }
}
migrate_create_print_queue_table($pdo);
