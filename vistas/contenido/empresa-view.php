<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/empresa.css">

<div class="container-fluid">
    <!-- Empresa -->
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
                <i class="fas fa-building breadcrumb-icon"></i>
                <span>Empresa</span>
            </li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 empresa-section-card">
        <div class="empresa-section-header">
            <div class="empresa-section-title">
                <div class="empresa-section-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <div>
                    <h5>Filtros de empresas</h5>
                    <p>Refine el listado por estado o utilice la búsqueda rápida.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary empresa-toggle-btn"
                    id="btn_toggle_filtros_empresa"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="empresa-section-body" id="contenido_filtros_empresa">
            <form id="form_main_empresa">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-5 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 empresa-label-filter">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_empresa"
                                    name="estado_empresa"
                                    class="form-control selectpicker"
                                    title="Estado"
                                    data-live-search="true">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-5 col-md-7 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 empresa-label-filter">
                                <i class="fas fa-search mr-1"></i> Búsqueda rápida
                            </label>
                            <div class="input-group empresa-search-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-building"></i>
                                    </span>
                                </div>
                                <input type="text"
                                       id="filtro_empresa_general"
                                       class="form-control"
                                       placeholder="Nombre, RTN, correo, teléfono, ubicación..."
                                       autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-12 mb-3 text-right empresa-filter-actions">
                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary" id="limpiar_empresa">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="card mb-4 empresa-section-card">
        <div class="empresa-section-header">
            <div class="empresa-section-title">
                <div class="empresa-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h5>Resumen de empresas</h5>
                    <p>Indicadores calculados sobre los registros actualmente filtrados.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary empresa-toggle-btn"
                    id="btn_toggle_kpis_empresa"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="empresa-section-body" id="contenido_kpis_empresa">
            <div class="row empresa-resumen-row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="empresa-resumen-card empresa-resumen-activa">
                        <div>
                            <span class="empresa-resumen-label">
                                <i class="fas fa-check-circle mr-1"></i> Empresas activas
                            </span>
                            <h3 id="empresa_total_activas">0</h3>
                            <p>Registros activos filtrados</p>
                        </div>
                        <div class="empresa-resumen-icon">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="empresa-resumen-card empresa-resumen-contacto">
                        <div>
                            <span class="empresa-resumen-label">
                                <i class="fas fa-phone-alt mr-1"></i> Con contacto
                            </span>
                            <h3 id="empresa_total_contacto">0</h3>
                            <p>Teléfono, celular o correo</p>
                        </div>
                        <div class="empresa-resumen-icon">
                            <i class="fas fa-address-book"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="empresa-resumen-card empresa-resumen-web">
                        <div>
                            <span class="empresa-resumen-label">
                                <i class="fas fa-globe mr-1"></i> Presencia digital
                            </span>
                            <h3 id="empresa_total_web">0</h3>
                            <p>Sitio web o Facebook</p>
                        </div>
                        <div class="empresa-resumen-icon">
                            <i class="fas fa-wifi"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="empresa-resumen-card empresa-resumen-firma">
                        <div>
                            <span class="empresa-resumen-label">
                                <i class="fas fa-file-signature mr-1"></i> Firma visible
                            </span>
                            <h3 id="empresa_total_firma">0</h3>
                            <p>Documento con firma activa</p>
                        </div>
                        <div class="empresa-resumen-icon">
                            <i class="fas fa-pen-nib"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado -->
    <div class="card mb-4 empresa-list-card">
        <div class="empresa-card-header">
            <div class="empresa-section-title">
                <div class="empresa-section-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <h5>Empresas</h5>
                    <p>Información general, contacto, ubicación, redes y configuración fiscal.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="empresa-list-toolbar">
                <div class="empresa-list-actions">
                    <button type="button"
                            class="btn btn-info table_actualizar ocultar"
                            id="btn_actualizar_empresa">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button"
                            class="btn btn-primary table_crear ocultar"
                            id="btn_ingresar_empresa">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button"
                            class="btn btn-success table_reportes ocultar"
                            id="btn_excel_empresa">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button"
                            class="btn btn-danger table_reportes ocultar"
                            id="btn_pdf_empresa">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="empresa-list-tools">
                    <div class="empresa-page-size">
                        <label for="empresa_page_size">Mostrar</label>
                        <select id="empresa_page_size" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </div>

                    <div class="empresa-view-switch"
                         role="group"
                         aria-label="Tipo de vista de empresas">
                        <button type="button"
                                class="empresa-view-btn active"
                                data-view="detalle"
                                title="Vista detalle"
                                aria-pressed="true">
                            <i class="fas fa-list-ul"></i>
                            <span>Detalle</span>
                        </button>

                        <button type="button"
                                class="empresa-view-btn"
                                data-view="miniatura"
                                title="Vista miniatura"
                                aria-pressed="false">
                            <i class="fas fa-th-large"></i>
                            <span>Miniatura</span>
                        </button>
                    </div>

                    <div class="empresa-search-list">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="search"
                                   id="buscar_empresa_listado"
                                   class="form-control"
                                   placeholder="Buscar empresa..."
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="empresaListado"
                 class="empresa-listado vista-detalle"></div>

            <div id="empresaVacio"
                 class="empresa-empty-state"
                 style="display:none;">
                <i class="fas fa-building"></i>
                <h5>No se encontraron empresas</h5>
                <p>No hay registros que coincidan con los filtros aplicados.</p>
            </div>

            <div class="empresa-list-footer">
                <div id="empresaInfo" class="empresa-list-info">0 registros</div>
                <nav id="empresaPaginacion"
                     class="empresa-pagination"
                     aria-label="Paginación de empresas"></nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "empresa";

                if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');

                    echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros ";
                }
            ?>
        </div>
    </div>

    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Empresas");
?>