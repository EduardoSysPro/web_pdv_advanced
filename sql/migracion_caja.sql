USE web_pdv_db;

ALTER TABLE ventas
    ADD COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL DEFAULT 'efectivo'
    AFTER cambio;

CREATE TABLE IF NOT EXISTS caja_cortes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    fecha_apertura  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fondo_inicial   DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    fecha_cierre    DATETIME NULL,
    monto_declarado DECIMAL(10, 2) NULL,
    diferencia      DECIMAL(10, 2) NULL,
    estado          ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    KEY idx_caja_cortes_usuario (usuario_id),
    KEY idx_caja_cortes_estado (estado),
    CONSTRAINT fk_caja_cortes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
