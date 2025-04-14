<!-- registrarPlanes-view.php (HTML)-->
<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Administrar Planes</li>
    </ol>

    <!-- Formulario para registrar/editar planes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-1"></i>
            <span id="form-title">Registrar Nuevo Plan</span>
        </div>
        <div class="card-body">
            <form id="formulario_plan">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label for="nombre_plan">Nombre del Plan</label>
                        <input type="text" class="form-control" id="nombre_plan" name="nombre_plan" required maxlength="40" autofocus>
                    </div>                
                    
                    <div class="form-group col-md-3 mb-2">
                        <label for="estado_plan">Estado</label>
                        <div class="d-flex align-items-center" style="height: 38px;">
                            <label class="switch mb-0 mr-2">
                                <input type="checkbox" id="estado_plan" name="estado_plan" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span id="estado_label" class="font-weight-bold mb-0">Activo</span>
                        </div>
                    </div>

                    <div class="form-group col-md-2 mb-2 text-right">
                        <button type="button" id="btn-cancelar-edicion" class="btn btn-danger" style="display: none;">
                            <i class="fas fa-times-circle fa-lg mr-1"></i> Cancelar
                        </button>
                    </div>
                </div>
                
                <!-- Configuraciones dinámicas -->
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Configuraciones Adicionales</label>
                        <div id="configuraciones-container">
                            <!-- Configuración vacía por defecto -->
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" id="agregar-configuracion">
                            <i class="fas fa-plus fa-lg mr-1"></i> Agregar Configuración
                        </button>
                    </div>
                </div>
                
                <!-- Botón de acción -->
                <div class="form-row mt-3">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary" id="btn-submit">
                            <i class="fas fa-save fa-lg mr-1"></i> Registrar Plan
                        </button>
                    </div>
                </div>

                <input type="hidden" name="plan_id" id="plan_id" value="">
            </form>
        </div>
    </div>

    <!-- DataTable para mostrar planes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa-solid fa-ranking-star fa-lg mr-1"></i>
            Planes Registrados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTablePlanes" class="table table-striped table-header-gradient table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>planes_id</th>
                            <th>Nombre</th>
                            <th>Configuraciones</th>
                            <th>Estado</th>
                            <th>Menús</th>
                            <th>Submenús N1</th>
                            <th>Submenús N2</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
                                <th>Configuración</th>
                                <th>Cantidad</th>
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
                                <th>Menú Padre</th>
                                <th>Submenú Nivel 1</th>
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