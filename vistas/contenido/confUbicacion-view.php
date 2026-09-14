<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confUbicacion.css">

<div class="container-fluid ubicacion-config-page">
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
                <i class="fas fa-map-marker-alt breadcrumb-icon"></i>
                <span>Ubicación</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 ubicacion-config-section-card ubicacion-config-filtro-card" id="ubicacion-config-filtros-section">
        <div class="ubicacion-config-section-header">
            <div class="ubicacion-config-section-title">
                <span class="ubicacion-config-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Ubicación</h5>
                    <small>Consulte los registros por estado sin alterar la información existente.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary ubicacion-config-toggle-btn" id="ubicacion-config-toggle-filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="ubicacion-config-filtros-contenido">
            <form id="form_main_ubicacion" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 ubicacion-config-label-filter" for="estado_ubicacion">Estado</label>
                            <select id="estado_ubicacion" name="estado_ubicacion" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="ubicacion-config-filter-actions w-100">
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
    <section class="card mb-4 ubicacion-config-section-card" id="ubicacion-config-kpis-section">
        <div class="ubicacion-config-section-header">
            <div class="ubicacion-config-section-title">
                <span class="ubicacion-config-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Ubicación</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary ubicacion-config-toggle-btn" id="ubicacion-config-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="ubicacion-config-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ubicacion-config-kpi-card ubicacion-config-kpi-blue">
                        <div>
                            <div class="ubicacion-config-kpi-label">Ubicaciones</div>
                            <h3 id="ubicacion-config-kpi-total">0</h3>
                            <p>Total de ubicaciones encontradas</p>
                        </div>
                        <div class="ubicacion-config-kpi-icon"><i class="fas fa-map-marked-alt"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ubicacion-config-kpi-card ubicacion-config-kpi-green">
                        <div>
                            <div class="ubicacion-config-kpi-label">Activas</div>
                            <h3 id="ubicacion-config-kpi-activos">0</h3>
                            <p>Ubicaciones activas</p>
                        </div>
                        <div class="ubicacion-config-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ubicacion-config-kpi-card ubicacion-config-kpi-orange">
                        <div>
                            <div class="ubicacion-config-kpi-label">Inactivas</div>
                            <h3 id="ubicacion-config-kpi-inactivos">0</h3>
                            <p>Ubicaciones inactivas</p>
                        </div>
                        <div class="ubicacion-config-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ubicacion-config-kpi-card ubicacion-config-kpi-purple">
                        <div>
                            <div class="ubicacion-config-kpi-label">Empresas</div>
                            <h3 id="ubicacion-config-kpi-empresas">0</h3>
                            <p>Empresas presentes en el resultado</p>
                        </div>
                        <div class="ubicacion-config-kpi-icon"><i class="fas fa-building"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 ubicacion-config-section-card ubicacion-config-list-card">
        <div class="ubicacion-config-section-header">
            <div class="ubicacion-config-section-title">
                <span class="ubicacion-config-section-icon"><i class="fas fa-map-marker-alt"></i></span>
                <div>
                    <h5 class="mb-0">Ubicaciones</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="ubicacion-config-toolbar">
                <div class="ubicacion-config-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="ubicacion-config-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="ubicacion-config-btn-create">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="ubicacion-config-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="ubicacion-config-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="ubicacion-config-toolbar-right">
                    <label class="ubicacion-config-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="ubicacion-config-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="ubicacion-config-view-switch">
                        <button type="button" class="ubicacion-config-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="ubicacion-config-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="ubicacion-config-search-wrap">
                        <span class="ubicacion-config-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="ubicacion-config-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="ubicacion-config-search-clear" class="ubicacion-config-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="ubicacion-config-listado" class="ubicacion-config-listado vista-detalle"></div>

            <div class="ubicacion-config-list-footer">
                <span id="ubicacion-config-info">0 registros</span>
                <div id="ubicacion-config-pagination" class="ubicacion-config-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="ubicacion-config-source-table" aria-hidden="true">
                <table id="dataTableConfUbicacion" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "ubicacion";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Ubicación");
?>
