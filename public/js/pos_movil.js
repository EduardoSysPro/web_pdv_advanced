/**
 * POS Móvil - Lógica Frontend táctil, gestión del carrito,
 * llamadas al backend y escáner de códigos de barras mediante cámara.
 */

document.addEventListener('DOMContentLoaded', function () {
    function obtenerCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Estado del carrito
    let carrito = [];
    let metodoPagoSeleccionado = 'efectivo';
    let clienteSeleccionado = null;
    let clavePrecioEditar = null;
    let html5QrCode = null;
    let isScannerRunning = false;
    let debounceBusqueda = null;
    let debounceCliente = null;
    const CLIENTES = (typeof CLIENTES_REGISTRADOS !== 'undefined' && Array.isArray(CLIENTES_REGISTRADOS)) ? CLIENTES_REGISTRADOS : [];

    // Elementos DOM principales
    const inputBusqueda = document.getElementById('movil-search-input');
    const contenedorResultados = document.getElementById('movil-search-results');
    const listaCarrito = document.getElementById('movil-cart-list');
    const placeholderVacio = document.getElementById('movil-empty-cart');
    const btnVaciar = document.getElementById('btn-vaciar-cart');
    const textoTotal = document.getElementById('movil-total-text');
    const btnAbrirCobro = document.getElementById('btn-abrir-cobro');

    // Elementos Escáner
    const btnOpenScanner = document.getElementById('btn-open-scanner');
    const btnCloseScanner = document.getElementById('btn-close-scanner');
    const scannerModal = document.getElementById('scanner-modal');
    const cameraFileInput = document.getElementById('camera-file-input');
    const btnFotoFallback = document.getElementById('btn-foto-fallback');
    const loadingOverlay = document.getElementById('movil-loading-overlay');
    const loadingText = document.getElementById('movil-loading-text');

    function mostrarOverlay(texto) {
        if (loadingText) loadingText.textContent = texto || 'Analizando código...';
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }

    function ocultarOverlay() {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }

    // Elementos Modal Cobro
    const modalCobro = document.getElementById('modal-cobro-movil');
    const modalConfirmarImprimir = document.getElementById('modal-confirmar-imprimir');
    const btnCerrarCobro = document.getElementById('btn-cerrar-cobro');
    const cobroTotalVal = document.getElementById('cobro-total-val');
    const inputEfectivo = document.getElementById('input-efectivo-touch');
    const cobroCambioVal = document.getElementById('cobro-cambio-val');
    const campoEfectivo = document.getElementById('campo-efectivo-touch');
    const resumenCambio = document.getElementById('resumen-cambio-touch');
    const btnFinalizarVenta = document.getElementById('btn-finalizar-venta');
    const btnConfirmarImprimir = document.getElementById('btn-confirmar-imprimir');
    const btnSinImprimir = document.getElementById('btn-sin-imprimir');
    const botonesMetodos = document.querySelectorAll('.btn-metodo-touch');
    const botonesBilletes = document.querySelectorAll('.btn-billete-touch');

    // Elementos Cliente / Crédito
    const resumenCredito = document.getElementById('resumen-credito-touch');
    const cobroCreditoVal = document.getElementById('cobro-credito-val');
    const btnToggleCliente = document.getElementById('btn-toggle-cliente');
    const clienteSelectLabel = document.getElementById('cliente-select-label');
    const clientePickerWrap = document.getElementById('cliente-picker-wrap');
    const inputCliente = document.getElementById('movil-input-cliente');
    const clienteResults = document.getElementById('movil-cliente-results');
    const clienteSeleccionadoPanel = document.getElementById('cliente-seleccionado');
    const clienteNombreTxt = document.getElementById('cliente-nombre-txt');
    const clienteDatosTxt = document.getElementById('cliente-datos-txt');
    const btnQuitarCliente = document.getElementById('btn-quitar-cliente');

    // Elementos Modal Precio / Descuento
    const modalPrecio = document.getElementById('modal-precio-movil');
    const btnCerrarPrecio = document.getElementById('btn-cerrar-precio');
    const precioTitulo = document.getElementById('precio-titulo');
    const precioListaVal = document.getElementById('precio-lista-val');
    const precioItemNombre = document.getElementById('precio-item-nombre');
    const inputPrecio = document.getElementById('input-precio-movil');
    const btnAplicarPrecio = document.getElementById('btn-aplicar-precio');
    const btnQuitarDescuento = document.getElementById('btn-quitar-descuento');
    const precioResumen = document.getElementById('precio-resumen');
    const botonesPorcentaje = document.querySelectorAll('.porcentajes-descuento button');

    // Elementos Historial de Ventas de Hoy
    const btnAbrirHistorial = document.getElementById('btn-abrir-historial');
    const modalHistorial = document.getElementById('modal-historial-movil');
    const btnCerrarHistorial = document.getElementById('btn-cerrar-historial');
    const btnRefrescarHistorial = document.getElementById('btn-refrescar-historial');
    const historialBody = document.getElementById('historial-body');

    // Recuperar carrito guardado si existe en sessionStorage
    try {
        const guardado = sessionStorage.getItem('pos_movil_carrito');
        if (guardado) {
            carrito = JSON.parse(guardado);
        }
    } catch (e) {
        carrito = [];
    }

    renderizarCarrito();

    // ==========================================
    // MANEJO DE BÚSQUEDA Y AGREGAR PRODUCTOS
    // ==========================================
    inputBusqueda.addEventListener('input', function () {
        clearTimeout(debounceBusqueda);
        const termino = this.value.trim();
        if (termino.length === 0) {
            contenedorResultados.style.display = 'none';
            contenedorResultados.innerHTML = '';
            return;
        }

        debounceBusqueda = setTimeout(() => {
            buscarProductos(termino);
        }, 200);
    });

    inputBusqueda.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const codigo = this.value.trim();
            if (codigo) {
                buscarPorCodigoDirecto(codigo);
            }
        }
    });

    function buscarProductos(termino) {
        fetch(URL_BASE + 'ventas/buscar-productos?q=' + encodeURIComponent(termino), {
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(productos => {
            if (!Array.isArray(productos) || productos.length === 0) {
                contenedorResultados.innerHTML = '<div style="padding: 12px; font-size: 13px; color: #64748b; text-align: center;">Sin coincidencias</div>';
                contenedorResultados.style.display = 'block';
                return;
            }

            contenedorResultados.innerHTML = productos.map((p, idx) => `
                <div class="movil-search-item" data-index="${idx}">
                    <div class="item-main-info">
                        <div class="item-name">${escapar(p.nombre)}</div>
                        <div class="item-barcode"><i class="fa-solid fa-barcode"></i> ${escapar(p.codigo_barras)}</div>
                    </div>
                    <div class="item-meta">
                        <div class="item-price">${MONEDA_SIMBOLO}${formatear(p.precio_venta)}</div>
                        <div class="item-stock">Stock: ${p.stock}</div>
                    </div>
                </div>
            `).join('');
            contenedorResultados.style.display = 'block';

            // Evento click a cada elemento de resultado
            contenedorResultados.querySelectorAll('.movil-search-item').forEach(item => {
                item.addEventListener('click', function () {
                    const idx = Number(this.dataset.index);
                    const prod = productos[idx];
                    agregarAlCarrito({
                        id: Number(prod.id),
                        item_key: prod.item_key || (prod.id + '_' + (prod.tipo_presentacion || 'unidad')),
                        codigo_barras: prod.codigo_barras,
                        nombre: prod.nombre,
                        precio_venta: Number(prod.precio_venta),
                        precio_lista: Number(prod.precio_venta),
                        precio_unitario: Number(prod.precio_venta),
                        descuento_unitario: 0,
                        stock: Number(prod.stock),
                        unidad_medida: prod.unidad_medida || 'unidad',
                        tipo_presentacion: prod.tipo_presentacion || 'unidad',
                        nombre_presentacion: prod.nombre_presentacion || 'Unidad',
                        factor_unidades: Number(prod.factor_unidades || 1)
                    });
                    inputBusqueda.value = '';
                    contenedorResultados.style.display = 'none';
                    contenedorResultados.innerHTML = '';
                });
            });
        })
        .catch(err => {
            console.error('Error en búsqueda:', err);
        });
    }

    function buscarPorCodigoDirecto(codigo) {
        const body = new URLSearchParams();
        body.append('codigo', codigo);
        body.append('csrf_token', obtenerCsrfToken());

        fetch(URL_BASE + 'ventas/buscar-producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.exito && data.producto) {
                agregarAlCarrito(data.producto);
                inputBusqueda.value = '';
                contenedorResultados.style.display = 'none';
                vibrar();
            } else {
                alert((data && data.mensaje) ? data.mensaje : 'Producto no encontrado');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al consultar el producto.');
        });
    }

    // ==========================================
    // OPERACIONES DEL CARRITO MÓVIL
    // ==========================================
    function agregarAlCarrito(producto) {
        const key = producto.item_key || String(producto.id);
        const existente = carrito.find(p => (p.item_key || String(p.id)) === key);

        if (existente) {
            existente.cantidad = Number((existente.cantidad + 1).toFixed(3));
        } else {
            carrito.push({
                ...producto,
                cantidad: 1,
                precio_lista: Number(producto.precio_lista ?? producto.precio_venta),
                precio_unitario: Number(producto.precio_unitario ?? producto.precio_venta),
                descuento_unitario: Number(producto.descuento_unitario || 0)
            });
        }

        guardarCarrito();
        renderizarCarrito();
    }

    function cambiarCantidad(key, delta) {
        const index = carrito.findIndex(p => (p.item_key || String(p.id)) === key);
        if (index === -1) return;

        const nuevaCant = Number((carrito[index].cantidad + delta).toFixed(3));
        if (nuevaCant <= 0) {
            carrito.splice(index, 1);
        } else {
            carrito[index].cantidad = nuevaCant;
        }

        guardarCarrito();
        renderizarCarrito();
    }

    function eliminarArticulo(key) {
        carrito = carrito.filter(p => (p.item_key || String(p.id)) !== key);
        guardarCarrito();
        renderizarCarrito();
    }

    btnVaciar.addEventListener('click', function () {
        if (carrito.length === 0) return;
        if (confirm('¿Vaciar toda la venta actual?')) {
            carrito = [];
            guardarCarrito();
            renderizarCarrito();
        }
    });

    let temporizadorGuardadoMovil = null;

    function persistirCarrito() {
        try {
            sessionStorage.setItem('pos_movil_carrito', JSON.stringify(carrito));
        } catch (e) {}
    }

    // "Debounce" del guardado: con muchos productos no se serializa el carrito
    // entero en cada toque del escáner. Se aplaza ~300 ms y se fuerza al salir.
    function guardarCarrito() {
        if (temporizadorGuardadoMovil) window.clearTimeout(temporizadorGuardadoMovil);
        temporizadorGuardadoMovil = window.setTimeout(function () {
            temporizadorGuardadoMovil = null;
            persistirCarrito();
        }, 300);
    }

    function guardarCarritoInmediato() {
        if (temporizadorGuardadoMovil) window.clearTimeout(temporizadorGuardadoMovil);
        temporizadorGuardadoMovil = null;
        persistirCarrito();
    }

    window.addEventListener('pagehide', guardarCarritoInmediato);
    window.addEventListener('beforeunload', guardarCarritoInmediato);

    function round2(n) {
        return Math.round((Number(n) || 0) * 100) / 100;
    }

    // ==========================================
    // DESCUENTO / EDICIÓN DE PRECIO POR ARTÍCULO
    // ==========================================
    function abrirModalPrecio(key) {
        const item = carrito.find(p => (p.item_key || String(p.id)) === key);
        if (!item) return;
        clavePrecioEditar = key;

        precioTitulo.textContent = 'Editar precio · ' + item.nombre;
        precioItemNombre.textContent = item.nombre;
        const lista = round2(item.precio_lista ?? item.precio_venta);
        precioListaVal.textContent = MONEDA_SIMBOLO + formatear(lista);
        inputPrecio.value = round2(item.precio_venta).toFixed(2);
        btnQuitarDescuento.style.display = (Number(item.descuento_unitario) > 0.004) ? 'inline-flex' : 'none';
        actualizarResumenPrecio();
        modalPrecio.classList.add('is-open');
        modalPrecio.setAttribute('aria-hidden', 'false');
        inputPrecio.focus();
    }

    function cerrarModalPrecio() {
        modalPrecio.classList.remove('is-open');
        modalPrecio.setAttribute('aria-hidden', 'true');
        clavePrecioEditar = null;
    }

    function actualizarResumenPrecio() {
        const item = carrito.find(p => (p.item_key || String(p.id)) === clavePrecioEditar);
        if (!item) return;
        const lista = round2(item.precio_lista ?? item.precio_venta);
        const final = round2(inputPrecio.value);

        if (final >= lista) {
            precioResumen.textContent = 'Sin descuento';
        } else {
            const desc = round2(lista - final);
            const por = lista > 0 ? Math.round((desc / lista) * 100) : 0;
            precioResumen.textContent = 'Descuento: ' + MONEDA_SIMBOLO + formatear(desc) + ' (' + por + '%)';
        }
    }

    function aplicarPrecioItem() {
        const item = carrito.find(p => (p.item_key || String(p.id)) === clavePrecioEditar);
        if (!item) return;
        const lista = round2(item.precio_lista ?? item.precio_venta);
        let final = round2(inputPrecio.value);

        if (final < 0) final = 0;
        if (final > lista) {
            alert('El precio no puede ser mayor que el precio de lista (' + MONEDA_SIMBOLO + formatear(lista) + ').');
            inputPrecio.value = lista.toFixed(2);
            final = lista;
        }

        item.precio_venta = final;
        item.precio_unitario = final;
        item.descuento_unitario = round2(lista - final);

        guardarCarrito();
        renderizarCarrito();
        cerrarModalPrecio();
    }

    btnCerrarPrecio.addEventListener('click', cerrarModalPrecio);
    btnAplicarPrecio.addEventListener('click', aplicarPrecioItem);
    inputPrecio.addEventListener('input', actualizarResumenPrecio);

    btnQuitarDescuento.addEventListener('click', function () {
        const item = carrito.find(p => (p.item_key || String(p.id)) === clavePrecioEditar);
        if (!item) return;
        const lista = round2(item.precio_lista ?? item.precio_venta);
        item.precio_venta = lista;
        item.precio_unitario = lista;
        item.descuento_unitario = 0;
        guardarCarrito();
        renderizarCarrito();
        cerrarModalPrecio();
    });

    botonesPorcentaje.forEach(btn => {
        btn.addEventListener('click', function () {
            const item = carrito.find(p => (p.item_key || String(p.id)) === clavePrecioEditar);
            if (!item) return;
            const por = Number(this.dataset.por) || 0;
            const lista = round2(item.precio_lista ?? item.precio_venta);
            inputPrecio.value = round2(lista * (1 - por / 100)).toFixed(2);
            actualizarResumenPrecio();
        });
    });

    // ==========================================
    // SELECCIÓN DE CLIENTE (opcional / requerido en crédito)
    // ==========================================
    function etiquetaCliente(c) {
        const saldo = Number(c.saldo_pendiente) || 0;
        return 'Saldo ' + MONEDA_SIMBOLO + formatear(saldo) + (c.rtn ? ' · RTN ' + c.rtn : '');
    }

    function esModoCredito() {
        return metodoPagoSeleccionado === 'credito';
    }

    function actualizarClienteUI() {
        if (esModoCredito() && !clienteSeleccionado) {
            clienteSelectLabel.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Cliente requerido (crédito)';
            clienteSelectLabel.classList.add('cliente-requerido');
        } else {
            clienteSelectLabel.innerHTML = '<i class="fa-solid fa-user"></i> Agregar cliente (opcional)';
            clienteSelectLabel.classList.remove('cliente-requerido');
        }

        if (clienteSeleccionado) {
            clienteNombreTxt.textContent = clienteSeleccionado.nombre;
            clienteDatosTxt.textContent = etiquetaCliente(clienteSeleccionado);
            clienteSeleccionadoPanel.style.display = 'flex';
            clientePickerWrap.style.display = 'none';
            btnToggleCliente.style.display = 'none';
        } else {
            clienteSeleccionadoPanel.style.display = 'none';
            btnToggleCliente.style.display = 'flex';
        }
    }

    function filtrarClientes() {
        const termino = (inputCliente.value || '').trim().toLowerCase();
        let lista = CLIENTES;
        if (termino) {
            lista = lista.filter(c => (c.nombre || '').toLowerCase().includes(termino) || (c.rtn || '').toLowerCase().includes(termino));
        }
        lista = lista.slice(0, 30);

        if (lista.length === 0) {
            clienteResults.innerHTML = '<div class="cliente-vacio">Sin clientes coincidentes</div>';
            return;
        }

        clienteResults.innerHTML = lista.map(c => `
            <div class="cliente-fila" data-id="${c.id}">
                <div class="cliente-fila-main">
                    <div class="cliente-fila-nombre">${escapar(c.nombre)}</div>
                    <div class="cliente-fila-sub">${escapar(etiquetaCliente(c))}</div>
                </div>
                <i class="fa-solid fa-plus"></i>
            </div>
        `).join('');

        clienteResults.querySelectorAll('.cliente-fila').forEach(row => {
            row.addEventListener('click', function () {
                const c = CLIENTES.find(x => x.id === Number(this.dataset.id));
                if (c) seleccionarCliente(c);
            });
        });
    }

    function seleccionarCliente(c) {
        clienteSeleccionado = c;
        actualizarClienteUI();
        actualizarEstadoCobro();
        vibrar();
    }

    btnToggleCliente.addEventListener('click', function () {
        const visible = clientePickerWrap.style.display !== 'none';
        clientePickerWrap.style.display = visible ? 'none' : 'block';
        if (!visible) {
            inputCliente.value = '';
            filtrarClientes();
            inputCliente.focus();
        }
    });

    inputCliente.addEventListener('input', function () {
        clearTimeout(debounceCliente);
        debounceCliente = setTimeout(filtrarClientes, 150);
    });

    btnQuitarCliente.addEventListener('click', function () {
        clienteSeleccionado = null;
        actualizarClienteUI();
        actualizarEstadoCobro();
    });

    // ==========================================
    // HISTORIAL DE VENTAS DE HOY
    // ==========================================
    function etiquetaMetodo(m) {
        const mapa = {
            'efectivo': 'Efectivo',
            'tarjeta': 'Tarjeta',
            'transferencia': 'Transferencia',
            'credito': 'Crédito'
        };
        return mapa[m] || m || '—';
    }

    function reimprimirVenta(id, btn) {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }

        if (IMPRESORA_LAN_ACTIVA) {
            fetch(URL_BASE + 'impresora/imprimir-venta/' + encodeURIComponent(id), { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: obtenerCsrfToken() }) })
                .then(r => r.json())
                .then(info => {
                    alert(info && info.exito === true ? 'Ticket enviado a la impresora LAN.' : 'No se pudo imprimir por LAN: ' + (info && info.mensaje || 'error desconocido'));
                })
                .catch(() => alert('No se pudo conectar con la impresora LAN.'))
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-print"></i>';
                    }
                });
        } else {
            if (typeof PDV_WEBAPP !== 'undefined' && PDV_WEBAPP.abrirTicket) {
                PDV_WEBAPP.abrirTicket(URL_BASE + 'ventas/ticket/' + encodeURIComponent(id));
            } else {
                window.open(URL_BASE + 'ventas/ticket/' + encodeURIComponent(id), '_blank');
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-print"></i>';
            }
        }
    }

    function renderHistorial(ventas) {
        if (!ventas || ventas.length === 0) {
            historialBody.innerHTML = `
                <div class="historial-vacio">
                    <i class="fa-solid fa-receipt"></i>
                    <p>Aún no hay ventas registradas hoy.</p>
                </div>`;
            return;
        }

        const totalDia = ventas.reduce((acc, x) => acc + (Number(x.total) || 0), 0);

        historialBody.innerHTML = `
            <div class="historial-total-dia">
                <span>Total del día</span>
                <span>${MONEDA_SIMBOLO}${formatear(totalDia)}</span>
            </div>
            ${ventas.map(x => `
                <div class="historial-fila">
                    <div class="hist-fila-info">
                        <div class="hist-fila-top">
                            <span class="hist-folio">${escapar(x.folio)}</span>
                            <span class="hist-hora">${escapar(x.hora)}</span>
                        </div>
                        <div class="hist-fila-sub">${escapar(x.cliente)} · ${escapar(etiquetaMetodo(x.metodo_pago))}</div>
                    </div>
                    <div class="hist-fila-accion">
                        <span class="hist-total">${MONEDA_SIMBOLO}${formatear(x.total)}</span>
                        <button type="button" class="btn-reimprimir-hist" data-id="${x.id}" title="Reimprimir">
                            <i class="fa-solid fa-print"></i>
                        </button>
                    </div>
                </div>
            `).join('')}`;

        historialBody.querySelectorAll('.btn-reimprimir-hist').forEach(btn => {
            btn.addEventListener('click', function () {
                reimprimirVenta(this.dataset.id, this);
            });
        });
    }

    function cargarHistorialHoy() {
        historialBody.innerHTML = '<div class="historial-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando ventas de hoy...</div>';
        fetch(URL_BASE + 'ventas/historial-hoy', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.exito) {
                    historialBody.innerHTML = '<div class="historial-vacio"><i class="fa-solid fa-triangle-exclamation"></i><p>No se pudo consultar el historial.</p></div>';
                    return;
                }
                renderHistorial(data.ventas);
            })
            .catch(() => {
                historialBody.innerHTML = '<div class="historial-vacio"><i class="fa-solid fa-triangle-exclamation"></i><p>No se pudo conectar con el servidor.</p></div>';
            });
    }

    btnAbrirHistorial.addEventListener('click', function () {
        modalHistorial.classList.add('is-open');
        modalHistorial.setAttribute('aria-hidden', 'false');
        cargarHistorialHoy();
    });

    btnCerrarHistorial.addEventListener('click', function () {
        modalHistorial.classList.remove('is-open');
        modalHistorial.setAttribute('aria-hidden', 'true');
    });

    btnRefrescarHistorial.addEventListener('click', cargarHistorialHoy);

    function calcularTotal() {
        return carrito.reduce((acc, item) => acc + (Number(item.precio_venta) * Number(item.cantidad)), 0);
    }

    function construirItemMovilHtml(item) {
        const key = item.item_key || String(item.id);
        const subtotal = Number(item.precio_venta) * Number(item.cantidad);
        const descuentoUnit = Number(item.descuento_unitario || 0);
        const badgeDesc = descuentoUnit > 0.004
            ? `<span class="badge-descuento">-${MONEDA_SIMBOLO}${formatear(descuentoUnit)}</span>`
            : '';

        return `
            <div class="movil-cart-item" data-key="${key}">
                <div class="cart-item-row-top">
                    <div class="cart-item-desc">
                        <div class="cart-item-title">${escapar(item.nombre)}</div>
                        <div class="cart-item-unit-price">
                            ${MONEDA_SIMBOLO}${formatear(item.precio_venta)} c/u${badgeDesc}
                        </div>
                    </div>
                    <div class="cart-item-subtotal">${MONEDA_SIMBOLO}${formatear(subtotal)}</div>
                </div>
                <div class="cart-item-row-bottom">
                    <div class="cart-item-controls">
                        <button type="button" class="btn-price-touch" data-action="precio" data-key="${key}" title="Editar precio o aplicar descuento">
                            <i class="fa-solid fa-tags"></i>
                        </button>
                        <button type="button" class="btn-qty-touch" data-action="restar" data-key="${key}">-</button>
                        <span class="cart-item-qty">${item.cantidad}</span>
                        <button type="button" class="btn-qty-touch" data-action="sumar" data-key="${key}">+</button>
                    </div>
                    <button type="button" class="btn-remove-touch" data-action="borrar" data-key="${key}">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function firmaItemMovil(item) {
        return [item.nombre, item.precio_venta, item.cantidad, item.descuento_unitario || 0].join('\u0001');
    }

    function renderizarCarrito() {
        if (carrito.length === 0) {
            listaCarrito.innerHTML = '';
            placeholderVacio.style.display = 'block';
            btnVaciar.style.display = 'none';
            btnAbrirCobro.disabled = true;
            textoTotal.textContent = MONEDA_SIMBOLO + '0.00';
            return;
        }

        placeholderVacio.style.display = 'none';
        btnVaciar.style.display = 'inline-flex';
        btnAbrirCobro.disabled = false;

        textoTotal.textContent = MONEDA_SIMBOLO + formatear(calcularTotal());

        // Delegación de eventos una sola vez (los botones del carrito no se rebinden en cada render)
        if (!listaCarrito.dataset.delegado) {
            listaCarrito.dataset.delegado = '1';
            listaCarrito.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-action]');
                if (!btn || !btn.dataset.key) return;
                const key = btn.dataset.key;
                const action = btn.dataset.action;
                if (action === 'sumar' || action === 'restar') cambiarCantidad(key, action === 'sumar' ? 1 : -1);
                else if (action === 'precio') abrirModalPrecio(key);
                else if (action === 'borrar') eliminarArticulo(key);
            });
        }

        // Reconciliación: reutiliza los nodos existentes (clave = item_key) y solo
        // regenera el HTML del artículo cuyo contenido cambió. Con muchos artículos
        // agregar uno nuevo cuesta O(1) en vez de reconstruir todo el carrito.
        const filasExistentes = new Map();
        Array.prototype.forEach.call(listaCarrito.children, function (nodo) {
            if (nodo && nodo.dataset && nodo.dataset.key) filasExistentes.set(nodo.dataset.key, nodo);
        });

        carrito.forEach(item => {
            const key = item.item_key || String(item.id);
            const firma = firmaItemMovil(item);
            let nodo = filasExistentes.get(key);
            if (nodo) filasExistentes.delete(key);
            if (nodo && nodo.dataset.firma === firma) return;

            const contenedor = document.createElement('div');
            contenedor.innerHTML = construirItemMovilHtml(item);
            const nuevoNodo = contenedor.firstElementChild;
            nuevoNodo.dataset.firma = firma;
            if (nodo) {
                nodo.replaceWith(nuevoNodo);
            } else {
                listaCarrito.appendChild(nuevoNodo);
            }
        });

        // Elimina nodos de artículos que ya no están en el carrito
        filasExistentes.forEach(function (nodo) { nodo.remove(); });
    }

    // ==========================================
    // ESCÁNER MULTI-MOTOR (BarcodeDetector + Quagga + Html5Qrcode)
    // ==========================================
    if (btnOpenScanner) {
        btnOpenScanner.addEventListener('click', function () {
            // En iOS Safari / Chrome por HTTP en LAN, navigator.mediaDevices no existe
            const tieneSoporteStream = Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            if (!tieneSoporteStream) {
                // Activar cámara nativa del teléfono directamente
                abrirCapturaFoto();
                return;
            }
            iniciarEscanerCamara();
        });
    }

    if (btnCloseScanner) {
        btnCloseScanner.addEventListener('click', detenerEscanerCamara);
    }

    if (btnFotoFallback) {
        btnFotoFallback.addEventListener('click', function () {
            detenerEscanerCamara();
            abrirCapturaFoto();
        });
    }

    if (cameraFileInput) {
        cameraFileInput.addEventListener('change', async function (e) {
            const archivos = e.target.files;
            if (!archivos || archivos.length === 0) return;
            const archivoImagen = archivos[0];

            try {
                mostrarOverlay('Analizando código de barras...');
                const codigoDecodificado = await procesarImagenMultiMotor(archivoImagen);
                cameraFileInput.value = '';
                ocultarOverlay();

                if (codigoDecodificado) {
                    vibrar();
                    buscarPorCodigoDirecto(codigoDecodificado);
                } else {
                    alert('No se detectó un código de barras claro.\n\nRecomendaciones:\n1. Acerca más la cámara al código de barras.\n2. Asegúrate de enfocar con buena iluminación.\n3. O escribe el código directamente en el buscador.');
                }
            } catch (err) {
                cameraFileInput.value = '';
                ocultarOverlay();
                console.error('Error al decodificar imagen:', err);
                alert('Ocurrió un error al procesar la foto del código.');
            }
        });
    }

    function abrirCapturaFoto() {
        if (cameraFileInput) {
            cameraFileInput.click();
        }
    }

    // ==========================================
    // PIPELINE DE DECODIFICACIÓN MULTI-MOTOR
    // ==========================================
    async function procesarImagenMultiMotor(archivoImagen) {
        // 1. Motor 1: BarcodeDetector nativo por hardware (soportado en iOS 17+, Chrome y Edge)
        if ('BarcodeDetector' in window) {
            try {
                mostrarOverlay('Escaneo hardware rápido...');
                const bitmap = await createImageBitmap(archivoImagen);
                const codigoNativo = await decodificarConBarcodeDetector(bitmap);
                if (codigoNativo) return codigoNativo;
            } catch (e) {
                console.warn('Fallo BarcodeDetector directo:', e);
            }
        }

        // 2. Preprocesamiento de imagen en Canvas
        mostrarOverlay('Mejorando resolución y enfoque...');
        const { img, url } = await cargarImagenDesdeArchivo(archivoImagen);

        try {
            const variantes = generarVariantesCanvas(img);

            for (let i = 0; i < variantes.length; i++) {
                const canvas = variantes[i];
                mostrarOverlay(`Analizando variante ${i + 1} de ${variantes.length}...`);

                // Intentar BarcodeDetector sobre canvas
                const resNat = await decodificarConBarcodeDetector(canvas);
                if (resNat) {
                    URL.revokeObjectURL(url);
                    return resNat;
                }

                // Intentar Quagga2 (motor especializado en códigos 1D: EAN, UPC, Code 128)
                const resQuagga = await decodificarConQuagga(canvas);
                if (resQuagga) {
                    URL.revokeObjectURL(url);
                    return resQuagga;
                }
            }

            // 3. Probar con filtro de contraste aumentado
            mostrarOverlay('Optimizando contraste y sombras...');
            const canvasContraste = aplicarRealceContraste(variantes[2] || variantes[0]);
            const resContraste = (await decodificarConBarcodeDetector(canvasContraste)) || (await decodificarConQuagga(canvasContraste));
            if (resContraste) {
                URL.revokeObjectURL(url);
                return resContraste;
            }

            // 4. Intentar con Html5Qrcode / ZXing
            if (window.Html5Qrcode) {
                try {
                    mostrarOverlay('Decodificación profunda ZXing...');
                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("reader", {
                            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                            verbose: false
                        });
                    }
                    const resZXing = await html5QrCode.scanFile(archivoImagen, false);
                    if (resZXing) {
                        URL.revokeObjectURL(url);
                        return resZXing;
                    }
                } catch (e) {}
            }

            URL.revokeObjectURL(url);
            return null;
        } catch (err) {
            URL.revokeObjectURL(url);
            throw err;
        }
    }

    function cargarImagenDesdeArchivo(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => resolve({ img, url });
            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('No se pudo cargar la imagen para escaneo.'));
            };
            img.src = url;
        });
    }

    function decodificarConBarcodeDetector(fuente) {
        if (!('BarcodeDetector' in window)) return Promise.resolve(null);
        return new Promise(async (resolve) => {
            try {
                const formatos = ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'itf', 'qr_code'];
                const detector = new BarcodeDetector({ formats: formatos });
                const resultados = await detector.detect(fuente);
                if (resultados && resultados.length > 0 && resultados[0].rawValue) {
                    resolve(resultados[0].rawValue.trim());
                    return;
                }
            } catch (e) {
                console.warn('BarcodeDetector detect error:', e);
            }
            resolve(null);
        });
    }

    function decodificarConQuagga(canvas) {
        if (typeof Quagga === 'undefined') return Promise.resolve(null);
        return new Promise((resolve) => {
            try {
                const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                Quagga.decodeSingle({
                    src: dataUrl,
                    numOfWorkers: 0,
                    inputStream: {
                        size: 1200
                    },
                    decoder: {
                        readers: [
                            "ean_reader",
                            "code_128_reader",
                            "upc_reader",
                            "upce_reader",
                            "ean_8_reader",
                            "code_39_reader",
                            "i2of5_reader"
                        ]
                    },
                    locate: true
                }, function (resultado) {
                    if (resultado && resultado.codeResult && resultado.codeResult.code) {
                        resolve(resultado.codeResult.code.trim());
                    } else {
                        resolve(null);
                    }
                });
            } catch (e) {
                console.warn('Quagga decodeSingle error:', e);
                resolve(null);
            }
        });
    }

    function generarVariantesCanvas(img) {
        const origW = img.naturalWidth || img.width;
        const origH = img.naturalHeight || img.height;
        const maxDim = 1280;

        const crearCanvas = (sx, sy, sw, sh, rotacion) => {
            const scale = Math.min(1, maxDim / Math.max(sw, sh));
            const dw = Math.round(sw * scale);
            const dh = Math.round(sh * scale);
            const c = document.createElement('canvas');
            const ctx = c.getContext('2d');

            if (rotacion === 90 || rotacion === 270) {
                c.width = dh;
                c.height = dw;
            } else {
                c.width = dw;
                c.height = dh;
            }

            ctx.save();
            if (rotacion === 90) {
                ctx.translate(dh, 0);
                ctx.rotate(90 * Math.PI / 180);
            } else if (rotacion === 180) {
                ctx.translate(dw, dh);
                ctx.rotate(180 * Math.PI / 180);
            } else if (rotacion === 270) {
                ctx.translate(0, dw);
                ctx.rotate(270 * Math.PI / 180);
            }
            ctx.drawImage(img, sx, sy, sw, sh, 0, 0, dw, dh);
            ctx.restore();
            return c;
        };

        // 1. Imagen completa normal escalada a tamaño óptimo
        const v1 = crearCanvas(0, 0, origW, origH, 0);

        // 2. Imagen rotada 90° (para fotos tomadas verticalmente con el móvil)
        const v2 = crearCanvas(0, 0, origW, origH, 90);

        // 3. Recorte centrado (75% central donde se ubica el código)
        const cropW = Math.round(origW * 0.75);
        const cropH = Math.round(origH * 0.75);
        const cropX = Math.round((origW - cropW) / 2);
        const cropY = Math.round((origH - cropH) / 2);
        const v3 = crearCanvas(cropX, cropY, cropW, cropH, 0);

        // 4. Recorte centrado rotado 90°
        const v4 = crearCanvas(cropX, cropY, cropW, cropH, 90);

        return [v1, v2, v3, v4];
    }

    function aplicarRealceContraste(canvas) {
        const c = document.createElement('canvas');
        c.width = canvas.width;
        c.height = canvas.height;
        const ctx = c.getContext('2d');
        ctx.drawImage(canvas, 0, 0);

        try {
            const imgData = ctx.getImageData(0, 0, c.width, c.height);
            const d = imgData.data;
            const factor = 1.35;
            for (let i = 0; i < d.length; i += 4) {
                const gray = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
                const res = factor * (gray - 128) + 128;
                const val = Math.min(255, Math.max(0, res));
                d[i] = val;
                d[i + 1] = val;
                d[i + 2] = val;
            }
            ctx.putImageData(imgData, 0, 0);
        } catch (e) {}

        return c;
    }

    // ==========================================
    // ESCÁNER EN VIVO (VIDEO STREAMING)
    // ==========================================
    function iniciarEscanerCamara() {
        if (!window.Html5Qrcode) {
            alert('Librería de escáner no disponible.');
            return;
        }

        scannerModal.classList.add('is-open');

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader", {
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                verbose: false
            });
        }

        const config = {
            fps: 20,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const w = Math.floor(viewfinderWidth * 0.88);
                const h = Math.floor(viewfinderHeight * 0.45);
                return {
                    width: Math.max(w, 260),
                    height: Math.max(h, 150)
                };
            },
            aspectRatio: 1.0,
            videoConstraints: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1920, min: 1280 },
                height: { ideal: 1080, min: 720 },
                focusMode: { ideal: "continuous" }
            }
        };

        const onScanSuccess = (decodedText) => {
            detenerEscanerCamara();
            vibrar();
            buscarPorCodigoDirecto(decodedText);
        };

        const onScanFailure = () => {
            // Cuadros sin detección (continuar escaneando)
        };

        // Enumerar cámaras reales
        Html5Qrcode.getCameras().then(devices => {
            if (!devices || devices.length === 0) {
                detenerEscanerCamara();
                alert('No se detectó ninguna cámara disponible en este dispositivo.');
                return;
            }

            // Preferir cámara trasera
            let camaraElegida = devices[0].id;
            const camaraTrasera = devices.find(d => {
                const label = (d.label || '').toLowerCase();
                return label.includes('back') || label.includes('trasera') || label.includes('rear') || label.includes('environment');
            });
            if (camaraTrasera) {
                camaraElegida = camaraTrasera.id;
            } else if (devices.length > 1) {
                camaraElegida = devices[devices.length - 1].id;
            }

            html5QrCode.start(camaraElegida, config, onScanSuccess, onScanFailure)
                .then(() => {
                    isScannerRunning = true;
                })
                .catch(err => {
                    console.warn('Fallo al iniciar por ID, intentando con facingMode:', err);
                    iniciarConFacingMode(config, onScanSuccess, onScanFailure);
                });
        }).catch(err => {
            console.warn('No se pudo listar cámaras con getCameras:', err);
            iniciarConFacingMode(config, onScanSuccess, onScanFailure);
        });
    }

    function iniciarConFacingMode(config, onScanSuccess, onScanFailure) {
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
            .then(() => {
                isScannerRunning = true;
            })
            .catch(err => {
                // Si la PC no tiene cámara trasera, intentar frontal
                html5QrCode.start({ facingMode: "user" }, config, onScanSuccess, onScanFailure)
                    .then(() => {
                        isScannerRunning = true;
                    })
                    .catch(errFinal => {
                        console.error('Error total al iniciar cámara:', errFinal);
                        detenerEscanerCamara();
                        
                        let mensaje = 'No se pudo acceder a la cámara en vivo.';
                        if (errFinal && errFinal.name === 'NotAllowedError') {
                            mensaje = 'Permiso de cámara denegado. Habilita los permisos en la barra de direcciones de tu navegador.';
                        } else if (errFinal && errFinal.name === 'NotFoundError') {
                            mensaje = 'No se encontró ninguna cámara conectada en este equipo.';
                        }
                        
                        if (confirm(mensaje + '\n\n¿Deseas tomar una foto al código de barras en su lugar?')) {
                            abrirCapturaFoto();
                        }
                    });
            });
    }

    function detenerEscanerCamara() {
        if (html5QrCode && isScannerRunning) {
            html5QrCode.stop().then(() => {
                isScannerRunning = false;
                scannerModal.classList.remove('is-open');
            }).catch(() => {
                isScannerRunning = false;
                scannerModal.classList.remove('is-open');
            });
        } else {
            isScannerRunning = false;
            scannerModal.classList.remove('is-open');
        }
    }

    // ==========================================
    // FLUJO DE COBRO MÓVIL
    // ==========================================
    btnAbrirCobro.addEventListener('click', function () {
        if (carrito.length === 0) return;
        const total = calcularTotal();
        cobroTotalVal.textContent = MONEDA_SIMBOLO + formatear(total);
        inputEfectivo.value = '';

        // Resetear estado de cobro
        metodoPagoSeleccionado = 'efectivo';
        botonesMetodos.forEach(b => b.classList.remove('is-selected'));
        botonesMetodos[0].classList.add('is-selected');
        clienteSeleccionado = null;
        actualizarClienteUI();
        clientePickerWrap.style.display = 'none';
        campoEfectivo.style.display = 'block';
        resumenCambio.style.display = 'flex';
        resumenCredito.style.display = 'none';

        actualizarEstadoCobro();
        modalCobro.classList.add('is-open');
        inputEfectivo.focus();
    });

    btnCerrarCobro.addEventListener('click', function () {
        modalCobro.classList.remove('is-open');
    });

    // Métodos de pago
    botonesMetodos.forEach(btn => {
        btn.addEventListener('click', function () {
            botonesMetodos.forEach(b => b.classList.remove('is-selected'));
            this.classList.add('is-selected');
            metodoPagoSeleccionado = this.dataset.metodo;

            const esCredito = metodoPagoSeleccionado === 'credito';
            const esEfectivo = metodoPagoSeleccionado === 'efectivo';

            campoEfectivo.style.display = esEfectivo ? 'block' : 'none';
            resumenCambio.style.display = esEfectivo ? 'flex' : 'none';
            resumenCredito.style.display = esCredito ? 'flex' : 'none';

            if (esCredito) {
                cobroCreditoVal.textContent = MONEDA_SIMBOLO + formatear(calcularTotal());
                if (!clienteSeleccionado) {
                    clientePickerWrap.style.display = 'block';
                    inputCliente.focus();
                }
            } else {
                clientePickerWrap.style.display = 'none';
            }

            actualizarClienteUI();
            actualizarEstadoCobro();
        });
    });

    // Billetes rápidos
    botonesBilletes.forEach(btn => {
        btn.addEventListener('click', function () {
            const monto = this.dataset.monto;
            const total = calcularTotal();

            if (monto === 'exacto') {
                inputEfectivo.value = total.toFixed(2);
            } else {
                const valorActual = Number(inputEfectivo.value) || 0;
                inputEfectivo.value = (valorActual + Number(monto)).toFixed(2);
            }
            actualizarEstadoCobro();
        });
    });

    inputEfectivo.addEventListener('input', actualizarEstadoCobro);

    function actualizarEstadoCobro() {
        const total = calcularTotal();

        if (metodoPagoSeleccionado === 'credito') {
            if (clienteSeleccionado) {
                cobroCreditoVal.textContent = MONEDA_SIMBOLO + formatear(total);
            }
            if (!clienteSeleccionado) {
                btnFinalizarVenta.disabled = true;
                return;
            }

            const saldo = Number(clienteSeleccionado.saldo_pendiente) || 0;
            const limite = Number(clienteSeleccionado.limite_credito) || 0;
            const excede = (saldo + total) > (limite + 0.001);

            if (excede) {
                clienteDatosTxt.textContent = 'Excede el límite de crédito (' + MONEDA_SIMBOLO + formatear(limite) + ')';
                clienteDatosTxt.classList.add('cliente-aviso-error');
                btnFinalizarVenta.disabled = true;
            } else {
                clienteDatosTxt.classList.remove('cliente-aviso-error');
                btnFinalizarVenta.disabled = false;
            }
            return;
        }

        if (metodoPagoSeleccionado !== 'efectivo') {
            btnFinalizarVenta.disabled = false;
            return;
        }

        const efectivo = Number(inputEfectivo.value) || 0;
        const cambio = efectivo - total;
        if (efectivo >= total && total > 0) {
            cobroCambioVal.textContent = MONEDA_SIMBOLO + formatear(cambio);
            cobroCambioVal.classList.remove('insuficiente');
            btnFinalizarVenta.disabled = false;
        } else {
            const faltante = total - efectivo;
            cobroCambioVal.textContent = 'Faltan ' + MONEDA_SIMBOLO + formatear(faltante);
            cobroCambioVal.classList.add('insuficiente');
            btnFinalizarVenta.disabled = true;
        }
    }

    function abrirConfirmacionImpresion() {
        if (!modalConfirmarImprimir) return false;
        modalConfirmarImprimir.classList.add('is-open');
        modalConfirmarImprimir.setAttribute('aria-hidden', 'false');
        return true;
    }

    function cerrarConfirmacionImpresion() {
        if (!modalConfirmarImprimir) return;
        modalConfirmarImprimir.classList.remove('is-open');
        modalConfirmarImprimir.setAttribute('aria-hidden', 'true');
    }

    function procesarVenta(imprimirRecibo) {
        const total = calcularTotal();
        const esCredito = metodoPagoSeleccionado === 'credito';
        const efectivo = esCredito
            ? total
            : (metodoPagoSeleccionado === 'efectivo' ? (Number(inputEfectivo.value) || total) : total);
        const cambio = esCredito ? 0 : (metodoPagoSeleccionado === 'efectivo' ? (efectivo - total) : 0);

        btnFinalizarVenta.disabled = true;
        btnFinalizarVenta.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

        const payload = {
            csrf_token: obtenerCsrfToken(),
            total: total,
            efectivo: efectivo,
            cambio: cambio,
            metodo_pago: metodoPagoSeleccionado,
            tipo_comprobante: 'recibo',
            imprimir_recibo: imprimirRecibo,
            cliente_id: clienteSeleccionado ? Number(clienteSeleccionado.id) : 0,
            cliente_nombre: clienteSeleccionado ? (clienteSeleccionado.nombre || 'Consumidor Final') : 'Consumidor Final',
            cliente_rtn: clienteSeleccionado ? (clienteSeleccionado.rtn || '') : '',
            cliente_telefono: clienteSeleccionado ? (clienteSeleccionado.telefono || '') : '',
            cliente_direccion: clienteSeleccionado ? (clienteSeleccionado.direccion || '') : '',
            ...(window.PDV_SUPERVISOR_AUTH ? { supervisor_usuario: window.PDV_SUPERVISOR_AUTH.u, supervisor_password: window.PDV_SUPERVISOR_AUTH.p } : {}),
            productos: carrito.map(item => ({
                ...item,
                precio_lista: Number(item.precio_lista ?? item.precio_venta),
                precio_unitario: Number(item.precio_unitario ?? item.precio_venta),
                descuento_unitario: Number(item.descuento_unitario || 0),
                cantidad: Number(parseFloat(String(item.cantidad)).toFixed(3))
            }))
        };

        fetch(URL_BASE + 'ventas/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=UTF-8' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(resp => {
            btnFinalizarVenta.disabled = false;
            btnFinalizarVenta.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';

            window.PDV_SUPERVISOR_AUTH = null;
            if (!resp || !resp.exito) {
                // P4: límite excedido -> pedir supervisor y reintentar con autorización.
                if (resp && esCredito && (resp.requiere_autorizacion || resp.codigo === 'SUPERVISOR_INVALIDO')) {
                    cerrarConfirmacionImpresion();
                    const supUser = prompt('La venta excede el crédito disponible.\n' + (resp.mensaje || '') + '\n\nUsuario del supervisor (admin):');
                    if (!supUser) {
                        btnFinalizarVenta.disabled = false;
                        btnFinalizarVenta.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
                        return;
                    }
                    const supPass = prompt('Contraseña del supervisor ' + supUser + ':');
                    if (!supPass) {
                        btnFinalizarVenta.disabled = false;
                        btnFinalizarVenta.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
                        return;
                    }
                    window.PDV_SUPERVISOR_AUTH = { u: supUser, p: supPass };
                    procesarVenta(imprimirRecibo);
                    return;
                }
                alert((resp && resp.mensaje) ? resp.mensaje : 'Error al procesar la venta.');
                cerrarConfirmacionImpresion();
                return;
            }

            if (imprimirRecibo) {
                if (IMPRESORA_LAN_ACTIVA) {
                    fetch(URL_BASE + 'impresora/imprimir-venta/' + encodeURIComponent(resp.venta_id), { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: obtenerCsrfToken() }) })
                        .then(r => r.json())
                        .then(info => alert(info && info.exito === true ? 'Ticket impreso en la impresora LAN.' : 'No se pudo imprimir por LAN: ' + (info && info.mensaje || 'error desconocido')))
                        .catch(() => alert('No se pudo imprimir por la impresora LAN.'));
                } else {
                    const ticketUrl = URL_BASE + 'ventas/ticket/' + encodeURIComponent(resp.venta_id);
                    if (typeof PDV_WEBAPP !== 'undefined' && PDV_WEBAPP.abrirTicket) {
                        PDV_WEBAPP.abrirTicket(ticketUrl);
                    } else {
                        window.open(ticketUrl, '_blank');
                    }
                }
            }

            // Limpiar carrito y cerrar modal
            carrito = [];
            guardarCarrito();
            renderizarCarrito();
            clienteSeleccionado = null;
            actualizarClienteUI();
            modalCobro.classList.remove('is-open');
            cerrarConfirmacionImpresion();

            alert('¡Venta registrada exitosamente! Folio: ' + (resp.folio || resp.venta_id));
        })
        .catch(err => {
            console.error('Error al guardar venta:', err);
            btnFinalizarVenta.disabled = false;
            btnFinalizarVenta.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Cobro';
            cerrarConfirmacionImpresion();
            alert('No se pudo conectar con el servidor.');
        });
    }

    btnFinalizarVenta.addEventListener('click', function () {
        if (carrito.length === 0) return;
        abrirConfirmacionImpresion();
    });

    btnConfirmarImprimir.addEventListener('click', function () {
        procesarVenta(true);
    });

    btnSinImprimir.addEventListener('click', function () {
        procesarVenta(false);
    });

    // ==========================================
    // HELPERS
    // ==========================================
    function formatear(numero) {
        return Number(numero || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapar(texto) {
        if (texto == null) return '';
        const d = document.createElement('div');
        d.textContent = String(texto);
        return d.innerHTML;
    }

    function vibrar() {
        if (navigator.vibrate) {
            navigator.vibrate(80);
        }
    }
});
