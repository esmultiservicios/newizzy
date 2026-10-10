/* ============================================================
   IZZY | MENÚ DE USUARIO
   Dos estados reales: ABIERTO / CERRADO.
   Segundo clic sobre el icono de persona = CERRAR.
   ============================================================ */
(function () {
    "use strict";

    var initialized = false;

    function getUserMenuElements() {
        var button = document.getElementById("userDropdown");
        if (!button) return null;

        var wrapper = button.closest(".nav-item.dropdown");
        if (!wrapper) return null;

        var menu = wrapper.querySelector(".user-dropdown");
        if (!menu) return null;

        return {
            button: button,
            wrapper: wrapper,
            menu: menu
        };
    }

    function openUserMenu() {
        var els = getUserMenuElements();
        if (!els) return;

        if (
            window.IZZYMobileMainMenu &&
            typeof window.IZZYMobileMainMenu.close === "function"
        ) {
            window.IZZYMobileMainMenu.close();
        }

        els.wrapper.classList.add("show");
        els.menu.classList.add("show");
        els.menu.style.display = "block";
        els.button.classList.add("show");
        els.button.setAttribute("aria-expanded", "true");
    }

    function closeUserMenu() {
        var els = getUserMenuElements();
        if (!els) return;

        els.wrapper.classList.remove("show");
        els.menu.classList.remove("show");
        els.menu.style.removeProperty("display");
        els.button.classList.remove("show");
        els.button.setAttribute("aria-expanded", "false");
    }

    function toggleUserMenu(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var els = getUserMenuElements();
        if (!els) return;

        if (
            els.menu.classList.contains("show") ||
            els.menu.style.display === "block" ||
            els.button.getAttribute("aria-expanded") === "true"
        ) {
            closeUserMenu();
        } else {
            openUserMenu();
        }
    }

    function initUserMenu() {
        if (initialized) return;

        var els = getUserMenuElements();
        if (!els) return;

        initialized = true;

        /* Evita doble control con Bootstrap. */
        els.button.removeAttribute("data-toggle");
        els.button.removeAttribute("data-bs-toggle");

        /* Captura el clic directamente sobre el usuario. */
        els.button.addEventListener("click", toggleUserMenu, false);

        /* Clic fuera = cerrar. */
        document.addEventListener("click", function (event) {
            var current = getUserMenuElements();
            if (!current) return;

            if (
                !current.button.contains(event.target) &&
                !current.menu.contains(event.target)
            ) {
                closeUserMenu();
            }
        }, false);

        /* ESC = cerrar. */
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeUserMenu();
            }
        }, false);

        /* Clic en opción = cerrar. */
        els.menu.addEventListener("click", function (event) {
            if (event.target.closest(".dropdown-item")) {
                closeUserMenu();
            }
        }, false);

        closeUserMenu();
    }

    window.IZZYUserMenu = {
        init: initUserMenu,
        open: openUserMenu,
        close: closeUserMenu,
        toggle: toggleUserMenu
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initUserMenu);
    } else {
        initUserMenu();
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
