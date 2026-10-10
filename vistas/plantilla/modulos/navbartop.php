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

/* ============================================================
   IZZY | TOPBAR RESPONSIVE FINAL
   Orden móvil/tablet:
   Logo -> Menú principal -> Sidebar -> Fullscreen -> Usuario
   ============================================================ */
.sb-topnav {
    display: flex;
    align-items: center;
    flex-wrap: nowrap !important;
    width: 100%;
    min-width: 0;
    overflow: visible;
}

.sb-topnav .logo-container {
    flex: 0 0 auto;
    min-width: 0;
}

.sb-topnav .mobile-mainmenu-wrapper {
    flex: 0 0 auto !important;
    min-width: 0;
    margin: 0 4px;
}

.sb-topnav #mobile-mainmenu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 34px;
    min-height: 34px;
    border-radius: 9px;
    white-space: nowrap;
}

.sb-topnav #mobile-mainmenu-btn .izzy-mainmenu-text {
    display: inline;
}

.sb-topnav #izzy-persistent-fullscreen {
    flex: 0 0 38px !important;
    width: 38px;
    min-width: 38px !important;
    height: 34px !important;
    margin-left: 4px !important;
    padding: 0 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.sb-topnav .navbar-nav-user {
    flex: 0 1 auto;
    min-width: 0;
    margin-left: auto !important;
    margin-right: 8px !important;
}

.sb-topnav .navbar-nav-user .nav-item,
.sb-topnav .navbar-nav-user #userDropdown {
    min-width: 0;
}

.sb-topnav .navbar-nav-user #userDropdown {
    max-width: 220px;
    overflow: hidden;
    white-space: nowrap;
}

.sb-topnav .navbar-nav-user #user_session {
    min-width: 0;
    max-width: 165px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
    vertical-align: middle;
}

/* Tablet */
@media (min-width: 577px) and (max-width: 991.98px) {
    .sb-topnav {
        padding-left: 10px;
        padding-right: 10px;
        gap: 4px;
    }

    .sb-topnav .logo-container {
        margin-right: 4px;
    }

    .sb-topnav .logo-container .logo {
        max-width: 132px;
        height: auto;
    }

    .sb-topnav #mobile-mainmenu-btn {
        padding: 0 12px !important;
    }

    .sb-topnav #mobile-mainmenu-btn i {
        margin-right: 7px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        max-width: 190px;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width: 130px;
    }
}

/* Móvil */
@media (max-width: 576px) {
    .sb-topnav {
        padding: 0 8px !important;
        gap: 4px;
        min-height: 58px;
    }

    .sb-topnav .logo-container {
        margin: 0 2px 0 0 !important;
        padding: 0 !important;
        flex: 0 0 auto !important;
    }

    .sb-topnav .logo-container a {
        display: flex;
        align-items: center;
    }

    .sb-topnav .logo-container .logo {
        width: auto !important;
        max-width: 118px !important;
        height: 38px !important;
        object-fit: contain;
    }

    /* En móvil "Menú principal" queda como icono compacto */
    .sb-topnav .mobile-mainmenu-wrapper {
        margin: 0 !important;
        order: 1;
    }

    .sb-topnav #mobile-mainmenu-btn {
        width: 38px;
        min-width: 38px;
        max-width: 38px;
        height: 34px;
        padding: 0 !important;
        border-radius: 9px;
    }

    .sb-topnav #mobile-mainmenu-btn i {
        margin: 0 !important;
        font-size: 17px;
    }

    .sb-topnav #mobile-mainmenu-btn .izzy-mainmenu-text {
        display: none !important;
    }

    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle {
        order: 2;
        flex: 0 0 38px;
        width: 38px;
        min-width: 38px;
        height: 34px;
        margin: 0 !important;
    }

    .sb-topnav #izzy-persistent-fullscreen {
        order: 3;
        flex: 0 0 38px !important;
        width: 38px !important;
        min-width: 38px !important;
        height: 34px !important;
        margin: 0 !important;
    }

    .sb-topnav .navbar-nav-user {
        order: 4;
        flex: 1 1 auto;
        min-width: 0;
        margin: 0 0 0 2px !important;
        overflow: visible;
    }

    .sb-topnav .navbar-nav-user > .nav-item.dropdown:last-child {
        width: 100%;
        min-width: 0;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        padding: 7px 4px !important;
        overflow: hidden;
    }

    .sb-topnav .navbar-nav-user #userDropdown > i {
        flex: 0 0 auto;
        margin-right: 5px !important;
        font-size: 16px;
    }

    .sb-topnav .navbar-nav-user #user_session {
        display: block;
        flex: 1 1 auto;
        min-width: 0;
        max-width: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        line-height: 1.2;
    }

    .sb-topnav .navbar-nav-user #userDropdown::after {
        flex: 0 0 auto;
        margin-left: 4px;
    }

    .sb-topnav .user-dropdown {
        right: 0 !important;
        left: auto !important;
        max-width: calc(100vw - 16px);
    }
}

