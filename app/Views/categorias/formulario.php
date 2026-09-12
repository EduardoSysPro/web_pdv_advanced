<?php $tituloPagina = $titulo; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Productos</span>
        <h1><?php echo htmlspecialchars($titulo); ?></h1>
        <p>Define el nombre y la descripción de la categoría.</p>
    </div>
    <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>categorias">Volver a categorías</a>
</section>
<section class="tarjeta formulario-producto">
    <?php foreach (($errores ?? []) as $error): ?>
        <div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="<?php echo $accion; ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="campo campo-ancho">
            <label for="categoria-nombre">Nombre</label>
            <input id="categoria-nombre" name="nombre" value="<?php echo htmlspecialchars($categoria['nombre'] ?? ''); ?>" maxlength="100" required>
        </div>

        <div class="campo campo-ancho">
            <label for="categoria-descripcion">Descripción</label>
            <textarea id="categoria-descripcion" name="descripcion" rows="4" maxlength="255"><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></textarea>
        </div>

        <div class="form-acciones" style="grid-column: 1 / -1;">
            <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>categorias">Cancelar</a>
            <button class="btn-pos btn-success" type="submit">Guardar categoría</button>
        </div>
    </form>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
