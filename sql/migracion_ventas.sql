-- Agregar campos adicionales a la tabla ventas para clientes eventuales/facturación SAR
ALTER TABLE ventas 
ADD COLUMN cliente_nombre VARCHAR(150) NULL AFTER cliente_id,
ADD COLUMN cliente_rtn VARCHAR(30) NULL AFTER cliente_nombre,
ADD COLUMN cliente_telefono VARCHAR(30) NULL AFTER cliente_rtn,
ADD COLUMN cliente_direccion VARCHAR(255) NULL AFTER cliente_telefono,
ADD COLUMN cai VARCHAR(100) NULL AFTER cliente_direccion,
ADD COLUMN correlativo_sar VARCHAR(50) NULL AFTER cai;