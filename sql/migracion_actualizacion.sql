-- =============================================================
-- MIGRACIÓN DE ACTUALIZACIÓN
-- Base de datos: web_pdv_db (existente / producción)
-- Objetivo: añadir las nuevas funcionalidades del esquema.sql
-- SIN eliminar ni modificar la información existente del cliente.
-- =============================================================
-- IMPORTANTE: Ejecutar únicamente sobre la base de datos actual.
-- Hacer copia de seguridad antes de ejecutar este script.
-- No elimina tablas, no borra filas, solo añade estructura nueva.
-- =============================================================

USE `web_pdv_db`;

-- =============================================================
-- 1) TABLA: login_intentos (nueva)
-- Registro de intentos de inicio de sesión para anti fuerza bruta
-- =============================================================
CREATE TABLE IF NOT EXISTS login_intentos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario      VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nombre de usuario del intento',
    ip           VARCHAR(45)  NOT NULL DEFAULT '' COMMENT 'Dirección IP del intento',
    intentado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del intento',
    resultado    VARCHAR(10)  NOT NULL DEFAULT 'fallo' COMMENT 'fallo o exito',
    KEY idx_login_intentos_busqueda (usuario, ip, intentado_en),
    KEY idx_login_intentos_limpieza (intentado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de intentos de inicio de sesión para anti fuerza bruta';

-- =============================================================
-- 2) TABLA: proveedores (nueva)
-- Maestro de proveedores (nombre, RTN, condición de contribuyente)
-- =============================================================
CREATE TABLE IF NOT EXISTS proveedores (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL COMMENT 'Razón social / nombre del proveedor',
    rtn         VARCHAR(30) NOT NULL COMMENT 'RTN del proveedor (único)',
    tipo        ENUM('contribuyente','no_contribuyente') NOT NULL DEFAULT 'contribuyente'
                COMMENT 'Contribuyente = con capacidad de emitir crédito fiscal con factura CAI',
    telefono    VARCHAR(30) NULL,
    direccion   VARCHAR(255) NULL,
    contacto    VARCHAR(100) NULL,
    correo      VARCHAR(100) NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_proveedores_rtn (rtn),
    KEY idx_proveedores_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Proveedores';

-- =============================================================
-- 3) TABLA: compras (nueva)
-- Encabezado de cada compra / ingreso de factura del proveedor
-- =============================================================
CREATE TABLE IF NOT EXISTS compras (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    folio                 VARCHAR(20) NOT NULL COMMENT 'Folio interno del sistema (COMP-000001)',
    proveedor_id          INT NULL COMMENT 'Proveedor del catálogo',
    proveedor_nombre      VARCHAR(150) NOT NULL COMMENT 'Nombre del proveedor al momento de la compra',
    proveedor_rtn         VARCHAR(30) NULL,
    proveedor_tipo        ENUM('contribuyente','no_contribuyente') NOT NULL DEFAULT 'contribuyente',
    usuario_id            INT NOT NULL COMMENT 'Usuario que registró la compra',
    sucursal              VARCHAR(100) NULL COMMENT 'Sucursal desde donde se registró',
    tipo_documento        ENUM('factura_cai','recibo','nota_credito','nota_debito') NOT NULL,
    numero_factura        VARCHAR(50) NOT NULL COMMENT 'Número de factura/recibo del proveedor',
    cai                   VARCHAR(100) NULL,
    rango_autorizado      VARCHAR(100) NULL,
    fecha_limite_emision  DATE NULL,
    fecha_emision         DATE NOT NULL,
    condicion_pago        ENUM('contado','credito') NOT NULL DEFAULT 'contado',
    dias_credito          INT NOT NULL DEFAULT 0,
    fecha_vencimiento     DATE NULL COMMENT 'Fecha límite de pago (emisión + días de crédito)',
    documento_referencia_id INT NULL COMMENT 'Compra original que referencia la NC/ND',
    estado                ENUM('recibida','anulada') NOT NULL DEFAULT 'recibida',
    importe_exento        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_exonerado     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_gravado_15    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    isv_15                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_gravado_18    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    isv_18                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal              DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de líneas antes de descuento',
    descuento_total       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    flete                 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo de transporte prorrateable al costo del producto',
    total                 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_pendiente       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Cuentas por pagar (0 si es de contado o ya saldada)',
    isv_acreditable       TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = factura CAI a contribuyente: ISV acreditable (crédito fiscal)',
    observaciones         VARCHAR(255) NULL,
    fecha_registro        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_compras_folio (folio),
    KEY idx_compras_proveedor (proveedor_id),
    KEY idx_compras_usuario (usuario_id),
    KEY idx_compras_fecha (fecha_emision),
    KEY idx_compras_estado (estado),
    CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compras_referencia FOREIGN KEY (documento_referencia_id) REFERENCES compras (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Encabezado de compras';

-- =============================================================
-- 4) TABLA: detalle_compras (nueva)
-- Líneas de detalle: productos recibidos y gastos operativos
-- =============================================================
CREATE TABLE IF NOT EXISTS detalle_compras (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    compra_id          INT NOT NULL,
    tipo_linea         ENUM('producto','gasto_operativo') NOT NULL DEFAULT 'producto',
    producto_id        INT NULL COMMENT 'NULL cuando es un gasto operativo',
    nombre             VARCHAR(150) NULL COMMENT 'Nombre del producto o concepto del gasto',
    cantidad           DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    costo_unitario     DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo unitario acordado con el proveedor',
    costo_ingreso      DECIMAL(10,2) NULL COMMENT 'Costo unitario usado para inventario (incluye prorrateo de flete/gastos)',
    tipo_presentacion  ENUM('unidad','empaque') NOT NULL DEFAULT 'unidad',
    nombre_presentacion VARCHAR(50) NOT NULL DEFAULT 'Unidad',
    factor_unidades    DECIMAL(10,3) NOT NULL DEFAULT 1.000,
    tipo_impuesto      ENUM('exento','gravado_15','gravado_18','exonerado') NOT NULL DEFAULT 'gravado_15',
    porcentaje_isv     DECIMAL(5,2) NOT NULL DEFAULT 15.00,
    monto_isv          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    KEY idx_detalle_compras_compra (compra_id),
    KEY idx_detalle_compras_producto (producto_id),
    CONSTRAINT fk_detalle_compras_compra FOREIGN KEY (compra_id) REFERENCES compras (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_compras_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de productos y gastos por compra';

-- =============================================================
-- 5) TABLA: pagos_proveedores (nueva)
-- Abonos y pagos realizados a proveedores (cuentas por pagar)
-- =============================================================
CREATE TABLE IF NOT EXISTS pagos_proveedores (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    compra_id    INT NOT NULL,
    proveedor_id INT NULL,
    usuario_id   INT NOT NULL,
    monto        DECIMAL(10,2) NOT NULL,
    forma_pago   ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    observacion  VARCHAR(255) NULL,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pagos_proveedores_compra (compra_id),
    KEY idx_pagos_proveedores_proveedor (proveedor_id),
    KEY idx_pagos_proveedores_usuario (usuario_id),
    CONSTRAINT fk_pagos_prov_compra FOREIGN KEY (compra_id) REFERENCES compras (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_prov_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_prov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagos a proveedores';

-- =============================================================
-- 6) TABLA: productos (agregar columnas de ISV, sin borrar datos)
-- =============================================================
ALTER TABLE `productos`
  ADD COLUMN `tipo_impuesto` ENUM('exento','gravado_15','gravado_18','exonerado') NOT NULL DEFAULT 'gravado_15' COMMENT 'Régimen de ISV del producto' AFTER `precio_empaque`,
  ADD COLUMN `porcentaje_isv` DECIMAL(5,2) NOT NULL DEFAULT 15.00 COMMENT 'Porcentaje de ISV aplicado' AFTER `tipo_impuesto`;

-- =============================================================
-- 7) TABLA: ventas (agregar columnas de descuentos e ISV)
-- =============================================================
ALTER TABLE `ventas`
  ADD COLUMN `descuento_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de descuentos otorgados en la venta' AFTER `total`,
  ADD COLUMN `importe_exento` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exentos de ISV' AFTER `correlativo_sar`,
  ADD COLUMN `importe_exonerado` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exonerados de ISV' AFTER `importe_exento`,
  ADD COLUMN `importe_gravado_15` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 15% de ISV' AFTER `importe_exonerado`,
  ADD COLUMN `isv_15` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 15%' AFTER `importe_gravado_15`,
  ADD COLUMN `importe_gravado_18` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 18% de ISV' AFTER `isv_15`,
  ADD COLUMN `isv_18` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 18%' AFTER `importe_gravado_18`,
  ADD COLUMN `rango_autorizado` VARCHAR(100) NULL COMMENT 'Rango CAI autorizado vigente al emitir la factura' AFTER `isv_18`,
  ADD COLUMN `fecha_limite_emision` DATE NULL COMMENT 'Fecha límite de emisión del CAI vigente al emitir la factura' AFTER `rango_autorizado`;

-- =============================================================
-- 8) TABLA: detalle_ventas (agregar columnas de descuento e ISV)
-- =============================================================
ALTER TABLE `detalle_ventas`
  ADD COLUMN `precio_lista` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio unitario antes del descuento' AFTER `precio_unitario`,
  ADD COLUMN `descuento_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento fijo aplicado a cada unidad' AFTER `precio_lista`,
  ADD COLUMN `porcentaje_isv` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de ISV aplicado a la línea' AFTER `factor_unidades`,
  ADD COLUMN `monto_isv` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto de ISV de la línea' AFTER `porcentaje_isv`,
  ADD COLUMN `es_exento` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exento de ISV' AFTER `monto_isv`,
  ADD COLUMN `es_exonerado` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exonerado de ISV' AFTER `es_exento`;

-- =============================================================
-- 9) TABLA: configuracion (agregar claves nuevas solo si no existen)
-- No sobrescribe los valores actuales del cliente.
-- =============================================================
INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
('sar_punto_venta', '001'),
('sar_establecimiento', '001'),
('sar_tipo_documento', '01');

-- =============================================================
-- FIN DE LA MIGRACIÓN
-- =============================================================