/* Teléfonos angostos */
@media (max-width: 430px) {
    .sb-topnav {
        padding-left: 6px !important;
        padding-right: 6px !important;
        gap: 3px;
    }

    .sb-topnav .logo-container .logo {
        max-width: 104px !important;
        height: 34px !important;
    }

    .sb-topnav #mobile-mainmenu-btn,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        width: 34px !important;
        min-width: 34px !important;
        flex-basis: 34px !important;
        height: 32px !important;
    }

    .sb-topnav #mobile-mainmenu-btn i,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle i,
    .sb-topnav #izzy-persistent-fullscreen i {
        font-size: 15px;
    }

    .sb-topnav .navbar-nav-user #user_session {
        font-size: 11px;
    }
}

/* Equipos extremadamente angostos: se preserva todo dentro del viewport. */
@media (max-width: 360px) {
    .sb-topnav .logo-container .logo {
        max-width: 90px !important;
    }

    .sb-topnav #mobile-mainmenu-btn,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        width: 32px !important;
        min-width: 32px !important;
        flex-basis: 32px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        padding-left: 2px !important;
        padding-right: 2px !important;
    }
}


/* ============================================================
   IZZY | ORDEN RESPONSIVE ESTRICTO
   Bootstrap define .order-* con !important; por eso el orden
   se fuerza explícitamente en móvil/tablet.
   ============================================================ */
@media (max-width: 991.98px) {
    .sb-topnav .logo-container {
        order: 0 !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper {
        order: 1 !important;
    }

    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle {
        order: 2 !important;
    }

    .sb-topnav #izzy-persistent-fullscreen {
        order: 3 !important;
    }

    .sb-topnav .navbar-nav-user {
        order: 4 !important;
    }
}

