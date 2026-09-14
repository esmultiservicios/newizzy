<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confImpuestos.css">

<div class="container-fluid impuestos-page">
    <!-- Impuestos -->
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
                <span>Impuestos</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card impuestos-section-card mb-4" id="impuestosFiltrosSection">
        <div class="impuestos-section-header">
            <div class="impuestos-section-heading">
                <span class="impuestos-section-icon">
                    <i class="fas fa-filter"></i>
                </span>
                <div>
                    <h5>Filtros de Impuestos</h5>
                    <p>Consulte los impuestos configurados sin alterar los registros.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary impuestos-toggle-section" data-target="#impuestosFiltrosBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="impuestos-section-body" id="impuestosFiltrosBody">
            <form id="form_main_Impuestos" autocomplete="off">
                <div class="impuestos-filter-grid">
                    <div class="impuestos-filter-field">
                        <label for="impuestosFiltroTipo">
                            <i class="fas fa-percentage mr-1"></i>
                            Impuesto
                        </label>

                        <select id="impuestosFiltroTipo"
                                class="form-control impuestos-select2"
                                data-placeholder="Todos los impuestos">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="impuestos-filter-actions">
                        <button type="submit" class="btn btn-primary" id="btnImpuestosFiltrar">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>

                        <button type="reset" class="btn btn-info" id="btnImpuestosLimpiar">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card impuestos-section-card mb-4" id="impuestosKpisSection">
        <div class="impuestos-section-header">
            <div class="impuestos-section-heading">
                <span class="impuestos-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <div>
                    <h5>Resumen de Impuestos</h5>
                    <p>Indicadores calculados sobre el resultado filtrado.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary impuestos-toggle-section" data-target="#impuestosKpisBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="impuestos-section-body" id="impuestosKpisBody">
            <div class="impuestos-kpi-grid">
                <article class="impuestos-kpi impuestos-kpi-primary">
                    <div class="impuestos-kpi-copy">
                        <span class="impuestos-kpi-label">REGISTROS</span>
                        <strong id="impuestosKpiRegistros">0</strong>
                        <small>Impuestos encontrados</small>
                    </div>
                    <span class="impuestos-kpi-icon"><i class="fas fa-list-ol"></i></span>
                </article>

                <article class="impuestos-kpi impuestos-kpi-info">
                    <div class="impuestos-kpi-copy">
                        <span class="impuestos-kpi-label">PROMEDIO</span>
                        <strong id="impuestosKpiPromedio">0.00%</strong>
                        <small>Valor promedio configurado</small>
                    </div>
                    <span class="impuestos-kpi-icon"><i class="fas fa-chart-line"></i></span>
                </article>

                <article class="impuestos-kpi impuestos-kpi-success">
                    <div class="impuestos-kpi-copy">
                        <span class="impuestos-kpi-label">MÁXIMO</span>
                        <strong id="impuestosKpiMaximo">0.00%</strong>
                        <small>Mayor porcentaje configurado</small>
                    </div>
                    <span class="impuestos-kpi-icon"><i class="fas fa-arrow-up"></i></span>
                </article>

                <article class="impuestos-kpi impuestos-kpi-purple">
                    <div class="impuestos-kpi-copy">
                        <span class="impuestos-kpi-label">MÍNIMO</span>
                        <strong id="impuestosKpiMinimo">0.00%</strong>
                        <small>Menor porcentaje configurado</small>
                    </div>
                    <span class="impuestos-kpi-icon"><i class="fas fa-arrow-down"></i></span>
                </article>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card impuestos-section-card mb-4">
        <div class="impuestos-section-header">
            <div class="impuestos-section-heading">
                <span class="impuestos-section-icon">
                    <i class="fas fa-percentage"></i>
                </span>
                <div>
                    <h5>Impuestos</h5>
                    <p>Administre los tipos de impuesto y sus valores.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary impuestos-toggle-section" data-target="#impuestosListadoBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="impuestos-section-body" id="impuestosListadoBody">
            <div class="impuestos-toolbar">
                <div class="impuestos-toolbar-left">
                    <button type="button" class="btn btn-info table_actualizar ocultar" id="btnImpuestosActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnImpuestosExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnImpuestosPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="impuestos-toolbar-right">
                    <label class="impuestos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="impuestosPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="impuestos-view-switch" role="group" aria-label="Vista de impuestos">
                        <button type="button" class="impuestos-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="impuestos-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="impuestos-search-wrap">
                        <span class="impuestos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="impuestosBuscar" class="form-control" placeholder="Buscar impuesto..." autocomplete="off">
                        <button type="button" id="impuestosBuscarLimpiar" class="impuestos-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="impuestos-detail-header" aria-hidden="true">
                <div>Acciones</div>
                <div>Impuesto</div>
                <div>Valor</div>
            </div>

            <div id="impuestosListado" class="impuestos-listado vista-detalle"></div>

            <div class="impuestos-list-footer">
                <span id="impuestosInfo">0 registros</span>
                <div id="impuestosPaginacion" class="impuestos-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted impuestos-last-update">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "isv";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Impuestos");
?>
