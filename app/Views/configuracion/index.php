<?php $tituloPagina = 'Configuración'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>

<section class="catalogo-encabezado">
    <div>
        <span class="eyebrow">Administración</span>
        <h1>Configuración del sistema</h1>
        <p>Parámetros generales, formato de comprobante y facturación SAR.</p>
    </div>
</section>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="config-layout" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: start; width: 100%;">
    <form id="form-configuracion" method="POST" action="<?php echo URL_BASE; ?>configuracion/guardar-empresa" enctype="multipart/form-data" class="form-grid" style="width: 100%; min-width: 0;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

        <section class="tarjeta campo-ancho">
            <h2 class="tarjeta-titulo">Datos del negocio y ticket</h2>
            <div class="form-grid">
                <div class="campo campo-ancho">
                    <label>Nombre del negocio</label>
                    <input name="nombre_negocio" required value="<?php echo htmlspecialchars($configuracion['nombre_negocio'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>RTN del Negocio</label>
                    <input name="rtn" value="<?php echo htmlspecialchars($configuracion['rtn'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Teléfono</label>
                    <input name="telefono" value="<?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Correo electrónico</label>
                    <input name="email" type="email" value="<?php echo htmlspecialchars($configuracion['email'] ?? ''); ?>">
                </div>

                <div class="campo campo-ancho">
                    <label>Dirección</label>
                    <input name="direccion" value="<?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?>">
                </div>

                <div class="campo campo-ancho">
                    <label>Mensaje del ticket</label>
                    <textarea name="mensaje_ticket" rows="2"><?php echo htmlspecialchars($configuracion['mensaje_ticket'] ?? ''); ?></textarea>
                </div>

                <div class="campo">
                    <label>Ancho del ticket</label>
                    <select name="ancho_ticket">
                        <option value="58mm" <?php echo ($configuracion['ancho_ticket'] ?? '') === '58mm' ? 'selected' : ''; ?>>58mm</option>
                        <option value="80mm" <?php echo ($configuracion['ancho_ticket'] ?? '') === '80mm' ? 'selected' : ''; ?>>80mm</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Tipo de comprobante por defecto</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
                        <label style="display:flex; align-items:center; gap:6px; font-weight:600;">
                            <input type="radio" name="tipo_comprobante_default" value="recibo" <?php echo (($configuracion['tipo_comprobante_default'] ?? 'recibo') === 'recibo') ? 'checked' : ''; ?>>
                            Recibo
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; font-weight:600;">
                            <input type="radio" name="tipo_comprobante_default" value="factura" <?php echo (($configuracion['tipo_comprobante_default'] ?? 'recibo') === 'factura') ? 'checked' : ''; ?>>
                            Factura Fiscal
                        </label>
                    </div>
                </div>

                <div class="campo">
                    <label>Impuesto ISV (%)</label>
                    <input name="impuesto_porcentaje" type="number" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($configuracion['impuesto_porcentaje'] ?? '15'); ?>">
                </div>

                <div class="campo">
                    <label>Símbolo de moneda</label>
                    <input name="moneda_simbolo" maxlength="3" value="<?php echo htmlspecialchars($configuracion['moneda_simbolo'] ?? 'L'); ?>">
                </div>

                <div class="campo">
                    <label>Tipo de fuente</label>
                    <select name="ticket_fuente">
                        <option value="Courier New" <?php echo (($configuracion['ticket_fuente'] ?? 'Courier New') === 'Courier New') ? 'selected' : ''; ?>>Courier New</option>
                        <option value="Consolas" <?php echo (($configuracion['ticket_fuente'] ?? 'Courier New') === 'Consolas') ? 'selected' : ''; ?>>Consolas</option>
                        <option value="Arial" <?php echo (($configuracion['ticket_fuente'] ?? 'Courier New') === 'Arial') ? 'selected' : ''; ?>>Arial</option>
                        <option value="Roboto Mono" <?php echo (($configuracion['ticket_fuente'] ?? 'Courier New') === 'Roboto Mono') ? 'selected' : ''; ?>>Roboto Mono</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Tamaño de fuente</label>
                    <select name="ticket_tamano_fuente">
                        <option value="9px" <?php echo (($configuracion['ticket_tamano_fuente'] ?? '11px') === '9px') ? 'selected' : ''; ?>>9px</option>
                        <option value="10px" <?php echo (($configuracion['ticket_tamano_fuente'] ?? '11px') === '10px') ? 'selected' : ''; ?>>10px</option>
                        <option value="11px" <?php echo (($configuracion['ticket_tamano_fuente'] ?? '11px') === '11px') ? 'selected' : ''; ?>>11px</option>
                        <option value="12px" <?php echo (($configuracion['ticket_tamano_fuente'] ?? '11px') === '12px') ? 'selected' : ''; ?>>12px</option>
                    </select>
                </div>

                <div class="campo">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="ticket_mostrar_logo" value="1" <?php echo (($configuracion['ticket_mostrar_logo'] ?? '1') === '1' || ($configuracion['ticket_mostrar_logo'] ?? 1) == 1) ? 'checked' : ''; ?>>
                        Mostrar logo
                    </label>
                </div>

                <div class="campo">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="ticket_mostrar_sar" value="1" <?php echo (($configuracion['ticket_mostrar_sar'] ?? '1') === '1' || ($configuracion['ticket_mostrar_sar'] ?? 1) == 1) ? 'checked' : ''; ?>>
                        Mostrar pie SAR
                    </label>
                </div>

                <div class="campo campo-logo">
                    <label>Logotipo del negocio</label>
                    <input name="logotipo" type="file" accept="image/png,image/jpeg,image/webp">
                    <?php if (!empty($configuracion['logotipo_path'])): ?>
                        <div class="logo-preview-box">
                            <div class="logo-frame">
                                <img src="<?php echo URL_BASE . htmlspecialchars($configuracion['logotipo_path']); ?>" alt="Logotipo">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="tarjeta campo-ancho">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 class="tarjeta-titulo" style="margin: 0;">Facturación Fiscal (SAR)</h2>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold;">
                    <input type="checkbox" name="sar_activo" value="1" <?php echo ($configuracion['sar_activo'] ?? '0') === '1' ? 'checked' : ''; ?> onchange="document.getElementById('bloque-sar').style.display = this.checked ? 'grid' : 'none'">
                    Habilitar exigencias SAR
                </label>
            </div>

            <div id="bloque-sar" class="form-grid" style="display: <?php echo ($configuracion['sar_activo'] ?? '0') === '1' ? 'grid' : 'none'; ?>;">
                <div class="campo campo-ancho">
                    <label>C.A.I. (Código de Autorización de Impresión)</label>
                    <input name="sar_cai" placeholder="Ej: 3B2D6F-9C4E1A-8B7C5D-102938-47561D-A2" value="<?php echo htmlspecialchars($configuracion['sar_cai'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Punto de Venta (PPP)</label>
                    <input name="sar_punto_venta" maxlength="3" placeholder="Ej: 001" value="<?php echo htmlspecialchars($configuracion['sar_punto_venta'] ?? '001'); ?>">
                </div>

                <div class="campo">
                    <label>Establecimiento (EEE)</label>
                    <input name="sar_establecimiento" maxlength="3" placeholder="Ej: 001" value="<?php echo htmlspecialchars($configuracion['sar_establecimiento'] ?? '001'); ?>">
                </div>

                <div class="campo">
                    <label>Tipo de Documento (TD)</label>
                    <input name="sar_tipo_documento" maxlength="2" placeholder="Ej: 01" value="<?php echo htmlspecialchars($configuracion['sar_tipo_documento'] ?? '01'); ?>">
                </div>

                <div class="campo">
                    <label>Rango Inicial Autorizado</label>
                    <input name="sar_rango_inicial" placeholder="Ej: 000-001-01-00000001" value="<?php echo htmlspecialchars($configuracion['sar_rango_inicial'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Rango Final Autorizado</label>
                    <input name="sar_rango_final" placeholder="Ej: 000-001-01-00005000" value="<?php echo htmlspecialchars($configuracion['sar_rango_final'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Fecha Límite de Emisión</label>
                    <input type="date" name="sar_fecha_limite" value="<?php echo htmlspecialchars($configuracion['sar_fecha_limite'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Correlativo Actual (Secuencia)</label>
                    <input type="number" name="sar_correlativo_actual" min="0" value="<?php echo htmlspecialchars($configuracion['sar_correlativo_actual'] ?? '0'); ?>">
                </div>
            </div>
        </section>

        <section class="tarjeta campo-ancho">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 8px;">
                <h2 class="tarjeta-titulo" style="margin: 0;">Impresora térmica por red (LAN)</h2>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: bold;">
                    <input type="checkbox" name="impresora_lan_activa" value="1" <?php echo (($configuracion['impresora_lan_activa'] ?? '0') === '1') ? 'checked' : ''; ?>>
                    Activar impresión por red
                </label>
            </div>

            <p style="margin: 0 0 1rem; color: #64748b; font-size: 13px;">
                Imprime el ticket directo a una impresora térmica conectada a la red mediante su dirección IP
                (puerto 9100 estándar). Mientras esté desactivada, se usa la impresión del navegador como hoy.
            </p>

            <div class="form-grid">
                <div class="campo">
                    <label>Dirección IP de la impresora</label>
                    <input name="impresora_lan_ip" placeholder="Ej: 192.168.1.50" value="<?php echo htmlspecialchars($configuracion['impresora_lan_ip'] ?? ''); ?>">
                </div>

                <div class="campo">
                    <label>Puerto</label>
                    <input name="impresora_lan_puerto" type="number" min="1" max="65535" value="<?php echo htmlspecialchars($configuracion['impresora_lan_puerto'] ?? '9100'); ?>">
                </div>

                <div class="campo campo-ancho">
                    <button type="button" class="btn btn-ligero" id="btn-probar-impresora"><i class="fa-solid fa-plug"></i> Probar impresión de red</button>
                    <div id="resultado-impresora" style="margin-top: 10px;"></div>
                </div>
            </div>
        </section>

        <div class="form-acciones campo-ancho">
            <button class="btn btn-exito">Guardar configuración</button>
        </div>
    </form>

    <aside style="width: 100%; min-width: 0;">
        <div class="tarjeta" style="padding:0; overflow:hidden;">
            <div style="padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                <strong>Vista previa del ticket</strong>
            </div>

            <div id="ticket-preview-wrapper" style="padding:16px; background:#f1f5f9;">
                <div id="ticket-preview" style="width: 80mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 10px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(15,23,42,0.08); font-family: 'Courier New'; font-size: 11px; color: #111827; line-height: 1.35;">
                    <div class="preview-header" style="text-align:center;">
                        <div id="preview-logo" style="display: block; margin-bottom:8px;">
                            <img src="<?php echo !empty($configuracion['logotipo_path']) ? URL_BASE . htmlspecialchars(ltrim((string)$configuracion['logotipo_path'], '/')) : ''; ?>" alt="Logo" style="max-width: 160px; max-height: 90px; display: block; margin: 0 auto;">
                        </div>
                        <h2 id="preview-negocio" style="margin:0; font-size:14px; font-weight:bold;">Mi Abarrotería</h2>
                        <div id="preview-rtn">RTN: 00000000000000</div>
                        <div id="preview-direccion">Dirección del negocio</div>
                        <div id="preview-telefono">Tel: 0000-0000</div>
                        <div id="preview-email">correo@dominio.com</div>
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <div id="preview-factura">
                        <div><strong id="preview-titulo-comprobante">DOCUMENTO NO FISCAL / RECIBO INTERNO:</strong> <span id="preview-folio">REC-00000001</span></div>
                        <div id="preview-cai" style="display:none;"><strong>CAI:</strong> 0000000000000000000000000000</div>
                        <div id="preview-rango" style="display:none;"><strong>Rango Autorizado:</strong><br>000-001-01-00000001 al 000-001-01-00010000</div>
                        <div id="preview-fecha-limite" style="display:none;"><strong>Fecha Límite Emisión:</strong> 2027-12-31</div>
                        <div><strong>Fecha Emisión:</strong> 2026-01-01 12:00:00</div>
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <div id="preview-cliente">
                        <div style="text-align:center; font-weight:bold; margin-bottom:3px;">DATOS DE CLIENTE</div>
                        <div><strong>Nombre:</strong> Consumidor Final</div>
                        <div><strong>RTN/ID:</strong> S/N</div>
                        <div id="preview-direccion-cliente" style="display:none;"><strong>Dirección:</strong> Dirección del cliente</div>
                        <div><strong>Cajero:</strong> Cajero</div>
                    </div>

                    <div id="preview-exonerado" style="display:none;">
                        <div style="border-top:1px dashed #000; margin:8px 0;"></div>
                        <div style="text-align:center; font-weight:bold; margin-bottom:3px;">DATOS DE ADQUIRIENTE EXONERADO</div>
                        <div><strong>Orden Compra Exenta:</strong> _________________</div>
                        <div><strong>Constancia Registro:</strong> _________________</div>
                        <div><strong>Registro SAG:</strong> _________________</div>
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left; padding:2px 0;">Cant/Descripción</th>
                                <th style="text-align:right; padding:2px 0;">P.U.</th>
                                <th style="text-align:right; padding:2px 0;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" style="padding:2px 0;">Producto A</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;">1 x</td>
                                <td style="text-align:right; padding:2px 0;">20.00</td>
                                <td style="text-align:right; padding:2px 0;">20.00</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding:2px 0;">Producto B [Caja x12]</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;">1 caja x</td>
                                <td style="text-align:right; padding:2px 0;">35.00</td>
                                <td style="text-align:right; padding:2px 0;">35.00</td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="preview-impuestos" style="display:none; margin-top:8px; border-top:1px dashed #000; padding-top:8px; font-size:10px;">
                        <div style="display:flex; justify-content:space-between;"><span>Importe Exento:</span><span>L 0.00</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Importe Exonerado:</span><span>L 0.00</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Importe Gravado 15%:</span><span id="preview-gravado15">L 30.43</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>ISV 15%:</span><span id="preview-isv15">L 4.57</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Importe Gravado 18%:</span><span>L 0.00</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>ISV 18%:</span><span>L 0.00</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Descuentos y Rebajas Otorgadas:</span><span>L 0.00</span></div>
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <div style="display:flex; justify-content:space-between;">
                        <span><strong id="preview-total-label">TOTAL:</strong></span>
                        <span><strong id="preview-total-valor">L 55.00</strong></span>
                    </div>

                    <div id="preview-descuento-recibo" style="display:flex; justify-content:space-between; margin-top:4px;"><span>Descuentos y Rebajas Otorgadas:</span><span>L 0.00</span></div>

                    <div id="preview-pago" style="margin-top:4px;">
                        <div id="preview-forma-pago" style="display:flex; justify-content:space-between;"><span>Forma de pago:</span><span>Efectivo</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Efectivo / Recibido:</span><span>L 60.00</span></div>
                        <div style="display:flex; justify-content:space-between;"><span>Cambio:</span><span>L 5.00</span></div>
                    </div>

                    <div id="preview-son" style="margin:8px 0; font-size:10px;"><strong>SON:</strong> CINCUENTA Y CINCO LEMPIRAS CON 00/100 CENTAVOS</div>

                    <div id="preview-mensaje" style="margin-top:8px; text-align:center; font-size:10px;">
                        ¡Gracias por su compra!
                    </div>

                    <div id="preview-copia" style="margin-top:8px; text-align:center;"><strong>*** Original: Cliente ***</strong></div>
                    <div id="preview-pie-sar" style="margin-top:4px; text-align:center; font-size:9px; display:none;">
                        La factura es beneficio de todos, exíjala.
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
    const form = document.getElementById('form-configuracion');
    if (!form) return;

    const preview = document.getElementById('ticket-preview');
    const previewNegocio = document.getElementById('preview-negocio');
    const previewRtn = document.getElementById('preview-rtn');
    const previewDireccion = document.getElementById('preview-direccion');
    const previewTelefono = document.getElementById('preview-telefono');
    const previewEmail = document.getElementById('preview-email');
    const previewMensaje = document.getElementById('preview-mensaje');
    const previewPieSar = document.getElementById('preview-pie-sar');
    const previewLogo = document.getElementById('preview-logo');
    const previewExonerado = document.getElementById('preview-exonerado');
    const previewDireccionCliente = document.getElementById('preview-direccion-cliente');
    const previewDescuentoRecibo = document.getElementById('preview-descuento-recibo');
    const previewFormaPago = document.getElementById('preview-forma-pago');
    const previewSon = document.getElementById('preview-son');

    function formatMoney(value) {
        return 'L ' + Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function actualizarPreview() {
        const formData = new FormData(form);
        const tipoComprobante = (formData.get('tipo_comprobante_default') || 'recibo').toString();
        const nombre = (formData.get('nombre_negocio') || 'Mi Abarrotería').toString().trim();
        const rtn = (formData.get('rtn') || '').toString().trim();
        const direccion = (formData.get('direccion') || '').toString().trim();
        const telefono = (formData.get('telefono') || '').toString().trim();
        const email = (formData.get('email') || '').toString().trim();
        const mensaje = (formData.get('mensaje_ticket') || '¡Gracias por su compra!').toString().trim();
        const fuente = (formData.get('ticket_fuente') || 'Courier New').toString();
        const tamano = (formData.get('ticket_tamano_fuente') || '11px').toString();
        const mostrarLogo = formData.get('ticket_mostrar_logo') === '1';
        const mostrarPieSar = formData.get('ticket_mostrar_sar') !== '0';
        const esFactura = tipoComprobante === 'factura';

        const totalCompra = 55.00;
        const gravado15 = totalCompra / 1.15;
        const isv15 = totalCompra - gravado15;

        preview.style.fontFamily = '"' + fuente + '", monospace';
        preview.style.fontSize = tamano;
        previewNegocio.textContent = nombre;
        previewRtn.textContent = rtn ? 'RTN: ' + rtn : 'RTN: 00000000000000';
        previewDireccion.textContent = direccion || 'Dirección del negocio';
        previewTelefono.textContent = telefono ? 'Tel: ' + telefono : 'Tel: 0000-0000';
        previewEmail.textContent = email || 'correo@dominio.com';
        previewMensaje.textContent = mensaje || '¡Gracias por su compra!';
        previewPieSar.style.display = mostrarPieSar && esFactura ? 'block' : 'none';
        previewLogo.style.display = mostrarLogo ? 'block' : 'none';
            previewDireccionCliente.style.display = 'none';
            previewExonerado.style.display = esFactura ? 'block' : 'none';
            previewDescuentoRecibo.style.display = esFactura ? 'none' : 'flex';
            previewFormaPago.style.display = esFactura ? 'none' : 'flex';
            previewSon.style.display = 'block';

        const titulo = document.getElementById('preview-titulo-comprobante');
        const folio = document.getElementById('preview-folio');
        const cai = document.getElementById('preview-cai');
        const rango = document.getElementById('preview-rango');
        const fechaLimite = document.getElementById('preview-fecha-limite');
        const impuestos = document.getElementById('preview-impuestos');
        const totalLabel = document.getElementById('preview-total-label');
        const totalValor = document.getElementById('preview-total-valor');
        const gravado15El = document.getElementById('preview-gravado15');
        const isv15El = document.getElementById('preview-isv15');

        if (esFactura) {
            titulo.textContent = 'FACTURA:';
            folio.textContent = '000-001-01-00000001';
            cai.style.display = 'block';
            rango.style.display = 'block';
            fechaLimite.style.display = 'block';
            impuestos.style.display = 'block';
            totalLabel.textContent = 'TOTAL A PAGAR:';
            totalValor.textContent = formatMoney(totalCompra);
            gravado15El.textContent = formatMoney(gravado15);
            isv15El.textContent = formatMoney(isv15);
        } else {
            titulo.textContent = 'DOCUMENTO NO FISCAL / RECIBO INTERNO:';
            folio.textContent = 'REC-00000001';
            cai.style.display = 'none';
            rango.style.display = 'none';
            fechaLimite.style.display = 'none';
            impuestos.style.display = 'none';
            totalLabel.textContent = 'TOTAL:';
            totalValor.textContent = formatMoney(totalCompra);
        }

        const ancho = (document.querySelector('[name="ancho_ticket"]')?.value || '80mm');
        preview.style.width = ancho;
    }

    form.addEventListener('input', actualizarPreview);
    form.addEventListener('change', actualizarPreview);
    actualizarPreview();
})();
</script>