/* Móvil: todo en una sola fila, sin salir del viewport. */
@media (max-width: 576px) {
    .sb-topnav {
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100vw !important;
        flex-wrap: nowrap !important;
        overflow: visible !important;
    }

    .sb-topnav .logo-container {
        flex: 0 0 auto !important;
        min-width: 0 !important;
        max-width: 108px !important;
    }

    .sb-topnav .logo-container .logo {
        width: 104px !important;
        max-width: 104px !important;
        height: 34px !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        flex: 0 0 34px !important;
        width: 34px !important;
        min-width: 34px !important;
        max-width: 34px !important;
        height: 34px !important;
        margin: 0 !important;
    }

    .sb-topnav #mobile-mainmenu-btn {
        width: 34px !important;
        min-width: 34px !important;
        max-width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border-radius: 9px !important;
    }

    .sb-topnav #mobile-mainmenu-btn i.fa-th-large {
        margin: 0 !important;
        font-size: 15px !important;
    }

    .sb-topnav .navbar-nav-user {
        flex: 1 1 0 !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        overflow: visible !important;
        margin-left: 2px !important;
        margin-right: 0 !important;
    }

    .sb-topnav .navbar-nav-user > .nav-item.dropdown:last-child {
        width: 100% !important;
        min-width: 0 !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        display: flex !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        padding: 6px 2px !important;
        overflow: hidden !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown > i {
        flex: 0 0 auto !important;
        margin-right: 4px !important;
        font-size: 15px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        display: block !important;
        flex: 1 1 auto !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown::after {
        flex: 0 0 auto !important;
        margin-left: 2px !important;
    }
}

/* Teléfonos pequeños: conservar nombre visible antes de reducirlo a ... */
@media (max-width: 390px) {
    .sb-topnav {
        padding-left: 4px !important;
        padding-right: 4px !important;
        gap: 2px !important;
    }

    .sb-topnav .logo-container {
        max-width: 92px !important;
    }

    .sb-topnav .logo-container .logo {
        width: 90px !important;
        max-width: 90px !important;
        height: 32px !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen,
    .sb-topnav #mobile-mainmenu-btn {
        flex-basis: 32px !important;
        width: 32px !important;
        min-width: 32px !important;
        max-width: 32px !important;
        height: 32px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        font-size: 10.5px !important;
    }
}


/* ============================================================
   IZZY | TOPBAR MÓVIL PREMIUM - AJUSTE FINAL
   - Usuario siempre legible
   - Iconos uniformes
   - Sin iconos blancos en controles claros
   - Sin desbordamientos
   ============================================================ */
@media (max-width: 991.98px) {
    .sb-topnav {
        background:#ffffff !important;
        border-bottom:1px solid #e6edf5 !important;
        box-shadow:0 4px 14px rgba(11,31,82,.08) !important;
    }

    /* Menú principal: claro, distinto al sidebar */
    .sb-topnav #mobile-mainmenu-btn {
        background:#f7f9fc !important;
        border:1px solid #d9e2ec !important;
        color:#0b1f52 !important;
        box-shadow:0 2px 7px rgba(11,31,82,.08) !important;
    }
    .sb-topnav #mobile-mainmenu-btn i {
        color:#0b1f52 !important;
        -webkit-text-fill-color:#0b1f52 !important;
        opacity:1 !important;
    }

    /* Sidebar: navy */
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle {
        background:#0b1f52 !important;
        border-color:#0b1f52 !important;
        box-shadow:0 2px 7px rgba(11,31,82,.18) !important;
    }
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle i {
        color:#ffffff !important;
        -webkit-text-fill-color:#ffffff !important;
    }

    /* Fullscreen: azul */
    .sb-topnav #izzy-persistent-fullscreen {
        background:#1098ea !important;
        border-color:#1098ea !important;
        color:#ffffff !important;
        box-shadow:0 2px 7px rgba(16,152,234,.22) !important;
    }

    /* Usuario: bloque premium y legible, alineado al extremo derecho */
    .sb-topnav .navbar-nav-user {
        display:flex !important;
        align-items:center !important;
        justify-content:flex-end !important;
        flex:1 1 auto !important;
        min-width:0 !important;
        max-width:none !important;
        margin-left:auto !important;
        overflow:visible !important;
    }

    .sb-topnav .navbar-nav-user > .nav-item.dropdown:last-child {
        display:flex !important;
        align-items:center !important;
        justify-content:flex-end !important;
        flex:0 1 auto !important;
        width:auto !important;
        max-width:100% !important;
        min-width:0 !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        display:flex !important;
        align-items:center !important;
        justify-content:flex-start !important;
        gap:6px !important;
        width:auto !important;
        max-width:100% !important;
        min-width:0 !important;
        min-height:34px !important;
        padding:5px 8px !important;
        border:1px solid #d9e2ec !important;
        border-radius:10px !important;
        background:#f7f9fc !important;
        color:#0b1f52 !important;
        box-shadow:0 2px 7px rgba(11,31,82,.07) !important;
        overflow:hidden !important;
        text-decoration:none !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown:hover,
    .sb-topnav .navbar-nav-user #userDropdown:focus {
        background:#eef5fb !important;
        border-color:#bfd7eb !important;
        color:#0b1f52 !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown > i {
        flex:0 0 auto !important;
        margin:0 !important;
        color:#114bc7 !important;
        -webkit-text-fill-color:#114bc7 !important;
        font-size:17px !important;
        line-height:1 !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        display:block !important;
        flex:1 1 auto !important;
        min-width:0 !important;
        max-width:140px !important;
        margin:0 !important;
        overflow:hidden !important;
        text-overflow:ellipsis !important;
        white-space:nowrap !important;
        color:#0b1f52 !important;
        -webkit-text-fill-color:#0b1f52 !important;
        font-size:12px !important;
        font-weight:700 !important;
        line-height:1.2 !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown::after {
        flex:0 0 auto !important;
        margin-left:2px !important;
        border-top-color:#0b1f52 !important;
    }
}

