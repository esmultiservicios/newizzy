<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/puestos.css">

<div class="container-fluid puestos-page">
    <!-- Puestos -->
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
                <i class="fas fa-user-tag breadcrumb-icon"></i>
                <span>Puestos</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 puestos-section-card puestos-filtro-card" id="puestosFiltrosSection">
        <div class="puestos-section-header">
            <div class="puestos-section-title">
                <span class="puestos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Puestos</h5>
                    <small>Consulte los puestos registrados por estado.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary puestos-toggle-btn" id="btnToggleFiltrosPuestos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="puestosFiltrosContenido">
            <form id="form_main_puestos" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 puestos-label-filter" for="estado_puestos">Estado</label>
                            <select id="estado_puestos" name="estado_puestos" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="puestos-filtro-actions w-100">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" class="btn btn-secondary" id="btn-limpiar-filtros-puestos">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card mb-4 puestos-section-card" id="puestosKpisSection">
        <div class="puestos-section-header">
            <div class="puestos-section-title">
                <span class="puestos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Puestos</h5>
                    <small>Indicadores calculados sobre los resultados visibles.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary puestos-toggle-btn" id="btnToggleKpisPuestos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="puestosKpisContenido">
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="puestos-resumen-card puestos-resumen-total">
                        <div>
                            <div class="puestos-resumen-label">Total puestos</div>
                            <h3 id="puestos-card-total">0</h3>
                            <p>Puestos encontrados</p>
                        </div>
                        <div class="puestos-resumen-icon"><i class="fas fa-briefcase"></i></div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="puestos-resumen-card puestos-resumen-activos">
                        <div>
                            <div class="puestos-resumen-label">Activos</div>
                            <h3 id="puestos-card-activos">0</h3>
                            <p>Puestos disponibles actualmente</p>
                        </div>
                        <div class="puestos-resumen-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="puestos-resumen-card puestos-resumen-inactivos">
                        <div>
                            <div class="puestos-resumen-label">Inactivos</div>
                            <h3 id="puestos-card-inactivos">0</h3>
                            <p>Puestos marcados como inactivos</p>
                        </div>
                        <div class="puestos-resumen-icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 puestos-section-card puestos-list-card">
        <div class="puestos-section-header">
            <div class="puestos-section-title">
                <span class="puestos-section-icon"><i class="fas fa-briefcase"></i></span>
                <div>
                    <h5 class="mb-0">Puestos</h5>
                    <small>Administración de puestos registrados en el sistema.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="puestos-list-toolbar">
                <div class="puestos-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnPuestosActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnPuestosIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnPuestosExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnPuestosPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="puestos-toolbar-right">
                    <label class="puestos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="puestosPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="puestos-view-switch">
                        <button type="button" class="puestos-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="puestos-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="puestos-search-wrap">
                        <span class="puestos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarPuestosListado" class="form-control" placeholder="Buscar puesto..." autocomplete="off">
                        <button type="button" id="limpiarBuscarPuestosListado" class="puestos-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="puestosListado" class="puestos-listado vista-detalle"></div>

            <div class="puestos-list-footer">
                <span id="puestosInfo">0 registros</span>
                <div id="puestosPaginacion" class="puestos-pagination"></div>
            </div>
        </div>

        <div class="card-footer small puestos-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "puestos";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Puestos");
?>
