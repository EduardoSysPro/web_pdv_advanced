/* ============================================================
   Web PDV - Lógica del Módulo de Ventas (POS)
   Incluye:
    - Multi-ticket dinámico (pestañas)
    - Persistencia en sessionStorage
    - Búsqueda de producto por AJAX
    - Captura de datos de facturación (SAR / Cliente)
    - Atajos de teclado (Enter, F1..F12, Del, Ins...)
   Todos los comentarios en español.
   ============================================================ */

(function () {
    'use strict';

    // ============================================================
    // CONFIGURACIÓN Y ESTADO GLOBAL
    // ============================================================

    const CLAVE_SESSION_STORAGE = 'web_pdv_tickets';
    const CLAVE_TICKET_ACTIVO  = 'web_pdv_ticket_activo';

    /**
     * Estado del POS:
     *  - tickets: [ { id, nombre, productos: [...], pagadoCon, cambio } ]
     *  - ticketActivo: id del ticket seleccionado
     */
    let estado = {
        tickets: [],
        ticketActivo: null,
        contadorTickets: 0
    };

    // ============================================================
    // FUNCIONES DE PERSISTENCIA (sessionStorage)
    // ============================================================

    /**
     * Guarda el estado completo de los tickets en sessionStorage.
     * Se invoca en cada operación que modifique los datos.
     */
    function guardarEstado() {
        try {
            sessionStorage.setItem(CLAVE_SESSION_STORAGE, JSON.stringify(estado.tickets));
            sessionStorage.setItem(CLAVE_TICKET_ACTIVO, String(estado.ticketActivo));
        } catch (e) {
            console.error('No se pudo guardar el estado en sessionStorage:', e);
        }
    }

    /**
     * Carga el estado desde sessionStorage (tras un F5 o recarga).
     * Si no existe información, crea un ticket inicial por defecto.
     */
    function cargarEstado() {
        try {
            const ticketsGuardados = sessionStorage.getItem(CLAVE_SESSION_STORAGE);
            const ticketActivoGuardado = sessionStorage.getItem(CLAVE_TICKET_ACTIVO);

            if (ticketsGuardados) {
                const parseados = JSON.parse(ticketsGuardados);
                if (Array.isArray(parseados) && parseados.length > 0) {
                    parseados.forEach(t => {
                        if (Array.isArray(t.productos)) {
                            t.productos.forEach(p => {
                                p.tipo_presentacion = p.tipo_presentacion || 'unidad';
                                p.nombre_presentacion = p.nombre_presentacion || (p.tipo_presentacion === 'empaque' ? 'Caja' : 'Unidad');
                                p.factor_unidades = Math.max(1, Number(p.factor_unidades || 1));
                                p.item_key = p.item_key || (p.id + '_' + p.tipo_presentacion);
                            });
                        }
                    });
                    estado.tickets = parseados;
                    // Buscar el id más alto para continuar el contador
                    const maxId = parseados.reduce((max, t) => Math.max(max, t.id), 0);
                    estado.contadorTickets = maxId;
                    estado.ticketActivo = ticketActivoGuardado ? parseInt(ticketActivoGuardado, 10) : parseados[0].id;
                    return;
                }
            }
        } catch (e) {
            console.error('No se pudo cargar el estado desde sessionStorage:', e);
        }

        // Si no había nada guardado, creamos el primer ticket
        crearNuevoTicket(true);
    }

    // ============================================================
    // FUNCIONES DE TICKETS (PESTAÑAS)
    // ============================================================

    /**
     * Crea un nuevo ticket en memoria.
     * @param {boolean} activar - Si debe ponerse como ticket activo.
     * @returns {object} El ticket creado.
     */
    function crearNuevoTicket(activar = true) {
        estado.contadorTickets += 1;
        const nuevo = {
            id: estado.contadorTickets,
            nombre: 'Ticket ' + estado.contadorTickets,
            productos: [],
            folio: null,
            pagadoCon: 0,
            cambio: 0
        };
        estado.tickets.push(nuevo);
        if (activar) {
            estado.ticketActivo = nuevo.id;
        }
        guardarEstado();
        return nuevo;
    }

    /**
     * Cierra (elimina) un ticket. Si era el activo, pasa al vecino.
     * Si queda sin tickets, crea uno nuevo automáticamente.
     * @param {number} idTicket
     */
    function cerrarTicket(idTicket) {
        const indice = estado.tickets.findIndex(t => t.id === idTicket);
        if (indice === -1) return;

        const ticket = estado.tickets[indice];
        const tieneProductos = ticket.productos.length > 0;

        if (tieneProductos) {
            abrirModalConfirmacion({
                id: 'modal-confirm-cerrar-ticket',
                titulo: 'Cerrar ticket',
                mensaje: `¿Cerrar ${ticket.nombre}? Tiene ${ticket.productos.length} artículo(s) sin cobrar.`,
                confirmText: 'Cerrar',
                onConfirm: function () {
                    estado.tickets.splice(indice, 1);

                    if (estado.ticketActivo === idTicket) {
                        if (estado.tickets.length > 0) {
                            estado.ticketActivo = estado.tickets[Math.max(0, indice - 1)].id;
                        } else {
                            crearNuevoTicket(true);
                        }
                    }

                    if (estado.tickets.length === 0) {
                        crearNuevoTicket(true);
                    }

                    guardarEstado();
                    renderizarTodo();
                }
            });
            return;
        }

        estado.tickets.splice(indice, 1);

        // Si era el activo, seleccionamos uno cercano
        if (estado.ticketActivo === idTicket) {
            if (estado.tickets.length > 0) {
                estado.ticketActivo = estado.tickets[Math.max(0, indice - 1)].id;
            } else {
                crearNuevoTicket(true);
            }
        }

        // Si no quedan tickets, creamos uno nuevo
        if (estado.tickets.length === 0) {
            crearNuevoTicket(true);
        }

        guardarEstado();
        renderizarTodo();
    }

    /**
     * Activa (selecciona) un ticket por su id.
     * @param {number} idTicket
     */
    function activarTicket(idTicket) {
        const existe = estado.tickets.some(t => t.id === idTicket);
        if (existe) {
            estado.ticketActivo = idTicket;
            guardarEstado();
            renderizarTodo();
            enfocarInputCodigo();
        }
    }

    /**
     * @returns {object|null} El ticket actualmente seleccionado.
     */
    function obtenerTicketActivo() {
        return estado.tickets.find(t => t.id === estado.ticketActivo) || null;
    }

    // ============================================================
    // GESTIÓN DE PRODUCTOS EN EL TICKET ACTIVO
    // ============================================================

    /**
     * Agrega un producto (o aumenta su cantidad) al ticket activo.
     * @param {object} producto - Datos del producto (id, item_key, codigo_barras, nombre, precio_venta, stock, factor_unidades, tipo_presentacion)
     */
    function agregarProductoAlTicket(producto) {
        const ticket = obtenerTicketActivo();
        if (!ticket) return;

        const claveItem = producto.item_key || (producto.id + '_' + (producto.tipo_presentacion || 'unidad'));
        const factorUnidades = Math.max(1, Number(producto.factor_unidades || 1));
        const tipoPres = producto.tipo_presentacion || 'unidad';
        const nombrePres = producto.nombre_presentacion || (tipoPres === 'empaque' ? 'Caja' : 'Unidad');
        const unidad = (producto.unidad_medida || 'unidad');
        const permiteDecimales = Boolean(producto.permite_decimales) && tipoPres !== 'empaque';

        const existente = ticket.productos.find(p => (p.item_key || (p.id + '_' + (p.tipo_presentacion || 'unidad'))) === claveItem);

        // Calcular stock base consumido por este producto en el ticket actual
        const unidadesBaseEnTicket = ticket.productos
            .filter(p => p.id === producto.id)
            .reduce((suma, p) => suma + (Number(p.cantidad) * Math.max(1, Number(p.factor_unidades || 1))), 0);

        // Si ya existe en el ticket
        if (existente) {
            const pasoCantidad = permiteDecimales ? 0.25 : 1;
            const nuevaCantidad = Number(existente.cantidad) + pasoCantidad;
            const unidadesRequeridas = (nuevaCantidad - Number(existente.cantidad)) * factorUnidades;

            // Validar stock base total
            const stockBaseDisponible = (producto.stock_base !== undefined ? Number(producto.stock_base) : (tipoPres === 'empaque' ? Number(producto.stock || 0) * factorUnidades : Number(producto.stock || 0)));
            if (stockBaseDisponible > 0 && (unidadesBaseEnTicket + unidadesRequeridas) > stockBaseDisponible) {
                alert(`Stock insuficiente en inventario. Quedan ${stockBaseDisponible} unidades base.`);
                return;
            }
            existente.cantidad = Number(nuevaCantidad.toFixed(3));
            existente.importe = redondear(existente.cantidad * existente.precio_unitario);
        } else {
            const unidadesRequeridas = 1 * factorUnidades;
            const stockBaseDisponible = (producto.stock_base !== undefined ? Number(producto.stock_base) : (tipoPres === 'empaque' ? Number(producto.stock || 0) * factorUnidades : Number(producto.stock || 0)));

            if (stockBaseDisponible > 0 && (unidadesBaseEnTicket + unidadesRequeridas) > stockBaseDisponible) {
                alert(`Stock insuficiente. Se requieren ${unidadesRequeridas} unidades base y solo hay disponible ${Math.max(0, stockBaseDisponible - unidadesBaseEnTicket)}.`);
                return;
            }

            if (Number(producto.stock || 0) <= 0) {
                abrirModalConfirmacion({
                    id: 'modal-confirm-stock-cero',
                    titulo: 'Stock agotado',
                    mensaje: `Producto "${producto.nombre}" sin existencia (stock 0). ¿Desea agregarlo de todas formas?`,
                    confirmText: 'Agregar',
                    onConfirm: function () {
                        ticket.productos.push({
                            id: producto.id,
                            item_key: claveItem,
                            codigo_barras: producto.codigo_barras,
                            nombre: producto.nombre,
                            precio_lista: Number(producto.precio_venta),
                            precio_unitario: Number(producto.precio_venta),
                            descuento_unitario: 0,
                            cantidad: 1,
                            importe: Number(producto.precio_venta),
                            stock: Number(producto.stock || 0),
                            unidad_medida: unidad,
                            permite_decimales: permiteDecimales,
                            tipo_presentacion: tipoPres,
                            nombre_presentacion: nombrePres,
                            factor_unidades: factorUnidades
                        });
                        guardarEstado();
                        renderizarTodo();
                        enfocarInputCodigo();
                    }
                });
                return;
            }

            ticket.productos.push({
                id: producto.id,
                item_key: claveItem,
                codigo_barras: producto.codigo_barras,
                nombre: producto.nombre,
                precio_lista: Number(producto.precio_venta),
                precio_unitario: Number(producto.precio_venta),
                descuento_unitario: 0,
                cantidad: 1,
                importe: Number(producto.precio_venta),
                stock: Number(producto.stock || 0),
                unidad_medida: unidad,
                permite_decimales: permiteDecimales,
                tipo_presentacion: tipoPres,
                nombre_presentacion: nombrePres,
                factor_unidades: factorUnidades
            });
        }

        guardarEstado();
        renderizarTodo();
        enfocarInputCodigo();
    }

    /**
     * Elimina la fila de producto seleccionada del ticket activo.
     * @param {string|number} claveFila
     */
    function quitarProductoDelTicket(claveFila) {
        const ticket = obtenerTicketActivo();
        if (!ticket) return;
        ticket.productos = ticket.productos.filter(p => {
            const k = p.item_key || (p.id + '_' + (p.tipo_presentacion || 'unidad'));
            return k !== String(claveFila);
        });
        guardarEstado();
        renderizarTodo();
    }

    /**
     * Limpia todos los productos del ticket activo (Cancelar todo).
     */
    function cancelarTicketActivo() {
        const ticket = obtenerTicketActivo();
        if (!ticket) return;
        if (ticket.productos.length === 0) return;

        abrirModalConfirmacion({
            id: 'modal-confirm-cancelar-ticket',
            titulo: 'Cancelar venta',
            mensaje: '¿Cancelar toda la venta del ticket activo?',
            confirmText: 'Aceptar',
            onConfirm: function () {
                ticket.productos = [];
                ticket.pagadoCon = 0;
                ticket.cambio = 0;
                guardarEstado();
                renderizarTodo();
                enfocarInputCodigo();
            }
        });
    }

    function escapeAttribute(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function abrirModalConfirmacion({ id, titulo, mensaje, confirmText = 'Aceptar', onConfirm }) {
        let modal = document.getElementById(id);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = id;
            modal.className = 'modal-contenedor modal-confirmacion';
            modal.setAttribute('aria-hidden', 'true');
            modal.innerHTML = `
                <div class="modal-fondo" data-modal-close="${id}"></div>
                <div class="modal-caja" role="dialog" aria-modal="true">
                    <div class="modal-cabecera">
                        <span class="modal-icono">!</span>
                        <h3>${escapeAttribute(titulo)}</h3>
                        <button type="button" class="modal-cerrar-x" data-modal-close="${id}" aria-label="Cerrar">×</button>
                    </div>
                    <div class="modal-cuerpo">
                        <p class="modal-confirm-text">${escapeAttribute(mensaje)}</p>
                    </div>
                    <div class="modal-pie">
                        <button type="button" class="btn-pos-secondary btn-modal" data-modal-close="${id}">Cancelar</button>
                        <button type="button" class="btn-pos-success btn-modal" data-confirm="${id}">${escapeAttribute(confirmText)}</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        const texto = modal.querySelector('.modal-confirm-text');
        if (texto) texto.textContent = mensaje;

        const confirmar = modal.querySelector('[data-confirm="' + id + '"]');
        if (confirmar) {
            confirmar.onclick = function () {
                if (typeof onConfirm === 'function') onConfirm();
                cerrarModalPrompt(id);
            };
        }

        modal.querySelectorAll('[data-modal-close]').forEach((el) => {
            el.addEventListener('click', () => cerrarModalPrompt(id));
        });

        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        actualizarEstadoModalOpen();
    }

    function abrirModalPrompt(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        actualizarEstadoModalOpen();
        const input = modal.querySelector('input');
        if (input) {
            setTimeout(() => {
                input.focus();
                input.select && input.select();
            }, 50);
        }
    }

    function cerrarModalPrompt(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        const mensaje = modal.querySelector('.modal-mensaje');
        if (mensaje) {
            mensaje.textContent = '';
            mensaje.className = 'modal-mensaje';
        }
        actualizarEstadoModalOpen();
        enfocarInputCodigo();
    }

    function configurarModalGenerico(id, titulo, campos, btnTexto) {
        let modal = document.getElementById(id);
        if (modal) return modal;

        const campoHtml = campos.map((campo) => {
            const attrs = [];
            if (campo.type) attrs.push(`type="${campo.type}"`);
            if (campo.name) attrs.push(`name="${campo.name}"`);
            if (campo.id) attrs.push(`id="${campo.id}"`);
            if (campo.min !== undefined) attrs.push(`min="${campo.min}"`);
            if (campo.step !== undefined) attrs.push(`step="${campo.step}"`);
            if (campo.placeholder) attrs.push(`placeholder="${campo.placeholder}"`);
            if (campo.value !== undefined) attrs.push(`value="${escapeAttribute(String(campo.value))}"`);
            return `
                <div class="modal-field">
                    <label for="${campo.id}">${campo.label}</label>
                    <input ${attrs.join(' ')}>
                </div>
            `;
        }).join('');

        modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal-contenedor modal-generico';
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';

        modal.innerHTML = `
            <div class="modal-fondo" data-modal-close="${id}"></div>
            <div class="modal-caja modal-caja-personalizada" role="dialog" aria-modal="true">
                <div class="modal-cabecera">
                    <span class="modal-icono">✦</span>
                    <h3>${titulo}</h3>
                    <button type="button" class="modal-cerrar-x" data-modal-close="${id}" aria-label="Cerrar">×</button>
                </div>
                <div class="modal-cuerpo">
                    ${campoHtml}
                    <div class="modal-mensaje" aria-live="polite"></div>
                </div>
                <div class="modal-pie">
                    <button type="button" class="btn-pos-secondary btn-modal" data-modal-close="${id}">Cancelar</button>
                    <button type="button" class="btn-pos-success btn-modal" data-confirm="${id}">${btnTexto}</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelectorAll('[data-modal-close]').forEach((el) => {
            el.addEventListener('click', () => cerrarModalPrompt(id));
        });

        modal.querySelectorAll('input').forEach((input) => {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const btnConfirm = modal.querySelector(`[data-confirm="${id}"]`);
                    if (btnConfirm) btnConfirm.click();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cerrarModalPrompt(id);
                }
            });
        });

        return modal;
    }

    function mostrarMensajeModal(id, texto, tipo = 'error') {
        const modal = document.getElementById(id);
        if (!modal) return;
        const mensaje = modal.querySelector('.modal-mensaje');
        if (!mensaje) return;
        mensaje.textContent = texto;
        mensaje.className = 'modal-mensaje ' + (tipo === 'success' ? 'msg-success' : 'msg-error');
    }

    /**
     * Modifica la cantidad de una fila desde modal personalizado.
     */
    function modificarCantidadFilaSeleccionada() {
        const idSel = obtenerFilaSeleccionadaId();
        if (!idSel) {
            alert('Selecciona primero un artículo de la tabla.');
            return;
        }
        const ticket = obtenerTicketActivo();
        const fila = ticket.productos.find(p => (p.item_key || String(p.id)) === String(idSel));
        if (!fila) return;

        const modalId = 'modal-cantidad';
        const modal = configurarModalGenerico(
            modalId,
            'Modificar cantidad',
            [{
                id: 'modal-cantidad-input',
                name: 'cantidad',
                type: 'number',
                label: `Cantidad actual: ${formatearCantidad(fila.cantidad, fila.unidad_medida || 'unidad')}`,
                value: String(fila.cantidad),
                min: '0.001',
                step: fila.permite_decimales || (fila.unidad_medida && fila.unidad_medida !== 'unidad' && fila.tipo_presentacion !== 'empaque') ? '0.001' : '1',
                placeholder: 'Ej. 2 o 1.5'
            }],
            'Guardar'
        );

        const input = modal.querySelector('#modal-cantidad-input');
        const btn = modal.querySelector('[data-confirm="modal-cantidad"]');
        input.value = String(fila.cantidad);
        btn.onclick = function () {
            const nueva = parseFloat(String(input.value).replace(',', '.'));
            if (!Number.isFinite(nueva) || nueva <= 0) {
                mostrarMensajeModal(modalId, 'Cantidad inválida. Debe ser mayor que 0.', 'error');
                return;
            }

            const factor = Math.max(1, Number(fila.factor_unidades || 1));
            // Calcular unidades consumidas por otras líneas del mismo producto
            const otrasUnidades = ticket.productos
                .filter(p => p.id === fila.id && (p.item_key || String(p.id)) !== (fila.item_key || String(fila.id)))
                .reduce((s, p) => s + (Number(p.cantidad) * Math.max(1, Number(p.factor_unidades || 1))), 0);

            const totalUnidadesRequeridas = (nueva * factor) + otrasUnidades;
            const stockBase = Number(fila.stock_base !== undefined ? fila.stock_base : (fila.tipo_presentacion === 'empaque' ? Number(fila.stock || 0) * factor : Number(fila.stock || 0)));

            if (stockBase > 0 && totalUnidadesRequeridas > stockBase) {
                abrirModalConfirmacion({
                    id: 'modal-confirm-cantidad-stock',
                    titulo: 'Stock limitado',
                    mensaje: `Solo hay ${formatearCantidad(stockBase, 'unidades')} en existencia total. ¿Continuar con ${formatearCantidad(nueva, fila.unidad_medida || 'unidad')}?`,
                    confirmText: 'Continuar',
                    onConfirm: function () {
                        fila.cantidad = Number(nueva.toFixed(3));
                        fila.importe = redondear(fila.cantidad * fila.precio_unitario);
                        guardarEstado();
                        renderizarTodo();
                        cerrarModalPrompt(modalId);
                    }
                });
                return;
            }
            fila.cantidad = Number(nueva.toFixed(3));
            fila.importe = redondear(fila.cantidad * fila.precio_unitario);
            guardarEstado();
            renderizarTodo();
            cerrarModalPrompt(modalId);
        };

        abrirModalPrompt(modalId);
    }

    /**
     * Modifica el precio unitario de una fila desde modal personalizado.
     */
    function modificarPrecioFilaSeleccionada() {
        const idSel = obtenerFilaSeleccionadaId();
        if (!idSel) {
            alert('Selecciona primero un artículo de la tabla.');
            return;
        }
        const ticket = obtenerTicketActivo();
        const fila = ticket.productos.find(p => (p.item_key || String(p.id)) === String(idSel));
        if (!fila) return;

        const modalId = 'modal-precio';
        const modal = configurarModalGenerico(
            modalId,
            'Modificar precio',
            [{
                id: 'modal-precio-input',
                name: 'precio',
                type: 'number',
                label: `Precio actual: ${formatearMonto(fila.precio_unitario)}`,
                value: String(fila.precio_unitario),
                min: '0',
                step: '0.01',
                placeholder: 'Ej. 18.50'
            }],
            'Guardar'
        );

        const input = modal.querySelector('#modal-precio-input');
        const btn = modal.querySelector('[data-confirm="modal-precio"]');
        btn.onclick = function () {
            const nuevo = parseFloat(input.value);
            if (Number.isNaN(nuevo) || nuevo < 0) {
                mostrarMensajeModal(modalId, 'Precio inválido.', 'error');
                return;
            }
            const precioLista = Number(fila.precio_lista ?? fila.precio_unitario);
            if (!Number.isFinite(precioLista) || nuevo > precioLista) {
                mostrarMensajeModal(modalId, 'El precio final no puede superar el precio de lista.', 'error');
                return;
            }
            fila.precio_lista = redondear(precioLista);
            fila.precio_unitario = redondear(nuevo);
            fila.descuento_unitario = redondear(precioLista - nuevo);
            fila.importe = redondear(fila.cantidad * fila.precio_unitario);
            guardarEstado();
            renderizarTodo();
            cerrarModalPrompt(modalId);
        };

        abrirModalPrompt(modalId);
    }

    // ============================================================
    // CÁLCULOS Y FORMATEO
    // ============================================================

    function redondear(n) {
        return Math.round(n * 100) / 100;
    }

    function formatearCantidad(cantidad, unidad = 'unidad') {
        const numero = Number(cantidad || 0);
        if (!Number.isFinite(numero)) return '0';
        const valor = numero.toFixed(3).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
        return unidad && unidad !== 'unidad' ? valor + ' ' + unidad : valor;
    }

    function formatearMonto(n) {
        const simbolo = (typeof MONEDA_SIMBOLO !== 'undefined') ? MONEDA_SIMBOLO : 'L ';
        return simbolo + Number(n || 0).toLocaleString('es-HN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Calcula totales de un ticket.
     * @param {object} ticket
     * @returns {{total: number, articulos: number}}
     */
    function calcularTotales(ticket) {
        let total = 0;
        let articulos = 0;
        ticket.productos.forEach(p => {
            total += p.importe;
            articulos += p.cantidad;
        });
        return { total: redondear(total), articulos };
    }

    // ============================================================
    // RENDERIZADO DE LA INTERFAZ
    // ============================================================

    function renderizarTodo() {
        renderizarPestanas();
        renderizarTabla();
        renderizarTotales();
    }

    /**
     * Dibuja la barra de pestañas.
     */
    function renderizarPestanas() {
        const barra = document.getElementById('pos-tabs-bar');
        if (!barra) return;

        // Remueve todas las pestañas (pero deja el botón "Nuevo Ticket")
        const tabsExistentes = barra.querySelectorAll('.tab-ticket');
        tabsExistentes.forEach(t => t.remove());

        estado.tickets.forEach(ticket => {
            const totales = calcularTotales(ticket);
            const esActiva = ticket.id === estado.ticketActivo;

            const div = document.createElement('div');
            div.className = 'tab-ticket' + (esActiva ? ' tab-activa' : '');
            div.dataset.idTicket = String(ticket.id);

            div.innerHTML = `
                <span class="tab-nombre">${ticket.nombre}</span>
                <span class="tab-total">${ticket.productos.length} art. - ${formatearMonto(totales.total)}</span>
                <span class="tab-cerrar" title="Cerrar ticket">&#10005;</span>
            `;

            // Click en la pestaña para activar
            div.addEventListener('click', function (e) {
                if (e.target.classList.contains('tab-cerrar')) {
                    e.stopPropagation();
                    cerrarTicket(ticket.id);
                } else {
                    activarTicket(ticket.id);
                }
            });

            barra.appendChild(div);
        });
    }

    /**
     * Dibuja las filas de la tabla del ticket activo.
     */
    function renderizarTabla() {
        const tbody = document.getElementById('pos-tabla-body');
        const msgVacio = document.getElementById('tabla-mensaje-vacio');
        if (!tbody) return;

        tbody.innerHTML = '';
        const ticket = obtenerTicketActivo();

        if (!ticket || ticket.productos.length === 0) {
            if (msgVacio) msgVacio.style.display = '';
            return;
        }
        if (msgVacio) msgVacio.style.display = 'none';

        ticket.productos.forEach((p, idx) => {
            const tr = document.createElement('tr');
            const claveFila = p.item_key || (p.id + '_' + (p.tipo_presentacion || 'unidad'));
            tr.dataset.idProducto = claveFila;

            const stockBajo = Number(p.stock || 0) > 0 && Number(p.stock || 0) <= Number(p.stock_minimo || 1);
            if (stockBajo) tr.classList.add('fila-stock-bajo');

            if (claveFila === filaSeleccionadaId || String(p.id) === filaSeleccionadaId) tr.classList.add('fila-seleccionada');

            const etiquetaPresentacion = p.tipo_presentacion === 'empaque'
                ? ` <span style="background:#e0f2fe; color:#0369a1; padding:1px 5px; border-radius:4px; font-size:10px; font-weight:600;">📦 ${escaparHTML(p.nombre_presentacion || 'Caja')}</span>`
                : '';

            tr.innerHTML = `
                <td class="numero">${idx + 1}</td>
                <td>${p.codigo_barras || '—'}</td>
                <td>${escaparHTML(p.nombre)}${etiquetaPresentacion}</td>
                <td class="numero">${formatearMonto(p.precio_unitario)}</td>
                <td class="numero">${formatearCantidad(p.cantidad, p.unidad_medida || 'unidad')}</td>
                <td class="col-importe">${formatearMonto(p.importe)}</td>
                <td class="numero">${formatearCantidad(p.stock, p.unidad_medida || 'unidad')}</td>
            `;

            // Seleccionar fila al hacer clic
            tr.addEventListener('click', function () {
                seleccionarFila(claveFila);
            });
            // Doble clic: modificar cantidad
            tr.addEventListener('dblclick', function () {
                seleccionarFila(claveFila);
                modificarCantidadFilaSeleccionada();
            });

            tbody.appendChild(tr);
        });
    }

    /**
     * Actualiza la barra inferior de totales.
     */
    function renderizarTotales() {
        const ticket = obtenerTicketActivo();
        const totales = ticket ? calcularTotales(ticket) : { total: 0, articulos: 0 };
        const pagadoCon = ticket ? (ticket.pagadoCon || 0) : 0;
        const cambio = redondear(pagadoCon - totales.total);

        document.getElementById('total-venta').textContent = formatearMonto(totales.total);
        document.getElementById('total-pago').textContent = formatearMonto(pagadoCon);
        document.getElementById('total-cambio').textContent = formatearMonto(Math.max(0, cambio));
        document.getElementById('total-articulos').textContent = String(totales.articulos);
        document.getElementById('total-gigante').textContent = formatearMonto(totales.total);
    }

    // ============================================================
    // UTILERÍAS VARIAS
    // ============================================================

    function escaparHTML(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function hayModalAbierto() {
        return Array.from(document.querySelectorAll('[aria-hidden="false"], .modal-busqueda-productos:not([hidden])'))
            .some(modal => modal.matches('[aria-hidden="false"]') || modal.classList.contains('modal-busqueda-productos'));
    }

    function actualizarEstadoModalOpen() {
        document.body.classList.toggle('modal-open', hayModalAbierto());
    }

    function enfocarInputCodigo() {
        const input = document.getElementById('codigo-barras-input');
        if (input) {
            input.focus();
            input.select();
        }
    }

    // ID de la fila seleccionada en la tabla
    let filaSeleccionadaId = null;

    function seleccionarFila(idProducto) {
        filaSeleccionadaId = idProducto;
        const filas = document.querySelectorAll('#pos-tabla-body tr');
        filas.forEach(f => {
            if (String(f.dataset.idProducto) === String(idProducto)) {
                f.classList.add('fila-seleccionada');
            } else {
                f.classList.remove('fila-seleccionada');
            }
        });
    }

    function obtenerFilaSeleccionadaId() {
        const ticket = obtenerTicketActivo();
        if (!ticket || !ticket.productos.length) return null;
        if (filaSeleccionadaId) {
            const existe = ticket.productos.some(p => (p.item_key || String(p.id)) === String(filaSeleccionadaId));
            if (existe) return filaSeleccionadaId;
        }
        // Autoseleccionar la última fila activa en el ticket para agilizar uso de teclado
        const ultimo = ticket.productos[ticket.productos.length - 1];
        const clave = ultimo.item_key || (ultimo.id + '_' + (ultimo.tipo_presentacion || 'unidad'));
        seleccionarFila(clave);
        return clave;
    }

    let resultadosBusqueda = [];
    let indiceBusqueda = -1;
    let temporizadorBusqueda = null;

    function abrirModalBusquedaProductos() {
        const modal = document.getElementById('modal-busqueda-productos');
        const input = document.getElementById('busqueda-productos-input');
        if (!modal || !input) return;
        modal.hidden = false;
        actualizarEstadoModalOpen();
        input.value = '';
        resultadosBusqueda = [];
        indiceBusqueda = -1;
        renderizarResultadosBusqueda();
        input.focus();
    }

    function cerrarModalBusquedaProductos() {
        const modal = document.getElementById('modal-busqueda-productos');
        if (modal) modal.hidden = true;
        actualizarEstadoModalOpen();
        enfocarInputCodigo();
    }

    function buscarProductosEnModal() {
        const input = document.getElementById('busqueda-productos-input');
        const estado = document.getElementById('busqueda-productos-estado');
        const termino = input ? input.value.trim() : '';
        if (!termino) {
            resultadosBusqueda = [];
            indiceBusqueda = -1;
            renderizarResultadosBusqueda();
            return;
        }
        if (estado) estado.textContent = 'Buscando productos...';
        fetch(URL_BASE + 'ventas/buscar-productos?q=' + encodeURIComponent(termino), { credentials: 'same-origin' })
            .then(resp => resp.json())
            .then(productos => {
                resultadosBusqueda = Array.isArray(productos) ? productos : [];
                indiceBusqueda = resultadosBusqueda.length ? 0 : -1;
                renderizarResultadosBusqueda();
            })
            .catch(() => {
                resultadosBusqueda = [];
                indiceBusqueda = -1;
                if (estado) estado.textContent = 'No se pudo consultar el catálogo.';
                renderizarResultadosBusqueda();
            });
    }

    function renderizarResultadosBusqueda() {
        const cuerpo = document.querySelector('#tabla-busqueda-productos tbody');
        const estado = document.getElementById('busqueda-productos-estado');
        if (!cuerpo) return;
        cuerpo.innerHTML = '';
        if (!resultadosBusqueda.length) {
            if (estado && document.getElementById('busqueda-productos-input').value.trim()) estado.textContent = 'No se encontraron coincidencias.';
            return;
        }
        if (estado) estado.textContent = 'Usa las flechas y Enter para seleccionar.';
        resultadosBusqueda.forEach((producto, indice) => {
            const fila = document.createElement('tr');
            if (indice === indiceBusqueda) fila.classList.add('fila-busqueda-activa');
            const unidad = producto.unidad_medida || 'unidad';
            const stock = formatearCantidad(Number(producto.stock || 0), unidad);
            fila.innerHTML = `<td>${escaparTexto(producto.codigo_barras)}</td><td>${escaparTexto(producto.nombre)}</td><td>${formatearMonto(Number(producto.precio_venta))}</td><td>${stock}</td><td><button type="button" class="btn-pos-success btn-busqueda-agregar">Agregar</button></td>`;
            fila.addEventListener('click', () => seleccionarProductoBusqueda(indice));
            cuerpo.appendChild(fila);
        });
    }

    function escaparTexto(valor) {
        const contenedor = document.createElement('span');
        contenedor.textContent = valor == null ? '' : String(valor);
        return contenedor.innerHTML;
    }

    function seleccionarProductoBusqueda(indice) {
        const producto = resultadosBusqueda[indice];
        if (!producto) return;
        agregarProductoAlTicket({
            id: Number(producto.id),
            item_key: producto.item_key || (producto.id + '_' + (producto.tipo_presentacion || 'unidad')),
            codigo_barras: producto.codigo_barras,
            nombre: producto.nombre,
            precio_venta: Number(producto.precio_venta),
            stock: Number(producto.stock),
            stock_minimo: Number(producto.stock_minimo || 0),
            unidad_medida: producto.unidad_medida || 'unidad',
            permite_decimales: Boolean(producto.permite_decimales) || (producto.unidad_medida || 'unidad') !== 'unidad',
            tipo_presentacion: producto.tipo_presentacion || 'unidad',
            nombre_presentacion: producto.nombre_presentacion || 'Unidad',
            factor_unidades: Number(producto.factor_unidades || 1)
        });
        cerrarModalBusquedaProductos();
    }

    function manejarTecladoBusqueda(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            cerrarModalBusquedaProductos();
        } else if (e.key === 'ArrowDown' && resultadosBusqueda.length) {
            e.preventDefault();
            indiceBusqueda = (indiceBusqueda + 1) % resultadosBusqueda.length;
            renderizarResultadosBusqueda();
        } else if (e.key === 'ArrowUp' && resultadosBusqueda.length) {
            e.preventDefault();
            indiceBusqueda = (indiceBusqueda - 1 + resultadosBusqueda.length) % resultadosBusqueda.length;
            renderizarResultadosBusqueda();
        } else if (e.key === 'Enter' && resultadosBusqueda.length) {
            e.preventDefault();
            seleccionarProductoBusqueda(indiceBusqueda < 0 ? 0 : indiceBusqueda);
        }
    }

    /**
     * Actualiza el reloj visible en la barra de información cada 30 seg.
     */
    function iniciarReloj() {
        const el = document.getElementById('info-fecha-hora');
        if (!el) return;
        function pad(n) { return n < 10 ? '0' + n : String(n); }
        function actualizar() {
            const d = new Date();
            const fecha = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
            const hora = pad(d.getHours()) + ':' + pad(d.getMinutes());
            el.textContent = fecha + ' ' + hora;
        }
        actualizar();
        setInterval(actualizar, 30000);
    }

    // ============================================================
    // COMUNICACIÓN AJAX CON EL BACKEND (BUSCAR PRODUCTO)
    // ============================================================

    /**
     * Consulta el backend (VentasController::buscarProducto) por código de barras.
     * @param {string} codigo
     * @returns {Promise<object>} Respuesta JSON { exito, producto, mensaje }
     */
    function buscarProductoPorCodigo(codigo) {
        const url = URL_BASE + 'ventas/buscar-producto';
        const cuerpo = new URLSearchParams();
        cuerpo.append('codigo', codigo);

        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            credentials: 'same-origin',
            body: cuerpo.toString()
        }).then(resp => resp.json());
    }

    /**
     * Flujo principal: leer el input, consultar el server y agregar el producto.
     */
    function procesarInputCodigo() {
        const input = document.getElementById('codigo-barras-input');
        const codigo = (input.value || '').trim();
        if (!codigo) {
            enfocarInputCodigo();
            return;
        }

        // Mostrar feedback visual
        input.style.backgroundColor = '#e6f7ff';

        buscarProductoPorCodigo(codigo)
            .then(resp => {
                input.style.backgroundColor = '';
                if (resp && resp.exito === true) {
                    agregarProductoAlTicket(resp.producto);
                } else {
                    alert(resp && resp.mensaje ? resp.mensaje : 'Producto no encontrado.');
                }
                input.value = '';
                enfocarInputCodigo();
            })
            .catch(err => {
                console.error(err);
                input.style.backgroundColor = '';
                alert('Error al consultar el producto. Revisa la conexión.');
                enfocarInputCodigo();
            });
    }

    /**
     * Flujo de cobro (F12 o botón Cobrar).
     */
    function iniciarCobro() {
        const ticket = obtenerTicketActivo();
        if (!ticket || ticket.productos.length === 0) {
            alert('No hay artículos en el ticket actual.');
            return;
        }
        const totales = calcularTotales(ticket);
        const modal = document.getElementById('modal-cobro');
        if (!modal) return;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        actualizarEstadoModalOpen();
        modal.dataset.total = String(totales.total);
        const totalElemento = document.getElementById('cobro-total-pagar');
        const efectivo = document.getElementById('cobro-efectivo');
        if (totalElemento) totalElemento.textContent = formatearMonto(totales.total);
        if (efectivo) { efectivo.value = ''; efectivo.focus(); }
        actualizarCobroModal();
    }

    function cerrarModalCobro() {
        const modal = document.getElementById('modal-cobro');
        if (modal) { modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); }
        actualizarEstadoModalOpen();
        enfocarInputCodigo();
    }

    function obtenerTipoComprobanteSeleccionado() {
        const radio = document.querySelector('input[name="tipo-comprobante"]:checked');
        const valor = radio ? radio.value : COMPROBANTE_DEFAULT || 'recibo';
        return (valor === 'factura') ? 'factura' : 'recibo';
    }

    function actualizarCobroModal() {
        const total = Number(document.getElementById('modal-cobro')?.dataset.total || 0);
        const efectivo = Number(document.getElementById('cobro-efectivo')?.value || 0);
        const metodo = document.querySelector('input[name="cobro-metodo"]:checked')?.value || 'efectivo';
        const cambio = document.getElementById('cobro-cambio');
        const mensaje = document.getElementById('cobro-mensaje');
        const confirmar = document.getElementById('cobro-confirmar');
        const cliente = document.getElementById('cobro-cliente-contenedor');
        const esCredito = metodo === 'credito';
        const valido = esCredito || (efectivo >= total && efectivo > 0);
        if (cliente) cliente.style.display = esCredito ? 'flex' : 'none';
        if (cambio) { cambio.textContent = esCredito ? 'Sin pago inmediato' : (valido ? formatearMonto(efectivo - total) : 'Faltan ' + formatearMonto(total - efectivo)); cambio.className = 'cobro-cambio-valor ' + (valido ? 'cambio-valido' : 'cambio-insuficiente'); }
        if (mensaje) mensaje.textContent = esCredito ? 'Selecciona un cliente con crédito disponible.' : (valido ? 'Pago suficiente.' : 'Ingresa un monto igual o mayor al total.');
        if (confirmar) confirmar.disabled = !valido || (esCredito && !(document.getElementById('cobro-cliente')?.value));
    }

    function confirmarCobro() {
        const ticket = obtenerTicketActivo();
        if (!ticket) return;
        const total = Number(document.getElementById('modal-cobro')?.dataset.total || 0);
        const metodoPago = document.querySelector('input[name="cobro-metodo"]:checked')?.value || 'efectivo';
        const tipoComprobante = obtenerTipoComprobanteSeleccionado();
        const pagadoN = metodoPago === 'credito' ? 0 : Number(document.getElementById('cobro-efectivo')?.value || 0);
        const clienteId = Number(document.getElementById('cobro-cliente')?.value || 0);
        const cambio = metodoPago === 'credito' ? 0 : redondear(pagadoN - total);

        // Captura de datos opcionales de facturación / cliente eventual
       const clienteNombreInput = document.getElementById('cliente_nombre')?.value.trim();
const clienteNombre = clienteNombreInput && clienteNombreInput !== '' ? clienteNombreInput : 'Consumidor Final';
        const clienteRtn = document.getElementById('cliente_rtn')?.value.trim() || '';
        const clienteTelefono = document.getElementById('cliente_telefono')?.value.trim() || '';
        const clienteDireccion = document.getElementById('cliente_direccion')?.value.trim() || '';

        const ventanaTicket = window.open('', 'TicketImpresion', 'width=400,height=600,top=100,left=300,toolbar=no,location=no,status=no,menubar=no');
        
        fetch(URL_BASE + 'ventas/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=UTF-8' },
            credentials: 'same-origin',
            body: JSON.stringify({
                total: total,
                efectivo: pagadoN,
                cambio: cambio,
                metodo_pago: metodoPago,
                tipo_comprobante: tipoComprobante,
                cliente_id: clienteId,
                cliente_nombre: clienteNombre,
                cliente_rtn: clienteRtn,
                cliente_telefono: clienteTelefono,
                cliente_direccion: clienteDireccion,
                productos: ticket.productos.map(item => ({
                    ...item,
                    cantidad: Number(parseFloat(String(item.cantidad).replace(',', '.')).toFixed(3))
                }))
            })
        })
        .then(resp => resp.json()).then(resp => {
            if (!resp || resp.exito !== true) { 
                if (ventanaTicket) ventanaTicket.close(); 
                alert(resp?.mensaje || 'No se pudo registrar la venta.'); 
                return; 
            }

            const esFacturaCredito = metodoPago === 'credito' && tipoComprobante === 'factura';
            if (esFacturaCredito) {
                if (ventanaTicket) ventanaTicket.close();
                alert('Factura a crédito registrada. Se emitirá cuando el cliente haya pagado el total de la factura.');
            } else if (ventanaTicket) {
                ventanaTicket.location = URL_BASE + 'ventas/ticket/' + encodeURIComponent(resp.venta_id) + '?tipo=' + encodeURIComponent(tipoComprobante);
            }
            cerrarModalCobro();
            
            // Limpiar inputs opcionales tras completar cobro
            if (document.getElementById('cliente_nombre')) document.getElementById('cliente_nombre').value = '';
            if (document.getElementById('cliente_rtn')) document.getElementById('cliente_rtn').value = '';
            if (document.getElementById('cliente_telefono')) document.getElementById('cliente_telefono').value = '';
            if (document.getElementById('cliente_direccion')) document.getElementById('cliente_direccion').value = '';

            ticket.productos = []; ticket.pagadoCon = 0; ticket.cambio = 0;
            guardarEstado(); renderizarTodo();
        }).catch(() => { 
            if (ventanaTicket) ventanaTicket.close(); 
            alert('No se pudo conectar con el servidor para registrar la venta.'); 
        });
    }

    function seleccionarMetodoCobro(metodo) {
        const radio = document.querySelector('input[name="cobro-metodo"][value="' + metodo + '"]');
        if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change')); }
        actualizarCobroModal();
        if (metodo !== 'credito') document.getElementById('cobro-efectivo')?.focus();
    }

    /**
     * Agrega un artículo "genérico" con modal personalizado.
     */
    function agregarArticuloVarios() {
        const modalId = 'modal-varios';
        const modal = configurarModalGenerico(
            modalId,
            'Artículo varios',
            [
                { id: 'modal-varios-nombre', name: 'nombre', type: 'text', label: 'Nombre del artículo', placeholder: 'Ej. Refresco, pan, etc.' },
                { id: 'modal-varios-precio', name: 'precio', type: 'number', label: 'Precio unitario', min: '0', step: '0.01', placeholder: 'Ej. 18.50' }
            ],
            'Agregar'
        );

        const nombreInput = modal.querySelector('#modal-varios-nombre');
        const precioInput = modal.querySelector('#modal-varios-precio');
        const btn = modal.querySelector('[data-confirm="modal-varios"]');

        btn.onclick = function () {
            const nombre = (nombreInput.value || '').trim();
            const precioN = parseFloat(precioInput.value);

            if (!nombre) {
                mostrarMensajeModal(modalId, 'Debes ingresar un nombre para el artículo.', 'error');
                return;
            }
            if (Number.isNaN(precioN) || precioN < 0) {
                mostrarMensajeModal(modalId, 'Precio inválido.', 'error');
                return;
            }

            const falso = {
                id: 'varios_' + Date.now(),
                codigo_barras: 'VARIOS',
                nombre: nombre,
                precio_venta: precioN,
                stock: 9999,
                stock_minimo: 0
            };
            agregarProductoAlTicket(falso);
            cerrarModalPrompt(modalId);
        };

        abrirModalPrompt(modalId);
    }

    // ============================================================
    // EVENTOS DE TECLADO (ATAJOS GLOBALES)
    // ============================================================

    function bindAtajosTeclado() {
        document.addEventListener('keydown', function (e) {
            // F12 -> Cobrar venta
            if (e.key === 'F12') {
                e.preventDefault();
                iniciarCobro();
                return;
            }

            const modalCobro = document.getElementById('modal-cobro');
            if (modalCobro && modalCobro.style.display !== 'none') {
                const metodosRapidos = { F1: 'efectivo', F2: 'tarjeta', F3: 'transferencia', F4: 'credito' };
                if (metodosRapidos[e.key]) {
                    e.preventDefault();
                    seleccionarMetodoCobro(metodosRapidos[e.key]);
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cerrarModalCobro();
                    return;
                }
                if (e.key === 'Enter') {
                    // Evitar que Enter confirme si se está escribiendo en campos del cliente
                    const idActivo = document.activeElement ? document.activeElement.id : '';
                    if (['cliente_nombre', 'cliente_rtn', 'cliente_telefono', 'cliente_direccion'].includes(idActivo)) {
                        return;
                    }
                    e.preventDefault();
                    confirmarCobro();
                    return;
                }
            }
            // Si el modal de búsqueda de productos está abierto, delegar teclas
            const modalBusqueda = document.getElementById('modal-busqueda-productos');
            if (modalBusqueda && !modalBusqueda.hidden) {
                if (e.key === 'F10' || e.key === 'Escape') {
                    e.preventDefault();
                    cerrarModalBusquedaProductos();
                    return;
                }
                if (e.target.id === 'busqueda-productos-input') {
                    manejarTecladoBusqueda(e);
                    return;
                }
            }

            // Modal de confirmación genérico abierto (F9 cancelar, F6 borrar, cerrar ticket, etc.)
            const modalConfirmAbierto = document.querySelector('.modal-confirmacion[aria-hidden="false"]');
            if (modalConfirmAbierto) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const btnConfirmar = modalConfirmAbierto.querySelector('[data-confirm]');
                    if (btnConfirmar) btnConfirmar.click();
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    const btnCerrar = modalConfirmAbierto.querySelector('[data-modal-close]');
                    if (btnCerrar) btnCerrar.click();
                    return;
                }
            }

            // F10 -> Abrir / alternar búsqueda de productos
            if (e.key === 'F10') {
                e.preventDefault();
                abrirModalBusquedaProductos();
                return;
            }

            // F6 o Supr / Delete -> Borrar artículo seleccionado
            if (e.key === 'F6' || e.key === 'Delete' || e.key === 'Del') {
                if (!hayModalAbierto()) {
                    const ticket = obtenerTicketActivo();
                    if (!ticket || ticket.productos.length === 0) {
                        return;
                    }
                    const idSel = obtenerFilaSeleccionadaId();
                    if (idSel) {
                        e.preventDefault();
                        abrirModalConfirmacion({
                            id: 'modal-confirm-borrar-articulo',
                            titulo: 'Eliminar artículo',
                            mensaje: '¿Eliminar el artículo seleccionado?',
                            confirmText: 'Eliminar',
                            onConfirm: function () { quitarProductoDelTicket(idSel); }
                        });
                        return;
                    }
                }
            }

            // F7 -> Modificar cantidad del artículo
            if (e.key === 'F7') {
                if (!hayModalAbierto()) {
                    e.preventDefault();
                    modificarCantidadFilaSeleccionada();
                    return;
                }
            }

            // F8 -> Modificar precio unitario
            if (e.key === 'F8') {
                if (!hayModalAbierto()) {
                    e.preventDefault();
                    modificarPrecioFilaSeleccionada();
                    return;
                }
            }

            // F9 o F5 -> Cancelar todo el ticket
            if (e.key === 'F9' || e.key === 'F5') {
                e.preventDefault();
                if (!hayModalAbierto()) {
                    cancelarTicketActivo();
                }
                return;
            }

            // Flechas Arriba/Abajo para navegar en la tabla de productos si no hay modales abiertos
            if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && !hayModalAbierto()) {
                const ticket = obtenerTicketActivo();
                if (ticket && ticket.productos.length > 1) {
                    const idActual = obtenerFilaSeleccionadaId();
                    const idx = ticket.productos.findIndex(p => (p.item_key || String(p.id)) === String(idActual));
                    let nuevoIdx = idx;
                    if (e.key === 'ArrowUp') {
                        nuevoIdx = idx > 0 ? idx - 1 : ticket.productos.length - 1;
                    } else {
                        nuevoIdx = idx < ticket.productos.length - 1 ? idx + 1 : 0;
                    }
                    const nuevaClave = ticket.productos[nuevoIdx].item_key || String(ticket.productos[nuevoIdx].id);
                    seleccionarFila(nuevaClave);
                    e.preventDefault();
                    return;
                }
            }

            // Enter: acciona si está enfocado el input de código
            if (e.key === 'Enter') {
                const input = document.getElementById('codigo-barras-input');
                if (document.activeElement === input) {
                    e.preventDefault();
                    procesarInputCodigo();
                    return;
                }
            }

            // Insert -> Producto varios
            if (e.key === 'Insert') {
                if (!hayModalAbierto()) {
                    e.preventDefault();
                    agregarArticuloVarios();
                }
            }
        });
    }

    // ============================================================
    // EVENTOS DE BOTONES Y ELEMENTOS DEL DOM
    // ============================================================

    function bindEventosDOM() {
        const inputRtn = document.getElementById('cliente_rtn');
        if (inputRtn && !inputRtn.dataset.listenerRtn) {
            inputRtn.dataset.listenerRtn = 'true';
            inputRtn.addEventListener('blur', function() {
                consultarRtnCliente(this.value.trim());
            });
            inputRtn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    consultarRtnCliente(this.value.trim());
                }
            });
        }

        const inputBuscarCliente = document.getElementById('cliente_buscar');
        if (inputBuscarCliente && !inputBuscarCliente.dataset.listenerBuscarCliente) {
            inputBuscarCliente.dataset.listenerBuscarCliente = 'true';
            inputBuscarCliente.addEventListener('input', function () {
                const termino = this.value.trim();
                if (termino.length < 2) {
                    mostrarResultadosCliente([]);
                    return;
                }
                buscarClientesAjax(termino);
            });
        }

        const btnNuevoCliente = document.getElementById('btn-nuevo-cliente');
        if (btnNuevoCliente && !btnNuevoCliente.dataset.listenerNuevoCliente) {
            btnNuevoCliente.dataset.listenerNuevoCliente = 'true';
            btnNuevoCliente.addEventListener('click', abrirModalNuevoCliente);
        }

        const btnAgregar = document.getElementById('btn-agregar-producto');
        if (btnAgregar) btnAgregar.addEventListener('click', procesarInputCodigo);

        const btnCobrar = document.getElementById('btn-cobrar');
        if (btnCobrar) btnCobrar.addEventListener('click', iniciarCobro);

        const btnNuevo = document.getElementById('tab-nuevo');
        if (btnNuevo) btnNuevo.addEventListener('click', function () {
            crearNuevoTicket(true);
            renderizarTodo();
            enfocarInputCodigo();
        });

        const btnBorrarArt = document.getElementById('btn-borrar-art');
        if (btnBorrarArt) btnBorrarArt.addEventListener('click', function () {
            const idSel = obtenerFilaSeleccionadaId();
            if (!idSel) { alert('Selecciona un artículo para borrar.'); return; }
            abrirModalConfirmacion({
                id: 'modal-confirm-borrar-articulo',
                titulo: 'Eliminar artículo',
                mensaje: '¿Eliminar el artículo seleccionado?',
                confirmText: 'Eliminar',
                onConfirm: function () { quitarProductoDelTicket(idSel); }
            });
        });

        const btnCancelarTodo = document.getElementById('btn-cancelar-todo');
        if (btnCancelarTodo) btnCancelarTodo.addEventListener('click', cancelarTicketActivo);

        const btnCantidad = document.getElementById('btn-cantidad');
        if (btnCantidad) btnCantidad.addEventListener('click', modificarCantidadFilaSeleccionada);

        const btnPrecio = document.getElementById('btn-precio');
        if (btnPrecio) btnPrecio.addEventListener('click', modificarPrecioFilaSeleccionada);

        const btnInsVarios = document.getElementById('btn-ins-varios');
        if (btnInsVarios) btnInsVarios.addEventListener('click', agregarArticuloVarios);

        const btnBuscar = document.getElementById('btn-buscar');
        if (btnBuscar) btnBuscar.addEventListener('click', abrirModalBusquedaProductos);

        const inputBusqueda = document.getElementById('busqueda-productos-input');
        if (inputBusqueda) inputBusqueda.addEventListener('input', function () {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(buscarProductosEnModal, 200);
        });
        document.querySelectorAll('[data-busqueda-cerrar]').forEach(elemento => elemento.addEventListener('click', cerrarModalBusquedaProductos));

        document.querySelectorAll('[data-modal-cerrar]').forEach(elemento => elemento.addEventListener('click', cerrarModalCobro));
        const efectivoCobro = document.getElementById('cobro-efectivo');
        if (efectivoCobro) efectivoCobro.addEventListener('input', actualizarCobroModal);
        document.querySelectorAll('input[name="cobro-metodo"]').forEach(radio => radio.addEventListener('change', actualizarCobroModal));
        const clienteCobro = document.getElementById('cobro-cliente');
        if (clienteCobro) clienteCobro.addEventListener('change', actualizarCobroModal);
        document.querySelectorAll('.btn-billete').forEach(boton => boton.addEventListener('click', function () { efectivoCobro.value = String((Number(efectivoCobro.value) || 0) + Number(this.dataset.monto)); actualizarCobroModal(); efectivoCobro.focus(); }));
        const pagoExacto = document.querySelector('.btn-pago-exacto');
        if (pagoExacto) pagoExacto.addEventListener('click', function () { efectivoCobro.value = document.getElementById('modal-cobro').dataset.total; actualizarCobroModal(); efectivoCobro.focus(); });
        const confirmarCobroBoton = document.getElementById('cobro-confirmar');
        if (confirmarCobroBoton) confirmarCobroBoton.addEventListener('click', confirmarCobro);

        // Si hace clic en cualquier zona vacía, vuelve a enfocar el input de código (UX cajero)
        document.addEventListener('click', function (e) {
            const tag = (e.target.tagName || '').toLowerCase();
            if (tag === 'button' || tag === 'input' || tag === 'select' || tag === 'textarea' || tag === 'a') {
                return;
            }
            // Si hace clic sobre la tabla, no robar foco (permite selección)
            const tabla = document.querySelector('.pos-tabla-contenedor');
            if (tabla && tabla.contains(e.target)) return;
            enfocarInputCodigo();
        });
    }

    function buscarClientesAjax(termino) {
        fetch(URL_BASE + 'clientes/buscar?busqueda=' + encodeURIComponent(termino), {
            credentials: 'same-origin'
        })
        .then(resp => resp.json())
        .then(data => {
            const clientes = Array.isArray(data) ? data : [];
            mostrarResultadosCliente(clientes);
        })
        .catch(() => mostrarResultadosCliente([]));
    }

    function mostrarResultadosCliente(clientes) {
        const contenedor = document.getElementById('cliente_buscar_resultados');
        if (!contenedor) return;
        if (!clientes.length) {
            contenedor.style.display = 'none';
            contenedor.innerHTML = '';
            return;
        }

        contenedor.style.display = 'block';
        contenedor.innerHTML = clientes.map((cliente) => `
            <button type="button" class="cliente-buscar-item" data-cliente-id="${cliente.id}" style="display: block; width: 100%; text-align: left; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; margin-bottom: 6px; cursor: pointer;">
                <strong>${escaparTexto(cliente.nombre || 'Sin nombre')}</strong><br>
                <small>RTN: ${escaparTexto(cliente.rtn_identidad || 'N/A')} · Tel: ${escaparTexto(cliente.telefono || 'N/A')}</small>
            </button>
        `).join('');

        contenedor.querySelectorAll('.cliente-buscar-item').forEach((boton) => {
            boton.addEventListener('click', function () {
                const id = Number(this.dataset.clienteId || 0);
                const cliente = clientes.find(item => Number(item.id) === id);
                if (cliente) {
                    aplicarDatosCliente(cliente);
                    const inputBuscar = document.getElementById('cliente_buscar');
                    if (inputBuscar) inputBuscar.value = cliente.nombre || '';
                    contenedor.style.display = 'none';
                    contenedor.innerHTML = '';
                    const inputId = document.getElementById('cobro-cliente');
                    if (inputId && cliente.id) inputId.value = String(cliente.id);
                    actualizarCobroModal();
                }
            });
        });
    }

    function abrirModalNuevoCliente() {
        const modalId = 'modal-nuevo-cliente-rapido';
        let modal = document.getElementById(modalId);
        if (modal) {
            modal.remove();
        }

        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal-contenedor';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="modal-fondo" data-modal-close="${modalId}"></div>
            <div class="modal-caja" role="dialog" aria-modal="true" style="max-width: 420px;">
                <div class="modal-cabecera">
                    <span class="modal-icono">+</span>
                    <h3>Nuevo Cliente</h3>
                    <button type="button" class="modal-cerrar-x" data-modal-close="${modalId}" aria-label="Cerrar">×</button>
                </div>
                <div class="modal-cuerpo">
                    <div class="campo" style="margin-bottom: 8px;">
                        <label for="nuevo_cliente_nombre">Nombre</label>
                        <input id="nuevo_cliente_nombre" type="text" class="cobro-input" placeholder="Nombre o razón social">
                    </div>
                    <div class="campo" style="margin-bottom: 8px;">
                        <label for="nuevo_cliente_rtn">RTN / DNI</label>
                        <input id="nuevo_cliente_rtn" type="text" class="cobro-input" placeholder="08011990000000">
                    </div>
                    <div class="campo" style="margin-bottom: 8px;">
                        <label for="nuevo_cliente_telefono">Teléfono</label>
                        <input id="nuevo_cliente_telefono" type="text" class="cobro-input" placeholder="+504 9999-9999">
                    </div>
                    <div class="campo" style="margin-bottom: 8px;">
                        <label for="nuevo_cliente_direccion">Dirección</label>
                        <input id="nuevo_cliente_direccion" type="text" class="cobro-input" placeholder="Dirección">
                    </div>
                    <div id="nuevo_cliente_mensaje" class="modal-mensaje" aria-live="polite"></div>
                </div>
                <div class="modal-pie">
                    <button type="button" class="btn-pos-secondary btn-modal" data-modal-close="${modalId}">Cancelar</button>
                    <button type="button" class="btn-pos-success btn-modal" id="guardar-cliente-rapido">Guardar y Seleccionar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelectorAll('[data-modal-close]').forEach((elemento) => {
            elemento.addEventListener('click', () => modal.remove());
        });

        const btnGuardar = document.getElementById('guardar-cliente-rapido');
        btnGuardar.addEventListener('click', function () {
            const nombre = document.getElementById('nuevo_cliente_nombre').value.trim();
            const rtn = document.getElementById('nuevo_cliente_rtn').value.trim();
            const telefono = document.getElementById('nuevo_cliente_telefono').value.trim();
            const direccion = document.getElementById('nuevo_cliente_direccion').value.trim();
            const mensaje = document.getElementById('nuevo_cliente_mensaje');

            if (!nombre) {
                mensaje.textContent = 'El nombre del cliente es obligatorio.';
                mensaje.className = 'modal-mensaje msg-error';
                return;
            }

            fetch(URL_BASE + 'clientes/guardar-ajax', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                credentials: 'same-origin',
                body: new URLSearchParams({
                    nombre: nombre,
                    rtn_identidad: rtn,
                    telefono: telefono,
                    direccion: direccion,
                    limite_credito: '0'
                }).toString()
            })
            .then(resp => resp.json())
            .then(data => {
                if (!data || !data.exito) {
                    mensaje.textContent = data?.mensaje || 'No se pudo guardar el cliente.';
                    mensaje.className = 'modal-mensaje msg-error';
                    return;
                }

                const cliente = data.cliente || {};
                aplicarDatosCliente(cliente);
                const inputNombre = document.getElementById('cliente_nombre');
                const inputBuscar = document.getElementById('cliente_buscar');
                if (inputNombre) inputNombre.value = cliente.nombre || nombre;
                if (inputBuscar) inputBuscar.value = cliente.nombre || nombre;
                const inputId = document.getElementById('cobro-cliente');
                if (inputId && cliente.id) inputId.value = String(cliente.id);
                modal.remove();
                actualizarCobroModal();
            })
            .catch(() => {
                mensaje.textContent = 'No se pudo conectar con el servidor.';
                mensaje.className = 'modal-mensaje msg-error';
            });
        });

        const nombreInput = document.getElementById('nuevo_cliente_nombre');
        if (nombreInput) {
            setTimeout(() => nombreInput.focus(), 80);
        }
    }

    // ============================================================
    // INICIALIZACIÓN
    // ============================================================

    document.addEventListener('DOMContentLoaded', function () {
        // 1) Cargar los tickets desde sessionStorage
        cargarEstado();

        // 2) Render inicial
        renderizarTodo();

        // 3) Eventos
        bindEventosDOM();
        bindAtajosTeclado();

        // 4) Reloj
        iniciarReloj();

        // 5) Foco inicial en el campo de código
        enfocarInputCodigo();

        console.log('[POS] Sistema inicializado. Tickets cargados:', estado.tickets.length);
    });

})();
const inputRtn = document.getElementById('cliente_rtn');
if (inputRtn) {
    inputRtn.addEventListener('blur', function() {
        consultarRtnCliente(this.value.trim());
    });
    inputRtn.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarRtnCliente(this.value.trim());
        }
    });
}

