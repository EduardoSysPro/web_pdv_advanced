INSERT INTO configuracion (clave, valor) VALUES
('sar_activo', '0'),
('sar_cai', ''),
('sar_rango_inicial', ''),
('sar_rango_final', ''),
('sar_fecha_limite', ''),
('sar_correlativo_actual', '0')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);