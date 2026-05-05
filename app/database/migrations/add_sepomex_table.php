<?php
$pdo->exec("CREATE TABLE IF NOT EXISTS sepomex_colonias (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    cp       VARCHAR(5)   NOT NULL,
    colonia  VARCHAR(120) NOT NULL,
    municipio VARCHAR(120) NOT NULL,
    estado   VARCHAR(80)  NOT NULL,
    INDEX idx_cp (cp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
