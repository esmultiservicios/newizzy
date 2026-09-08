<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/clientes.css">

<div class="container-fluid clientes-page">
    <div class="breadcrumb-harmony-container">
        <ol class="breadcrumb-harmony">
            <li class="breadcrumb-item">
                <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
                    <i class="fas fa-home breadcrumb-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-separator">/</li>
            <li class="breadcrumb-item active">
                <i class="fas fa-user-friends breadcrumb-icon"></i>
                <span>Clientes</span>
            </li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 clientes-section-card">
        <div class="clientes-section-header">
            <div class="clientes-section-title">
                <div class="clientes-section-icon"><i class="fas fa-filter"></i></div>
                <div>
                    <h5>Filtros de clientes</h5>
                    <p>Refine el listado por estado sin afectar la búsqueda principal.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary clientes-toggle-btn" id="btn_toggle_clientes_filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="clientes-section-body" id="clientes_filtros_body">
            <form id="form_main_clientes">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <label class="clientes-filter-label" for="estado_clientes">Estado</label>
                        <select id="estado_clientes" name="estado_clientes" class="form-control selectpicker"
                                title="Estado" data-live-search="true">
                        </select>
                    </div>

                    <div class="col-lg-8 col-md-6 mb-3 clientes-filter-actions">
                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
    <!-- KPI -->
    <div class="card mb-4 clientes-section-card">
        <div class="clientes-section-header">
            <div class="clientes-section-title">
                <div class="clientes-section-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h5>Resumen de clientes</h5>
                    <p>Indicadores calculados sobre los registros filtrados.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary clientes-toggle-btn" id="btn_toggle_clientes_kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="clientes-section-body" id="clientes_kpis_body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="clientes-kpi-card">
                        <div><span>Total</span><h3 id="clientes_kpi_total">0</h3><p>Clientes filtrados</p></div>
                        <div class="clientes-kpi-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="clientes-kpi-card">
                        <div><span>Activos</span><h3 id="clientes_kpi_activos">0</h3><p>Clientes habilitados</p></div>
                        <div class="clientes-kpi-icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="clientes-kpi-card">
                        <div><span>Inactivos</span><h3 id="clientes_kpi_inactivos">0</h3><p>Clientes deshabilitados</p></div>
                        <div class="clientes-kpi-icon"><i class="fas fa-user-times"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="clientes-kpi-card">
                        <div><span>Con sistema</span><h3 id="clientes_kpi_sistema">0</h3><p>Clientes con plataforma</p></div>
                        <div class="clientes-kpi-icon"><i class="fas fa-laptop-code"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado -->
    <div class="card mb-4 clientes-list-card">
        <div class="clientes-section-header">
            <div class="clientes-section-title">
                <div class="clientes-section-icon"><i class="fas fa-user-friends"></i></div>
                <div>
                    <h5>Clientes</h5>
                    <p>Administración y consulta general de clientes registrados.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="clientes-toolbar">
                <div class="clientes-toolbar-actions">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btn_actualizar_clientes">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary crear ocultar" id="btn_nuevo_cliente">
                        <i class="fas fa-plus"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btn_exportar_clientes_excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btn_exportar_clientes_pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>

                <div class="clientes-toolbar-tools">
                    <div class="clientes-page-size">
                        <label for="clientes_page_size">Mostrar</label>
                        <select id="clientes_page_size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </div>

                    <div class="clientes-view-switch">
                        <button type="button" class="clientes-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list-ul"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="clientes-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="clientes-search">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="search" id="clientes_buscar" class="form-control"
                                   placeholder="Buscar cliente..." autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="clientes_loading" class="clientes-state-box d-none">
                <i class="fas fa-spinner fa-spin"></i>
                <div><strong>Cargando clientes...</strong><small>Espere un momento.</small></div>
            </div>

            <div id="clientes_listado" class="clientes-listado vista-detalle"></div>

            <div id="clientes_empty" class="clientes-state-box d-none">
                <i class="fas fa-user-slash"></i>
                <div><strong>No se encontraron clientes</strong><small>Revise los filtros o la búsqueda.</small></div>
            </div>

            <div class="clientes-list-footer">
                <div id="clientes_resultado_info" class="clientes-result-info">Mostrando 0 registros</div>
                <nav id="clientes_paginacion" class="clientes-pagination"></nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "clientes";
				
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

    <?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Clientes");
?>
</div>