function consultarRtnCliente(rtn) {
    if (!rtn) return;

    fetch(URL_BASE + 'ventas/buscar-cliente-por-rtn?rtn=' + encodeURIComponent(rtn), {
        credentials: 'same-origin'
    })
    .then(resp => resp.json())
    .then(data => {
        if (!data.exito || !data.coincidencias.length) return;

        const lista = data.coincidencias;

        // Caso A: Si solo hay una razón social asociada al RTN
        if (lista.length === 1) {
            aplicarDatosCliente(lista[0]);
        } 
        // Caso B: Si existen múltiples razones sociales con el mismo RTN
        else {
            mostrarOpcionesRazonSocial(lista);
        }
    })
    .catch(err => console.error('Error al consultar RTN:', err));
}

function aplicarDatosCliente(cliente) {
    if (document.getElementById('cliente_nombre')) {
        document.getElementById('cliente_nombre').value = cliente.nombre || '';
    }
    if (document.getElementById('cliente_telefono')) {
        document.getElementById('cliente_telefono').value = cliente.telefono || '';
    }
    if (document.getElementById('cliente_direccion')) {
        document.getElementById('cliente_direccion').value = cliente.direccion || '';
    }
    if (cliente.cliente_id && document.getElementById('cobro-cliente')) {
        document.getElementById('cobro-cliente').value = cliente.cliente_id;
    }
}

