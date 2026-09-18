USE web_pdv_db;

CREATE TABLE IF NOT EXISTS login_intentos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario      VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nombre de usuario del intento',
    ip           VARCHAR(45)  NOT NULL DEFAULT '' COMMENT 'Dirección IP del intento',
    intentado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del intento',
    resultado    VARCHAR(10)  NOT NULL DEFAULT 'fallo' COMMENT 'fallo o exito',
    KEY idx_login_intentos_busqueda (usuario, ip, intentado_en),
    KEY idx_login_intentos_limpieza (intentado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de intentos de inicio de sesión para anti fuerza bruta';