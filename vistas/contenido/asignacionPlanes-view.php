<div class="container-fluid" id="div_top">
    <!-- Asignación de Planes -->
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
                <i class="fas fa-user-check breadcrumb-icon"></i>
                <span>Asignación de Planes</span>
            </li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-tag mr-1"></i>
            Asignar Plan a Cliente
        </div>
        <div class="card-body">
            <form id="formAsignacionPlan">
                <input type="hidden" id="server_customers_id" name="server_customers_id">
                
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="cliente_id">Seleccionar Cliente</label>
                        <select class="form-control selectpicker" id="cliente_id" name="cliente_id" 
                                data-live-search="true" title="Buscar cliente..." required>
                            <!-- Opciones se cargarán via AJAX -->
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="planes_id">Seleccionar Plan</label>
                        <select class="form-control selectpicker" id="planes_id" name="planes_id" required>
                            <!-- Opciones se cargarán via AJAX -->
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="user_extra">Usuarios Extras</label>
                        <input type="number" class="form-control" id="user_extra" name="user_extra" 
                               min="0" value="0" required>
                        <small class="form-text text-muted">Cantidad adicional de usuarios</small>
                    </div>   
                    
                    <div class="form-group col-md-3">
                        <label for="validar">Validar</label>
                        <select class="form-control selectpicker" id="validar" name="validar" required>
                            <option value="1">Sí</option>
                            <option value="2">No</option>
                        </select>
                    </div>                    
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="estado">Estado</label>
                        <select class="form-control selectpicker" id="estado" name="estado" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="sistema_id">Sistema</label>
                        <select class="form-control selectpicker" id="sistema_id" name="sistema_id" disabled>
                            <!-- Opciones se cargarán via AJAX -->
                        </select>
                        <small class="form-text text-muted">Sistema asignado (solo lectura)</small>
                    </div>                    
                </div>
                
                <div class="form-row mt-3">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary mr-2" id="btn-asignar-plan">
                            <i class="fas fa-save mr-1"></i> Actualizar Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history mr-1"></i>
            Asignaciones Actuales
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaAsignaciones" class="table table-striped table-header-gradient table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Plan Actual</th>
                            <th>Sistema</th>
                            <th>Usuarios Extras</th>
                            <th>Validar</th>
                            <th>Estado</th>
                            <th>Fecha Asignación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- El modal permanece igual -->
<div class="modal fade" id="modalConfirmarCambio">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Confirmar Cambio de Plan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p id="mensajeConfirmacion"></p>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Advertencia:</strong> Se actualizará el plan y los usuarios permitidos para este cliente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-cambio">Confirmar</button>
            </div>
        </div>
    </div>
</div>