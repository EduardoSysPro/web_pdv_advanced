-- =============================================================
-- SCRIPT DE ESTRUCTURA Y DATOS INICIALES
-- Base de datos: web_pdv_db
-- Sistema: PDV (Punto de Venta) de Abarrotes
-- =============================================================

-- =============================================================
-- INSTALACION COMPLETA PARA LA NUEVA BASE
-- Base de datos: web_pdv_advanced_db
-- Sistema: PDV (Punto de Venta) de Abarrotes
-- =============================================================
CREATE DATABASE IF NOT EXISTS web_pdv_advanced_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_pdv_advanced_db;
DROP TABLE IF EXISTS login_intentos;


CREATE TABLE cajas (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(80) NOT NULL,
    estado   TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY idx_cajas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cajas (nombre, estado) VALUES ('Caja 01', 1), ('Caja 02', 1);

-- =============================================================
-- TABLA: usuarios
-- Almacena los usuarios del sistema (administradores y cajeros)
-- =============================================================
CREATE TABLE usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del usuario',
    nombre      VARCHAR(100) NOT NULL COMMENT 'Nombre completo del usuario',
    usuario     VARCHAR(50)  NOT NULL COMMENT 'Nombre de usuario para iniciar sesión',
    password    VARCHAR(255) NOT NULL COMMENT 'Contraseña hasheada con bcrypt',
    rol         ENUM('admin', 'cajero', 'cajero_movil', 'vendedor') NOT NULL DEFAULT 'cajero' COMMENT 'Rol del usuario en el sistema',
    estado      TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo, 0 = Inactivo',
    caja_id     INT NULL COMMENT 'Caja predeterminada del usuario',
    sucursal    VARCHAR(120) NULL COMMENT 'Sucursal asignada',
    caja        VARCHAR(80) NULL COMMENT 'Caja asignada',
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación del registro',
    -- Índice único para evitar nombres de usuario duplicados
    UNIQUE KEY idx_usuarios_usuario (usuario),
    -- Índice para búsquedas rápidas por rol
    KEY idx_usuarios_rol (rol),
    -- Índice para búsquedas por estado
    KEY idx_usuarios_estado (estado),
    KEY idx_usuarios_caja (caja_id),
    CONSTRAINT fk_usuarios_caja FOREIGN KEY (caja_id) REFERENCES cajas (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema PDV';

CREATE TABLE configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración general del sistema';

-- =============================================================
-- TABLA: login_intentos
-- Registro de intentos de inicio de sesión para anti fuerza bruta
-- =============================================================
CREATE TABLE login_intentos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    usuario      VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Nombre de usuario del intento',
    ip           VARCHAR(45)  NOT NULL DEFAULT '' COMMENT 'Dirección IP del intento',
    intentado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del intento',
    resultado    VARCHAR(10)  NOT NULL DEFAULT 'fallo' COMMENT 'fallo o exito',
    KEY idx_login_intentos_busqueda (usuario, ip, intentado_en),
    KEY idx_login_intentos_limpieza (intentado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de intentos de inicio de sesión para anti fuerza bruta';

-- =============================================================
-- TABLA: categorias
-- Clasificación de los productos del inventario
-- =============================================================
CREATE TABLE categorias (
    id              INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único de la categoría',
    nombre          VARCHAR(100) NOT NULL COMMENT 'Nombre de la categoría',
    descripcion     VARCHAR(255) NULL COMMENT 'Descripción detallada de la categoría',
    -- Índice único para evitar categorías duplicadas
    UNIQUE KEY idx_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de productos';

-- =============================================================
-- TABLA: productos
-- Catálogo de productos disponibles para la venta
-- =============================================================
CREATE TABLE productos (
    id              INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del producto',
    codigo_barras   VARCHAR(50) NOT NULL COMMENT 'Código de barras del producto (EAN/UPC)',
    nombre          VARCHAR(150) NOT NULL COMMENT 'Nombre o descripción del producto',
    precio_costo    DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio de compra al proveedor',
    precio_venta    DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio de venta al público',
    stock           DECIMAL(10,3) NOT NULL DEFAULT 0.000 COMMENT 'Cantidad disponible en inventario',
    stock_minimo    DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Stock mínimo para alerta de reposición',
    unidad_medida   VARCHAR(20) NOT NULL DEFAULT 'unidad' COMMENT 'Unidad de medida del producto',
    permite_decimales TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el producto acepta cantidades fraccionarias',
    tipo_venta      ENUM('solo_unidad','solo_empaque','ambos') NOT NULL DEFAULT 'solo_unidad' COMMENT 'Modalidad de venta',
    nombre_empaque  VARCHAR(50) NOT NULL DEFAULT 'Caja' COMMENT 'Nombre presentación mayorista: Caja, Bulto, Fardo, Paquete, etc.',
    unidades_por_empaque DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Unidades contenidas en 1 empaque',
    precio_empaque  DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio de venta por empaque',
    tipo_impuesto   ENUM('exento','gravado_15','gravado_18','exonerado') NOT NULL DEFAULT 'gravado_15' COMMENT 'Régimen de ISV del producto',
    porcentaje_isv  DECIMAL(5,2) NOT NULL DEFAULT 15.00 COMMENT 'Porcentaje de ISV aplicado',
    imagen          VARCHAR(255) NULL COMMENT 'Ruta de la foto del producto (uploads/productos/...)',
    codigo_barras_empaque VARCHAR(50) NULL COMMENT 'Código de barras exclusivo del empaque',
    categoria_id    INT NULL COMMENT 'Llave foránea hacia la categoría del producto',
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de alta del producto',
    -- Índice único para el código de barras (búsqueda rápida en ventas)
    UNIQUE KEY idx_productos_codigo_barras (codigo_barras),
    UNIQUE KEY idx_productos_codigo_barras_empaque (codigo_barras_empaque),
    -- Índice para búsquedas por nombre
    KEY idx_productos_nombre (nombre),
    -- Índice para filtrar por categoría
    KEY idx_productos_categoria_id (categoria_id),
    -- Índice para identificar productos con stock bajo
    KEY idx_productos_stock (stock),
    -- Llave foránea hacia categorías
    -- Llave foránea hacia categorías
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de productos';

-- =============================================================
-- TABLA: clientes
-- Registro de clientes y control de límites de crédito
-- =============================================================
CREATE TABLE clientes (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes y crédito';

-- =============================================================
-- TABLA: proveedores
-- Maestro de proveedores (nombre, RTN, condición de contribuyente)
-- =============================================================
CREATE TABLE proveedores (
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
-- TABLA: compras
-- Encabezado de cada compra / ingreso de factura del proveedor
-- Desglose de ISV espejo de la tabla ventas.
-- =============================================================
CREATE TABLE compras (
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
-- TABLA: detalle_compras
-- Líneas de detalle: productos recibidos y gastos operativos
-- =============================================================
CREATE TABLE detalle_compras (
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
-- TABLA: pagos_proveedores
-- Abonos y pagos realizados a proveedores (cuentas por pagar)
-- =============================================================
CREATE TABLE pagos_proveedores (
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
-- TABLA: ventas
-- Encabezado de cada transacción de venta
-- =============================================================
CREATE TABLE ventas (
    id              INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único de la venta',
    folio           VARCHAR(20) NOT NULL COMMENT 'Número de folio o ticket de la venta',
    usuario_id      INT NOT NULL COMMENT 'Llave foránea hacia el usuario que realizó la venta',
    caja_id         INT NULL COMMENT 'Caja activa de la venta',
    total           DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Monto total de la venta',
    descuento_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total de descuentos otorgados en la venta',
    pagado_con      DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad de dinero entregada por el cliente',
    cambio          DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Cambio devuelto al cliente',
    metodo_pago     ENUM('efectivo', 'tarjeta', 'transferencia', 'credito') NOT NULL DEFAULT 'efectivo' COMMENT 'Método de pago utilizado',
    cliente_id      INT NULL COMMENT 'Cliente asociado a la venta a crédito',
    cliente_nombre  VARCHAR(150) NULL COMMENT 'Nombre del cliente eventual o factura',
    cliente_rtn     VARCHAR(30) NULL COMMENT 'RTN/identidad del cliente eventual',
    cliente_telefono VARCHAR(30) NULL COMMENT 'Teléfono del cliente eventual',
    cliente_direccion VARCHAR(255) NULL COMMENT 'Dirección del cliente eventual',
    tipo_comprobante ENUM('recibo', 'factura') NOT NULL DEFAULT 'recibo' COMMENT 'Tipo de comprobante de la venta',
    cai             VARCHAR(100) NULL COMMENT 'Número de CAI asociado al comprobante',
    correlativo_sar VARCHAR(50) NULL COMMENT 'Correlativo SAR para facturación',
    importe_exento       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exentos de ISV',
    importe_exonerado    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exonerados de ISV',
    importe_gravado_15   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 15% de ISV',
    isv_15               DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 15%',
    importe_gravado_18   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 18% de ISV',
    isv_18               DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 18%',
    rango_autorizado     VARCHAR(100) NULL COMMENT 'Rango CAI autorizado vigente al emitir la factura',
    fecha_limite_emision DATE NULL COMMENT 'Fecha límite de emisión del CAI vigente al emitir la factura',
    fecha_venta     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se realizó la venta',
    -- Índice único por folio
    UNIQUE KEY idx_ventas_folio (folio),
    -- Índice para búsquedas por fecha de venta (reportes)
    KEY idx_ventas_fecha_venta (fecha_venta),
    -- Índice para filtrar ventas por cajero/usuario
    KEY idx_ventas_usuario_id (usuario_id),
    KEY idx_ventas_caja_id (caja_id),
    KEY idx_ventas_cliente_id (cliente_id),
    -- Llaves foráneas
    CONSTRAINT fk_ventas_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_ventas_caja
        FOREIGN KEY (caja_id)
        REFERENCES cajas (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_ventas_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES clientes (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Encabezado de ventas';

-- =============================================================
-- TABLA: pagos_clientes
-- Abonos y pagos realizados por clientes
-- =============================================================
CREATE TABLE pagos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    forma_pago ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    observacion VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pagos_cliente (cliente_id),
    KEY idx_pagos_usuario (usuario_id),
    CONSTRAINT fk_pagos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abonos de clientes';

-- =============================================================
-- TABLA: detalle_ventas
-- Líneas de detalle de cada venta (productos vendidos)
-- =============================================================
CREATE TABLE detalle_ventas (
    id              INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del detalle',
    venta_id        INT NOT NULL COMMENT 'Llave foránea hacia la venta',
    producto_id     INT NOT NULL COMMENT 'Llave foránea hacia el producto vendido',
    cantidad        DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Cantidad de unidades vendidas',
    precio_unitario DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio unitario al momento de la venta',
    precio_lista    DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Precio unitario antes del descuento',
    descuento_unitario DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Descuento fijo aplicado a cada unidad',
    subtotal        DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal = cantidad * precio_unitario',
    tipo_presentacion ENUM('unidad','empaque') NOT NULL DEFAULT 'unidad' COMMENT 'Indica si se vendió por unidad o empaque',
    nombre_presentacion VARCHAR(50) NOT NULL DEFAULT 'Unidad' COMMENT 'Nombre de la presentación vendida',
    factor_unidades DECIMAL(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Factor de unidades descontadas del inventario',
    porcentaje_isv  DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de ISV aplicado a la línea',
    monto_isv       DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto de ISV de la línea',
    es_exento       TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exento de ISV',
    es_exonerado    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exonerado de ISV',
    -- Índice para consultar los detalles de una venta rápida
    KEY idx_detalle_ventas_venta_id (venta_id),
    -- Índice para consultar qué ventas incluyeron un producto
    KEY idx_detalle_ventas_producto_id (producto_id),
    -- Índice compuesto para reportes de ventas por producto
    KEY idx_detalle_ventas_venta_producto (venta_id, producto_id),
    -- Llave foránea hacia ventas
    CONSTRAINT fk_detalle_ventas_venta
        FOREIGN KEY (venta_id)
        REFERENCES ventas (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    -- Llave foránea hacia productos
    CONSTRAINT fk_detalle_ventas_producto
        FOREIGN KEY (producto_id)
        REFERENCES productos (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de productos por venta';

-- =============================================================
-- TABLA: cotizaciones
-- Cotizaciones creadas por el rol Vendedor, pendientes de facturar
-- =============================================================
CREATE TABLE cotizaciones (
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
    UNIQUE KEY idx_cotizaciones_folio (folio),
    KEY idx_cotizaciones_estado (estado),
    KEY idx_cotizaciones_vendedor (vendedor_id),
    KEY idx_cotizaciones_cliente (cliente_id),
    KEY idx_cotizaciones_venta (venta_id),
    KEY idx_cotizaciones_creada (creada_en),
    CONSTRAINT fk_cot_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cot_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cot_venta    FOREIGN KEY (venta_id)    REFERENCES ventas (id)   ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cotizaciones creadas por vendedores';

-- =============================================================
-- TABLA: detalle_cotizaciones
-- Líneas de productos por cotización
-- =============================================================
CREATE TABLE detalle_cotizaciones (
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

-- =============================================================
-- TABLA: caja_movimientos
-- Registro de movimientos de efectivo en caja
-- =============================================================
CREATE TABLE caja_movimientos (
    id              INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único del movimiento',
    usuario_id      INT NOT NULL COMMENT 'Llave foránea hacia el usuario que registró el movimiento',
    caja_id         INT NULL COMMENT 'Caja activa del movimiento',
    tipo            ENUM('apertura', 'ingreso', 'egreso', 'cierre', 'ingreso_abono') NOT NULL COMMENT 'Tipo de movimiento de caja',
    monto           DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Monto del movimiento',
    concepto        VARCHAR(255) NULL COMMENT 'Descripción o concepto del movimiento',
    fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del movimiento',
    -- Índice para búsquedas por tipo de movimiento
    KEY idx_caja_movimientos_tipo (tipo),
    -- Índice para reportes por fecha
    KEY idx_caja_movimientos_fecha (fecha),
    -- Índice para filtrar por usuario
    KEY idx_caja_movimientos_usuario_id (usuario_id),
    KEY idx_caja_movimientos_caja_id (caja_id),
    -- Llave foránea hacia usuarios
    CONSTRAINT fk_caja_movimientos_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_caja_movimientos_caja FOREIGN KEY (caja_id) REFERENCES cajas (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Movimientos de caja';

CREATE TABLE caja_cortes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    caja_id         INT NULL,
    fecha_apertura  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fondo_inicial   DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    fecha_cierre    DATETIME NULL,
    monto_declarado DECIMAL(10, 2) NULL,
    diferencia      DECIMAL(10, 2) NULL,
    estado          ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    KEY idx_caja_cortes_usuario (usuario_id),
    KEY idx_caja_cortes_caja (caja_id),
    KEY idx_caja_cortes_estado (estado),
    CONSTRAINT fk_caja_cortes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_caja_cortes_caja FOREIGN KEY (caja_id) REFERENCES cajas (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones de apertura y corte de caja';

CREATE TABLE inventario_movimientos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT NOT NULL,
    usuario_id      INT NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida') NOT NULL,
    cantidad        DECIMAL(10,3) NOT NULL COMMENT 'Cantidad del movimiento',
    motivo          VARCHAR(255) NOT NULL,
    fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_inventario_producto (producto_id),
    KEY idx_inventario_usuario (usuario_id),
    CONSTRAINT fk_inventario_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_inventario_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bitácora de ajustes de inventario';

-- =============================================================
-- DATOS DE PRUEBA
-- =============================================================

-- Inserción de categorías de ejemplo
INSERT INTO categorias (nombre, descripcion) VALUES
('Abarrotes',     'Productos de despensa básica'),
('Bebidas',       'Bebidas sin alcohol y refrescos'),
('Snacks',        'Botanas, dulces y frituras'),
('Limpieza',      'Productos de limpieza del hogar'),
('Cuidado Personal', 'Artículos de higiene y cuidado personal');

-- Configuración general del sistema para ticket, recibos y facturas fiscales
INSERT INTO configuracion (clave, valor) VALUES
('nombre_negocio', 'COMERCIAL EL SOL S. DE R. L.'),
('rtn', '08011995123456'),
('telefono', '+504 2444-1234'),
('email', 'ventas@comercialelsol.hn'),
('direccion', 'Barrio El Centro, 2da Calle, Tocoa, Colón'),
('mensaje_ticket', '¡Gracias por su compra!'),
('ancho_ticket', '80mm'),
('tipo_comprobante_default', 'recibo'),
('impuesto_porcentaje', '15'),
('moneda_simbolo', 'L'),
('logotipo_path', 'uploads/logo.png'),
('ticket_fuente', 'Arial'),
('ticket_tamano_fuente', '12px'),
('ticket_mostrar_logo', '0'),
('ticket_mostrar_sar', '1'),
('sar_activo', '1'),
('sar_cai', '3B8E9F-12A456-7890BC-DEF123-456789-A1'),
('sar_rango_inicial', '000-001-01-00000001'),
('sar_rango_final', '000-001-01-00020000'),
('sar_fecha_limite', '2026-12-31'),
('sar_correlativo_actual', '1'),
('sar_punto_venta', '001'),
('sar_establecimiento', '001'),
('sar_tipo_documento', '01'),
('cotizacion_dias_validez', '15'),
('cotizacion_mostrar_precios', '1'),
('cotizacion_mensaje', 'Cotización sujeta a confirmación de precio y disponibilidad en caja.');

-- Inserción de usuarios base del sistema
-- Password inicial: password  (hash bcrypt válido con cost = 10)
-- Nota: Puedes generar un nuevo hash con PHP: password_hash('tu_password', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, usuario, password, rol, estado, caja_id, sucursal, caja) VALUES
('Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, NULL, NULL),
('Cajero Principal', 'cajero', '$2y$10$uFaZvFrwaw3uV/ACRDgs2eRqP7KFg01yutDioFbbXQQ1r8u.L51Lu', 'cajero', 1, 1, 'Mi Negocio', ''),
('Cajero Móvil', 'movil', '$2y$10$uFaZvFrwaw3uV/ACRDgs2eRqP7KFg01yutDioFbbXQQ1r8u.L51Lu', 'cajero_movil', 1, 1, 'Mi Negocio', ''),
('Vendedor Principal', 'vendedor', '$2y$10$uFaZvFrwaw3uV/ACRDgs2eRqP7KFg01yutDioFbbXQQ1r8u.L51Lu', 'vendedor', 1, NULL, 'Mi Negocio', '');

-- Inserción de cliente de prueba para ventas a crédito
INSERT INTO clientes (rtn_identidad, nombre, telefono, direccion, limite_credito, saldo_pendiente) VALUES
('08011990123456', 'Cliente Ejemplo', '+504 9999-0000', 'Barrio El Centro, Tocoa', 5000.00, 0.00);

-- Inserción de 10 productos representativos de la canasta básica de Honduras
INSERT INTO productos (codigo_barras, nombre, precio_costo, precio_venta, stock, stock_minimo, categoria_id, unidad_medida, permite_decimales, tipo_impuesto, porcentaje_isv) VALUES
('7501000123451', 'Arroz Blanco 1kg',                 18.50, 25.00, 120.000, 20.000, 1, 'kg', 0, 'exento',     0.00),
('7501000123452', 'Frijoles Negros 1kg',              22.00, 30.50,  90.000, 15.000, 1, 'kg', 0, 'exento',     0.00),
('7501000123453', 'Aceite Vegetal 1L',                28.00, 38.00,  60.000, 10.000, 1, 'unidad', 0, 'gravado_15', 15.00),
('7501000123454', 'Leche Entera 1L',                  21.00, 27.00,  80.000, 12.000, 2, 'unidad', 0, 'exento',     0.00),
('7501000123455', 'Azúcar 1kg',                       16.00, 22.00, 110.000, 18.000, 1, 'kg', 0, 'exento',     0.00),
('7501000123456', 'Sal de Cocina 1kg',                12.00, 18.00,  70.000, 10.000, 1, 'kg', 0, 'exento',     0.00),
('7501000123457', 'Fideos Spaghetti 200g',            8.00, 12.00,  90.000, 15.000, 1, 'unidad', 0, 'gravado_18', 18.00),
('7501000123458', 'Sardinas en Salsa 425g',          18.00, 26.00,  60.000, 10.000, 1, 'unidad', 0, 'gravado_15', 15.00),
('7501000123459', 'Papel Higiénico 4 rollos',         26.00, 36.00,  40.000,  8.000, 4, 'unidad', 0, 'exonerado',  0.00),
('7501000123460', 'Jabón de Baño 120g',              14.00, 20.00,  90.000, 15.000, 5, 'unidad', 0, 'gravado_15', 15.00);

