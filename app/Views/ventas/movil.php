<!DOCTYPE html>
<html lang="es">
<head>
    <script>(function(){try{var t=localStorage.getItem('web_pdv_tema')||'claro';if(t==='oscuro')document.documentElement.setAttribute('data-tema','oscuro');}catch(e){}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web PDV - Cotizaciones Móvil</title>
    <link rel="icon" type="image/x-icon" href="<?php echo URL_BASE; ?>favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/pos_movil.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/cotizaciones.css?v=<?php echo filemtime(PUBLIC_PATH . 'css/cotizaciones.css'); ?>">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/dark.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
</head>
<body class="pos-movil-body">
    <div class="movil-app-shell">
        <!-- Header Móvil -->
        <header class="movil-header">
            <div class="movil-brand">
                <div class="movil-brand-icon" style="background:#0ea5e9;">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div>
                    <div class="movil-brand-title"><?php echo htmlspecialchars($configuracion['nombre_negocio'] ?? 'Web PDV'); ?></div>
                    <div class="movil-brand-sub">Cotizaciones · <?php echo htmlspecialchars($nombreUsuario ?? 'Vendedor'); ?> · <?php echo htmlspecialchars($sucursalNombre ?? 'Mi Negocio'); ?></div>
                </div>
            </div>
            <div class="movil-header-actions">
                <button class="btn-tema-movil" id="pos-boton-tema" type="button" aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"><i class="fa-solid fa-moon"></i></button>
                <a href="<?php echo URL_BASE; ?>cotizaciones" class="btn-movil-logout" title="Mis cotizaciones" style="background:#d1fae5; margin-right:6px;">
                    <i class="fa-solid fa-list"></i>
                    <span>Mis</span>
                </a>
            <?php if ($esAdmin): ?>
                <a href="<?php echo URL_BASE; ?>" class="btn-movil-logout" title="Volver a la vista normal" style="background:#b9f3fd; margin-right:6px;">
                    <i class="fa-solid fa-desktop"></i>
                    <span>Escritorio</span>
                </a>
            <?php endif; ?>
                <a href="<?php echo URL_BASE; ?>logout" class="btn-movil-logout" title="Cerrar Sesión">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Salir</span>
                </a>
            </div>
        </header>

        <div class="movil-cot-contenido">
            <section class="movil-cot-intro">
                <p><i class="fa-solid fa-circle-info"></i> Registra la cotización con los productos y datos del cliente. El cajero podrá facturarla en caja en tiempo real.</p>
            </section>

            <form id="cotizacion-form" autocomplete="off">
                <div class="cotizaciones-editor-grid">
                    <div class="cotizaciones-editor-principal">
                        <fieldset class="cot-seccion cot-seccion-plegable" id="cot-seccion-cliente" data-abierto="false">
                            <legend class="cot-seccion-leyenda" id="cot-leyenda-cliente">
                                <span class="cot-seccion-leyenda-texto"><i class="fa-solid fa-user"></i> Datos del cliente</span>
                                <button type="button" class="cot-plegar-btn" id="cot-plegar-cliente" title="Mostrar u ocultar" aria-expanded="false" aria-controls="cot-cliente-contenido">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </legend>
                            <div id="cot-cliente-contenido">
                            <div class="cot-cliente-buscar">
                                <input type="text" id="cot-cliente-selector" list="cot-lista-clientes" class="form-control-pos" placeholder="Buscar cliente existente...">
                                <datalist id="cot-lista-clientes"></datalist>
                            </div>
                            <div class="cot-grid-cliente">
                                <label class="cot-campo" style="grid-column: span 2;">
                                    <span>Nombre del cliente</span>
                                    <input type="text" id="cot-cliente-nombre" class="form-control-pos" placeholder="Consumidor Final si vacío">
                                </label>
                                <label class="cot-campo">
                                    <span>RTN</span>
                                    <input type="text" id="cot-cliente-rtn" class="form-control-pos" maxlength="14" placeholder="0801-1990-12345">
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
                            </div>
                            </div>
                        </fieldset>

                        <fieldset class="cot-seccion">
                            <legend><i class="fa-solid fa-box"></i> Artículos de la cotización</legend>
                            <div class="cot-buscador">
                                <input type="search" id="cot-busqueda-input" class="form-control-pos cot-busqueda-input" placeholder="Buscar producto o código...">
                                <button type="button" id="cot-btn-escaneo" class="cot-btn-camara" title="Activar escaneo por cámara (silencioso)">
                                    <i class="fa-solid fa-camera"></i>
                                </button>
                                <span class="cot-busqueda-icono"><i class="fa-solid fa-spinner fa-spin" style="display:none;"></i></span>
                            </div>
                            <p class="cot-escaneo-estado" id="cot-escaneo-estado" hidden></p>
                            <div class="cot-resultados" id="cot-resultados" hidden>
                                <p class="cot-resultados-titulo">Resultados</p>
                                <div class="tabla-responsive"><table class="table-pos cot-resultados-tabla" id="cot-resultados-tabla">
                                    <thead><tr><th></th><th>Producto</th><th>P. Venta</th><th>Exist.</th><th>Agregar</th></tr></thead>
                                    <tbody></tbody>
                                </table></div>
                            </div>
                            <div class="tabla-responsive cot-carrito-wrap">
                                <table class="table-pos cot-carrito" id="cot-carrito">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Desc.</th>
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
                                <textarea id="cot-observaciones" class="form-control-pos" rows="3" placeholder="Notas para el cajero..."></textarea>
                            </label>
                            <?php if (($configuracion['cotizacion_mensaje'] ?? '') !== ''): ?>
                                <p class="cot-mensaje-config"><?php echo htmlspecialchars($configuracion['cotizacion_mensaje']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="cot-resumen-acciones">
                            <button type="button" class="btn btn-exito" id="cot-btn-guardar"><i class="fa-solid fa-check"></i> Guardar cotización</button>
                            <button type="button" class="btn btn-secondary" id="cot-btn-imprimir"><i class="fa-solid fa-print"></i> Guardar e imprimir</button>
                            <button type="button" class="btn btn-danger" id="cot-btn-vaciar"><i class="fa-solid fa-broom"></i> Vaciar carrito</button>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

<?php
$clientesJson = array_map(static function ($c) {
    return [
        'id'          => (int)$c['id'],
        'nombre'      => (string)($c['nombre'] ?? ''),
        'rtn_identidad' => (string)($c['rtn_identidad'] ?? ''),
        'telefono'    => (string)($c['telefono'] ?? ''),
        'direccion'   => (string)($c['direccion'] ?? '')
    ];
}, $clientes ?? []);
?>
<script>
    const URL_BASE = '<?php echo URL_BASE; ?>';
    const MONEDA_SIMBOLO = '<?php echo MONEDA_SIMBOLO; ?>';
    const CSRF_TOKEN = '<?php echo htmlspecialchars(csrf_token()); ?>';
    const COTIZACION_ID = 0;
    const MODO_EDICION = 0;
    const CLIENTES = <?php echo json_encode($clientesJson, JSON_UNESCAPED_UNICODE); ?>;
    const ITEMS_INICIALES = [];
    const COTIZACION_CLIENTE = null;
    const COTIZACION_FECHA_VALIDEZ = '<?php echo htmlspecialchars($fechaValidez ?? ''); ?>';
    const COTIZACION_OBSERVACIONES = '';
</script>
<script src="<?php echo URL_BASE; ?>js/quagga.min.js?v=<?php echo filemtime(PUBLIC_PATH . 'js/quagga.min.js'); ?>"></script>
<script src="<?php echo URL_BASE; ?>js/cotizaciones.js?v=<?php echo filemtime(PUBLIC_PATH . 'js/cotizaciones.js'); ?>"></script>
<script>
    (function () {
        var botonVaciar = document.getElementById('cot-btn-vaciar');
        if (botonVaciar) botonVaciar.addEventListener('click', function () {
            if (window.POS_COTIZACIONES_VACIAR) window.POS_COTIZACIONES_VACIAR();
        });
    }());
</script>
<script>
    (function () {
        var botonTema = document.getElementById('pos-boton-tema');
        var temaKey = 'web_pdv_tema';

        function iconoTema(t) {
            return t === 'oscuro' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        }

        function actualizarIconoTema(t) {
            if (!botonTema) return;
            botonTema.innerHTML = iconoTema(t);
            botonTema.setAttribute('aria-label', t === 'oscuro' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
        }

        function aplicarTema(t) {
            document.documentElement.setAttribute('data-tema', t);
            actualizarIconoTema(t);
            try { localStorage.setItem(temaKey, t); } catch (e) {}
        }

        actualizarIconoTema(document.documentElement.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro');
        if (botonTema) {
            botonTema.addEventListener('click', function () {
                var actual = document.documentElement.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
                aplicarTema(actual === 'oscuro' ? 'claro' : 'oscuro');
            });
        }
    }());
</script>
<script src="<?php echo URL_BASE; ?>js/webapp.js?v=<?php echo filemtime(PUBLIC_PATH . 'js/webapp.js'); ?>"></script>
</body>
</html>