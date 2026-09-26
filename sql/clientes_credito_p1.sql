-- =============================================================
-- MEJORAS CLIENTES P1 (puntos 1-5): crédito, mora, FIFO, anulación,
-- autorización de supervisor y cartera CxC.
-- -------------------------------------------------------------
-- APLICACIÓN: una sola vez, ANTES de usar el módulo actualizado.
--   mysql -u root web_pdv_advanced_db < sql/clientes_credito_p1.sql
-- NOTA: la aplicación también auto-aplica estos cambios al entrar al
-- módulo de clientes o al vender (Cliente::asegurarEsquema), por lo que
-- este archivo es respaldo/documentación y para instalaciones frescas.
-- Las sentencias CREATE son IF NOT EXISTS (re-ejecutables); los ALTER
-- fallarán con error 1060 si la columna ya existe: ignóralo.
-- OJO: este archivo NO trae USE; se aplica sobre la BD que selecciones
-- en la línea de comandos.
-- =============================================================

-- P1: días de crédito por cliente (0 = contado estricto, N = vence a N días).
ALTER TABLE clientes
    ADD COLUMN dias_credito INT NOT NULL DEFAULT 30 COMMENT 'Días de crédito otorgados (0 = contado)';

-- P1: vencimiento por factura (NULL en ventas viejas = fecha_venta + dias_credito).
ALTER TABLE ventas
    ADD COLUMN fecha_vencimiento DATE NULL COMMENT 'Vencimiento de la factura a crédito';

-- P4: marcas de autorización al exceder el límite.
ALTER TABLE ventas
    ADD COLUMN excede_limite TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = se vendió excediendo el límite con autorización',
    ADD COLUMN autorizado_por INT NULL COMMENT 'Usuario supervisor que autorizó exceder el límite';

-- P2: aplicación de abonos a facturas específicas (FIFO automático).
CREATE TABLE IF NOT EXISTS abono_aplicaciones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    pago_id    INT NOT NULL COMMENT 'Abono en pagos_clientes',
    venta_id   INT NOT NULL COMMENT 'Factura saldada parcial o totalmente',
    monto      DECIMAL(10,2) NOT NULL COMMENT 'Monto del abono aplicado a esta factura',
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_abono_aplic_pago_venta (pago_id, venta_id),
    KEY idx_abono_aplic_venta (venta_id),
    CONSTRAINT fk_abono_aplic_pago FOREIGN KEY (pago_id) REFERENCES pagos_clientes (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_abono_aplic_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abono aplicado a facturas (FIFO)';

-- P3: anulación de abonos con motivo y rastro.
ALTER TABLE pagos_clientes
    ADD COLUMN estado ENUM('activo','anulado') NOT NULL DEFAULT 'activo' COMMENT 'activo o anulado (reversado)',
    ADD COLUMN motivo_anulacion VARCHAR(255) NULL COMMENT 'Motivo de la anulación',
    ADD COLUMN anulado_por INT NULL COMMENT 'Usuario que anuló el abono',
    ADD COLUMN anulado_en DATETIME NULL COMMENT 'Fecha y hora de la anulación';

-- P3/P4: auditoría de eventos de crédito.
CREATE TABLE IF NOT EXISTS credito_auditoria (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT NULL COMMENT 'Cliente involucrado (si aplica)',
    usuario_id      INT NOT NULL COMMENT 'Usuario que originó el evento',
    accion          VARCHAR(40) NOT NULL COMMENT 'venta_excede_limite | abono_anulado | limite_modificado',
    referencia_tipo VARCHAR(20) NULL COMMENT 'venta | pago | cliente',
    referencia_id   INT NULL COMMENT 'Id de la venta/pago/cliente referenciado',
    detalle         VARCHAR(255) NULL COMMENT 'Descripción legible del evento',
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_credito_aud_cliente (cliente_id),
    KEY idx_credito_aud_usuario (usuario_id),
    KEY idx_credito_aud_accion (accion),
    CONSTRAINT fk_credito_aud_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_credito_aud_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bitácora de crédito y cobranza';
