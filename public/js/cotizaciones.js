(function () {
    'use strict';

    var URL = URL_BASE;
    var MONEDA = typeof MONEDA_SIMBOLO !== 'undefined' ? MONEDA_SIMBOLO : 'L';
    var items = [];            // carrito
    var cliente = { id: 0 };   // snapshot del cliente
    var edicion = MODO_EDICION === 1;
    var cotizacionId = COTIZACION_ID;

    function moneda(n) {
        n = Number(n) || 0;
        var partes = n.toFixed(2).split('.');
        partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return MONEDA + partes.join('.');
    }

    function mostrarMensaje(error) {
        var cont = document.createElement('div');
        cont.className = error ? 'alerta alerta-error cot-alerta-flotante' : 'alerta alerta-exito cot-alerta-flotante';
        cont.textContent = error;
        document.getElementById('cotizacion-form').prepend(cont);
        window.setTimeout(function () { cont.remove(); }, error ? 9000 : 4000);
    }

    // ---------- Cliente / datalist ----------
    function construirDatalists() {
        var dlNombres = document.getElementById('cot-lista-clientes');
        var dlRtn = document.getElementById('cot-lista-rtn');
        var nombres = {}, rtns = {};
        if (dlNombres) dlNombres.innerHTML = '';
        if (dlRtn) dlRtn.innerHTML = '';
        CLIENTES.forEach(function (c) {
            var n = (c.nombre || '').trim();
            if (n && !nombres[n]) {
                nombres[n] = true;
                if (dlNombres) {
                    var op = document.createElement('option');
                    op.value = n;
                    dlNombres.appendChild(op);
                }
            }
            var r = (c.rtn_identidad || '').trim();
            if (r && !rtns[r]) {
                rtns[r] = true;
                if (dlRtn) {
                    var opR = document.createElement('option');
                    opR.value = r;
                    dlRtn.appendChild(opR);
                }
            }
        });
    }

    function clientePorRtn(rtnTexto) {
        var buscar = String(rtnTexto || '').replace(/\D/g, '');
        if (buscar === '') return null;
        for (var i = 0; i < CLIENTES.length; i++) {
            if (String(CLIENTES[i].rtn_identidad || '').replace(/\D/g, '') === buscar) {
                return CLIENTES[i];
            }
        }
        return null;
    }

    function poblarClientes() {
        construirDatalists();
        var campo = document.getElementById('cot-cliente-selector');
        if (edicion && COTIZACION_CLIENTE) {
            aplicarCliente(COTIZACION_CLIENTE);
        }
        if (campo) {
            campo.value = edicion && COTIZACION_CLIENTE ? COTIZACION_CLIENTE.nombre : '';
        }
    }

    function vincularBuscadorCliente() {
        var campo = document.getElementById('cot-cliente-selector');
        if (campo && !campo.__conectado) {
            campo.__conectado = true;
            campo.addEventListener('input', function () {
                var nombreBuscado = this.value.trim();
                if (nombreBuscado === '') return;
                var encontrado = CLIENTES.find(function (c) { return c.nombre === nombreBuscado; });
                if (encontrado) {
                    var sel = {
                        id: encontrado.id,
                        nombre: encontrado.nombre,
                        rtn: encontrado.rtn_identidad,
                        telefono: encontrado.telefono,
                        direccion: encontrado.direccion
                    };
                    aplicarCliente(sel, true);
                }
            });
        }
        var rtnInput = document.getElementById('cot-cliente-rtn');
        if (rtnInput && !rtnInput.__conectado) {
            rtnInput.__conectado = true;
            rtnInput.addEventListener('change', function () {
                var c = clientePorRtn(this.value);
                if (c) {
                    aplicarCliente({
                        id: c.id,
                        nombre: c.nombre,
                        rtn: c.rtn_identidad,
                        telefono: c.telefono,
                        direccion: c.direccion
                    }, true);
                } else {
                    cliente.id = 0;
                }
            });
        }
    }

    function aplicarCliente(sel, sobreescribir) {
        cliente.id = sel.id || 0;
        if (!sobreescribir || !document.getElementById('cot-cliente-nombre').value) {
            document.getElementById('cot-cliente-nombre').value = sel.nombre || '';
        }
        document.getElementById('cot-cliente-rtn').value = sel.rtn || '';
        document.getElementById('cot-cliente-telefono').value = sel.telefono || '';
        document.getElementById('cot-cliente-direccion').value = sel.direccion || '';
    }

    // ---------- Modal registro rápido de cliente ----------
    function vincularModalCliente() {
        var modal = document.getElementById('modal-cliente-rapido');
        var btnAbrir = document.getElementById('cot-btn-registrar-cliente');
        if (!modal || !btnAbrir) return;

        function cerrar() { modal.hidden = true; }

        function abrir() {
            document.getElementById('mc-nombre').value = '';
            document.getElementById('mc-rtn').value = '';
            document.getElementById('mc-telefono').value = '';
            document.getElementById('mc-credito').value = '0';
            document.getElementById('mc-direccion').value = '';
            ocultarError();
            modal.hidden = false;
            document.getElementById('mc-nombre').focus();
        }

        function mostrarError(msg) {
            var div = document.getElementById('mc-error');
            div.textContent = msg;
            div.style.display = '';
        }

        function ocultarError() {
            document.getElementById('mc-error').style.display = 'none';
        }

        btnAbrir.addEventListener('click', abrir);
        document.getElementById('mc-cerrar').addEventListener('click', cerrar);
        document.getElementById('mc-cancelar').addEventListener('click', cerrar);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) cerrar();
        });

        document.getElementById('modal-cliente-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var nombre = document.getElementById('mc-nombre').value.trim();
            if (!nombre) {
                mostrarError('El nombre del cliente es obligatorio.');
                return;
            }
            var btn = document.getElementById('mc-guardar');
            btn.disabled = true;
            var texto = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

            var params = new URLSearchParams();
            params.append('csrf_token', CSRF_TOKEN);
            params.append('nombre', nombre);
            params.append('rtn_identidad', document.getElementById('mc-rtn').value.trim());
            params.append('telefono', document.getElementById('mc-telefono').value.trim());
            params.append('direccion', document.getElementById('mc-direccion').value.trim());
            params.append('limite_credito', document.getElementById('mc-credito').value.trim() || '0');

            fetch(URL + 'clientes/guardar-ajax', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    btn.disabled = false;
                    btn.innerHTML = texto;
                    if (!resp.exito || !resp.cliente) {
                        mostrarError(resp.mensaje || 'No se pudo crear el cliente.');
                        return;
                    }
                    var nuevo = resp.cliente;
                    CLIENTES.push({
                        id: nuevo.id,
                        nombre: nuevo.nombre,
                        rtn_identidad: nuevo.rtn_identidad || '',
                        telefono: nuevo.telefono || '',
                        direccion: nuevo.direccion || ''
                    });
                    construirDatalists();
                    cerrar();
                    aplicarCliente({
                        id: nuevo.id,
                        nombre: nuevo.nombre,
                        rtn: nuevo.rtn_identidad || '',
                        telefono: nuevo.telefono || '',
                        direccion: nuevo.direccion || ''
                    }, true);
                    mostrarMensaje('Cliente registrado correctamente.');
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.innerHTML = texto;
                    mostrarError('Error de conexión al registrar el cliente.');
                });
        });
    }

    // ---------- Búsqueda de productos ----------
    var inputBusqueda = document.getElementById('cot-busqueda-input');
    var temporizadorBusqueda = null;

    function buscarProductos(termino) {
        var spinner = document.querySelector('.cot-busqueda-icono i');
        spinner.style.display = 'inline-block';
        fetch(URL + 'cotizaciones/buscar-productos?q=' + encodeURIComponent(termino), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (datos) { renderizarResultados(datos); })
            .catch(function () { renderizarResultados([]); })
            .finally(function () { spinner.style.display = 'none'; });
    }

    function renderizarResultados(datos) {
        var contenedor = document.getElementById('cot-resultados');
        var cuerpo = document.querySelector('#cot-resultados-tabla tbody');
        cuerpo.innerHTML = '';
        if (!datos || datos.length === 0) {
            contenedor.hidden = true;
            return;
        }
        contenedor.hidden = false;
        datos.forEach(function (p) {
            var tr = document.createElement('tr');
            var tdImagen = document.createElement('td');
            if (p.imagen) {
                var img = document.createElement('img');
                img.src = URL + 'uploads/productos/' + p.imagen;
                img.alt = p.nombre;
                img.className = 'cot-prod-miniatura cot-prod-miniatura-zoom';
                img.title = 'Ver imagen grande';
                img.addEventListener('click', function () {
                    abrirVisorImagen(img.src, p.nombre);
                });
                tdImagen.appendChild(img);
            }
            var tdNombre = document.createElement('td');
            tdNombre.textContent = p.nombre;
            tdNombre.className = 'cot-prod-nombre';
            var tdPrecio = document.createElement('td');
            tdPrecio.textContent = moneda(p.precio_venta);
            var tdStock = document.createElement('td');
            tdStock.textContent = String(Number(p.stock || 0).toFixed(p.permite_decimales ? 2 : 0));
            tdStock.className = (Number(p.stock) <= 0) ? 'cot-stock-cero' : '';
            var tdAccion = document.createElement('td');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-item btn-exito btn-pequeno';
            btn.innerHTML = '<i class="fa-solid fa-plus"></i> Agregar';
            btn.addEventListener('click', function () { agregarProducto(p); });
            tdAccion.appendChild(btn);

            tr.appendChild(tdImagen);
            tr.appendChild(tdNombre);
            tr.appendChild(tdPrecio);
            tr.appendChild(tdStock);
            tr.appendChild(tdAccion);
            cuerpo.appendChild(tr);
        });
    }

    // ---------- Visor de imagen de producto (lightbox) ----------
    var visorImagen = null;

    function cerrarVisorImagen() {
        if (!visorImagen) return;
        visorImagen.hidden = true;
        document.body.classList.remove('cot-visor-abierto');
    }

    function abrirVisorImagen(url, nombre) {
        if (!visorImagen) {
            visorImagen = document.createElement('div');
            visorImagen.className = 'cot-visor';
            visorImagen.setAttribute('role', 'dialog');
            visorImagen.setAttribute('aria-modal', 'true');
            visorImagen.innerHTML =
                '<button type="button" class="cot-visor-cerrar" aria-label="Cerrar imagen">&#10005;</button>' +
                '<figure class="cot-visor-figura">' +
                    '<img class="cot-visor-img" alt="">' +
                    '<figcaption class="cot-visor-caption"></figcaption>' +
                '</figure>';
            visorImagen.addEventListener('click', function (e) {
                if (e.target === visorImagen || e.target.closest('.cot-visor-cerrar')) cerrarVisorImagen();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && visorImagen && !visorImagen.hidden) cerrarVisorImagen();
            });
            document.body.appendChild(visorImagen);
        }
        var img = visorImagen.querySelector('.cot-visor-img');
        img.src = url;
        img.alt = nombre || 'Imagen del producto';
        visorImagen.querySelector('.cot-visor-caption').textContent = nombre || '';
        visorImagen.hidden = false;
        document.body.classList.add('cot-visor-abierto');
    }

    function agregarProducto(p) {
        var existente = items.find(function (it) { return it.item_key === p.item_key && it.id === p.id; });
        var incremento = p.tipo_presentacion === 'empaque' ? 1 : 1;
        if (existente) {
            existente.cantidad = Number(existente.cantidad) + incremento;
        } else {
            items.push({
                id: p.id,
                item_key: p.item_key,
                codigo_barras: p.codigo_barras || '',
                nombre: p.nombre,
                nombre_original: p.nombre_original || p.nombre,
                precio_lista: Number(p.precio_venta) || 0,
                precio_v: Number(p.precio_venta) || 0,
                descuento: 0,
                cantidad: 1,
                stock: Number(p.stock) || 0,
                stock_minimo: Number(p.stock_minimo) || 0,
                unidad_medida: p.unidad_medida || 'unidad',
                permite_decimales: !!p.permite_decimales,
                tipo_presentacion: p.tipo_presentacion,
                nombre_presentacion: p.nombre_presentacion,
                factor_unidades: Number(p.factor_unidades) || 1,
                clave_isv: p.clave_isv || 'gravado_15',
                porcentaje_isv: Number(p.porcentaje_isv) || 15,
                imagen: p.imagen || ''
            });
        }
        renderizarCarrito();
        // Limpiar el buscador para poder agregar otro producto rápidamente
        window.clearTimeout(temporizadorBusqueda);
        if (inputBusqueda) {
            inputBusqueda.value = '';
            inputBusqueda.focus();
        }
        document.getElementById('cot-resultados').hidden = true;
        var tol = document.querySelector('.cot-carrito-wrap');
        if (tol) tol.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Texto sin espacios que puede ser un código de barras (escáner o tecleado)
    function esCodigoBuscable(termino) {
        return /^[A-Za-z0-9-]+$/.test(termino);
    }

    // Intenta agregar por código de barras exacto; si no coincide, muestra la búsqueda normal
    function agregarPorCodigoBarras(codigo) {
        window.clearTimeout(temporizadorBusqueda);
        document.getElementById('cot-resultados').hidden = true;
        fetch(URL + 'cotizaciones/buscar-por-codigo?codigo=' + encodeURIComponent(codigo), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (resp && resp.exito && resp.producto) {
                    agregarProducto(resp.producto);
                } else if (codigo.length >= 2) {
                    buscarProductos(codigo);
                }
            })
            .catch(function () {
                if (codigo.length >= 2) buscarProductos(codigo);
            });
    }

    // ---------- Carrito ----------
    function renderizarCarrito() {
        var cuerpo = document.getElementById('cot-carrito-body');
        var vacio = document.getElementById('cot-carrito-vacio');
        cuerpo.innerHTML = '';
        vacio.style.display = items.length === 0 ? '' : 'none';
        items.forEach(function (it, indice) {
            var tr = document.createElement('tr');
            tr.dataset.indice = String(indice);

            var tdNombre = document.createElement('td');
            tdNombre.className = 'cot-celda-nombre';
            if (it.imagen) {
                var img = document.createElement('img');
                img.src = URL + 'uploads/productos/' + it.imagen;
                img.alt = it.nombre;
                img.className = 'cot-prod-miniatura cot-prod-miniatura-zoom';
                img.title = 'Ver imagen grande';
                img.addEventListener('click', function () {
                    abrirVisorImagen(img.src, it.nombre);
                });
                tdNombre.appendChild(img);
            }
            var spanNombre = document.createElement('span');
            spanNombre.textContent = it.nombre;
            tdNombre.appendChild(spanNombre);

            function crearInputNumerico(valor, clase, paso, onCambio) {
                var inp = document.createElement('input');
                inp.type = 'number';
                inp.min = '0';
                inp.step = String(paso || '0.01');
                inp.value = String(valor);
                inp.className = 'form-control-pos ' + clase;
                inp.addEventListener('change', onCambio);
                inp.addEventListener('input', function () { linea(indice).guardar = true; });
                return inp;
            }
            var userChanged = null;

            var tdLista = document.createElement('td');
            tdLista.className = 'cot-celda-input';
            tdLista.dataset.label = 'P. Lista';
            var inpLista = crearInputNumerico(it.precio_lista, 'cot-inp-monto', '0.01', function () {
                var n = Math.max(0, parseFloat(this.value) || 0);
                it.precio_lista = n;
                var nuevofinal = Math.max(0, n - it.descuento);
                it.precio_v = nuevofinal;
                renderizarCarrito();
            });
            tdLista.appendChild(inpLista);

            var tdDesc = document.createElement('td');
            tdDesc.className = 'cot-celda-input';
            tdDesc.dataset.label = 'Descuento';
            var inpDesc = crearInputNumerico(it.descuento, 'cot-inp-monto', '0.01', function () {
                var d = Math.min(it.precio_lista, Math.max(0, parseFloat(this.value) || 0));
                it.descuento = d;
                it.precio_v = Math.max(0, it.precio_lista - d);
                renderizarCarrito();
            });
            tdDesc.appendChild(inpDesc);

            var tdFinal = document.createElement('td');
            tdFinal.className = 'cot-celda-input';
            tdFinal.dataset.label = 'P. Final';
            var inpFinal = crearInputNumerico(it.precio_v, 'cot-inp-monto', '0.01', function () {
                var f = Math.max(0, parseFloat(this.value) || 0);
                it.precio_v = f;
                it.descuento = Math.min(it.precio_lista, Math.max(0, it.precio_lista - f));
                renderizarCarrito();
            });
            tdFinal.appendChild(inpFinal);

            var tdCant = document.createElement('td');
            tdCant.className = 'cot-celda-input';
            tdCant.dataset.label = 'Cant.';
            var inpCant = crearInputNumerico(it.cantidad, 'cot-inp-cant', it.permite_decimales ? '0.001' : '1', function () {
                var c = Math.max(0, parseFloat(this.value) || 0);
                it.cantidad = c;
                renderizarCarrito();
            });
            tdCant.appendChild(inpCant);

            var tdImporte = document.createElement('td');
            tdImporte.dataset.label = 'Importe';
            tdImporte.className = 'text-right cot-celda-importe';
            tdImporte.textContent = moneda(it.cantidad * it.precio_v);

            var tdEliminar = document.createElement('td');
            tdEliminar.dataset.label = '';
            var btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.className = 'btn btn-danger btn-pequeno';
            btnEliminar.title = 'Quitar artículo';
            btnEliminar.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
            btnEliminar.addEventListener('click', function () {
                items.splice(indice, 1);
                renderizarCarrito();
            });
            tdEliminar.appendChild(btnEliminar);

            tr.appendChild(tdNombre);
            tr.appendChild(tdLista);
            tr.appendChild(tdDesc);
            tr.appendChild(tdFinal);
            tr.appendChild(tdCant);
            tr.appendChild(tdImporte);
            tr.appendChild(tdEliminar);
            cuerpo.appendChild(tr);
        });
        calcularTotales();
    }

    function linea(indice) {
        return items[indice];
    }

    // ---------- Totales (mismo criterio que el backend) ----------
    function calcularTotales() {
        var subtotalLista = 0, descuento = 0, base15 = 0, isv15 = 0, base18 = 0, isv18 = 0, exento = 0, exonerado = 0, totalFinal = 0;
        items.forEach(function (it) {
            var cantidad = Number(it.cantidad) || 0;
            var lista = Number(it.precio_lista) || 0;
            var final = Number(it.precio_v) || 0;
            var subtotal = cantidad * final;
            subtotalLista += cantidad * lista;
            descuento += cantidad * (lista - final);
            var montoIsv = it.porcentaje_isv > 0 ? subtotal - (subtotal / (1 + it.porcentaje_isv / 100)) : 0;
            var base = subtotal - montoIsv;
            if (it.clave_isv === 'exento') { exento += subtotal; }
            else if (it.clave_isv === 'exonerado') { exonerado += subtotal; }
            else if (it.porcentaje_isv >= 18) { base18 += base; isv18 += montoIsv; }
            else { base15 += base; isv15 += montoIsv; }
            totalFinal += subtotal;
        });
        document.getElementById('cot-res-subtotal').textContent = moneda(subtotalLista);
        document.getElementById('cot-res-descuento').textContent = '- ' + moneda(descuento);
        document.getElementById('cot-res-base15').textContent = moneda(base15);
        document.getElementById('cot-res-isv15').textContent = moneda(isv15);
        document.getElementById('cot-res-base18').textContent = moneda(base18);
        document.getElementById('cot-res-isv18').textContent = moneda(isv18);
        document.getElementById('cot-res-exento').textContent = moneda(exento + exonerado);
        document.getElementById('cot-res-total').textContent = moneda(totalFinal);
    }

    // ---------- Guardar ----------
    function construirPayload() {
        return {
            csrf_token: CSRF_TOKEN,
            cliente_id: cliente.id,
            cliente_nombre: document.getElementById('cot-cliente-nombre').value.trim(),
            cliente_rtn: document.getElementById('cot-cliente-rtn').value.trim(),
            cliente_telefono: document.getElementById('cot-cliente-telefono').value.trim(),
            cliente_direccion: document.getElementById('cot-cliente-direccion').value.trim(),
            observaciones: document.getElementById('cot-observaciones').value.trim(),
            fecha_validez: document.getElementById('cot-fecha-validez').value,
            productos: items.map(function (it) {
                return {
                    id: it.id,
                    item_key: it.item_key,
                    cantidad: Number(it.cantidad) || 0,
                    precio_lista: Number(it.precio_lista) || 0,
                    precio_unitario: Number(it.precio_v) || 0,
                    descuento_unitario: Number(it.descuento) || 0,
                    tipo_presentacion: it.tipo_presentacion,
                    nombre_presentacion: it.nombre_presentacion,
                    factor_unidades: Number(it.factor_unidades) || 1
                };
            })
        };
    }

    function guardar(imprimir) {
        if (items.length === 0) {
            mostrarMensaje('Agrega al menos un producto a la cotización.');
            return;
        }
        var ruta = edicion ? URL + 'cotizaciones/actualizar/' + cotizacionId : URL + 'cotizaciones/guardar';
        var btn = imprimir ? document.getElementById('cot-btn-imprimir') : document.getElementById('cot-btn-guardar');
        btn.disabled = true;
        var textoOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        fetch(ruta, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(construirPayload())
        })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                if (!resp.exito) {
                    mostrarMensaje(resp.mensaje || 'No se pudo guardar la cotización.');
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                    return;
                }
                if (imprimir) {
                    if (typeof PDV_WEBAPP !== 'undefined') {
                        if (PDV_WEBAPP.instalada) {
                            // WebApp instalada: no abrir ventana; la impresión se hace
                            // al llegar a la vista "ver" (la URL queda pendiente).
                            PDV_WEBAPP.imprimirAlVolver(resp.url_imprimir);
                        } else {
                            // Navegador normal: reutiliza la ventana de ticket del POS.
                            PDV_WEBAPP.abrirTicket(resp.url_imprimir);
                        }
                    } else {
                        var imp = window.open(resp.url_imprimir, '_blank');
                        if (imp) imp.focus();
                    }
                }
                window.location.href = URL + 'cotizaciones/ver/' + resp.cotizacion_id;
            })
            .catch(function () {
                mostrarMensaje('Error de conexión al guardar la cotización.');
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            });
    }

    // ---------- Sección plegable de datos del cliente ----------
    function vincularPlegadoCliente() {
        var seccion = document.getElementById('cot-seccion-cliente');
        var contenido = document.getElementById('cot-cliente-contenido');
        if (!seccion || !contenido) return;

        var leyenda = document.getElementById('cot-leyenda-cliente');
        var boton = document.getElementById('cot-plegar-cliente');

        function aplicar() {
            var abierto = seccion.getAttribute('data-abierto') === 'true';
            seccion.setAttribute('data-abierto', abierto ? 'true' : 'false');
            if (boton) boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        }

        function alternar() {
            var abierto = seccion.getAttribute('data-abierto') === 'true';
            seccion.setAttribute('data-abierto', abierto ? 'false' : 'true');
            aplicar();
        }

        if (leyenda && !leyenda.__conectado) {
            leyenda.__conectado = true;
            leyenda.addEventListener('click', function (e) {
                if (e.target.closest('.cot-plegar-btn')) return;
                alternar();
            });
        }
        if (boton && !boton.__conectado) {
            boton.__conectado = true;
            boton.addEventListener('click', alternar);
        }
        aplicar();
    }

    // ---------- Init ----------
    poblarClientes();
    vincularBuscadorCliente();
    vincularModalCliente();
    vincularPlegadoCliente();

    if (items.length === 0 && ITEMS_INICIALES && ITEMS_INICIALES.length) {
        items = ITEMS_INICIALES.map(function (p) {
            return {
                id: p.id,
                item_key: p.item_key,
                codigo_barras: p.codigo_barras || '',
                nombre: p.nombre,
                nombre_original: p.nombre_original || p.nombre,
                precio_lista: Number(p.precio_lista) || 0,
                precio_v: Number(p.precio_unitario) || 0,
                descuento: Number(p.descuento_unitario) || 0,
                cantidad: Number(p.cantidad) || 1,
                stock: Number(p.stock) || 0,
                stock_minimo: Number(p.stock_minimo) || 0,
                unidad_medida: p.unidad_medida || 'unidad',
                permite_decimales: !!p.permite_decimales,
                tipo_presentacion: p.tipo_presentacion || 'unidad',
                nombre_presentacion: p.nombre_presentacion || 'Unidad',
                factor_unidades: Number(p.factor_unidades) || 1,
                clave_isv: p.clave_isv || 'gravado_15',
                porcentaje_isv: Number(p.porcentaje_isv) || 15,
                imagen: p.imagen || ''
            };
        });
    }

    document.getElementById('cot-fecha-validez').value = COTIZACION_FECHA_VALIDEZ || '';
    document.getElementById('cot-observaciones').value = COTIZACION_OBSERVACIONES || '';
    renderizarCarrito();

    inputBusqueda.addEventListener('input', function () {
        var t = this.value.trim();
        window.clearTimeout(temporizadorBusqueda);
        if (t.length < 2) {
            document.getElementById('cot-resultados').hidden = true;
            return;
        }
        temporizadorBusqueda = window.setTimeout(function () { buscarProductos(t); }, 250);
    });
    inputBusqueda.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var t = this.value.trim();
            if (t === '') return;
            if (esCodigoBuscable(t)) {
                // Parece código de barras: se agrega directo (+1 por escaneo)
                agregarPorCodigoBarras(t);
            } else if (t.length >= 2) {
                buscarProductos(t);
            }
        }
    });

    document.getElementById('cot-btn-guardar').addEventListener('click', function () { guardar(false); });
    document.getElementById('cot-btn-imprimir').addEventListener('click', function () { guardar(true); });

    function vaciarCarrito() {
        if (!items.length) return;
        items = [];
        renderizarCarrito();
        document.getElementById('cot-busqueda-input').value = '';
        document.getElementById('cot-resultados').hidden = true;
    }
    window.POS_COTIZACIONES_VACIAR = vaciarCarrito;
}());