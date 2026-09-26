-- =============================================================
-- PRECIO MAYORISTA (Precio 2) por producto.
-- -------------------------------------------------------------
-- APLICACIÓN: una sola vez.
--   mysql -u root web_pdv_advanced_db < sql/productos_precio_mayorista.sql
-- NOTA: la aplicación también auto-aplica este cambio al entrar a
-- productos o cotizaciones (Producto::asegurarPrecioMayorista).
-- Si la columna ya existe, el ALTER fallará con error 1060: ignóralo.
-- Regla: 0 = sin precio mayorista (se usa el normal). Si es mayor a 0
-- debe ser menor o igual al precio de venta normal.
-- =============================================================

-- Precio 2 para clientes mayoristas (solo venta por unidad).
ALTER TABLE productos
    ADD COLUMN precio_mayorista DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio 2 para clientes mayoristas (0 = sin precio mayorista)';
