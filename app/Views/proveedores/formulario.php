<?php
$esEdicion = !empty($proveedor['id']);
$accion = $esEdicion
    ? URL_BASE . 'proveedores/actualizar/' . (int)$proveedor['id']
    : URL_BASE . 'proveedores/guardar';
$tituloPagina = $esEdicion ? 'Editar proveedor' : 'Nuevo proveedor';
require APP_PATH . 'Views/layouts/pos_header.php';
?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Compras / Proveedores</span>
        <h1><?php echo $esEdicion ? 'Editar proveedor' : 'Nuevo proveedor'; ?></h1>
        <p>Registra al proveedor y define si es contribuyente (con crédito fiscal) o no contribuyente.</p>
    </div>
    <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>proveedores">Volver a proveedores</a>
</section>

<section class="tarjeta formulario-producto">
    <form method="POST" action="<?php echo $accion; ?>" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="campo campo-ancho">
            <label for="prov-nombre">Nombre o razón social *</label>
            <input id="prov-nombre" name="nombre" value="<?php echo htmlspecialchars($proveedor['nombre'] ?? ''); ?>" maxlength="150" required>
        </div>
        <div class="campo">
            <label for="prov-rtn">RTN</label>
            <input id="prov-rtn" name="rtn" value="<?php echo htmlspecialchars($proveedor['rtn'] ?? ''); ?>" maxlength="30" placeholder="0000-0000-000000">
        </div>
        <div class="campo">
            <label for="prov-tipo">Condición tributaria *</label>
            <select id="prov-tipo" name="tipo" required>
                <option value="contribuyente" <?php echo ($proveedor['tipo'] ?? 'contribuyente') === 'contribuyente' ? 'selected' : ''; ?>>Contribuyente (emite crédito fiscal con factura CAI)</option>
                <option value="no_contribuyente" <?php echo ($proveedor['tipo'] ?? '') === 'no_contribuyente' ? 'selected' : ''; ?>>No contribuyente (compra sin crédito fiscal)</option>
            </select>
        </div>
        <div class="campo">
            <label for="prov-telefono">Teléfono</label>
            <input id="prov-telefono" name="telefono" value="<?php echo htmlspecialchars($proveedor['telefono'] ?? ''); ?>" maxlength="30">
        </div>
        <div class="campo">
            <label for="prov-contacto">Contacto</label>
            <input id="prov-contacto" name="contacto" value="<?php echo htmlspecialchars($proveedor['contacto'] ?? ''); ?>" maxlength="100">
        </div>
        <div class="campo">
            <label for="prov-correo">Correo</label>
            <input id="prov-correo" name="correo" type="email" value="<?php echo htmlspecialchars($proveedor['correo'] ?? ''); ?>" maxlength="100">
        </div>
        <div class="campo campo-ancho">
            <label for="prov-direccion">Dirección</label>
            <input id="prov-direccion" name="direccion" value="<?php echo htmlspecialchars($proveedor['direccion'] ?? ''); ?>" maxlength="255">
        </div>

        <div class="form-acciones" style="grid-column: 1 / -1;">
            <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>proveedores">Cancelar</a>
            <button class="btn-pos btn-success" type="submit">Guardar proveedor</button>
        </div>
    </form>
</section>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>