-- =============================================================
-- P2-12: secuencias para folios sin race condition
-- -------------------------------------------------------------
-- Reemplaza los COUNT(*) + 1 (ventas REC, cotizaciones COT,
-- compras COMP) por correlativos bloqueados con FOR UPDATE.
-- La semilla parte del máximo existente para no colisionar.
-- Aplicar UNA sola vez (CREATE es IF NOT EXISTS; los INSERT
-- con ON DUPLICATE KEY son idempotentes).
-- =============================================================

USE web_pdv_advanced_db;

CREATE TABLE IF NOT EXISTS secuencias (
    nombre VARCHAR(50) NOT NULL PRIMARY KEY,
    valor INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO secuencias (nombre, valor)
    SELECT 'ventas_recibo', COALESCE(MAX(CAST(SUBSTRING(folio, 5) AS UNSIGNED)), 0)
    FROM ventas WHERE folio LIKE 'REC-%'
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO secuencias (nombre, valor)
    SELECT 'cotizaciones', COALESCE(MAX(CAST(SUBSTRING(folio, 5) AS UNSIGNED)), 0)
    FROM cotizaciones WHERE folio LIKE 'COT-%'
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO secuencias (nombre, valor)
    SELECT 'compras', COALESCE(MAX(CAST(SUBSTRING(folio, 6) AS UNSIGNED)), 0)
    FROM compras WHERE folio LIKE 'COMP-%'
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
