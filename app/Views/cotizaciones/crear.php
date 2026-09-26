<?php $tituloPagina = $cotizacion ? 'Editar cotización' : 'Nueva cotización'; $claseMain = 'cotizaciones-editor-main'; $seccionClienteAbierta = !empty($cotizacion) && ((int)($cotizacion['cliente_id'] ?? 0) !== 0 || trim((string)($cotizacion['cliente_nombre'] ?? '')) !== ''); require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<link rel="stylesheet" href="<?php echo URL_BASE; ?>css/cotizaciones.css?v=<?php echo filemtime(PUBLIC_PATH . 'css/cotizaciones.css'); ?>">
<?php $esAdmin = (int)($_SESSION['rol_id'] ?? 0) === 1 || in_array(strtolower((string)($_SESSION['rol'] ?? '')), ['admin', 'administrador'], true); ?>

<section class="cotizaciones-editor-cabecera">
    <div class="cotizaciones-editor-titulo">
        <a class="btn btn-secondary btn-sm" href="<?php echo URL_BASE; ?>cotizaciones"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        <div>
            <span class="eyebrow"><?php echo $esAdmin ? 'Administración' : 'Ventas'; ?></span>
            <h1><?php echo $cotizacion ? 'Editar cotización' : 'Nueva cotización'; ?></h1>
            <?php if ($cotizacion): ?>
                <p>Folio <strong><?php echo htmlspecialchars($cotizacion['folio']); ?></strong> · creada el <?php echo date('d/m/Y H:i', strtotime($cotizacion['creada_en'])); ?></p>
            <?php else: ?>
                <p>Registra una cotización sin procesar pago; el cajero podrá facturarla en tiempo real.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<form id="cotizacion-form" autocomplete="off">
    <div class="cotizaciones-editor-grid">
        <div class="cotizaciones-editor-principal">
            <fieldset class="cot-seccion cot-seccion-plegable" id="cot-seccion-cliente" data-abierto="<?php echo $seccionClienteAbierta ? 'true' : 'false'; ?>">
                <legend class="cot-seccion-leyenda" id="cot-leyenda-cliente">
                    <span class="cot-seccion-leyenda-texto"><i class="fa-solid fa-user"></i> Datos del cliente</span>
                    <button type="button" class="cot-plegar-btn" id="cot-plegar-cliente" title="Mostrar u ocultar" aria-expanded="<?php echo $seccionClienteAbierta ? 'true' : 'false'; ?>" aria-controls="cot-cliente-contenido">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </legend>
                <div id="cot-cliente-contenido">
                <div class="cot-cliente-barra">
                    <p class="cot-ayuda-rtn">Escribe el <strong>RTN / Identidad</strong> y selecciona el cliente para autocompletar sus datos.</p>
                    <button type="button" class="btn btn-secondary btn-sm" id="cot-btn-registrar-cliente"><i class="fa-solid fa-user-plus"></i> Registrar cliente</button>
                </div>
                <div class="cot-cliente-buscar" id="cot-cliente-buscar" style="display:none;">
                    <input type="text" id="cot-cliente-selector" list="cot-lista-clientes" placeholder="Buscar un cliente existente...">
                    <datalist id="cot-lista-clientes"></datalist>
                </div>
                <div class="cot-grid-cliente">
                    <label class="cot-campo" style="grid-column: span 2;">
                        <span>Nombre del cliente</span>
                        <input type="text" id="cot-cliente-nombre" class="form-control-pos" placeholder="Consumidor Final si se deja vacío">
                    </label>
                    <label class="cot-campo">
                        <span>RTN / Identidad</span>
                        <input type="text" id="cot-cliente-rtn" class="form-control-pos" maxlength="14" placeholder="0801-1990-12345" list="cot-lista-rtn">
                        <datalist id="cot-lista-rtn"></datalist>
                    </label>
                    <label class="cot-campo">
                        <span>Teléfono</span>
                        <input type="text" id="cot-cliente-telefono" class="form-control-pos" maxlength="20" placeholder="98XX-XXXX">
                    </label>
                    <label class="cot-campo" style="grid-column: span 2;">
                        <span>Dirección</span>
                        <input type="text" id="cot-cliente-direccion" class="form-control-pos" placeholder="Dirección del cliente">
                    </label>
                    <label class="cot-campo">
                        <span>Válida hasta</span>
                        <input type="date" id="cot-fecha-validez" class="form-control-pos" value="<?php echo htmlspecialchars($fechaValidez ?? ''); ?>">
                    </label>
                    <label class="cot-campo">
                        <span>Estado</span>
                        <input type="text" class="form-control-pos" value="Pendiente de facturar" readonly>
                    </label>
                    <label class="cot-campo">
                        <span>Categoría</span>
                        <input type="text" id="cot-cliente-tipo" class="form-control-pos" value="Minorista" readonly>
                    </label>
                </div>
                </div>
            </fieldset>

            <fieldset class="cot-seccion">
                <legend><i class="fa-solid fa-box"></i> Artículos de la cotización</legend>
                <div class="cot-buscador">
                    <input type="search" id="cot-busqueda-input" class="form-control-pos cot-busqueda-input" placeholder="Buscar producto por nombre o código (mínimo 2 letras)...">
                    <span class="cot-busqueda-icono"><i class="fa-solid fa-spinner fa-spin" style="display:none;"></i></span>
                </div>
                <div class="cot-resultados" id="cot-resultados" hidden>
                    <p class="cot-resultados-titulo">Resultados</p>
                    <div class="tabla-responsive"><table class="table-pos cot-resultados-tabla" id="cot-resultados-tabla">
                        <thead><tr><th></th><th>Producto</th><th>P. Venta</th><th>Existen.</th><th>Agregar</th></tr></thead>
                        <tbody></tbody>
                    </table></div>
                </div>
                <div class="tabla-responsive cot-carrito-wrap">
                    <table class="table-pos cot-carrito" id="cot-carrito">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>P. Lista</th>
                                <th>Descuento</th>
                                <th>P. Final</th>
                                <th>Cant.</th>
                                <th class="text-right">Importe</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cot-carrito-body"></tbody>
                    </table>
                    <p class="cot-carrito-vacio" id="cot-carrito-vacio">
                        <i class="fa-solid fa-cart-plus"></i> Agrega productos a la cotización desde la búsqueda.
                    </p>
                </div>
            </fieldset>
        </div>

        <aside class="cotizaciones-editor-resumen">
            <div class="cot-resumen-totales" id="cot-resumen-totales">
                <h3><i class="fa-solid fa-calculator"></i> Resumen</h3>
                <dl>
                    <div class="cot-resumen-fila"><dt>Subtotal (lista)</dt><dd id="cot-res-subtotal">L 0.00</dd></div>
                    <div class="cot-resumen-fila"><dt>Descuentos</dt><dd id="cot-res-descuento">- L 0.00</dd></div>
                    <div class="cot-resumen-fila cot-resumen-divisor"><dt id="cot-res-label-grav15">Base gravada 15%</dt><dd id="cot-res-base15">L 0.00</dd></div>
                    <div class="cot-resumen-fila"><dt>ISV 15%</dt><dd id="cot-res-isv15">L 0.00</dd></div>
                    <div class="cot-resumen-fila"><dt id="cot-res-label-grav18">Base gravada 18%</dt><dd id="cot-res-base18">L 0.00</dd></div>
                    <div class="cot-resumen-fila"><dt>ISV 18%</dt><dd id="cot-res-isv18">L 0.00</dd></div>
                    <div class="cot-resumen-fila"><dt>Exento / Exonerado</dt><dd id="cot-res-exento">L 0.00</dd></div>
                    <div class="cot-resumen-fila cot-resumen-total"><dt>Total a pagar</dt><dd id="cot-res-total">L 0.00</dd></div>
                </dl>
            </div>

            <div class="cot-resumen-extra">
                <label class="cot-campo">
                    <span>Observaciones (opcional)</span>
                    <textarea id="cot-observaciones" class="form-control-pos" rows="3" placeholder="Notas que verá el cajero al facturar (p. ej. plazo, forma de pago acordada..."></textarea>
                </label>
                <?php if (($configuracion['cotizacion_mensaje'] ?? '') !== ''): ?>
                    <p class="cot-mensaje-config"><?php echo htmlspecialchars($configuracion['cotizacion_mensaje']); ?></p>
                <?php endif; ?>
            </div>

            <div class="cot-resumen-acciones">
                <button type="button" class="btn btn-exito" id="cot-btn-guardar"><i class="fa-solid fa-check"></i> Guardar cotización</button>
                <button type="button" class="btn btn-secondary" id="cot-btn-imprimir"><i class="fa-solid fa-print"></i> Guardar e imprimir</button>
                <a class="btn btn-secondary" href="<?php echo URL_BASE; ?>cotizaciones">Cancelar</a>
            </div>
        </aside>
    </div>
