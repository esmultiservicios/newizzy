<nav class="sb-topnav navbar navbar-expand navbar-dark bg-color-navarlateral">
    <div class="navbar-brand logo-container">
        <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
            <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logos/logo.svg" 
                alt="IZZY"
                class="logo img-fluid">
        </a>
    </div>

    <!-- Botón de alternar menú para pantallas pequeñas -->
    <button class="btn btn-link btn-sm order-1 order-lg-0" id="sidebarToggle" href="#">
        <i class="fas fa-bars fa-lg"></i>
    </button>

    <!-- Botón de pantalla completa - Ahora con mejor posición -->
    <button id="global-fullscreen-btn" title="Pantalla completa">
        <i class="fas fa-expand"></i>
    </button>

    <!-- Menú principal -->
    <ul class="navbar-nav">
        <!-- Elementos del menú principal -->
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item reporteVentas" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/"
                style="display:none">
                <i class="fas fa-file-invoice-dollar fa-lg mr-2"></i>Reporte Ventas
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item reporteCotizacion" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCotizacion/"
                style="display:none">
                <i class="fas fa-file-signature fa-lg mr-2"></i>Reporte Cotización
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item reporteCompras" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/"
                style="display:none">
                <i class="fas fa-shopping-cart fa-lg mr-2"></i>Reporte Compras
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item cobrarClientes" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cobrarClientes/"
                style="display:none">
                <i class="fas fa-hand-holding-usd fa-lg mr-2"></i>CXC Clientes
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item pagarProveedores" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>pagarProveedores/"
                style="display:none">
                <i class="fas fa-file-invoice fa-lg mr-2"></i>CXP Proveedores
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item inventario" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>inventario/"
                style="display:none">
                <i class="fas fa-exchange-alt fa-lg mr-2"></i>Movimientos
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item transferencia" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/"
                style="display:none">
                <i class="fas fa-boxes fa-lg mr-2"></i>Inventario
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item nomina" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>nomina/" style="display:none">
                <i class="fas fa-money-check-alt fa-lg mr-2"></i>Nomina
            </a>
        </li>
        <li class="nav-item d-none d-md-block d-sm-block">
            <a class="nav-link link menu-item asistencia" href="#" id="marcarAsistencia">
                <i class="fas fa-user-clock fa-lg mr-2"></i>Asistencia
            </a>
        </li>
    </ul>

    <!-- Navbar usuario -->
    <ul class="navbar-nav ml-auto mr-0 mr-md-3 my-2 my-md-0 navbar-nav-user">
        <!-- Campana de notificaciones (se ocultará si no hay notificaciones) -->
        <li class="nav-item dropdown mx-1" style="display: none;">
            <a class="nav-link dropdown-toggle position-relative" id="notification-bell" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="far fa-bell"></i>
                <span id="notification-count" class="position-absolute top-0 start-100 translate-middle" style="display: none;"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notification-bell">
                <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notificaciones</span>
                </h6>
                <a class="dropdown-item d-flex align-items-center" href="<?php echo SERVERURL; ?>MisFacturas/">
                    <i class="fas fa-file-invoice mr-2"></i>
                    <span class="flex-grow-1 ml-2">Facturas pendientes</span>
                    <span id="notification-dropdown-count" class="badge">0</span>
                </a>
                <!-- Puedes agregar más notificaciones aquí -->
            </div>
        </li>

        <!-- Menú de usuario -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle fa-lg mr-2"></i>
                <span id="user_session" class="mr-1"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right user-dropdown" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" id="cambiar_contraseña_usuarios_sistema">
                    <i class="fas fa-key"></i> Modificar Contraseña
                </a>
                <a class="dropdown-item" href="#" id="modificar_perfil_usuario_sistema">
                    <i class="fas fa-id-card"></i> Mi Perfil
                    <span id="badge-codigo-cliente" class="badge bg-primary ml-2"></span>
                </a>
                <!-- Opción para ver PIN con popover -->
                <a class="dropdown-item" href="#" id="ver-pin-usuario" data-toggle="popover">
                    <i class="fas fa-lock"></i> Ver mi PIN
                    <span id="badge-pin-cliente" class="badge bg-info ml-2"></span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item d-flex align-items-center" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>DetallesFacturacion/">
                    <i class="fas fa-file-invoice"></i>
                    <span class="flex-grow-1 ml-2">Detalles de Facturación</span>
                    <span id="badge-facturas-pendientes-dropdown" class="badge bg-danger" style="display: none;">0</span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item btn-exit-system" href="<?php echo $lc->encryption($_SESSION['token_sd']);?>">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </li>
    </ul>
</nav>