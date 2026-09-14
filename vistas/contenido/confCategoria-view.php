<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confCategoria.css">

<div class="container-fluid categoria-productos-page">
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
                <i class="fas fa-layer-group breadcrumb-icon"></i>
                <span>Categoría Productos</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 categoria-productos-section-card categoria-productos-filtro-card" id="categoria-productos-filtros-section">
        <div class="categoria-productos-section-header">
            <div class="categoria-productos-section-title">
                <span class="categoria-productos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Categoría Productos</h5>
                    <small>Consulte los registros por estado sin alterar la información existente.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary categoria-productos-toggle-btn" id="categoria-productos-toggle-filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="categoria-productos-filtros-contenido">
            <form id="form_main_categorias" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 categoria-productos-label-filter" for="estado_categorias">Estado</label>
                            <select id="estado_categorias" name="estado_categorias" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="categoria-productos-filter-actions w-100">
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
    <section class="card mb-4 categoria-productos-section-card" id="categoria-productos-kpis-section">
        <div class="categoria-productos-section-header">
            <div class="categoria-productos-section-title">
                <span class="categoria-productos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Categoría Productos</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary categoria-productos-toggle-btn" id="categoria-productos-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="categoria-productos-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="categoria-productos-kpi-card categoria-productos-kpi-blue">
                        <div>
                            <div class="categoria-productos-kpi-label">Categorías</div>
                            <h3 id="categoria-productos-kpi-total">0</h3>
                            <p>Total de categorías encontradas</p>
                        </div>
                        <div class="categoria-productos-kpi-icon"><i class="fas fa-tags"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="categoria-productos-kpi-card categoria-productos-kpi-green">
                        <div>
                            <div class="categoria-productos-kpi-label">Activas</div>
                            <h3 id="categoria-productos-kpi-activos">0</h3>
                            <p>Categorías activas</p>
                        </div>
                        <div class="categoria-productos-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="categoria-productos-kpi-card categoria-productos-kpi-orange">
                        <div>
                            <div class="categoria-productos-kpi-label">Inactivas</div>
                            <h3 id="categoria-productos-kpi-inactivos">0</h3>
                            <p>Categorías inactivas</p>
                        </div>
                        <div class="categoria-productos-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 categoria-productos-section-card categoria-productos-list-card">
        <div class="categoria-productos-section-header">
            <div class="categoria-productos-section-title">
                <span class="categoria-productos-section-icon"><i class="fas fa-layer-group"></i></span>
                <div>
                    <h5 class="mb-0">Categorías de Productos</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="categoria-productos-toolbar">
                <div class="categoria-productos-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="categoria-productos-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="categoria-productos-btn-create">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="categoria-productos-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="categoria-productos-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="categoria-productos-toolbar-right">
                    <label class="categoria-productos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="categoria-productos-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="categoria-productos-view-switch">
                        <button type="button" class="categoria-productos-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="categoria-productos-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="categoria-productos-search-wrap">
                        <span class="categoria-productos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="categoria-productos-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="categoria-productos-search-clear" class="categoria-productos-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="categoria-productos-listado" class="categoria-productos-listado vista-detalle"></div>

            <div class="categoria-productos-list-footer">
                <span id="categoria-productos-info">0 registros</span>
                <div id="categoria-productos-pagination" class="categoria-productos-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="categoria-productos-source-table" aria-hidden="true">
                <table id="dataTableConfCategorias" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "categoria";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Categoría Productos");
?>
