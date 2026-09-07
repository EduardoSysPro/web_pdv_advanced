-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 06-09-2026 a las 00:58:48
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `web_pdv_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

DROP TABLE IF EXISTS `cajas`;
CREATE TABLE IF NOT EXISTS `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cajas_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cajas`
--

INSERT INTO `cajas` (`id`, `nombre`, `estado`) VALUES
(1, 'Caja 01', 1),
(2, 'Caja 02', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_cortes`
--

DROP TABLE IF EXISTS `caja_cortes`;
CREATE TABLE IF NOT EXISTS `caja_cortes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `caja_id` int DEFAULT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fondo_inicial` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_declarado` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  PRIMARY KEY (`id`),
  KEY `idx_caja_cortes_usuario` (`usuario_id`),
  KEY `idx_caja_cortes_caja` (`caja_id`),
  KEY `idx_caja_cortes_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sesiones de apertura y corte de caja';

--
-- Volcado de datos para la tabla `caja_cortes`
--

INSERT INTO `caja_cortes` (`id`, `usuario_id`, `caja_id`, `fecha_apertura`, `fondo_inicial`, `fecha_cierre`, `monto_declarado`, `diferencia`, `estado`) VALUES
(1, 2, 1, '2026-08-31 21:45:55', 2000.00, '2026-08-31 21:45:59', 2000.00, 0.00, 'cerrada'),
(2, 2, 1, '2026-08-31 21:48:02', 1500.00, '2026-08-31 21:51:28', 2378.00, 0.00, 'cerrada'),
(3, 1, 1, '2026-09-05 17:32:23', 2000.00, '2026-09-05 17:32:46', 2000.00, 0.00, 'cerrada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_movimientos`
--

DROP TABLE IF EXISTS `caja_movimientos`;
CREATE TABLE IF NOT EXISTS `caja_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del movimiento',
  `usuario_id` int NOT NULL COMMENT 'Llave foránea hacia el usuario que registró el movimiento',
  `caja_id` int DEFAULT NULL COMMENT 'Caja activa del movimiento',
  `tipo` enum('apertura','ingreso','egreso','cierre','ingreso_abono') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de movimiento de caja',
  `monto` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Monto del movimiento',
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descripción o concepto del movimiento',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del movimiento',
  PRIMARY KEY (`id`),
  KEY `idx_caja_movimientos_tipo` (`tipo`),
  KEY `idx_caja_movimientos_fecha` (`fecha`),
  KEY `idx_caja_movimientos_usuario_id` (`usuario_id`),
  KEY `idx_caja_movimientos_caja_id` (`caja_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Movimientos de caja';

--
-- Volcado de datos para la tabla `caja_movimientos`
--

INSERT INTO `caja_movimientos` (`id`, `usuario_id`, `caja_id`, `tipo`, `monto`, `concepto`, `fecha`) VALUES
(1, 1, 1, 'ingreso', 25.00, 'Venta registrada - Folio V-20260831-00001 - Método: Efectivo', '2026-08-31 21:38:36'),
(2, 1, 1, 'ingreso', 415.00, 'Venta registrada - Folio V-20260831-00002 - Método: Efectivo', '2026-08-31 21:43:09'),
(3, 2, 1, 'apertura', 2000.00, 'Apertura de caja', '2026-08-31 21:45:55'),
(4, 2, 1, 'cierre', 2000.00, 'Cierre de caja', '2026-08-31 21:45:59'),
(5, 2, 1, 'apertura', 1500.00, 'Apertura de caja', '2026-08-31 21:48:02'),
(6, 2, 1, 'ingreso', 760.00, 'Venta registrada - Folio V-20260831-00004 - Método: Efectivo', '2026-08-31 21:48:50'),
(7, 2, 1, 'ingreso', 118.00, 'Venta registrada - Folio V-20260831-00005 - Método: Efectivo', '2026-08-31 21:49:01'),
(8, 2, 1, 'cierre', 2378.00, 'Cierre de caja', '2026-08-31 21:51:28'),
(9, 1, 1, 'ingreso', 80.00, 'Venta registrada - Folio V-20260904-00001 - Método: Efectivo', '2026-09-04 19:12:44'),
(10, 1, 1, 'ingreso', 70.00, 'Venta registrada - Folio V-20260905-00001 - Método: Efectivo', '2026-09-05 17:04:26'),
(11, 1, 1, 'ingreso', 90.00, 'Venta registrada - Folio V-20260905-00002 - Método: Efectivo', '2026-09-05 17:20:36'),
(12, 1, 1, 'ingreso', 126.00, 'Venta registrada - Folio V-20260905-00003 - Método: Efectivo', '2026-09-05 17:25:47'),
(13, 1, 1, 'ingreso', 38.00, 'Venta registrada - Folio V-20260905-00004 - Método: Efectivo', '2026-09-05 17:27:35'),
(14, 1, 1, 'ingreso', 18.00, 'Venta registrada - Folio V-20260905-00005 - Método: Efectivo', '2026-09-05 17:31:12'),
(15, 1, 1, 'apertura', 2000.00, 'Apertura de caja', '2026-09-05 17:32:23'),
(16, 1, 1, 'cierre', 2000.00, 'Cierre de caja', '2026-09-05 17:32:46'),
(17, 1, 1, 'ingreso', 245.00, 'Venta registrada - Folio V-20260905-00006 - Método: Efectivo', '2026-09-05 17:40:04'),
(18, 1, 1, 'ingreso', 128.00, 'Venta registrada - Folio V-20260905-00007 - Método: Efectivo', '2026-09-05 18:00:42'),
(19, 1, 1, 'ingreso', 70.00, 'Venta registrada - Folio V-20260905-00008 - Método: Efectivo', '2026-09-05 18:01:34'),
(20, 1, 1, 'ingreso', 36.00, 'Venta registrada - Folio V-20260905-00009 - Método: Efectivo', '2026-09-05 18:01:57'),
(21, 1, 1, 'ingreso', 106.00, 'Venta registrada - Folio V-20260905-00010 - Método: Efectivo', '2026-09-05 18:03:01'),
(22, 1, 1, 'ingreso', 270.00, 'Venta registrada - Folio V-20260905-00011 - Método: Efectivo', '2026-09-05 18:03:59'),
(23, 1, 1, 'ingreso', 38.00, 'Venta registrada - Folio V-20260905-00012 - Método: Efectivo', '2026-09-05 18:04:22'),
(24, 3, 2, 'ingreso', 38.00, 'Venta registrada - Folio V-20260905-00013 - Método: Efectivo', '2026-09-05 18:35:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la categoría',
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre de la categoría',
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descripción detallada de la categoría',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de productos';

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Abarrotes', 'Productos de despensa básica'),
(2, 'Bebidas', 'Bebidas sin alcohol y refrescos'),
(3, 'Snacks', 'Botanas, dulces y frituras'),
(4, 'Limpieza', 'Productos de limpieza del hogar'),
(5, 'Cuidado Personal', 'Artículos de higiene y cuidado personal'),
(6, 'Verduras', 'Verduras se venden por libra y unidad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rtn_identidad` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `limite_credito` decimal(10,2) NOT NULL DEFAULT '0.00',
  `saldo_pendiente` decimal(10,2) NOT NULL DEFAULT '0.00',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_clientes_rtn` (`rtn_identidad`),
  KEY `idx_clientes_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes y crédito';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración general del sistema';

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`, `actualizado_en`) VALUES
('ancho_ticket', '80mm', '2026-08-31 21:28:10'),
('direccion', 'Barrio El Centro, 2da Calle, Tocoa, Colón', '2026-08-31 21:28:10'),
('email', 'ventas@comercialelsol.hn', '2026-08-31 21:28:10'),
('impuesto_porcentaje', '15', '2026-08-31 21:28:10'),
('logotipo_path', 'uploads/logo.png', '2026-08-31 21:28:10'),
('mensaje_ticket', '¡Gracias por su compra!', '2026-08-31 21:28:10'),
('moneda_simbolo', 'L', '2026-08-31 21:28:10'),
('nombre_negocio', 'COMERCIAL EL SOL S. DE R. L.', '2026-08-31 21:28:10'),
('rtn', '08011995123456', '2026-08-31 21:28:10'),
('sar_activo', '0', '2026-09-04 19:42:34'),
('sar_cai', '3B8E9F-12A456-7890BC-DEF123-456789-A1', '2026-08-31 21:28:10'),
('sar_correlativo_actual', '1', '2026-08-31 21:28:10'),
('sar_fecha_limite', '2026-12-31', '2026-08-31 21:28:10'),
('sar_rango_final', '000-001-01-00020000', '2026-08-31 21:28:10'),
('sar_rango_inicial', '000-001-01-00000001', '2026-08-31 21:28:10'),
('telefono', '+504 2444-1234', '2026-08-31 21:28:10'),
('ticket_fuente', 'Arial', '2026-08-31 21:28:10'),
('ticket_mostrar_logo', '0', '2026-08-31 21:28:10'),
('ticket_mostrar_sar', '1', '2026-08-31 21:28:10'),
('ticket_tamano_fuente', '12px', '2026-08-31 21:28:10'),
('tipo_comprobante_default', 'recibo', '2026-08-31 21:28:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

DROP TABLE IF EXISTS `detalle_ventas`;
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del detalle',
  `venta_id` int NOT NULL COMMENT 'Llave foránea hacia la venta',
  `producto_id` int NOT NULL COMMENT 'Llave foránea hacia el producto vendido',
  `cantidad` decimal(10,3) NOT NULL DEFAULT '1.000' COMMENT 'Cantidad de unidades vendidas',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio unitario al momento de la venta',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Subtotal = cantidad * precio_unitario',
  `tipo_presentacion` enum('unidad','empaque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidad',
  `nombre_presentacion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unidad',
  `factor_unidades` decimal(10,3) NOT NULL DEFAULT '1.000',
  PRIMARY KEY (`id`),
  KEY `idx_detalle_ventas_venta_id` (`venta_id`),
  KEY `idx_detalle_ventas_producto_id` (`producto_id`),
  KEY `idx_detalle_ventas_venta_producto` (`venta_id`,`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de productos por venta';

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `tipo_presentacion`, `nombre_presentacion`, `factor_unidades`) VALUES
(1, 1, 1, 1.000, 25.00, 25.00, 'unidad', 'Unidad', 1.000),
(2, 2, 4, 3.000, 27.00, 81.00, 'unidad', 'Unidad', 1.000),
(3, 2, 10, 4.000, 20.00, 80.00, 'unidad', 'Unidad', 1.000),
(4, 2, 8, 1.000, 26.00, 26.00, 'unidad', 'Unidad', 1.000),
(5, 2, 9, 1.000, 36.00, 36.00, 'unidad', 'Unidad', 1.000),
(6, 2, 7, 1.000, 12.00, 12.00, 'unidad', 'Unidad', 1.000),
(7, 2, 6, 10.000, 18.00, 180.00, 'unidad', 'Unidad', 1.000),
(8, 3, 3, 1.000, 38.00, 38.00, 'unidad', 'Unidad', 1.000),
(9, 4, 3, 20.000, 38.00, 760.00, 'unidad', 'Unidad', 1.000),
(10, 5, 6, 1.000, 18.00, 18.00, 'unidad', 'Unidad', 1.000),
(11, 5, 9, 1.000, 36.00, 36.00, 'unidad', 'Unidad', 1.000),
(12, 5, 8, 1.000, 26.00, 26.00, 'unidad', 'Unidad', 1.000),
(13, 5, 3, 1.000, 38.00, 38.00, 'unidad', 'Unidad', 1.000),
(14, 6, 12, 4.000, 20.00, 80.00, 'unidad', 'Unidad', 1.000),
(15, 7, 12, 1.000, 70.00, 70.00, 'empaque', 'Paquete', 4.000),
(16, 8, 12, 1.000, 20.00, 20.00, 'unidad', 'Unidad', 1.000),
(17, 8, 12, 1.000, 70.00, 70.00, 'empaque', 'Paquete', 4.000),
(18, 9, 9, 1.000, 36.00, 36.00, 'unidad', 'Unidad', 1.000),
(19, 9, 12, 1.000, 20.00, 20.00, 'unidad', 'Unidad', 1.000),
(20, 9, 12, 1.000, 70.00, 70.00, 'empaque', 'Paquete', 4.000),
(21, 10, 3, 1.000, 38.00, 38.00, 'unidad', 'Unidad', 1.000),
(22, 11, 11, 1.000, 18.00, 18.00, 'unidad', 'Unidad', 1.000),
(23, 12, 1, 1.000, 220.00, 220.00, 'empaque', 'Caja', 10.000),
(24, 12, 1, 1.000, 25.00, 25.00, 'unidad', 'Unidad', 1.000),
(25, 13, 12, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(26, 13, 12, 1.000, 0.00, 0.00, 'empaque', 'Paquete', 4.000),
(27, 13, 3, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(28, 14, 12, 1.000, 0.00, 0.00, 'empaque', 'Paquete', 4.000),
(29, 15, 9, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(30, 16, 9, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(31, 16, 12, 1.000, 0.00, 0.00, 'empaque', 'Paquete', 4.000),
(32, 17, 12, 3.000, 0.00, 0.00, 'empaque', 'Paquete', 4.000),
(33, 17, 12, 3.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(34, 18, 3, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000),
(35, 19, 3, 1.000, 0.00, 0.00, 'unidad', 'Unidad', 1.000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

DROP TABLE IF EXISTS `inventario_movimientos`;
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `tipo_movimiento` enum('entrada','salida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL COMMENT 'Cantidad del movimiento',
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventario_producto` (`producto_id`),
  KEY `idx_inventario_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bitácora de ajustes de inventario';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_clientes`
--

DROP TABLE IF EXISTS `pagos_clientes`;
CREATE TABLE IF NOT EXISTS `pagos_clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `forma_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pagos_cliente` (`cliente_id`),
  KEY `fk_pagos_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abonos de clientes';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del producto',
  `codigo_barras` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código de barras del producto (EAN/UPC)',
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre o descripción del producto',
  `precio_costo` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio de compra al proveedor',
  `precio_venta` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio de venta al público',
  `stock` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT 'Cantidad disponible en inventario',
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT '1.000' COMMENT 'Stock mínimo para alerta de reposición',
  `unidad_medida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidad' COMMENT 'Unidad de medida del producto',
  `permite_decimales` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Indica si el producto acepta cantidades fraccionarias',
  `categoria_id` int DEFAULT NULL COMMENT 'Llave foránea hacia la categoría del producto',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de alta del producto',
  `unidades_por_empaque` decimal(10,3) NOT NULL DEFAULT '1.000' COMMENT 'Cantidad de unidades base contenidas en 1 empaque (caja/fardo). Ej: 10 = 1 caja = 10 unidades',
  `precio_empaque` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio de venta de 1 empaque completo (caja/fardo). Dejar en 0 si no se vende por empaque.',
  `codigo_barras_empaque` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de barras exclusivo del empaque (si el proveedor imprime uno diferente por caja).',
  `tipo_venta` enum('solo_unidad','solo_empaque','ambos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'solo_unidad' COMMENT 'Restringe cómo se ofrece el producto en el POS: solo unidad, solo empaque, o ambos.',
  `nombre_empaque` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Caja' COMMENT 'Nombre de la presentación: Caja, Bulto, Fardo, Paquete',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_productos_codigo_barras` (`codigo_barras`),
  UNIQUE KEY `idx_productos_codigo_barras_empaque` (`codigo_barras_empaque`),
  KEY `idx_productos_nombre` (`nombre`),
  KEY `idx_productos_categoria_id` (`categoria_id`),
  KEY `idx_productos_stock` (`stock`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de productos';

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_barras`, `nombre`, `precio_costo`, `precio_venta`, `stock`, `stock_minimo`, `unidad_medida`, `permite_decimales`, `categoria_id`, `creado_en`, `unidades_por_empaque`, `precio_empaque`, `codigo_barras_empaque`, `tipo_venta`, `nombre_empaque`) VALUES
(1, '7501000123451', 'Arroz Blanco 1kg', 18.50, 25.00, 108.000, 20.000, 'kg', 0, 1, '2026-08-31 21:28:10', 10.000, 220.00, '7701000123451', 'ambos', 'Caja'),
(2, '7501000123452', 'Frijoles Negros 1kg', 22.00, 30.50, 90.000, 15.000, 'kg', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(3, '7501000123453', 'Aceite Vegetal 1L', 28.00, 38.00, 34.000, 10.000, 'unidad', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(4, '7501000123454', 'Leche Entera 1L', 21.00, 27.00, 77.000, 12.000, 'unidad', 0, 2, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(5, '7501000123455', 'Azúcar 1kg', 16.00, 22.00, 110.000, 18.000, 'kg', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(6, '7501000123456', 'Sal de Cocina 1kg', 12.00, 18.00, 59.000, 10.000, 'kg', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(7, '7501000123457', 'Fideos Spaghetti 200g', 8.00, 12.00, 89.000, 15.000, 'unidad', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(8, '7501000123458', 'Sardinas en Salsa 425g', 18.00, 26.00, 58.000, 10.000, 'unidad', 0, 1, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(9, '7501000123459', 'Papel Higiénico 4 rollos', 26.00, 36.00, 35.000, 8.000, 'unidad', 0, 4, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(10, '7501000123460', 'Jabón de Baño 120g', 14.00, 20.00, 86.000, 15.000, 'unidad', 0, 5, '2026-08-31 21:28:10', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(11, 'PAPAS', 'Libra de papa', 10.00, 18.00, 74.500, 1.000, 'libra', 1, 6, '2026-09-04 18:29:45', 1.000, 0.00, NULL, 'solo_unidad', 'Caja'),
(12, '207993190450', 'Rollo de Papel Higiénico', 15.00, 20.00, 57.000, 20.000, 'unidad', 0, 4, '2026-09-04 18:58:58', 4.000, 70.00, '200865816948', 'ambos', 'Paquete');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del usuario',
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre completo del usuario',
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre de usuario para iniciar sesión',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contraseña hasheada con bcrypt',
  `rol` enum('admin','cajero','cajero_movil') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cajero' COMMENT 'Rol del usuario en el sistema',
  `estado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = Activo, 0 = Inactivo',
  `caja_id` int DEFAULT NULL COMMENT 'Caja predeterminada del usuario',
  `sucursal` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sucursal asignada',
  `caja` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Caja asignada',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación del registro',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuarios_usuario` (`usuario`),
  KEY `idx_usuarios_rol` (`rol`),
  KEY `idx_usuarios_estado` (`estado`),
  KEY `idx_usuarios_caja` (`caja_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios del sistema PDV';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `estado`, `caja_id`, `sucursal`, `caja`, `creado_en`) VALUES
(1, 'Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, NULL, NULL, '2026-08-31 21:28:10'),
(2, 'Cajero Principal', 'cajero', '$2y$10$uFaZvFrwaw3uV/ACRDgs2eRqP7KFg01yutDioFbbXQQ1r8u.L51Lu', 'cajero', 1, 1, 'Mi Negocio', '', '2026-08-31 21:28:10'),
(3, 'Usuario Movil', 'mobile', '$2y$10$tla8G1mZIE0vqiFSPoSLlOD8XRXIM4N2eqF.CudunzxsjhWcdNByu', 'cajero_movil', 1, 2, 'Tienda', '', '2026-09-05 18:14:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la venta',
  `folio` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de folio o ticket de la venta',
  `usuario_id` int NOT NULL COMMENT 'Llave foránea hacia el usuario que realizó la venta',
  `caja_id` int DEFAULT NULL COMMENT 'Caja activa de la venta',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Monto total de la venta',
  `pagado_con` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Cantidad de dinero entregada por el cliente',
  `cambio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Cambio devuelto al cliente',
  `metodo_pago` enum('efectivo','tarjeta','transferencia','credito') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo' COMMENT 'Método de pago utilizado',
  `cliente_id` int DEFAULT NULL COMMENT 'Cliente asociado a la venta a crédito',
  `cliente_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nombre del cliente eventual o factura',
  `cliente_rtn` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RTN/identidad del cliente eventual',
  `cliente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Teléfono del cliente eventual',
  `cliente_direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dirección del cliente eventual',
  `tipo_comprobante` enum('recibo','factura') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recibo' COMMENT 'Tipo de comprobante de la venta',
  `cai` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de CAI asociado al comprobante',
  `correlativo_sar` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Correlativo SAR para facturación',
  `fecha_venta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se realizó la venta',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ventas_folio` (`folio`),
  KEY `idx_ventas_fecha_venta` (`fecha_venta`),
  KEY `idx_ventas_usuario_id` (`usuario_id`),
  KEY `idx_ventas_caja_id` (`caja_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Encabezado de ventas';

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `folio`, `usuario_id`, `caja_id`, `total`, `pagado_con`, `cambio`, `metodo_pago`, `cliente_id`, `cliente_nombre`, `cliente_rtn`, `cliente_telefono`, `cliente_direccion`, `tipo_comprobante`, `cai`, `correlativo_sar`, `fecha_venta`) VALUES
(1, 'V-20260831-00001', 1, 1, 25.00, 25.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-08-31 21:38:36'),
(2, 'V-20260831-00002', 1, 1, 415.00, 415.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'factura', NULL, NULL, '2026-08-31 21:43:09'),
(3, 'V-20260831-00003', 1, 1, 38.00, 38.00, 0.00, 'transferencia', NULL, 'Consumidor Final', NULL, NULL, NULL, 'factura', NULL, NULL, '2026-08-31 21:43:30'),
(4, 'V-20260831-00004', 2, 1, 760.00, 760.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-08-31 21:48:50'),
(5, 'V-20260831-00005', 2, 1, 118.00, 118.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'factura', NULL, NULL, '2026-08-31 21:49:01'),
(6, 'V-20260904-00001', 1, 1, 80.00, 80.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-04 19:12:44'),
(7, 'V-20260905-00001', 1, 1, 70.00, 70.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:04:26'),
(8, 'V-20260905-00002', 1, 1, 90.00, 90.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:20:36'),
(9, 'V-20260905-00003', 1, 1, 126.00, 126.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:25:47'),
(10, 'V-20260905-00004', 1, 1, 38.00, 38.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:27:35'),
(11, 'V-20260905-00005', 1, 1, 18.00, 100.00, 82.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:31:12'),
(12, 'V-20260905-00006', 1, 1, 245.00, 245.00, 0.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 17:40:04'),
(13, 'V-20260905-00007', 1, 1, 128.00, 500.00, 372.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:00:42'),
(14, 'V-20260905-00008', 1, 1, 70.00, 100.00, 30.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:01:34'),
(15, 'V-20260905-00009', 1, 1, 36.00, 500.00, 464.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:01:57'),
(16, 'V-20260905-00010', 1, 1, 106.00, 500.00, 394.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:03:01'),
(17, 'V-20260905-00011', 1, 1, 270.00, 500.00, 230.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:03:59'),
(18, 'V-20260905-00012', 1, 1, 38.00, 500.00, 462.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:04:22'),
(19, 'V-20260905-00013', 3, 2, 38.00, 500.00, 462.00, 'efectivo', NULL, 'Consumidor Final', NULL, NULL, NULL, 'recibo', NULL, NULL, '2026-09-05 18:35:15');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `caja_cortes`
--
ALTER TABLE `caja_cortes`
  ADD CONSTRAINT `fk_caja_cortes_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caja_cortes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `caja_movimientos`
--
ALTER TABLE `caja_movimientos`
  ADD CONSTRAINT `fk_caja_movimientos_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caja_movimientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `fk_detalle_ventas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_ventas_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `fk_inventario_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_clientes`
--
ALTER TABLE `pagos_clientes`
  ADD CONSTRAINT `fk_pagos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
