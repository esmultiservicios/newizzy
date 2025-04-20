<!--INICIO MODAL PARA EL INGRESO DE BANCOS-->
<div class="modal fade" id="modalConfBancos">
    <div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-university mr-2"></i>Gestión de Bancos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formBancos" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="banco_id" name="banco_id">			
                    
                    <!-- Sección de Datos del Banco -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-university mr-2"></i>Datos del Banco</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="confbanco"><i class="fas fa-signature mr-1"></i>Banco <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="confbanco" name="confbanco" class="form-control" placeholder="Banco" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-university"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre del banco (máx. 30 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_bancos">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_confbanco_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="confbanco_activo" name="confbanco_activo" value="1" checked>
                                <label class="custom-control-label" for="confbanco_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_confbanco_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Activar/Desactivar este banco en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_banco" form="formBancos">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_banco" form="formBancos">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_banco" form="formBancos">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE BANCOS-->