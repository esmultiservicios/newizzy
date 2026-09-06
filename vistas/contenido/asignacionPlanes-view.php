<link rel="stylesheet"
      href="<?php echo SERVERURL; ?>vistas/plantilla/css/asignacionPlanes.css">

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
