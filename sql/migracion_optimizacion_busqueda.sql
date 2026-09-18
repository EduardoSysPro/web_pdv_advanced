-- ============================================================
-- Optimización de búsqueda de productos (ventas / cotizaciones)
-- ============================================================
-- Agrega un índice FULLTEXT sobre productos.nombre para que la
-- búsqueda AJAX use MATCH...AGAINST (IN BOOLEAN MODE) en lugar de
-- escanear toda la tabla con LIKE '%termino%' cuando el catálogo
-- es grande.
--
-- Reemplázalo tú mismo, o ejecuta en MySQL/MariaDB:
--   mysql -u root web_pdv_advanced_db < migracion_optimizacion_busqueda.sql
--
-- Nota: si el índice ya existe, este comando falla; omítelo
-- (los índices BTREE de codigo_barras y nombre ya estaban creados).

CREATE FULLTEXT INDEX ft_productos_nombre ON productos(nombre);