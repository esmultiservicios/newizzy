<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/registrarPlanes.css">

<div class="container-fluid planes-page">
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
                <i class="fas fa-tasks breadcrumb-icon"></i>
                <span>Administrar Planes</span>
            </li>
        </ol>
    </div>

    <div class="card mb-4 planes-form-card" id="cardFormularioPlan">
        <div class="planes-section-header">
            <div class="planes-section-title">
                <div class="planes-section-icon"><i class="fas fa-edit"></i></div>
                <div>
                    <h5 id="form-title">Registrar Nuevo Plan</h5>
                    <p>Defina el nombre, estado y cantidades/configuraciones permitidas por el plan.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form id="formulario_plan">
                <div class="form-row align-items-end">
                    <div class="form-group col-lg-5 col-md-6 mb-3">
                        <label for="nombre_plan">Nombre del Plan</label>
                        <input type="text" class="form-control" id="nombre_plan" name="nombre_plan" required maxlength="40" autofocus>
                    </div>

                    <div class="form-group col-lg-3 col-md-6 mb-3">
                        <label for="estado_plan">Estado</label>
                        <div class="d-flex align-items-center planes-switch-line">
                            <label class="switch mb-0 mr-2">
                                <input type="checkbox" id="estado_plan" name="estado_plan" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span id="estado_label" class="font-weight-bold mb-0">Activo</span>
                        </div>
                    </div>

                    <div class="form-group col-lg-4 col-md-12 mb-3 text-right">
                        <button type="button" id="btn-cancelar-edicion" class="btn btn-secondary" style="display:none;">
                            <i class="fas fa-times-circle mr-1"></i> Cancelar edición
                        </button>
                    </div>
                </div>

                <div class="planes-config-block">
                    <div class="planes-config-heading">
                        <div>
                            <h6>Configuraciones adicionales</h6>
                            <p>Defina las cantidades permitidas para cada característica del plan.</p>
                        </div>

                        <button type="button" class="btn btn-info" id="agregar-configuracion">
                            <i class="fas fa-plus mr-1"></i> Agregar configuración
                        </button>
                    </div>

                    <div id="configuraciones-container"></div>
                </div>

                <div class="planes-form-footer">
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="fas fa-save mr-1"></i> Registrar Plan
                    </button>
                </div>

                <input type="hidden" name="plan_id" id="plan_id" value="">
            </form>
        </div>
    </div>


    <div class="card mb-4 planes-section-card">
        <div class="planes-section-header">
            <div class="planes-section-title">
                <div class="planes-section-icon"><i class="fas fa-filter"></i></div>
                <div>
                    <h5>Filtros de planes</h5>
                    <p>Refine los planes por estado o por configuración incluida.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary planes-toggle-btn"
                    id="btnToggleFiltrosPlanes"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="planes-section-body" id="planesFiltrosContenido">
            <form id="formFiltrosPlanes">
                <div class="row align-items-end">
                    <div class="col-xl-4 col-md-6 mb-3">
                        <label for="filtroEstadoPlanes" class="small mb-1">
                            <i class="fas fa-toggle-on mr-1"></i> Estado
                        </label>
                        <select id="filtroEstadoPlanes" class="form-control">
                            <option value="todos">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-3">
                        <label for="filtroConfiguracionPlanes" class="small mb-1">
                            <i class="fas fa-sliders-h mr-1"></i> Configuración
                        </label>
                        <select id="filtroConfiguracionPlanes" class="form-control">
                            <option value="">Todas las configuraciones</option>
                            <option value="usuarios">Usuarios</option>
                            <option value="clientes">Clientes</option>
                            <option value="proveedores">Proveedores</option>
                            <option value="productos">Productos</option>
                            <option value="facturas">Facturas</option>
                            <option value="compras">Compras</option>
                            <option value="cotizaciones">Cotizaciones</option>
                            <option value="perfiles">Puntos de Venta</option>
                            <option value="almacenes">Almacenes</option>
                            <option value="categorias">Categorías</option>
                            <option value="colaboradores">Colaboradores</option>
                            <option value="ubicaciones">Ubicaciones</option>
                            <option value="contratos">Contratos</option>
                            <option value="cuentas">Cuentas Contables</option>
                            <option value="ingresos">Ingresos Contables</option>
                            <option value="egresos">Egresos Contables</option>
                            <option value="secuencia">Secuencias de Facturación</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-12 mb-3">
                        <div class="planes-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" class="btn btn-secondary" id="btnLimpiarFiltrosPlanes">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 planes-section-card">
        <div class="planes-section-header">
            <div class="planes-section-title">
                <div class="planes-section-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h5>Resumen de planes</h5>
                    <p>Indicadores calculados sobre los registros actualmente filtrados.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary planes-toggle-btn"
                    id="btnToggleKpisPlanes"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="planes-section-body" id="planesKpisContenido">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="planes-kpi-card planes-kpi-registros">
                        <div>
                            <span class="planes-kpi-label"><i class="fas fa-list-ul mr-1"></i> Registros</span>
                            <h3 id="planesKpiRegistros">0</h3>
                            <p>Planes filtrados</p>
                        </div>
                        <div class="planes-kpi-icon"><i class="fas fa-layer-group"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="planes-kpi-card planes-kpi-activos">
                        <div>
                            <span class="planes-kpi-label"><i class="fas fa-check-circle mr-1"></i> Activos</span>
                            <h3 id="planesKpiActivos">0</h3>
                            <p>Planes disponibles</p>
                        </div>
                        <div class="planes-kpi-icon"><i class="fas fa-toggle-on"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="planes-kpi-card planes-kpi-config">
                        <div>
                            <span class="planes-kpi-label"><i class="fas fa-sliders-h mr-1"></i> Configurados</span>
                            <h3 id="planesKpiConfigurados">0</h3>
                            <p>Con límites/configuraciones</p>
                        </div>
                        <div class="planes-kpi-icon"><i class="fas fa-cogs"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="planes-kpi-card planes-kpi-accesos">
                        <div>
                            <span class="planes-kpi-label"><i class="fas fa-link mr-1"></i> Accesos asignados</span>
                            <h3 id="planesKpiAccesos">0</h3>
                            <p>Menús y submenús habilitados</p>
                        </div>
                        <div class="planes-kpi-icon"><i class="fas fa-sitemap"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 planes-list-card">
        <div class="planes-section-header">
            <div class="planes-section-title">
                <div class="planes-section-icon"><i class="fas fa-ranking-star"></i></div>
                <div>
                    <h5>Planes registrados</h5>
                    <p>Configuraciones, accesos, estado y administración de cada plan.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="planes-list-toolbar">
                <div class="planes-list-actions">
                    <button type="button" class="btn btn-info table_actualizar ocultar" id="btnActualizarPlanes">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnIngresarPlan">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnExcelPlanes">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnPdfPlanes">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="planes-list-tools">
                    <div class="planes-page-size">
                        <label for="planesPageSize">Mostrar</label>
                        <select id="planesPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </div>

                    <div class="planes-view-switch" role="group" aria-label="Tipo de vista de planes">
                        <button type="button" class="planes-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list-ul"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="planes-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="planes-search-box">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="search"
                                   id="buscarPlanes"
                                   class="form-control"
                                   placeholder="Buscar plan..."
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="planesListado" class="planes-listado vista-detalle"></div>

            <div id="planesVacio" class="planes-empty-state" style="display:none;">
                <i class="fas fa-layer-group"></i>
                <h5>No se encontraron planes</h5>
                <p>No hay registros que coincidan con los filtros aplicados.</p>
            </div>

            <div class="planes-list-footer">
                <div id="planesInfo" class="planes-list-info">0 registros</div>
                <nav id="planesPaginacion" class="planes-pagination" aria-label="Paginación de planes"></nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "planes";

                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha = htmlspecialchars($consulta['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = date('g:i:s a', strtotime($fecha));
                    echo "Última actualización: " . $insMainModel->getTheDay($fecha, $hora);
                } else {
                    echo "No hay registros recientes";
                }
            ?>
        </div>
    </div>


</div>

<!-- Modal para configuraciones -->
<div class="modal fade" id="modalConfiguraciones">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Configuraciones del Plan</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tablaConfiguraciones" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th>Configuración</th>
                                <th>Cantidad</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Asignar Menús Principales -->
<div class="modal fade" id="modalAsignarMenus">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Asignar Menús Principales</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="plan_id_menus" value="">
                <div class="table-responsive">
                    <table id="tablaMenus" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Nombre del Menú</th>
                                <th width="15%">Estado</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMenus"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>

<!-- Modal para Asignar Submenús Nivel 1 -->
<div class="modal fade" id="modalAsignarSubmenus">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Asignar Submenús Nivel 1</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="plan_id_submenus" value="">
                <div class="table-responsive">
                    <table id="tablaSubmenus" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Menú Padre</th>
                                <th>Nombre del Submenú</th>
                                <th width="15%">Estado</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySubmenus"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>

<!-- Modal para Asignar Submenús Nivel 2 -->
<div class="modal fade" id="modalAsignarSubmenus2">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Asignar Submenús Nivel 2</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="plan_id_submenus2" value="">
                <div class="table-responsive">
                    <table id="tablaSubmenus2" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Nombre del Submenú</th>
                                <th>Menú Padre</th>                                
                                <th>Submenú Nivel 1</th>                                
                                <th width="15%">Estado</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySubmenus"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>