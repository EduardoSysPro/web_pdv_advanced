<?php $tituloPagina = $titulo; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado"><div><span class="eyebrow">F3 / Catálogo</span><h1><?php echo htmlspecialchars($titulo); ?></h1><p>Completa la información comercial y de inventario.</p></div><a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>productos">Volver al catálogo</a></section>
<section class="tarjeta formulario-producto">
    <?php foreach (($errores ?? []) as $error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endforeach; ?>
    <form method="POST" action="<?php echo $accion; ?>" id="form-producto">
        <div class="form-grid">
            <div class="campo">
                <label for="codigo_barras">Código de barras</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input id="codigo_barras" name="codigo_barras" value="<?php echo htmlspecialchars($producto['codigo_barras'] ?? ''); ?>" maxlength="50" class="codigo-barras-input" style="flex:1;">
                    <button type="button" id="generar-codigo-interno" class="btn-pos btn-secondary" style="white-space:nowrap;">⚡ Generar Código</button>
                </div>
                <div id="barcode-preview-wrapper" style="display:none; margin-top:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px; text-align:center;">
                    <svg id="barcode-preview" style="max-width:100%; height:70px;"></svg>
                </div>
            </div>
            <div class="campo campo-ancho"><label for="nombre">Descripción / nombre</label><input id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" maxlength="150" required></div>
            <div class="campo"><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id"><option value="">Sin categoría</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int)$categoria['id']; ?>" <?php echo (string)($producto['categoria_id'] ?? '') === (string)$categoria['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nombre']); ?></option><?php endforeach; ?></select></div>
            <div class="campo"><label for="unidad_medida">Unidad de medida</label><select id="unidad_medida" name="unidad_medida"><option value="unidad" <?php echo (($producto['unidad_medida'] ?? 'unidad') === 'unidad') ? 'selected' : ''; ?>>Unidades</option><option value="libra" <?php echo (($producto['unidad_medida'] ?? 'unidad') === 'libra') ? 'selected' : ''; ?>>Libras</option><option value="kg" <?php echo (($producto['unidad_medida'] ?? 'unidad') === 'kg') ? 'selected' : ''; ?>>Kilogramos</option><option value="arroba" <?php echo (($producto['unidad_medida'] ?? 'unidad') === 'arroba') ? 'selected' : ''; ?>>Arroba</option><option value="litro" <?php echo (($producto['unidad_medida'] ?? 'unidad') === 'litro') ? 'selected' : ''; ?>>Litros</option></select></div>
            <div class="campo"><label for="precio_costo">Precio costo (L)</label><input id="precio_costo" name="precio_costo" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($producto['precio_costo'] ?? '0.00'); ?>" required></div>
            <div class="campo"><label for="precio_venta">Precio venta (L)</label><input id="precio_venta" name="precio_venta" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($producto['precio_venta'] ?? '0.00'); ?>" required></div>
            <div class="campo">
                <label for="tipo_impuesto">Impuesto (ISV)</label>
                <select id="tipo_impuesto" name="tipo_impuesto">
                    <?php $tipoImpuestoActual = $producto['tipo_impuesto'] ?? 'gravado_15'; ?>
                    <option value="exento" <?php echo $tipoImpuestoActual === 'exento' ? 'selected' : ''; ?>>Exento 0%</option>
                    <option value="gravado_15" <?php echo $tipoImpuestoActual === 'gravado_15' ? 'selected' : ''; ?>>ISV 15%</option>
                    <option value="gravado_18" <?php echo $tipoImpuestoActual === 'gravado_18' ? 'selected' : ''; ?>>ISV 18%</option>
                    <option value="exonerado" <?php echo $tipoImpuestoActual === 'exonerado' ? 'selected' : ''; ?>>Exonerado 0%</option>
                </select>
            </div>
            <div class="campo"><label for="stock">Stock actual</label><input id="stock" name="stock" type="number" min="0" step="0.001" value="<?php echo htmlspecialchars((string)($producto['stock'] ?? '0')); ?>" required></div>
            <div class="campo"><label for="stock_minimo">Stock mínimo</label><input id="stock_minimo" name="stock_minimo" type="number" min="0" step="0.001" value="<?php echo htmlspecialchars((string)($producto['stock_minimo'] ?? '1')); ?>" required></div>
            <div class="campo">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="permite_decimales" value="1" <?php echo (($producto['permite_decimales'] ?? 0) == 1 || (($producto['unidad_medida'] ?? 'unidad') !== 'unidad')) ? 'checked' : ''; ?>>
                    Permite cantidades decimales / peso/granel
                </label>
            </div>
        </div>

        <!-- ================= SECCIÓN DE VENTA POR EMPAQUE (CAJA / BULTO / FARDO) ================= -->
        <?php
            $tieneEmpaqueInicial = in_array($producto['tipo_venta'] ?? 'solo_unidad', ['solo_empaque', 'ambos'], true) || ((float)($producto['precio_empaque'] ?? 0) > 0);
            $nombreEmpaqueActual = $producto['nombre_empaque'] ?? 'Caja';
            $opcionesEmpaqueComunes = ['Caja', 'Bulto', 'Fardo', 'Paquete', 'Display'];
            $esEmpaquePersonalizado = !in_array($nombreEmpaqueActual, $opcionesEmpaqueComunes, true);
        ?>
        <div class="tarjeta-empaque-config" style="margin-top: 20px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom: 12px;">
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="tiene_empaque" name="tiene_empaque" value="1" <?php echo $tieneEmpaqueInicial ? 'checked' : ''; ?>>
                    📦 Configurar venta por Caja, Bulto o Fardo (Mayorista)
                </label>
                <span style="font-size: 12px; color: #64748b;">El inventario siempre se descuenta en unidades individuales automáticamente</span>
            </div>

            <div id="contenedor-campos-empaque" style="<?php echo $tieneEmpaqueInicial ? '' : 'display:none;'; ?>">
                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <div class="campo">
                        <label for="tipo_venta">Modalidad de venta</label>
                        <select id="tipo_venta" name="tipo_venta">
                            <option value="ambos" <?php echo (($producto['tipo_venta'] ?? 'solo_unidad') === 'ambos' || empty($producto['tipo_venta']) || $producto['tipo_venta'] === 'solo_unidad') ? 'selected' : ''; ?>>Ambos (Vender por Unidad y por Empaque)</option>
                            <option value="solo_empaque" <?php echo (($producto['tipo_venta'] ?? '') === 'solo_empaque') ? 'selected' : ''; ?>>Solo Empaque completo (No vender suelto)</option>
                            <option value="solo_unidad" <?php echo (($producto['tipo_venta'] ?? '') === 'solo_unidad') ? 'selected' : ''; ?>>Solo Unidad individual</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="nombre_empaque">Tipo de empaque</label>
                        <select id="nombre_empaque" name="nombre_empaque">
                            <option value="Caja" <?php echo ($nombreEmpaqueActual === 'Caja') ? 'selected' : ''; ?>>Caja</option>
                            <option value="Bulto" <?php echo ($nombreEmpaqueActual === 'Bulto') ? 'selected' : ''; ?>>Bulto</option>
                            <option value="Fardo" <?php echo ($nombreEmpaqueActual === 'Fardo') ? 'selected' : ''; ?>>Fardo</option>
                            <option value="Paquete" <?php echo ($nombreEmpaqueActual === 'Paquete') ? 'selected' : ''; ?>>Paquete</option>
                            <option value="Display" <?php echo ($nombreEmpaqueActual === 'Display') ? 'selected' : ''; ?>>Display</option>
                            <option value="otro" <?php echo $esEmpaquePersonalizado ? 'selected' : ''; ?>>Otro (personalizado)...</option>
                        </select>
                    </div>

                    <div class="campo" id="campo-empaque-personalizado" style="<?php echo $esEmpaquePersonalizado ? '' : 'display:none;'; ?>">
                        <label for="nombre_empaque_personalizado">Nombre personalizado</label>
                        <input id="nombre_empaque_personalizado" name="nombre_empaque_personalizado" placeholder="Ej: Arroba, Saco, Blister" value="<?php echo htmlspecialchars($nombreEmpaqueActual); ?>">
                    </div>

                    <div class="campo">
                        <label for="unidades_por_empaque">Unidades por empaque</label>
                        <input id="unidades_por_empaque" name="unidades_por_empaque" type="number" min="2" step="0.001" value="<?php echo htmlspecialchars((string)($producto['unidades_por_empaque'] ?? '10')); ?>" placeholder="Ej: 10, 12, 24">
                        <small style="color:#64748b; font-size:11px;">Ej: 1 caja contiene 10 unidades</small>
                    </div>

                    <div class="campo">
                        <label for="precio_empaque">Precio de venta del empaque (L)</label>
                        <input id="precio_empaque" name="precio_empaque" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars((string)($producto['precio_empaque'] ?? '0.00')); ?>" placeholder="0.00">
                        <small style="color:#64748b; font-size:11px;" id="info-precio-unidad-empaque">L 0.00 / unidad en caja</small>
                    </div>

                    <div class="campo campo-ancho">
                        <label for="codigo_barras_empaque">Código de barras exclusivo del empaque (opcional)</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input id="codigo_barras_empaque" name="codigo_barras_empaque" value="<?php echo htmlspecialchars($producto['codigo_barras_empaque'] ?? ''); ?>" maxlength="50" placeholder="Escanea el código de la caja si viene de fábrica" style="flex:1;">
                            <button type="button" id="btn-gen-codigo-empaque" class="btn-pos btn-secondary" style="white-space:nowrap;">⚡ Generar Código</button>
                        </div>
                    </div>
                </div>

                <!-- Resumen informativo de venta mayorista -->
                <div id="resumen-empaque" style="margin-top: 10px; padding: 8px 12px; background: #e0f2fe; border-left: 4px solid #0284c7; border-radius: 4px; font-size: 12px; color: #0369a1;">
                    Al vender 1 <strong id="lbl-tipo-empaque">Caja</strong>, se descontarán automáticamente <strong id="lbl-unidades-descuento">10</strong> unidades del inventario base.
                </div>
            </div>
        </div>

        <div class="ganancia-calculo"><span>Ganancia (Unidad): <strong id="ganancia">0.00%</strong></span><button type="button" class="btn-pos btn-secondary" id="sugerir-precio">Sugerir venta +30%</button></div>
        <div class="form-acciones"><a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>productos">Cancelar</a><button class="btn-pos btn-success" type="submit">Guardar Producto</button></div>
    </form>