</form>

<?php
$clientesJson = array_map(static function ($cliente) {
    return [
        'id'          => (int)$cliente['id'],
        'nombre'      => (string)($cliente['nombre'] ?? ''),
        'rtn_identidad' => (string)($cliente['rtn_identidad'] ?? ''),
        'telefono'    => (string)($cliente['telefono'] ?? ''),
        'direccion'   => (string)($cliente['direccion'] ?? ''),
        'tipo'        => (string)($cliente['tipo'] ?? 'minorista')
    ];
}, $clientes ?? []);
?>

<div class="modal-simple" id="modal-cliente-rapido" hidden>
    <div class="tarjeta">
        <h2 class="tarjeta-titulo">Registrar cliente <button type="button" class="modal-cerrar-x" id="mc-cerrar" aria-label="Cerrar">&#10005;</button></h2>
        <p class="form-nota">El cliente quedará disponible en el buscador para futuras cotizaciones.</p>
        <form id="modal-cliente-form" class="form-grid" novalidate>
            <div class="campo"><label>Nombre *</label><input type="text" id="mc-nombre" class="form-control-pos" placeholder="Nombre del cliente" required></div>
            <div class="campo"><label>RTN / Identidad</label><input type="text" id="mc-rtn" class="form-control-pos" maxlength="14" placeholder="0801-1990-12345"></div>
            <div class="campo"><label>Teléfono</label><input type="text" id="mc-telefono" class="form-control-pos" maxlength="20" placeholder="98XX-XXXX"></div>
            <div class="campo"><label>Categoría</label><select id="mc-tipo" class="form-control-pos"><option value="minorista">Minorista (detalle)</option><option value="mayorista">Mayorista</option></select></div>
            <div class="campo"><label>Límite de crédito (L)</label><input type="number" id="mc-credito" class="form-control-pos" min="0" step="0.01" value="0"></div>
            <div class="campo campo-ancho"><label>Dirección</label><input type="text" id="mc-direccion" class="form-control-pos" placeholder="Dirección del cliente"></div>
            <div class="campo-ancho">
                <div id="mc-error" class="alerta alerta-error" style="display:none;"></div>
            </div>
            <div class="campo-ancho form-acciones">
                <button type="button" class="btn btn-secondary" id="mc-cancelar">Cancelar</button>
                <button type="submit" class="btn btn-exito" id="mc-guardar"><i class="fa-solid fa-floppy-disk"></i> Guardar cliente</button>
            </div>
        </form>
    </div>
