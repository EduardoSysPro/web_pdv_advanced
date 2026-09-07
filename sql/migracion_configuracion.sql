USE web_pdv_db;

CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion (clave, valor)
VALUES
    ('ticket_fuente', 'Courier New'),
    ('ticket_tamano_fuente', '11px'),
    ('ticket_mostrar_logo', '1'),
    ('ticket_mostrar_sar', '1')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
