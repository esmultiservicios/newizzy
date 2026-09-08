<link rel="stylesheet"
      href="<?php echo SERVERURL; ?>vistas/plantilla/css/asignacionPlanes.css?v=<?php echo @filemtime(__DIR__ . '/../plantilla/css/asignacionPlanes.css') ?: time(); ?>">

<div class="container-fluid asignacion-planes-page" id="div_top">
    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
        <ol class="breadcrumb-harmony">
            <li class="breadcrumb-item">
                <a class="breadcrumb-link"
                   href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
                    <i class="fas fa-home breadcrumb-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-separator">/</li>
            <li class="breadcrumb-item active">
                <i class="fas fa-user-check breadcrumb-icon"></i>
                <span>Asignación de Planes</span>
            </li>
        </ol>
    </div>

    <!-- Gestión del plan -->
    <div class="card mb-4 asignacion-card asignacion-form-card">
        <div class="card-header asignacion-section-header">
            <div class="asignacion-section-heading">
                <div class="asignacion-section-icon">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div>
                    <strong>Asignar Plan a Cliente</strong>
                    <small class="d-block text-muted mt-1">
                        Cambie el plan, usuarios extra y parámetros de acceso del cliente.
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form id="formAsignacionPlan" autocomplete="off">
                <input type="hidden" id="server_customers_id" name="server_customers_id">

                <div class="form-row">
                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="cliente_id">
                            <i class="fas fa-building mr-1"></i> Seleccionar Cliente
                        </label>
                        <select class="form-control selectpicker"
                                id="cliente_id"
                                name="cliente_id"
                                data-live-search="true"
                                data-width="100%"
                                title="Buscar cliente..."
                                required>
                        </select>
                        <small class="form-text text-muted">Cliente al que se aplicará el cambio de plan.</small>
                    </div>

                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="planes_id">
                            <i class="fas fa-layer-group mr-1"></i> Seleccionar Plan
                        </label>
                        <select class="form-control selectpicker"
                                id="planes_id"
                                name="planes_id"
                                data-live-search="true"
                                data-width="100%"
                                title="Seleccione un plan"
                                required>
                        </select>
                        <small class="form-text text-muted">Plan que quedará activo para el cliente.</small>
                    </div>

                    <div class="form-group col-xl-2 col-lg-4 col-md-6">
                        <label for="user_extra">
                            <i class="fas fa-user-plus mr-1"></i> Usuarios Extras
                        </label>
                        <input type="number"
                               class="form-control"
                               id="user_extra"
                               name="user_extra"
                               min="0"
                               value="0"
                               required>
                        <small class="form-text text-muted">Usuarios adicionales al plan.</small>
                    </div>

                    <div class="form-group col-xl-2 col-lg-4 col-md-6">
                        <label for="validar">
                            <i class="fas fa-shield-alt mr-1"></i> Validar
                        </label>
                        <select class="form-control selectpicker"
                                id="validar"
                                name="validar"
                                data-width="100%"
                                required>
                            <option value="1">Sí</option>
                            <option value="2">No</option>
                        </select>
                        <small class="form-text text-muted">Control de validación del cliente.</small>
                    </div>

                    <div class="form-group col-xl-2 col-lg-4 col-md-6">
                        <label for="estado">
                            <i class="fas fa-toggle-on mr-1"></i> Estado
                        </label>
                        <select class="form-control selectpicker"
                                id="estado"
                                name="estado"
                                data-width="100%"
                                required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        <small class="form-text text-muted">Estado de la asignación.</small>
                    </div>

                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="sistema_id">
                            <i class="fas fa-cubes mr-1"></i> Sistema
                        </label>
                        <select class="form-control selectpicker"
                                id="sistema_id"
                                name="sistema_id"
                                data-width="100%"
                                disabled>
                        </select>
                        <small class="form-text text-muted">Sistema asignado (solo lectura).</small>
                    </div>
                </div>

                <div class="asignacion-form-actions">
                    <button type="submit"
                            class="btn btn-primary"
                            id="btn-asignar-plan">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 asignacion-card asignacion-collapsible-card">
        <div class="card-header asignacion-section-header">
            <div class="asignacion-section-heading">
                <div class="asignacion-section-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <div>
                    <strong>Filtros de Asignaciones</strong>
                    <small class="d-block text-muted mt-1">
                        Refine el listado por plan, sistema, validación y disponibilidad de la base de datos.
                    </small>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary asignacion-toggle-btn"
                    id="btn_toggle_asignacion_filtros">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="card-body" id="asignacion_filtros_body">
            <form id="formFiltrosAsignacion" autocomplete="off">
                <div class="form-row align-items-end">
                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="filtro_plan">
                            <i class="fas fa-layer-group mr-1"></i> Plan
                        </label>
                        <select id="filtro_plan"
                                class="form-control selectpicker"
                                data-live-search="true"
                                data-width="100%"
                                title="Todos los planes">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="filtro_sistema">
                            <i class="fas fa-cubes mr-1"></i> Sistema
                        </label>
                        <select id="filtro_sistema"
                                class="form-control selectpicker"
                                data-live-search="true"
                                data-width="100%"
                                title="Todos los sistemas">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="filtro_validar">
                            <i class="fas fa-shield-alt mr-1"></i> Validación
                        </label>
                        <select id="filtro_validar"
                                class="form-control selectpicker"
                                data-width="100%"
                                title="Todos">
                            <option value="">Todos</option>
                            <option value="1">Sí</option>
                            <option value="2">No</option>
                        </select>
                    </div>

                    <div class="form-group col-xl-3 col-lg-4 col-md-6">
                        <label for="filtro_db">
                            <i class="fas fa-database mr-1"></i> Base del Cliente
                        </label>
                        <select id="filtro_db"
                                class="form-control selectpicker"
                                data-width="100%"
                                title="Todas">
                            <option value="">Todas</option>
                            <option value="1">Disponible</option>
                            <option value="0">No disponible</option>
                        </select>
                    </div>
                </div>

                <div class="asignacion-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-broom mr-1"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="card mb-4 asignacion-card asignacion-collapsible-card">
        <div class="card-header asignacion-section-header">
            <div class="asignacion-section-heading">
                <div class="asignacion-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <strong>Resumen de Asignaciones</strong>
                    <small class="d-block text-muted mt-1">
                        Indicadores calculados sobre las asignaciones visibles según los filtros.
                    </small>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary asignacion-toggle-btn"
                    id="btn_toggle_asignacion_kpis">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="card-body" id="asignacion_kpis_body">
            <div class="row asignacion-kpis-row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="asignacion-kpi-card asignacion-kpi-total">
                        <div>
                            <span class="asignacion-kpi-label">
                                <i class="fas fa-users-cog mr-1"></i> Asignaciones
                            </span>
                            <h3 id="kpi_asignaciones_total">0</h3>
                            <p>Clientes visibles</p>
                        </div>
                        <div class="asignacion-kpi-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="asignacion-kpi-card asignacion-kpi-planes">
                        <div>
                            <span class="asignacion-kpi-label">
                                <i class="fas fa-layer-group mr-1"></i> Planes en uso
                            </span>
                            <h3 id="kpi_planes_uso">0</h3>
                            <p>Planes distintos</p>
                        </div>
                        <div class="asignacion-kpi-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="asignacion-kpi-card asignacion-kpi-users">
                        <div>
                            <span class="asignacion-kpi-label">
                                <i class="fas fa-user-plus mr-1"></i> Usuarios extra
                            </span>
                            <h3 id="kpi_usuarios_extra">0</h3>
                            <p>Total adicional</p>
                        </div>
                        <div class="asignacion-kpi-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="asignacion-kpi-card asignacion-kpi-db">
                        <div>
                            <span class="asignacion-kpi-label">
                                <i class="fas fa-database mr-1"></i> Bases disponibles
                            </span>
                            <h3 id="kpi_bases_disponibles">0</h3>
                            <p>Conexión confirmada</p>
                        </div>
                        <div class="asignacion-kpi-icon">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado por DIVs -->
    <div class="card mb-4 asignacion-card asignacion-list-card">
        <div class="card-header asignacion-section-header">
            <div class="asignacion-section-heading">
                <div class="asignacion-section-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div>
                    <strong>Asignaciones Actuales</strong>
                    <small class="d-block text-muted mt-1">
                        Plan, sistema, usuarios extra, validación y estado de sincronización por cliente.
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="asignacion-toolbar">
                <div class="asignacion-toolbar-actions">
                    <button type="button"
                            class="btn btn-secondary table_actualizar ocultar"
                            id="btn_actualizar_asignaciones">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button"
                            class="btn btn-success table_reportes ocultar"
                            id="btn_exportar_asignaciones_excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button"
                            class="btn btn-danger table_reportes ocultar"
                            id="btn_exportar_asignaciones_pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="asignacion-toolbar-right">
                    <div class="asignacion-page-size">
                        <label for="asignacion_page_size">Mostrar</label>
                        <select id="asignacion_page_size"
                                class="form-control form-control-sm">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <span>registros</span>
                    </div>

                    <div class="asignacion-view-switch" role="group" aria-label="Tipo de vista">
                        <button type="button"
                                class="btn asignacion-view-btn active"
                                id="btn_vista_detalle"
                                data-view="detalle"
                                title="Vista detalle">
                            <i class="fas fa-list-ul mr-1"></i> Detalle
                        </button>
                        <button type="button"
                                class="btn asignacion-view-btn"
                                id="btn_vista_miniatura"
                                data-view="miniatura"
                                title="Vista miniatura">
                            <i class="fas fa-th-large mr-1"></i> Miniatura
                        </button>
                    </div>

                    <div class="asignacion-search">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="search"
                                   id="buscar_asignacion"
                                   class="form-control"
                                   placeholder="Buscar asignación...">
                        </div>
                    </div>
                </div>
            </div>

            <div id="asignacion_loading"
                 class="asignacion-state-box d-none"
                 role="status"
                 aria-live="polite">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Cargando asignaciones...</span>
            </div>

            <div id="asignacion_empty"
                 class="asignacion-state-box d-none"
                 role="status"
                 aria-live="polite">
                <i class="fas fa-inbox"></i>
                <div>
                    <strong>No se encontraron asignaciones</strong>
                    <small>Modifique los filtros o la búsqueda para ver otros resultados.</small>
                </div>
            </div>

            <div id="asignacion_listado"
                 class="asignacion-listado"
                 aria-live="polite"></div>

            <div class="asignacion-list-footer">
                <div id="asignacion_resultado_info"
                     class="asignacion-resultado-info">
                    Mostrando 0 registros
                </div>

                <nav id="asignacion_paginacion"
                     class="asignacion-paginacion"
                     aria-label="Paginación de asignaciones"></nav>
            </div>
        </div>
    </div>
