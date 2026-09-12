<?php $tituloPagina = 'Ventas'; $claseMain = 'pos-ventas-main'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>

<!-- ========== ZONA DE CODIGO DE BARRAS Y BOTONES PRINCIPALES ========== -->
    <section class="pos-codigo-barra">
        <div class="cb-fila-principal">
            <label class="cb-label" for="codigo-barras-input">
                <span class="cb-label-icono"><i class="fa-solid fa-barcode" aria-hidden="true"></i></span> Código de barras
            </label>
            <input
                type="text"
                id="codigo-barras-input"
                class="cb-input"
                placeholder="Escanea o escribe el código de barras..."
                autocomplete="off"
                autofocus>
            <button class="cb-btn-agregar" id="btn-agregar-producto" type="button">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Agregar artículo</span>
            </button>
        </div>
        <div class="cb-fila-secundaria">
  
            <button class="cb-btn-secundario" id="btn-buscar" type="button" title="Buscar productos en catálogo (F10)">
                <i class="fa-solid fa-magnifying-glass pos-action-icon" aria-hidden="true"></i>
                <span>Buscar</span>
                <kbd class="pos-tecla">F10</kbd>
            </button>
            <button class="cb-btn-secundario cb-btn-peligro" id="btn-borrar-art" type="button" title="Eliminar artículo seleccionado (F6 o Supr)">
                <i class="fa-regular fa-trash-can pos-action-icon" aria-hidden="true"></i>
                <span>Borrar artículo</span>
                <kbd class="pos-tecla">F6</kbd>
            </button>
            <button class="cb-btn-secundario" id="btn-cantidad" type="button" title="Modificar cantidad del artículo (F7)">
                <i class="fa-solid fa-arrow-right-arrow-left pos-action-icon" aria-hidden="true"></i>
                <span>Cantidad</span>
                <kbd class="pos-tecla">F7</kbd>
            </button>
            <button class="cb-btn-secundario" id="btn-precio" type="button" title="Modificar precio unitario (F8)">
                <i class="fa-solid fa-tag pos-action-icon" aria-hidden="true"></i>
                <span>Precio</span>
                <kbd class="pos-tecla">F8</kbd>
            </button>
            <button class="cb-btn-secundario cb-btn-peligro" id="btn-cancelar-todo" type="button" title="Cancelar venta activa (F9 o F5)">
                <i class="fa-solid fa-ban pos-action-icon" aria-hidden="true"></i>
                <span>Cancelar todo</span>
                <kbd class="pos-tecla">F9</kbd>
            </button>
        </div>
    </section>

    <!-- ========== BARRA DE PESTAÑAS MULTI-TICKET ========== -->
    <div class="pos-tabs-bar" id="pos-tabs-bar">
        <!-- Las pestañas de los tickets se inyectan dinámicamente por JS -->
        <button class="tab-nuevo" id="tab-nuevo" type="button">
            <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Nuevo ticket</span>
        </button>
    </div>

    <!-- ========== TABLA DE PRODUCTOS ========== -->
    <section class="pos-tabla-contenedor">
        <table class="pos-tabla" id="pos-tabla">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 15%;">Código de Barras</th>
                    <th style="width: 40%;">Descripción del Producto</th>
                    <th style="width: 10%;">Precio Venta</th>
                    <th style="width: 7%;">Cant.</th>
                    <th style="width: 12%;" class="col-importe">Importe</th>
                    <th style="width: 12%;">Existencia</th>
                </tr>
            </thead>
            <tbody id="pos-tabla-body">
                <!-- Filas inyectadas dinámicamente por JS -->
            </tbody>
            <tfoot>
                <tr class="tabla-mensaje" id="tabla-mensaje-vacio">
                    <td colspan="7" class="msg-vacio">
                        <i class="fa-solid fa-barcode" aria-hidden="true"></i> Escanea un código de barras para comenzar la venta.
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>

    <!-- ========== BARRA INFERIOR DE TOTALES ========== -->
    <section class="pos-totales">
        <div class="totales-izquierda">
            <div class="total-fila">
                <span class="total-label">Total:</span>
                <span class="total-valor" id="total-venta"><?php echo formatearMoneda(0); ?></span>
            </div>
            <div class="total-fila">
                <span class="total-label">Pagó Con:</span>
                <span class="total-valor" id="total-pago"><?php echo formatearMoneda(0); ?></span>
            </div>
            <div class="total-fila total-cambio">
                <span class="total-label">Cambio:</span>
                <span class="total-valor" id="total-cambio"><?php echo formatearMoneda(0); ?></span>
            </div>
            <div class="total-fila total-articulos">
                <span class="total-label">Artículos:</span>
                <span class="total-valor" id="total-articulos">0</span>
            </div>
        </div>
        <div class="totales-derecha">
            <div class="total-gigante-contenedor">
                <div class="total-gigante-label">TOTAL VENTA</div>
                <div class="total-gigante" id="total-gigante"><?php echo formatearMoneda(0); ?></div>
            </div>
            <button class="btn-cobrar-gigante" id="btn-cobrar" type="button" title="Cobrar venta actual (F12)">
                <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                <span class="btn-cobrar-texto">Cobrar</span>
                <kbd class="pos-tecla pos-tecla-cobrar">F12</kbd>
            </button>
        </div>
    </section>

  <!-- ========== MODAL DE COBRO ========== -->
    <div id="modal-cobro" class="modal-contenedor" style="display: none;" aria-hidden="true">
        <div class="modal-fondo" data-modal-cerrar></div>
        <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="modal-cobro-titulo">
            <div class="modal-cabecera">
                <span class="modal-icono">&#128179;</span>
                <h3 id="modal-cobro-titulo">Cobrar Venta</h3>
                <button type="button" class="modal-cerrar-x" data-modal-cerrar aria-label="Cerrar">&#10005;</button>
            </div>

            <div class="modal-cuerpo">
                <!-- Total a pagar -->
                <div class="cobro-total-fila">
                    <span class="cobro-total-label">Total a Pagar:</span>
                    <span class="cobro-total-valor" id="cobro-total-pagar">L 0.00</span>
                </div>
                <hr class="cobro-separador">

                <div class="campo cobro-campo" style="margin-bottom: 12px;">
                    <label>Tipo de comprobante:</label>
                    <div class="cobro-metodos" style="margin-top: 8px;">
                        <label class="metodo-opcion">
                            <input type="radio" name="tipo-comprobante" value="recibo" checked>
                            <span>Recibo</span>
                        </label>
                        <label class="metodo-opcion">
                            <input type="radio" name="tipo-comprobante" value="factura">
                            <span>Factura Fiscal</span>
                        </label>
                    </div>
                </div>

                <!-- DATOS DEL CLIENTE (OPCIONALES) -->
                <details class="cobro-cliente-acordeon" style="margin-bottom: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                    <summary style="font-weight: bold; cursor: pointer; color: #334155;">
                        <i class="fa-solid fa-user-tag"></i> Datos del Cliente (Facturación/Opcional)
                    </summary>
                    <div style="margin-top: 8px;">
                        <div class="campo" style="margin-bottom: 8px;">
                            <label for="cliente_buscar" style="font-size: 11px;">Buscar cliente registrado:</label>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <input type="text" id="cliente_buscar" class="cobro-input" placeholder="Nombre o RTN / DNI" style="padding: 4px 8px; font-size: 12px; flex: 1;">
                                <button type="button" id="btn-nuevo-cliente" class="btn-pos-success" style="padding: 6px 10px; font-size: 11px; white-space: nowrap;">+ Nuevo Cliente</button>
                            </div>
                            <div id="cliente_buscar_resultados" style="display: none; margin-top: 6px; background: #fff; border: 1px solid #dbeafe; border-radius: 6px; max-height: 160px; overflow: auto; padding: 6px;"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div class="campo">
                                <label for="cliente_nombre" style="font-size: 11px;">Nombre / Razón Social:</label>
                                <input type="text" id="cliente_nombre" class="cobro-input" placeholder="Consumidor Final" style="padding: 4px 8px; font-size: 12px;">
                            </div>
                            <div class="campo">
                                <label for="cliente_rtn" style="font-size: 11px;">RTN / Identidad:</label>
                                <input type="text" id="cliente_rtn" class="cobro-input" placeholder="08011990000000" style="padding: 4px 8px; font-size: 12px;">
                            </div>
                            <div class="campo">
                                <label for="cliente_telefono" style="font-size: 11px;">Celular / Teléfono:</label>
                                <input type="text" id="cliente_telefono" class="cobro-input" placeholder="+504 9999-9999" style="padding: 4px 8px; font-size: 12px;">
                            </div>
                            <div class="campo">
                                <label for="cliente_direccion" style="font-size: 11px;">Dirección:</label>
                                <input type="text" id="cliente_direccion" class="cobro-input" placeholder="Ciudad, Barrio..." style="padding: 4px 8px; font-size: 12px;">
                            </div>
                        </div>
                    </div>
                </details>

                <!-- Efectivo recibido -->
                <div class="campo cobro-campo">
                    <label for="cobro-efectivo">Efectivo Recibido:</label>
                    <input
                        type="number"
                        id="cobro-efectivo"
                        class="cobro-input"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        autocomplete="off">
                    <div class="cobro-billetes">
                        <button type="button" class="btn-pos-secondary btn-billete" data-monto="50">L 50</button>
                        <button type="button" class="btn-pos-secondary btn-billete" data-monto="100">L 100</button>
                        <button type="button" class="btn-pos-secondary btn-billete" data-monto="200">L 200</button>
                        <button type="button" class="btn-pos-secondary btn-billete" data-monto="500">L 500</button>
                        <button type="button" class="btn-pos-secondary btn-pago-exacto">Pago Exacto</button>
                    </div>
                </div>

                <!-- Método de pago -->
                <div class="campo cobro-campo">
                    <label>Método de Pago:</label>
                    <div class="cobro-metodos">
                        <label class="metodo-opcion">
                            <input type="radio" name="cobro-metodo" value="efectivo" checked>
                            <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i><span>Efectivo</span>
                        </label>
                        <label class="metodo-opcion">
                            <input type="radio" name="cobro-metodo" value="tarjeta">
                            <i class="fa-regular fa-credit-card" aria-hidden="true"></i><span>Tarjeta</span>
                        </label>
                        <label class="metodo-opcion">
                            <input type="radio" name="cobro-metodo" value="transferencia">
                            <i class="fa-solid fa-building-columns" aria-hidden="true"></i><span>Transferencia</span>
                        </label>
                        <label class="metodo-opcion">
                            <input type="radio" name="cobro-metodo" value="credito">
                            <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i><span>Crédito</span>
                        </label>
                    </div>
                </div>
                <div class="campo cobro-campo" id="cobro-cliente-contenedor" style="display:none;">
                    <label for="cobro-cliente">Cliente registrado para crédito:</label>
                    <select id="cobro-cliente">
                        <option value="">Selecciona un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo (int)$cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?> - Saldo <?php echo formatearMoneda($cliente['saldo_pendiente']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cambio -->
                <div class="cobro-cambio-fila">
                    <span class="cobro-cambio-label">Cambio a Devolver:</span>
                    <span class="cobro-cambio-valor" id="cobro-cambio">L 0.00</span>
                </div>

                <div id="cobro-mensaje" class="cobro-mensaje"></div>

                <!-- Impresión por red (LAN) -->
                <div class="campo cobro-campo" style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                        <input type="checkbox" id="cobro-imprimir-lan" <?php echo !empty($impresoraLanActiva) ? 'checked' : ''; ?>>
                        <i class="fa-solid fa-wifi" aria-hidden="true"></i> Imprimir en impresora de red (LAN)
                    </label>
                    <small style="color: #64748b; display: block; margin-top: 2px;">Si lo desmarcas, el comprobante se imprime con el diálogo del navegador.</small>
                </div>
            </div>

            <div class="modal-pie">
                <button type="button" class="btn-pos-danger btn-modal" data-modal-cerrar>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancelar
                </button>
                <button type="button" class="btn-pos-success btn-modal" id="cobro-confirmar" disabled>
                    <i class="fa-solid fa-print" aria-hidden="true"></i> Confirmar / Imprimir ticket
                </button>
            </div>
        </div>
    </div>

    <div id="modal-busqueda-productos" class="modal-busqueda-productos" hidden>
        <div class="modal-busqueda-fondo" data-busqueda-cerrar></div>
        <section class="modal-busqueda-caja" role="dialog" aria-modal="true" aria-labelledby="titulo-busqueda-productos">
            <header class="modal-busqueda-cabecera"><h2 id="titulo-busqueda-productos">Buscar producto</h2><button type="button" class="modal-busqueda-cerrar" data-busqueda-cerrar aria-label="Cerrar">&#10005;</button></header>
            <div class="modal-busqueda-cuerpo"><label for="busqueda-productos-input">Nombre o código</label><input class="form-control-pos" id="busqueda-productos-input" type="search" placeholder="Escribe el nombre o código (ej: Sal, Leche, Arroz..)" autocomplete="off"><p id="busqueda-productos-estado" class="busqueda-productos-estado">Escribe para buscar productos.</p><div class="tabla-responsive"><table class="table-pos" id="tabla-busqueda-productos"><thead><tr><th>Código</th><th>Descripción</th><th>Precio Venta</th><th>Existencia</th><th>Acción</th></tr></thead><tbody></tbody></table></div></div>
        </section>
    </div>

    <!-- ========== VARIABLES GLOBALES PARA JS ========== -->
    <script>
        const URL_BASE       = '<?php echo $urlBase; ?>';
        const MONEDA_SIMBOLO = '<?php echo MONEDA_SIMBOLO; ?>';
        const MONEDA_ISO     = '<?php echo MONEDA_ISO; ?>';
        const ISV_PORCENTAJE = <?php echo ISV_PORCENTAJE; ?>;
        const COMPROBANTE_DEFAULT = '<?php echo htmlspecialchars($tipoComprobanteDefault ?? 'recibo'); ?>';
        document.querySelectorAll('input[name="cobro-metodo"]').forEach(function (radio) { radio.addEventListener('change', function () { document.getElementById('cobro-cliente-contenedor').style.display = this.value === 'credito' ? 'flex' : 'none'; }); });
    </script>
    <?php $versionJs = file_exists(PUBLIC_PATH . 'js/pos.js') ? filemtime(PUBLIC_PATH . 'js/pos.js') : time(); ?>
    <script src="<?php echo URL_BASE; ?>js/pos.js?v=<?php echo $versionJs; ?>"></script>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
