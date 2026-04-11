<?php
function migrate_create_tickets_tables($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'tb_tickets'");
        if ($check->rowCount() === 0) {
            $pdo->exec("CREATE TABLE tb_tickets (
                id_ticket    INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario   INT NOT NULL,
                titulo       VARCHAR(200) NOT NULL,
                descripcion  TEXT NOT NULL,
                importancia  ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
                estado       ENUM('pendiente','en_progreso','resuelto') NOT NULL DEFAULT 'pendiente',
                respuesta    TEXT NULL,
                id_tecnico   INT NULL,
                fecha_creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            error_log('[MIGRATION] ✅ Tabla tb_tickets creada');
        }

        $check2 = $pdo->query("SHOW TABLES LIKE 'tb_tickets_archivos'");
        if ($check2->rowCount() === 0) {
            $pdo->exec("CREATE TABLE tb_tickets_archivos (
                id_archivo      INT AUTO_INCREMENT PRIMARY KEY,
                id_ticket       INT NOT NULL,
                nombre_original VARCHAR(255) NOT NULL,
                ruta            VARCHAR(500) NOT NULL,
                tipo            VARCHAR(100) NOT NULL,
                tamano          INT NOT NULL,
                fecha_subida    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_ticket) REFERENCES tb_tickets(id_ticket) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            error_log('[MIGRATION] ✅ Tabla tb_tickets_archivos creada');
        }
        return true;
    } catch (Exception $e) {
        error_log('[MIGRATION] ❌ Error tickets: ' . $e->getMessage());
        return false;
    }
}
migrate_create_tickets_tables($pdo);
