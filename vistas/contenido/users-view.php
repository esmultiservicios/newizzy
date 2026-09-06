<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/users.css">

<div class="container-fluid usuarios-page">
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
                <i class="fas fa-users-cog breadcrumb-icon"></i>
                <span>Usuarios</span>
            </li>
        </ol>
    </div>

    <section class="card usuarios-section-card mb-4" id="usuariosFiltrosCard">
        <div class="card-header usuarios-section-header">
            <div>
                <h5 class="mb-1"><i class="fas fa-sliders-h mr-2"></i>Filtros de usuarios</h5>
                <small>Refine el directorio por estado, tipo, privilegio o búsqueda rápida.</small>
            </div>
            <button type="button" class="btn btn-secondary btn-sm usuarios-toggle-btn" id="btnToggleFiltrosUsuarios" aria-expanded="false">
                <i class="fas fa-chevron-down mr-1"></i>
                <span>Mostrar</span>
            </button>
        </div>
        <div class="card-body usuarios-collapsible" id="usuariosFiltrosContenido" style="display:none;">
            <form id="form_main_usuarios">
                <div class="row align-items-end">
                    <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
                        <label class="usuarios-filter-label" for="estado_usuarios">
                            <i class="fas fa-toggle-on mr-1"></i>Estado
                        </label>
                        <select id="estado_usuarios" name="estado_usuarios" class="form-control selectpicker" title="Todos" data-width="100%">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
                        <label class="usuarios-filter-label" for="filtroTipoUsuario">
                            <i class="fas fa-user-tag mr-1"></i>Tipo de usuario
                        </label>
                        <select id="filtroTipoUsuario" class="form-control selectpicker" title="Todos" data-width="100%">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
                        <label class="usuarios-filter-label" for="filtroPrivilegioUsuario">
                            <i class="fas fa-user-shield mr-1"></i>Privilegio
                        </label>
                        <select id="filtroPrivilegioUsuario" class="form-control selectpicker" title="Todos" data-width="100%">
                            <option value="">Todos</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end flex-wrap usuarios-filter-actions">
                    <button type="submit" class="btn btn-primary mr-2 mb-2" id="search">
                        <i class="fas fa-filter mr-1"></i>Filtrar
                    </button>
                    <button type="reset" class="btn btn-secondary mb-2">
                        <i class="fas fa-broom mr-1"></i>Limpiar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card usuarios-section-card mb-4" id="usuariosResumenCard">
        <div class="card-header usuarios-section-header">
            <div>
                <h5 class="mb-1"><i class="fas fa-chart-pie mr-2"></i>Resumen de usuarios</h5>
                <small>Indicadores calculados sobre los registros filtrados.</small>
            </div>
            <button type="button" class="btn btn-secondary btn-sm usuarios-toggle-btn" id="btnToggleResumenUsuarios" aria-expanded="false">
                <i class="fas fa-chevron-down mr-1"></i>
                <span>Mostrar</span>
            </button>
        </div>
        <div class="card-body usuarios-collapsible" id="usuariosResumenContenido" style="display:none;">
            <div class="row usuarios-kpi-row">
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <article class="usuarios-kpi usuarios-kpi-primary">
                        <div class="usuarios-kpi-copy">
                            <span class="usuarios-kpi-label"><i class="fas fa-users mr-1"></i>Registros</span>
                            <strong id="usuariosKpiRegistros">0</strong>
                            <small>Usuarios filtrados</small>
                        </div>
                        <span class="usuarios-kpi-icon"><i class="fas fa-users"></i></span>
                    </article>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <article class="usuarios-kpi usuarios-kpi-success">
                        <div class="usuarios-kpi-copy">
                            <span class="usuarios-kpi-label"><i class="fas fa-check-circle mr-1"></i>Activos</span>
                            <strong id="usuariosKpiActivos">0</strong>
                            <small>Cuentas habilitadas</small>
                        </div>
                        <span class="usuarios-kpi-icon"><i class="fas fa-user-check"></i></span>
                    </article>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-md-0">
                    <article class="usuarios-kpi usuarios-kpi-danger">
                        <div class="usuarios-kpi-copy">
                            <span class="usuarios-kpi-label"><i class="fas fa-times-circle mr-1"></i>Inactivos</span>
                            <strong id="usuariosKpiInactivos">0</strong>
                            <small>Cuentas deshabilitadas</small>
                        </div>
                        <span class="usuarios-kpi-icon"><i class="fas fa-user-slash"></i></span>
                    </article>
                </div>
                <div class="col-xl-3 col-md-6">
                    <article class="usuarios-kpi usuarios-kpi-info">
                        <div class="usuarios-kpi-copy">
                            <span class="usuarios-kpi-label"><i class="fas fa-user-shield mr-1"></i>Administradores</span>
                            <strong id="usuariosKpiAdministradores">0</strong>
                            <small>Acceso administrativo</small>
                        </div>
                        <span class="usuarios-kpi-icon"><i class="fas fa-user-cog"></i></span>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="card usuarios-section-card usuarios-panel mb-4">
        <div class="card-header usuarios-directory-header">
            <div>
                <h5 class="mb-1"><i class="fas fa-users-cog mr-2"></i>Usuarios</h5>
                <small>Gestión de accesos, permisos, privilegios y estado de las cuentas.</small>
            </div>
        </div>

        <div class="card-body usuarios-directory-body">
            <div class="usuarios-list-toolbar">
                <div class="usuarios-toolbar d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-info table_actualizar ocultar mr-2 mb-2" id="btnActualizarUsuarios">
                        <i class="fas fa-sync-alt mr-1"></i>Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar mr-2 mb-2" id="btnNuevoUsuario">
                        <i class="fas fa-plus mr-1"></i>Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar mr-2 mb-2" id="btnExcelUsuarios">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar mb-2" id="btnPdfUsuarios">
                        <i class="fas fa-file-pdf mr-1"></i>PDF
                    </button>
                </div>

                <div class="usuarios-list-tools-right">
                    <div class="usuarios-page-size">
                        <label for="usuariosPageSize">Mostrar</label>
                        <select id="usuariosPageSize" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </div>

                    <div class="usuarios-view-switch"
                         role="group"
                         aria-label="Tipo de vista de usuarios">
                        <button type="button"
                                class="usuarios-view-btn active"
                                data-view="detalle"
                                title="Vista detalle"
                                aria-pressed="true">
                            <i class="fas fa-list-ul"></i>
                            <span>Detalle</span>
                        </button>

                        <button type="button"
                                class="usuarios-view-btn"
                                data-view="miniatura"
                                title="Vista miniatura"
                                aria-pressed="false">
                            <i class="fas fa-th-large"></i>
                            <span>Miniatura</span>
                        </button>
                    </div>

                    <div class="usuarios-search">
                        <label class="sr-only" for="buscarUsuarios">Buscar usuario</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="search"
                                   id="buscarUsuarios"
                                   class="form-control"
                                   placeholder="Buscar usuario..."
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="usuariosListado" class="usuarios-listado"></div>
            <div id="usuariosVacio" class="usuarios-empty" style="display:none;">
                <i class="fas fa-users"></i>
                <strong>No se encontraron usuarios.</strong>
                <span>Pruebe ajustando los filtros o la búsqueda.</span>
            </div>

            <div class="usuarios-list-footer">
                <div id="usuariosInfo" class="small text-muted mb-2"></div>
                <nav aria-label="Paginación de usuarios">
                    <ul class="pagination mb-0" id="usuariosPaginacion"></ul>
                </nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "users";
                if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>

    <?php $insMainModel->guardar_historial_accesos("Ingreso al modulo Usuarios"); ?>
</div>
