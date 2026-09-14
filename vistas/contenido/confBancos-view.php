<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confBancos.css">

<div class="container-fluid bancos-page">
    <!-- Bancos -->
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
                <i class="fas fa-university breadcrumb-icon"></i>
                <span>Bancos</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card bancos-section-card mb-4" id="bancosFiltrosSection">
        <div class="bancos-section-header">
            <div class="bancos-section-heading">
                <span class="bancos-section-icon">
                    <i class="fas fa-filter"></i>
                </span>
                <div>
                    <h5>Filtros de Bancos</h5>
                    <p>Consulte los bancos por estado sin alterar los registros.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary bancos-toggle-section" data-target="#bancosFiltrosBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="bancos-section-body" id="bancosFiltrosBody">
            <form id="form_main_Bancos" autocomplete="off">
                <div class="bancos-filter-grid">
                    <div class="bancos-filter-field">
                        <label for="estado_conf_Bancos">
                            <i class="fas fa-toggle-on mr-1"></i>
                            Estado
                        </label>

                        <select id="estado_conf_Bancos"
                                name="estado_conf_Bancos"
                                class="form-control bancos-select2"
                                data-placeholder="Todos los estados">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="bancos-filter-actions">
                        <button type="submit" class="btn btn-primary" id="search">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>

                        <button type="reset" class="btn btn-info" id="btnBancosLimpiarFiltros">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card bancos-section-card mb-4" id="bancosKpisSection">
        <div class="bancos-section-header">
            <div class="bancos-section-heading">
                <span class="bancos-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <div>
                    <h5>Resumen de Bancos</h5>
                    <p>Indicadores calculados sobre el resultado filtrado.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary bancos-toggle-section" data-target="#bancosKpisBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="bancos-section-body" id="bancosKpisBody">
            <div class="bancos-kpi-grid">
                <article class="bancos-kpi bancos-kpi-primary">
                    <div class="bancos-kpi-copy">
                        <span class="bancos-kpi-label">REGISTROS</span>
                        <strong id="bancosKpiRegistros">0</strong>
                        <small>Bancos encontrados</small>
                    </div>
                    <span class="bancos-kpi-icon"><i class="fas fa-list-ol"></i></span>
                </article>

                <article class="bancos-kpi bancos-kpi-success">
                    <div class="bancos-kpi-copy">
                        <span class="bancos-kpi-label">ACTIVOS</span>
                        <strong id="bancosKpiActivos">0</strong>
                        <small>Bancos disponibles</small>
                    </div>
                    <span class="bancos-kpi-icon"><i class="fas fa-check-circle"></i></span>
                </article>

                <article class="bancos-kpi bancos-kpi-danger">
                    <div class="bancos-kpi-copy">
                        <span class="bancos-kpi-label">INACTIVOS</span>
                        <strong id="bancosKpiInactivos">0</strong>
                        <small>Bancos deshabilitados</small>
                    </div>
                    <span class="bancos-kpi-icon"><i class="fas fa-ban"></i></span>
                </article>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card bancos-section-card mb-4">
        <div class="bancos-section-header">
            <div class="bancos-section-heading">
                <span class="bancos-section-icon">
                    <i class="fas fa-university"></i>
                </span>
                <div>
                    <h5>Bancos</h5>
                    <p>Administre las instituciones bancarias y su estado.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary bancos-toggle-section" data-target="#bancosListadoBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="bancos-section-body" id="bancosListadoBody">
            <div class="bancos-toolbar">
                <div class="bancos-toolbar-left">
                    <button type="button" class="btn btn-info table_actualizar ocultar" id="btnBancosActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnBancosIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnBancosExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnBancosPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="bancos-toolbar-right">
                    <label class="bancos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="bancosPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="bancos-view-switch" role="group" aria-label="Vista de bancos">
                        <button type="button" class="bancos-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="bancos-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="bancos-search-wrap">
                        <span class="bancos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="bancosBuscar" class="form-control" placeholder="Buscar banco..." autocomplete="off">
                        <button type="button" id="bancosBuscarLimpiar" class="bancos-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bancos-detail-header" aria-hidden="true">
                <div>Acciones</div>
                <div>Banco</div>
                <div>Estado</div>
            </div>

            <div id="bancosListado" class="bancos-listado vista-detalle"></div>

            <div class="bancos-list-footer">
                <span id="bancosInfo">0 registros</span>
                <div id="bancosPaginacion" class="bancos-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted bancos-last-update">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "banco";

                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                }else{
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Bancos");
?>
