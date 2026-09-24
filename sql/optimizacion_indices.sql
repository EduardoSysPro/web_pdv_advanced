-- =============================================================
-- OPTIMIZACIÓN PARA CATÁLOGOS GRANDES (+8,000 productos)
-- -------------------------------------------------------------
-- Índice compuesto (categoria_id, nombre): acelera el listado de
-- productos filtrados por categoría y ordenados alfabéticamente.
-- Aplicar UNA sola vez sobre instalaciones existentes (las nuevas
-- ya lo incluyen en su CREATE TABLE).
-- =============================================================

USE web_pdv_advanced_db;

ALTER TABLE productos
    ADD INDEX idx_productos_categoria_nombre (categoria_id, nombre);