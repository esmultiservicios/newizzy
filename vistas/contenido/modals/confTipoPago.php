<!--INICIO MODAL PARA EL INGRESO DE TIPO DE PAGO-->
<div class="modal fade" id="modalConfTipoPago">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-money-check-alt mr-2"></i>Tipo de Pago</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formConfTipoPago" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="tipo_pago_id" name="tipo_pago_id" class="form-control">
					                    
                    <!-- Sección de Datos del Tipo de Pago -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-credit-card mr-2"></i>Datos del Tipo de Pago</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="confTipoCuenta"><i class="fas fa-wallet mr-1"></i>Tipo de Cuenta <span class="priority">*</span></label>
                                    <select id="confTipoCuenta" name="confTipoCuenta" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo de cuenta" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Seleccione el tipo de cuenta asociada</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confCuentaTipoPago"><i class="fas fa-piggy-bank mr-1"></i>Cuenta <span class="priority">*</span></label>
                                    <select id="confCuentaTipoPago" name="confCuentaTipoPago" class="selectpicker form-control" data-live-search="true" title="Seleccione cuenta" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Cuenta asociada a este tipo de pago</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="confTipoPago"><i class="fas fa-money-bill-wave mr-1"></i>Tipo de Pago <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="confTipoPago" name="confTipoPago" class="form-control" placeholder="Tipo de pago" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-money-check-alt"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre del tipo de pago (máx. 30 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_tipo_pago">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_confTipoPago_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="confTipoPago_activo" name="confTipoPago_activo" value="1" checked>
                                <label class="custom-control-label" for="confTipoPago_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_confTipoPago_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Activar/Desactivar este tipo de pago en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_formTipoPago" form="formConfTipoPago">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_formTipoPago" form="formConfTipoPago">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_formTipoPago" form="formConfTipoPago">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE TIPO DE PAGO-->