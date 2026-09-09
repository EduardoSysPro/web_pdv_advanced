<?php
$inicio = substr($fechaInicio, 0, 10);
$fin = substr($fechaFin, 0, 10);
$periodo = $inicio === $fin ? date('d/m/Y', strtotime($inicio)) : date('d/m/Y', strtotime($inicio)) . ' al ' . date('d/m/Y', strtotime($fin));
$escapar = static function ($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte general - <?php echo $escapar($periodo); ?></title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #1f2933; font-family: Arial, sans-serif; font-size: 11px; margin: 0; }
        h1 { color: #172b4d; font-size: 22px; margin: 0 0 4px; }
        h2 { border-bottom: 2px solid #d9e2ec; color: #172b4d; font-size: 14px; margin: 22px 0 8px; padding-bottom: 5px; }
        p { color: #52606d; margin: 0; }
        .encabezado { border-bottom: 3px solid #1f7a8c; margin-bottom: 16px; padding-bottom: 12px; }
        .encabezado strong { color: #52606d; display: block; font-size: 12px; margin-top: 8px; }
        .kpis { display: grid; gap: 8px; grid-template-columns: repeat(4, 1fr); }
        .kpi { border: 1px solid #d9e2ec; padding: 9px; }
        .kpi span { color: #52606d; display: block; font-size: 10px; }
        .kpi strong { color: #172b4d; display: block; font-size: 14px; margin-top: 4px; }
        .dos-columnas { display: grid; gap: 20px; grid-template-columns: 1fr 2fr; }
        table { border-collapse: collapse; page-break-inside: auto; width: 100%; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #eaf4f4; color: #172b4d; font-size: 10px; text-align: left; }
        th, td { border-bottom: 1px solid #d9e2ec; padding: 6px 5px; }
        td { color: #364152; }
        .numero { text-align: right; }
        .vacio { color: #7b8794; text-align: center; }
        .pie { border-top: 1px solid #d9e2ec; color: #7b8794; font-size: 9px; margin-top: 22px; padding-top: 7px; }
        @media print { .no-imprimir { display: none; } }
    </style>
</head>
<body>
    <header class="encabezado">
        <h1>Reporte general</h1>
        <p>Resumen de actividad comercial, ventas y cajas</p>
        <strong>Período: <?php echo $escapar($periodo); ?></strong>
    </header>

    <section class="kpis">
        <div class="kpi"><span>Total de ventas</span><strong><?php echo formatearMoneda($resumen['total_ventas']); ?></strong></div>
        <div class="kpi"><span>Costo de lo vendido</span><strong><?php echo formatearMoneda($resumen['total_costo']); ?></strong></div>
        <div class="kpi"><span>Ganancia neta estimada</span><strong><?php echo formatearMoneda($resumen['ganancia_neta']); ?></strong></div>
        <div class="kpi"><span>Transacciones</span><strong><?php echo (int)$resumen['transacciones']; ?></strong></div>
    </section>

    <div class="dos-columnas">
        <section>
            <h2>Ventas por método de pago</h2>
            <table>
                <thead><tr><th>Método</th><th class="numero">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($metodosPago as $metodo => $total): ?>
                        <tr><td><?php echo $escapar(ucfirst($metodo)); ?></td><td class="numero"><?php echo formatearMoneda($total); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Top 10 productos más vendidos</h2>
            <table>
                <thead><tr><th>Código</th><th>Producto</th><th class="numero">Unidades</th><th class="numero">Total</th></tr></thead>
                <tbody>
                    <?php if (!$topProductos): ?><tr><td class="vacio" colspan="4">No hay productos vendidos en este período.</td></tr><?php endif; ?>
                    <?php foreach ($topProductos as $producto): ?>
                        <tr>
                            <td><?php echo $escapar($producto['codigo_barras']); ?></td>
                            <td><?php echo $escapar($producto['nombre']); ?></td>
                            <td class="numero"><?php echo (int)$producto['unidades_vendidas']; ?></td>
                            <td class="numero"><?php echo formatearMoneda($producto['ingreso_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <section>
        <h2>Historial de ventas</h2>
        <table>
            <thead><tr><th>Folio</th><th>Fecha / Hora</th><th>Cliente</th><th>Vendedor</th><th>Método</th><th class="numero">Total</th></tr></thead>
            <tbody>
                <?php if (!$ventas): ?><tr><td class="vacio" colspan="6">No hay ventas en este período.</td></tr><?php endif; ?>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><?php echo $escapar($venta['folio']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                        <td><?php echo $escapar($venta['cliente']); ?></td>
                        <td><?php echo $escapar($venta['vendedor']); ?></td>
                        <td><?php echo $escapar(ucfirst($venta['metodo_pago'])); ?></td>
                        <td class="numero"><?php echo formatearMoneda($venta['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Reportes de cajas</h2>
        <table>
            <thead><tr><th>Caja</th><th>Cajero</th><th>Apertura</th><th>Cierre</th><th class="numero">Fondo</th><th class="numero">Ingresos</th><th class="numero">Egresos</th><th class="numero">Declarado</th><th class="numero">Diferencia</th></tr></thead>
            <tbody>
                <?php if (empty($reportesCajas)): ?><tr><td class="vacio" colspan="9">No hay aperturas de caja en este período.</td></tr><?php endif; ?>
                <?php foreach ($reportesCajas as $reporteCaja): ?>
                    <tr>
                        <td><?php echo $escapar($reporteCaja['caja_nombre']); ?></td>
                        <td><?php echo $escapar($reporteCaja['cajero']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($reporteCaja['fecha_apertura'])); ?></td>
                        <td><?php echo $reporteCaja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($reporteCaja['fecha_cierre'])) : 'Abierta'; ?></td>
                        <td class="numero"><?php echo formatearMoneda($reporteCaja['fondo_inicial']); ?></td>
                        <td class="numero"><?php echo formatearMoneda($reporteCaja['total_ingresos']); ?></td>
                        <td class="numero"><?php echo formatearMoneda($reporteCaja['total_egresos']); ?></td>
                        <td class="numero"><?php echo $reporteCaja['monto_cierre'] !== null ? formatearMoneda($reporteCaja['monto_cierre']) : '-'; ?></td>
                        <td class="numero"><?php echo $reporteCaja['diferencia'] !== null ? formatearMoneda($reporteCaja['diferencia']) : '-'; ?></td>
                    </tr>
                    <?php foreach ($reporteCaja['movimientos'] as $movimiento): ?>
                        <tr>
                            <td></td>
                            <td colspan="3">Comprobante: <?php echo $escapar($movimiento['tipo'] === 'egreso' ? 'Egreso' : 'Ingreso'); ?> - <?php echo $escapar($movimiento['concepto'] ?: 'Sin concepto'); ?></td>
                            <td colspan="3"><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])); ?> · <?php echo $escapar($movimiento['usuario_nombre']); ?></td>
                            <td colspan="2" class="numero"><?php echo formatearMoneda($movimiento['monto']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <footer class="pie">Generado el <?php echo date('d/m/Y H:i'); ?></footer>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
