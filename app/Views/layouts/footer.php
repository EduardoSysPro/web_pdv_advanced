            </main>
        </div>
    </div>
    <script>
        (function () {
            var shell = document.getElementById('pos-app-shell');
            var toggle = document.getElementById('pos-sidebar-toggle');
            var storageKey = 'web_pdv_sidebar_collapsed';
            var clock = document.getElementById('info-fecha-hora');

            function setCollapsed(collapsed) {
                shell.classList.toggle('sidebar-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', String(!collapsed));
                toggle.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
            }

            setCollapsed(localStorage.getItem(storageKey) === 'true');
            toggle.addEventListener('click', function () {
                var collapsed = !shell.classList.contains('sidebar-collapsed');
                setCollapsed(collapsed);
                localStorage.setItem(storageKey, String(collapsed));
            });

            var adminToggle = document.getElementById('pos-sidebar-admin-toggle');
            var adminSubmenu = document.getElementById('pos-sidebar-admin-submenu');
            if (adminToggle && adminSubmenu) {
                var adminKey = 'web_pdv_sidebar_admin_abierto';
                var adminActivo = adminSubmenu.querySelector('.is-active') !== null;

                function setAdminOpen(abierto) {
                    adminToggle.classList.toggle('is-open', abierto);
                    adminSubmenu.classList.toggle('is-open', abierto);
                    adminToggle.setAttribute('aria-expanded', String(abierto));
                }

                if (adminActivo || localStorage.getItem(adminKey) === 'true') setAdminOpen(true);

                adminToggle.addEventListener('click', function () {
                    if (shell.classList.contains('sidebar-collapsed')) {
                        setCollapsed(false);
                        localStorage.setItem(storageKey, 'false');
                        setAdminOpen(true);
                        return;
                    }
                    var abierto = !adminToggle.classList.contains('is-open');
                    setAdminOpen(abierto);
                    localStorage.setItem(adminKey, String(abierto));
                });
            }

            function updateClock() {
                if (!clock) return;
                clock.textContent = new Intl.DateTimeFormat('es-HN', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }).format(new Date());
            }
            updateClock();
            window.setInterval(updateClock, 30000);

            /* ===== Selector de temas: claro -> minimalista -> oscuro -> claro ===== */
            var botonTema = document.getElementById('pos-boton-tema');
            var temaKey = 'web_pdv_tema';
            var ordenTemas = ['claro', 'minimal', 'oscuro'];

            function temaActual() {
                var t = document.documentElement.getAttribute('data-tema');
                return (t === 'oscuro' || t === 'minimal') ? t : 'claro';
            }

            function iconoTema(t) {
                if (t === 'oscuro') return '<i class="fa-solid fa-sun"></i>';
                if (t === 'minimal') return '<i class="fa-solid fa-wand-magic-sparkles"></i>';
                return '<i class="fa-solid fa-moon"></i>';
            }

            function etiquetaTema(t) {
                if (t === 'oscuro') return 'Tema oscuro activo. Cambiar a tema claro';
                if (t === 'minimal') return 'Tema minimalista activo. Cambiar a tema oscuro';
                return 'Tema claro activo. Cambiar a tema minimalista';
            }

            function actualizarIconoTema(t) {
                if (!botonTema) return;
                botonTema.innerHTML = iconoTema(t);
                botonTema.setAttribute('aria-label', etiquetaTema(t));
                botonTema.setAttribute('title', etiquetaTema(t) + ' (claro → minimalista → oscuro)');
            }

            function aplicarTema(t) {
                if (t === 'claro') {
                    document.documentElement.removeAttribute('data-tema');
                } else {
                    document.documentElement.setAttribute('data-tema', t);
                }
                actualizarIconoTema(t);
                try { localStorage.setItem(temaKey, t); } catch (e) {}
            }

            actualizarIconoTema(temaActual());
            if (botonTema) {
                botonTema.addEventListener('click', function () {
                    var actual = temaActual();
                    var siguiente = ordenTemas[(ordenTemas.indexOf(actual) + 1) % ordenTemas.length];
                    aplicarTema(siguiente);
                });
            }

            /* ===== Buscador universal compacto (Ctrl + K o F10) ===== */
            /* Reutiliza el modal de búsqueda existente (F10): no crea lógica nueva,
               solo redirige el clic/teclado al flujo ya probado de pos.js. */
            function abrirBusquedaUniversal() {
                var btnBuscar = document.getElementById('btn-buscar');
                if (btnBuscar) {
                    btnBuscar.click();
                    return;
                }
                var inputBusqueda = document.getElementById('busqueda-productos-input');
                if (inputBusqueda) {
                    var modal = document.getElementById('modal-busqueda-productos');
                    if (modal) modal.hidden = false;
                    inputBusqueda.focus();
                    return;
                }
                /* Fuera de ventas: llevar a la pantalla de ventas (donde vive el buscador). */
                var base = (typeof URL_BASE !== 'undefined') ? URL_BASE : './';
                window.location.href = base + 'ventas';
            }

            var buscadorUniversal = document.getElementById('pos-busqueda-universal');
            if (buscadorUniversal && !buscadorUniversal.dataset.wired) {
                buscadorUniversal.dataset.wired = 'true';
                buscadorUniversal.addEventListener('click', abrirBusquedaUniversal);
            }

            document.addEventListener('keydown', function (e) {
                var esCtrlK = (e.ctrlKey || e.metaKey) && String(e.key || '').toLowerCase() === 'k';
                if (!esCtrlK) return;
                /* Si pos.js ya lo gestionó (pantalla de ventas), no duplicar. */
                if (e.defaultPrevented) return;
                e.preventDefault();
                abrirBusquedaUniversal();
            });
        }());
    </script>
    <script src="<?php echo URL_BASE; ?>js/webapp.js?v=<?php echo filemtime(PUBLIC_PATH . 'js/webapp.js'); ?>"></script>
</body>
</html>
