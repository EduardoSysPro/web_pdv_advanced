/**
 * Web PDV — Soporte para instalación como WebApp (PWA / "Agregar a inicio").
 *
 * Objetivo: evitar que las impresiones (tickets, cotizaciones, reportes,
 * comprobantes) abran pestañas/ventanas nuevas que se acumulan al trabajar.
 *
 *  - WebApp instalada (display-mode: standalone): las vistas de impresión se
 *    cargan en un <iframe> oculto reutilizable y ellas mismas llaman a
 *    window.print() al cargar (o detectan el marcador __pdv_autoprint__).
 *  - Navegador normal: las vistas de impresión se abren en la ventana nombrada
 *    'TicketImpresion' — la misma que usa el POS al cobrar — de modo que las
 *    reimpresiones reutilizan esa ventana en lugar de abrir pestañas nuevas.
 *  - Los enlaces internos con target="_blank" (Imprimir, Ver ticket, Reimpresión)
 *    se redirigen a ese mismo mecanismo en ambos modos.
 *  - Cuando un flujo guarda y navega de inmediato (p. ej. "Guardar e imprimir"
 *    de cotizaciones), la URL queda pendiente en sessionStorage y se imprime
 *    al llegar a la página siguiente (imprimirAlVolver).
 */
(function () {
    'use strict';

    var CLAVE_PENDIENTE = 'pdv_impresion_pendiente';
    var NOMBRE_VENTANA = 'TicketImpresion';
    var FEATURES_VENTANA = 'width=400,height=600,top=100,left=300,toolbar=no,location=no,status=no,menubar=no';

    function esInstalada() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
               window.navigator.standalone === true;
    }

    var instalada = esInstalada();
    var iframeImpresion = null;

    function obtenerIframe() {
        if (!iframeImpresion) {
            iframeImpresion = document.createElement('iframe');
            iframeImpresion.name = '__pdv_autoprint__';
            iframeImpresion.setAttribute('aria-hidden', 'true');
            iframeImpresion.tabIndex = -1;
            iframeImpresion.style.cssText = 'position:fixed;left:-9999px;top:0;width:640px;height:480px;border:0;visibility:hidden;';
            document.body.appendChild(iframeImpresion);
        }
        return iframeImpresion;
    }

    /**
     * Abre una vista de impresión interna (ticket, cotización, reporte, comprobante...).
     * - Instalada como WebApp: iframe oculto reutilizable (sin pestañas nuevas).
     * - Navegador normal: ventana nombrada 'TicketImpresion' reutilizable
     *   (la del POS al cobrar), en lugar de una pestaña nueva por impresión.
     */
    function abrirImpresion(url) {
        if (instalada) {
            obtenerIframe().src = url;
            return null;
        }
        var w = window.open(url, NOMBRE_VENTANA, FEATURES_VENTANA);
        window.setTimeout(function () {
            try {
                if (w && !w.closed) w.focus();
            } catch (e) {}
        }, 250);
        return w;
    }

    /**
     * Reserva la impresión para la siguiente página (flujo "guardar y navegar"),
     * evitando que la impresión se pierda al navegar de inmediato.
     */
    function imprimirAlVolver(url) {
        try {
            sessionStorage.setItem(CLAVE_PENDIENTE, url);
        } catch (e) {}
    }

    // Clics en enlaces internos con target="_blank" (Imprimir, Ver ticket,
    // Reimpresión...) → vista de impresión reutilizable, sin pestañas nuevas.
    document.addEventListener('click', function (e) {
        var enlace = e.target && e.target.closest ? e.target.closest('a[target="_blank"]') : null;
        if (!enlace) return;
        var destino;
        try {
            destino = new URL(enlace.href, window.location.href);
        } catch (err) {
            return;
        }
        // Enlaces externos (WhatsApp, ayuda, etc.) siguen abriendo pestaña normal.
        if (destino.origin !== window.location.origin) return;
        e.preventDefault();
        abrirImpresion(destino.href);
    }, true);

    // Al llegar a una página nueva, imprime lo pendiente (flujo guardar + imprimir).
    if (instalada) {
        (function procesarPendiente() {
            var pendiente = null;
            try {
                pendiente = sessionStorage.getItem(CLAVE_PENDIENTE);
            } catch (e) {}
            if (!pendiente) return;
            try {
                sessionStorage.removeItem(CLAVE_PENDIENTE);
            } catch (e) {}
            abrirImpresion(pendiente);
        }());
    }

    window.PDV_WEBAPP = {
        instalada: instalada,
        imprimir: abrirImpresion,
        abrirTicket: abrirImpresion,
        imprimirAlVolver: imprimirAlVolver
    };
}());