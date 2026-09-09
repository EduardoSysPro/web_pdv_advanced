<?php 
$inicio = substr($fechaInicio, 0, 10); 
$fin = substr($fechaFin, 0, 10); 
$periodoActual = $_GET['periodo'] ?? 'hoy';
$tituloPagina = 'Reportes y Estadísticas'; 
require APP_PATH . 'Views/layouts/pos_header.php'; 
?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Análisis / Negocio</span>
        <h1>Reportes y estadísticas</h1>
        <p>Ventas, rotación y rentabilidad del período seleccionado.</p>
    </div>
    <div class="reporte-acciones">
        <a class="btn-pos btn-pos-secondary" target="_blank" rel="noopener" href="<?php echo URL_BASE; ?>reportes/imprimir?periodo=<?php echo urlencode($periodoActual); ?>&fecha_inicio=<?php echo urlencode($inicio); ?>&fecha_fin=<?php echo urlencode($fin); ?>">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a class="btn-pos btn-pos-primary" href="<?php echo URL_BASE; ?>reportes/exportar?periodo=<?php echo urlencode($periodoActual); ?>&fecha_inicio=<?php echo urlencode($inicio); ?>&fecha_fin=<?php echo urlencode($fin); ?>">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
    </div>
</section>

<!-- Filtros de Reporte -->
<section class="tarjeta filtros-reporte">
    <form method="GET" action="<?php echo URL_BASE; ?>reportes" id="formFiltros">
        <div class="grupo-filtro">
            <label for="periodo">Rango de tiempo</label>
            <select name="periodo" id="periodo">
                <option value="hoy">Hoy</option>
                <option value="semana">Esta semana</option>
                <option value="mes">Este mes</option>
                <option value="personalizado">Personalizado</option>
            </select>
        </div>
        
        <div class="grupo-filtro">
            <label for="fecha_inicio">Desde</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($inicio); ?>">
        </div>

        <div class="grupo-filtro">
            <label for="fecha_fin">Hasta</label>
            <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fin); ?>">
        </div>

        <button type="submit" class="btn btn-primario">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</section>

<!-- KPIs Principales -->
<section class="kpis-reportes">
    <div>
        <span>Total de ventas</span>
        <strong><?php echo formatearMoneda($resumen['total_ventas']); ?></strong>
    </div>
    <div>
        <span>Costo de lo vendido</span>
        <strong><?php echo formatearMoneda($resumen['total_costo']); ?></strong>
    </div>
    <div class="kpi-ganancia">
        <span>Ganancia neta estimada</span>
        <strong><?php echo formatearMoneda($resumen['ganancia_neta']); ?></strong>
    </div>
    <div>
        <span>Transacciones</span>
        <strong><?php echo (int)$resumen['transacciones']; ?></strong>
        <small>Ticket promedio: <?php echo formatearMoneda($resumen['promedio_ticket']); ?></small>
    </div>
</section>

