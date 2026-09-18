<?php $tituloPagina = 'Soporte'; require APP_PATH . 'Views/layouts/pos_header.php'; ?>

<section class="catalogo-encabezado soporte-encabezado">
    <div>
        <span class="eyebrow">Administración</span>
        <h1>Soporte y guía del sistema</h1>
        <p>Conoce cómo funciona cada módulo del punto de venta y contáctanos directamente por WhatsApp si necesitas ayuda.</p>
    </div>
</section>

<div class="soporte-guia-grid">

    <!-- Ventas -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Ventas (POS Escritorio)</div>
                <span class="soporte-modulo-ruta">/ventas</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">El corazón del sistema. Busca productos por nombre o código, escanéalos, maneja varias cuentas a la vez, aplica descuentos por artículo y cobra en efectivo, tarjeta, transferencia o a crédito. Al finalizar se genera el ticket y se imprime a la impresora térmica de red.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Búsqueda rápida</span>
            <span class="soporte-chip">Descuentos</span>
            <span class="soporte-chip">Crédito a clientes</span>
        </div>
    </section>

    <!-- Cotizaciones -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-verde"><i class="fa-solid fa-file-invoice"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Cotizaciones</div>
                <span class="soporte-modulo-ruta">/cotizaciones</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Prepara una propuesta de venta sin afectar el inventario: busca productos, ajusta precio, descuento y cantidad, selecciona el cliente (o lo registras al momento) y define la fecha de validez. Al guardar se genera el folio, la cotización queda en estado pendiente y puedes imprimirla o revisarla desde el listado. Mientras esté pendiente se puede editar o cancelar; cuando el cliente la acepta, el cajero la carga en el POS y la convierte en venta.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">No descuenta stock</span>
            <span class="soporte-chip">Folio e impresión</span>
            <span class="soporte-chip">Facturar desde caja</span>
        </div>
    </section>

    <!-- Vista Móvil -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-verde"><i class="fa-solid fa-mobile-screen"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Terminal Móvil</div>
                <span class="soporte-modulo-ruta">/movil</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Diseñada para celulares y tablets. Permite tomar ventas tocando la pantalla, leer códigos de barras con la cámara, editar precios/descuentos, vender a crédito seleccionando el cliente, ver el historial de ventas del día con reimpresión y consultar el cambio al cobrar.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Sin lector externo</span>
            <span class="soporte-chip">Cámara</span>
            <span class="soporte-chip">Ventas de hoy</span>
        </div>
    </section>

    <!-- Clientes -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Clientes</div>
                <span class="soporte-modulo-ruta">/clientes</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Registra clientes con su RTN, teléfono y dirección; controla su límite y saldo de crédito, revisa el estado de cuenta (cargos y abonos) y registra pagos de créditos con su comprobante.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Estado de cuenta</span>
            <span class="soporte-chip">Abonos</span>
        </div>
    </section>

    <!-- Productos -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-ambar"><i class="fa-solid fa-box"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Productos</div>
                <span class="soporte-modulo-ruta">/productos</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Da de alta tu mercadería: nombre, código de barras (unidad y empaque), costo, precio de venta, impuesto (ISV o exento) y stock mínimo. También administras las categorías desde cada producto.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Códigos de barras</span>
            <span class="soporte-chip">Presentaciones</span>
            <span class="soporte-chip">ISV</span>
        </div>
    </section>

    <!-- Inventario -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-rojo"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Inventario</div>
                <span class="soporte-modulo-ruta">/inventario</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Consulta existencias, ajusta stock (entradas, salidas o correcciones) y recibe alertas de productos con stock bajo para saber cuándo reabastecer.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Ajustes</span>
            <span class="soporte-chip">Alertas de stock</span>
        </div>
    </section>

    <!-- Corte de Caja -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-violeta"><i class="fa-solid fa-calculator"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Corte de Caja</div>
                <span class="soporte-modulo-ruta">/caja</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Abre el turno de caja al iniciar la jornada, registra ingresos y egresos, y al cierre genera el corte con el resumen de ventas, métodos de pago y arqueo de la caja para imprimir o guardar.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Turnos</span>
            <span class="soporte-chip">Arqueo</span>
        </div>
    </section>

    <!-- Reimpresión -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Reimpresión de Comprobantes</div>
                <span class="soporte-modulo-ruta">/comprobantes</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Busca cualquier venta por folio, cliente o fecha y reimprime el comprobante —por la impresora LAN o en la ventana del navegador— cuando un recibo se pierde o el cliente lo pide de nuevo.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Búsqueda por folio</span>
            <span class="soporte-chip">Impresión LAN</span>
        </div>
    </section>

    <!-- Compras -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-verde"><i class="fa-solid fa-truck-ramp-box"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Compras</div>
                <span class="soporte-modulo-ruta">/compras</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Registra la entrada de mercadería al negocio: selecciona productos, cantidades y costo, vincula proveedor y factura, y el stock se actualiza automáticamente.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Entradas de stock</span>
            <span class="soporte-chip">Pagos a proveedor</span>
        </div>
    </section>

    <!-- Proveedores -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-ambar"><i class="fa-solid fa-handshake"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Proveedores</div>
                <span class="soporte-modulo-ruta">/proveedores</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Mantén el catálogo de tus abastecedores con datos de contacto y el saldo pendiente por compras a plazos, facilitando la gestión de pagos.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Saldos</span>
            <span class="soporte-chip">Historial</span>
        </div>
    </section>

    <!-- Reportes -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-violeta"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Reportes</div>
                <span class="soporte-modulo-ruta">/reportes</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Analiza la marcha del negocio: ventas, ganancias, productos favoritos y desglose por método de pago entre dos fechas. Se imprimen o se exportan a Excel (CSV).</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Ganancias</span>
            <span class="soporte-chip">Exportar CSV</span>
        </div>
    </section>

    <!-- Configuración -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-neutro"><i class="fa-solid fa-gear"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Configuración</div>
                <span class="soporte-modulo-ruta">/configuracion</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Datos del negocio (nombre, RTN, teléfono, dirección), formato del ticket (ancho, fuente, mensaje), configuración de la impresora por red y los parámetros de facturación SAR cuando se requiera facturar.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Impresora LAN</span>
            <span class="soporte-chip">SAR / CAI</span>
        </div>
    </section>

    <!-- Usuarios -->
    <section class="tarjeta soporte-modulo">
        <div class="soporte-modulo-top">
            <div class="soporte-modulo-icon soporte-icon-rojo"><i class="fa-solid fa-user-gear"></i></div>
            <div>
                <div class="soporte-modulo-titulo">Usuarios</div>
                <span class="soporte-modulo-ruta">/usuarios</span>
            </div>
        </div>
        <p class="soporte-modulo-desc">Crea y administra los perfiles que usan el sistema: cajeros (con obligación de abrir caja), cajeros móviles (solo terminal móvil) y administradores con acceso total.</p>
        <div class="soporte-chips">
            <span class="soporte-chip">Roles</span>
            <span class="soporte-chip">Permisos</span>
        </div>
    </section>

</div>

<!-- Contacto de soporte directo -->
<section class="soporte-whatsapp">
    <div class="soporte-whatsapp-inner">
        <div class="soporte-whatsapp-icono">
            <i class="fa-brands fa-whatsapp"></i>
        </div>
        <div class="soporte-whatsapp-texto">
            <h2>¿Necesitas ayuda con el sistema?</h2>
            <p>Escríbenos y con gusto te atiende <strong>Carlos Martínez</strong>: dudas, configuraciones, impresora de red o asesoría en cualquier módulo.</p>
            <div class="soporte-whatsapp-numero"><i class="fa-solid fa-phone"></i> +504 9552-4118 (Solo WhatsApp)</div>
        </div>
        <a class="soporte-whatsapp-btn" href="https://wa.me/50495524118?text=Hola%2C%20te%20escribo%20por%20el%20sistema%20Web%20PDV%2C%20necesito%20ayuda." target="_blank" rel="noopener">
            <i class="fa-brands fa-whatsapp"></i>
            Escribir por WhatsApp
        </a>
    </div>
</section>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>