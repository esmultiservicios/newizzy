<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/contrato.css">

<div class="container-fluid">
    <!-- Contrato -->
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
                <i class="fas fa-file-signature breadcrumb-icon"></i>
                <span>Contrato</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card contrato-section-card mb-4" id="contratoFiltrosSection">
        <div class="contrato-section-header">
            <div class="contrato-section-title">
                <span class="contrato-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Contratos</h5>
                    <small>Consulte contratos por estado, tipo, pago planificado y tipo de empleado.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary contrato-toggle-btn"
                    id="btnToggleFiltrosContrato" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="contratoFiltrosContenido">
            <form id="form_main_contrato">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="contrato-filter-label" for="estado">
                            <i class="fas fa-toggle-on mr-1"></i> Estado
                        </label>
                        <select id="estado" name="estado"
                                class="form-control selectpicker" title="Estado"
                                data-live-search="true" data-width="100%">
                            <option value="1">Activo</option>
                            <option value="2">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="contrato-filter-label" for="tipo_contrato">
                            <i class="fas fa-file-signature mr-1"></i> Tipo Contrato
                        </label>
                        <select id="tipo_contrato" name="tipo_contrato"
                                class="form-control selectpicker" title="Tipo de Contrato"
                                data-live-search="true" data-width="100%">
                            <option value="">Seleccione</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="contrato-filter-label" for="pago_planificado">
                            <i class="fas fa-calendar-check mr-1"></i> Pago Planificado
                        </label>
                        <select id="pago_planificado" name="pago_planificado"
                                class="form-control selectpicker" title="Pago Planificado"
                                data-live-search="true" data-width="100%">
                            <option value="">Seleccione</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="contrato-filter-label" for="tipo_empleado">
                            <i class="fas fa-user-tag mr-1"></i> Tipo Empleado
                        </label>
                        <select id="tipo_empleado" name="tipo_empleado"
                                class="form-control selectpicker" title="Tipo Empleado"
                                data-live-search="true" data-width="100%">
                            <option value="">Seleccione</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="contrato-filter-actions">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card contrato-section-card mb-4" id="contratoKpisSection">
        <div class="contrato-section-header">
            <div class="contrato-section-title">
                <span class="contrato-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Contratos</h5>
                    <small>Indicadores calculados sobre el resultado filtrado.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary contrato-toggle-btn"
                    id="btnToggleKpisContrato" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="contratoKpisContenido">
            <div class="row contrato-kpi-row">
                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="contrato-kpi contrato-kpi-primary">
                        <div class="contrato-kpi-copy">
                            <span>Contratos</span>
                            <strong id="contratoKpiTotal">0</strong>
                            <small>Registros filtrados</small>
                        </div>
                        <span class="contrato-kpi-icon"><i class="fas fa-file-contract"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="contrato-kpi contrato-kpi-success">
                        <div class="contrato-kpi-copy">
                            <span>Activos</span>
                            <strong id="contratoKpiActivos">0</strong>
                            <small>Contratos vigentes</small>
                        </div>
                        <span class="contrato-kpi-icon"><i class="fas fa-check-circle"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="contrato-kpi contrato-kpi-warning">
                        <div class="contrato-kpi-copy">
                            <span>Inactivos</span>
                            <strong id="contratoKpiInactivos">0</strong>
                            <small>Contratos no vigentes</small>
                        </div>
                        <span class="contrato-kpi-icon"><i class="fas fa-clock"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="contrato-kpi contrato-kpi-purple">
                        <div class="contrato-kpi-copy">
                            <span>Salario Total</span>
                            <strong id="contratoKpiSalario">L 0.00</strong>
                            <small>Total filtrado</small>
                        </div>
                        <span class="contrato-kpi-icon"><i class="fas fa-coins"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card contrato-section-card mb-4" id="contratoListadoSection">
        <div class="contrato-directory-header">
            <div class="contrato-section-title">
                <span class="contrato-section-icon"><i class="fas fa-file-signature"></i></span>
                <div>
                    <h5 class="mb-0">Contratos</h5>
                    <small>Empleado, tipo, pago planificado, salario, vigencia, notas y estado.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="contrato-list-toolbar">
                <div class="contrato-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnContratoActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnContratoIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnContratoExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnContratoPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="contrato-toolbar-right">
                    <label class="contrato-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="contratoPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="contrato-view-switch">
                        <button type="button" class="contrato-view-btn active" data-view="detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="contrato-view-btn" data-view="miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="contrato-search">
                        <span class="contrato-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarContrato" class="form-control"
                               placeholder="Buscar contrato..." autocomplete="off">
                        <button type="button" id="limpiarBuscarContrato" class="contrato-search-clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="contratoListado" class="contrato-listado vista-detalle"></div>

            <div class="contrato-total-strip">
                <span>Salario total filtrado</span>
                <strong id="contratoTotalListado">L 0.00</strong>
            </div>

            <div class="contrato-list-footer">
                <span id="contratoInfo">0 registros</span>
                <div id="contratoPaginacion" class="contrato-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "contrato";

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
    <?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "contrato";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Contratos");
?>