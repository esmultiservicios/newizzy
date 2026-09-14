<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confMedida.css">

<div class="container-fluid medidas-config-page">
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
                <i class="fas fa-ruler-combined breadcrumb-icon"></i>
                <span>Medidas</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 medidas-config-section-card medidas-config-filtro-card" id="medidas-config-filtros-section">
        <div class="medidas-config-section-header">
            <div class="medidas-config-section-title">
                <span class="medidas-config-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Medidas</h5>
                    <small>Consulte los registros por estado sin alterar la información existente.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary medidas-config-toggle-btn" id="medidas-config-toggle-filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="medidas-config-filtros-contenido">
            <form id="form_main_medidas" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 medidas-config-label-filter" for="estado_medidas">Estado</label>
                            <select id="estado_medidas" name="estado_medidas" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="medidas-config-filter-actions w-100">
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
    <section class="card mb-4 medidas-config-section-card" id="medidas-config-kpis-section">
        <div class="medidas-config-section-header">
            <div class="medidas-config-section-title">
                <span class="medidas-config-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Medidas</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary medidas-config-toggle-btn" id="medidas-config-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="medidas-config-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="medidas-config-kpi-card medidas-config-kpi-blue">
                        <div>
                            <div class="medidas-config-kpi-label">Medidas</div>
                            <h3 id="medidas-config-kpi-total">0</h3>
                            <p>Total de medidas encontradas</p>
                        </div>
                        <div class="medidas-config-kpi-icon"><i class="fas fa-ruler-combined"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="medidas-config-kpi-card medidas-config-kpi-green">
                        <div>
                            <div class="medidas-config-kpi-label">Activas</div>
                            <h3 id="medidas-config-kpi-activos">0</h3>
                            <p>Medidas activas</p>
                        </div>
                        <div class="medidas-config-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="medidas-config-kpi-card medidas-config-kpi-orange">
                        <div>
                            <div class="medidas-config-kpi-label">Inactivas</div>
                            <h3 id="medidas-config-kpi-inactivos">0</h3>
                            <p>Medidas inactivas</p>
                        </div>
                        <div class="medidas-config-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 medidas-config-section-card medidas-config-list-card">
        <div class="medidas-config-section-header">
            <div class="medidas-config-section-title">
                <span class="medidas-config-section-icon"><i class="fas fa-ruler-combined"></i></span>
                <div>
                    <h5 class="mb-0">Medidas</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="medidas-config-toolbar">
                <div class="medidas-config-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="medidas-config-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="medidas-config-btn-create">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="medidas-config-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="medidas-config-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="medidas-config-toolbar-right">
                    <label class="medidas-config-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="medidas-config-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="medidas-config-view-switch">
                        <button type="button" class="medidas-config-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="medidas-config-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="medidas-config-search-wrap">
                        <span class="medidas-config-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="medidas-config-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="medidas-config-search-clear" class="medidas-config-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="medidas-config-listado" class="medidas-config-listado vista-detalle"></div>

            <div class="medidas-config-list-footer">
                <span id="medidas-config-info">0 registros</span>
                <div id="medidas-config-pagination" class="medidas-config-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="medidas-config-source-table" aria-hidden="true">
                <table id="dataTableConfMedidas" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "medida";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Medidas");
?>
