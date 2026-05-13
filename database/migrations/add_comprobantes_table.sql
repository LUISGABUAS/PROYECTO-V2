-- ============================================================
-- PASO 1: Crear tabla de comprobantes múltiples por venta
-- ============================================================
CREATE TABLE IF NOT EXISTS tb_ventas_comprobantes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_venta   INT          NOT NULL,
    ruta       VARCHAR(500) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_id_venta (id_venta),
    FOREIGN KEY (id_venta) REFERENCES tb_ventas(id_venta) ON DELETE CASCADE
);

-- ============================================================
-- PASO 2: Migrar comprobantes existentes de la columna antigua
-- Solo migra registros que aún no estén en la nueva tabla
-- ============================================================
INSERT INTO tb_ventas_comprobantes (id_venta, ruta)
SELECT id_venta, comprobante
FROM tb_ventas
WHERE comprobante IS NOT NULL
  AND comprobante != ''
  AND id_venta NOT IN (SELECT id_venta FROM tb_ventas_comprobantes);

-- ============================================================
-- PASO 3: Limpiar columna antigua (ya los datos están migrados)
-- ============================================================
UPDATE tb_ventas SET comprobante = NULL WHERE comprobante IS NOT NULL;
