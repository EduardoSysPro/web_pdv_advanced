<?php $tituloPagina = 'Compras'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div><span class="eyebrow">Compras / Proveedores</span><h1>Ingreso de Facturas de Compra</h1><p>Registra la factura fiscal o recibo del proveedor, incluye gastos operativos y actualiza inventario y costos.</p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>proveedores">Proveedores</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras/pagos">Cuentas por pagar</a>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>inventario">Volver a Inventario</a>
    </div>
</section>

<?php if ($mensaje): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

<form id="form-compra" class="tarjeta" style="margin-top:16px;" onsubmit="return false;">
    <h2 class="tarjeta-titulo">1. Proveedor y documento del proveedor</h2>
    <div class="form-grid">
        <div class="campo">
            <label for="proveedor_id">Proveedor</label>
            <div style="display:flex;gap:6px;">
                <select id="proveedor_id" class="form-control-pos" name="proveedor_id" required style="flex:1;">
                    <option value="">-- Selecciona un proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?php echo (int)$prov['id']; ?>"
                                data-rtn="<?php echo htmlspecialchars($prov['rtn'] ?? ''); ?>"
                                data-tipo="<?php echo htmlspecialchars($prov['tipo'] ?? 'contribuyente'); ?>">
                            <?php echo htmlspecialchars($prov['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-pos-primary" type="button" id="btn-nuevo-proveedor">+ Nuevo</button>
            </div>
        </div>
        <div class="campo">
            <label>RTN del proveedor</label>
            <input class="form-control-pos" id="proveedor_rtn_mostrado" disabled>
        </div>
        <div class="campo">
            <label>Condición tributaria</label>
            <input class="form-control-pos" id="proveedor_tipo_mostrado" disabled>
        </div>
        <div class="campo">
            <label for="tipo_documento">Tipo de documento</label>
            <select id="tipo_documento" class="form-control-pos" name="tipo_documento">
                <option value="factura_cai">Factura fiscal (con CAI)</option>
                <option value="recibo">Recibo simple</option>
                <option value="nota_credito">Nota de crédito de proveedor</option>
                <option value="nota_debito">Nota de débito de proveedor</option>
            </select>
        </div>
        <div class="campo">
            <label for="numero_factura">Número de factura</label>
            <input id="numero_factura" class="form-control-pos" name="numero_factura" placeholder="000-001-01-00012345" required>
        </div>
        <div class="campo">
            <label for="cai">CAI</label>
            <input id="cai" class="form-control-pos" name="cai" placeholder="XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XX">
        </div>
        <div class="campo">
            <label for="rango_autorizado">Rango autorizado</label>
            <input id="rango_autorizado" class="form-control-pos" name="rango_autorizado" placeholder="Del 00000001 al 00050000">
        </div>
        <div class="campo">
            <label for="fecha_emision">Fecha de emisión</label>
            <input id="fecha_emision" class="form-control-pos" type="date" name="fecha_emision" required>
        </div>
        <div class="campo">
            <label for="fecha_limite_emision">Fecha límite de emisión (CAI)</label>
            <input id="fecha_limite_emision" class="form-control-pos" type="date" name="fecha_limite_emision">
        </div>
        <div class="campo" id="campo-documento-referencia" hidden>
            <label for="documento_referencia_id">Documento que referencia (NC/ND)</label>
            <select id="documento_referencia_id" class="form-control-pos">
                <option value="">-- Selecciona la compra original --</option>
                <?php foreach ($historial as $ref): ?>
                    <option value="<?php echo (int)$ref['id']; ?>"><?php echo htmlspecialchars($ref['folio'] . ' - ' . $ref['proveedor_nombre'] . ' (' . $ref['numero_factura'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="condicion_pago">Condición de pago</label>
            <select id="condicion_pago" class="form-control-pos" name="condicion_pago">
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
            </select>
        </div>
        <div class="campo">
            <label for="dias_credito">Días de crédito</label>
            <input id="dias_credito" class="form-control-pos" type="number" min="0" name="dias_credito" placeholder="Ej. 30" disabled>
        </div>
        <div class="campo">
            <label for="fecha_vencimiento_mostrado">Fecha de vencimiento</label>
            <input class="form-control-pos" id="fecha_vencimiento_mostrado" disabled>
        </div>
        <div class="campo">
            <label>Sucursal</label>
            <input class="form-control-pos" value="<?php echo htmlspecialchars($_SESSION['sucursal_nombre'] ?? 'Mi Negocio'); ?>" disabled>
        </div>
        <div class="campo campo-ancho">
            <label for="observaciones">Observaciones</label>
            <input id="observaciones" class="form-control-pos" name="observaciones" placeholder="Notas adicionales de la compra (opcional)">
        </div>
    </div>
    <div id="alerta-cf" class="alerta" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; display:none; margin-top:12px;">
        Documento con factura CAI de un contribuyente: el ISV de esta compra es <strong>acreditable</strong> (crédito fiscal).
    </div>
</form>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">2. Productos recibidos y gastos operativos</h2>
    <p style="color:var(--color-texto-claro,#718096); margin-top:-6px;">Solo se pueden agregar productos ya existentes en el catálogo; el inventario y el costo promedio se actualizarán al guardar. Los gastos operativos se registran sin afectar inventario.</p>

    <div class="busqueda-inventario" style="margin-bottom:12px;">
        <input class="form-control-pos" type="search" id="buscador-producto-compra" placeholder="Buscar por código de barras o nombre del producto" autocomplete="off">
        <button class="btn-pos-primary" type="button" id="btn-buscar-producto-compra">Buscar</button>
        <button class="btn-pos btn-secondary" type="button" id="btn-agregar-gasto">+ Gasto operativo</button>
    </div>
    <div id="resultados-producto-compra" class="resultados-inventario" style="display:none;"></div>

    <div class="tabla-responsive">
        <table class="tabla-catalogo" id="tabla-compra-items">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Stock actual</th>
                    <th>Cantidad</th>
                    <th>Costo unitario (L)</th>
                    <th>ISV</th>
                    <th>Subtotal (L)</th>
                    <th>Quitar</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-compra-items">
                <tr id="fila-vacia-compra"><td colspan="8" class="tabla-vacia">Aún no has agregado productos ni gastos a esta factura.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">3. Totales de la factura</h2>
    <div class="form-grid">
        <div class="campo"><label for="descuento_factura">Descuento (L)</label><input id="descuento_factura" class="form-control-pos" type="number" min="0" step="0.01" value="0"></div>
        <div class="campo"><label for="flete_compra">Flete / transporte (L)</label><input id="flete_compra" class="form-control-pos" type="number" min="0" step="0.01" value="0"></div>
        <div class="campo campo-ancho"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" id="prorratear_flete" checked> Prorratear flete y gastos operativos al costo de los productos</label></div>

        <div class="campo"><label>Importe exento (L)</label><div class="form-control-pos" id="total-exento">L 0.00</div></div>
        <div class="campo"><label>Base gravada 15% (L)</label><div class="form-control-pos" id="total-base15">L 0.00</div></div>
        <div class="campo"><label>ISV 15% (L)</label><div class="form-control-pos" id="total-isv15">L 0.00</div></div>
        <div class="campo"><label>Base gravada 18% (L)</label><div class="form-control-pos" id="total-base18">L 0.00</div></div>
        <div class="campo"><label>ISV 18% (L)</label><div class="form-control-pos" id="total-isv18">L 0.00</div></div>
        <div class="campo"><label>Subtotal (L)</label><div class="form-control-pos" id="total-subtotal">L 0.00</div></div>
        <div class="campo"><label><strong>Total a pagar</strong></label><div class="form-control-pos" id="total-general" style="background:#f7fafc; font-weight:800; font-size:1.1rem;">L 0.00</div></div>
    </div>
    <div class="form-acciones">
        <button class="btn-pos btn-secondary" type="button" onclick="window.location.href='<?php echo URL_BASE; ?>inventario'">Cancelar</button>
        <button class="btn-pos-success" type="button" id="btn-guardar-compra">Registrar compra</button>
    </div>
</section>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">Últimas compras registradas</h2>
    <form method="GET" action="<?php echo URL_BASE; ?>compras" class="busqueda-inventario" style="margin-bottom:12px;">
        <input class="form-control-pos" type="search" name="busqueda" placeholder="Buscar por proveedor, factura o folio" value="<?php echo htmlspecialchars($busqueda); ?>">
        <select class="form-control-pos" name="proveedor_id" style="max-width:220px;">
            <option value="">Todos los proveedores</option>
            <?php foreach ($proveedores as $prov): ?>
                <option value="<?php echo (int)$prov['id']; ?>" <?php echo ((int)$proveedorId === (int)$prov['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control-pos" type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fechaDesde); ?>">
        <input class="form-control-pos" type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fechaHasta); ?>">
        <select class="form-control-pos" name="estado" style="max-width:160px;">
            <option value="">Todos los estados</option>
            <option value="recibida" <?php echo $estado === 'recibida' ? 'selected' : ''; ?>>Recibida</option>
            <option value="anulada" <?php echo $estado === 'anulada' ? 'selected' : ''; ?>>Anulada</option>
        </select>
        <button class="btn-pos-primary" type="submit">Filtrar</button>
        <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>compras">Limpiar</a>
    </form>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Folio</th><th>Fecha</th><th>Proveedor</th><th>N.º Factura</th><th>Tipo</th><th>Total</th><th>Saldo</th><th>Estado</th><th style="width:160px;">Acciones</th></tr></thead>
            <tbody>
                <?php if (!$historial): ?><tr><td colspan="9" class="tabla-vacia">No hay compras registradas.</td></tr><?php endif; ?>
                <?php foreach ($historial as $compra): ?>
                    <?php
                    $etiquetasTipo = ['factura_cai' => 'Factura CAI', 'recibo' => 'Recibo', 'nota_credito' => 'Nota de crédito', 'nota_debito' => 'Nota de débito'];
                    $tipoDoc = $etiquetasTipo[$compra['tipo_documento']] ?? $compra['tipo_documento'];
                    $esAnulada = $compra['estado'] === 'anulada';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($compra['folio']); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($compra['fecha_emision'])); ?></td>
                        <td><?php echo htmlspecialchars($compra['proveedor_nombre']); ?><?php if ((int)$compra['isv_acreditable'] === 1): ?><span class="badge" style="margin-left:6px;background:#dcfce7;color:#166534;">CF</span><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($compra['numero_factura']); ?></td>
                        <td><?php echo htmlspecialchars($tipoDoc); ?></td>
                        <td><?php echo formatearMoneda($compra['total']); ?></td>
                        <td><?php echo $compra['saldo_pendiente'] > 0 ? '<strong style="color:#b45309;">' . formatearMoneda($compra['saldo_pendiente']) . '</strong>' : '—'; ?></td>
                        <td><span class="badge" style="background:<?php echo $esAnulada ? '#fee2e2;color:#991b1b;' : '#dcfce7;color:#166534;'; ?>"><?php echo $esAnulada ? 'Anulada' : 'Recibida'; ?></span></td>
                        <td style="white-space:nowrap;">
                            <a class="btn-pos btn-secondary btn-pequeno" href="<?php echo URL_BASE; ?>compras/ver/<?php echo (int)$compra['id']; ?>">Ver</a>
                            <?php if (!$esAnulada): ?>
                                <form method="POST" action="<?php echo URL_BASE; ?>compras/anular/<?php echo (int)$compra['id']; ?>" style="display:inline;" onsubmit="return confirm('¿Anular esta compra? El inventario se revertirá.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <button class="btn-pos btn-danger btn-pequeno" type="submit">Anular</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-simple" id="modal-proveedor" hidden>
    <div class="tarjeta">
        <h2 class="tarjeta-titulo">Nuevo proveedor</h2>
        <div class="form-grid">
            <div class="campo"><label>Nombre</label><input class="form-control-pos" id="nuevo-prov-nombre" placeholder="Razón social"></div>
            <div class="campo"><label>RTN</label><input class="form-control-pos" id="nuevo-prov-rtn" placeholder="0000-0000-000000"></div>
            <div class="campo"><label>Condición tributaria</label><select class="form-control-pos" id="nuevo-prov-tipo"><option value="contribuyente">Contribuyente</option><option value="no_contribuyente">No contribuyente</option></select></div>
            <div class="campo"><label>Teléfono</label><input class="form-control-pos" id="nuevo-prov-telefono"></div>
            <div class="campo"><label>Contacto</label><input class="form-control-pos" id="nuevo-prov-contacto"></div>
            <div class="campo"><label>Correo</label><input class="form-control-pos" id="nuevo-prov-correo"></div>
        </div>
        <div class="form-acciones">
            <button class="btn-pos btn-secondary" type="button" onclick="document.getElementById('modal-proveedor').hidden=true">Cancelar</button>
            <button class="btn-pos-success" type="button" id="btn-guardar-proveedor-ajax">Guardar proveedor</button>
        </div>
    </div>
</div>

<div class="modal-simple" id="modal-gasto" hidden>
    <div class="tarjeta">
        <h2 class="tarjeta-titulo">Gasto operativo</h2>
        <p style="color:var(--color-texto-claro,#718096); margin-top:-6px;">Conceptos que no son inventario: servicios, alquileres, transporte, suministros, etc.</p>
        <div class="form-grid">
            <div class="campo campo-ancho"><label>Concepto</label><input class="form-control-pos" id="gasto-concepto" placeholder="Ej. Flete urbano, energía, suministro de oficina"></div>
            <div class="campo"><label>Monto (L)</label><input class="form-control-pos" id="gasto-monto" type="number" min="0.01" step="0.01" value="0"></div>
            <div class="campo"><label>Tipo de impuesto</label><select class="form-control-pos" id="gasto-isv">
                <option value="exento">Exento (0%)</option>
                <option value="gravado_15" selected>Gravado 15%</option>
                <option value="gravado_18">Gravado 18%</option>
                <option value="exonerado">Exonerado (0%)</option>
            </select></div>
        </div>
        <div class="form-acciones">
            <button class="btn-pos btn-secondary" type="button" onclick="document.getElementById('modal-gasto').hidden=true">Cancelar</button>
            <button class="btn-pos-success" type="button" id="btn-agregar-gasto-confirmar">Agregar gasto</button>
        </div>
    </div>
</div>

<script>
(function () {
    var itemsCompra = [];
    var contadorGasto = 0;
    var urlBase = <?php echo json_encode(URL_BASE); ?>;
    var etiquetasImpuesto = {
        'exento': 'Exento 0%',
        'exonerado': 'Exonerado 0%',
        'gravado_15': '15%',
        'gravado_18': '18%'
    };
    var porcentajesImpuesto = { 'exento': 0, 'exonerado': 0, 'gravado_15': 15, 'gravado_18': 18 };

    function obtenerCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function redondear(valor) { return Math.round(valor * 100) / 100; }
    function moneda(valor) { return 'L ' + redondear(valor).toFixed(2); }

    // ---------- Proveedor ----------
    var selectProveedor = document.getElementById('proveedor_id');
    function opcionProveedorSeleccionada() {
        return selectProveedor.options[selectProveedor.selectedIndex];
    }
    function actualizarProveedorResumen() {
        var opt = opcionProveedorSeleccionada();
        var rtn = opt ? (opt.getAttribute('data-rtn') || '') : '';
        var tipo = opt ? (opt.getAttribute('data-tipo') || 'contribuyente') : '';
        document.getElementById('proveedor_rtn_mostrado').value = rtn;
        document.getElementById('proveedor_tipo_mostrado').value = tipo === 'contribuyente'
            ? 'Contribuyente (emite crédito fiscal)'
            : 'No contribuyente (sin crédito fiscal)';
        actualizarBadgeCF();
    }
    selectProveedor.addEventListener('change', actualizarProveedorResumen);

    document.getElementById('btn-nuevo-proveedor').addEventListener('click', function () {
        document.getElementById('nuevo-prov-nombre').value = '';
        document.getElementById('nuevo-prov-rtn').value = '';
        document.getElementById('nuevo-prov-tipo').value = 'contribuyente';
        document.getElementById('nuevo-prov-telefono').value = '';
        document.getElementById('nuevo-prov-contacto').value = '';
        document.getElementById('nuevo-prov-correo').value = '';
        document.getElementById('modal-proveedor').hidden = false;
        document.getElementById('nuevo-prov-nombre').focus();
    });

    document.getElementById('btn-guardar-proveedor-ajax').addEventListener('click', function () {
        var nombre = document.getElementById('nuevo-prov-nombre').value.trim();
        if (nombre === '') { alert('Escribe el nombre del proveedor.'); return; }
        fetch(urlBase + 'compras/guardar-proveedor-ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: obtenerCsrfToken(),
                nombre: nombre,
                rtn: document.getElementById('nuevo-prov-rtn').value.trim(),
                tipo: document.getElementById('nuevo-prov-tipo').value,
                telefono: document.getElementById('nuevo-prov-telefono').value.trim(),
                contacto: document.getElementById('nuevo-prov-contacto').value.trim(),
                correo: document.getElementById('nuevo-prov-correo').value.trim()
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            if (!resp.exito) { alert(resp.mensaje); return; }
            var prov = resp.proveedor;
            var opt = document.createElement('option');
            opt.value = prov.id;
            opt.setAttribute('data-rtn', prov.rtn || '');
            opt.setAttribute('data-tipo', prov.tipo || 'contribuyente');
            opt.textContent = prov.nombre;
            selectProveedor.appendChild(opt);
            selectProveedor.value = prov.id;
            actualizarProveedorResumen();
            document.getElementById('modal-proveedor').hidden = true;
        })
        .catch(function () { alert('No se pudo conectar con el servidor.'); });
    });

    // ---------- Documento ----------
    var tipoDocumento = document.getElementById('tipo_documento');
    function actualizarDocumento() {
        var tipo = tipoDocumento.value;
        document.getElementById('campo-documento-referencia').hidden = !(tipo === 'nota_credito' || tipo === 'nota_debito');
        actualizarBadgeCF();
    }
    tipoDocumento.addEventListener('change', actualizarDocumento);

    function actualizarBadgeCF() {
        var opt = opcionProveedorSeleccionada();
        var acreditable = tipoDocumento.value === 'factura_cai'
            && opt && (opt.getAttribute('data-tipo') === 'contribuyente');
        document.getElementById('alerta-cf').style.display = acreditable ? 'block' : 'none';
    }

    // ---------- Condición de pago y vencimiento ----------
    var campoCondicionPago = document.getElementById('condicion_pago');
    var campoDiasCredito = document.getElementById('dias_credito');
    function actualizarVencimiento() {
        var esCredito = campoCondicionPago.value === 'credito';
        campoDiasCredito.disabled = !esCredito;
        if (!esCredito) campoDiasCredito.value = '';
        var emision = document.getElementById('fecha_emision').value;
        var dias = parseInt(campoDiasCredito.value, 10);
        if (esCredito && emision && dias > 0) {
            var fecha = new Date(emision + 'T00:00:00');
            fecha.setDate(fecha.getDate() + dias);
            document.getElementById('fecha_vencimiento_mostrado').value = fecha.toISOString().slice(0, 10);
        } else {
            document.getElementById('fecha_vencimiento_mostrado').value = '';
        }
    }
    campoCondicionPago.addEventListener('change', actualizarVencimiento);
    campoDiasCredito.addEventListener('input', actualizarVencimiento);
    document.getElementById('fecha_emision').addEventListener('change', actualizarVencimiento);

    // ---------- Búsqueda de productos ----------
    var inputBuscar = document.getElementById('buscador-producto-compra');
    var btnBuscar = document.getElementById('btn-buscar-producto-compra');
    var contenedorResultados = document.getElementById('resultados-producto-compra');

    function buscarProducto() {
        var termino = inputBuscar.value.trim();
        if (termino === '') {
            contenedorResultados.style.display = 'none';
            contenedorResultados.innerHTML = '';
            return;
        }
        fetch(urlBase + 'compras/buscar-producto?termino=' + encodeURIComponent(termino))
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (productos) { mostrarResultados(Array.isArray(productos) ? productos : []); })
            .catch(function () { mostrarResultados([]); });
    }

    function mostrarResultados(productos) {
        contenedorResultados.innerHTML = '';
        if (!productos.length) {
            contenedorResultados.innerHTML = '<a href="javascript:void(0)"><span class="tabla-vacia" style="padding:.5rem 0;">No se encontraron productos.</span></a>';
            contenedorResultados.style.display = 'grid';
            return;
        }
        productos.forEach(function (producto) {
            var enlace = document.createElement('a');
            enlace.href = 'javascript:void(0)';
            enlace.innerHTML = '<strong>' + (producto.nombre || '') + '</strong><span>' + (producto.codigo_barras || '') + ' | Stock: ' + (producto.stock || 0) + '</span>';
            enlace.addEventListener('click', function () {
                agregarItem(producto);
                contenedorResultados.style.display = 'none';
                inputBuscar.value = '';
            });
            contenedorResultados.appendChild(enlace);
        });
        contenedorResultados.style.display = 'grid';
    }

    btnBuscar.addEventListener('click', buscarProducto);
    inputBuscar.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') { evento.preventDefault(); buscarProducto(); }
    });

    function agregarItem(producto) {
        var itemKey = producto.item_key || String(producto.id + '_unidad');
        if (itemsCompra.some(function (i) { return i.itemKey === itemKey; })) { return; }
        var factor = parseFloat(producto.factor_unidades || 1);
        var presentacion = producto.tipo_presentacion || 'unidad';
        var nombrePres = producto.nombre_presentacion || (presentacion === 'empaque' ? (producto.unidad_medida || 'Caja') : 'Unidad');
        itemsCompra.push({
            itemKey: itemKey,
            tipo: 'producto',
            producto_id: producto.id,
            codigo: producto.codigo_barras || '',
            nombre: producto.nombre || '',
            stock: parseFloat(producto.stock || 0),
            cantidad: 1,
            costo: 0,
            presentacion: presentacion,
            nombrePresentacion: nombrePres,
            factor: factor,
            claveIsv: producto.clave_isv || 'gravado_15',
            pctIsv: parseFloat(producto.porcentaje_isv || 15)
        });
        renderizarTabla();
    }

    // ---------- Gasto operativo ----------
    document.getElementById('btn-agregar-gasto').addEventListener('click', function () {
        document.getElementById('gasto-concepto').value = '';
        document.getElementById('gasto-monto').value = '';
        document.getElementById('gasto-isv').value = 'gravado_15';
        document.getElementById('modal-gasto').hidden = false;
        document.getElementById('gasto-concepto').focus();
    });

    document.getElementById('btn-agregar-gasto-confirmar').addEventListener('click', function () {
        var concepto = document.getElementById('gasto-concepto').value.trim();
        var monto = parseFloat(document.getElementById('gasto-monto').value) || 0;
        if (concepto === '') { alert('Escribe el concepto del gasto.'); return; }
        if (monto <= 0) { alert('El monto del gasto debe ser mayor a cero.'); return; }
        var claveIsv = document.getElementById('gasto-isv').value;
        contadorGasto++;
        itemsCompra.push({
            itemKey: 'gasto_' + contadorGasto,
            tipo: 'gasto',
            producto_id: 0,
            codigo: 'GASTO',
            nombre: concepto,
            stock: null,
            cantidad: 1,
            costo: redondear(monto),
            presentacion: 'unidad',
            nombrePresentacion: '-',
            factor: 1,
            claveIsv: claveIsv,
            pctIsv: porcentajesImpuesto[claveIsv] || 0
        });
        renderizarTabla();
        document.getElementById('modal-gasto').hidden = true;
    });

    // ---------- Tabla de líneas ----------
    function quitarItem(itemKey) {
        itemsCompra = itemsCompra.filter(function (i) { return i.itemKey !== itemKey; });
        renderizarTabla();
    }

    function renderizarTabla() {
        var cuerpo = document.getElementById('cuerpo-tabla-compra-items');
        cuerpo.innerHTML = '';
        if (!itemsCompra.length) {
            cuerpo.innerHTML = '<tr id="fila-vacia-compra"><td colspan="8" class="tabla-vacia">Aún no has agregado productos ni gastos a esta factura.</td></tr>';
            calcularTotales();
            return;
        }
        itemsCompra.forEach(function (item) {
            var fila = document.createElement('tr');
            var etiquetaIsv = item.claveIsv === 'exento' || item.claveIsv === 'exonerado'
                ? etiquetasImpuesto[item.claveIsv]
                : etiquetasImpuesto[item.claveIsv] + ' (' + item.pctIsv + '%)';
            var descripcion = item.tipo === 'gasto'
                ? '<strong>GASTO:</strong> ' + escapar(item.nombre)
                : '<strong>' + escapar(item.nombre) + '</strong><span class="sub">' + escapar(item.nombrePresentacion) + '</span>';
            var stockCelda = item.tipo === 'gasto' ? '—' : item.stock;

            fila.innerHTML =
                '<td>' + escapar(item.codigo) + '</td>' +
                '<td>' + descripcion + '</td>' +
                '<td>' + stockCelda + '</td>' +
                '<td><input type="number" min="0" step="' + (item.tipo === 'producto' ? 'any' : '1') + '"' + (item.tipo === 'gasto' ? ' disabled' : '') + ' class="form-control-pos input-cantidad" value="' + item.cantidad + '" style="width:90px;"></td>' +
                '<td><input type="number" min="0" step="0.01" class="form-control-pos input-costo" value="' + item.costo.toFixed(2) + '" style="width:120px;"></td>' +
                '<td>' + etiquetaIsv + '</td>' +
                '<td class="celda-subtotal">' + moneda(item.cantidad * item.costo) + '</td>' +
                '<td><button type="button" class="btn-pos btn-danger btn-pequeno btn-quitar">Quitar</button></td>';

            if (item.tipo === 'producto') {
                var inputCantidad = fila.querySelector('.input-cantidad');
                inputCantidad.addEventListener('input', function () {
                    item.cantidad = parseFloat(this.value) || 0;
                    fila.querySelector('.celda-subtotal').textContent = moneda(item.cantidad * item.costo);
                    calcularTotales();
                });
            }
            fila.querySelector('.input-costo').addEventListener('input', function () {
                item.costo = parseFloat(this.value) || 0;
                fila.querySelector('.celda-subtotal').textContent = moneda(item.cantidad * item.costo);
                calcularTotales();
            });
            fila.querySelector('.btn-quitar').addEventListener('click', function () { quitarItem(item.itemKey); });

            cuerpo.appendChild(fila);
        });
        calcularTotales();
    }

    function escapar(texto) {
        return String(texto || '').replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    // ---------- Totales e ISV ----------
    var campoDescuento = document.getElementById('descuento_factura');
    var campoFlete = document.getElementById('flete_compra');
    campoDescuento.addEventListener('input', calcularTotales);
    campoFlete.addEventListener('input', calcularTotales);

    function calcularTotales() {
        var subtotal = 0, baseExento = 0, baseGravado15 = 0, isv15 = 0, baseGravado18 = 0, isv18 = 0;

        itemsCompra.forEach(function (item) {
            var base = redondear(item.cantidad * item.costo);
            subtotal += base;
            if (item.claveIsv === 'exento') { baseExento += base; }
            else if (item.claveIsv === 'exonerado') { baseExento += base; }
            else if (item.pctIsv >= 18) { baseGravado18 += base; isv18 += redondear(base * item.pctIsv / 100); }
            else { baseGravado15 += base; isv15 += redondear(base * item.pctIsv / 100); }
        });

        var descuento = parseFloat(campoDescuento.value) || 0;
        var flete = parseFloat(campoFlete.value) || 0;
        descuento = Math.min(descuento, subtotal);
        var total = subtotal - descuento + isv15 + isv18 + flete;

        document.getElementById('total-exento').textContent = moneda(baseExento);
        document.getElementById('total-base15').textContent = moneda(baseGravado15);
        document.getElementById('total-isv15').textContent = moneda(isv15);
        document.getElementById('total-base18').textContent = moneda(baseGravado18);
        document.getElementById('total-isv18').textContent = moneda(isv18);
        document.getElementById('total-subtotal').textContent = moneda(subtotal);
        document.getElementById('total-general').textContent = moneda(total);
    }

    // ---------- Guardado ----------
    document.getElementById('btn-guardar-compra').addEventListener('click', function () {
        var opt = opcionProveedorSeleccionada();
        if (!opt || opt.value === '') { alert('Selecciona o crea un proveedor.'); return; }
        if (!document.getElementById('numero_factura').value.trim()) { alert('Escribe el número de factura del proveedor.'); return; }
        if (!document.getElementById('fecha_emision').value) { alert('Selecciona la fecha de emisión.'); return; }
        if (!itemsCompra.length) { alert('Agrega al menos una línea de producto o gasto.'); return; }

        var lineas = itemsCompra.map(function (item) {
            var linea = {
                tipo_linea: item.tipo,
                producto_id: item.producto_id,
                cantidad: item.cantidad,
                costo_unitario: item.costo
            };
            if (item.tipo === 'producto') {
                linea.tipo_presentacion = item.presentacion;
                linea.nombre_presentacion = item.nombrePresentacion;
                linea.factor_unidades = item.factor;
            } else {
                linea.nombre = item.nombre;
                linea.tipo_impuesto = item.claveIsv;
                linea.porcentaje_isv = item.pctIsv;
            }
            return linea;
        });

        var payload = {
            csrf_token: obtenerCsrfToken(),
            proveedor_id: parseInt(opt.value, 10) || 0,
            proveedor_nombre: opt.textContent.trim(),
            proveedor_rtn: opt.getAttribute('data-rtn') || '',
            proveedor_tipo: opt.getAttribute('data-tipo') || 'contribuyente',
            tipo_documento: tipoDocumento.value,
            numero_factura: document.getElementById('numero_factura').value.trim(),
            cai: document.getElementById('cai').value.trim(),
            rango_autorizado: document.getElementById('rango_autorizado').value.trim(),
            fecha_limite_emision: document.getElementById('fecha_limite_emision').value,
            fecha_emision: document.getElementById('fecha_emision').value,
            condicion_pago: campoCondicionPago.value,
            dias_credito: parseInt(campoDiasCredito.value, 10) || 0,
            documento_referencia_id: document.getElementById('documento_referencia_id').value || 0,
            observaciones: document.getElementById('observaciones').value.trim(),
            descuento: parseFloat(campoDescuento.value) || 0,
            flete: parseFloat(campoFlete.value) || 0,
            prorratear: document.getElementById('prorratear_flete').checked ? 1 : 0,
            lineas: lineas
        };

        var btn = document.getElementById('btn-guardar-compra');
        btn.disabled = true;
        btn.textContent = 'Registrando...';

        fetch(urlBase + 'compras/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            if (resp.exito) {
                alert(resp.mensaje);
                window.location.href = urlBase + 'compras/ver/' + resp.compra_id;
            } else {
                alert(resp.mensaje);
                btn.disabled = false;
                btn.textContent = 'Registrar compra';
            }
        })
        .catch(function () {
            alert('No se pudo conectar con el servidor.');
            btn.disabled = false;
            btn.textContent = 'Registrar compra';
        });
    });

    actualizarProveedorResumen();
    actualizarDocumento();
    calcularTotales();
}());
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>