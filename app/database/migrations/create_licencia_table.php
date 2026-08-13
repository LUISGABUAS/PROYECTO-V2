<?php
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tb_licencia (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            periodo    VARCHAR(7)  NOT NULL,
            pagado     TINYINT(1)  NOT NULL DEFAULT 0,
            fecha_pago DATETIME    NULL,
            pagado_por INT         NULL,
            UNIQUE KEY uk_periodo (periodo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // silencioso — no rompe el sistema si falla
}
