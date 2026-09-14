<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confImpresora.css">

<div class="container-fluid impresora-config-page">
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
                <i class="fas fa-print breadcrumb-icon"></i>
                <span>Impresora</span>
            </li>
        </ol>
    </div>

    <!-- KPIs -->
    <section class="card mb-4 impresora-config-section-card" id="impresora-config-kpis-section">
        <div class="impresora-config-section-header">
            <div class="impresora-config-section-title">
                <span class="impresora-config-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Impresora</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary impresora-config-toggle-btn" id="impresora-config-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="impresora-config-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="impresora-config-kpi-card impresora-config-kpi-blue">
                        <div>
                            <div class="impresora-config-kpi-label">Impresoras</div>
                            <h3 id="impresora-config-kpi-total">0</h3>
                            <p>Configuraciones encontradas</p>
                        </div>
                        <div class="impresora-config-kpi-icon"><i class="fas fa-print"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="impresora-config-kpi-card impresora-config-kpi-green">
                        <div>
                            <div class="impresora-config-kpi-label">Activas</div>
                            <h3 id="impresora-config-kpi-activos">0</h3>
                            <p>Impresoras activas</p>
                        </div>
                        <div class="impresora-config-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="impresora-config-kpi-card impresora-config-kpi-orange">
                        <div>
                            <div class="impresora-config-kpi-label">Inactivas</div>
                            <h3 id="impresora-config-kpi-inactivos">0</h3>
                            <p>Impresoras inactivas</p>
                        </div>
                        <div class="impresora-config-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 impresora-config-section-card impresora-config-list-card">
        <div class="impresora-config-section-header">
            <div class="impresora-config-section-title">
                <span class="impresora-config-section-icon"><i class="fas fa-print"></i></span>
                <div>
                    <h5 class="mb-0">Configuración de Impresora</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="impresora-config-toolbar">
                <div class="impresora-config-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="impresora-config-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="impresora-config-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="impresora-config-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="impresora-config-toolbar-right">
                    <label class="impresora-config-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="impresora-config-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="impresora-config-view-switch">
                        <button type="button" class="impresora-config-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="impresora-config-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="impresora-config-search-wrap">
                        <span class="impresora-config-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="impresora-config-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="impresora-config-search-clear" class="impresora-config-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="impresora-config-listado" class="impresora-config-listado vista-detalle"></div>

            <div class="impresora-config-list-footer">
                <span id="impresora-config-info">0 registros</span>
                <div id="impresora-config-pagination" class="impresora-config-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="impresora-config-source-table" aria-hidden="true">
                <table id="dataTableConfImpresora" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "impresora";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configurar Impresora");
?>
