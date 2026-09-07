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

            function updateClock() {
                if (!clock) return;
                clock.textContent = new Intl.DateTimeFormat('es-HN', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                }).format(new Date());
            }
            updateClock();
            window.setInterval(updateClock, 30000);
        }());
    </script>
</body>
</html>