<script>
(function () {
    var btn = document.getElementById('btn-probar-impresora');
    var resultado = document.getElementById('resultado-impresora');
    if (!btn || !resultado) return;

    btn.addEventListener('click', function () {
        var ip = (document.querySelector('[name="impresora_lan_ip"]')?.value || '').trim();
        var puerto = document.querySelector('[name="impresora_lan_puerto"]')?.value || '9100';
        if (!ip) {
            resultado.textContent = 'Ingresa la dirección IP de la impresora primero.';
            resultado.className = 'alerta alerta-error';
            return;
        }
        btn.disabled = true;
        resultado.textContent = 'Enviando ticket de prueba...';
        resultado.className = 'alerta alerta-exito';

        fetch(URL_BASE + 'impresora/probar', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json; charset=UTF-8' },
            body: JSON.stringify({
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
                ip: ip,
                puerto: puerto
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (datos) {
            resultado.textContent = datos.mensaje || 'Sin respuesta del servidor.';
            resultado.className = datos.exito ? 'alerta alerta-exito' : 'alerta alerta-error';
        })
        .catch(function () {
            resultado.textContent = 'No se pudo conectar con el servidor.';
            resultado.className = 'alerta alerta-error';
        })
        .finally(function () { btn.disabled = false; });
    });
})();
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>