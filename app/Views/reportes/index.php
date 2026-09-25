<?php
$inicio = substr($fechaInicio, 0, 10);
$fin = substr($fechaFin, 0, 10);
$periodoActual = $periodo ?? ($_GET['periodo'] ?? 'hoy');
$tituloPagina = 'Reportes y Estadísticas';
require APP_PATH . 'Views/layouts/pos_header.php';
$linkExtra = '&vendedor_id=' . (int)($vendedorId ?? 0) . '&caja_id=' . (int)($cajaId ?? 0) . '&categoria_id=' . (int)($categoriaId ?? 0);
?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Análisis / Negocio</span>
        <h1>Reportes y estadísticas</h1>
        <p>Ventas, rotación y rentabilidad del período seleccionado.</p>
    </div>
    <div class="reporte-acciones">
        <a class="btn-pos btn-pos-secondary" target="_blank" rel="noopener" href="<?php echo URL_BASE; ?>reportes/imprimir?periodo=<?php echo urlencode($periodoActual); ?>&fecha_inicio=<?php echo urlencode($inicio); ?>&fecha_fin=<?php echo urlencode($fin) . $linkExtra; ?>">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a class="btn-pos btn-pos-primary" href="<?php echo URL_BASE; ?>reportes/exportar?periodo=<?php echo urlencode($periodoActual); ?>&fecha_inicio=<?php echo urlencode($inicio); ?>&fecha_fin=<?php echo urlencode($fin) . $linkExtra; ?>">
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
                <?php $periodos = ['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana', 'ultimos7' => 'Últimos 7 días', 'mes' => 'Este mes', 'ultimos30' => 'Últimos 30 días', 'mes_anterior' => 'Mes anterior', 'personalizado' => 'Personalizado']; ?>
                <?php foreach ($periodos as $valor => $etiqueta): ?>
                <option value="<?php echo $valor; ?>" <?php echo $periodoActual === $valor ? 'selected' : ''; ?>><?php echo $etiqueta; ?></option>
                <?php endforeach; ?>
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

        <div class="grupo-filtro">
            <label for="vendedor_id">Vendedor</label>
            <select name="vendedor_id" id="vendedor_id">
                <option value="0">Todos</option>
                <?php foreach (($vendedores ?? []) as $v): ?>
                <option value="<?php echo (int)$v['id']; ?>" <?php echo (int)($vendedorId ?? 0) === (int)$v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['nombre'] ?? $v['usuario']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupo-filtro">
            <label for="caja_id">Caja</label>
            <select name="caja_id" id="caja_id">
                <option value="0">Todas</option>
                <?php foreach (($cajas ?? []) as $cj): ?>
                <option value="<?php echo (int)$cj['id']; ?>" <?php echo (int)($cajaId ?? 0) === (int)$cj['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cj['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupo-filtro">
            <label for="categoria_id">Categoría (top)</label>
            <select name="categoria_id" id="categoria_id">
                <option value="0">Todas</option>
                <?php foreach (($categorias ?? []) as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)($categoriaId ?? 0) === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primario">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</section>

<!-- KPIs Principales -->
<?php
function rep_delta($valor) {
    $valor = (float)$valor;
    $clase = $valor > 0 ? 'kpi-delta-sub' : ($valor < 0 ? 'kpi-delta-baj' : 'kpi-delta-igu');
    $flecha = $valor > 0 ? '▲' : ($valor < 0 ? '▼' : '＝');
    return '<span class="kpi-delta ' . $clase . '">' . $flecha . ' ' . number_format(abs($valor), 1) . '% vs ant.</span>';
}
?>
<section class="kpis-reportes">
    <div>
        <span>Total de ventas</span>
        <strong><?php echo formatearMoneda($resumen['total_ventas']); ?></strong>
        <?php echo rep_delta($variacion['ventas'] ?? 0); ?>
    </div>
    <div class="kpi-ganancia">
        <span>Ganancia neta estimada</span>
        <strong><?php echo formatearMoneda($resumen['ganancia_neta']); ?></strong>
        <?php echo rep_delta($variacion['ganancia'] ?? 0); ?>
    </div>
    <div>
        <span>Transacciones</span>
        <strong><?php echo (int)$resumen['transacciones']; ?></strong>
        <?php echo rep_delta($variacion['tickets'] ?? 0); ?>
    </div>
    <div>
        <span>Ticket promedio</span>
        <strong><?php echo formatearMoneda($resumen['promedio_ticket']); ?></strong>
        <?php echo rep_delta($variacion['ticket_prom'] ?? 0); ?>
        <small>Costo de lo vendido: <?php echo formatearMoneda($resumen['total_costo']); ?></small>
    </div>
</section>

<!-- Gráficas (SVG local, sin dependencias) -->
<div class="grid-reportes-dos-columnas">
    <section class="tarjeta">
        <h2 class="tarjeta-titulo">Ventas por día</h2>
        <?php $porDia = $ventasPorDia ?? []; $maxDia = 0; foreach ($porDia as $d) { $maxDia = max($maxDia, (float)$d['total']); } ?>
        <?php if (!$porDia): ?>
            <p class="tabla-vacia">Sin ventas en este período.</p>
        <?php else: ?>
        <svg viewBox="0 0 560 <?php echo 40 + count($porDia) * 26; ?>" style="width:100%;height:auto;" role="img" aria-label="Ventas por día">
            <?php $y = 8; foreach ($porDia as $d): $ancho = $maxDia > 0 ? max(2, round((float)$d['total'] / $maxDia * 340)) : 2; ?>
            <text x="0" y="<?php echo $y + 13; ?>" font-size="11" fill="#64748b"><?php echo htmlspecialchars(date('d/m', strtotime($d['dia']))); ?></text>
            <rect x="52" y="<?php echo $y; ?>" width="<?php echo $ancho; ?>" height="17" rx="4" fill="#2563eb"/>
            <text x="<?php echo 58 + $ancho; ?>" y="<?php echo $y + 13; ?>" font-size="11" fill="#0f172a"><?php echo number_format((float)$d['total'], 0); ?></text>
            <?php $y += 26; endforeach; ?>
        </svg>
        <?php endif; ?>
    </section>

    <section class="tarjeta">
        <h2 class="tarjeta-titulo">Ventas por método de pago</h2>
        <?php $totalMet = array_sum($metodosPago); $coloresMet = ['efectivo' => '#10b981', 'tarjeta' => '#2563eb', 'transferencia' => '#8b5cf6', 'credito' => '#f59e0b', 'mixto' => '#64748b']; $offset = 0; ?>
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
        <svg viewBox="0 0 120 120" style="width:130px;height:130px;" role="img" aria-label="Métodos de pago">
            <circle cx="60" cy="60" r="45" fill="none" stroke="#e2e8f0" stroke-width="18"/>
            <?php foreach ($metodosPago as $metodo => $monto): ?>
            <?php if ($totalMet > 0 && (float)$monto > 0): $frac = (float)$monto / $totalMet; ?>
            <circle cx="60" cy="60" r="45" fill="none" stroke="<?php echo $coloresMet[$metodo] ?? '#0ea5e9'; ?>" stroke-width="18" stroke-dasharray="<?php echo round($frac * 283, 1); ?> 283" stroke-dashoffset="<?php echo round(-$offset * 283 / 100, 1); ?>" transform="rotate(-90 60 60)"/>
            <?php $offset += $frac * 100; endif; ?>
            <?php endforeach; ?>
        </svg>
        <div class="metodos-reporte" style="flex:1;min-width:180px;">
            <?php foreach ($metodosPago as $metodo => $monto): ?>
                <div>
                    <span><i style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $coloresMet[$metodo] ?? '#0ea5e9'; ?>;"></i> <?php echo htmlspecialchars(ucfirst($metodo)); ?> (<?php echo $totalMet > 0 ? round((float)$monto / $totalMet * 100) : 0; ?>%)</span>
                    <strong><?php echo formatearMoneda($monto); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </section>
</div>

<!-- Top productos -->
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

<!-- Historial de Ventas -->
<section class="tarjeta">
    <h2 class="tarjeta-titulo">Historial de ventas</h2>
    <?php if (count($ventas) >= 500): ?><p class="alerta alerta-info">Mostrando las 500 más recientes. Usa Exportar para el listado completo o acota el rango de fechas.</p><?php endif; ?>
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
                            <span class="badge-metodo badge-<?php echo htmlspecialchars(strtolower((string)$venta['metodo_pago'])); ?>">
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