</div>


<!-- Administración central de colaboradores, usuarios y empresas por cliente -->
<div class="modal" id="modalAdministrarCliente" tabindex="-1" role="dialog" aria-labelledby="modalAdministrarClienteTitulo" aria-hidden="true" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog ap-admin-main-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalAdministrarClienteTitulo">
                        <i class="fas fa-users-cog mr-2"></i>Administración del Cliente
                    </h5>
                    <div class="ap-modal-subtitle">Colaboradores, usuarios, empresas, secuencias y documentos del cliente seleccionado.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ap_admin_server_customer_id" value="">
                <div id="ap_admin_loading" class="ap-loading d-none"><i class="fas fa-spinner fa-spin"></i> Cargando información del cliente...</div>
                <div id="ap_admin_content">
                    <div class="ap-client-hero">
                        <div class="ap-client-hero-main">
                            <div class="ap-client-icon"><i class="fas fa-building"></i></div>
                            <div>
                                <strong id="ap_cliente_nombre">Cliente</strong>
                                <div class="ap-client-meta">
                                    <span><i class="fas fa-id-card mr-1"></i><span id="ap_cliente_rtn">—</span></span>
                                    <span><i class="fas fa-barcode mr-1"></i><span id="ap_cliente_codigo">—</span></span>
                                    <span><i class="fas fa-database mr-1"></i><span id="ap_cliente_db">—</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="ap-client-badges">
                            <span class="ap-chip"><i class="fas fa-user-friends"></i><span id="ap_total_colaboradores">0</span> colaboradores</span>
                            <span class="ap-chip secondary"><i class="fas fa-user-shield"></i><span id="ap_total_usuarios">0</span> usuarios</span>
                            <span class="ap-chip ap-limit-chip"><i class="fas fa-user-lock"></i><span id="ap_limite_usuarios_resumen">0 / 0 usuarios</span></span>
                            <span class="ap-chip ap-limit-chip company"><i class="fas fa-building"></i><span id="ap_limite_empresas_resumen">0 / 0 empresas</span></span>
                        </div>
                    </div>

                    <div class="ap-admin-tabs" role="tablist">
                        <button type="button" class="ap-admin-tab active" data-ap-tab="colaboradores"><i class="fas fa-users mr-1"></i> Colaboradores y Usuarios</button>
                        <button type="button" class="ap-admin-tab" data-ap-tab="empresas"><i class="fas fa-building mr-1"></i> Empresas</button>
                        <button type="button" class="ap-admin-tab" data-ap-tab="secuencias"><i class="fas fa-file-invoice mr-1"></i> Secuencias</button>
                    </div>

                    <section class="ap-admin-panel active" id="ap_panel_colaboradores">
                        <div class="ap-access-subtabs" role="tablist" aria-label="Administración de accesos">
                            <button type="button" class="ap-access-subtab active" data-ap-access-tab="directory"><i class="fas fa-address-book mr-1"></i> Directorio</button>
                            <button type="button" class="ap-access-subtab" data-ap-access-tab="privileges"><i class="fas fa-user-shield mr-1"></i> Privilegios</button>
                            <button type="button" class="ap-access-subtab" data-ap-access-tab="permissions"><i class="fas fa-key mr-1"></i> Tipos de Usuario / Permisos</button>
                        </div>

                        <div class="ap-access-subpanel active" id="ap_access_directory">
                            <div class="ap-section-card">
                                <div class="ap-section-head">
                                    <h5><i class="fas fa-users-cog mr-1"></i> Colaboradores y accesos</h5>
                                    <button type="button" class="btn btn-primary btn-sm" id="ap_btn_nuevo_usuario"><i class="fas fa-user-plus mr-1"></i> Nuevo usuario / colaborador</button>
                                </div>
                                <div class="ap-toolbar">
                                    <div class="ap-toolbar-left">
                                        <button type="button" class="btn btn-secondary btn-sm" id="ap_btn_actualizar"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
                                        <button type="button" class="btn btn-success btn-sm" id="ap_btn_excel"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                                        <button type="button" class="btn btn-danger btn-sm" id="ap_btn_pdf"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                                    </div>
                                    <div class="ap-toolbar-right">
                                        <div class="ap-status-filter ap-collaborator-status-filter" id="ap_collaborator_status_filter" aria-label="Filtrar colaboradores por estado">
                                            <button type="button" class="active" data-collaborator-status="1"><i class="fas fa-check-circle mr-1"></i>Activos</button>
                                            <button type="button" data-collaborator-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivos</button>
                                            <button type="button" data-collaborator-status="all"><i class="fas fa-list mr-1"></i>Todos</button>
                                        </div>
                                        <div class="ap-page-size"><label class="mb-0" for="ap_page_size">Mostrar</label><select id="ap_page_size" class="form-control form-control-sm"><option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="50">50</option></select><span>registros</span></div>
                                        <div class="ap-view-switch"><button type="button" class="active" data-ap-view="detail"><i class="fas fa-list-ul mr-1"></i>Detalle</button><button type="button" data-ap-view="mini"><i class="fas fa-th-large mr-1"></i>Miniatura</button></div>
                                        <div class="ap-search"><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div><input type="search" id="ap_search" class="form-control" placeholder="Buscar colaborador, correo, identidad..."></div></div>
                                    </div>
                                </div>
                                <div id="ap_collaborator_list" class="ap-list ap-detail"></div>
                                <div class="ap-list-footer"><div id="ap_result_info" class="text-muted small">Mostrando 0 registros</div><div id="ap_pagination" class="ap-pagination"></div></div>
                            </div>
                        </div>

                        <div class="ap-access-subpanel" id="ap_access_privileges">
                            <div class="ap-section-card">
                                <div class="ap-section-head ap-filterable-head">
                                    <div>
                                        <h5><i class="fas fa-user-shield mr-1"></i> Privilegios y navegación</h5>
                                        <small class="ap-section-note">Defina qué menús, submenús y submenús de nivel 2 puede utilizar cada privilegio.</small>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="ap_btn_nuevo_privilegio"><i class="fas fa-plus-circle mr-1"></i> Nuevo privilegio</button>
                                </div>
                                <div class="ap-toolbar">
                                    <div class="ap-toolbar-left">
                                        <button type="button" class="btn btn-secondary btn-sm" id="ap_btn_actualizar_privilegios"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
                                        <button type="button" class="btn btn-success btn-sm" id="ap_btn_excel_privilegios"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                                        <button type="button" class="btn btn-danger btn-sm" id="ap_btn_pdf_privilegios"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                                    </div>
                                    <div class="ap-toolbar-right">
                                        <div class="ap-status-filter" id="ap_privilege_status_filter" aria-label="Filtrar privilegios por estado">
                                            <button type="button" class="active" data-privilege-status="1"><i class="fas fa-check-circle mr-1"></i>Activos</button>
                                            <button type="button" data-privilege-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivos</button>
                                            <button type="button" data-privilege-status="all"><i class="fas fa-list mr-1"></i>Todos</button>
                                        </div>
                                        <div class="ap-view-switch" id="ap_privilege_view_switch">
                                            <button type="button" class="active" data-privilege-view="detail"><i class="fas fa-list mr-1"></i>Detalle</button>
                                            <button type="button" data-privilege-view="mini"><i class="fas fa-th-large mr-1"></i>Miniatura</button>
                                        </div>
                                        <div class="ap-search"><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div><input type="search" id="ap_privilege_search" class="form-control" placeholder="Buscar privilegio..."></div></div>
                                    </div>
                                </div>
                                <div class="ap-access-summary">
                                    <div><i class="fas fa-user-shield"></i><strong id="ap_privilege_total">0</strong><small>Privilegios</small></div>
                                    <div><i class="fas fa-bars"></i><strong id="ap_privilege_menu_total">0</strong><small>Menús asignados</small></div>
                                    <div><i class="fas fa-users"></i><strong id="ap_privilege_user_total">0</strong><small>Usuarios vinculados</small></div>
                                </div>
                                <div id="ap_privilege_list" class="ap-access-list"></div>
                                <div id="ap_privilege_empty" class="ap-empty d-none"><i class="fas fa-user-shield"></i><strong class="d-block">No hay privilegios para mostrar</strong></div>
                            </div>
                        </div>

                        <div class="ap-access-subpanel" id="ap_access_permissions">
                            <div class="ap-section-card">
                                <div class="ap-section-head ap-filterable-head">
                                    <div>
                                        <h5><i class="fas fa-key mr-1"></i> Tipos de Usuario y permisos</h5>
                                        <small class="ap-section-note">Configure perfiles de acciones y asígnelos a los usuarios desde su formulario.</small>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="ap_btn_nuevo_tipo_usuario"><i class="fas fa-plus-circle mr-1"></i> Nuevo tipo de usuario</button>
                                </div>
                                <div class="ap-toolbar">
                                    <div class="ap-toolbar-left">
                                        <button type="button" class="btn btn-secondary btn-sm" id="ap_btn_actualizar_tipos"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
                                        <button type="button" class="btn btn-success btn-sm" id="ap_btn_excel_tipos"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                                        <button type="button" class="btn btn-danger btn-sm" id="ap_btn_pdf_tipos"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                                    </div>
                                    <div class="ap-toolbar-right">
                                        <div class="ap-status-filter" id="ap_type_status_filter" aria-label="Filtrar tipos de usuario por estado">
                                            <button type="button" class="active" data-type-status="1"><i class="fas fa-check-circle mr-1"></i>Activos</button>
                                            <button type="button" data-type-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivos</button>
                                            <button type="button" data-type-status="all"><i class="fas fa-list mr-1"></i>Todos</button>
                                        </div>
                                        <div class="ap-view-switch" id="ap_type_view_switch">
                                            <button type="button" class="active" data-type-view="detail"><i class="fas fa-list mr-1"></i>Detalle</button>
                                            <button type="button" data-type-view="mini"><i class="fas fa-th-large mr-1"></i>Miniatura</button>
                                        </div>
                                        <div class="ap-search"><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div><input type="search" id="ap_type_search" class="form-control" placeholder="Buscar tipo de usuario..."></div></div>
                                    </div>
                                </div>
                                <div class="ap-access-summary">
                                    <div><i class="fas fa-user-tag"></i><strong id="ap_type_total">0</strong><small>Tipos de usuario</small></div>
                                    <div><i class="fas fa-check-double"></i><strong id="ap_type_permission_total">0</strong><small>Permisos activos</small></div>
                                    <div><i class="fas fa-users"></i><strong id="ap_type_user_total">0</strong><small>Usuarios vinculados</small></div>
                                </div>
                                <div id="ap_type_list" class="ap-access-list"></div>
                                <div id="ap_type_empty" class="ap-empty d-none"><i class="fas fa-key"></i><strong class="d-block">No hay tipos de usuario para mostrar</strong></div>
                            </div>
                        </div>
                    </section>

                    <section class="ap-admin-panel" id="ap_panel_empresas">
                        <div class="ap-section-card">
                            <div class="ap-section-head ap-filterable-head">
                                <div>
                                    <h5><i class="fas fa-building mr-1"></i> Empresas del cliente</h5>
                                    <small class="ap-section-note" id="ap_company_limit_note">Límite según plan: —</small>
                                </div>
                                <div class="ap-head-actions ap-company-toolbar">
                                    <div class="ap-status-filter" id="ap_company_status_filter" aria-label="Filtrar empresas por estado">
                                        <button type="button" class="active" data-company-status="1"><i class="fas fa-check-circle mr-1"></i>Activas</button>
                                        <button type="button" data-company-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivas</button>
                                        <button type="button" data-company-status="all"><i class="fas fa-list mr-1"></i>Todas</button>
                                    </div>
                                    <div class="ap-view-switch ap-company-view-switch" aria-label="Vista de empresas">
                                        <button type="button" class="active" data-company-view="detail"><i class="fas fa-list mr-1"></i>Detalle</button>
                                        <button type="button" data-company-view="mini"><i class="fas fa-th-large mr-1"></i>Miniatura</button>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" id="ap_btn_excel_empresas"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                                    <button type="button" class="btn btn-danger btn-sm" id="ap_btn_pdf_empresas"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="ap_btn_nueva_empresa"><i class="fas fa-plus-circle mr-1"></i> Nueva empresa</button>
                                </div>
                            </div>
                            <div id="ap_company_list" class="ap-list"><div class="ap-company-grid"></div></div>
                        </div>
                    </section>

                    <section class="ap-admin-panel" id="ap_panel_secuencias">
                        <div class="ap-section-card">
                            <div class="ap-section-head ap-sequence-head">
                                <div>
                                    <h5><i class="fas fa-file-invoice mr-1"></i> Secuencias y documentos de facturación</h5>
                                    <small class="ap-section-note">La información se consulta directamente en la base de datos del cliente que está administrando.</small>
                                </div>
                                <div class="ap-head-actions">
                                    <div class="ap-status-filter" id="ap_sequence_status_filter" aria-label="Filtrar secuencias por estado">
                                        <button type="button" class="active" data-sequence-status="1"><i class="fas fa-check-circle mr-1"></i>Activas</button>
                                        <button type="button" data-sequence-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivas</button>
                                        <button type="button" data-sequence-status="all"><i class="fas fa-list mr-1"></i>Todas</button>
                                    </div>
                                    <div class="ap-view-switch ap-sequence-view-switch" aria-label="Vista de secuencias">
                                        <button type="button" class="active" data-sequence-view="detail"><i class="fas fa-list mr-1"></i>Detalle</button>
                                        <button type="button" data-sequence-view="mini"><i class="fas fa-th-large mr-1"></i>Miniatura</button>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" id="ap_btn_excel_secuencias"><i class="fas fa-file-excel mr-1"></i> Excel</button>
                                    <button type="button" class="btn btn-danger btn-sm" id="ap_btn_pdf_secuencias"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
                                    <div class="dropdown ap-sequence-main-actions">
                                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" id="ap_sequence_main_actions"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog mr-1"></i> Acciones
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ap_sequence_main_actions">
                                        <button type="button" class="dropdown-item" id="ap_btn_nueva_secuencia">
                                            <i class="fas fa-plus-circle mr-2"></i>Nueva secuencia
                                        </button>
                                        <button type="button" class="dropdown-item" id="ap_btn_documentos_secuencia">
                                            <i class="fas fa-folder-open mr-2"></i>Documentos
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item" id="ap_btn_actualizar_secuencias">
                                            <i class="fas fa-sync-alt mr-2"></i>Actualizar
                                        </button>
                                    </div>
                                </div>
                                </div>
                            </div>

                            <div class="ap-billing-summary">
                                <div class="ap-billing-summary-card">
                                    <span class="ap-billing-summary-icon"><i class="fas fa-file-invoice"></i></span>
                                    <div><strong id="ap_total_secuencias">0</strong><small>Secuencias</small></div>
                                </div>
                                <div class="ap-billing-summary-card">
                                    <span class="ap-billing-summary-icon"><i class="fas fa-folder-open"></i></span>
                                    <div><strong id="ap_total_documentos">0</strong><small>Documentos</small></div>
                                </div>
                                <div class="ap-billing-summary-card">
                                    <span class="ap-billing-summary-icon"><i class="fas fa-building"></i></span>
                                    <div><strong id="ap_total_empresas_facturacion">0</strong><small>Empresas</small></div>
                                </div>
                            </div>

                            <div id="ap_billing_loading" class="ap-loading d-none">
                                <i class="fas fa-spinner fa-spin"></i> Consultando secuencias y documentos del cliente...
                            </div>
                            <div id="ap_sequence_list" class="ap-sequence-list"></div>
                            <div id="ap_sequence_empty" class="ap-empty d-none">
                                <i class="fas fa-file-invoice"></i>
                                <strong class="d-block">No hay secuencias registradas</strong>
                                <small>Puede crear la primera secuencia desde el menú Acciones.</small>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cerrar</button></div>
        </div>
    </div>
