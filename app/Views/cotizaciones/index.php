<?php
$tituloPagina = 'Cotizaciones';
require APP_PATH . 'Views/layouts/pos_header.php';
$esVendedor = in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['vendedor', 'cajero_movil'], true);
$esAdmin = (int)($_SESSION['rol_id'] ?? 0) === 1 || in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['admin', 'administrador'], true);
$puedeCrear = $esVendedor || $esAdmin;
$puedeFacturar = !$esVendedor;
$etiquetasEstado = ['pendiente' => 'Pendiente', 'facturada' => 'Facturada', 'cancelada' => 'Cancelada'];
?>
<link rel="stylesheet" href="<?php echo URL_BASE; ?>css/cotizaciones.css?v=<?php echo filemtime(PUBLIC_PATH . 'css/cotizaciones.css'); ?>">
<section class="catalogo-encabezado cotizaciones-encabezado">
    <div>
        <span class="eyebrow"><?php echo $esVendedor ? 'Ventas' : 'Caja / Ventas'; ?></span>
        <h1>Cotizaciones</h1>
        <p><?php echo $esVendedor ? 'Crea y administra cotizaciones para tus clientes.' : 'Cotizaciones listas para facturar como recibo o factura fiscal.'; ?></p>
    </div>
    <?php if ($puedeCrear): ?>
        <a class="btn btn-exito" href="<?php echo URL_BASE; ?>cotizaciones/crear"><i class="fa-solid fa-plus"></i> Nueva cotización</a>
    <?php endif; ?>
    <?php if ($esVendedor): ?>
        <a class="btn btn-secondary" href="<?php echo URL_BASE; ?>movil"><i class="fa-solid fa-mobile-screen"></i> Vista móvil</a>
    <?php endif; ?>
</section>

<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<section class="tabla-responsive cotizaciones-tabla-wrap">
    <div class="cotizaciones-filtros">
        <a class="cotizacion-filtro <?php echo $seleccion === 'todas' ? 'is-active' : ''; ?>" href="?estado=todas">Todas <span class="filtro-conteo"><?php echo array_sum($conteos); ?></span></a>
        <a class="cotizacion-filtro <?php echo $seleccion === 'pendiente' ? 'is-active' : ''; ?>" href="?estado=pendiente">Pendientes <span class="filtro-conteo"><?php echo $conteos['pendiente']; ?></span></a>
        <a class="cotizacion-filtro <?php echo $seleccion === 'facturada' ? 'is-active' : ''; ?>" href="?estado=facturada">Facturadas <span class="filtro-conteo"><?php echo $conteos['facturada']; ?></span></a>
        <a class="cotizacion-filtro <?php echo $seleccion === 'cancelada' ? 'is-active' : ''; ?>" href="?estado=cancelada">Canceladas <span class="filtro-conteo"><?php echo $conteos['cancelada']; ?></span></a>
    </div>

    <?php if (count($cotizaciones) === 0): ?>
        <div class="cotizaciones-vacio">
            <div class="cotizaciones-vacio-icono"><i class="fa-solid fa-file-lines"></i></div>
            <h3><?php echo $seleccion === 'pendiente' ? 'No hay cotizaciones pendientes' : 'No hay cotizaciones en esta vista'; ?></h3>
            <p>
                <?php echo $puedeCrear ? 'Crea una nueva cotización para comenzar.' : 'Cuando un vendedor registre cotizaciones aparecerán aquí en tiempo real.'; ?>
            </p>
            <?php if ($puedeCrear): ?>
                <a class="btn btn-exito" href="<?php echo URL_BASE; ?>cotizaciones/crear">+ Nueva cotización</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <table class="tabla-catalogo cotizaciones-tabla">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Vendedor</th>
                <th>Cliente</th>
                <th>RTN</th>
                <th class="text-right">Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cotizaciones as $cotizacion): ?>
                <?php $etiquetaEstado = $etiquetasEstado[$cotizacion['estado']] ?? $cotizacion['estado']; ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($cotizacion['folio']); ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($cotizacion['creada_en'])); ?></td>
                    <td><?php echo htmlspecialchars($cotizacion['vendedor'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($cotizacion['cliente_nombre'] ?: 'Consumidor Final'); ?></td>
                    <td><?php echo htmlspecialchars($cotizacion['cliente_rtn'] ?: '—'); ?></td>
                    <td class="text-right"><?php echo formatearMoneda($cotizacion['total']); ?></td>
                    <td><span class="estado-cotizacion estado-<?php echo htmlspecialchars($cotizacion['estado']); ?>"><?php echo $etiquetaEstado; ?></span></td>
                    <td class="cotizaciones-acciones">
                        <a class="btn btn-secondary btn-sm" title="Ver detalle" href="<?php echo URL_BASE; ?>cotizaciones/ver/<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-eye"></i></a>
                        <a class="btn btn-secondary btn-sm" title="Imprimir" target="_blank" href="<?php echo URL_BASE; ?>cotizaciones/imprimir/<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-print"></i></a>
                        <?php if ($cotizacion['estado'] === 'pendiente'): ?>
                            <?php if ($puedeFacturar): ?>
                                <a class="btn btn-exito btn-sm" title="Facturar en caja" href="<?php echo URL_BASE; ?>ventas?cotizar=<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-cash-register"></i></a>
                            <?php endif; ?>
                            <?php if ($puedeCrear): ?>
                                <a class="btn btn-secondary btn-sm" title="Editar" href="<?php echo URL_BASE; ?>cotizaciones/editar/<?php echo (int)$cotizacion['id']; ?>"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="<?php echo URL_BASE; ?>cotizaciones/cancelar/<?php echo (int)$cotizacion['id']; ?>" style="display:inline;" onsubmit="return confirm('¿Cancelar esta cotización?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Cancelar"><i class="fa-solid fa-ban"></i></button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>