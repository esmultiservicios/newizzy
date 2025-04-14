/*!
 * Start Bootstrap - SB Admin v6.0.1 (https://startbootstrap.com/templates/sb-admin)
 * Copyright 2013-2020 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
 */

(function($) {
    "use strict";

    // Add active state to sidebar nav links
    var path = window.location.href; // because the 'href' property of the DOM element is the absolute path
    $("#layoutSidenav_nav .sb-sidenav a.nav-link").each(function() {
        if (this.href === path) {
            $(this).addClass("active");
        }
    });

    // Toggle the side navigation
    $("#sidebarToggle").on("click", function(e) {
        e.preventDefault();
        $("body").toggleClass("sb-sidenav-toggled");
    });
})(jQuery);

// Función para manejar pantalla completa
function toggleFullscreen() {
    const elem = document.documentElement;

    if (!document.fullscreenElement) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
}

// Guardar el estado de pantalla completa
function saveFullscreenState(isFullscreen) {
    localStorage.setItem('isFullscreen', isFullscreen);
}

// Cargar el estado de pantalla completa
function loadFullscreenState() {
    return localStorage.getItem('isFullscreen') === 'true';
}

// Restaurar el modo de pantalla completa al cargar la página
function restoreFullscreenIfNeeded() {
    const shouldRestoreFullscreen = loadFullscreenState();
    if (shouldRestoreFullscreen) {
        // Activar el modo de pantalla completa inmediatamente
        toggleFullscreen();
    }
}

$(function() {
    const fullscreenBtn = $('#global-fullscreen-btn');

    // Manejar el clic en el botón de pantalla completa
    fullscreenBtn.click(function() {
        toggleFullscreen();
    });

    // Escuchar cambios en el modo de pantalla completa
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

        // Guardar el estado actual
        saveFullscreenState(isFullscreen);
    }

    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('msfullscreenchange', updateFullscreenButton);

    // Restaurar el modo de pantalla completa tan pronto como sea posible
    restoreFullscreenIfNeeded();
});

// Función para manejar la barra lateral
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sb-sidenav');
    const topbar = document.querySelector('.sb-topnav');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.querySelector('.sb-content-overlay');
    
    // Función para abrir/cerrar sidebar
    function toggleSidebar() {
        if (window.innerWidth <= 992) {
            // Comportamiento móvil
            sidebar.classList.toggle('show-mobile');
            overlay.classList.toggle('active');
            
            // Bloquear scroll del body cuando sidebar está abierto
            document.body.style.overflow = sidebar.classList.contains('show-mobile') ? 'hidden' : '';
        } else {
            // Comportamiento desktop
            sidebar.classList.toggle('collapsed');
            updateTopbarPosition();
        }
    }
    
    // Función para actualizar la posición del topbar (solo desktop)
    function updateTopbarPosition() {
        if (window.innerWidth > 992) {
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
            document.body.classList.toggle('sidebar-open', !isCollapsed);
        }
    }
    
    // Evento para el botón de toggle
    sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });
    
    // Cerrar sidebar al hacer clic en overlay (solo móviles)
    overlay.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show-mobile');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Cerrar sidebar al hacer clic fuera (solo móviles)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && 
            sidebar.classList.contains('show-mobile') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle) {
            sidebar.classList.remove('show-mobile');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Manejo responsive mejorado
    function handleResponsive() {
        if (window.innerWidth <= 992) {
            // Comportamiento móvil
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-open', 'sidebar-collapsed');
            
            // Asegurar que el sidebar está cerrado al cambiar a móvil
            if (sidebar.classList.contains('show-mobile')) {
                sidebar.classList.remove('show-mobile');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        } else {
            // Comportamiento desktop
            sidebar.classList.remove('show-mobile');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            updateTopbarPosition();
        }
    }
    
    // Inicialización
    handleResponsive();
    
    // Escuchar cambios de tamaño con debounce para mejor rendimiento
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResponsive, 100);
    });
});
