-- Tabla de descuentos por producto
CREATE TABLE IF NOT EXISTS tb_descuentos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  id_producto     INT            NOT NULL,
  precio_original DECIMAL(10,2)  NOT NULL,
  precio_descuento DECIMAL(10,2) NOT NULL,
  porcentaje      DECIMAL(5,2)   NOT NULL,
  fecha_inicio    DATETIME       NOT NULL,
  fecha_fin       DATETIME       NOT NULL,
  created_at      DATETIME       DEFAULT NOW(),
  INDEX idx_producto (id_producto),
  INDEX idx_vigencia (id_producto, fecha_inicio, fecha_fin)
);

-- Referencia del descuento aplicado en cada línea de venta
ALTER TABLE tb_ventas_detalle
  ADD COLUMN IF NOT EXISTS id_descuento INT NULL DEFAULT NULL;