</div>

<script>
    const URL_BASE = '<?php echo URL_BASE; ?>';
    const MONEDA_SIMBOLO = '<?php echo MONEDA_SIMBOLO; ?>';
    const CSRF_TOKEN = '<?php echo htmlspecialchars(csrf_token()); ?>';
    const COTIZACION_ID = <?php echo $cotizacion ? (int)$cotizacion['id'] : 0; ?>;
    const MODO_EDICION = <?php echo $cotizacion ? 1 : 0; ?>;
    const CLIENTES = <?php echo json_encode($clientesJson, JSON_UNESCAPED_UNICODE); ?>;
    const ITEMS_INICIALES = <?php echo json_encode($itemsEdicion ?? [], JSON_UNESCAPED_UNICODE); ?>;
    const COTIZACION_CLIENTE = <?php echo $cotizacion ? json_encode([
        'id' => (int)($cotizacion['cliente_id'] ?? 0),
        'nombre' => (string)($cotizacion['cliente_nombre'] ?? ''),
        'rtn' => (string)($cotizacion['cliente_rtn'] ?? ''),
        'telefono' => (string)($cotizacion['cliente_telefono'] ?? ''),
        'direccion' => (string)($cotizacion['cliente_direccion'] ?? '')
    ], JSON_UNESCAPED_UNICODE) : 'null'; ?>;
    const COTIZACION_FECHA_VALIDEZ = '<?php echo htmlspecialchars($cotizacion['fecha_validez'] ?? ($fechaValidez ?? '')); ?>';
    const COTIZACION_OBSERVACIONES = '<?php echo htmlspecialchars($cotizacion['observaciones'] ?? ''); ?>';
</script>
<?php $versionJs = file_exists(PUBLIC_PATH . 'js/cotizaciones.js') ? filemtime(PUBLIC_PATH . 'js/cotizaciones.js') : time(); ?>
<script src="<?php echo URL_BASE; ?>js/cotizaciones.js?v=<?php echo $versionJs; ?>"></script>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>