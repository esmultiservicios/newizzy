<style>
/* IZZY | Control del menú lateral en la barra superior.
   Estilos aislados: no alteran los demás botones del navbar. */
.sb-topnav #sidebarToggle.izzy-navbar-menu-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
    width: 38px;
    height: 34px;
    min-width: 38px;
    margin: 0 6px 0 4px;
    padding: 0;
    border: 1px solid #183d70;
    border-radius: 9px;
    background: #17345b;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(11, 31, 82, 0.14);
    transition: background-color 0.18s ease, border-color 0.18s ease,
                box-shadow 0.18s ease, transform 0.18s ease;
}
.sb-topnav #sidebarToggle.izzy-navbar-menu-toggle i {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    opacity: 1 !important;
    text-shadow: none !important;
    font-size: 17px;
    line-height: 1;
    pointer-events: none;
}
/* Los estilos globales del sidebar no deben oscurecer el icono. */
.sb-topnav button#sidebarToggle.izzy-navbar-menu-toggle .fa-bars,
.sb-topnav button#sidebarToggle.izzy-navbar-menu-toggle .fa-bars::before {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    opacity: 1 !important;
}
.sb-topnav #sidebarToggle.izzy-navbar-menu-toggle:hover {
    background: #245c9c;
    border-color: #245c9c;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(11, 31, 82, 0.19);
    transform: translateY(-1px);
}
.sb-topnav #sidebarToggle.izzy-navbar-menu-toggle:focus-visible {
    outline: 2px solid #1098ea;
    outline-offset: 2px;
}
.sb-topnav #sidebarToggle.izzy-navbar-menu-toggle:active {
    transform: translateY(0);
    background: #0b1f52;
}
@media (prefers-reduced-motion: reduce) {
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle { transition: none; }
}
</style>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-color-navarlateral">
  <div class="navbar-brand logo-container">
    <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
      <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logos/logo.svg"
           alt="IZZY" class="logo img-fluid">
    </a>
  </div>

  <!-- Botón de alternar menú lateral (sidebar) -->
  <button class="btn izzy-navbar-menu-toggle order-1 order-lg-0" id="sidebarToggle"
          type="button" title="Mostrar u ocultar menú lateral"
          aria-label="Mostrar u ocultar menú lateral" aria-controls="layoutSidenav_nav">
    <i class="fas fa-bars" aria-hidden="true" style="color:#fff!important;-webkit-text-fill-color:#fff!important;opacity:1!important;"></i>
  </button>

  <!-- Control de pantalla completa del navbar. Se mantiene al navegar la preferencia. -->
  <button id="izzy-persistent-fullscreen" class="btn btn-primary btn-sm ml-2 order-1 order-lg-0"
          type="button" title="Activar pantalla completa" aria-label="Activar pantalla completa"
          aria-pressed="false" style="flex:0 0 auto;min-width:36px;height:34px;border-radius:8px;">
    <i class="fas fa-expand" aria-hidden="true"></i>
  </button>

  <!-- ===== Botón Menú principal (solo tablets/móviles) ===== -->
  <div class="mobile-mainmenu-wrapper d-lg-none flex-grow-1 d-flex justify-content-center">
    <div class="dropdown">
      <button id="mobile-mainmenu-btn" class="btn btn-light btn-md px-3" type="button"
              aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bars mr-2"></i> Menú principal
      </button>
      <div id="mobile-mainmenu" class="dropdown-menu dropdown-menu-center shadow"
           aria-labelledby="mobile-mainmenu-btn"></div>
    </div>
  </div>

  <!-- Menú principal (versión desktop) -->
  <ul class="navbar-nav d-none d-lg-flex">
    <li class="nav-item"><a class="nav-link link menu-item reporteVentas" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/" style="display:none"><i class="fas fa-file-invoice-dollar fa-lg mr-2"></i>Reporte Ventas</a></li>
    <li class="nav-item"><a class="nav-link link menu-item reporteCotizacion" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCotizacion/" style="display:none"><i class="fas fa-file-signature fa-lg mr-2"></i>Reporte Cotización</a></li>
    <li class="nav-item"><a class="nav-link link menu-item reporteCompras" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/" style="display:none"><i class="fas fa-shopping-cart fa-lg mr-2"></i>Reporte Compras</a></li>
    <li class="nav-item"><a class="nav-link link menu-item cobrarClientes" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cobrarClientes/" style="display:none"><i class="fas fa-hand-holding-usd fa-lg mr-2"></i>CXC Clientes</a></li>
    <li class="nav-item"><a class="nav-link link menu-item pagarProveedores" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>pagarProveedores/" style="display:none"><i class="fas fa-file-invoice fa-lg mr-2"></i>CXP Proveedores</a></li>
    <li class="nav-item"><a class="nav-link link menu-item inventario" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>inventario/" style="display:none"><i class="fas fa-exchange-alt fa-lg mr-2"></i>Movimientos</a></li>
    <li class="nav-item"><a class="nav-link link menu-item transferencia" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/" style="display:none"><i class="fas fa-boxes fa-lg mr-2"></i>Inventario</a></li>
    <li class="nav-item"><a class="nav-link link menu-item nomina" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>nomina/" style="display:none"><i class="fas fa-money-check-alt fa-lg mr-2"></i>Nomina</a></li>
    <li class="nav-item"><a class="nav-link link menu-item asistencia" href="#" id="marcarAsistencia"><i class="fas fa-user-clock fa-lg mr-2"></i>Asistencia</a></li>
  </ul>

  <!-- Navbar usuario -->
  <ul class="navbar-nav ml-auto mr-0 mr-md-3 my-2 my-md-0 navbar-nav-user">
    <li class="nav-item dropdown mx-1" style="display:none;">
      <a class="nav-link dropdown-toggle position-relative" id="notification-bell" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="far fa-bell"></i><span id="notification-count" class="position-absolute top-0 start-100 translate-middle" style="display:none;"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notification-bell">
        <h6 class="dropdown-header d-flex justify-content-between align-items-center">Notificaciones</h6>
        <a class="dropdown-item d-flex align-items-center" href="<?php echo SERVERURL; ?>DetallesFacturacion/">
          <i class="fas fa-file-invoice mr-2"></i><span class="flex-grow-1 ml-2">Facturas pendientes</span>
          <span id="notification-dropdown-count" class="badge">0</span>
        </a>
      </div>
    </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-user-circle fa-lg mr-2"></i><span id="user_session" class="mr-1"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right user-dropdown" aria-labelledby="userDropdown">
        <a class="dropdown-item" href="#" id="cambiar_contraseña_usuarios_sistema"><i class="fas fa-key"></i> Modificar Contraseña</a>
        <a class="dropdown-item" href="#" id="modificar_perfil_usuario_sistema"><i class="fas fa-id-card"></i> Mi Perfil<span id="badge-codigo-cliente" class="badge bg-primary ml-2"></span></a>
        <a class="dropdown-item" href="#" id="ver-pin-usuario" data-toggle="popover"><i class="fas fa-lock"></i> Ver mi PIN<span id="badge-pin-cliente" class="badge bg-info ml-2"></span></a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item d-flex align-items-center" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>DetallesFacturacion/"><i class="fas fa-file-invoice"></i><span class="flex-grow-1 ml-2">Detalles de Facturación</span><span id="badge-facturas-pendientes-dropdown" class="badge bg-danger" style="display:none;">0</span></a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item btn-exit-system" href="<?php echo $lc->encryption($_SESSION['token_sd']);?>"><i class="fas fa-sign-out-alt"></i> Salir</a>
      </div>
    </li>
  </ul>
</nav>
<style>
/* La página raíz conserva Fullscreen mientras los módulos navegan en el iframe. */
html.izzy-fs-shell-root, html.izzy-fs-shell-root body {overflow: hidden !important;}
body.izzy-fs-shell > :not(#izzyFullscreenFrame) {display: none !important;}
#izzyFullscreenFrame {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    display: block !important;
    border: 0 !important;
    background: #f5f8fc;
    z-index: 2147483647 !important;
}
</style>
<script>
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
            // Navegar a un módulo distinto requiere cargarlo en el documento raíz.
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
                // No volver a cargar la página actual al expandir.
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

    // La navegación normal dentro de IZZY se mantiene en la ventana contenedora.
    // Al hacer clic en un enlace de otro módulo, se carga solo el destino y
    // nunca se recarga primero el módulo desde el que se expandió.
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
</script>
