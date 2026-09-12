<?php $tituloPagina = 'Proveedores'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div><span class="eyebrow">Compras / Proveedores</span><h1>Proveedores</h1><p>Maestro de proveedores con su condición tributaria y saldo pendiente de cuentas por pagar.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras/pagos">Cuentas por pagar</a>
        <a class="btn-pos-primary" href="<?php echo URL_BASE; ?>proveedores/crear">+ Nuevo proveedor</a>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

<section class="tarjeta">
    <form method="GET" action="<?php echo URL_BASE; ?>proveedores" class="busqueda-inventario" style="margin-bottom:12px;">
        <input class="form-control-pos" type="search" name="busqueda" placeholder="Buscar por nombre, RTN o contacto" value="<?php echo htmlspecialchars($busqueda); ?>">
        <button class="btn-pos-primary" type="submit">Buscar</button>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>proveedores">Limpiar</a>
    </form>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Nombre</th><th>RTN</th><th>Condición</th><th>Contacto</th><th>Teléfono</th><th>Saldo pendiente</th><th style="width:150px;">Acciones</th></tr></thead>
            <tbody>
                <?php if (!$proveedores): ?><tr><td colspan="7" class="tabla-vacia">No hay proveedores registrados.</td></tr><?php endif; ?>
                <?php foreach ($proveedores as $prov): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($prov['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($prov['rtn'] ?? ''); ?></td>
                        <td><span class="badge" style="background:<?php echo $prov['tipo'] === 'contribuyente' ? '#dcfce7;color:#166534;' : '#fef3c7;color:#92400e;'; ?>"><?php echo $prov['tipo'] === 'contribuyente' ? 'Contribuyente' : 'No contribuyente'; ?></span></td>
                        <td><?php echo htmlspecialchars($prov['contacto'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($prov['telefono'] ?? '—'); ?></td>
                        <td><?php echo (float)$prov['saldo_pendiente'] > 0 ? '<strong style="color:#b45309;">' . formatearMoneda($prov['saldo_pendiente']) . '</strong>' : '—'; ?></td>
                        <td style="white-space:nowrap;">
                            <a class="btn-pos btn-secondary btn-pequeno" href="<?php echo URL_BASE; ?>proveedores/editar/<?php echo (int)$prov['id']; ?>">Editar</a>
                            <form method="POST" action="<?php echo URL_BASE; ?>proveedores/eliminar/<?php echo (int)$prov['id']; ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar este proveedor? Se conservarán sus compras registradas.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <button class="btn-pos btn-danger btn-pequeno" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>