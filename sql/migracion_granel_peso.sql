-- Migración para soportar inventario y ventas por peso/granel
-- Ejecutar en MySQL/MariaDB del proyecto WAMP

ALTER TABLE productos
  MODIFY COLUMN stock DECIMAL(10,3) NOT NULL DEFAULT 0.000 COMMENT 'Cantidad disponible en inventario',
  MODIFY COLUMN stock_minimo DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Stock mínimo para alerta',
  ADD COLUMN unidad_medida VARCHAR(20) NOT NULL DEFAULT 'unidad' AFTER stock_minimo,
  ADD COLUMN permite_decimales TINYINT(1) NOT NULL DEFAULT 0 AFTER unidad_medida;

ALTER TABLE detalle_ventas
  MODIFY COLUMN cantidad DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Cantidad vendida';

ALTER TABLE inventario_movimientos
  MODIFY COLUMN cantidad DECIMAL(10,3) NOT NULL COMMENT 'Cantidad del movimiento';