function mostrarOpcionesRazonSocial(lista) {
    const modalId = 'modal-seleccionar-razon-social';
    let modal = document.getElementById(modalId);

    if (modal) modal.remove(); // Limpiar previo si existe

    modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal-contenedor';
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    const opcionesHtml = lista.map((item, index) => `
        <button type="button" class="btn-pos-secondary opcion-razon-social" data-index="${index}" style="width: 100%; text-align: left; padding: 10px; margin-bottom: 5px; cursor: pointer;">
            <strong>${item.nombre}</strong><br>
            <small>Tel: ${item.telefono || 'N/A'} | Dir: ${item.direccion || 'N/A'}</small>
        </button>
    `).join('');

    modal.innerHTML = `
        <div class="modal-fondo"></div>
        <div class="modal-caja" role="dialog" aria-modal="true" style="max-width: 450px;">
            <div class="modal-cabecera">
                <span class="modal-icono">ℹ</span>
                <h3>Múltiples Nombres Encontrados</h3>
            </div>
            <div class="modal-cuerpo">
                <p style="margin-bottom: 10px;">Selecciona la razón social correspondiente para este RTN:</p>
                <div class="lista-razones">${opcionesHtml}</div>
            </div>
            <div class="modal-pie">
                <button type="button" class="btn-pos-secondary btn-cerrar-razon">Cancelar</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelectorAll('.opcion-razon-social').forEach(btn => {
        btn.onclick = function() {
            const idx = parseInt(this.dataset.index, 10);
            aplicarDatosCliente(lista[idx]);
            modal.remove();
        };
    });

    modal.querySelector('.btn-cerrar-razon').onclick = function() {
        modal.remove();
    };
}