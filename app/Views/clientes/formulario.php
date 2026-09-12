<?php $esEdicion = isset($cliente['id']); $accion = $esEdicion ? URL_BASE . 'clientes/actualizar/' . (int)$cliente['id'] : URL_BASE . 'clientes/guardar'; ?>
<form method="POST" action="<?php echo $accion; ?>" class="form-grid cliente-formulario">
	<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
	<div class="campo form-group"><label for="cliente-rtn">RTN / Identidad</label><input id="cliente-rtn" name="rtn_identidad" maxlength="30" value="<?php echo htmlspecialchars($cliente['rtn_identidad'] ?? ''); ?>"></div>
	<div class="campo campo-ancho form-group"><label for="cliente-nombre">Nombre / Negocio</label><input id="cliente-nombre" name="nombre" maxlength="150" required value="<?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?>"></div>
	<div class="campo form-group"><label for="cliente-telefono">Teléfono</label><input id="cliente-telefono" name="telefono" maxlength="30" value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>"></div>
	<div class="campo campo-ancho form-group"><label for="cliente-direccion">Dirección</label><input id="cliente-direccion" name="direccion" maxlength="255" value="<?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?>"></div>
	<div class="campo form-group"><label for="cliente-limite">Límite de crédito (L)</label><input id="cliente-limite" name="limite_credito" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($cliente['limite_credito'] ?? '0.00'); ?>"></div>
	<div class="form-acciones"><a class="btn btn-secondary" href="<?php echo URL_BASE; ?>clientes">Cancelar</a><button class="btn btn-success" type="submit">Guardar cliente</button></div>
</form>
