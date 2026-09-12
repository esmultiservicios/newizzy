<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/proveedores.css">

<div class="container-fluid proveedores-page">
    <!-- Breadcrumb -->
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
                <i class="fas fa-truck breadcrumb-icon"></i>
                <span>Proveedores</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card proveedores-section-card mb-4" id="proveedoresFiltrosSection">
        <div class="proveedores-section-header">
            <div class="proveedores-section-title">
                <span class="proveedores-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros</h5>
                    <small>Refine el directorio de proveedores.</small>
                </div>
            </div>
            <button type="button"
                    class="btn btn-primary proveedores-toggle-btn"
                    id="btnToggleFiltrosProveedores"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="proveedoresFiltrosContenido">
            <form id="form_main_proveedores" autocomplete="off">
                <div class="form-row align-items-end">
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="proveedores-filter-label" for="estado_proveedores">
                            <i class="fas fa-toggle-on mr-1"></i> Estado
                        </label>
                        <select id="estado_proveedores"
                                name="estado_proveedores"
                                class="form-control selectpicker"
                                title="Estado"
                                data-live-search="true"
                                data-width="100%">
                        </select>
                    </div>

                    <div class="col-lg-8 col-md-6 col-12 mb-3">
                        <div class="proveedores-filter-actions">
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
    <section class="card proveedores-section-card mb-4" id="proveedoresKpisSection">
        <div class="proveedores-section-header">
            <div class="proveedores-section-title">
                <span class="proveedores-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Proveedores</h5>
                    <small>Indicadores del conjunto filtrado actualmente.</small>
                </div>
            </div>
            <button type="button"
                    class="btn btn-primary proveedores-toggle-btn"
                    id="btnToggleKpisProveedores"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="proveedoresKpisContenido">
            <div class="row proveedores-kpi-row">
                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="proveedores-kpi proveedores-kpi-primary">
                        <div class="proveedores-kpi-copy">
                            <span class="proveedores-kpi-label">Registros</span>
                            <strong id="proveedoresKpiRegistros">0</strong>
                            <small>Proveedores encontrados</small>
                        </div>
                        <span class="proveedores-kpi-icon"><i class="fas fa-truck"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="proveedores-kpi proveedores-kpi-success">
                        <div class="proveedores-kpi-copy">
                            <span class="proveedores-kpi-label">Con RTN</span>
                            <strong id="proveedoresKpiRtn">0</strong>
                            <small>Identificación registrada</small>
                        </div>
                        <span class="proveedores-kpi-icon"><i class="fas fa-id-card"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="proveedores-kpi proveedores-kpi-info">
                        <div class="proveedores-kpi-copy">
                            <span class="proveedores-kpi-label">Con Teléfono</span>
                            <strong id="proveedoresKpiTelefono">0</strong>
                            <small>Contacto telefónico</small>
                        </div>
                        <span class="proveedores-kpi-icon"><i class="fas fa-phone-alt"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="proveedores-kpi proveedores-kpi-purple">
                        <div class="proveedores-kpi-copy">
                            <span class="proveedores-kpi-label">Con Correo</span>
                            <strong id="proveedoresKpiCorreo">0</strong>
                            <small>Correo electrónico registrado</small>
                        </div>
                        <span class="proveedores-kpi-icon"><i class="fas fa-envelope"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card proveedores-section-card proveedores-directory-card mb-4" id="proveedoresListadoSection">
        <div class="proveedores-directory-header">
            <div class="proveedores-section-title">
                <span class="proveedores-section-icon"><i class="fas fa-address-book"></i></span>
                <div>
                    <h5 class="mb-0">Directorio de Proveedores</h5>
                    <small>Listado administrativo, acciones y exportaciones.</small>
                </div>
            </div>
        </div>

        <div class="card-body proveedores-directory-body">
            <div class="proveedores-list-toolbar">
                <div class="proveedores-toolbar-left">
                    <button type="button" id="btnActualizarProveedores"
                            class="btn btn-secondary table_actualizar ocultar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" id="btnNuevoProveedor"
                            class="btn btn-primary table_crear ocultar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" id="btnExcelProveedores"
                            class="btn btn-success table_reportes ocultar">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" id="btnPdfProveedores"
                            class="btn btn-danger table_reportes ocultar">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="proveedores-list-tools-right">
                    <label class="proveedores-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="proveedoresPageSize" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </label>

                    <div class="proveedores-view-switch" role="group" aria-label="Tipo de vista">
                        <button type="button"
                                class="proveedores-view-btn active"
                                data-view="detalle"
                                aria-pressed="true"
                                title="Vista detalle">
                            <i class="fas fa-list mr-1"></i><span>Detalle</span>
                        </button>
                        <button type="button"
                                class="proveedores-view-btn"
                                data-view="miniatura"
                                aria-pressed="false"
                                title="Vista miniatura">
                            <i class="fas fa-th-large mr-1"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="proveedores-search">
                        <span class="proveedores-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search"
                               id="buscarProveedoresListado"
                               class="form-control"
                               placeholder="Buscar proveedor..."
                               autocomplete="off">
                        <button type="button"
                                id="limpiarBuscarProveedores"
                                class="proveedores-search-clear"
                                title="Limpiar búsqueda"
                                aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="proveedoresListado"
                 class="proveedores-listado vista-detalle"
                 aria-live="polite"></div>

            <div class="proveedores-list-footer">
                <span id="proveedoresInfo" class="proveedores-list-info">0 registros</span>
                <div id="proveedoresPaginacion" class="proveedores-pagination"></div>
            </div>

            <!-- Tabla técnica oculta: solo para exportación Excel. Nunca se muestra al usuario. -->
            <div class="proveedores-export-host" aria-hidden="true">
                <table id="proveedoresExportTable"></table>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "proveedores";

                if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>

    <?php
        $insMainModel->guardar_historial_accesos("Ingreso al modulo Proveedores");
    ?>
</div>
