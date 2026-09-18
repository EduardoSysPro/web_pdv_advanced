USE web_pdv_db;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rtn_identidad VARCHAR(30) NULL,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) NULL,
    direccion VARCHAR(255) NULL,
    limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_pendiente DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_clientes_nombre (nombre),
    UNIQUE KEY idx_clientes_rtn (rtn_identidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ventas MODIFY metodo_pago ENUM('efectivo','tarjeta','transferencia','credito') NOT NULL DEFAULT 'efectivo';
ALTER TABLE ventas ADD COLUMN cliente_id INT NULL AFTER metodo_pago;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE caja_movimientos MODIFY tipo ENUM('apertura','ingreso','egreso','cierre','ingreso_abono') NOT NULL;

CREATE TABLE IF NOT EXISTS pagos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    forma_pago ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    observacion VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pagos_cliente (cliente_id),
    CONSTRAINT fk_pagos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
