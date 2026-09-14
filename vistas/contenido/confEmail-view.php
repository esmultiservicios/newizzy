<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confEmail.css">

<div class="container-fluid correo-config-page">
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
                <i class="fas fa-envelope breadcrumb-icon"></i>
                <span>Configurar Correos</span>
            </li>
        </ol>
    </div>

    <!-- KPIs -->
    <section class="card mb-4 correo-config-section-card" id="correo-config-kpis-section">
        <div class="correo-config-section-header">
            <div class="correo-config-section-title">
                <span class="correo-config-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Configurar Correos</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary correo-config-toggle-btn" id="correo-config-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="correo-config-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="correo-config-kpi-card correo-config-kpi-blue">
                        <div>
                            <div class="correo-config-kpi-label">Configuraciones</div>
                            <h3 id="correo-config-kpi-total">0</h3>
                            <p>Total de correos configurados</p>
                        </div>
                        <div class="correo-config-kpi-icon"><i class="fas fa-envelope"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="correo-config-kpi-card correo-config-kpi-teal">
                        <div>
                            <div class="correo-config-kpi-label">SMTP</div>
                            <h3 id="correo-config-kpi-smtp">0</h3>
                            <p>Configuraciones mediante SMTP</p>
                        </div>
                        <div class="correo-config-kpi-icon"><i class="fas fa-server"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="correo-config-kpi-card correo-config-kpi-purple">
                        <div>
                            <div class="correo-config-kpi-label">Microsoft Graph</div>
                            <h3 id="correo-config-kpi-graph">0</h3>
                            <p>Configuraciones mediante Graph</p>
                        </div>
                        <div class="correo-config-kpi-icon"><i class="fab fa-microsoft"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="correo-config-kpi-card correo-config-kpi-green">
                        <div>
                            <div class="correo-config-kpi-label">Activos</div>
                            <h3 id="correo-config-kpi-activos">0</h3>
                            <p>Configuraciones activas</p>
                        </div>
                        <div class="correo-config-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 correo-config-section-card correo-config-list-card">
        <div class="correo-config-section-header">
            <div class="correo-config-section-title">
                <span class="correo-config-section-icon"><i class="fas fa-envelope"></i></span>
                <div>
                    <h5 class="mb-0">Configuraciones de Correo</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="correo-config-toolbar">
                <div class="correo-config-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="correo-config-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="correo-config-btn-create">
                        <i class="fas fa-user-plus mr-1"></i> Destinatarios
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="correo-config-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="correo-config-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="correo-config-toolbar-right">
                    <label class="correo-config-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="correo-config-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="correo-config-view-switch">
                        <button type="button" class="correo-config-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="correo-config-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="correo-config-search-wrap">
                        <span class="correo-config-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="correo-config-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="correo-config-search-clear" class="correo-config-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="correo-config-listado" class="correo-config-listado vista-detalle"></div>

            <div class="correo-config-list-footer">
                <span id="correo-config-info">0 registros</span>
                <div id="correo-config-pagination" class="correo-config-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="correo-config-source-table" aria-hidden="true">
                <table id="dataTableConfCorreos" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "correo";

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
    $insMainModel->guardar_historial_accesos("Ingreso al módulo Configurar Correos");
?>
