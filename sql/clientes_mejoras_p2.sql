-- =============================================================
-- MEJORAS CLIENTES P2: categoría mayorista/minorista.
-- -------------------------------------------------------------
-- APLICACIÓN: una sola vez.
--   mysql -u root web_pdv_advanced_db < sql/clientes_mejoras_p2.sql
-- NOTA: la aplicación también auto-aplica este cambio al entrar al
-- módulo de clientes (Cliente::asegurarEsquema). Si la columna ya
-- existe, el ALTER fallará con error 1060: ignóralo.
-- (No se incluye borrado de clientes: por decisión del negocio los
-- clientes no se eliminan.)
-- =============================================================

-- Categoría del cliente para precios y reportes diferenciados.
ALTER TABLE clientes
    ADD COLUMN tipo ENUM('minorista','mayorista') NOT NULL DEFAULT 'minorista' COMMENT 'Categoría: minorista (detalle) o mayorista';
