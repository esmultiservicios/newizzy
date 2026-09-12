<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/nomina.css">

<div id="nomina_principal">
    <div class="container-fluid">
        <!-- Nómina -->
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
                    <i class="fas fa-file-invoice-dollar breadcrumb-icon"></i>
                    <span>Nómina</span>
                </li>
            </ol>
        </div>


        <!-- FILTROS -->
        <section class="card nomina-section-card mb-4" id="nominaFiltrosSection">
            <div class="nomina-section-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-filter"></i></span>
                    <div>
                        <h5 class="mb-0">Filtros de Nómina</h5>
                        <small>Consulte períodos por estado, contrato, pago planificado y fechas.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-primary nomina-toggle-btn" id="btnToggleFiltrosNomina" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>

            <div class="card-body" id="nominaFiltrosContenido">
                <form id="form_main_nominas">
                    <div class="row align-items-end">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label" for="estado_nomina"><i class="fas fa-toggle-on mr-1"></i> Estado</label>
                            <select id="estado_nomina" class="form-control selectpicker" name="estado_nomina" data-live-search="true" title="Estado" data-width="100%">
                                <option value="0">Sin Generar</option>
                                <option value="1">Generada</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label" for="tipo_contrato_nomina"><i class="fas fa-file-contract mr-1"></i> Tipo Contrato</label>
                            <select id="tipo_contrato_nomina" name="tipo_contrato_nomina" class="form-control selectpicker" title="Tipo Contrato" data-live-search="true" data-width="100%">
                                <option value="">Seleccione</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label" for="pago_planificado_nomina"><i class="fas fa-calendar-check mr-1"></i> Pago Planificado</label>
                            <select id="pago_planificado_nomina" name="pago_planificado_nomina" class="form-control selectpicker" data-live-search="true" title="Pago Planificado" data-width="100%">
                                <option value="">Seleccione</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label" for="fechai"><i class="fas fa-calendar-alt mr-1"></i> Fecha Inicio</label>
                            <input type="date" class="form-control" id="fechai" name="fechai" value="<?php
                                $fecha = date("Y-m-d");
                                $año = date("Y", strtotime($fecha));
                                $mes = date("m", strtotime($fecha));
                                $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                echo htmlspecialchars($fecha_inicial, ENT_QUOTES, 'UTF-8');
                            ?>">
                        </div>

                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label" for="fechaf"><i class="fas fa-calendar-check mr-1"></i> Fecha Fin</label>
                            <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-xl-9 col-lg-8 col-md-6 col-12 mb-3">
                            <div class="nomina-filter-actions">
                                <button type="submit" class="btn btn-primary" id="search"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                                <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- KPIs -->
        <section class="card nomina-section-card mb-4" id="nominaKpisSection">
            <div class="nomina-section-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-chart-pie"></i></span>
                    <div>
                        <h5 class="mb-0">Resumen de Nómina</h5>
                        <small>Indicadores calculados sobre los períodos filtrados.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-primary nomina-toggle-btn" id="btnToggleKpisNomina" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>

            <div class="card-body" id="nominaKpisContenido">
                <div class="row nomina-kpi-row">
                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="nomina-kpi nomina-kpi-primary">
                            <div class="nomina-kpi-copy"><span>Nóminas</span><strong id="nominaKpiRegistros">0</strong><small>Períodos filtrados</small></div>
                            <span class="nomina-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="nomina-kpi nomina-kpi-success">
                            <div class="nomina-kpi-copy"><span>Generadas</span><strong id="nominaKpiGeneradas">0</strong><small>Nóminas procesadas</small></div>
                            <span class="nomina-kpi-icon"><i class="fas fa-check-circle"></i></span>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="nomina-kpi nomina-kpi-warning">
                            <div class="nomina-kpi-copy"><span>Pendientes</span><strong id="nominaKpiPendientes">0</strong><small>Sin generar</small></div>
                            <span class="nomina-kpi-icon"><i class="fas fa-clock"></i></span>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="nomina-kpi nomina-kpi-purple">
                            <div class="nomina-kpi-copy"><span>Importe Total</span><strong id="nominaKpiImporte">L 0.00</strong><small>Total filtrado</small></div>
                            <span class="nomina-kpi-icon"><i class="fas fa-coins"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- LISTADO -->
        <section class="card nomina-section-card mb-4" id="nominaListadoSection">
            <div class="nomina-directory-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-money-check-alt"></i></span>
                    <div>
                        <h5 class="mb-0">Nóminas</h5>
                        <small>Períodos, empresas, importes, estado y acciones operativas.</small>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="nomina-list-toolbar">
                    <div class="nomina-toolbar-left">
                        <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnNominaActualizar"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
                        <button type="button" class="btn btn-primary table_crear ocultar" id="btnNominaRegistrar"><i class="fas fa-plus mr-1"></i> Registrar Nómina</button>
                        <button type="button" class="btn btn-primary table_crear ocultar" id="btnNominaVales"><i class="fas fa-ticket-alt mr-1"></i> Registrar Vales</button>
                        <button type="button" class="btn btn-success table_reportes ocultar" id="btnNominaExcel"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                        <button type="button" class="btn btn-danger table_reportes ocultar" id="btnNominaPdf"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                    </div>

                    <div class="nomina-toolbar-right">
                        <label class="nomina-page-size mb-0"><span>Mostrar</span><select id="nominaPageSize" class="form-control form-control-sm"></select><span>registros</span></label>
                        <div class="nomina-view-switch">
                            <button type="button" class="nomina-view-btn active" data-view="detalle"><i class="fas fa-list"></i><span>Detalle</span></button>
                            <button type="button" class="nomina-view-btn" data-view="miniatura"><i class="fas fa-th-large"></i><span>Miniatura</span></button>
                        </div>
                        <div class="nomina-search">
                            <span class="nomina-search-icon"><i class="fas fa-search"></i></span>
                            <input type="search" id="buscarNomina" class="form-control" placeholder="Buscar nómina..." autocomplete="off">
                            <button type="button" id="limpiarBuscarNomina" class="nomina-search-clear"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>

                <div id="nominaListado" class="nomina-listado vista-detalle"></div>

                <div class="nomina-total-strip">
                    <span>Total filtrado</span>
                    <strong id="nominaTotalListado">L 0.00</strong>
                </div>

                <div class="nomina-list-footer">
                    <span id="nominaInfo">0 registros</span>
                    <div id="nominaPaginacion" class="nomina-pagination"></div>
                </div>
            </div>

            <div class="card-footer small text-muted">
                <?php
                    require_once "./core/mainModel.php";
                    $insMainModel = new mainModel();
                    $entidad = "nomina";
                    if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                        $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                        $fecha_registro = $consulta_last_update['fecha_registro'];
                        $hora = date('g:i:s a',strtotime($fecha_registro));
                        echo "Última Actualización ".$insMainModel->getTheDay($fecha_registro, $hora);
                    }else{
                        echo "No se encontraron registros ";
                    }
                ?>
            </div>
        </section>
    </div>
    <?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Nomnas");
