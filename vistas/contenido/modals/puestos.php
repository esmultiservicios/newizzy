<!--INICIO MODAL PUESTO-->
<div class="modal fade" id="modal_registrar_puestos">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-briefcase mr-2"></i>Registro de Puestos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formPuestos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="puestos_id" name="puestos_id" class="form-control">   
					
                    <!-- Sección de Información del Puesto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información del Puesto</h5>
                        </div>
                        <div class="card-body">                           
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="puesto">Nombre del Puesto <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="puesto" name="puesto" placeholder="Nombre del puesto" required>          
                                    </div>
                                    <small class="form-text text-muted">Ingrese el nombre completo del puesto</small>
                                </div>        
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado del Puesto</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="puestos_activo" name="puestos_activo" checked>
                                <label class="custom-control-label" for="puestos_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_puestos_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Active o desactive el puesto en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>  
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_puestos" form="formPuestos">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_puestos" form="formPuestos">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_puestos" form="formPuestos">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>                    
            </div>            
        </div>
    </div>
</div>
<!--FIN MODAL PUESTO-->