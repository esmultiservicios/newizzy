<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/cuentasContabilidad.css">

<div class="container-fluid cuentas-premium-page">
    <!-- Cuentas -->
    <div class="breadcrumb-container">
        <ol class="breadcrumb-harmony">
            <li class="breadcrumb-item">
                <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
                    <i class="fas fa-home breadcrumb-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="breadcrumb-separator">/</li>

            <li class="breadcrumb-item active">
                <i class="fas fa-wallet breadcrumb-icon"></i>
                <span>Cuentas</span>
            </li>
        </ol>
    </div>
    
    <div class="card mb-4 cuentas-filter-card cuentas-section-card">
        <div class="cuentas-section-header">
            <div class="cuentas-section-title">
                <span class="cuentas-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Cuentas</h5>
                    <small>Consulte cuentas por estado, tipo, saldo, orden y período contable.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary cuentas-toggle-btn" id="btnToggleFiltrosCuentas" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>
        <div class="card-body" id="cuentasFiltrosContenido">
            <form id="formMainCuentasContabilidad">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-search mr-1"></i> Buscar cuenta
                            </label>

                            <input type="text" class="form-control" id="buscar_cuenta" name="buscar_cuenta" placeholder="Nombre, código o saldo">
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>

                            <select id="estado_cuentasContabilidad" name="estado_cuentasContabilidad" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-seedling mr-1"></i> Tipo
                            </label>

                            <select id="tipo_cuenta" name="tipo_cuenta" class="form-control selectpicker" title="Tipo" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="normal">Normal</option>
                                <option value="inversion">Inversión</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-balance-scale mr-1"></i> Saldo
                            </label>

                            <select id="tipo_saldo" name="tipo_saldo" class="form-control selectpicker" title="Saldo" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="positivo">Positivo</option>
                                <option value="negativo">Negativo</option>
                                <option value="cero">En cero</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-sort-amount-down mr-1"></i> Ordenar por
                            </label>

                            <select id="orden_cuentas" name="orden_cuentas" class="form-control selectpicker" title="Ordenar" data-live-search="true">
                                <option value="neto_desc" selected>Mayor saldo total</option>
                                <option value="neto_asc">Menor saldo total</option>
                                <option value="nombre_asc">Nombre A-Z</option>
                                <option value="nombre_desc">Nombre Z-A</option>
                                <option value="ingreso_desc">Mayor ingreso</option>
                                <option value="egreso_desc">Mayor egreso</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-alt mr-1"></i> Fecha Inicio
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>

                                <input type="date" class="form-control" id="fechai" name="fechai" value="<?php 
                                    $fecha = date ("Y-m-d");
                                    
                                    $año = date("Y", strtotime($fecha));
                                    $mes = date("m", strtotime($fecha));
                                    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

                                    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));

                                    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                    echo $fecha_inicial;
                                ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-check mr-1"></i> Fecha Fin
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>

                                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-4 col-md-12 col-sm-12 mb-3">
                        <div class="cuentas-toolbar">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter fa-lg"></i> Filtrar
                            </button>

                            <button type="reset" class="btn btn-secondary" id="btnLimpiarCuentas">
                                <i class="fas fa-broom fa-lg"></i> Limpiar
                            </button>  
                        </div>                      
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 cuentas-summary-card cuentas-section-card">
        <div class="cuentas-section-header">
            <div class="cuentas-section-title">
                <span class="cuentas-section-icon"><i class="fas fa-wallet"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Cuentas</h5>
                    <small>Saldos, movimientos del período y saldo actual real.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- KPIs -->
            <section class="cuentas-kpi-section mb-3" id="cuentasKpisSection">
                <div class="cuentas-kpi-header">
                    <div class="cuentas-section-title">
                        <span class="cuentas-section-icon"><i class="fas fa-chart-pie"></i></span>
                        <div>
                            <h5 class="mb-0">Indicadores de Cuentas</h5>
                            <small>Resumen financiero del resultado filtrado.</small>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary cuentas-toggle-btn"
                            id="btnToggleKpisCuentas" aria-expanded="true">
                        <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                    </button>
                </div>

                <div class="cuentas-kpi-content" id="cuentasKpisContenido">
                    <div class="row cuentas-kpi-row">
                        <div class="col-xl-3 col-md-6 col-12 mb-3">
                            <div class="cuentas-kpi cuentas-kpi-primary">
                                <div class="cuentas-kpi-copy">
                                    <span>Cuentas</span>
                                    <strong id="cuentasKpiTotal">0</strong>
                                    <small>Registros filtrados</small>
                                </div>
                                <span class="cuentas-kpi-icon"><i class="fas fa-wallet"></i></span>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 col-12 mb-3">
                            <div class="cuentas-kpi cuentas-kpi-success">
                                <div class="cuentas-kpi-copy">
                                    <span>Ingresos</span>
                                    <strong id="cuentasKpiIngresos">L. 0.00</strong>
                                    <small>Total del período</small>
                                </div>
                                <span class="cuentas-kpi-icon"><i class="fas fa-arrow-down"></i></span>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 col-12 mb-3">
                            <div class="cuentas-kpi cuentas-kpi-danger">
                                <div class="cuentas-kpi-copy">
                                    <span>Egresos</span>
                                    <strong id="cuentasKpiEgresos">L. 0.00</strong>
                                    <small>Total del período</small>
                                </div>
                                <span class="cuentas-kpi-icon"><i class="fas fa-arrow-up"></i></span>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 col-12 mb-3">
                            <div class="cuentas-kpi cuentas-kpi-purple">
                                <div class="cuentas-kpi-copy">
                                    <span>Saldo Total</span>
                                    <strong id="cuentasKpiSaldoActual">L. 0.00</strong>
                                    <small>Saldo actual real</small>
                                </div>
                                <span class="cuentas-kpi-icon"><i class="fas fa-balance-scale"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="cuentas-list-toolbar">
                <div class="cuentas-toolbar-left">
                    <button type="button" class="btn btn-primary table_crear ocultar" onclick="modal_cuentas_contables()">
                        <i class="fas fa-plus mr-1"></i> Nueva Cuenta
                    </button>

                    <button type="button" class="btn btn-secondary table_actualizar ocultar" onclick="listar_cuentas_contabilidad()">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnCuentasExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnCuentasPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="cuentas-toolbar-right">
                    <label class="cuentas-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="cuentasPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="cuentas-view-switch">
                        <button type="button" class="cuentas-view-btn" data-view="detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="cuentas-view-btn active" data-view="miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="cuentas-search-wrap">
                        <span class="cuentas-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarCuentasListado" class="form-control"
                               placeholder="Buscar cuenta..." autocomplete="off">
                        <button type="button" id="limpiarBuscarCuentasListado" class="cuentas-search-clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="cuentas-detalle-container" class="cuentas-detalle-container d-none"></div>
            <div id="cuentas-container" class="row"></div>

            <div class="cuentas-totales-grid" id="cuentasTotales">
                <div class="cuentas-total-card">
                    <span>Saldo Anterior</span>
                    <strong id="cuentasTotalSaldoAnterior">L. 0.00</strong>
                </div>
                <div class="cuentas-total-card cuentas-total-success">
                    <span>Ingresos</span>
                    <strong id="cuentasTotalIngresos">L. 0.00</strong>
                </div>
                <div class="cuentas-total-card cuentas-total-danger">
                    <span>Egresos</span>
                    <strong id="cuentasTotalEgresos">L. 0.00</strong>
                </div>
                <div class="cuentas-total-card">
                    <span>Saldo Cierre</span>
                    <strong id="cuentasTotalSaldoCierre">L. 0.00</strong>
                </div>
                <div class="cuentas-total-card cuentas-total-current">
                    <span>Saldo Total</span>
                    <strong id="cuentasTotalSaldoActual">L. 0.00</strong>
                </div>
            </div>

            <div class="cuentas-list-footer">
                <span id="cuentasInfo">0 registros</span>
                <div id="cuentasPaginacion" class="cuentas-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                
                $insMainModel = new mainModel();
                $entidad = "cuentas";
                
                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros ";
                }				
            ?>
        </div>
    </div>
</div>

<?php
$insMainModel->guardar_historial_accesos("Ingreso al modulo Cuentas Cuentas Contabilidad");
?>