@media (max-width: 576px) {
    .sb-topnav {
        padding:0 7px !important;
        gap:5px !important;
        min-height:60px !important;
    }

    .sb-topnav .logo-container {
        flex:0 0 auto !important;
        width:auto !important;
        max-width:96px !important;
        margin:0 !important;
        padding:0 !important;
    }

    .sb-topnav .logo-container .logo {
        width:92px !important;
        max-width:92px !important;
        height:34px !important;
        object-fit:contain !important;
    }

    /* Tres controles exactamente uniformes */
    .sb-topnav .mobile-mainmenu-wrapper,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        flex:0 0 36px !important;
        width:36px !important;
        min-width:36px !important;
        max-width:36px !important;
        height:36px !important;
        margin:0 !important;
    }

    .sb-topnav #mobile-mainmenu-btn {
        display:inline-flex !important;
        align-items:center !important;
        justify-content:center !important;
        width:36px !important;
        min-width:36px !important;
        max-width:36px !important;
        height:36px !important;
        padding:0 !important;
        border-radius:10px !important;
    }

    .sb-topnav #mobile-mainmenu-btn i,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle i,
    .sb-topnav #izzy-persistent-fullscreen i {
        font-size:16px !important;
        line-height:1 !important;
    }

    /* Usuario ocupa solo el espacio restante y siempre queda a la derecha */
    .sb-topnav .navbar-nav-user {
        flex:1 1 0 !important;
        min-width:0 !important;
        width:auto !important;
        margin:0 0 0 1px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        max-width:150px !important;
        min-width:0 !important;
        height:36px !important;
        min-height:36px !important;
        padding:5px 7px !important;
        border-radius:10px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width:98px !important;
        font-size:11.5px !important;
    }

    .sb-topnav .user-dropdown {
        right:0 !important;
        left:auto !important;
        max-width:calc(100vw - 14px) !important;
    }
}

@media (max-width: 430px) {
    .sb-topnav {
        padding-left:5px !important;
        padding-right:5px !important;
        gap:4px !important;
    }

    .sb-topnav .logo-container {
        max-width:86px !important;
    }

    .sb-topnav .logo-container .logo {
        width:84px !important;
        max-width:84px !important;
        height:32px !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen,
    .sb-topnav #mobile-mainmenu-btn {
        flex-basis:34px !important;
        width:34px !important;
        min-width:34px !important;
        max-width:34px !important;
        height:34px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        height:34px !important;
        min-height:34px !important;
        max-width:132px !important;
        padding-left:6px !important;
        padding-right:6px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown > i {
        font-size:15px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width:82px !important;
        font-size:11px !important;
    }
}

@media (max-width: 360px) {
    .sb-topnav {
        gap:3px !important;
    }

    .sb-topnav .logo-container {
        max-width:76px !important;
    }

    .sb-topnav .logo-container .logo {
        width:74px !important;
        max-width:74px !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper,
    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen,
    .sb-topnav #mobile-mainmenu-btn {
        flex-basis:32px !important;
        width:32px !important;
        min-width:32px !important;
        max-width:32px !important;
        height:32px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        height:32px !important;
        min-height:32px !important;
        max-width:116px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width:68px !important;
        font-size:10.5px !important;
    }
}


/* ============================================================
   IZZY | IDENTIDAD VISUAL DE CONTROLES - AJUSTE PREMIUM
   Menú principal = icono cuadrícula + texto "Menú"
   Sidebar        = hamburguesa
   Fullscreen     = expandir
   Usuario        = avatar + nombre
   ============================================================ */
@media (max-width: 991.98px) {
    .sb-topnav #mobile-mainmenu-btn {
        width:auto !important;
        min-width:72px !important;
        max-width:none !important;
        padding:0 10px !important;
        gap:6px !important;
    }

    .sb-topnav #mobile-mainmenu-btn .izzy-mainmenu-text {
        display:inline-block !important;
        margin:0 !important;
        color:#0b1f52 !important;
        -webkit-text-fill-color:#0b1f52 !important;
        font-size:12px !important;
        font-weight:700 !important;
        line-height:1 !important;
        white-space:nowrap !important;
    }

    .sb-topnav #mobile-mainmenu-btn i.fa-th-large {
        color:#114bc7 !important;
        -webkit-text-fill-color:#114bc7 !important;
        font-size:14px !important;
        margin:0 !important;
    }
}

