<?php
$mensaje = $_SESSION['mensaje_compras'] ?? null;
unset($_SESSION['mensaje_compras']);
$etiquetasTipo = ['factura_cai' => 'Factura fiscal (CAI)', 'recibo' => 'Recibo simple', 'nota_credito' => 'Nota de crédito', 'nota_debito' => 'Nota de débito'];
$tipoDoc = $etiquetasTipo[$compra['tipo_documento']] ?? $compra['tipo_documento'];
$esAnulada = $compra['estado'] === 'anulada';
$saldo = (float)$compra['saldo_pendiente'];
$abonado = (float)$compra['total'] - $saldo;
require APP_PATH . 'Views/layouts/pos_header.php';
?>
<section class="catalogo-encabezado">
    <div><span class="eyebrow">Compras / Detalle</span><h1>Compra <?php echo htmlspecialchars($compra['folio']); ?></h1>
        <p><?php echo htmlspecialchars($compra['proveedor_nombre']); ?> | <?php echo htmlspecialchars($compra['numero_factura']); ?>
        <span class="badge" style="background:<?php echo $esAnulada ? '#fee2e2;color:#991b1b;' : '#dcfce7;color:#166534;'; ?>"><?php echo $esAnulada ? 'Anulada' : 'Recibida'; ?></span></p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras">Volver a Compras</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras/pagos">Cuentas por pagar</a>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

<section class="resumen-caja">
    <div><span>Total factura</span><strong><?php echo formatearMoneda($compra['total']); ?></strong></div>
    <div class="esperado"><span>Abonado</span><strong><?php echo formatearMoneda($abonado); ?></strong></div>
    <div><span>Saldo pendiente</span><strong><?php echo formatearMoneda($saldo); ?></strong></div>
    <div><span>ISV acreditable</span><strong><?php echo (int)$compra['isv_acreditable'] === 1 ? 'SÍ' : 'NO'; ?></strong></div>
</section>

<section class="tarjeta"><h2 class="tarjeta-titulo">Datos del documento</h2>
    <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div><span class="campo-label">Tipo de documento</span><div><?php echo htmlspecialchars($tipoDoc); ?></div></div>
        <div><span class="campo-label">Proveedor</span><div><?php echo htmlspecialchars($compra['proveedor_nombre']); ?></div></div>
        <div><span class="campo-label">RTN proveedor</span><div><?php echo htmlspecialchars($compra['proveedor_rtn'] ?? '—'); ?></div></div>
        <div><span class="campo-label">Condición tributaria</span><div><?php echo $compra['proveedor_tipo'] === 'contribuyente' ? 'Contribuyente' : 'No contribuyente'; ?></div></div>
        <div><span class="campo-label">Fecha de emisión</span><div><?php echo date('d/m/Y', strtotime($compra['fecha_emision'])); ?></div></div>
        <div><span class="campo-label">N.º de factura</span><div><?php echo htmlspecialchars($compra['numero_factura']); ?></div></div>
        <div><span class="campo-label">CAI</span><div><?php echo htmlspecialchars($compra['cai'] ?? '—'); ?></div></div>
        <div><span class="campo-label">Rango autorizado</span><div><?php echo htmlspecialchars($compra['rango_autorizado'] ?? '—'); ?></div></div>
        <div><span class="campo-label">Límite de emisión CAI</span><div><?php echo $compra['fecha_limite_emision'] ? date('d/m/Y', strtotime($compra['fecha_limite_emision'])) : '—'; ?></div></div>
        <div><span class="campo-label">Condición de pago</span><div><?php echo $compra['condicion_pago'] === 'credito' ? 'Crédito (' . (int)$compra['dias_credito'] . ' días)' : 'Contado'; ?></div></div>
        <div><span class="campo-label">Vencimiento</span><div><?php echo $compra['fecha_vencimiento'] ? date('d/m/Y', strtotime($compra['fecha_vencimiento'])) : '—'; ?></div></div>
        <div><span class="campo-label">Sucursal</span><div><?php echo htmlspecialchars($compra['sucursal'] ?? '—'); ?></div></div>
        <div><span class="campo-label">Registrado por</span><div><?php echo htmlspecialchars($compra['usuario_nombre'] ?? ''); ?> (<?php echo date('d/m/Y H:i', strtotime($compra['fecha_registro'])); ?>)</div></div>
        <?php if ((int)$compra['documento_referencia_id'] > 0): ?>
            <div><span class="campo-label">Documento que referencia</span><div>Compra #<?php echo (int)$compra['documento_referencia_id']; ?></div></div>
        <?php endif; ?>
        <?php if (!empty($compra['observaciones'])): ?>
            <div><span class="campo-label">Observaciones</span><div><?php echo htmlspecialchars($compra['observaciones']); ?></div></div>
        <?php endif; ?>
    </div>
</section>

