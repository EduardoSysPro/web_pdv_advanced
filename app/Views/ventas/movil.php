<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Web PDV - Terminal Móvil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo URL_BASE; ?>css/pos_movil.css?v=<?php echo time(); ?>">
    <script>
        const URL_BASE = "<?php echo URL_BASE; ?>";
        const MONEDA_SIMBOLO = "<?php echo MONEDA_SIMBOLO; ?>";
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
                <p>Usa el buscador o escanea con la cámara para añadir artículos a la venta.</p>
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

            <!-- Botón Finalizar Cobro e Imprimir Ticket -->
            <button type="button" id="btn-finalizar-venta" class="btn-finalizar-touch" disabled>
                <i class="fa-solid fa-print"></i>
                <span>Confirmar e Imprimir Ticket</span>
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo URL_BASE; ?>js/html5-qrcode.min.js"></script>
    <script src="<?php echo URL_BASE; ?>js/quagga.min.js"></script>
    <script src="<?php echo URL_BASE; ?>js/pos_movil.js?v=<?php echo time(); ?>"></script>
</body>
</html>
