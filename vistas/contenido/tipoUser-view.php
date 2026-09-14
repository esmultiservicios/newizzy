<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/tipoUser.css">

<div class="container-fluid tipo-user-page">
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
                <i class="fas fa-user-shield breadcrumb-icon"></i>
                <span>Permisos / Tipo Usuario</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 tipo-user-section-card tipo-user-filtro-card" id="tipo-user-filtros-section">
        <div class="tipo-user-section-header">
            <div class="tipo-user-section-title">
                <span class="tipo-user-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Permisos / Tipo Usuario</h5>
                    <small>Consulte los registros por estado sin alterar la información existente.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary tipo-user-toggle-btn" id="tipo-user-toggle-filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="tipo-user-filtros-contenido">
            <form id="form_main_permisos" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 tipo-user-label-filter" for="estado_permisos">Estado</label>
                            <select id="estado_permisos" name="estado_permisos" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="tipo-user-filter-actions w-100">
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
    <section class="card mb-4 tipo-user-section-card" id="tipo-user-kpis-section">
        <div class="tipo-user-section-header">
            <div class="tipo-user-section-title">
                <span class="tipo-user-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Permisos / Tipo Usuario</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary tipo-user-toggle-btn" id="tipo-user-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="tipo-user-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="tipo-user-kpi-card tipo-user-kpi-blue">
                        <div>
                            <div class="tipo-user-kpi-label">Tipos de usuario</div>
                            <h3 id="tipo-user-kpi-total">0</h3>
                            <p>Registros encontrados</p>
                        </div>
                        <div class="tipo-user-kpi-icon"><i class="fas fa-users-cog"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="tipo-user-kpi-card tipo-user-kpi-green">
                        <div>
                            <div class="tipo-user-kpi-label">Activos</div>
                            <h3 id="tipo-user-kpi-activos">0</h3>
                            <p>Tipos de usuario activos</p>
                        </div>
                        <div class="tipo-user-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="tipo-user-kpi-card tipo-user-kpi-orange">
                        <div>
                            <div class="tipo-user-kpi-label">Inactivos</div>
                            <h3 id="tipo-user-kpi-inactivos">0</h3>
                            <p>Tipos de usuario inactivos</p>
                        </div>
                        <div class="tipo-user-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 tipo-user-section-card tipo-user-list-card">
        <div class="tipo-user-section-header">
            <div class="tipo-user-section-title">
                <span class="tipo-user-section-icon"><i class="fas fa-user-shield"></i></span>
                <div>
                    <h5 class="mb-0">Tipos de Usuario</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="tipo-user-toolbar">
                <div class="tipo-user-toolbar-left">
                    <button type="button" class="btn btn-secondary" id="tipo-user-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary" id="tipo-user-btn-create">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success" id="tipo-user-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger" id="tipo-user-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="tipo-user-toolbar-right">
                    <label class="tipo-user-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="tipo-user-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="tipo-user-view-switch">
                        <button type="button" class="tipo-user-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="tipo-user-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="tipo-user-search-wrap">
                        <span class="tipo-user-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="tipo-user-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="tipo-user-search-clear" class="tipo-user-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="tipo-user-listado" class="tipo-user-listado vista-detalle"></div>

            <div class="tipo-user-list-footer">
                <span id="tipo-user-info">0 registros</span>
                <div id="tipo-user-pagination" class="tipo-user-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="tipo-user-source-table" aria-hidden="true">
                <table id="dataTableTipoUser" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "tipo_user";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Tipo Usuario");
?>
