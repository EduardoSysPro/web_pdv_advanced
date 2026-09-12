<?php $tituloPagina = 'Categorías'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Productos</span>
        <h1>Categorías</h1>
        <p>Organiza tu inventario por familias, tipos o líneas de venta.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>productos">Volver a productos</a>
        <a class="btn-pos btn-success" href="<?php echo URL_BASE; ?>categorias/crear">+ Nueva categoría</a>
    </div>
</section>
<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<section class="tarjeta usuarios-lista">
    <h2 class="tarjeta-titulo">Listado de categorías</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Productos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="4" class="tabla-vacia">No hay categorías registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($categoria['descripcion'] ?? 'Sin descripción'); ?></td>
                            <td><?php echo (int)($categoria['total_productos'] ?? 0); ?></td>
                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>categorias/editar/<?php echo (int)$categoria['id']; ?>">Editar</a>
                                    <form method="POST" action="<?php echo URL_BASE; ?>categorias/eliminar/<?php echo (int)$categoria['id']; ?>" onsubmit="return confirm('¿Deseas eliminar esta categoría?');" style="display:inline;"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <button type="submit" class="btn-pos btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
