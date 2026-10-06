/* ============================================================
   Web PDV Advanced - VISTA ODOO DE VENTAS
   Alterna la pantalla de ventas entre tabla clásica y catálogo
   visual estilo Odoo (productos con imagen a la izquierda,
   ticket + cobro a la derecha, zona de cliente arriba).
   NO duplica lógica de negocio: el carrito, el cobro, los tickets
   y los atajos F6-F12 siguen en pos.js; aquí solo se invoca su
   API pública (window.POS_*).
   ============================================================ */

(function () {
    'use strict';

    var CLAVE_VISTA = 'web_pdv_vista_venta';
    var MODO_ODOO = 'odoo';
    var MODO_TABLA = 'tabla';

    /* ---------- Utilidades ---------- */

    function escaparHtml(valor) {
        var span = document.createElement('span');
        span.textContent = valor == null ? '' : String(valor);
        return span.innerHTML;
    }

    function debounce(fn, ms) {
        var t = null;
        return function () {
            var args = arguments;
            var ctx = this;
            window.clearTimeout(t);
            t = window.setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function formatearPrecio(valor) {
        var n = Number(valor || 0);
        var simbolo = (typeof MONEDA_SIMBOLO !== 'undefined') ? MONEDA_SIMBOLO : 'L ';
        return simbolo + n.toFixed(2);
    }

    // Replica las reglas de Producto::urlImagen() en PHP.
    function urlImagenProducto(valor) {
        var v = String(valor || '').trim();
        if (!v) return '';
        if (/^https?:\/\//i.test(v)) return v;
        var prefijo = 'uploads/productos/';
        if (v.indexOf(prefijo) === 0) v = v.substring(prefijo.length);
        v = v.replace(/^\/+/, '');
        if (!v) return '';
        var base = (typeof URL_BASE !== 'undefined') ? URL_BASE : './';
        return base + 'uploads/productos/' + v;
    }

    function vistaActual() {
        try {
            return window.localStorage.getItem(CLAVE_VISTA) === MODO_ODOO ? MODO_ODOO : MODO_TABLA;
        } catch (e) {
            return MODO_TABLA;
        }
    }

    function contenedorVista() {
        return document.querySelector('.pos-ventas-main');
    }

    /* Altura del grid medida con píxeles reales: garantiza el scroll
       vertical en la zona del catálogo sin depender de la cadena flex/grid. */
    function ajustarAlturaCatalogo() {
        var main = contenedorVista();
        var grid = document.getElementById('odoo-catalogo-grid');
        if (!main || !grid) return;
        if (main.getAttribute('data-vista') !== MODO_ODOO) {
            grid.style.maxHeight = '';
            return;
        }
        var mainRect = main.getBoundingClientRect();
        var gridTop = grid.getBoundingClientRect().top;
        var disponible = Math.floor(mainRect.bottom - gridTop - 18);
        if (disponible > 200) {
            grid.style.maxHeight = disponible + 'px';
        }
    }

    var alturaAjustadaResize = false;

    /* ---------- Toggle de vista ---------- */

    var catalogoIniciado = false;

    function aplicarVista(modo) {
        var main = contenedorVista();
        if (!main) return;
        if (modo === MODO_ODOO) {
            main.setAttribute('data-vista', MODO_ODOO);
            if (!catalogoIniciado) {
                catalogoIniciado = true;
                iniciarCatalogo();
            }
        } else {
            main.setAttribute('data-vista', MODO_TABLA);
        }
        try { window.localStorage.setItem(CLAVE_VISTA, modo); } catch (e) {}
        actualizarBotonToggle(modo);
        refrescarZonaCliente();
        // Medir tras el reflow para que el grid ya tenga su posición final.
        window.requestAnimationFrame(function () {
            ajustarAlturaCatalogo();
        });
    }

    function actualizarBotonToggle(modo) {
        var btn = document.getElementById('btn-vista-odoo');
        if (!btn) return;
        var esOdoo = modo === MODO_ODOO;
        btn.classList.toggle('is-activo', esOdoo);
        btn.setAttribute('aria-pressed', esOdoo ? 'true' : 'false');
        btn.setAttribute('title', esOdoo ? 'Volver a la vista de tabla' : 'Alternar entre vista de tabla y vista catálogo estilo Odoo');
        var etiqueta = btn.querySelector('span');
        if (etiqueta) etiqueta.textContent = esOdoo ? 'Vista tabla' : 'Vista catálogo';
    }

    /* ---------- Catálogo visual: todas / por categoría + paginación ---------- */

    var abortadorCatalogo = null;
    var estadoCatalogo = { q: '', cat: 0, pagina: 1, limite: 50, cargando: false, hayMas: true };

    // Autodiagnóstico: verifica que odoo.css esté aplicado (border-radius
    // solo existe en la hoja). Si no, avisa en la propia vista.
    function cssAplicado() {
        var sonda = document.createElement('span');
        sonda.className = 'odoo-producto-precio';
        sonda.style.position = 'absolute';
        sonda.style.visibility = 'hidden';
        document.body.appendChild(sonda);
        var ok = false;
        try {
            var cs = window.getComputedStyle(sonda);
            ok = !!cs && parseFloat(cs.borderRadius) > 0;
        } catch (e) { ok = false; }
        sonda.remove();
        return ok;
    }

    function iniciarCatalogo() {
        var estado = document.getElementById('odoo-catalogo-estado');
        if (!cssAplicado() && estado) {
            estado.textContent = 'Aviso: los estilos están desactualizados. Recarga la página con Ctrl+F5.';
        }
        var input = document.getElementById('odoo-catalogo-busqueda');
        if (input) {
            input.addEventListener('input', debounce(function () {
                estadoCatalogo.q = input.value.trim();
                recargarCatalogo();
            }, 300));
        }
        cargarCategorias();
        recargarCatalogo();
    }

    function recargarCatalogo() {
        estadoCatalogo.pagina = 1;
        estadoCatalogo.hayMas = true;
        cargarCatalogo(true);
    }

    function cargarCatalogo(reemplazar) {
        var estado = document.getElementById('odoo-catalogo-estado');
        var grid = document.getElementById('odoo-catalogo-grid');
        if (!grid || estadoCatalogo.cargando) return;
        estadoCatalogo.cargando = true;
        actualizarBotonMas();
        if (abortadorCatalogo) abortadorCatalogo.abort();
        abortadorCatalogo = ('AbortController' in window) ? new AbortController() : null;
        if (estado && reemplazar) estado.textContent = 'Buscando productos…';
        var base = (typeof URL_BASE !== 'undefined') ? URL_BASE : './';
        var url = base + 'ventas/catalogo?q=' + encodeURIComponent(estadoCatalogo.q) +
            '&categoria_id=' + estadoCatalogo.cat +
            '&limite=' + estadoCatalogo.limite +
            '&pagina=' + estadoCatalogo.pagina;
        var opciones = { credentials: 'same-origin' };
        if (abortadorCatalogo) opciones.signal = abortadorCatalogo.signal;
        fetch(url, opciones)
            .then(function (resp) { return resp.json(); })
            .then(function (productos) {
                estadoCatalogo.cargando = false;
                var lista = Array.isArray(productos) ? productos : [];
                estadoCatalogo.hayMas = lista.length >= estadoCatalogo.limite;
                renderizarCatalogo(lista, reemplazar);
                actualizarBotonMas();
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                estadoCatalogo.cargando = false;
                if (estado && reemplazar) estado.textContent = 'No se pudo cargar el catálogo.';
                if (reemplazar) grid.innerHTML = '';
                actualizarBotonMas();
            });
    }

    function renderizarCatalogo(productos, reemplazar) {
        var estado = document.getElementById('odoo-catalogo-estado');
        var grid = document.getElementById('odoo-catalogo-grid');
        if (!grid) return;
        retirarBotonMas();
        if (reemplazar) {
            grid.innerHTML = '';
            grid.scrollTop = 0;
        }
        if (reemplazar && !productos.length) {
            if (estado) estado.textContent = 'Sin resultados. Prueba con otro nombre, código o categoría.';
            var vacio = document.createElement('p');
            vacio.className = 'odoo-catalogo-vacio';
            vacio.textContent = 'No hay productos para mostrar.';
            grid.appendChild(vacio);
            return;
        }
        productos.forEach(function (p) {
            grid.appendChild(crearTarjetaProducto(p));
        });
        ajustarAlturaCatalogo();
        var total = grid.querySelectorAll('.odoo-producto').length;
        if (estado) {
            estado.textContent = total + (total === 1 ? ' producto' : ' productos') +
                ' · Toca uno para agregarlo al ticket.' +
                (estadoCatalogo.hayMas ? '' : ' · Fin del catálogo.');
        }
        if (estadoCatalogo.hayMas) {
            var mas = document.createElement('button');
            mas.type = 'button';
            mas.id = 'odoo-catalogo-mas';
            mas.className = 'odoo-catalogo-mas';
            mas.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i> Cargar más';
            mas.addEventListener('click', function () {
                estadoCatalogo.pagina += 1;
                cargarCatalogo(false);
            });
            grid.appendChild(mas);
        }
    }

    function retirarBotonMas() {
        var grid = document.getElementById('odoo-catalogo-grid');
        if (!grid) return;
        var anterior = document.getElementById('odoo-catalogo-mas');
        if (anterior) anterior.remove();
    }

    function actualizarBotonMas() {
        var mas = document.getElementById('odoo-catalogo-mas');
        if (!mas) return;
        mas.disabled = estadoCatalogo.cargando;
        mas.innerHTML = estadoCatalogo.cargando
            ? '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Cargando…'
            : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Cargar más';
    }

    function cargarCategorias() {
        var caja = document.getElementById('odoo-catalogo-categorias');
        if (!caja) return;
        var base = (typeof URL_BASE !== 'undefined') ? URL_BASE : './';
        fetch(base + 'ventas/catalogo-categorias', { credentials: 'same-origin' })
            .then(function (resp) { return resp.json(); })
            .then(function (cats) {
                renderizarCategorias(Array.isArray(cats) ? cats : []);
            })
            .catch(function () {
                renderizarCategorias([]);
            });
    }

    function renderizarCategorias(cats) {
        var caja = document.getElementById('odoo-catalogo-categorias');
        if (!caja) return;
        caja.innerHTML = '';
        var todas = document.createElement('button');
        todas.type = 'button';
        todas.className = 'odoo-cat is-activo';
        todas.setAttribute('role', 'tab');
        todas.setAttribute('aria-selected', 'true');
        todas.textContent = 'Todas';
        todas.addEventListener('click', function () {
            seleccionarCategoria(0, todas);
        });
        caja.appendChild(todas);
        cats.forEach(function (c) {
            var pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'odoo-cat';
            pill.setAttribute('role', 'tab');
            pill.setAttribute('aria-selected', 'false');
            pill.innerHTML = escaparHtml(c.nombre) + ' <span class="odoo-cat-total">' + Number(c.total || 0) + '</span>';
            pill.addEventListener('click', function () {
                seleccionarCategoria(Number(c.id), pill);
            });
            caja.appendChild(pill);
        });
    }

    function seleccionarCategoria(id, pill) {
        var caja = document.getElementById('odoo-catalogo-categorias');
        if (caja) {
            Array.prototype.forEach.call(caja.querySelectorAll('.odoo-cat'), function (el) {
                el.classList.remove('is-activo');
                el.setAttribute('aria-selected', 'false');
            });
        }
        if (pill) {
            pill.classList.add('is-activo');
            pill.setAttribute('aria-selected', 'true');
        }
        estadoCatalogo.cat = Number(id) || 0;
        recargarCatalogo();
    }

    // Construcción con DOM API (sin innerHTML): inmune a comillas o
    // caracteres raros en nombres, códigos o rutas de imagen.
    function crearTarjetaProducto(p) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'odoo-producto';
        btn.setAttribute('role', 'listitem');
        btn.title = 'Agregar: ' + String(p.nombre || '');

        var media = document.createElement('span');
        media.className = 'odoo-producto-img';
        media.style.display = 'flex';
        media.style.alignItems = 'center';
        media.style.justifyContent = 'center';
        media.style.minHeight = '110px';
        var src = urlImagenProducto(p.imagen);
        if (src) {
            var img = document.createElement('img');
            img.setAttribute('src', src);
            img.setAttribute('alt', '');
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function () {
                media.textContent = '';
                var iconoFallo = document.createElement('i');
                iconoFallo.className = 'fa-solid fa-box-open';
                iconoFallo.setAttribute('aria-hidden', 'true');
                media.appendChild(iconoFallo);
            });
            media.appendChild(img);
        } else {
            var icono = document.createElement('i');
            icono.className = 'fa-solid fa-box-open';
            icono.setAttribute('aria-hidden', 'true');
            media.appendChild(icono);
        }
        btn.appendChild(media);

        var cuerpo = document.createElement('span');
        cuerpo.className = 'odoo-producto-cuerpo';
        cuerpo.style.display = 'block';
        cuerpo.style.padding = '10px 12px 12px';

        var nombre = document.createElement('span');
        nombre.className = 'odoo-producto-nombre';
        nombre.style.display = 'block';
        nombre.style.margin = '0 0 6px';
        nombre.textContent = String(p.nombre || '');
        cuerpo.appendChild(nombre);

        var precio = document.createElement('span');
        precio.className = 'odoo-producto-precio';
        precio.style.display = 'inline-block';
        precio.style.margin = '0 0 6px';
        precio.textContent = formatearPrecio(p.precio_venta);
        cuerpo.appendChild(precio);

        var stock = Number(p.stock || 0);
        var stockMin = Number(p.stock_minimo || 0);
        var chip = document.createElement('span');
        chip.className = 'odoo-producto-stock' +
            (stock <= 0 ? ' is-cero' : (stockMin > 0 && stock <= stockMin ? ' is-bajo' : ''));
        chip.style.display = 'inline-block';
        chip.textContent = stock <= 0 ? 'Sin stock' : ('Stock: ' + stock);
        cuerpo.appendChild(chip);

        btn.appendChild(cuerpo);
        btn.addEventListener('click', function () {
            agregarDesdeCatalogo(p);
        });
        return btn;
    }

    // Mismo mapeo que seleccionarProductoBusqueda() en pos.js
    function agregarDesdeCatalogo(p) {
        if (typeof window.POS_agregarProducto !== 'function') {
            alert('El módulo de ventas aún se está cargando. Intenta de nuevo.');
            return;
        }
        window.POS_agregarProducto({
            id: Number(p.id),
            item_key: p.item_key || (p.id + '_' + (p.tipo_presentacion || 'unidad')),
            codigo_barras: p.codigo_barras,
            nombre: p.nombre,
            precio_venta: Number(p.precio_venta),
            stock: Number(p.stock),
            stock_minimo: Number(p.stock_minimo || 0),
            unidad_medida: p.unidad_medida || 'unidad',
            permite_decimales: Boolean(p.permite_decimales) || (p.unidad_medida || 'unidad') !== 'unidad',
            tipo_presentacion: p.tipo_presentacion || 'unidad',
            nombre_presentacion: p.nombre_presentacion || 'Unidad',
            factor_unidades: Number(p.factor_unidades || 1)
        });
    }

    /* ---------- Zona de cliente (ver + editar) ---------- */

    function ticketCliente() {
        var t = null;
        try {
            t = (typeof window.POS_ticketActivo === 'function') ? window.POS_ticketActivo() : null;
        } catch (e) { t = null; }
        return (t && t.cliente) ? t.cliente : null;
    }

    function refrescarZonaCliente() {
        var nombreEl = document.getElementById('odoo-cliente-nombre');
        if (!nombreEl) return;
        var metaEl = document.getElementById('odoo-cliente-meta');
        var avatarEl = document.getElementById('odoo-cliente-avatar');
        var folioEl = document.getElementById('odoo-cliente-cotizacion');
        var quitarBtn = document.getElementById('odoo-cliente-quitar');
        var c = ticketCliente() || {};
        var nombre = String(c.nombre || '').trim();
        var hayCliente = nombre !== '';
        nombreEl.textContent = hayCliente ? nombre : 'Consumidor Final';
        var partes = [];
        if (String(c.rtn || '').trim() !== '') partes.push('RTN ' + c.rtn);
        if (String(c.telefono || '').trim() !== '') partes.push(c.telefono);
        if (metaEl) metaEl.textContent = partes.length ? partes.join(' • ') : 'Sin RTN registrado';
        if (avatarEl) {
            avatarEl.innerHTML = hayCliente
                ? '<strong>' + escaparHtml(nombre.charAt(0).toUpperCase()) + '</strong>'
                : '<i class="fa-solid fa-user"></i>';
        }
        var t = null;
        try { t = (typeof window.POS_ticketActivo === 'function') ? window.POS_ticketActivo() : null; } catch (e) {}
        var folio = t ? String(t.cotizacion_folio || '') : '';
        if (folioEl) {
            if (folio !== '') {
                folioEl.hidden = false;
                folioEl.textContent = 'Cot ' + folio;
            } else {
                folioEl.hidden = true;
                folioEl.textContent = '';
            }
        }
        if (quitarBtn) quitarBtn.style.display = hayCliente ? '' : 'none';
    }

    // pos.js la invoca al final de cada renderizarTodo().
    window.POS_odooRefrescar = refrescarZonaCliente;

    var abortadorCliente = null;

    function buscarClientes(termino) {
        var caja = document.getElementById('odoo-cliente-resultados');
        if (!caja) return;
        if (abortadorCliente) abortadorCliente.abort();
        abortadorCliente = ('AbortController' in window) ? new AbortController() : null;
        var base = (typeof URL_BASE !== 'undefined') ? URL_BASE : './';
        var url = base + 'clientes/buscar?busqueda=' + encodeURIComponent(termino) + '&limite=8';
        var opciones = { credentials: 'same-origin' };
        if (abortadorCliente) opciones.signal = abortadorCliente.signal;
        fetch(url, opciones)
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                var lista = Array.isArray(data) ? data : (data.clientes || data.data || []);
                renderizarClientes(lista);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                renderizarClientes([]);
            });
    }

    function renderizarClientes(lista) {
        var caja = document.getElementById('odoo-cliente-resultados');
        if (!caja) return;
        caja.innerHTML = '';
        if (!lista.length) {
            caja.hidden = true;
            return;
        }
        caja.hidden = false;
        lista.forEach(function (c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'odoo-cliente-opcion';
            var linea = String(c.rtn_identidad || '').trim();
            if (String(c.telefono || '').trim() !== '') {
                linea += (linea ? ' • ' : '') + c.telefono;
            }
            btn.innerHTML = '<strong>' + escaparHtml(c.nombre) + '</strong>' +
                (linea ? '<small>' + escaparHtml(linea) + '</small>' : '');
            btn.addEventListener('click', function () {
                asignarCliente({
                    id: c.id,
                    nombre: c.nombre,
                    rtn: c.rtn_identidad,
                    telefono: c.telefono,
                    direccion: c.direccion
                });
            });
            caja.appendChild(btn);
        });
    }

    function asignarCliente(cliente) {
        if (typeof window.POS_definirCliente !== 'function') return;
        window.POS_definirCliente(cliente || {});
        var panel = document.getElementById('odoo-cliente-buscador');
        var input = document.getElementById('odoo-cliente-input');
        var caja = document.getElementById('odoo-cliente-resultados');
        if (input) input.value = '';
        if (caja) { caja.hidden = true; caja.innerHTML = ''; }
        if (panel) panel.hidden = true;
    }

    /* ---------- Arranque ---------- */

    document.addEventListener('DOMContentLoaded', function () {
        try { console.info('[ODOO] build 2026-10-05-r4 (tarjetas DOM + pills + scroll medido)'); } catch (e) {}
        var main = contenedorVista();
        if (!main) return;

        aplicarVista(vistaActual());

        // Pill "Todas" estática: funciona aunque falle la carga de categorías.
        var pillTodas = document.querySelector('#odoo-catalogo-categorias .odoo-cat[data-cat="0"]');
        if (pillTodas && !pillTodas.dataset.odooWired) {
            pillTodas.dataset.odooWired = 'true';
            pillTodas.addEventListener('click', function () {
                seleccionarCategoria(0, pillTodas);
            });
        }

        var toggle = document.getElementById('btn-vista-odoo');
        if (toggle && !toggle.dataset.odooWired) {
            toggle.dataset.odooWired = 'true';
            toggle.addEventListener('click', function () {
                var actual = main.getAttribute('data-vista') === MODO_ODOO ? MODO_ODOO : MODO_TABLA;
                aplicarVista(actual === MODO_ODOO ? MODO_TABLA : MODO_ODOO);
            });
        }

        var cambiarBtn = document.getElementById('odoo-cliente-cambiar');
        var panel = document.getElementById('odoo-cliente-buscador');
        var input = document.getElementById('odoo-cliente-input');
        if (cambiarBtn && panel) {
            cambiarBtn.addEventListener('click', function () {
                panel.hidden = !panel.hidden;
                if (!panel.hidden && input) input.focus();
            });
        }
        if (input) {
            input.addEventListener('input', debounce(function () {
                var q = input.value.trim();
                if (q.length < 2) {
                    var caja = document.getElementById('odoo-cliente-resultados');
                    if (caja) { caja.hidden = true; caja.innerHTML = ''; }
                    return;
                }
                buscarClientes(q);
            }, 250));
        }
        var quitarBtn = document.getElementById('odoo-cliente-quitar');
        if (quitarBtn) {
            quitarBtn.addEventListener('click', function () {
                asignarCliente({});
            });
        }

        if (!alturaAjustadaResize) {
            alturaAjustadaResize = true;
            window.addEventListener('resize', debounce(function () {
                ajustarAlturaCatalogo();
            }, 150));
        }

        refrescarZonaCliente();
        ajustarAlturaCatalogo();
    });
})();
