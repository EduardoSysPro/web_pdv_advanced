-- =============================================================
-- MIGRACIÓN: tabla central de auditoría / log del sistema
-- Ejecución: mysql -u root web_pdv_db < sql/migracion_auditoria_logs.sql
-- Idempotente: usa CREATE TABLE IF NOT EXISTS
-- =============================================================

CREATE TABLE IF NOT EXISTS auditoria_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador del evento',
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del movimiento',
    usuario_id    INT NULL COMMENT 'Usuario que realizó la operación (NULL = sistema)',
    usuario_nombre VARCHAR(100) NULL COMMENT 'Snapshot del nombre al momento del evento',
    rol           VARCHAR(20) NULL COMMENT 'Rol al momento del evento',
    modulo        VARCHAR(30) NOT NULL COMMENT 'ventas, compras, clientes, productos, caja, etc.',
    accion        VARCHAR(30) NOT NULL COMMENT 'crear, actualizar, eliminar, anular, login, abono...',
    entidad_tipo  VARCHAR(30) NULL COMMENT 'venta, cliente, factura, producto...',
    entidad_id    VARCHAR(50) NULL COMMENT 'Folio o id afectado',
    descripcion   VARCHAR(255) NULL COMMENT 'Resumen legible del movimiento',
    monto         DECIMAL(10,2) NULL COMMENT 'Monto involucrado si aplica',
    ip            VARCHAR(45) NULL COMMENT 'IP del cliente',
    datos         JSON NULL COMMENT 'Detalle extra (antes/después, método pago, etc.)',
    KEY idx_aud_fecha (creado_en),
    KEY idx_aud_usuario (usuario_id),
    KEY idx_aud_modulo_accion (modulo, accion),
    KEY idx_aud_entidad (entidad_tipo, entidad_id),
    CONSTRAINT fk_aud_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bitácora central de movimientos del sistema';
