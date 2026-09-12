<?php $tituloPagina = 'Cuentas por pagar'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div><span class="eyebrow">Compras / Proveedores</span><h1>Cuentas por pagar</h1><p>Facturas de compra a crédito pendientes de pago y su historial de abonos.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras">Volver a Compras</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>proveedores">Proveedores</a>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

<section class="resumen-caja">
    <div><span>Total facturado a crédito</span><strong><?php echo formatearMoneda($cuentas['totales']['total_credito']); ?></strong></div>
    <div class="esperado"><span>Abonado</span><strong><?php echo formatearMoneda($cuentas['totales']['total_abonado']); ?></strong></div>
    <div><span>Pendiente de pago</span><strong><?php echo formatearMoneda($cuentas['totales']['total_pendiente']); ?></strong></div>
    <div><span>Facturas vencidas</span><strong><?php echo (int)$cuentas['totales']['vencidas']; ?></strong></div>
</section>

<section class="tarjeta">
    <h2 class="tarjeta-titulo">Facturas pendientes</h2>
    <form method="GET" action="<?php echo URL_BASE; ?>compras/pagos" class="busqueda-inventario" style="margin-bottom:12px;">
        <input class="form-control-pos" type="search" name="busqueda" placeholder="Proveedor, factura o folio" value="<?php echo htmlspecialchars($busqueda); ?>">
        <select class="form-control-pos" name="proveedor_id" style="max-width:220px;">
            <option value="">Todos los proveedores</option>
            <?php foreach ($proveedores as $prov): ?>
                <option value="<?php echo (int)$prov['id']; ?>" <?php echo ((int)$proveedorId === (int)$prov['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn-pos-primary" type="submit">Filtrar</button>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras/pagos">Limpiar</a>
    </form>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Folio</th><th>Proveedor</th><th>N.º Factura</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Abonado</th><th>Saldo</th><th></th></tr></thead>
            <tbody>
                <?php if (!$cuentas['compras']): ?><tr><td colspan="9" class="tabla-vacia">No hay cuentas por pagar pendientes.</td></tr><?php endif; ?>
                <?php foreach ($cuentas['compras'] as $compra): ?>
                    <?php $vencida = !empty($compra['fecha_vencimiento']) && $compra['fecha_vencimiento'] < date('Y-m-d'); ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($compra['folio']); ?></strong></td>
                        <td><?php echo htmlspecialchars($compra['proveedor_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($compra['numero_factura']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($compra['fecha_emision'])); ?></td>
                        <td><?php echo $compra['fecha_vencimiento'] ? date('d/m/Y', strtotime($compra['fecha_vencimiento'])) : '—'; ?><?php if ($vencida): ?> <span class="badge" style="background:#fee2e2;color:#991b1b;">Vencida</span><?php endif; ?></td>
                        <td><?php echo formatearMoneda($compra['total']); ?></td>
                        <td><?php echo formatearMoneda($compra['abonado']); ?></td>
                        <td><strong style="color:#b45309;"><?php echo formatearMoneda($compra['saldo_pendiente']); ?></strong></td>
                        <td><a class="btn-pos btn-primary btn-pequeno" href="<?php echo URL_BASE; ?>compras/ver/<?php echo (int)$compra['id']; ?>">Abonar / Ver</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>