-- =============================================================
-- MIGRACIÓN: SOPORTE PARA VENTA POR UNIDAD Y POR EMPAQUE (CAJA/FARDO)
-- Fecha: 2026-09-04
-- =============================================================

ALTER TABLE productos
    ADD COLUMN unidades_por_empaque   DECIMAL(10,3) NOT NULL DEFAULT 1.000
        COMMENT 'Cantidad de unidades base contenidas en 1 empaque (caja/fardo). Ej: 10 = 1 caja = 10 unidades',
    ADD COLUMN precio_empaque         DECIMAL(10,2) NOT NULL DEFAULT 0.00
        COMMENT 'Precio de venta de 1 empaque completo (caja/fardo). Dejar en 0 si no se vende por empaque.',
    ADD COLUMN nombre_empaque         VARCHAR(50) NOT NULL DEFAULT 'Caja'
        COMMENT 'Nombre de la presentación: Caja, Bulto, Fardo, Paquete, Display o personalizado',
    ADD COLUMN codigo_barras_empaque  VARCHAR(50) NULL
        COMMENT 'Código de barras exclusivo del empaque (si el proveedor imprime uno diferente por caja).',
    ADD COLUMN tipo_venta             ENUM('solo_unidad','solo_empaque','ambos') NOT NULL DEFAULT 'solo_unidad'
        COMMENT 'Restringe cómo se ofrece el producto en el POS: solo unidad, solo empaque, o ambos.';

CREATE UNIQUE INDEX idx_productos_codigo_barras_empaque
    ON productos (codigo_barras_empaque);

ALTER TABLE detalle_ventas
    ADD COLUMN tipo_presentacion      ENUM('unidad','empaque') NOT NULL DEFAULT 'unidad'
        COMMENT 'Indica si se vendió por unidad individual o por empaque',
    ADD COLUMN nombre_presentacion    VARCHAR(50) NOT NULL DEFAULT 'Unidad'
        COMMENT 'Etiqueta de la presentación al momento de la venta (ej. Unidad, Caja, Bulto)',
    ADD COLUMN factor_unidades        DECIMAL(10,3) NOT NULL DEFAULT 1.000
        COMMENT 'Cantidad de unidades base que descuenta del inventario por cada ítem';

