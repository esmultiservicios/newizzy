<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Administrar Planes</li>
    </ol>

    <!-- Formulario para registrar nuevos planes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-1"></i>
            Registrar Nuevo Plan
        </div>
        <div class="card-body">
            <form id="formulario_plan">
                <!-- Todos los campos en una sola fila -->
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="nombre_plan">Nombre del Plan</label>
                        <input type="text" class="form-control" id="nombre_plan" name="nombre_plan" required maxlength="40" autofocus>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="usuarios_plan">Cantidad de Usuarios</label>
                        <input type="number" class="form-control" id="usuarios_plan" name="usuarios_plan" required min="1">
                    </div>
                    <div class="form-group col-md-4">
						<label class="switch">
							<input type="checkbox" id="estado_plan" name="estado_plan" value="1"
								checked>
							<div class="slider round"></div>
						</label>										
                    </div>
                </div>
                
                <!-- Botón de registrar colocado abajo y alineado a la derecha -->
                <div class="form-row mt-3">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Registrar Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable para mostrar los planes registrados -->
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
                            <th>Nombre</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                            <th>Menús Asignados</th>
                            <th>Submenús Nivel 1</th>
                            <th>Submenús Nivel 2</th>
                            <th>Editar</th>
							<th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                
                $insMainModel = new mainModel();
                $entidad = "menu";
                
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
    </div>
</div>

<!-- Modal para asignar menús y submenús -->
<div class="modal fade" id="modalRegistarPlanes" tabindex="-1" role="dialog" aria-labelledby="modalAsignarMenusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAsignarMenusLabel">
                    <i class="fas fa-list-alt mr-2"></i> Asignar Menús y Submenús
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formularioRegstrarPlanes">
                    <input type="hidden" id="plan_id" name="plan_id">

                    <div class="form-group">
                        <label for="menu_asignado"><i class="fas fa-bars mr-1"></i> Menú Principal</label>
                        <select class="form-control selectpicker" id="menu_asignado" name="menu_asignado" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div class="form-group" id="submenu_group" style="display:none;">
                        <label for="submenu_asignado"><i class="fas fa-indent mr-1"></i> Submenú Nivel 1</label>
                        <select class="form-control selectpicker" id="submenu_asignado" name="submenu_asignado" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div class="form-group" id="submenu1_group" style="display:none;">
                        <label for="submenu1_asignado"><i class="fas fa-outdent mr-1"></i> Submenú Nivel 2</label>
                        <select class="form-control selectpicker" id="submenu1_asignado" name="submenu1_asignado" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsignacion">
                    <i class="fas fa-save mr-1"></i> Guardar Asignación
                </button>
            </div>
        </div>
    </div>
</div>