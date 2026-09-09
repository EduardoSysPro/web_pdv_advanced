<?php $tituloPagina = 'Compras'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>
<section class="catalogo-encabezado">
    <div><span class="eyebrow">Compras / Proveedores</span><h1>Ingreso de Facturas de Compra</h1><p>Registra la factura fiscal o recibo del proveedor y actualiza el inventario de productos existentes.</p></div>
    <a class="btn-pos btn-secondary" href="<?php echo URL_BASE; ?>inventario">Volver a Inventario</a>
</section>

<div class="alerta" style="background:#fff7e6; color:#92610c; border:1px solid #f5d99b;">
    <strong>Vista previa (solo frontend):</strong> esta pantalla todavía no guarda información en la base de datos. Es una maqueta para validar el flujo antes de construir el backend.
</div>

<form id="form-compra" class="tarjeta" style="margin-top:16px;" onsubmit="return false;">
    <h2 class="tarjeta-titulo">1. Datos del documento del proveedor</h2>
    <div class="form-grid">
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
            <label for="proveedor">Proveedor</label>
            <input id="proveedor" class="form-control-pos" name="proveedor" placeholder="Nombre del proveedor" required>
        </div>
        <div class="campo">
            <label for="rtn_proveedor">RTN del proveedor</label>
            <input id="rtn_proveedor" class="form-control-pos" name="rtn_proveedor" placeholder="0000-0000-000000" maxlength="20">
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
            <label for="fecha_emision">Fecha de emisión</label>
            <input id="fecha_emision" class="form-control-pos" type="date" name="fecha_emision" required>
        </div>
        <div class="campo">
            <label for="fecha_limite_emision">Fecha límite de emisión (CAI)</label>
            <input id="fecha_limite_emision" class="form-control-pos" type="date" name="fecha_limite_emision">
        </div>
        <div class="campo">
            <label for="rango_autorizado">Rango autorizado</label>
            <input id="rango_autorizado" class="form-control-pos" name="rango_autorizado" placeholder="Del 00000001 al 00050000">
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
        <div class="campo campo-ancho">
            <label for="observaciones">Observaciones</label>
            <input id="observaciones" class="form-control-pos" name="observaciones" placeholder="Notas adicionales de la compra (opcional)">
        </div>
    </div>
</form>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">2. Productos recibidos</h2>
    <p style="color:var(--color-texto-claro,#718096); margin-top:-6px;">Solo se pueden agregar productos ya existentes en el catálogo; el inventario se actualizará al guardar.</p>

    <div class="busqueda-inventario" style="margin-bottom:12px;">
        <input class="form-control-pos" type="search" id="buscador-producto-compra" placeholder="Buscar por código de barras o nombre del producto" autocomplete="off">
        <button class="btn-pos-primary" type="button" id="btn-buscar-producto-compra">Buscar</button>
    </div>
    <div id="resultados-producto-compra" class="resultados-inventario" style="display:none;"></div>

    <div class="tabla-responsive">
        <table class="tabla-catalogo" id="tabla-compra-items">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock actual</th>
                    <th>Cantidad a ingresar</th>
                    <th>Costo unitario (L)</th>
                    <th>Subtotal (L)</th>
                    <th>Quitar</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-compra-items">
                <tr id="fila-vacia-compra"><td colspan="7" class="tabla-vacia">Aún no has agregado productos a esta factura.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">3. Totales de la factura</h2>
    <div class="form-grid">
        <div class="campo"><label for="descuento_factura">Descuento (L)</label><input id="descuento_factura" class="form-control-pos" type="number" min="0" step="0.01" value="0"></div>
        <div class="campo">
            <label for="tipo_isv">ISV aplicado</label>
            <select id="tipo_isv" class="form-control-pos">
                <option value="0">Exento (0%)</option>
                <option value="0.15" selected>15%</option>
                <option value="0.18">18%</option>
            </select>
        </div>
        <div class="campo"><label>Subtotal</label><div class="form-control-pos" id="total-subtotal" style="background:#f7fafc;">L 0.00</div></div>
        <div class="campo"><label>ISV</label><div class="form-control-pos" id="total-isv" style="background:#f7fafc;">L 0.00</div></div>
        <div class="campo"><label><strong>Total a pagar</strong></label><div class="form-control-pos" id="total-general" style="background:#f7fafc; font-weight:800; font-size:1.1rem;">L 0.00</div></div>
    </div>
    <div class="form-acciones">
        <button class="btn-pos btn-secondary" type="button" onclick="window.location.href='<?php echo URL_BASE; ?>inventario'">Cancelar</button>
        <button class="btn-pos-success" type="button" id="btn-guardar-compra">Registrar compra</button>
    </div>
</section>

