USE web_pdv_db;

CREATE TABLE IF NOT EXISTS cajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY idx_cajas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO cajas (nombre, estado) VALUES ('Caja 01', 1), ('Caja 02', 1);

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS caja_id INT NULL AFTER estado;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS caja_id INT NULL AFTER usuario_id;
ALTER TABLE caja_movimientos ADD COLUMN IF NOT EXISTS caja_id INT NULL AFTER usuario_id;
ALTER TABLE caja_cortes ADD COLUMN IF NOT EXISTS caja_id INT NULL AFTER usuario_id;

UPDATE usuarios SET caja_id = CASE WHEN MOD(id, 2) = 1 THEN 1 ELSE 2 END WHERE caja_id IS NULL;