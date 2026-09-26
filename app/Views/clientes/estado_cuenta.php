<?php
$cliente = $cuenta['cliente'];
$resumen = $cuenta['resumen'];
$mensaje = $_SESSION['mensaje_clientes'] ?? null;
unset($_SESSION['mensaje_clientes']);
$esAdmin = in_array(($_SESSION['rol'] ?? ''), ['admin', 'administrador'], true);
$tituloPagina = 'Estado de cuenta';
require APP_PATH . 'Views/layouts/pos_header.php';
?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Cuenta corriente · <?php echo (($cliente['tipo'] ?? 'minorista') === 'mayorista') ? 'Mayorista' : 'Minorista'; ?> · Crédito a <?php echo (int)($resumen['dias_credito'] ?? 30); ?> días</span>
        <h1><?php echo htmlspecialchars($cliente['nombre']); ?></h1>
        <p><?php echo htmlspecialchars($cliente['rtn_identidad'] ?? ''); ?> | <?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></p>
    </div>
    <div>
        <a class="btn-pos btn-pos-secondary" href="<?php echo URL_BASE; ?>clientes/cartera">Cartera CxC</a>
        <a class="btn-pos btn-pos-secondary" href="<?php echo URL_BASE; ?>clientes/estado-cuenta/<?php echo (int)$cliente['id']; ?>/imprimir" target="_blank">Imprimir</a>
        <?php
        $waDigitos = preg_replace('/\D+/', '', (string)($cliente['telefono'] ?? ''));
        if (strlen($waDigitos) === 8) { $waDigitos = '504' . $waDigitos; }
        $waTexto = 'Hola ' . $cliente['nombre'] . ', su estado de cuenta: saldo ' . formatearMoneda($resumen['saldo']) . ' (vencido ' . formatearMoneda($resumen['vencido']) . '). ¡Gracias por su pago!';
        ?>
        <?php if (strlen($waDigitos) >= 11): ?>
            <a class="btn-pos btn-pos-primary" href="https://wa.me/<?php echo htmlspecialchars($waDigitos); ?>?text=<?php echo urlencode($waTexto); ?>" target="_blank">WhatsApp</a>
        <?php endif; ?>
        <button class="btn-pos btn-success" type="button" onclick="document.getElementById('modal-abono').hidden=false">Registrar Nuevo Abono</button>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?><?php if (!empty($_SESSION['ultimo_abono_id'])): ?> <a href="<?php echo URL_BASE; ?>clientes/abono/ticket/<?php echo (int)$_SESSION['ultimo_abono_id']; ?>">Imprimir comprobante</a><?php unset($_SESSION['ultimo_abono_id']); endif; ?></div><?php endif; ?>

<section class="resumen-caja">
    <div><span>Límite de crédito</span><strong><?php echo formatearMoneda($cliente['limite_credito']); ?></strong></div>
    <div class="esperado"><span>Saldo pendiente</span><strong><?php echo formatearMoneda($resumen['saldo']); ?></strong></div>
    <div><span>Crédito disponible</span><strong><?php echo formatearMoneda($resumen['disponible']); ?></strong></div>
    <div class="<?php echo $resumen['vencido'] > 0.005 ? 'stock-bajo' : ''; ?>"><span>Vencido</span><strong><?php echo formatearMoneda($resumen['vencido']); ?></strong></div>
    <div><span>Por vencer</span><strong><?php echo formatearMoneda($resumen['por_vencer']); ?></strong></div>
    <div><span>Mora máxima</span><strong><?php echo (int)$resumen['mora_max_dias']; ?> días</strong></div>
</section>

<?php $cubetas = $resumen['cubetas']; ?>
<section class="tarjeta">
    <h2 class="tarjeta-titulo">Antigüedad de saldos</h2>
    <div class="resumen-caja">
        <div><span>Vigente</span><strong><?php echo formatearMoneda($cubetas['al_dia']); ?></strong></div>
        <div><span>1-30 días</span><strong><?php echo formatearMoneda($cubetas['m1_30']); ?></strong></div>
        <div><span>31-60 días</span><strong><?php echo formatearMoneda($cubetas['m31_60']); ?></strong></div>
        <div><span>61-90 días</span><strong><?php echo formatearMoneda($cubetas['m61_90']); ?></strong></div>
        <div><span>+90 días</span><strong><?php echo formatearMoneda($cubetas['m90']); ?></strong></div>
    </div>
</section>

