<?php $tituloPagina = 'Editar usuario'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Administración</span>
        <h1>Editar usuario</h1>
        <p>Actualiza los datos del acceso y la asignación del usuario.</p>
    </div>
    <a class="btn btn-secondary" href="<?php echo URL_BASE; ?>usuarios">Volver</a>
</section>

<section class="tarjeta card-form usuarios-formulario">
    <h2 class="tarjeta-titulo">Datos del usuario</h2>
    <form method="POST" action="<?php echo URL_BASE; ?>usuarios/actualizar/<?php echo (int)$usuario['id']; ?>" class="form-grid cliente-formulario">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="campo form-group">
            <label for="usuario-nombre">Nombre completo</label>
            <input id="usuario-nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>
        </div>

        <div class="campo form-group">
            <label for="usuario-username">Nombre de usuario</label>
            <input id="usuario-username" name="username" value="<?php echo htmlspecialchars($usuario['usuario'] ?? ''); ?>" required autocomplete="username">
        </div>

        <div class="campo form-group">
            <label for="usuario-password">Nueva contraseña</label>
            <input id="usuario-password" name="password" type="password" minlength="6" placeholder="Dejar vacío para mantener la actual" autocomplete="new-password">
        </div>

        <div class="campo form-group">
            <label for="usuario-rol">Rol</label>
            <select id="usuario-rol" name="rol">
                <option value="cajero" <?php echo (($usuario['rol'] ?? 'cajero') === 'cajero') ? 'selected' : ''; ?>>Cajero</option>
                <option value="cajero_movil" <?php echo (($usuario['rol'] ?? 'cajero') === 'cajero_movil') ? 'selected' : ''; ?>>Cajero Móvil (Celular / Tablet)</option>
                <option value="admin" <?php echo (($usuario['rol'] ?? 'cajero') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
            </select>
        </div>

        <div class="campo form-group">
            <label for="usuario-estado">Estado</label>
            <select id="usuario-estado" name="estado">
                <option value="1" <?php echo !empty($usuario['estado']) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo empty($usuario['estado']) ? 'selected' : ''; ?>>Inactivo</option>
            </select>
        </div>

        <div class="campo form-group">
            <label for="usuario-sucursal">Sucursal</label>
            <input id="usuario-sucursal" name="sucursal" value="<?php echo htmlspecialchars($usuario['sucursal'] ?? ''); ?>" placeholder="Ej. Abarrotes Central">
        </div>

        <div class="campo form-group">
            <label for="usuario-caja">Caja asignada</label>
            <select id="usuario-caja" name="caja_id">
                <option value="0">Sin caja asignada</option>
                <?php foreach ($cajas as $caja): ?>
                    <option value="<?php echo (int)$caja['id']; ?>" <?php echo ((int)($usuario['caja_id'] ?? 0) === (int)$caja['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($caja['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-acciones" style="grid-column: 1 / -1;">
            <button class="btn btn-primario" type="submit">Guardar cambios</button>
        </div>
    </form>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
