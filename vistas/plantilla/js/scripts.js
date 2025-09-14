/*!
 * Start Bootstrap - SB Admin v6.0.1 (https://startbootstrap.com/templates/sb-admin)
 * Copyright 2013-2020 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
 */

(function($) {
    "use strict";

    // Add active state to sidebar nav links
    var path = window.location.href;
    $("#layoutSidenav_nav .sb-sidenav a.nav-link").each(function() {
        if (this.href === path) {
            $(this).addClass("active");
        }
    });

    // Toggle the side navigation (modo clásico)
    $("#sidebarToggle").on("click", function(e) {
        e.preventDefault();
        $("body").toggleClass("sb-sidenav-toggled");
    });
})(jQuery);

// ===== Pantalla completa =====
function toggleFullscreen() {
    const elem = document.documentElement;

    if (!document.fullscreenElement &&
        !document.webkitFullscreenElement &&
        !document.msFullscreenElement) {

        (elem.requestFullscreen ||
         elem.webkitRequestFullscreen ||
         elem.msRequestFullscreen ||
         function(){}).call(elem);

    } else {
        (document.exitFullscreen ||
         document.webkitExitFullscreen ||
         document.msExitFullscreen ||
         function(){})();

    }
}

function saveFullscreenState(isFullscreen) {
    try { localStorage.setItem('isFullscreen', !!isFullscreen); } catch(e){}
}

function loadFullscreenState() {
    try { return localStorage.getItem('isFullscreen') === 'true'; } catch(e){ return false; }
}

function restoreFullscreenIfNeeded() {
    if (loadFullscreenState()) {
        // Por seguridad no forzamos automáticamente, algunos navegadores lo bloquean
    }
}

$(function() {
    const fullscreenBtn = $('#global-fullscreen-btn');

    fullscreenBtn.on('click', function() {
        toggleFullscreen();
    });

    function updateFullscreenButton() {
        const isFullscreen = document.fullscreenElement ||
                             document.webkitFullscreenElement ||
                             document.msFullscreenElement;

        const icon = fullscreenBtn.find('i');
        if (isFullscreen) {
            icon.removeClass('fa-expand').addClass('fa-compress');
            fullscreenBtn.attr('title', 'Salir de pantalla completa');
            fullscreenBtn.addClass('fullscreen-active');
        } else {
            icon.removeClass('fa-compress').addClass('fa-expand');
            fullscreenBtn.attr('title', 'Pantalla completa');
            fullscreenBtn.removeClass('fullscreen-active');
        }
        saveFullscreenState(!!isFullscreen);
    }

    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('msfullscreenchange', updateFullscreenButton);

    restoreFullscreenIfNeeded();
    try { updateFullscreenButton(); } catch(e){}
});

// ===== Barra lateral / overlay =====
document.addEventListener('DOMContentLoaded', function() {
    const sidebar       = document.querySelector('.sb-sidenav');
    const sidebarToggle = document.getElementById('sidebarToggle');
    let overlay         = document.querySelector('.sb-content-overlay'); // opcional

    // Si quieres generar overlay si no existe, descomenta:
    // if (!overlay) {
    //     overlay = document.createElement('div');
    //     overlay.className = 'sb-content-overlay';
    //     document.body.appendChild(overlay);
    // }

    function toggleSidebar() {
        if (!sidebar) return;

        if (window.innerWidth <= 992) {
            // Móvil
            sidebar.classList.toggle('show-mobile');
            if (overlay) overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('show-mobile') ? 'hidden' : '';
        } else {
            // Desktop
            sidebar.classList.toggle('collapsed');
            updateTopbarPosition();
        }
    }

    function updateTopbarPosition() {
        if (!sidebar) return;
        if (window.innerWidth > 992) {
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
            document.body.classList.toggle('sidebar-open', !isCollapsed);
        }
    }

    // Botón toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Cerrar con overlay (solo si existe)
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (!sidebar) return;
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show-mobile');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!sidebar) return;

        if (window.innerWidth <= 992 &&
            sidebar.classList.contains('show-mobile') &&
            !sidebar.contains(e.target) &&
            e.target !== sidebarToggle) {
            sidebar.classList.remove('show-mobile');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    function handleResponsive() {
        if (!sidebar) return;

        if (window.innerWidth <= 992) {
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-open', 'sidebar-collapsed');

            if (sidebar.classList.contains('show-mobile')) {
                sidebar.classList.remove('show-mobile');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        } else {
            sidebar.classList.remove('show-mobile');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
            updateTopbarPosition();
        }
    }

    // Inicializar
    handleResponsive();

    // Resize con debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResponsive, 100);
    });
});

