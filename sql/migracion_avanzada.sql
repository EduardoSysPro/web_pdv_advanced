-- =============================================================
-- MIGRACIÓN AVANZADA - web_pdv_advanced
-- Módulo de cotizaciones (ventas), rol Vendedor y fotos de producto
-- Base de datos: web_pdv_advanced_db
-- =============================================================

CREATE DATABASE IF NOT EXISTS web_pdv_advanced_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_pdv_advanced_db;

-- -------------------------------------------------------------
-- 1) Nuevo rol: Vendedor (genera cotizaciones)
-- -------------------------------------------------------------
ALTER TABLE usuarios
    MODIFY rol ENUM('admin', 'cajero', 'cajero_movil', 'vendedor') NOT NULL DEFAULT 'cajero'
    COMMENT 'Rol del usuario en el sistema';

-- -------------------------------------------------------------
-- 2) Foto opcional del producto (visible en POS y cotizaciones)
-- -------------------------------------------------------------
ALTER TABLE productos
    ADD COLUMN imagen VARCHAR(255) NULL COMMENT 'Ruta de la foto del producto (uploads/productos/...)'
    AFTER porcentaje_isv;

-- -------------------------------------------------------------
-- 3) Tabla: cotizaciones
-- Encabezado de cada cotización creada por un vendedor.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cotizaciones (
    id                    INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único de la cotización',
    folio                 VARCHAR(20) NOT NULL COMMENT 'Número de folio interno (COT-00000001)',
    vendedor_id           INT NOT NULL COMMENT 'Usuario vendedor que creó la cotización',
    cliente_id            INT NULL COMMENT 'Cliente del catálogo (opcional)',
    cliente_nombre        VARCHAR(150) NULL COMMENT 'Nombre / razón social del cliente',
    cliente_rtn           VARCHAR(30) NULL COMMENT 'RTN o identidad del cliente',
    cliente_telefono      VARCHAR(30) NULL COMMENT 'Teléfono del cliente',
    cliente_direccion     VARCHAR(255) NULL COMMENT 'Dirección del cliente',
    estado                ENUM('pendiente', 'facturada', 'cancelada') NOT NULL DEFAULT 'pendiente'
                          COMMENT 'pendiente = lista para facturar, facturada = ya convertida en venta',
    venta_id              INT NULL COMMENT 'Venta generada cuando la cotización fue facturada en caja',
    importe_exento        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_exonerado     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_gravado_15    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    isv_15                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe_gravado_18    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    isv_18                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal              DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de líneas antes de descuento',
    descuento_total       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de descuentos otorgados',
    total                 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto total de la cotización',
    observaciones         VARCHAR(255) NULL COMMENT 'Notas u observaciones del vendedor',
    fecha_validez         DATE NULL COMMENT 'Fecha hasta la que la cotización tiene validez',
    creada_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación',
    -- Índices
    UNIQUE KEY idx_cotizaciones_folio (folio),
    KEY idx_cotizaciones_estado (estado),
    KEY idx_cotizaciones_vendedor (vendedor_id),
    KEY idx_cotizaciones_cliente (cliente_id),
    KEY idx_cotizaciones_venta (venta_id),
    KEY idx_cotizaciones_creada (creada_en),
    -- Llaves foráneas
    CONSTRAINT fk_cot_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cot_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cot_venta    FOREIGN KEY (venta_id)    REFERENCES ventas (id)   ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cotizaciones creadas por vendedores';

-- -------------------------------------------------------------
-- 4) Tabla: detalle_cotizaciones
-- Líneas de detalle de cada cotización (productos a vender)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detalle_cotizaciones (
    id                  INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del detalle',
    cotizacion_id       INT NOT NULL COMMENT 'Llave foránea hacia la cotización',
    producto_id         INT NULL COMMENT 'Producto cotizado',
    nombre_producto     VARCHAR(150) NULL COMMENT 'Nombre del producto al momento de cotizar',
    cantidad            DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Cantidad cotizada',
    precio_lista        DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio antes del descuento',
    precio_unitario     DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Precio final por unidad',
    descuento_unitario  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento fijo por unidad',
    subtotal            DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal = cantidad * precio_unitario',
    tipo_presentacion   ENUM('unidad', 'empaque') NOT NULL DEFAULT 'unidad' COMMENT 'Presentación cotizada',
    nombre_presentacion VARCHAR(50) NOT NULL DEFAULT 'Unidad' COMMENT 'Nombre de la presentación',
    factor_unidades     DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Factor de unidades del empaque',
    porcentaje_isv      DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de ISV de la línea',
    monto_isv           DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto de ISV de la línea',
    es_exento           TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = exento de ISV',
    es_exonerado        TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = exonerado de ISV',
    KEY idx_detalle_cotizaciones_cotizacion (cotizacion_id),
    KEY idx_detalle_cotizaciones_producto (producto_id),
    CONSTRAINT fk_detalle_cot_cotizacion FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_cot_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Líneas de productos por cotización';

-- -------------------------------------------------------------
-- 5) Configuración por defecto del módulo de cotizaciones
-- -------------------------------------------------------------
INSERT INTO configuracion (clave, valor) VALUES
('cotizacion_dias_validez', '15'),
('cotizacion_mostrar_precios', '1'),
('cotizacion_mensaje', 'Cotización sujeta a confirmación de precio y disponibilidad en caja.')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- -------------------------------------------------------------
-- 6) Usuario de demostración con rol Vendedor
-- Password: vendedor
-- -------------------------------------------------------------
INSERT INTO usuarios (nombre, usuario, password, rol, estado, caja_id, sucursal, caja) VALUES
('Vendedor Principal', 'vendedor', '$2y$10$uFaZvFrwaw3uV/ACRDgs2eRqP7KFg01yutDioFbbXQQ1r8u.L51Lu', 'vendedor', 1, NULL, 'Mi Negocio', '')
ON DUPLICATE KEY UPDATE rol = 'vendedor';