/* ============================================================
   IZZY | Dropdown de usuario
   Control propio para evitar conflicto con el toggle de Bootstrap.
   1er clic: abre
   2do clic: cierra
   clic fuera: cierra
   Escape: cierra
   ============================================================ */
(function () {
    'use strict';

    function obtenerElementos() {
        var toggle = document.getElementById('userDropdown');
        if (!toggle) return null;

        var item = toggle.closest('.nav-item.dropdown');
        if (!item) return null;

        var menu = item.querySelector('.user-dropdown');
        if (!menu) return null;

        return {
            toggle: toggle,
            item: item,
            menu: menu
        };
    }

    function estaAbierto(partes) {
        return !!(
            partes &&
            (
                partes.item.classList.contains('show') ||
                partes.menu.classList.contains('show') ||
                partes.toggle.getAttribute('aria-expanded') === 'true'
            )
        );
    }

    function abrir(partes) {
        if (!partes) return;

        partes.item.classList.add('show');
        partes.menu.classList.add('show');
        partes.toggle.classList.add('show');
        partes.toggle.setAttribute('aria-expanded', 'true');
    }

    function cerrar(partes) {
        if (!partes) return;

        partes.item.classList.remove('show');
        partes.menu.classList.remove('show');
        partes.toggle.classList.remove('show');
        partes.toggle.setAttribute('aria-expanded', 'false');
    }

    function toggleUsuario(event) {
        var partes = obtenerElementos();
        if (!partes) return;

        event.preventDefault();
        event.stopPropagation();

        if (estaAbierto(partes)) {
            cerrar(partes);
        } else {
            abrir(partes);
        }
    }

    function iniciar() {
        var partes = obtenerElementos();
        if (!partes) return;

        /* Evita registrar el comportamiento más de una vez. */
        if (partes.toggle.getAttribute('data-izzy-user-dropdown-ready') === '1') {
            return;
        }

        partes.toggle.setAttribute('data-izzy-user-dropdown-ready', '1');

        partes.toggle.addEventListener('click', toggleUsuario, false);

        /* Cerrar al tocar cualquier zona fuera del menú. */
        document.addEventListener('click', function (event) {
            var actuales = obtenerElementos();
            if (!actuales || !estaAbierto(actuales)) return;

            if (
                actuales.toggle.contains(event.target) ||
                actuales.menu.contains(event.target)
            ) {
                return;
            }

            cerrar(actuales);
        }, false);

        /* Cerrar con Escape. */
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;

            var actuales = obtenerElementos();
            if (!actuales || !estaAbierto(actuales)) return;

            cerrar(actuales);
            actuales.toggle.focus();
        }, false);

        /* Al seleccionar una opción real del menú, dejarlo cerrado. */
        partes.menu.addEventListener('click', function (event) {
            var enlace = event.target && event.target.closest
                ? event.target.closest('a.dropdown-item')
                : null;

            if (!enlace) return;

            window.setTimeout(function () {
                cerrar(obtenerElementos());
            }, 0);
        }, false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();

/* IZZY | Pantalla completa sin recargar al activar ni desactivar.
   El iframe se crea solamente al navegar a otra pantalla en modo Fullscreen.
   El documento principal conserva sus formularios mientras no se navegue. */
(function () {
    'use strict';
    var boton = document.getElementById('izzy-persistent-fullscreen');
    if (!boton) return;
    var origen = window.location.origin;
    var enFrame = window.self !== window.top;
    var marco = null;
    var cerrando = false;
    var cargandoMarco = false;
    var rutaRaizInicial = location.pathname + location.search + location.hash;

    function elementoFullscreen() {
        return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    function dibujar(activo) {
        var icono = boton.querySelector('i');
        if (icono) icono.className = activo ? 'fas fa-compress' : 'fas fa-expand';
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        boton.setAttribute('aria-label', activo ? 'Salir de pantalla completa' : 'Activar pantalla completa');
        boton.title = activo ? 'Salir de pantalla completa' : 'Activar pantalla completa';
        boton.classList.toggle('active', activo);
    }

    if (enFrame) {
        dibujar(true);
        boton.addEventListener('click', function (event) {
            event.preventDefault();
            window.parent.postMessage({tipo: 'izzy:salir-fullscreen'}, origen);
        });
        return;
    }

    function rutaActual() {
        return location.pathname + location.search + location.hash;
    }

    function rutaDelMarco() {
        try {
            var url = new URL(marco.contentWindow.location.href);
            if (url.origin === origen) return url.pathname + url.search + url.hash;
        } catch (error) {}
        return '';
    }

    function montarMarco(url) {
        if (marco) return;
        marco = document.createElement('iframe');
        marco.id = 'izzyFullscreenFrame';
        marco.name = 'izzyFullscreenFrame';
        marco.title = 'IZZY - Pantalla completa';
        marco.setAttribute('allow', 'clipboard-read; clipboard-write');
        cargandoMarco = true;
        marco.addEventListener('load', function () {
            cargandoMarco = false;
            var ruta = rutaDelMarco();
            if (ruta) history.replaceState(history.state, '', ruta);
        });
        marco.src = url;
        document.body.appendChild(marco);
        document.documentElement.classList.add('izzy-fs-shell-root');
        document.body.classList.add('izzy-fs-shell');
    }

    function desmontarMarco() {
        var destino = rutaDelMarco();
        if (marco) marco.remove();
        marco = null;
        cargandoMarco = false;
        document.body.classList.remove('izzy-fs-shell');
        document.documentElement.classList.remove('izzy-fs-shell-root');
        dibujar(false);
        if (destino && destino !== rutaRaizInicial) {
            location.replace(destino);
        }
    }

    function activar() {
        var raiz = document.documentElement;
        var solicitar = raiz.requestFullscreen || raiz.webkitRequestFullscreen;
        if (!solicitar) {
            if (typeof showNotify === 'function') showNotify('warning', 'Pantalla completa', 'No disponible en este navegador.');
            return;
        }
        try {
            Promise.resolve(solicitar.call(raiz, {navigationUI: 'hide'})).then(function () {
                dibujar(true);
            }).catch(function () {
                dibujar(false);
                if (typeof showNotify === 'function') showNotify('warning', 'Pantalla completa', 'El navegador no permitió activarla.');
            });
        } catch (error) { dibujar(false); }
    }

    function salir() {
        if (cerrando) return;
        cerrando = true;
        var terminar = document.exitFullscreen || document.webkitExitFullscreen;
        if (!terminar || !elementoFullscreen()) {
            desmontarMarco();
            cerrando = false;
            return;
        }
        try {
            Promise.resolve(terminar.call(document)).then(function () {
                desmontarMarco();
                cerrando = false;
            }).catch(function () {
                cerrando = false;
                if (!elementoFullscreen()) desmontarMarco();
            });
        } catch (error) { cerrando = false; }
    }

    document.addEventListener('click', function (event) {
        if (!elementoFullscreen() || marco || event.defaultPrevented ||
            event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var enlace = event.target.closest && event.target.closest('a[href]');
        if (!enlace || enlace.hasAttribute('download') || enlace.target && enlace.target !== '_self') return;
        var url;
        try { url = new URL(enlace.href, location.href); } catch (error) { return; }
        if (url.origin !== origen || !/^https?:$/.test(url.protocol)) return;
        if (url.pathname === location.pathname && url.search === location.search) return;
        event.preventDefault();
        montarMarco(url.href);
    }, true);

    boton.addEventListener('click', function (event) {
        event.preventDefault();
        if (elementoFullscreen()) salir(); else activar();
    });

    function alCambiarFullscreen() {
        if (elementoFullscreen()) {
            dibujar(true);
        } else if (!cerrando && marco) {
            desmontarMarco();
        } else {
            dibujar(false);
        }
    }
    document.addEventListener('fullscreenchange', alCambiarFullscreen);
    document.addEventListener('webkitfullscreenchange', alCambiarFullscreen);
    window.addEventListener('message', function (event) {
        if (event.origin !== origen || !event.data || event.data.tipo !== 'izzy:salir-fullscreen') return;
        if (!marco || event.source !== marco.contentWindow) return;
        salir();
    });
    dibujar(false);
})();

/* Prioridad GLOBAL de la validación administrativa para todos los módulos. */
(function () {
    'use strict';
    if (window.__izzyAdminModalSuperiorGlobal) return;
    window.__izzyAdminModalSuperiorGlobal = true;
    var selector = '#modalAutenticacionAdminSistema';
    var modalZ = '2147483000';
    var backdropZ = '2147482990';
    var ultimoBackdrop = null;

    function autorizacionAbierta() {
        var modal = document.querySelector(selector);
        return !!(modal && (modal.classList.contains('show') ||
            modal.style.display === 'block'));
    }
    function elevarAutorizacion() {
        var modal = document.querySelector(selector);
        if (!modal) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.style.setProperty('z-index', modalZ, 'important');
        modal.style.setProperty('position', 'fixed', 'important');
        var fondos = document.querySelectorAll('.modal-backdrop');
        if (fondos.length) {
            var fondo = fondos[fondos.length - 1];
            if (ultimoBackdrop && ultimoBackdrop !== fondo) {
                ultimoBackdrop.classList.remove('izzy-admin-priority-backdrop');
                ultimoBackdrop.style.removeProperty('z-index');
            }
            ultimoBackdrop = fondo;
            fondo.classList.add('izzy-admin-priority-backdrop');
            fondo.style.setProperty('z-index', backdropZ, 'important');
        }
    }
    function limpiar() {
        var modal = document.querySelector(selector);
        if (modal) {
            modal.style.removeProperty('z-index');
            modal.style.removeProperty('position');
        }
        if (ultimoBackdrop) {
            ultimoBackdrop.classList.remove('izzy-admin-priority-backdrop');
            ultimoBackdrop.style.removeProperty('z-index');
            ultimoBackdrop = null;
        }
        if (document.querySelector('.modal.show')) document.body.classList.add('modal-open');
    }
    function iniciar() {
        if (!window.jQuery) return;
        var $ = window.jQuery;
        $(document)
            .off('.izzyAdminPriorityGlobal')
            .on('show.bs.modal.izzyAdminPriorityGlobal', selector, function () {
                elevarAutorizacion();
            })
            .on('shown.bs.modal.izzyAdminPriorityGlobal', selector, function () {
                elevarAutorizacion();
                window.setTimeout(elevarAutorizacion, 0);
            })
            .on('hidden.bs.modal.izzyAdminPriorityGlobal', selector, limpiar)
            .on('shown.bs.modal.izzyAdminPriorityGlobal', '.modal', function () {
                if (this.id !== 'modalAutenticacionAdminSistema' && autorizacionAbierta())
                    elevarAutorizacion();
            });
        var observer = new MutationObserver(function (cambios) {
            if (!autorizacionAbierta()) return;
            for (var i = 0; i < cambios.length; i++) {
                if (cambios[i].addedNodes.length) {
                    elevarAutorizacion();
                    return;
                }
            }
        });
        observer.observe(document.body, {childList: true});
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, {once: true});
    } else iniciar();
})();
