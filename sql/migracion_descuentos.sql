-- Descuentos fijos por unidad y precio de lista de cada línea de venta.
-- Aplicar una sola vez sobre bases existentes.

ALTER TABLE ventas
    ADD COLUMN descuento_total DECIMAL(10,2) NOT NULL DEFAULT 0.00
        COMMENT 'Total de descuentos otorgados en la venta' AFTER total;

ALTER TABLE detalle_ventas
    ADD COLUMN precio_lista DECIMAL(10,2) NOT NULL DEFAULT 0.00
        COMMENT 'Precio unitario antes del descuento' AFTER precio_unitario,
    ADD COLUMN descuento_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00
        COMMENT 'Descuento fijo aplicado a cada unidad' AFTER precio_lista;

UPDATE detalle_ventas
SET precio_lista = precio_unitario
WHERE precio_lista = 0;