</div>

<!-- Modal colaborador -->
<div class="modal" id="modalApColaborador" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="true">
 <div class="modal-dialog ap-admin-child-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i><span id="ap_collab_modal_title">Colaborador</span></h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
 <form id="ap_form_collaborator" class="ap-admin-modal-form" autocomplete="off"><div class="modal-body"><input type="hidden" name="colaboradores_id" id="ap_colaboradores_id"><div class="ap-form-grid">
  <div class="form-group full"><label>Nombre completo <span class="text-danger">*</span></label><input type="text" class="form-control" name="nombre" id="ap_colab_nombre" required maxlength="100"></div>
  <div class="form-group"><label>Identidad</label><input type="text" class="form-control" name="identidad" id="ap_colab_identidad" maxlength="13"><div class="ap-help">Si se deja vacía se genera una identificación interna.</div></div>
  <div class="form-group"><label>Teléfono</label><input type="text" class="form-control" name="telefono" id="ap_colab_telefono" maxlength="8"></div>
  <div class="form-group"><label>Fecha de ingreso <span class="text-danger">*</span></label><input type="date" class="form-control" name="fecha_ingreso" id="ap_colab_fecha" required></div>
  <div class="form-group"><label>Puesto <span class="text-danger">*</span></label><select class="form-control ap-select2" name="puestos_id" id="ap_colab_puesto" required></select></div>
  <div class="form-group"><label>Empresa <span class="text-danger">*</span></label><select class="form-control ap-select2" name="empresa_id" id="ap_colab_empresa" required></select></div>
  <div class="form-group ap-status-field">
    <label class="ap-field-label">Estado</label>
    <input type="hidden" name="estado" id="ap_colab_estado" value="1">
    <div class="ap-status-control">
        <label class="ap-status-switch" for="ap_colab_estado_toggle">
            <input type="checkbox" id="ap_colab_estado_toggle" checked>
            <span class="ap-status-slider"></span>
            <span class="ap-status-label">Activo</span>
        </label>
    </div>
</div>
 </div></div><div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar colaborador</button></div></form></div></div>
</div>