</section>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {
    const costo = document.getElementById('precio_costo');
    const venta = document.getElementById('precio_venta');
    const ganancia = document.getElementById('ganancia');
    const codigoInput = document.getElementById('codigo_barras');
    const botonGenerar = document.getElementById('generar-codigo-interno');
    const previewWrapper = document.getElementById('barcode-preview-wrapper');
    const unidadInput = document.getElementById('unidad_medida');
    const permiteDecimalesInput = document.querySelector('input[name="permite_decimales"]');

    function actualizarUnidad() {
        if (!unidadInput || !permiteDecimalesInput) return;
        const unidad = unidadInput.value;
        const requiereFracciones = unidad !== 'unidad';
        permiteDecimalesInput.checked = requiereFracciones || permiteDecimalesInput.checked;
    }

    function generarCodigoInterno() {
        const prefijo = '20';
        const aleatorio = String(Math.floor(Math.random() * 9999999999)).padStart(10, '0');
        let codigo = prefijo + aleatorio;
        if (codigo.length > 13) {
            codigo = codigo.substring(0, 13);
        }
        if (!/^\d{12,13}$/.test(codigo)) {
            return generarCodigoInterno();
        }
        return codigo;
    }

    function actualizarPreviewCodigo() {
        const valor = (codigoInput.value || '').trim();
        if (!valor || !/^\d{12,13}$/.test(valor) || typeof window.JsBarcode === 'undefined') {
            if (previewWrapper) previewWrapper.style.display = 'none';
            return;
        }
        if (previewWrapper) previewWrapper.style.display = 'block';
        try {
            window.JsBarcode('#barcode-preview', valor, {
                format: 'CODE128',
                displayValue: true,
                fontSize: 13,
                margin: 10,
                width: 2,
                height: 50,
                background: '#ffffff'
            });
        } catch (error) {
            console.warn('No se pudo dibujar el código de barras:', error);
        }
    }

    function calcular() { const c = Number(costo.value) || 0; const v = Number(venta.value) || 0; ganancia.textContent = (c ? ((v - c) / c * 100).toFixed(2) : '0.00') + '%'; }
    costo.addEventListener('input', calcular); venta.addEventListener('input', calcular);
    document.getElementById('sugerir-precio').addEventListener('click', function () { venta.value = ((Number(costo.value) || 0) * 1.3).toFixed(2); calcular(); });
    if (botonGenerar) {
        botonGenerar.addEventListener('click', function () {
            const codigo = generarCodigoInterno();
            if (codigoInput) codigoInput.value = codigo;
            actualizarPreviewCodigo();
        });
    }
    if (codigoInput) {
        codigoInput.addEventListener('input', actualizarPreviewCodigo);
        codigoInput.addEventListener('change', actualizarPreviewCodigo);
    }
    if (unidadInput) {
        unidadInput.addEventListener('change', actualizarUnidad);
    }
    if (permiteDecimalesInput) {
        permiteDecimalesInput.addEventListener('change', function () {
            if (!this.checked && unidadInput && unidadInput.value !== 'unidad') {
                this.checked = true;
            }
        });
    }
    calcular();
    actualizarUnidad();
    actualizarPreviewCodigo();

    // ================= LÓGICA INTERACTIVA DE EMPAQUES =================
    const tieneEmpaqueChk = document.getElementById('tiene_empaque');
    const contenedorEmpaque = document.getElementById('contenedor-campos-empaque');
    const tipoVentaSel = document.getElementById('tipo_venta');
    const nombreEmpaqueSel = document.getElementById('nombre_empaque');
    const campoPersonalizado = document.getElementById('campo-empaque-personalizado');
    const nombrePersonalizadoInput = document.getElementById('nombre_empaque_personalizado');
    const unidadesEmpaqueInput = document.getElementById('unidades_por_empaque');
    const precioEmpaqueInput = document.getElementById('precio_empaque');
    const codigoEmpaqueInput = document.getElementById('codigo_barras_empaque');
    const btnGenCodigoEmpaque = document.getElementById('btn-gen-codigo-empaque');
    const infoPrecioUnidadEmpaque = document.getElementById('info-precio-unidad-empaque');
    const lblTipoEmpaque = document.getElementById('lbl-tipo-empaque');
    const lblUnidadesDescuento = document.getElementById('lbl-unidades-descuento');

    function actualizarVisibilidadEmpaque() {
        if (!tieneEmpaqueChk || !contenedorEmpaque) return;
        contenedorEmpaque.style.display = tieneEmpaqueChk.checked ? 'block' : 'none';
        actualizarCalculoEmpaque();
    }

    function obtenerNombreEmpaqueElegido() {
        if (!nombreEmpaqueSel) return 'Caja';
        if (nombreEmpaqueSel.value === 'otro') {
            return (nombrePersonalizadoInput && nombrePersonalizadoInput.value.trim()) || 'Empaque';
        }
        return nombreEmpaqueSel.value;
    }

    function actualizarCalculoEmpaque() {
        const nom = obtenerNombreEmpaqueElegido();
        if (lblTipoEmpaque) lblTipoEmpaque.textContent = nom;
        const u = Number(unidadesEmpaqueInput?.value) || 1;
        if (lblUnidadesDescuento) lblUnidadesDescuento.textContent = u;

        const pEmp = Number(precioEmpaqueInput?.value) || 0;
        if (infoPrecioUnidadEmpaque) {
            if (u > 0 && pEmp > 0) {
                const equiv = (pEmp / u).toFixed(2);
                infoPrecioUnidadEmpaque.textContent = `Equivalente: L ${equiv} c/u (Contiene ${u} unid)`;
            } else {
                infoPrecioUnidadEmpaque.textContent = 'L 0.00 / unidad en caja';
            }
        }
    }

    if (tieneEmpaqueChk) {
        tieneEmpaqueChk.addEventListener('change', actualizarVisibilidadEmpaque);
    }
    if (nombreEmpaqueSel) {
        nombreEmpaqueSel.addEventListener('change', function () {
            if (campoPersonalizado) {
                campoPersonalizado.style.display = this.value === 'otro' ? 'block' : 'none';
            }
            actualizarCalculoEmpaque();
        });
    }
    if (nombrePersonalizadoInput) {
        nombrePersonalizadoInput.addEventListener('input', actualizarCalculoEmpaque);
    }
    if (unidadesEmpaqueInput) {
        unidadesEmpaqueInput.addEventListener('input', actualizarCalculoEmpaque);
    }
    if (precioEmpaqueInput) {
        precioEmpaqueInput.addEventListener('input', actualizarCalculoEmpaque);
    }
    if (btnGenCodigoEmpaque && codigoEmpaqueInput) {
        btnGenCodigoEmpaque.addEventListener('click', function () {
            codigoEmpaqueInput.value = '77' + String(Math.floor(Math.random() * 9999999999)).padStart(10, '0').substring(0, 11);
        });
    }

    actualizarCalculoEmpaque();
}());
</script>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
