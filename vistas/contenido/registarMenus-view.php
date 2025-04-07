<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Administrar Menús</li>
    </ol>

    <!-- Formulario para registrar nuevos elementos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-1"></i>
            Registrar Nuevo Elemento de Menú
        </div>
        <div class="card-body">
            <form id="formulario_menu">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="tipo_menu">Tipo de Elemento</label>
                        <select class="form-control selectpicker" id="tipo_menu" name="tipo_menu" required>
                            <option value="">Seleccionar...</option>
                            <option value="menu">Menú Principal</option>
                            <option value="submenu">Submenú Nivel 1</option>
                            <option value="submenu1">Submenú Nivel 2</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4" id="dependencia_menu_group" style="display:none;">
                        <label id="label_dependencia">Dependencia</label>
                        <select class="form-control selectpicker" id="dependencia_menu" name="dependencia_menu" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="nombre_menu">Nombre del Elemento</label>
                        <input type="text" class="form-control" id="nombre_menu" name="nombre_menu" required maxlength="25">
                    </div>
                </div>
                
                <!-- Botón de registrar colocado abajo y alineado a la derecha -->
                <div class="form-row mt-3">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary" id="btnRegistrarMenu">
                            <i class="fas fa-save mr-1"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable para mostrar los elementos registrados -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fa-solid fa-bars fa-lg mr-1"></i>
            Menús Registrados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableMenus" class="table table-striped table-header-gradient table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nombre</th>
                            <th>Dependencia</th>
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

<!-- Modal para edición (actualizado para centrarlo) -->
<div class="modal fade" id="modalEditarMenu" tabindex="-1" role="dialog" aria-labelledby="modalEditarMenuLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarMenuLabel">
                    <i class="fas fa-edit mr-2"></i> Editar Elemento de Menú
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario_editar_menu">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <input type="hidden" id="edit_tipo" name="edit_tipo">

                    <div class="form-group">
                        <label for="edit_nombre">Nombre</label>
                        <input type="text" class="form-control" id="edit_nombre" name="edit_nombre" required>
                    </div>

                    <div class="form-group" id="edit_dependencia_group" style="display:none;">
                        <label id="edit_label_dependencia"><i class="fas fa-link mr-1"></i> Dependencia</label>
                        <select class="form-control selectpicker" id="edit_dependencia" name="edit_dependencia" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarCambios">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Menús");
?>