?>
</div>
<div id="nomina_detalles" style="display: none;">
    <div class="container-fluid">
        <!-- Breadcrumb: Dashboard / Nómina / Empleados -->
        <div class="breadcrumb-container">
            <ol class="breadcrumb-harmony">
                <!-- Dashboard -->
                <li class="breadcrumb-item">
                <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
                    <i class="fas fa-home breadcrumb-icon"></i>
                    <span>Dashboard</span>
                </a>
                </li>

                <li class="breadcrumb-separator">/</li>

                <!-- Nómina (usa el id para volver a la vista principal) -->
                <li class="breadcrumb-item">
                <a class="breadcrumb-link" id="volver_nomina" href="javascript:void(0);">
                    <i class="fas fa-file-invoice-dollar breadcrumb-icon"></i>
                    <span>Nómina</span>
                </a>
                </li>

                <li class="breadcrumb-separator">/</li>

                <!-- Empleados (activo) -->
                <li class="breadcrumb-item active" id="volver_nomina_empleados">
                <i class="fas fa-users breadcrumb-icon"></i>
                <span>Empleados</span>
                </li>
            </ol>
        </div>


        <section class="card nomina-section-card mb-4" id="nominaDetalleFiltrosSection">
            <div class="nomina-section-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-filter"></i></span>
                    <div><h5 class="mb-0">Filtros de Empleados</h5><small>Filtre empleados del período de nómina seleccionado.</small></div>
                </div>
                <button type="button" class="btn btn-primary nomina-toggle-btn" id="btnToggleFiltrosNominaDetalle" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>
            <div class="card-body" id="nominaDetalleFiltrosContenido">
                <form id="form_main_nominas_detalles">
                    <input type="hidden" class="form-control" id="nomina_id" name="nomina_id">
                    <input type="hidden" id="fecha_inicio" name="fecha_inicio" class="form-control">
                    <input type="hidden" id="fecha_fin" name="fecha_fin" class="form-control">
                    <div class="row align-items-end">
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label"><i class="fas fa-toggle-on mr-1"></i> Estado</label>
                            <select id="estado_nomina_detalles" class="form-control selectpicker" data-live-search="true" data-width="100%">
                                <option value="0">Sin Generar</option>
                                <option value="1">Generada</option>
                            </select>
                        </div>
                        <div class="col-lg-5 col-md-6 col-12 mb-3">
                            <label class="nomina-filter-label"><i class="fas fa-user mr-1"></i> Empleado</label>
                            <select id="detalle_nomina_empleado" name="detalle_nomina_empleado" class="form-control selectpicker" title="Empleado" data-live-search="true" data-width="100%">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-12 mb-3">
                            <div class="nomina-filter-actions">
                                <button type="button" id="btnNominaDetalleFiltrar" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                                <button type="button" id="btnNominaDetalleLimpiar" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="card nomina-section-card mb-4" id="nominaDetalleKpisSection">
            <div class="nomina-section-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-chart-bar"></i></span>
                    <div><h5 class="mb-0">Resumen de Empleados</h5><small>Ingresos, egresos y neto del detalle filtrado.</small></div>
                </div>
                <button type="button" class="btn btn-primary nomina-toggle-btn" id="btnToggleKpisNominaDetalle" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>
            <div class="card-body" id="nominaDetalleKpisContenido">
                <div class="row nomina-kpi-row">
                    <div class="col-xl-3 col-md-6 col-12 mb-3"><div class="nomina-kpi nomina-kpi-primary"><div class="nomina-kpi-copy"><span>Empleados</span><strong id="nominaDetalleKpiRegistros">0</strong><small>Registros filtrados</small></div><span class="nomina-kpi-icon"><i class="fas fa-users"></i></span></div></div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3"><div class="nomina-kpi nomina-kpi-success"><div class="nomina-kpi-copy"><span>Ingresos</span><strong id="nominaDetalleKpiIngresos">L 0.00</strong><small>Total ingresos</small></div><span class="nomina-kpi-icon"><i class="fas fa-plus-circle"></i></span></div></div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3"><div class="nomina-kpi nomina-kpi-danger"><div class="nomina-kpi-copy"><span>Egresos</span><strong id="nominaDetalleKpiEgresos">L 0.00</strong><small>Total egresos</small></div><span class="nomina-kpi-icon"><i class="fas fa-minus-circle"></i></span></div></div>
                    <div class="col-xl-3 col-md-6 col-12 mb-3"><div class="nomina-kpi nomina-kpi-purple"><div class="nomina-kpi-copy"><span>Neto</span><strong id="nominaDetalleKpiNeto">L 0.00</strong><small>Total a pagar</small></div><span class="nomina-kpi-icon"><i class="fas fa-hand-holding-usd"></i></span></div></div>
                </div>
            </div>
        </section>

        <section class="card nomina-section-card mb-4" id="nominaDetalleListadoSection">
            <div class="nomina-directory-header">
                <div class="nomina-section-title">
                    <span class="nomina-section-icon"><i class="fas fa-users"></i></span>
                    <div><h5 class="mb-0">Empleados de Nómina</h5><small>Contrato, empresa, ingresos, egresos, neto, notas y estado.</small></div>
                </div>
            </div>
            <div class="card-body">
                <div class="nomina-list-toolbar">
                    <div class="nomina-toolbar-left">
                        <button type="button" id="btnNominaDetalleActualizar" class="btn btn-secondary table_actualizar ocultar"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
                        <button type="button" id="btnNominaDetalleAgregar" class="btn btn-primary table_crear ocultar"><i class="fas fa-plus mr-1"></i> Agregar</button>
                        <button type="button" id="btnNominaDetalleExcel" class="btn btn-success table_reportes ocultar"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                        <button type="button" id="btnNominaDetallePdf" class="btn btn-danger table_reportes ocultar"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                    </div>
                    <div class="nomina-toolbar-right">
                        <label class="nomina-page-size mb-0"><span>Mostrar</span><select id="nominaDetallePageSize" class="form-control form-control-sm"></select><span>registros</span></label>
                        <div class="nomina-view-switch">
                            <button type="button" class="nomina-detalle-view-btn active" data-view="detalle"><i class="fas fa-list"></i><span>Detalle</span></button>
                            <button type="button" class="nomina-detalle-view-btn" data-view="miniatura"><i class="fas fa-th-large"></i><span>Miniatura</span></button>
                        </div>
                        <div class="nomina-search">
                            <span class="nomina-search-icon"><i class="fas fa-search"></i></span>
                            <input type="search" id="buscarNominaDetalle" class="form-control" placeholder="Buscar empleado..." autocomplete="off">
                            <button type="button" id="limpiarBuscarNominaDetalle" class="nomina-search-clear"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>

                <div id="nominaDetalleListado" class="nomina-listado vista-detalle"></div>
                <div class="nomina-total-strip nomina-total-strip-3">
                    <div><span>Ingresos</span><strong id="nominaDetalleTotalIngresos">L 0.00</strong></div>
                    <div><span>Egresos</span><strong id="nominaDetalleTotalEgresos">L 0.00</strong></div>
                    <div><span>Neto</span><strong id="nominaDetalleTotalNeto">L 0.00</strong></div>
                </div>
                <div class="nomina-list-footer">
                    <span id="nominaDetalleInfo">0 registros</span>
                    <div id="nominaDetallePaginacion" class="nomina-pagination"></div>
                </div>
            </div>
            <div class="card-footer small text-muted">
                <?php
                    require_once "./core/mainModel.php";
                    $insMainModel = new mainModel();
                    $entidad = "nomina_detalles";
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
        </section>
    </div>
    <?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Nomina de Empleados");
?>

</div>