<section class="tarjeta"><h2 class="tarjeta-titulo">Detalle de líneas</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Código</th><th>Descripción</th><th>Presentación</th><th>Cantidad</th><th>Costo unit. (L)</th><th>Costo ingresado (L)</th><th>Impuesto</th><th>Subtotal (L)</th></tr></thead>
            <tbody>
                <?php if (!$compra['detalle']): ?><tr><td colspan="8" class="tabla-vacia">Sin líneas.</td></tr><?php endif; ?>
                <?php foreach ($compra['detalle'] as $linea): ?>
                    <tr>
                        <td><?php echo $linea['tipo_linea'] === 'gasto_operativo' ? 'GASTO' : htmlspecialchars((string)($linea['producto_id'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($linea['nombre'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($linea['nombre_presentacion'] ?? 'Unidad'); ?></td>
                        <td><?php echo rtrim(rtrim(number_format((float)$linea['cantidad'], 3, '.', ''), '0'), '.'); ?></td>
                        <td><?php echo formatearMoneda($linea['costo_unitario']); ?></td>
                        <td><?php echo $linea['costo_ingreso'] !== null && $linea['tipo_linea'] === 'producto' ? formatearMoneda($linea['costo_ingreso']) : '—'; ?></td>
                        <td><?php echo $linea['tipo_impuesto'] === 'exento' || $linea['tipo_impuesto'] === 'exonerado'
                            ? htmlspecialchars(($linea['tipo_impuesto'] === 'exento' ? 'Exento' : 'Exonerado') . ' 0%')
                            : htmlspecialchars((float)$linea['porcentaje_isv'] . '%'); ?></td>
                        <td><?php echo formatearMoneda($linea['subtotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form-grid totales-detalle" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-top:16px;">
        <div><span>Importe exento</span><strong><?php echo formatearMoneda($compra['importe_exento']); ?></strong></div>
        <div><span>Importe exonerado</span><strong><?php echo formatearMoneda($compra['importe_exonerado']); ?></strong></div>
        <div><span>Base gravada 15%</span><strong><?php echo formatearMoneda($compra['importe_gravado_15']); ?></strong></div>
        <div><span>ISV 15%</span><strong><?php echo formatearMoneda($compra['isv_15']); ?></strong></div>
        <div><span>Base gravada 18%</span><strong><?php echo formatearMoneda($compra['importe_gravado_18']); ?></strong></div>
        <div><span>ISV 18%</span><strong><?php echo formatearMoneda($compra['isv_18']); ?></strong></div>
        <div><span>Subtotal</span><strong><?php echo formatearMoneda($compra['subtotal']); ?></strong></div>
        <div><span>Descuento</span><strong><?php echo formatearMoneda($compra['descuento_total']); ?></strong></div>
        <div><span>Flete</span><strong><?php echo formatearMoneda($compra['flete']); ?></strong></div>
        <div><span>Total</span><strong><?php echo formatearMoneda($compra['total']); ?></strong></div>
    </div>
</section>

<section class="tarjeta"><h2 class="tarjeta-titulo">Pagos realizados</h2>
    <div style="margin-bottom:12px;">
        <?php if (!$esAnulada && $saldo > 0): ?>
            <button class="btn-pos btn-success" type="button" onclick="document.getElementById('modal-pago').hidden=false">Registrar pago al proveedor</button>
        <?php else: ?>
            <span class="badge" style="<?php echo $saldo <= 0 ? 'background:#dcfce7;color:#166534;' : 'background:#e2e8f0;color:#475569;'; ?>"><?php echo $saldo <= 0 ? 'Saldada' : 'Sin pagos disponibles'; ?></span>
        <?php endif; ?>
    </div>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Forma de pago</th><th>Observación</th><th>Monto</th></tr></thead>
            <tbody>
                <?php if (!$compra['pagos']): ?><tr><td colspan="5" class="tabla-vacia">No hay pagos registrados.</td></tr><?php endif; ?>
                <?php foreach ($compra['pagos'] as $pago): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($pago['usuario_nombre'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($pago['forma_pago']); ?></td>
                        <td><?php echo htmlspecialchars($pago['observacion'] ?? ''); ?></td>
                        <td><strong><?php echo formatearMoneda($pago['monto']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-simple" id="modal-pago" hidden>
    <div class="tarjeta">
        <h2 class="tarjeta-titulo">Registrar pago al proveedor</h2>
        <form method="POST" action="<?php echo URL_BASE; ?>compras/abonar" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="compra_id" value="<?php echo (int)$compra['id']; ?>">
            <div class="campo"><label>Monto (L)</label><input name="monto" type="number" min="0.01" max="<?php echo htmlspecialchars($saldo); ?>" step="0.01" required value="<?php echo htmlspecialchars($saldo); ?>"></div>
            <div class="campo"><label>Forma de pago</label><select name="forma_pago"><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option><option value="transferencia">Transferencia</option></select></div>
            <div class="campo campo-ancho"><label>Observación</label><input name="observacion" maxlength="255"></div>
            <div class="form-acciones">
                <button type="button" class="btn btn-ligero" onclick="document.getElementById('modal-pago').hidden=true">Cancelar</button>
                <button class="btn btn-exito">Guardar pago</button>
            </div>
        </form>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>