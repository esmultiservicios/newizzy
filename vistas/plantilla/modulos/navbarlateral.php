<nav class="sb-sidenav accordion bg-color-navarlateral" id="sidenavAccordion">
<!--sb-sidenav-menu-heading-->
<!--     <div class="custom-header">

    </div> -->
   <br/>
    <div class="sb-sidenav-menu">
        <div class="nav">
            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/" id="dashboard" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt fa-lg"></i></div>
                Dashboard
            </a>
            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#collapseVentas"
                aria-expanded="false" aria-controls="collapseVentas" id="ventas" style="display:none">
                <div class="sb-nav-link-icon"><i class="fab fa-sellsy fa-lg"></i></div>
                Ventas
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseVentas" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>clientes/" id="clientes"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user fa-lg"></i></div>Clientes
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>facturas/" id="facturas"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-file-invoice fa-lg"></i></div>Facturas
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cotizacion/" id="cotizacion"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar fa-lg"></i></div>Cotización
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cajas/" id="cajas" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-cash-register fa-lg"></i></div>Cajas
                    </a>
                </nav>
            </div>
            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#collapseCompras"
                aria-expanded="false" aria-controls="collapseCompras" id="compras" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-store-alt fa-lg"></i></div>
                Compras
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down fa-lg"></i></div>
            </a>
            <div class="collapse" id="collapseCompras" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>proveedores/" id="proveedores"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-tie fa-lg"></i></div>Proveedores
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>facturaCompras/" id="facturaCompras"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-store fa-lg"></i></div>Compras
                    </a>
                </nav>
            </div>
            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#collapseAlmacen"
                aria-expanded="false" aria-controls="collapseAlmacen" id="almacen" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-warehouse fa-lg"></i></div>
                Almacen
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseAlmacen" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>productos/" id="productos"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fab fa-product-hunt fa-lg"></i></div>Productos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>inventario/" id="inventario"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt fa-lg"></i></div>Movimientos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/" id="transferencia"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-boxes fa-lg"></i></div>Inventario
                    </a>
                </nav>
            </div>

            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#collapseContabilidad"
                aria-expanded="false" aria-controls="collapseVentas" id="contabilidad" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-calculator fa-lg"></i></div>
                Contabilidad
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseContabilidad" aria-labelledby="headingOne"
                data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cuentasContabilidad/"
                        id="cuentasContabilidad" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-receipt fa-lg"></i></div>Cuentas
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>movimientosContabilidad/"
                        id="movimientosContabilidad" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt fa-lg"></i></div>Movimientos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ingresosContabilidad/"
                        id="ingresosContabilidad" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-hand-holding-usd fa-lg"></i></div>Ingresos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>gastosContabilidad/" id="gastosContabilidad"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar fa-lg"></i></div>Gastos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>chequesContabilidad/"
                        id="chequesContabilidad" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-money-check fa-lg"></i></div>Cheques
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confCtaContabilidad/"
                        id="confCtaContabilidad" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-tasks fa-lg"></i></div>Conf Cuentas
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confTipoPago/" id="confTipoPago"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fab fa-bitcoin fa-lg"></i></div>Tipo de Pago
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confBancos/" id="confBancos"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-university fa-lg"></i></div>Bancos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confImpuestos/" id="confImpuestos"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-percentage fa-lg"></i></div>Impuestos
                    </a>
                </nav>
            </div>

            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#collapseReportes"
                aria-expanded="false" aria-controls="collapseReportes" id="reportes" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-pie fa-lg"></i></div>
                Reportes
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseReportes" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                    </a>
                    <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#historialCollapse"
                        aria-expanded="false" aria-controls="historialCollapse" id="reporte_historial"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                        Historial
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down fa-lg"></i></div>
                    </a>
                    <div class="collapse" id="historialCollapse" aria-labelledby="headingOne"
                        data-parent="#sidenavAccordionPages">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>historialAccesos/"
                                id="historialAccesos" style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-history fa-lg"></i></div>Accesos
                            </a>
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>bitacora/" id="bitacora"
                                style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-history fa-lg"></i></div>Bitacora
                            </a>
                        </nav>
                    </div>
                    <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#facturasCollapse"
                        aria-expanded="false" aria-controls="facturasCollapse" id="reporte_ventas" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fab fa-sellsy fa-lg"></i></div>
                        Ventas
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="facturasCollapse" aria-labelledby="headingOne"
                        data-parent="#sidenavAccordionPages">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/" id="reporteVentas"
                                style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar fa-lg"></i></div>Ventas
                            </a>
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCotizacion/"
                                id="reporteCotizacion" style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-file-signature fa-lg"></i></div>Cotización
                            </a>
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cobrarClientes/" id="cobrarClientes"
                                style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-hand-holding-usd fa-lg"></i></div>CXC Clientes
                            </a>
                        </nav>
                    </div>
                    <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#comprasCollapse"
                        aria-expanded="false" aria-controls="comprasCollapse" id="reporte_compras" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-store-alt fa-lg"></i></i></div>
                        Compras
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="collapse" id="comprasCollapse" aria-labelledby="headingOne"
                        data-parent="#sidenavAccordionPages">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/" id="reporteCompras"
                                style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart fa-lg"></i></div>Compras
                            </a>
                            <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>pagarProveedores/"
                                id="pagarProveedores" style="display:none">
                                <div class="sb-nav-link-icon"><i class="fas fa-file-invoice fa-lg"></i></div>CXP Proveedores
                            </a>
                        </nav>
                    </div>
                </nav>
            </div>

            <!--Area de Recursos Humanos-->
            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#recursosHumanos"
                aria-expanded="false" aria-controls="recursosHumanos" id="recursosHumanos" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-users fa-lg"></i></div>
                Recursos Humanos
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down fa-lg"></i></div>
            </a>
            <div class="collapse" id="recursosHumanos" aria-labelledby="headingOne fa-lg" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>colaboradores/" id="colaboradores"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-plus fa-lg"></i></div>Colaboradores
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>contrato/" id="contrato"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-file-signature fa-lg"></i></div>Contrato
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>nomina/" id="nomina" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-money-check-alt fa-lg"></i></div>Nomina
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>asistencia/" id="asistencia"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-clock fa-lg"></i></div>Asistencia
                    </a>
                </nav>
            </div>

            <a class="nav-link collapsed link" href="#" data-toggle="collapse" data-target="#configuracion"
                aria-expanded="false" aria-controls="configuracion" id="configuracion" style="display:none">
                <div class="sb-nav-link-icon"><i class="fas fa-cogs fa-lg"></i></div>
                Configuración
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down fa-lg"></i></div>
            </a>
            <div class="collapse" id="configuracion" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>programaPuntos/" id="programaPuntos" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fa-solid fa-money-bill-trend-up fa-lg"></i></div>Programa Puntos
                    </a>                    
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>puestos/" id="puestos" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-briefcase fa-lg"></i></div>Puestos
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>users/" id="users" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-cog fa-lg"></i></div>Usuarios
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>secuencia/" id="secuencia"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-sliders-h fa-lg"></i></div>Secuencia
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>empresa/" id="empresa" style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-building fa-lg"></i></div>Empresa
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confAlmacen/" id="confAlmacen"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fa-solid fa-warehouse fa-lg"></i></div>Almacén
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confImpresora/" id="confImpresora"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fa-solid fa-print fa-lg"></i></div>Impresora
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confUbicacion/" id="confUbicacion"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-search-location fa-lg"></i></div>Ubicación
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confCategoria/" id="confCategoria"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fas fa-layer-group fa-lg"></i></div>Categoría
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confMedida/" id="confMedida"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-balance-scale-left fa-lg"></i></div>Medidas
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confPlanes/" id="confPlanes"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-globe fa-lg"></i></div>Planes
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confHost/" id="confHost"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-network-wired fa-lg"></i></div>Hosts
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confHostProductos/" id="confHostProductos"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-network-wired fa-lg"></i></div>Productos Hosts
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>confEmail/" id="confEmail"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-envelope fa-lg"></i></div>Correo
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>privilegio/" id="privilegio"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-key fa-lg"></i></div>Privilegios
                    </a>
                    <a class="nav-link link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>tipoUser/" id="tipoUser"
                        style="display:none">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-lock fa-lg"></i></div>Permisos
                    </a>
                </nav>
            </div>
            <br />
            <br />
            <br />
            <br />
        </div>
    </div>
    <div class="sb-sidenav-footer link">
        <?php 
            $prefixes = array("clinicarehn_", "clientes_");
            $nombre_db_final = str_replace($prefixes, "", $GLOBALS['db']);            
            
            echo "<center><span class='small-font'>".$nombre_db_final.'</span></center>'; 
        ?>
    </div>

    <a href="https://api.whatsapp.com/send?phone=50489136844&text=Hola%20ES%20MULTISERVICIOS,%20nos%20gustar%C3%ADa%20que%20nos%20puedan%20brindar%20asistencia%20t%C3%A9cnica,%20muchas%20gracias."
        class="float-ws" target="_blank" data-toggle="tooltip" data-placement="top" title="Soporte ES MULTISERVICIOS">
        <i class="fab fa-whatsapp my-float-ws"></i>
    </a>
</nav>