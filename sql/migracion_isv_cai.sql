-- Migración: separar Factura Fiscal (CAI/SAR) de Recibo Interno + ISV por producto
-- Aplicar una sola vez sobre la base de datos existente.

-- 1. Tipo de impuesto (ISV) configurable por producto
ALTER TABLE productos
    ADD COLUMN tipo_impuesto ENUM('exento','gravado_15','gravado_18','exonerado') NOT NULL DEFAULT 'gravado_15' COMMENT 'Régimen de ISV del producto' AFTER precio_empaque,
    ADD COLUMN porcentaje_isv DECIMAL(5,2) NOT NULL DEFAULT 15.00 COMMENT 'Porcentaje de ISV aplicado' AFTER tipo_impuesto;

-- Deja explícito el estado de los productos ya existentes (comportamiento actual del sistema)
UPDATE productos SET tipo_impuesto = 'gravado_15', porcentaje_isv = 15.00;

-- 2. Desglose de ISV por línea de detalle de venta
ALTER TABLE detalle_ventas
    ADD COLUMN porcentaje_isv DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de ISV aplicado a la línea',
    ADD COLUMN monto_isv DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto de ISV de la línea',
    ADD COLUMN es_exento TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exento de ISV',
    ADD COLUMN es_exonerado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = producto exonerado de ISV';

-- 3. Desglose fiscal SAR en el encabezado de venta
-- Nota: las columnas `cai` y `correlativo_sar` ya existen (ver migracion_ventas.sql)
ALTER TABLE ventas
    ADD COLUMN importe_exento DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exentos de ISV',
    ADD COLUMN importe_exonerado DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total de importes exonerados de ISV',
    ADD COLUMN importe_gravado_15 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 15% de ISV',
    ADD COLUMN isv_15 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 15%',
    ADD COLUMN importe_gravado_18 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base gravada al 18% de ISV',
    ADD COLUMN isv_18 DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ISV calculado al 18%',
    ADD COLUMN rango_autorizado VARCHAR(100) NULL COMMENT 'Rango CAI autorizado vigente al emitir la factura',
    ADD COLUMN fecha_limite_emision DATE NULL COMMENT 'Fecha límite de emisión del CAI vigente al emitir la factura';

-- 4. Nuevos parámetros de numeración oficial SAR (Punto de Venta / Establecimiento / Tipo de Documento)
INSERT INTO configuracion (clave, valor) VALUES
    ('sar_punto_venta', '001'),
    ('sar_establecimiento', '001'),
    ('sar_tipo_documento', '01')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