/* Móvil estándar: todos se distinguen y el usuario sigue visible. */
@media (max-width: 576px) {
    .sb-topnav {
        gap:5px !important;
    }

    .sb-topnav .logo-container {
        max-width:84px !important;
    }

    .sb-topnav .logo-container .logo {
        width:82px !important;
        max-width:82px !important;
        height:32px !important;
    }

    .sb-topnav .mobile-mainmenu-wrapper {
        flex:0 0 auto !important;
        width:auto !important;
        min-width:0 !important;
        max-width:none !important;
        height:34px !important;
    }

    .sb-topnav #mobile-mainmenu-btn {
        width:auto !important;
        min-width:68px !important;
        max-width:76px !important;
        height:34px !important;
        padding:0 9px !important;
        border-radius:10px !important;
    }

    .sb-topnav #mobile-mainmenu-btn .izzy-mainmenu-text {
        display:inline-block !important;
        font-size:11.5px !important;
    }

    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        flex:0 0 34px !important;
        width:34px !important;
        min-width:34px !important;
        max-width:34px !important;
        height:34px !important;
    }

    .sb-topnav .navbar-nav-user {
        flex:1 1 0 !important;
        min-width:0 !important;
        justify-content:flex-end !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        max-width:124px !important;
        min-width:0 !important;
        height:34px !important;
        min-height:34px !important;
        padding:4px 7px !important;
        gap:5px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown > i {
        color:#114bc7 !important;
        -webkit-text-fill-color:#114bc7 !important;
        font-size:16px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        display:block !important;
        max-width:76px !important;
        color:#0b1f52 !important;
        -webkit-text-fill-color:#0b1f52 !important;
        font-size:11px !important;
        font-weight:700 !important;
    }
}

/* Móvil angosto: conserva texto corto "Menú" y nombre truncado. */
@media (max-width: 390px) {
    .sb-topnav {
        gap:3px !important;
        padding-left:4px !important;
        padding-right:4px !important;
    }

    .sb-topnav .logo-container {
        max-width:74px !important;
    }

    .sb-topnav .logo-container .logo {
        width:72px !important;
        max-width:72px !important;
        height:30px !important;
    }

    .sb-topnav #mobile-mainmenu-btn {
        min-width:61px !important;
        max-width:65px !important;
        height:32px !important;
        padding:0 7px !important;
    }

    .sb-topnav #mobile-mainmenu-btn .izzy-mainmenu-text {
        font-size:10.5px !important;
    }

    .sb-topnav #sidebarToggle.izzy-navbar-menu-toggle,
    .sb-topnav #izzy-persistent-fullscreen {
        flex-basis:32px !important;
        width:32px !important;
        min-width:32px !important;
        max-width:32px !important;
        height:32px !important;
    }

    .sb-topnav .navbar-nav-user #userDropdown {
        max-width:105px !important;
        height:32px !important;
        min-height:32px !important;
        padding:4px 5px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width:60px !important;
        font-size:10px !important;
    }
}

/* Muy angosto: si no cabe el nombre completo, sigue visible truncado. */
@media (max-width: 340px) {
    .sb-topnav .navbar-nav-user #userDropdown {
        max-width:88px !important;
    }

    .sb-topnav .navbar-nav-user #user_session {
        max-width:44px !important;
    }
}
</style>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-color-navarlateral">
  <div class="navbar-brand logo-container">
    <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
      <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logos/logo.svg"
           alt="IZZY" class="logo img-fluid">
    </a>
  </div>

  <!-- ===== Botón Menú principal (solo tablets/móviles) ===== -->
  <div class="mobile-mainmenu-wrapper d-lg-none d-flex justify-content-center">
    <div class="dropdown">
      <button id="mobile-mainmenu-btn" class="btn btn-light btn-md" type="button"
              title="Menú principal" aria-label="Abrir menú principal"
              aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-th-large" aria-hidden="true"></i>
        <span class="izzy-mainmenu-text ml-2">Menú</span>
      </button>
      <div id="mobile-mainmenu" class="dropdown-menu dropdown-menu-center shadow"
           aria-labelledby="mobile-mainmenu-btn"></div>
    </div>
  </div>

  <!-- Botón de alternar menú lateral (sidebar) -->
  <button class="btn izzy-navbar-menu-toggle order-1 order-lg-0" id="sidebarToggle"
          type="button" title="Mostrar u ocultar menú lateral"
          aria-label="Mostrar u ocultar menú lateral" aria-controls="layoutSidenav_nav">
    <i class="fas fa-bars" aria-hidden="true" style="color:#fff!important;-webkit-text-fill-color:#fff!important;opacity:1!important;"></i>
  </button>

  <!-- Control de pantalla completa del navbar. Se mantiene al navegar la preferencia. -->
  <button id="izzy-persistent-fullscreen" class="btn btn-primary btn-sm order-1 order-lg-0"
          type="button" title="Activar pantalla completa" aria-label="Activar pantalla completa"
          aria-pressed="false">
    <i class="fas fa-expand" aria-hidden="true"></i>
  </button>

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
</script>

<script>
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
</script>