// === Botón "Menú principal" para el topnav (sin tocar tu HTML) ===
(function () {
    // Evita duplicar si el script se inyecta dos veces
    if (window.__topMenuInjected) return;
    window.__topMenuInjected = true;
  
    // 1) Localizar la topbar y el UL del menú principal (el PRIMERO, no el de usuario)
    var $topbar   = $('.sb-topnav').first();
    var $menuUL   = $topbar.find('> ul.navbar-nav').first(); // tu UL de Reporte Ventas, etc.
    var $fsBtn    = $('#global-fullscreen-btn');             // para insertar el botón después
    if (!$topbar.length || !$menuUL.length || !$fsBtn.length) return;
  
    // 2) Crear botón y contenedor colapsable
    var $btn = $(
      '<button id="topMenuBtn" type="button" class="btn btn-outline-light btn-sm ml-3 d-none" ' +
        'data-toggle="collapse" data-target="#mainTopMenu" ' +
        'aria-controls="mainTopMenu" aria-expanded="false" aria-label="Menú principal">' +
        '<i class="fas fa-list mr-2"></i> Menú principal' +
      '</button>'
    );
  
    var $wrap = $('<div id="mainTopMenu" class="collapse navbar-collapse"></div>');
  
    // 3) Insertar en el DOM (después del botón de fullscreen)
    $btn.insertAfter($fsBtn);
    $wrap.insertAfter($btn);
  
    // 4) Mover TU UL dentro del contenedor colapsable
    //    (le ponemos un id para referencias futuras)
    $menuUL.attr('id', 'topMenuList').appendTo($wrap);
  
    // 5) Mostrar/ocultar el botón SOLO si hay items visibles (tras permisos)
    function updateTopMenuVisibility() {
      // .menu-item es la clase que ya tienen tus <a>; evaluamos si están visibles
      var visibles = $wrap.find('.menu-item:visible').length;
      if (visibles > 0) {
        $btn.removeClass('d-none').show();
      } else {
        $btn.addClass('d-none').hide();
        $wrap.collapse('hide');
      }
    }
  
    // 6) Cerrar al hacer clic fuera (cuando esté abierto)
    $(document).on('click', function (e) {
      var abierto = $wrap.hasClass('show');
      if (!abierto) return;
      var clickEnWrap = $wrap.is(e.target) || $wrap.has(e.target).length > 0;
      var clickEnBtn  = $btn.is(e.target)  || $btn.has(e.target).length > 0;
      if (!clickEnWrap && !clickEnBtn) $wrap.collapse('hide');
    });
  
    // 7) Desktop vs móvil
    function syncDesktopMobile() {
      var isDesktop = window.innerWidth >= 992; // >= lg
      if (isDesktop) {
        // en desktop mantenemos el menú visible inline
        if (!$wrap.hasClass('show')) $wrap.addClass('show');
      } else {
        // en tablet/móvil arranca cerrado
        $wrap.removeClass('show');
      }
    }
  
    // 8) Observador de cambios de estilo/permisos (si tus permisos cambian display:none)
    var node = $wrap.get(0);
    if (node && 'MutationObserver' in window) {
      var obs = new MutationObserver(function (muts) {
        var needs = muts.some(function (m) {
          return (m.type === 'attributes' && m.attributeName === 'style') || m.type === 'childList';
        });
        if (needs) updateTopMenuVisibility();
      });
      obs.observe(node, { subtree: true, childList: true, attributes: true, attributeFilter: ['style', 'class'] });
    }
  
    // 9) Hooks públicos: llama esto después de actualizar permisos
    window.updateTopMenuVisibility = updateTopMenuVisibility;
    window.syncTopMenuLayout = syncDesktopMobile;
  
    // 10) Arranque
    $(function () {
      syncDesktopMobile();
      updateTopMenuVisibility();
    });
  
    // 11) Redimensionado
    var t;
    $(window).on('resize', function () {
      clearTimeout(t);
      t = setTimeout(function () {
        syncDesktopMobile();
        updateTopMenuVisibility();
      }, 120);
    });
  })();

  // ===== Menú principal móvil: clona los .menu-item del menú desktop =====
(function () {
    function buildMobileMenu() {
      var container = document.getElementById('mobile-mainmenu');
      if (!container) return;
  
      container.innerHTML = '';
      // Todos los links con clase .menu-item del menú grande
      var links = document.querySelectorAll('.navbar-nav.d-none.d-lg-flex .nav-link.menu-item');
  
      links.forEach(function (a) {
        var item = a.cloneNode(true);           // conserva clases (reporteVentas, transferencia, etc.)
        item.classList.remove('nav-link');
        item.classList.add('dropdown-item');
  
        // Respeta display:none (permisos)
        var s = window.getComputedStyle(a);
        item.style.display = (s.display === 'none') ? 'none' : '';
  
        container.appendChild(item);
      });
  
      // Cerrar dropdown al hacer clic en un ítem visible
      container.addEventListener('click', function (e) {
        var t = e.target.closest('.dropdown-item');
        if (t && t.style.display !== 'none') {
          var btn = document.getElementById('mobile-mainmenu-btn');
          if (btn && typeof $ === 'function' && $.fn.dropdown) $(btn).dropdown('hide');
        }
      });
    }
  
    // Construir al cargar
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', buildMobileMenu);
    } else {
      buildMobileMenu();
    }
  
    // Si tu app llama a actualizarPermisos(), reconstruir después
    if (typeof window.actualizarPermisos === 'function') {
      var original = window.actualizarPermisos;
      window.actualizarPermisos = function () {
        var p = original.apply(this, arguments);
        try {
          if (p && typeof p.then === 'function') {
            p.finally(buildMobileMenu);
          } else {
            setTimeout(buildMobileMenu, 0);
          }
        } catch (_) {
          setTimeout(buildMobileMenu, 0);
        }
        return p;
      };
    }
  })();
  