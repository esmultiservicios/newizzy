<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confAlmacen.css">

<div class="container-fluid almacen-page">
    <!-- Almacén -->
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
                <i class="fas fa-warehouse breadcrumb-icon"></i>
                <span>Almacén</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 almacen-section-card almacen-filtro-card" id="almacenFiltrosSection">
        <div class="almacen-section-header">
            <div class="almacen-section-title">
                <span class="almacen-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Almacén</h5>
                    <small>Consulte los almacenes registrados por estado.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary almacen-toggle-btn" id="btnToggleFiltrosAlmacen" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="almacenFiltrosContenido">
            <form id="form_main_almacen" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 almacen-label-filter" for="estado_almacen">Estado</label>
                            <select id="estado_almacen" name="estado_almacen" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="almacen-filtro-actions w-100">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" class="btn btn-secondary" id="btn-limpiar-filtros-almacen">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card mb-4 almacen-section-card" id="almacenKpisSection">
        <div class="almacen-section-header">
            <div class="almacen-section-title">
                <span class="almacen-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Almacenes</h5>
                    <small>Indicadores calculados sobre los resultados visibles.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary almacen-toggle-btn" id="btnToggleKpisAlmacen" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="almacenKpisContenido">
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="almacen-resumen-card almacen-resumen-total">
                        <div>
                            <div class="almacen-resumen-label">Total almacenes</div>
                            <h3 id="almacen-card-total">0</h3>
                            <p>Registros encontrados</p>
                        </div>
                        <div class="almacen-resumen-icon"><i class="fas fa-warehouse"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="almacen-resumen-card almacen-resumen-activos">
                        <div>
                            <div class="almacen-resumen-label">Activos</div>
                            <h3 id="almacen-card-activos">0</h3>
                            <p>Almacenes disponibles</p>
                        </div>
                        <div class="almacen-resumen-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="almacen-resumen-card almacen-resumen-cero">
                        <div>
                            <div class="almacen-resumen-label">Facturan en cero</div>
                            <h3 id="almacen-card-cero">0</h3>
                            <p>Permiten facturación en cero</p>
                        </div>
                        <div class="almacen-resumen-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="almacen-resumen-card almacen-resumen-empresas">
                        <div>
                            <div class="almacen-resumen-label">Empresas</div>
                            <h3 id="almacen-card-empresas">0</h3>
                            <p>Empresas presentes en el resultado</p>
                        </div>
                        <div class="almacen-resumen-icon"><i class="fas fa-building"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 almacen-section-card almacen-list-card">
        <div class="almacen-section-header">
            <div class="almacen-section-title">
                <span class="almacen-section-icon"><i class="fas fa-warehouse"></i></span>
                <div>
                    <h5 class="mb-0">Almacenes</h5>
                    <small>Administración de almacenes, empresas, ubicación y configuración de facturación.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="almacen-list-toolbar">
                <div class="almacen-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnAlmacenActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnAlmacenIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnAlmacenExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnAlmacenPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="almacen-toolbar-right">
                    <label class="almacen-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="almacenPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="almacen-view-switch">
                        <button type="button" class="almacen-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="almacen-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="almacen-search-wrap">
                        <span class="almacen-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarAlmacenListado" class="form-control" placeholder="Buscar almacén..." autocomplete="off">
                        <button type="button" id="limpiarBuscarAlmacenListado" class="almacen-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="almacenListado" class="almacen-listado vista-detalle"></div>

            <div class="almacen-list-footer">
                <span id="almacenInfo">0 registros</span>
                <div id="almacenPaginacion" class="almacen-pagination"></div>
            </div>
        </div>

        <div class="card-footer small almacen-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "almacen";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configurar Almacén");
?>
