<?php $tituloPagina = 'Productos'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">F3 / Catálogo</span>
        <h1>Productos</h1>
        <p>Administra precios, existencias y categorías.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn-pos btn-success" href="<?php echo URL_BASE; ?>productos/crear">+ Nuevo Producto</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>productos/exportar-csv?busqueda=<?php echo urlencode($busqueda); ?>&categoria_id=<?php echo urlencode((string)$categoriaId); ?>">⬇ Exportar CSV (etiquetas)</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>categorias">Gestionar categorías</a>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
<section class="tarjeta catalogo-panel">
    <form class="filtros-productos" method="GET" action="<?php echo URL_BASE; ?>productos">
        <input type="search" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar por nombre o código..." autofocus>
        <select name="categoria_id">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo (int)$categoria['id']; ?>" <?php echo (string)$categoriaId === (string)$categoria['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn-pos btn-primary" type="submit">Buscar</button>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>productos">Limpiar</a>
    </form>
    <div class="tabla-responsive">
        <table class="table-pos tabla-catalogo">
            <thead><tr><th>Código</th><th>Descripción</th><th>Categoría</th><th>Precio Costo</th><th>Precio Venta</th><th>Ganancia %</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if (!$resultado['productos']): ?><tr><td colspan="8" class="tabla-vacia">No hay productos que coincidan con la búsqueda.</td></tr><?php endif; ?>
            <?php foreach ($resultado['productos'] as $producto): ?>
                <?php
                    $ganancia = (float)$producto['precio_costo'] > 0 ? (((float)$producto['precio_venta'] - (float)$producto['precio_costo']) / (float)$producto['precio_costo']) * 100 : 0;
                    $tieneEmpaque = in_array($producto['tipo_venta'] ?? '', ['solo_empaque', 'ambos'], true) || ((float)($producto['precio_empaque'] ?? 0) > 0);
                    $nomEmp = !empty($producto['nombre_empaque']) ? $producto['nombre_empaque'] : 'Caja';
                    $cantEmp = rtrim(rtrim(number_format((float)($producto['unidades_por_empaque'] ?? 1), 2, '.', ''), '0'), '.');
                ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($producto['codigo_barras']); ?>
                        <?php if (!empty($producto['codigo_barras_empaque'])): ?>
                            <br><small style="color: #64748b; font-size: 10px;">📦 <?php echo htmlspecialchars($producto['codigo_barras_empaque']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                        <?php if ($tieneEmpaque): ?>
                            <div style="margin-top: 3px;">
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 11px; padding: 2px 6px; border-radius: 4px;">
                                    📦 <?php echo htmlspecialchars($nomEmp); ?> x<?php echo htmlspecialchars((string)$cantEmp); ?>: <?php echo formatearMoneda($producto['precio_empaque']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                    <td><?php echo formatearMoneda($producto['precio_costo']); ?></td>
                    <td>
                        <?php echo formatearMoneda($producto['precio_venta']); ?>
                        <?php if (($producto['tipo_venta'] ?? '') === 'solo_empaque'): ?>
                            <br><small style="color:#dc2626; font-size:10px;">(Solo empaque)</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($ganancia, 2); ?>%</td>
                    <td>
                        <span class="stock <?php echo (int)$producto['stock'] <= (int)$producto['stock_minimo'] ? 'stock-bajo' : ''; ?>"><?php echo (int)$producto['stock']; ?></span>
                        <?php if ($tieneEmpaque && (float)($producto['unidades_por_empaque'] ?? 1) > 0): ?>
                            <div style="font-size: 10px; color: #64748b;">
                                (~<?php echo floor((float)$producto['stock'] / (float)$producto['unidades_por_empaque']); ?> <?php echo strtolower(htmlspecialchars($nomEmp)); ?>s)
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="acciones"><a class="btn-pos btn-primary btn-pequeno" href="<?php echo URL_BASE; ?>productos/editar/<?php echo (int)$producto['id']; ?>">Editar</a><form method="POST" action="<?php echo URL_BASE; ?>productos/eliminar/<?php echo (int)$producto['id']; ?>" onsubmit="return confirm('¿Eliminar este producto?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><button class="btn-pos btn-danger btn-pequeno" type="submit">Eliminar</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
        $paginas = max(1, (int)ceil($resultado['total'] / $resultado['porPagina']));
        $paginaActual = (int)$resultado['pagina'];
        $rango = 2;
        $inicio = max(1, $paginaActual - $rango);
        $fin = min($paginas, $paginaActual + $rango);
        $urlBase = '?busqueda=' . urlencode($busqueda) . '&categoria_id=' . urlencode((string)$categoriaId) . '&pagina=';
    ?>
    <?php if ($paginas > 1): ?>
    <div class="paginacion-info">Página <?php echo $paginaActual; ?> de <?php echo $paginas; ?> (<?php echo number_format((int)$resultado['total']); ?> productos)</div>
    <nav class="paginacion">
        <?php if ($paginaActual > 1): ?><a class="pag-flecha" href="<?php echo $urlBase . ($paginaActual - 1); ?>" aria-label="Anterior">‹</a><?php endif; ?>
        <?php if ($inicio > 1): ?>
            <a href="<?php echo $urlBase . '1'; ?>">1</a>
            <?php if ($inicio > 2): ?><span class="pag-puntos">…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a class="<?php echo $i === $paginaActual ? 'activa' : ''; ?>" href="<?php echo $urlBase . $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($fin < $paginas): ?>
            <?php if ($fin < $paginas - 1): ?><span class="pag-puntos">…</span><?php endif; ?>
            <a href="<?php echo $urlBase . $paginas; ?>"><?php echo $paginas; ?></a>
        <?php endif; ?>
        <?php if ($paginaActual < $paginas): ?><a class="pag-flecha" href="<?php echo $urlBase . ($paginaActual + 1); ?>" aria-label="Siguiente">›</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
