-- ================================================
-- ÍNDICES PARA OPTIMIZAR RENDIMIENTO
-- Ejecutar en phpMyAdmin o tu cliente MySQL
-- ================================================

-- stock
CREATE INDEX idx_stock_producto_estado ON stock(id_producto, estado);
CREATE INDEX idx_stock_creado_por      ON stock(creado_por);
CREATE INDEX idx_stock_fecha           ON stock(fecha_ingreso);

-- tb_almacen
CREATE INDEX idx_almacen_categoria ON tb_almacen(id_categoria);
CREATE INDEX idx_almacen_usuario    ON tb_almacen(id_usuario);
CREATE INDEX idx_almacen_codigo     ON tb_almacen(codigo);
CREATE INDEX idx_almacen_proveedor  ON tb_almacen(id_proovedor);

-- tb_ventas_detalle
CREATE INDEX idx_vd_venta           ON tb_ventas_detalle(id_venta);
CREATE INDEX idx_vd_producto        ON tb_ventas_detalle(id_producto);
CREATE INDEX idx_vd_producto_entrega ON tb_ventas_detalle(id_producto, cantidad_entregada);

-- tb_ventas
CREATE INDEX idx_ventas_usuario         ON tb_ventas(id_usuario);
CREATE INDEX idx_ventas_cliente         ON tb_ventas(cliente);
CREATE INDEX idx_ventas_fecha           ON tb_ventas(fecha);
CREATE INDEX idx_ventas_estado_logistico ON tb_ventas(estado_logistico);
CREATE INDEX idx_ventas_envio           ON tb_ventas(envio);

-- tb_usuario
CREATE INDEX idx_usuario_email   ON tb_usuario(email);
CREATE INDEX idx_usuario_id_rol  ON tb_usuario(id_rol);

-- tb_roles_permisos
CREATE INDEX idx_rp_rol     ON tb_roles_permisos(id_rol);
CREATE INDEX idx_rp_permiso ON tb_roles_permisos(id_permiso);

-- clientes
CREATE INDEX idx_clientes_tipo ON clientes(tipo_cliente);

-- tb_auditoria
CREATE INDEX idx_auditoria_fecha    ON tb_auditoria(fecha_hora);
CREATE INDEX idx_auditoria_usuario  ON tb_auditoria(id_usuario);
