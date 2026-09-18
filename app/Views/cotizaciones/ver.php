<?php $tituloPagina = 'Cotización ' . ($cotizacion['folio'] ?? ''); $claseMain = 'cotizaciones-ver-main'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<link rel="stylesheet" href="<?php echo URL_BASE; ?>css/cotizaciones.css?v=<?php echo filemtime(PUBLIC_PATH . 'css/cotizaciones.css'); ?>">
<?php
$esvendedor = in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['vendedor', 'cajero_movil'], true);
$esCajador = !$esvendedor;
$etiquetasEstado = ['pendiente' => 'Pendiente', 'facturada' => 'Facturada', 'cancelada' => 'Cancelada'];
?>
<section class="cotizaciones-editor-cabecera">
    <div class="cotizaciones-editor-titulo">
        <a class="btn btn-secondary btn-sm" href="<?php echo URL_BASE; ?>cotizaciones"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        <div>
            <span class="eyebrow">Cotización</span>
            <h1><?php echo htmlspecialchars($cotizacion['folio']); ?></h1>
            <p>Registrada el <?php echo date('d/m/Y H:i', strtotime($cotizacion['creada_en'])); ?> por <strong><?php echo htmlspecialchars($cotizacion['vendedor']); ?></strong></p>
        </div>
        <span class="estado-cotizacion estado-<?php echo htmlspecialchars($cotizacion['estado']); ?>"><?php echo $etiquetasEstado[$cotizacion['estado']] ?? $cotizacion['estado']; ?></span>
    </div>
    <div class="cotizaciones-editor-acciones" style="display:flex; gap:8px;">
        <a class="btn btn-secondary" target="_blank" href="<?php echo URL_BASE; ?>cotizaciones/imprimir/<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-print"></i> Imprimir</a>
        <?php if ($cotizacion['estado'] === 'pendiente'): ?>
            <?php if ($esCajador): ?>
                <a class="btn btn-exito" href="<?php echo URL_BASE; ?>ventas?cotizar=<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-cash-register"></i> Facturar en caja</a>
            <?php elseif ((int)$cotizacion['vendedor_id'] === (int)$_SESSION['id']): ?>
                <a class="btn btn-secondary" href="<?php echo URL_BASE; ?>cotizaciones/editar/<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <form method="POST" action="<?php echo URL_BASE; ?>cotizaciones/cancelar/<?php echo (int)$cotizacion['id']; ?>" onsubmit="return confirm('¿Cancelar esta cotización?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancelar</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($cotizacion['estado'] === 'facturada' && !empty($cotizacion['venta_id'])): ?>
    <div class="alerta alerta-exito"><i class="fa-solid fa-circle-check"></i> Esta cotización ya fue facturada y convertida en venta.</div>
<?php elseif ($cotizacion['estado'] === 'cancelada'): ?>
    <div class="alerta alerta-error"><i class="fa-solid fa-ban"></i> Esta cotización fue cancelada.</div>
<?php endif; ?>

<section class="cot-ver-cabecera">
    <div class="cot-ver-tarjeta">
        <h3>Cliente</h3>
        <p class="cot-ver-nombre"><?php echo htmlspecialchars($cotizacion['cliente_nombre'] ?: 'CONSUMIDOR FINAL'); ?></p>
        <?php if ($cotizacion['cliente_rtn']): ?><p><strong>RTN:</strong> <?php echo htmlspecialchars($cotizacion['cliente_rtn']); ?></p><?php endif; ?>
        <?php if ($cotizacion['cliente_telefono']): ?><p><strong>Tel:</strong> <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?></p><?php endif; ?>
        <?php if ($cotizacion['cliente_direccion']): ?><p><strong>Dir:</strong> <?php echo htmlspecialchars($cotizacion['cliente_direccion']); ?></p><?php endif; ?>
    </div>
    <div class="cot-ver-tarjeta">
        <h3>Vigencia</h3>
        <?php if ($cotizacion['fecha_validez']): ?>
            <p>Válida hasta <strong><?php echo date('d/m/Y', strtotime($cotizacion['fecha_validez'])); ?></strong></p>
            <?php if (strtotime($cotizacion['fecha_validez']) < strtotime('today') && $cotizacion['estado'] === 'pendiente'): ?>
                <span class="estado-cotizacion estado-cancelada">VENCIDA</span>
            <?php endif; ?>
        <?php else: ?>
            <p>Sin fecha de vencimiento</p>
        <?php endif; ?>
        <?php if ($cotizacion['observaciones']): ?><h3>Observaciones</h3><p><?php echo nl2br(htmlspecialchars($cotizacion['observaciones'])); ?></p><?php endif; ?>
    </div>
</section>

<section class="tabla-responsive">
    <table class="tabla-catalogo">
        <thead>
            <tr>
                <th>#</th>
                <th>Descripción</th>
                <th class="text-right">P. Unitario</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php $contador = 0; ?>
            <?php foreach ($detalles as $detalle): ?>
                <?php
                $contador++;
                $nombreLin = $detalle['nombre_producto'];
                if (($detalle['tipo_presentacion'] ?? 'unidad') === 'empaque' && !empty($detalle['nombre_presentacion'])) {
                    $factorTexto = rtrim(rtrim(number_format((float)$detalle['factor_unidades'], 2, '.', ''), '0'), '.');
                    $nombreLin = '[' . $detalle['nombre_presentacion'] . ' x' . $factorTexto . '] ' . $nombreLin;
                }
                ?>
                <tr>
                    <td><?php echo $contador; ?></td>
                    <td><?php echo htmlspecialchars($nombreLin); ?></td>
                    <td class="text-right"><?php echo formatearMoneda($detalle['precio_unitario']); ?></td>
                    <td class="text-right"><?php echo rtrim(rtrim(number_format((float)$detalle['cantidad'], 3, '.', ''), '0'), '.'); ?></td>
                    <td class="text-right"><?php echo formatearMoneda($detalle['subtotal']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="cot-ver-totales">
    <div class="cot-ver-totales-block">
        <div class="cot-resumen-fila"><span>Subtotal (lista)</span><strong><?php echo formatearMoneda($cotizacion['subtotal'] + $cotizacion['descuento_total']); ?></strong></div>
        <div class="cot-resumen-fila"><span>Descuentos</span><strong>- <?php echo formatearMoneda($cotizacion['descuento_total']); ?></strong></div>
        <div class="cot-resumen-fila"><span>Base gravada 15%</span><strong><?php echo formatearMoneda($cotizacion['importe_gravado_15']); ?></strong></div>
        <div class="cot-resumen-fila"><span>ISV 15%</span><strong><?php echo formatearMoneda($cotizacion['isv_15']); ?></strong></div>
        <div class="cot-resumen-fila"><span>Base gravada 18%</span><strong><?php echo formatearMoneda($cotizacion['importe_gravado_18']); ?></strong></div>
        <div class="cot-resumen-fila"><span>ISV 18%</span><strong><?php echo formatearMoneda($cotizacion['isv_18']); ?></strong></div>
        <div class="cot-resumen-fila"><span>Exento / Exonerado</span><strong><?php echo formatearMoneda($cotizacion['importe_exento'] + $cotizacion['importe_exonerado']); ?></strong></div>
        <div class="cot-resumen-fila cot-resumen-total"><span>Total a pagar</span><strong><?php echo formatearMoneda($cotizacion['total']); ?></strong></div>
    </div>
    <?php if (($configuracion['cotizacion_mensaje'] ?? '') !== ''): ?>
        <div class="cot-ver-nota"><?php echo htmlspecialchars($configuracion['cotizacion_mensaje']); ?></div>
    <?php endif; ?>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>