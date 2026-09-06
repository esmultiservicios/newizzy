<link rel="stylesheet"
      href="<?php echo SERVERURL; ?>vistas/plantilla/css/secuencia_facturacion.css">
      
<div class="container-fluid secuencia-page">
    <!-- Secuencia Facturación -->
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
                <i class="fas fa-file-invoice breadcrumb-icon"></i>
                <span>Secuencia Facturación</span>
            </li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 secuencia-filtro-card secuencia-panel-card">
        <div class="card-header secuencia-panel-header">
            <div>
                <div class="secuencia-panel-title">
                    <i class="fas fa-filter mr-2"></i> Filtros de secuencia
                </div>
                <small>Refine los registros por estado, documento, vencimiento o búsqueda rápida.</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm secuencia-toggle-btn" id="btn_toggle_secuencia_filtros">
                <i class="fas fa-chevron-down mr-1"></i> Mostrar
            </button>
        </div>
        <div class="card-body" id="secuencia_filtros_body" style="display:none;">
            <form id="form_main_secuencia" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter" for="estado_secuencia_main">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_secuencia_main" name="estado_secuencia_main" class="form-control selectpicker" title="Estado" data-live-search="true" data-width="100%">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter" for="documento_secuencia_main">
                                <i class="fas fa-file-alt mr-1"></i> Documento
                            </label>
                            <select id="documento_secuencia_main" name="documento_secuencia_main" class="form-control selectpicker" title="Documento" data-live-search="true" data-width="100%">
                                <option value="">Todos</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter" for="vencimiento_secuencia_main">
                                <i class="fas fa-calendar-times mr-1"></i> Vencimiento
                            </label>
                            <select id="vencimiento_secuencia_main" name="vencimiento_secuencia_main" class="form-control selectpicker" title="Vencimiento" data-live-search="true" data-width="100%">
                                <option value="">Todos</option>
                                <option value="vigente">Vigente</option>
                                <option value="por_vencer">Por vencer</option>
                                <option value="vencida">Vencida</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-12 col-md-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter" for="filtro_secuencia_general">
                                <i class="fas fa-search mr-1"></i> Búsqueda rápida
                            </label>
                            <div class="input-group secuencia-search-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                </div>
                                <input type="text" id="filtro_secuencia_general" name="filtro_secuencia_general" class="form-control" placeholder="Empresa, documento, CAI, prefijo, rango...">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-1">
                        <div class="secuencia-filter-actions">
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
    </div>

    <!-- Cards resumen -->
    <div class="card mb-4 secuencia-kpi-card secuencia-panel-card">
        <div class="card-header secuencia-panel-header">
            <div>
                <div class="secuencia-panel-title">
                    <i class="fas fa-chart-pie mr-2"></i> Resumen de secuencia
                </div>
                <small>Indicadores calculados sobre los registros filtrados.</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm secuencia-toggle-btn" id="btn_toggle_secuencia_kpis">
                <i class="fas fa-chevron-down mr-1"></i> Mostrar
            </button>
        </div>
        <div class="card-body" id="secuencia_kpis_body" style="display:none;">
            <div class="row secuencia-resumen-row mb-0">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="secuencia-resumen-card secuencia-resumen-activa">
                        <div class="secuencia-resumen-content">
                            <span class="secuencia-resumen-label"><i class="fas fa-check-circle mr-1"></i> Secuencias activas</span>
                            <h3 id="secuencia_total_activas">0</h3>
                            <p>Registros activos filtrados</p>
                        </div>
                        <div class="secuencia-resumen-icon"><i class="fas fa-sliders-h"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="secuencia-resumen-card secuencia-resumen-fiscal">
                        <div class="secuencia-resumen-content">
                            <span class="secuencia-resumen-label"><i class="fas fa-file-invoice-dollar mr-1"></i> Con CAI</span>
                            <h3 id="secuencia_total_cai">0</h3>
                            <p>Documentos fiscales</p>
                        </div>
                        <div class="secuencia-resumen-icon"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="secuencia-resumen-card secuencia-resumen-disponible">
                        <div class="secuencia-resumen-content">
                            <span class="secuencia-resumen-label"><i class="fas fa-layer-group mr-1"></i> Disponibles</span>
                            <h3 id="secuencia_total_disponibles">0</h3>
                            <p>Correlativos restantes</p>
                        </div>
                        <div class="secuencia-resumen-icon"><i class="fas fa-list-ol"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="secuencia-resumen-card secuencia-resumen-vencer">
                        <div class="secuencia-resumen-content">
                            <span class="secuencia-resumen-label"><i class="fas fa-calendar-times mr-1"></i> Por vencer</span>
                            <h3 id="secuencia_total_por_vencer">0</h3>
                            <p>Vencen en 30 días o menos</p>
                        </div>
                        <div class="secuencia-resumen-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado por DIVs -->
    <div class="card mb-4 secuencia-list-card">
        <div class="card-header secuencia-card-header">
            <div class="secuencia-card-heading">
                <div>
                    <i class="fas fa-sliders-h fa-lg mr-1"></i>
                    <strong>Secuencia Facturación</strong>
                    <small class="d-block text-muted mt-1">Control de CAI, prefijo, rangos autorizados, correlativo siguiente y vencimiento.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="secuencia-toolbar" aria-label="Acciones de secuencia">
                <div class="secuencia-toolbar-group">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btn_actualizar_secuencias">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btn_nueva_secuencia">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-info table_crear ocultar" id="btn_documentos_secuencia">
                        <i class="fas fa-folder-open mr-1"></i> Documentos
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btn_exportar_secuencia_excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btn_exportar_secuencia_pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="secuencia-toolbar-tools">
                    <div class="secuencia-page-size">
                        <label for="secuencia_page_size">Mostrar</label>
                        <select id="secuencia_page_size" class="form-control form-control-sm">
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <span>registros</span>
                    </div>

                    <div class="secuencia-view-switch"
                         role="group"
                         aria-label="Cambiar vista de secuencias">
                        <button type="button"
                                class="secuencia-view-btn active"
                                data-view="detalle"
                                title="Vista detalle"
                                aria-pressed="true">
                            <i class="fas fa-list"></i>
                            <span>Detalle</span>
                        </button>

                        <button type="button"
                                class="secuencia-view-btn"
                                data-view="miniatura"
                                title="Vista miniatura"
                                aria-pressed="false">
                            <i class="fas fa-th-large"></i>
                            <span>Miniatura</span>
                        </button>
                    </div>

                    <div class="secuencia-list-search">
                        <label for="secuencia_buscar_listado" class="sr-only">Buscar</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="search" id="secuencia_buscar_listado" class="form-control" placeholder="Buscar secuencia..." autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="secuencia_loading" class="secuencia-state-box d-none" role="status" aria-live="polite">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Cargando secuencias...</span>
            </div>

            <div id="secuencia_empty" class="secuencia-state-box d-none" role="status" aria-live="polite">
                <i class="fas fa-inbox"></i>
                <div>
                    <strong>No se encontraron secuencias</strong>
                    <small>Modifica los filtros o registra una nueva secuencia.</small>
                </div>
            </div>

            <div id="secuencia_listado" class="secuencia-listado" aria-live="polite"></div>

            <div class="secuencia-list-footer">
                <div id="secuencia_resultado_info" class="secuencia-resultado-info">Mostrando 0 registros</div>
                <nav id="secuencia_paginacion" class="secuencia-paginacion" aria-label="Paginación de secuencias"></nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "secuencia_facturacion";

                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Secuencia de Facturación");
?>