<!-- Métodos de Pago y Gráficos -->
<div class="grid-reportes-dos-columnas">
    <section class="tarjeta">
        <h2 class="tarjeta-titulo">Ventas por método de pago</h2>
        <div class="metodos-reporte">
            <?php foreach ($metodosPago as $metodo => $total): ?>
                <div>
                    <span><?php echo htmlspecialchars(ucfirst($metodo)); ?></span>
                    <strong><?php echo formatearMoneda($total); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tarjeta">
        <h2 class="tarjeta-titulo">Top 10 productos más vendidos</h2>
        <div class="tabla-responsive">
            <table class="tabla-catalogo">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Unidades</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$topProductos): ?>
                        <tr><td colspan="4" class="tabla-vacia">No hay productos vendidos en este período.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topProductos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['codigo_barras']); ?></td>
                            <td><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></td>
                            <td><?php echo (int)$producto['unidades_vendidas']; ?></td>
                            <td><?php echo formatearMoneda($producto['ingreso_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Historial de Ventas -->
<section class="tarjeta">
    <h2 class="tarjeta-titulo">Historial de ventas</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo" id="tablaHistorial">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Método</th>
                    <th>Total</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$ventas): ?>
                    <tr><td colspan="7" class="tabla-vacia">No hay ventas en este período.</td></tr>
                <?php endif; ?>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($venta['folio']); ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                        <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($venta['vendedor']); ?></td>
                        <td>
                            <span class="badge-metodo badge-<?php echo strtolower($venta['metodo_pago']); ?>">
                                <?php echo htmlspecialchars(ucfirst($venta['metodo_pago'])); ?>
                            </span>
                        </td>
                        <td><?php echo formatearMoneda($venta['total']); ?></td>
                        <td>
                            <a class="btn btn-pequeno btn-primario" target="_blank" href="<?php echo URL_BASE; ?>ventas/ticket/<?php echo (int)$venta['id']; ?>">
                                Ver ticket
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="tarjeta">
    <h2 class="tarjeta-titulo">Reportes de Cajas</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo tabla-reportes-caja">
            <thead>
                <tr>
                    <th>Caja</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Fondo inicial</th>
                    <th>Ingresos</th>
                    <th>Egresos</th>
                    <th>Declarado</th>
                    <th>Diferencia</th>
                    <th>Comprobantes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportesCajas)): ?>
                    <tr><td colspan="10" class="tabla-vacia">No hay aperturas de caja en este período.</td></tr>
                <?php endif; ?>
                <?php foreach ($reportesCajas as $reporteCaja): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($reporteCaja['caja_nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($reporteCaja['cajero']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($reporteCaja['fecha_apertura'])); ?></td>
                        <td><?php echo $reporteCaja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($reporteCaja['fecha_cierre'])) : '<span class="estado-caja-abierta">Abierta</span>'; ?></td>
                        <td><?php echo formatearMoneda($reporteCaja['fondo_inicial']); ?></td>
                        <td class="monto-ingreso"><?php echo formatearMoneda($reporteCaja['total_ingresos']); ?></td>
                        <td class="monto-egreso"><?php echo formatearMoneda($reporteCaja['total_egresos']); ?></td>
                        <td><?php echo $reporteCaja['monto_cierre'] !== null ? formatearMoneda($reporteCaja['monto_cierre']) : '-'; ?></td>
                        <td class="<?php echo (float)$reporteCaja['diferencia'] < 0 ? 'monto-egreso' : ((float)$reporteCaja['diferencia'] > 0 ? 'monto-ingreso' : ''); ?>">
                            <?php echo $reporteCaja['diferencia'] !== null ? formatearMoneda($reporteCaja['diferencia']) : '-'; ?>
                        </td>
                        <td><?php echo count($reporteCaja['movimientos']); ?></td>
                    </tr>
                    <tr class="fila-detalle-caja">
                        <td colspan="10">
                            <details>
                                <summary>Ver comprobantes de ingresos y egresos</summary>
                                <div class="tabla-responsive comprobantes-caja">
                                    <table class="tabla-catalogo">
                                        <thead>
                                            <tr>
                                                <th>Fecha / Hora</th>
                                                <th>Tipo</th>
                                                <th>Concepto</th>
                                                <th>Registrado por</th>
                                                <th>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($reporteCaja['movimientos'])): ?>
                                                <tr><td colspan="5" class="tabla-vacia">No hay comprobantes de ingreso o egreso para esta apertura.</td></tr>
                                            <?php endif; ?>
                                            <?php foreach ($reporteCaja['movimientos'] as $movimiento): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])); ?></td>
                                                    <td><?php echo htmlspecialchars($movimiento['tipo'] === 'egreso' ? 'Egreso' : 'Ingreso'); ?></td>
                                                    <td><?php echo htmlspecialchars($movimiento['concepto'] ?: 'Sin concepto'); ?></td>
                                                    <td><?php echo htmlspecialchars($movimiento['usuario_nombre']); ?></td>
                                                    <td class="<?php echo $movimiento['tipo'] === 'egreso' ? 'monto-egreso' : 'monto-ingreso'; ?>"><?php echo formatearMoneda($movimiento['monto']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectPeriodo = document.getElementById('periodo');
    const inputInicio = document.getElementById('fecha_inicio');
    const inputFin = document.getElementById('fecha_fin');

    selectPeriodo.value = <?php echo json_encode($periodoActual); ?>;

    function toggleFechas() {
        const esPersonalizado = selectPeriodo.value === 'personalizado';
        inputInicio.disabled = !esPersonalizado;
        inputFin.disabled = !esPersonalizado;
    }

    selectPeriodo.addEventListener('change', function() {
        toggleFechas();
        if (selectPeriodo.value !== 'personalizado') {
            document.getElementById('formFiltros').submit();
        }
    });

    toggleFechas();
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>