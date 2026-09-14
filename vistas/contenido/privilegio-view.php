<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/privilegio.css">

<div class="container-fluid privilegios-page">
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
                <i class="fas fa-key breadcrumb-icon"></i>
                <span>Privilegios</span>
            </li>
        </ol>
    </div>

    <section class="card mb-4 privilegios-section-card" id="privilegiosFiltrosSection">
        <div class="privilegios-section-header">
            <div class="privilegios-section-title">
                <span class="privilegios-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5>Filtros de Privilegios</h5>
                    <p>Consulte los privilegios registrados por estado.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary privilegios-toggle-btn" id="btnToggleFiltrosPrivilegios" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="privilegiosFiltrosContenido">
            <form id="form_main_privilegios" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <label class="privilegios-filter-label" for="estado_privilegios">Estado</label>
                        <select id="estado_privilegios" name="estado_privilegios" class="form-control izzy-select2" data-placeholder="Todos">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-8 col-sm-6 mb-3">
                        <div class="privilegios-filter-actions">
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

    <section class="card mb-4 privilegios-section-card" id="privilegiosResumenSection">
        <div class="privilegios-section-header">
            <div class="privilegios-section-title">
                <span class="privilegios-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5>Resumen de Privilegios</h5>
                    <p>Indicadores calculados sobre los registros actualmente filtrados.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary privilegios-toggle-btn" id="btnToggleResumenPrivilegios" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="privilegiosResumenContenido">
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="privilegios-kpi-card kpi-blue">
                        <div>
                            <span>Privilegios</span>
                            <h3 id="privilegiosKpiTotal">0</h3>
                            <p>Total de privilegios encontrados</p>
                        </div>
                        <div class="privilegios-kpi-icon"><i class="fas fa-key"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="privilegios-kpi-card kpi-green">
                        <div>
                            <span>Activos</span>
                            <h3 id="privilegiosKpiActivos">0</h3>
                            <p>Privilegios activos</p>
                        </div>
                        <div class="privilegios-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="privilegios-kpi-card kpi-teal">
                        <div>
                            <span>Menús asignados</span>
                            <h3 id="privilegiosKpiMenus">0</h3>
                            <p>Asignaciones de menú</p>
                        </div>
                        <div class="privilegios-kpi-icon"><i class="fas fa-bars"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="privilegios-kpi-card kpi-purple">
                        <div>
                            <span>Submenús asignados</span>
                            <h3 id="privilegiosKpiSubmenus">0</h3>
                            <p>Niveles 1 y 2 asignados</p>
                        </div>
                        <div class="privilegios-kpi-icon"><i class="fas fa-sitemap"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card mb-4 privilegios-section-card privilegios-list-card">
        <div class="privilegios-section-header">
            <div class="privilegios-section-title">
                <span class="privilegios-section-icon"><i class="fas fa-user-shield"></i></span>
                <div>
                    <h5>Privilegios</h5>
                    <p>Administración de privilegios y sus accesos a menús y submenús.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="privilegios-toolbar">
                <div class="privilegios-toolbar-actions">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnActualizarPrivilegios">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnNuevoPrivilegio">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnExcelPrivilegios">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnPdfPrivilegios">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="privilegios-toolbar-tools">
                    <div class="privilegios-page-size">
                        <label for="privilegiosPageSize">Mostrar</label>
                        <select id="privilegiosPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </div>

                    <div class="privilegios-view-switch">
                        <button type="button" class="privilegios-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="privilegios-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="privilegios-search">
                        <span class="privilegios-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarPrivilegios" class="form-control" placeholder="Buscar privilegio..." autocomplete="off">
                        <button type="button" id="limpiarBuscarPrivilegios" class="privilegios-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="privilegiosListado" class="privilegios-listado vista-detalle"></div>
            <div id="privilegiosVacio" class="privilegios-state-box" style="display:none;">
                <i class="fas fa-user-shield"></i>
                <div>
                    <strong>Sin privilegios</strong>
                    <small>No se encontraron registros con los filtros actuales.</small>
                </div>
            </div>

            <div class="privilegios-list-footer">
                <span id="privilegiosInfo" class="privilegios-result-info">Mostrando 0 registros</span>
                <div id="privilegiosPaginacion" class="privilegios-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "privilegio";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Privilegios");
?>
