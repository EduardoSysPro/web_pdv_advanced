<?php $tituloPagina = 'Inventario'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado"><div><span class="eyebrow">F4 / Operaciones</span><h1>Inventario</h1><p>Controla las existencias y registra cada ajuste.</p></div><div style="display:flex; gap:10px;"><a class="btn-pos btn-primary" href="<?php echo URL_BASE; ?>compras">Ingresar factura de compra</a><a class="btn-pos btn-primary" href="<?php echo URL_BASE; ?>productos">Gestionar productos</a></div></section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="pestanas-inventario"><a class="activa" href="#ajuste-stock">Ajuste de Stock</a><a href="#bajo-stock">Alertas de Stock Bajo <strong><?php echo count($bajoStock); ?></strong></a></div>
<section class="tarjeta inventario-seccion" id="ajuste-stock">
    <h2 class="tarjeta-titulo">Ajuste rápido</h2>
    <form class="busqueda-inventario" method="GET" action="<?php echo URL_BASE; ?>inventario">
        <input class="form-control-pos" type="search" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Código de barras o nombre del producto" autofocus>
        <button class="btn-pos-primary" type="submit">Buscar</button>
    </form>
    <?php if ($busqueda !== ''): ?><div class="resultados-inventario"><p>Selecciona un producto para ajustar:</p><?php foreach ($productos as $producto): ?><a href="<?php echo URL_BASE; ?>inventario?producto_id=<?php echo (int)$producto['id']; ?>#ajuste-stock"><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><span><?php echo htmlspecialchars($producto['codigo_barras']); ?> | Stock: <?php echo (int)$producto['stock']; ?></span></a><?php endforeach; ?><?php if (!$productos): ?><p class="tabla-vacia">No se encontraron productos.</p><?php endif; ?></div><?php endif; ?>
    <form class="ajuste-formulario" method="POST" action="<?php echo URL_BASE; ?>inventario/ajustar">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="campo campo-ancho"><label for="producto_id">Producto seleccionado</label><input id="producto_id" name="producto_id" type="hidden" value="<?php echo $productoSeleccionado ?: ''; ?>" required><div class="producto-elegido"><?php if ($productoElegido): ?><strong><?php echo htmlspecialchars($productoElegido['nombre']); ?></strong><span><?php echo htmlspecialchars($productoElegido['codigo_barras']); ?> | Stock actual: <b><?php echo (int)$productoElegido['stock']; ?></b></span><?php else: ?><span>Busca y selecciona un producto arriba.</span><?php endif; ?></div></div>
        <div class="campo"><label for="tipo_movimiento">Tipo de movimiento</label><select class="form-control-pos" id="tipo_movimiento" name="tipo_movimiento"><option value="entrada">Entrada</option><option value="salida">Salida</option></select></div>
        <div class="campo"><label for="cantidad">Cantidad</label><input class="form-control-pos" id="cantidad" name="cantidad" type="number" min="1" step="1" required></div>
        <div class="campo campo-ancho"><label for="motivo">Motivo</label><select class="form-control-pos" id="motivo" name="motivo" required><option value="">Selecciona un motivo</option><option>Compra a proveedor</option><option>Mercadería dañada</option><option>Ajuste por conteo físico</option><option>Devolución</option><option>Otro</option></select></div>
        <div class="form-acciones"><button class="btn-pos-success" type="submit">Registrar ajuste</button></div>
    </form>
</section>
<section class="tarjeta inventario-seccion" id="bajo-stock">
    <h2 class="tarjeta-titulo">Alertas de stock bajo</h2>
    <div class="tabla-responsive"><table class="tabla-catalogo"><thead><tr><th>Código</th><th>Producto</th><th>Stock actual</th><th>Stock mínimo</th><th>Diferencia</th><th>Acción</th></tr></thead><tbody><?php if (!$bajoStock): ?><tr><td colspan="6" class="tabla-vacia">No hay productos con stock bajo.</td></tr><?php endif; ?><?php foreach ($bajoStock as $producto): ?><tr class="<?php echo (int)$producto['stock'] === 0 ? 'fila-critica' : 'fila-alerta'; ?>"><td><?php echo htmlspecialchars($producto['codigo_barras']); ?></td><td><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></td><td><?php echo (int)$producto['stock']; ?></td><td><?php echo (int)$producto['stock_minimo']; ?></td><td><?php echo (int)$producto['diferencia']; ?></td><td><a class="btn-pos btn-success btn-pequeno" href="<?php echo URL_BASE; ?>inventario?producto_id=<?php echo (int)$producto['id']; ?>#ajuste-stock">Reabastecer</a></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
