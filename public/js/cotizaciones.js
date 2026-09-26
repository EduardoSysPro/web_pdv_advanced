(function () {
    'use strict';

    var URL = URL_BASE;
    var MONEDA = typeof MONEDA_SIMBOLO !== 'undefined' ? MONEDA_SIMBOLO : 'L';
    var items = [];            // carrito
    var cliente = { id: 0, tipo: 'minorista' };   // snapshot del cliente
    var edicion = MODO_EDICION === 1;
    var cotizacionId = COTIZACION_ID;

    // Precio mayorista: solo si el cliente es mayorista registrado.
    function esMayorista() {
        return cliente.tipo === 'mayorista';
    }

    function tipoDeCliente(id) {
        for (var i = 0; i < CLIENTES.length; i++) {
            if (Number(CLIENTES[i].id) === Number(id)) {
                return CLIENTES[i].tipo === 'mayorista' ? 'mayorista' : 'minorista';
            }
        }
        return 'minorista';
    }

    // Precio aplicable según cliente: {lista, final, mayorista}.
    // La lista siempre es el precio normal; el final es el Precio 2
    // solo para mayoristas en venta por unidad.
    function precioParaCliente(p, presentacion) {
        var lista = Number(p.precio_venta) || 0;
        var pm = Number(p.precio_mayorista) || 0;
        if (esMayorista() && presentacion !== 'empaque' && pm > 0) {
            return { lista: lista, final: pm, mayorista: true };
        }
        return { lista: lista, final: lista, mayorista: false };
    }

    // Re-cotiza los artículos por unidad al cambiar de cliente:
    // mayorista -> Precio 2, resto -> precio normal. El empaque no cambia.
    function reaplicarPrecios() {
        var cambiados = 0;
        items.forEach(function (it) {
            if (!it.id || it.tipo_presentacion === 'empaque') return;
            var pm = Number(it.precio_mayorista) || 0;
            var lista = Number(it.precio_lista) || 0;
            var final = (esMayorista() && pm > 0) ? pm : lista;
            var esMay = (esMayorista() && pm > 0 && final < lista);
            if (Number(it.precio_v) !== final || !!it.es_mayorista !== esMay) {
                cambiados++;
            }
            it.precio_v = final;
            it.descuento = Math.max(0, Math.round((lista - final) * 100) / 100);
            it.es_mayorista = esMay;
        });
        if (cambiados > 0) {
            renderizarCarrito();
            mostrarMensaje(esMayorista()
                ? 'Precios actualizados a mayorista en ' + cambiados + ' artículo(s).'
                : 'Precios actualizados a lista normal.');
        } else {
            renderizarCarrito();
        }
    }

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
                        direccion: encontrado.direccion,
                        tipo: encontrado.tipo
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
                        direccion: c.direccion,
                        tipo: c.tipo
                    }, true);
                } else {
                    cliente.id = 0;
                    cliente.tipo = 'minorista';
                    actualizarEtiquetaCliente();
                    reaplicarPrecios();
                }
            });
        }
    }

    function actualizarEtiquetaCliente() {
        var campo = document.getElementById('cot-cliente-tipo');
        if (campo) {
            campo.value = esMayorista() ? 'Mayorista (Precio 2)' : 'Minorista';
        }
    }

    function aplicarCliente(sel, sobreescribir) {
        cliente.id = sel.id || 0;
        cliente.tipo = sel.tipo === 'mayorista' ? 'mayorista' : tipoDeCliente(cliente.id);
        if (!sobreescribir || !document.getElementById('cot-cliente-nombre').value) {
            document.getElementById('cot-cliente-nombre').value = sel.nombre || '';
        }
        document.getElementById('cot-cliente-rtn').value = sel.rtn || '';
        document.getElementById('cot-cliente-telefono').value = sel.telefono || '';
        document.getElementById('cot-cliente-direccion').value = sel.direccion || '';
        actualizarEtiquetaCliente();
        reaplicarPrecios();
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
            params.append('tipo', document.getElementById('mc-tipo').value === 'mayorista' ? 'mayorista' : 'minorista');

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
                    var nuevoTipo = nuevo.tipo === 'mayorista' ? 'mayorista' : 'minorista';
                    CLIENTES.push({
                        id: nuevo.id,
                        nombre: nuevo.nombre,
                        rtn_identidad: nuevo.rtn_identidad || '',
                        telefono: nuevo.telefono || '',
                        direccion: nuevo.direccion || '',
                        tipo: nuevoTipo
                    });
                    construirDatalists();
                    cerrar();
                    aplicarCliente({
                        id: nuevo.id,
                        nombre: nuevo.nombre,
                        rtn: nuevo.rtn_identidad || '',
                        telefono: nuevo.telefono || '',
                        direccion: nuevo.direccion || '',
                        tipo: nuevoTipo
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
            var pmRes = Number(p.precio_mayorista) || 0;
            if (esMayorista() && pmRes > 0 && p.tipo_presentacion !== 'empaque') {
                var smallMay = document.createElement('small');
                smallMay.style.display = 'block';
                smallMay.style.color = '#0369a1';
                smallMay.textContent = 'May. ' + moneda(pmRes);
                tdPrecio.appendChild(smallMay);
            }
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
            var pr = precioParaCliente(p, p.tipo_presentacion);
            items.push({
                id: p.id,
                item_key: p.item_key,
                codigo_barras: p.codigo_barras || '',
                nombre: p.nombre,
                nombre_original: p.nombre_original || p.nombre,
                precio_lista: pr.lista,
                precio_v: pr.final,
                descuento: Math.max(0, Math.round((pr.lista - pr.final) * 100) / 100),
                precio_mayorista: Number(p.precio_mayorista) || 0,
                es_mayorista: pr.mayorista && pr.final < pr.lista,
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
    function construirFilaArticulo(it, indice) {
        var itemKey = it.item_key || (String(it.id) + '_' + (it.tipo_presentacion || 'unidad'));
        var tr = document.createElement('tr');
        tr.dataset.indice = String(indice);
        tr.dataset.itemKey = itemKey;

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
        if (it.es_mayorista) {
            var badgeMay = document.createElement('span');
            badgeMay.textContent = 'MAY';
            badgeMay.title = 'Precio mayorista aplicado';
            badgeMay.style.cssText = 'display:inline-block;margin-left:6px;font-size:10px;font-weight:700;color:#0369a1;background:#e0f2fe;border:1px solid #bae6fd;border-radius:4px;padding:1px 5px;vertical-align:middle;';
            tdNombre.appendChild(badgeMay);
        }

        function crearInputNumerico(valor, clase, paso, onCambio) {
            var inp = document.createElement('input');
            inp.type = 'number';
            inp.min = '0';
            inp.step = String(paso || '0.01');
            inp.value = String(valor);
            inp.className = 'form-control-pos ' + clase;
            inp.addEventListener('change', onCambio);
            inp.addEventListener('input', function () { it.guardar = true; });
            return inp;
        }

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
            items = items.filter(function (x) {
                return (x.item_key || (String(x.id) + '_' + (x.tipo_presentacion || 'unidad'))) !== itemKey;
            });
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

        // Firma del contenido para saber si esta fila cambió
        tr.dataset.firma = [it.nombre, it.imagen || '', it.precio_lista, it.descuento, it.precio_v, it.cantidad, it.permite_decimales ? 1 : 0, it.es_mayorista ? 1 : 0].join('\u0001');

        return tr;
    }

    /**
     * Dibuja el carrito reutilizando las filas existentes (clave = item_key).
     * Solo se reconstruye la fila cuyo contenido cambió: al agregar productos
     * en cadena el costo es O(1) por producto en vez de reconstruir todo el DOM
     * (que es lo que congelaba el navegador con muchos artículos).
     */
    function renderizarCarrito() {
        var cuerpo = document.getElementById('cot-carrito-body');
        var vacio = document.getElementById('cot-carrito-vacio');
        if (!cuerpo) return;

        var filasExistentes = new Map();
        Array.prototype.forEach.call(cuerpo.children, function (tr) {
            if (tr && tr.dataset && tr.dataset.itemKey) filasExistentes.set(tr.dataset.itemKey, tr);
        });

        var fragmento = document.createDocumentFragment();

        items.forEach(function (it, indice) {
            var itemKey = it.item_key || (String(it.id) + '_' + (it.tipo_presentacion || 'unidad'));
            var tr = filasExistentes.get(itemKey);
            if (tr) filasExistentes.delete(itemKey);

            var nuevaFila = construirFilaArticulo(it, indice);
            if (tr && tr.dataset.firma === nuevaFila.dataset.firma) {
                // Sin cambios: conserva la fila actual (mantiene inputs y foco intactos)
                tr.dataset.indice = String(indice);
                fragmento.appendChild(tr);
            } else {
                fragmento.appendChild(nuevaFila);
            }
        });

        // Elimina filas de artículos que ya no están en el carrito
        filasExistentes.forEach(function (tr) { tr.remove(); });

        cuerpo.appendChild(fragmento);
        vacio.style.display = items.length === 0 ? '' : 'none';
        calcularTotales();
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
                precio_mayorista: Number(p.precio_mayorista) || 0,
                es_mayorista: !!p.es_mayorista,
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

    // ---------- Escáner en vivo por cámara (silencioso, sin vista previa) ----------
    (function () {
        var btnEscaneo = document.getElementById('cot-btn-escaneo');
        if (!btnEscaneo) return;

        var estado = document.getElementById('cot-escaneo-estado');
        var activo = false;
        var motor = null;          // 'nativo' | 'quagga'
        var stream = null;
        var video = null;
        var lienzo = null;
        var contexto = null;
        var detector = null;
        var rafId = null;
        var quaggaTarget = null;
        var ultimoCuadro = 0;
        var ultimaCoincidencia = 0;
        var COOLDOWN_MS = 2200;
        var FORMATOS_NATIVO = ['ean_13', 'ean_8', 'code_128', 'code_39', 'code_93', 'upc_a', 'upc_e', 'itf', 'codabar'];

        function mostrarEstado(texto, tipo) {
            if (!estado) return;
            if (texto) {
                estado.textContent = texto;
                estado.hidden = false;
                estado.setAttribute('data-tipo', tipo || 'info');
            } else {
                estado.hidden = true;
            }
        }

        function destello() {
            btnEscaneo.classList.add('flash');
            window.setTimeout(function () { btnEscaneo.classList.remove('flash'); }, 450);
        }

        function procesarCodigo(codigo) {
            codigo = String(codigo || '').trim();
            if (!codigo) return;
            var ahora = Date.now();
            if (ahora - ultimaCoincidencia < COOLDOWN_MS) return;
            ultimaCoincidencia = ahora;
            if (navigator.vibrate) { try { navigator.vibrate(80); } catch (e) {} }
            destello();
            agregarPorCodigoBarras(codigo);
        }

        function detenerTodo(mensaje) {
            activo = false;
            if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
            if (stream) {
                stream.getTracks().forEach(function (t) { try { t.stop(); } catch (e) {} });
                stream = null;
            }
            if (window.Quagga) { try { window.Quagga.stop(); } catch (e) {} }
            if (video && video.parentNode) video.parentNode.removeChild(video);
            video = null;
            if (quaggaTarget && quaggaTarget.parentNode) quaggaTarget.parentNode.removeChild(quaggaTarget);
            quaggaTarget = null;
            motor = null;
            btnEscaneo.classList.remove('activo', 'iniciando');
            btnEscaneo.innerHTML = '<i class="fa-solid fa-camera"></i>';
            btnEscaneo.title = 'Activar escaneo por cámara (silencioso)';
            mostrarEstado(mensaje || '', 'info');
            if (!mensaje) estado.hidden = true;
        }

        function prepararVideo(s) {
            video = document.createElement('video');
            video.muted = true;
            video.autoplay = true;
            video.playsInline = true;
            video.setAttribute('muted', '');
            video.setAttribute('playsinline', '');
            video.setAttribute('autoplay', '');
            video.className = 'cot-camara-oculta';
            video.srcObject = s;
            document.body.appendChild(video);
            var promesa = video.play();
            if (promesa && promesa.catch) promesa.catch(function () {});
        }

        function iniciarNativo() {
            lienzo = document.createElement('canvas');
            contexto = lienzo.getContext('2d', { willReadFrequently: true });
            detector = new window.BarcodeDetector({ formats: FORMATOS_NATIVO });

            function cuadro(tiempo) {
                if (!activo || !video) return;
                rafId = requestAnimationFrame(cuadro);
                if (tiempo - ultimoCuadro < 150) return;
                if (!video.videoWidth) return;
                ultimoCuadro = tiempo;
                try {
                    var escala = Math.min(1, 480 / video.videoWidth);
                    lienzo.width = Math.floor(video.videoWidth * escala);
                    lienzo.height = Math.floor(video.videoHeight * escala);
                    contexto.drawImage(video, 0, 0, lienzo.width, lienzo.height);
                    detector.detect(lienzo).then(function (codigos) {
                        if (!codigos || !codigos.length || !activo) return;
                        for (var i = 0; i < codigos.length; i++) {
                            if (codigos[i].rawValue) { procesarCodigo(codigos[i].rawValue); break; }
                        }
                    }).catch(function () {});
                } catch (e) {}
            }
            rafId = requestAnimationFrame(cuadro);
        }

        function iniciarQuagga() {
            if (!window.Quagga) { detenerTodo('Sin motor de detección disponible.'); return; }
            quaggaTarget = document.createElement('div');
            quaggaTarget.className = 'cot-camara-oculta';
            document.body.appendChild(quaggaTarget);
            window.Quagga.init({
                inputStream: {
                    name: 'Live',
                    type: 'LiveStream',
                    target: quaggaTarget,
                    constraints: { facingMode: 'environment', width: { min: 640 }, height: { min: 480 } }
                },
                locator: { halfSample: true, patchSize: 'medium' },
                numOfWorkers: (navigator.hardwareConcurrency && navigator.hardwareConcurrency > 1) ? Math.min(navigator.hardwareConcurrency, 4) : 1,
                decoder: { readers: ['ean_reader', 'ean_8_reader', 'code_128_reader', 'code_39_reader', 'upc_reader', 'upc_e_reader'], multiple: false },
                locate: true
            }, function (err) {
                if (err) {
                    detenerTodo('El escáner por cámara no pudo iniciar.');
                    return;
                }
                window.Quagga.onDetected(function (res) {
                    if (res && res.codeResult && res.codeResult.code) procesarCodigo(res.codeResult.code);
                });
                try { window.Quagga.start(); } catch (e) {}
                mostrarEstado('Escaneo activo: apunta el código a la cámara.', 'ok');
            });
        }

        function iniciar() {
            if (activo) return;
            if (!window.isSecureContext) {
                mostrarEstado('La cámara solo funciona con HTTPS. Accede con https para activar el escaneo.', 'error');
                return;
            }
            if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
                mostrarEstado('Este navegador no permite el acceso a la cámara.', 'error');
                return;
            }
            btnEscaneo.classList.add('iniciando');
            mostrarEstado('Solicitando permiso de cámara...', 'info');
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            }).then(function (s) {
                stream = s;
                activo = true;
                prepararVideo(s);
                btnEscaneo.classList.remove('iniciando');
                btnEscaneo.classList.add('activo');
                btnEscaneo.innerHTML = '<i class="fa-solid fa-eye"></i>';
                btnEscaneo.title = 'Detener escaneo por cámara';
                if (window.BarcodeDetector) {
                    motor = 'nativo';
                    iniciarNativo();
                    mostrarEstado('Escaneo activo: apunta el código a la cámara.', 'ok');
                } else if (window.Quagga) {
                    motor = 'quagga';
                    iniciarQuagga();
                } else {
                    detenerTodo('Navegador sin soporte de detección de códigos.');
                }
            }).catch(function (err) {
                btnEscaneo.classList.remove('iniciando');
                var nombre = (err && err.name) || '';
                if (nombre === 'NotAllowedError' || nombre === 'SecurityError') {
                    mostrarEstado('Permiso de cámara denegado. Actívalo en el navegador.', 'error');
                } else if (nombre === 'NotFoundError' || nombre === 'OverconstrainedError') {
                    mostrarEstado('No se encontró una cámara en este dispositivo.', 'error');
                } else {
                    mostrarEstado('Error al acceder a la cámara (' + nombre + ').', 'error');
                }
            });
        }

        btnEscaneo.addEventListener('click', function () {
            if (activo) detenerTodo('');
            else iniciar();
        });

        document.addEventListener('visibilitychange', function () {
            if (!activo) return;
            if (document.hidden) {
                detenerTodo('Escaneo pausado: vuelve a tocar la cámara para reanudar.');
            }
        });
    }());
}());