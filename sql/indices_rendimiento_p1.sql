-- =============================================================
-- RENDIMIENTO P1 (auditoría puntos 5-6): índices compuestos
-- -------------------------------------------------------------
-- Aplicar UNA sola vez. Si un índice ya existe, MySQL dará error
-- 1061 (Duplicate key name): ignóralo y continúa con el siguiente.
-- BD: web_pdv_advanced_db
-- =============================================================

USE web_pdv_advanced_db;

-- 5. Reportes de ventas por fecha/cajero/caja (Reporte::obtenerResumenGeneral,
--    obtenerVentasPorMetodoPago, VentasController::historialHoy)
ALTER TABLE ventas
    ADD INDEX idx_ventas_fecha_usuario_caja (fecha_venta, usuario_id, caja_id);

-- 5b. Estado de cuenta y ventas a crédito por cliente
ALTER TABLE ventas
    ADD INDEX idx_ventas_cliente_metodo_fecha (cliente_id, metodo_pago, fecha_venta);

-- 5c. Agregado por método de pago en rango de fechas
ALTER TABLE ventas
    ADD INDEX idx_ventas_metodo_fecha (metodo_pago, fecha_venta);

-- 5d. Movimientos de caja por turno (Caja::obtenerMovimientosTurno,
--    Reporte::obtenerReporteCajas)
ALTER TABLE caja_movimientos
    ADD INDEX idx_caja_mov_usu_caja_fecha (usuario_id, caja_id, fecha, tipo);

-- 6. Cotizaciones por vendedor+estado ordenadas (Cotizacion::obtenerTodas)
ALTER TABLE cotizaciones
    ADD INDEX idx_cot_vend_estado_creada (vendedor_id, estado, creada_en);

-- 6b. Cuentas por pagar: historial y CxP (Compra::obtenerCuentasPorPagar)
ALTER TABLE compras
    ADD INDEX idx_compras_cxp (estado, condicion_pago, saldo_pendiente, fecha_vencimiento);

-- 6c. Estado de cuenta clientes / abonos por compra
ALTER TABLE pagos_clientes
    ADD INDEX idx_pagos_cli_fecha (cliente_id, fecha);

ALTER TABLE pagos_proveedores
    ADD INDEX idx_pagos_prov_compra_fecha (compra_id, fecha);