<section class="tarjeta">
    <h2 class="tarjeta-titulo">Facturas a crédito (con saldo y vencimiento)</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Fecha</th><th>Folio</th><th>Total</th><th>Abonado</th><th>Saldo</th><th>Vence</th><th>Mora</th></tr></thead>
            <tbody>
                <?php if (!$cuenta['facturas']): ?><tr><td colspan="7" class="tabla-vacia">No hay compras a crédito.</td></tr><?php endif; ?>
                <?php foreach ($cuenta['facturas'] as $factura): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($factura['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($factura['folio']); ?></td>
                        <td><?php echo formatearMoneda($factura['total']); ?></td>
                        <td><?php echo formatearMoneda($factura['aplicado']); ?></td>
                        <td><?php echo formatearMoneda($factura['saldo']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($factura['vencimiento'])); ?></td>
                        <td class="<?php echo $factura['dias_mora'] > 0 ? 'stock-bajo' : ''; ?>">
                            <?php echo $factura['saldo'] > 0.005 ? htmlspecialchars($factura['bucket']['etiqueta']) : 'Saldada'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="tarjeta">
    <h2 class="tarjeta-titulo">Movimiento unificado (cargos y abonos con saldo corrido)</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Fecha</th><th>Movimiento</th><th>Cargo</th><th>Abono</th><th>Saldo</th></tr></thead>
            <tbody>
                <?php if (!$cuenta['movimientos']): ?><tr><td colspan="5" class="tabla-vacia">Sin movimientos.</td></tr><?php endif; ?>
                <?php foreach ($cuenta['movimientos'] as $mov): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?></td>
                        <td>
                            <?php if ($mov['tipo'] === 'cargo'): ?>
                                Compra <?php echo htmlspecialchars($mov['folio']); ?>
                                <?php if ($mov['dias_mora'] > 0): ?> <span class="stock-bajo">(vence <?php echo date('d/m/Y', strtotime($mov['vencimiento'])); ?>)</span><?php endif; ?>
                            <?php else: ?>
                                Abono (<?php echo htmlspecialchars($mov['forma_pago']); ?>)<?php if (!empty($mov['observacion'])): ?> — <?php echo htmlspecialchars($mov['observacion']); ?><?php endif; ?>
                                <?php if (!empty($mov['aplicaciones'])): ?>
                                    <br><small>Aplicado a:
                                    <?php foreach ($mov['aplicaciones'] as $ap): ?>
                                        <?php echo htmlspecialchars($ap['folio']); ?> (<?php echo formatearMoneda($ap['monto']); ?>)
                                    <?php endforeach; ?>
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $mov['cargo'] > 0 ? formatearMoneda($mov['cargo']) : '—'; ?></td>
                        <td><?php echo $mov['abono'] > 0 ? formatearMoneda($mov['abono']) : '—'; ?></td>
                        <td><strong><?php echo formatearMoneda($mov['saldo_corrido']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="tarjeta">
    <h2 class="tarjeta-titulo">Historial de abonos</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Fecha</th><th>Forma de pago</th><th>Observación</th><th>Abono</th><th>Comprobante</th><?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?></tr></thead>
            <tbody>
                <?php if (!$cuenta['abonos']): ?><tr><td colspan="<?php echo $esAdmin ? 6 : 5; ?>" class="tabla-vacia">No hay abonos registrados.</td></tr><?php endif; ?>
                <?php foreach ($cuenta['abonos'] as $abono): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($abono['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($abono['forma_pago']); ?></td>
                        <td><?php echo htmlspecialchars($abono['observacion'] ?? ''); ?></td>
                        <td><?php echo formatearMoneda($abono['monto']); ?></td>
                        <td><a class="btn btn-pequeno btn-primario" href="<?php echo URL_BASE; ?>clientes/abono/ticket/<?php echo (int)$abono['id']; ?>?reimpresion=1" target="_blank">Reimprimir</a></td>
                        <?php if ($esAdmin): ?>
                            <td>
                                <form method="POST" action="<?php echo URL_BASE; ?>clientes/anular-abono" onsubmit="return pedirMotivoAnulacion(this);">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="pago_id" value="<?php echo (int)$abono['id']; ?>">
                                    <input type="hidden" name="cliente_id" value="<?php echo (int)$cliente['id']; ?>">
                                    <input type="hidden" name="motivo" value="">
                                    <button class="btn btn-pequeno btn-ligero" type="submit">Anular</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($esAdmin): ?><p><small>Anular revierte el saldo, libera las facturas aplicadas y registra un egreso compensatorio en caja con auditoría.</small></p><?php endif; ?>
</section>

<script>
function pedirMotivoAnulacion(form) {
    var motivo = prompt('Motivo de la anulación del abono (queda en auditoría):');
    if (motivo === null || motivo.trim() === '') {
        alert('Debes indicar el motivo para anular.');
        return false;
    }
    form.querySelector('input[name="motivo"]').value = motivo.trim();
    return confirm('¿Anular este abono? Se revertirá el saldo del cliente.');
}
</script>

<div class="modal-simple" id="modal-abono" hidden><div class="tarjeta"><h2 class="tarjeta-titulo">Registrar abono (se aplica a las facturas más antiguas)</h2><form method="POST" action="<?php echo URL_BASE; ?>clientes/abonar" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><input type="hidden" name="cliente_id" value="<?php echo (int)$cliente['id']; ?>"><div class="campo"><label>Monto (L)</label><input name="monto" type="number" min="0.01" max="<?php echo htmlspecialchars($resumen['saldo']); ?>" step="0.01" required></div><div class="campo"><label>Forma de pago</label><select name="forma_pago"><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option><option value="transferencia">Transferencia</option></select></div><div class="campo campo-ancho"><label>Observación</label><input name="observacion" maxlength="255"></div><div class="form-acciones"><button type="button" class="btn btn-ligero" onclick="document.getElementById('modal-abono').hidden=true">Cancelar</button><button class="btn btn-exito">Guardar abono</button></div></form></div></div>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