<section class="tarjeta" style="margin-top:16px;">
    <h2 class="tarjeta-titulo">Últimas compras registradas</h2>
    <div class="tabla-responsive">
        <table class="tabla-catalogo">
            <thead><tr><th>Fecha</th><th>Proveedor</th><th>N.º Factura</th><th>Tipo</th><th>Total</th><th>Estado</th></tr></thead>
            <tbody>
                <?php if (!$historial): ?><tr><td colspan="6" class="tabla-vacia">No hay compras registradas.</td></tr><?php endif; ?>
                <?php foreach ($historial as $compra): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($compra['fecha']); ?></td>
                        <td><strong><?php echo htmlspecialchars($compra['proveedor']); ?></strong></td>
                        <td><?php echo htmlspecialchars($compra['numero_factura']); ?></td>
                        <td><?php echo htmlspecialchars($compra['tipo_documento']); ?></td>
                        <td>L <?php echo number_format((float)$compra['total'], 2); ?></td>
                        <td><?php echo htmlspecialchars($compra['estado']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    var itemsCompra = [];

    var campoCondicionPago = document.getElementById('condicion_pago');
    var campoDiasCredito = document.getElementById('dias_credito');
    campoCondicionPago.addEventListener('change', function () {
        var esCredito = this.value === 'credito';
        campoDiasCredito.disabled = !esCredito;
        if (!esCredito) { campoDiasCredito.value = ''; }
    });

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
        fetch('<?php echo URL_BASE; ?>compras/buscar-producto?termino=' + encodeURIComponent(termino))
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
        var id = producto.id || producto.producto_id;
        if (itemsCompra.some(function (item) { return item.id === id; })) {
            return;
        }
        itemsCompra.push({
            id: id,
            codigo: producto.codigo_barras || '',
            nombre: producto.nombre || '',
            stock: parseFloat(producto.stock || 0),
            cantidad: 1,
            costo: parseFloat(producto.precio_costo || 0)
        });
        renderizarTabla();
    }

    function quitarItem(id) {
        itemsCompra = itemsCompra.filter(function (item) { return item.id !== id; });
        renderizarTabla();
    }

    function renderizarTabla() {
        var cuerpo = document.getElementById('cuerpo-tabla-compra-items');
        cuerpo.innerHTML = '';
        if (!itemsCompra.length) {
            cuerpo.innerHTML = '<tr id="fila-vacia-compra"><td colspan="7" class="tabla-vacia">Aún no has agregado productos a esta factura.</td></tr>';
            calcularTotales();
            return;
        }
        itemsCompra.forEach(function (item) {
            var fila = document.createElement('tr');
            fila.innerHTML =
                '<td>' + item.codigo + '</td>' +
                '<td><strong>' + item.nombre + '</strong></td>' +
                '<td>' + item.stock + '</td>' +
                '<td><input type="number" min="1" step="1" class="form-control-pos input-cantidad" value="' + item.cantidad + '" style="width:100px;"></td>' +
                '<td><input type="number" min="0" step="0.01" class="form-control-pos input-costo" value="' + item.costo.toFixed(2) + '" style="width:120px;"></td>' +
                '<td class="celda-subtotal">L ' + (item.cantidad * item.costo).toFixed(2) + '</td>' +
                '<td><button type="button" class="btn-pos btn-danger btn-pequeno btn-quitar">Quitar</button></td>';

            fila.querySelector('.input-cantidad').addEventListener('input', function () {
                item.cantidad = parseFloat(this.value) || 0;
                fila.querySelector('.celda-subtotal').textContent = 'L ' + (item.cantidad * item.costo).toFixed(2);
                calcularTotales();
            });
            fila.querySelector('.input-costo').addEventListener('input', function () {
                item.costo = parseFloat(this.value) || 0;
                fila.querySelector('.celda-subtotal').textContent = 'L ' + (item.cantidad * item.costo).toFixed(2);
                calcularTotales();
            });
            fila.querySelector('.btn-quitar').addEventListener('click', function () { quitarItem(item.id); });

            cuerpo.appendChild(fila);
        });
        calcularTotales();
    }

    var campoDescuento = document.getElementById('descuento_factura');
    var selectIsv = document.getElementById('tipo_isv');
    campoDescuento.addEventListener('input', calcularTotales);
    selectIsv.addEventListener('change', calcularTotales);

    function calcularTotales() {
        var subtotal = itemsCompra.reduce(function (acumulado, item) { return acumulado + (item.cantidad * item.costo); }, 0);
        var descuento = parseFloat(campoDescuento.value) || 0;
        var baseImponible = Math.max(subtotal - descuento, 0);
        var isv = baseImponible * parseFloat(selectIsv.value || 0);
        var total = baseImponible + isv;

        document.getElementById('total-subtotal').textContent = 'L ' + subtotal.toFixed(2);
        document.getElementById('total-isv').textContent = 'L ' + isv.toFixed(2);
        document.getElementById('total-general').textContent = 'L ' + total.toFixed(2);
    }

    document.getElementById('btn-guardar-compra').addEventListener('click', function () {
        if (!document.getElementById('numero_factura').value || !document.getElementById('proveedor').value) {
            alert('Completa el proveedor y el número de factura antes de continuar.');
            return;
        }
        if (!itemsCompra.length) {
            alert('Agrega al menos un producto a la factura.');
            return;
        }
        alert('Esta es solo una vista previa del módulo de Compras. El guardado en base de datos aún no está implementado.');
    });

    calcularTotales();
}());
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
