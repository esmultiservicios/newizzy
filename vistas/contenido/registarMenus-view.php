<div class="container-fluid" id="div_top">
    <!-- Administrar Menús -->
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
                <i class="fas fa-list-alt breadcrumb-icon"></i>
                <span>Administrar Menús</span>
            </li>
        </ol>
    </div>

    <!-- Formulario para registrar/editar elementos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-1"></i>
            <span id="form_title">Registrar Nuevo Elemento de Menú</span>
        </div>
        <div class="card-body">
            <form id="formulario_menu">
                <input type="hidden" id="menu_id" name="menu_id" value="">
                
                <!-- Primera fila: Tipo y Nombre -->
                <div class="form-row" style="margin-bottom: 0.5rem;">
                    <div class="form-group col-md-3">
                        <label for="tipo_menu">Tipo de Elemento</label>
                        <select class="form-control selectpicker" id="tipo_menu" name="tipo_menu" required>
                            <option value="">Seleccionar...</option>
                            <option value="menu">Menú Principal</option>
                            <option value="submenu">Submenú Nivel 1</option>
                            <option value="submenu1">Submenú Nivel 2</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3" id="dependencia_menu_group" style="display:none;">
                        <label id="label_dependencia">Dependencia</label>
                        <select class="form-control selectpicker" id="dependencia_menu" name="dependencia_menu" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="nombre_menu">Nombre (Código)</label>
                        <input type="text" class="form-control" id="nombre_menu" name="nombre_menu" required maxlength="25" placeholder="Ej: ventas, clientes">
                        <small class="form-text text-muted" style="margin-top: 0.25rem;">Nombre interno (sin espacios)</small>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="descripcion_menu">Descripción</label>
                        <input type="text" class="form-control" id="descripcion_menu" name="descripcion_menu" required maxlength="50" placeholder="Nombre visible en el menú">
                    </div>
                </div>
                
                <!-- Segunda fila: Ícono, Orden y Visibilidad -->
                <div class="form-row" style="margin-bottom: 0.5rem;">
                    <div class="form-group col-md-4">
                        <label for="icono_menu">Ícono (FontAwesome)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i id="icono_preview" class="fas fa-question"></i></span>
                            </div>
                            <input type="text" class="form-control" id="icono_menu" name="icono_menu" placeholder="Ej: fas fa-home fa-lg">
                        </div>
                        <div style="margin-top: 0.25rem;">
                            <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank" 
                            class="icon-explorer-link">
                                <i class="fas fa-external-link-alt"></i>Explorar íconos disponibles
                            </a>
                        </div>
                    </div>
                                        
                    <div class="form-group col-md-4">
                        <label for="orden_menu">Orden</label>
                        <input type="number" class="form-control" id="orden_menu" name="orden_menu" min="0" value="0">
                        <small class="form-text text-muted" style="margin-top: 0.25rem;">Define el orden de aparición</small>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" id="visible_menu" name="visible_menu" checked>
                            <label class="form-check-label" for="visible_menu">Mostrar en menú lateral</label>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="form-row" style="margin-top: 1rem;">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-secondary mr-2" id="btnCancelarEdicion" style="display:none;">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnAccionMenu">
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
                            <th>Descripción</th>
                            <th>Ícono</th>
                            <th>Orden</th>                            
                            <th>Dependencia</th>
                            <th>Visible</th>
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

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Menús");
?>