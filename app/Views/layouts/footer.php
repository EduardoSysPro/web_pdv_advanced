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
</body>
</html>
