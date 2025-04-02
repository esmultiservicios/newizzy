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

    <!-- Menú rápido -->
    <div class="dropdown d-md-none">
        <button class="btn btn-secondary bg-color-navarlateral dropdown-toggle" type="button" id="dropdownMenuButton"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bars mr-2"></i>Menú Rápido
        </button>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">

            <a class="dropdown-item menu-rapido reporteVentas" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/"
                style="display:none">
                <i class="fas fa-file-invoice-dollar fa-lg mr-2"></i>Reporte Ventas
            </a>
            <a class="dropdown-item menu-rapido reporteCotizacion" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCotizacion/"
                style="display:none">
                <i class="fas fa-file-signature fa-lg mr-2"></i>Reporte Cotización
            </a>
            <a class="dropdown-item menu-rapido reporteCompras" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/"
                style="display:none">
                <i class="fas fa-shopping-cart fa-lg mr-2"></i>Reporte Compras
            </a>
            <a class="dropdown-item menu-rapido cobrarClientes" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cobrarClientes/"
                style="display:none">
                <i class="fas fa-hand-holding-usd fa-lg mr-2"></i>CXC Clientes
            </a>
            <a class="dropdown-item menu-rapido pagarProveedores" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>pagarProveedores/"
                style="display:none">
                <i class="fas fa-file-invoice fa-lg mr-2"></i>CXP Proveedores
            </a>
            <a class="dropdown-item menu-rapido inventario" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>inventario/"
                style="display:none">
                <i class="fas fa-exchange-alt fa-lg mr-2"></i>Movimientos
            </a>
            <a class="dropdown-item menu-rapido transferencia" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/"
                style="display:none">
                <i class="fas fa-boxes fa-lg mr-2"></i>Inventario
            </a>
            <a class="dropdown-item menu-rapido nomina" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>nomina/" style="display:none">
                <i class="fas fa-money-check-alt fa-lg mr-2"></i>Nomina
            </a>
            <a class="dropdown-item menu-rapido asistencia" href="#" id="marcarAsistencia">
                <i class="fas fa-user-clock fa-lg mr-2"></i>Asistencia
            </a>
        </div>
    </div>

    <!-- Navbar usuario -->
    <ul class="navbar-nav ml-auto mr-0 mr-md-3 my-2 my-md-0 navbar-nav-user">
        <li class="nav-item dropdown active">
            <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user fa-lg"></i> <span id="user_session"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" id="cambiar_contraseña_usuarios_sistema">
                    <i class="fas fa-key mr-2"></i>Modificar Contraseña
                </a>
                <a class="dropdown-item" href="#" id="modificar_perfil_usuario_sistema">
                    <i class="fas fa-id-card mr-2"></i>Mi Perfil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item btn-exit-system"
                    href="<?php echo $lc->encryption($_SESSION['token_sd']);?>">
                    <i class="fas fa-sign-out-alt mr-2"></i>Salir
                </a>
            </div>
        </li>
    </ul>
</nav>