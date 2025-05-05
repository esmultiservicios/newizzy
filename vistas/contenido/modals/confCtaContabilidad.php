<!--INICIO MODAL PARA EL INGRESO DE DIARIOS-->
<div class="modal fade" id="modalConfEntidades">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Configurar Cuentas para las Entidades</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formConfCuentasEntidades" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required="required" readonly id="diarios_id" name="diarios_id" />                
                    
                    <!-- Sección de Configuración -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Configuración</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label><i class="fas fa-university mr-1"></i>Entidad <span class="priority">*<span /></label>
                                    <div class="input-group">
                                        <input type="text" required id="confEntidad" name="confEntidad" class="form-control" placeholder="Banco" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                    </div>
                                    <small class="form-text text-muted">Nombre de la entidad bancaria o financiera</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mb-3">                            
                                    <label><i class="fas fa-wallet mr-1"></i>Cuenta <span class="priority">*<span /></label>
                                    <select id="confCuenta" name="confCuenta" class="selectpicker form-control" data-live-search="true" title="Seleccione una cuenta">
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Seleccione la cuenta contable asociada</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_confEntidades" form="formConfCuentasEntidades">
                    <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE DIARIOS-->