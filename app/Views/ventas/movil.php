<!DOCTYPE html>
<html lang="es">
<head>
    <script>(function(){try{var t=localStorage.getItem('web_pdv_tema')||'claro';if(t==='oscuro')document.documentElement.setAttribute('data-tema','oscuro');}catch(e){}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Web PDV - Terminal Móvil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/pos_movil.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/dark.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <script>
        const URL_BASE = "<?php echo URL_BASE; ?>";
        const MONEDA_SIMBOLO = "<?php echo MONEDA_SIMBOLO; ?>";
        const IMPRESORA_LAN_ACTIVA = <?php echo !empty($impresoraLanActiva) ? 'true' : 'false'; ?>;
        const CLIENTES_REGISTRADOS = <?php echo json_encode(array_map(function ($c) {
            return [
                'id'              => (int)$c['id'],
                'nombre'          => (string)$c['nombre'],
                'rtn'             => !empty($c['rtn_identidad']) ? (string)$c['rtn_identidad'] : '',
                'telefono'        => !empty($c['telefono']) ? (string)$c['telefono'] : '',
                'direccion'       => !empty($c['direccion']) ? (string)$c['direccion'] : '',
                'limite_credito'  => (float)$c['limite_credito'],
                'saldo_pendiente' => (float)$c['saldo_pendiente']
            ];
        }, $clientes)); ?>;
    </script>
</head>
<body class="pos-movil-body">
    <div class="movil-app-shell">
<!-- Header Móvil -->
<header class="movil-header">
    <div class="movil-brand">
        <div class="movil-brand-icon">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <div>
            <div class="movil-brand-title"><?php echo htmlspecialchars($configuracion['nombre_negocio'] ?? 'Web PDV'); ?></div>
            <div class="movil-brand-sub"><?php echo $nombreUsuario; ?> · <?php echo $cajaNombre; ?></div>
        </div>
    </div>
    <div class="movil-header-actions">
        <!-- Botón Tema -->
        <button class="btn-tema-movil" id="pos-boton-tema" type="button" aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"><i class="fa-solid fa-moon"></i></button>

        <!-- Botón Historial de Ventas de Hoy -->
        <button class="btn-movil-logout btn-historial-movil" id="btn-abrir-historial" type="button" title="Ventas de hoy">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Hoy</span>
        </button>

        <!-- Botón Volver a POS Normal -->
        <a href="<?php echo URL_BASE; ?>" class="btn-movil-logout" title="Volver a la vista normal" style="background: #b9f3fd; margin-right: 6px;">
            <i class="fa-solid fa-desktop"></i>
            <span>Volver</span>
        </a>

        <!-- Botón Salir -->
        <a href="<?php echo URL_BASE; ?>logout" class="btn-movil-logout" title="Cerrar Sesión">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Salir</span>
        </a>
    </div>
</header>

        <!-- Barra de Búsqueda y Escáner -->
        <section class="movil-search-section">
            <div class="movil-search-bar">
                <div class="movil-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass movil-search-icon"></i>
                    <input 
                        type="text" 
                        id="movil-search-input" 
                        class="movil-search-input" 
                        placeholder="Buscar producto o código..." 
                        autocomplete="off">
                </div>
                <button type="button" id="btn-open-scanner" class="btn-scan-camera" title="Escanear con cámara" style="display: none;">
                    <i class="fa-solid fa-camera"></i>
                    <span>Escanear</span>
                </button>
                <input type="file" id="camera-file-input" accept="image/*" capture="environment" style="display: none;">
            </div>
            <div id="movil-search-results" class="movil-search-results" style="display: none;"></div>
        </section>

        <!-- Carrito de Compras Móvil -->
        <main class="movil-cart-container">
            <div class="movil-cart-header">
                <span class="movil-cart-title"><i class="fa-solid fa-basket-shopping"></i> Venta Actual</span>
                <button type="button" id="btn-vaciar-cart" class="btn-vaciar-cart" style="display: none;">
                    <i class="fa-regular fa-trash-can"></i> Vaciar
                </button>
            </div>

            <!-- Listado de Artículos -->
            <div id="movil-cart-list" class="movil-cart-list">
                <!-- Se inyecta dinámicamente con JS -->
            </div>

            <!-- Placeholder Carrito Vacío -->
            <div id="movil-empty-cart" class="movil-empty-cart">
                <div class="movil-empty-icon">
                    <i class="fa-solid fa-cart-arrow-down"></i>
                </div>
                <h3>Carrito vacío</h3>
                <p>Usa el buscador o escanea con un lector de códigos de barras.</p>
            </div>
        </main>

        <!-- Barra Inferior Fija (Totales + Cobrar) -->
        <footer class="movil-bottom-bar">
            <div class="movil-total-info">
                <span class="movil-total-label">Total a Pagar</span>
                <span class="movil-total-amount" id="movil-total-text">L 0.00</span>
            </div>
            <button type="button" id="btn-abrir-cobro" class="btn-movil-cobrar" disabled>
                <i class="fa-solid fa-cash-register"></i>
                <span>Cobrar</span>
            </button>
        </footer>
    </div>

    <!-- Modal del Escáner de Cámara con Html5-Qrcode -->
    <div id="scanner-modal" class="scanner-modal">
        <div class="scanner-header">
            <div class="scanner-title">
                <i class="fa-solid fa-barcode"></i>
                <span>Escanear Código de Barras</span>
            </div>
            <button type="button" id="btn-close-scanner" class="btn-close-scanner">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="scanner-body">
            <div id="reader"></div>
            <div class="scanner-laser-guide" id="scanner-laser-guide">
                <div class="scanner-laser-line"></div>
            </div>
        </div>
        <div class="scanner-footer">
            <p style="margin-bottom: 8px;">Apunta y alinea el código de barras dentro de la guía roja</p>
            <button type="button" id="btn-foto-fallback" class="btn-scan-camera" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.4); font-size: 13px; height: 38px;">
                <i class="fa-solid fa-camera-retro"></i> Tomar foto al código (Fallback HTTP)
            </button>
        </div>
    </div>

    <!-- Modal editar precio/descuento por artículo -->
    <div id="modal-precio-movil" class="modal-movil-overlay" aria-hidden="true">
        <div class="modal-movil-sheet">
            <div class="sheet-header">
                <div class="sheet-title" id="precio-titulo">Editar precio</div>
                <button type="button" id="btn-cerrar-precio" class="sheet-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="precio-modal-body">
                <div class="precio-lista-row">
                    <span class="precio-lista-label">Precio de lista</span>
                    <span class="precio-lista-valor" id="precio-lista-val">L 0.00</span>
                </div>
                <div class="precio-item-nombre" id="precio-item-nombre"></div>

                <label for="input-precio-movil" class="precio-campo-label">Precio final por unidad:</label>
                <input
                    type="number"
                    id="input-precio-movil"
                    class="input-efectivo-touch"
                    step="0.01"
                    min="0"
                    inputmode="decimal">

                <div class="porcentajes-descuento">
                    <button type="button" data-por="0">0%</button>
                    <button type="button" data-por="5">5%</button>
                    <button type="button" data-por="10">10%</button>
                    <button type="button" data-por="15">15%</button>
                    <button type="button" data-por="20">20%</button>
                    <button type="button" data-por="25">25%</button>
                    <button type="button" data-por="50">50%</button>
                </div>

                <div class="precio-resumen" id="precio-resumen">Sin descuento</div>

                <button type="button" id="btn-quitar-descuento" class="btn-confirm-secondary" style="display: none;">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Quitar descuento</span>
                </button>
                <button type="button" id="btn-aplicar-precio" class="btn-confirm-primary">
                    <i class="fa-solid fa-check"></i>
                    <span>Aplicar precio</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Historial de Ventas de Hoy -->
    <div id="modal-historial-movil" class="modal-movil-overlay" aria-hidden="true">
        <div class="modal-movil-sheet">
            <div class="sheet-header">
                <div class="sheet-title"><i class="fa-solid fa-clock-rotate-left"></i> Ventas de Hoy</div>
                <div class="sheet-header-actions">
                    <button type="button" id="btn-refrescar-historial" class="sheet-refresh" title="Actualizar">
                        <i class="fa-solid fa-rotate"></i>
                    </button>
                    <button type="button" id="btn-cerrar-historial" class="sheet-close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div class="historial-body" id="historial-body">
                <div class="historial-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando ventas de hoy...</div>
            </div>
        </div>
    </div>

    <!-- Overlay de carga/procesamiento de código -->
    <div id="movil-loading-overlay" class="movil-loading-overlay" style="display: none;">
        <div class="movil-loading-box">
            <i class="fa-solid fa-barcode fa-bounce" style="font-size: 34px; color: #0284c7; margin-bottom: 12px;"></i>
            <div id="movil-loading-text" style="font-size: 15px; font-weight: 700; color: #0f172a;">Analizando código...</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Optimizando imagen y decodificando</div>
        </div>
    </div>

    <!-- Modal de Cobro Táctil -->
    <div id="modal-cobro-movil" class="modal-movil-overlay">
        <div class="modal-movil-sheet">
            <div class="sheet-header">
                <div class="sheet-title">Cobrar Venta</div>
                <button type="button" id="btn-cerrar-cobro" class="sheet-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="sheet-total-banner">
                <div class="sheet-total-label">Monto Total</div>
                <div class="sheet-total-val" id="cobro-total-val">L 0.00</div>
            </div>

            <!-- Métodos de Pago Touch -->
            <div class="metodos-touch">
                <button type="button" class="btn-metodo-touch is-selected" data-metodo="efectivo">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Efectivo</span>
                </button>
                <button type="button" class="btn-metodo-touch" data-metodo="tarjeta">
                    <i class="fa-regular fa-credit-card"></i>
                    <span>Tarjeta</span>
                </button>
                <button type="button" class="btn-metodo-touch" data-metodo="transferencia">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Transfer</span>
                </button>
                <button type="button" class="btn-metodo-touch" data-metodo="credito">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Crédito</span>
                </button>
            </div>

            <!-- Campo Efectivo -->
            <div class="campo-efectivo-touch" id="campo-efectivo-touch">
                <label for="input-efectivo-touch">Efectivo Recibido</label>
                <input 
                    type="number" 
                    id="input-efectivo-touch" 
                    class="input-efectivo-touch" 
                    step="0.01" 
                    placeholder="0.00" 
                    inputmode="decimal">
                
                <div class="billetes-touch">
                    <button type="button" class="btn-billete-touch" data-monto="exacto">Exacto</button>
                    <button type="button" class="btn-billete-touch" data-monto="100">L 100</button>
                    <button type="button" class="btn-billete-touch" data-monto="200">L 200</button>
                    <button type="button" class="btn-billete-touch" data-monto="500">L 500</button>
                </div>
            </div>

            <!-- Resumen de Cambio -->
            <div class="resumen-cambio-touch" id="resumen-cambio-touch">
                <span class="resumen-cambio-label">Cambio:</span>
                <span class="resumen-cambio-val" id="cobro-cambio-val">L 0.00</span>
            </div>

            <!-- Resumen de Crédito -->
            <div class="resumen-cambio-touch" id="resumen-credito-touch" style="display: none;">
                <span class="resumen-cambio-label">Se abonará al saldo:</span>
                <span class="resumen-cambio-val" id="cobro-credito-val">L 0.00</span>
            </div>

            <!-- Selección de Cliente -->
            <div class="cobro-cliente-movil" id="cobro-cliente-movil">
                <button type="button" class="btn-cliente-toggle" id="btn-toggle-cliente">
                    <i class="fa-solid fa-user"></i>
                    <span id="cliente-select-label">Agregar cliente (opcional)</span>
                </button>

                <div class="cliente-picker-wrap" id="cliente-picker-wrap" style="display: none;">
                    <div class="cliente-picker-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            id="movil-input-cliente"
                            class="input-efectivo-touch"
                            placeholder="Buscar cliente por nombre o RTN..."
                            autocomplete="off">
                    </div>
                    <div id="movil-cliente-results" class="movil-cliente-results"></div>
                </div>

                <div class="cliente-seleccionado" id="cliente-seleccionado" style="display: none;">
                    <div class="cliente-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="cliente-info">
                        <div class="cliente-nombre" id="cliente-nombre-txt"></div>
                        <div class="cliente-datos" id="cliente-datos-txt"></div>
                    </div>
                    <button type="button" class="btn-quitar-cliente" id="btn-quitar-cliente">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Botón Finalizar Cobro -->
            <button type="button" id="btn-finalizar-venta" class="btn-finalizar-touch" disabled>
                <i class="fa-solid fa-check"></i>
                <span>Confirmar Cobro</span>
            </button>
        </div>
    </div>

    <!-- Modal de confirmación de impresión -->
    <div id="modal-confirmar-imprimir" class="modal-movil-confirm-overlay" aria-hidden="true">
        <div class="modal-movil-confirm">
            <div class="confirm-icon">
                <i class="fa-solid fa-print"></i>
            </div>
            <h3>¿Desea imprimir el recibo?</h3>
            <p>La venta se registrará y podrá imprimir el comprobante ahora o dejarlo sin recibo.</p>
            <div class="confirm-actions">
                <button type="button" id="btn-sin-imprimir" class="btn-confirm-secondary">
                    <i class="fa-solid fa-ban"></i>
                    <span>No imprimir</span>
                </button>
                <button type="button" id="btn-confirmar-imprimir" class="btn-confirm-primary">
                    <i class="fa-solid fa-print"></i>
                    <span>Imprimir recibo</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo URL_BASE; ?>js/html5-qrcode.min.js"></script>
    <script src="<?php echo URL_BASE; ?>js/quagga.min.js"></script>
    <script src="<?php echo URL_BASE; ?>js/pos_movil.js?v=<?php echo time(); ?>"></script>
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
            botonTema.addEventListener('click', function () {
                var actual = document.documentElement.getAttribute('data-tema') === 'oscuro' ? 'oscuro' : 'claro';
                aplicarTema(actual === 'oscuro' ? 'claro' : 'oscuro');
            });
        }());
    </script>
</body>
</html>
