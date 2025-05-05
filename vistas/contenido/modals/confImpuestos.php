<!--INICIO MODAL PARA EL INGRESO DE IMPUESTOS-->
<div class="modal fade" id="modalImpuestos">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-percentage mr-2"></i>Impuestos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formImpuestos" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección Información Impuesto -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Configuración de Impuestos</h5>
                        </div>
                        <div class="card-body">
							<input type="hidden" required id="isv_id" name="isv_id" class="form-control">						
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="tipo_isv"><i class="fas fa-tag mr-1"></i>Tipo ISV <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" readonly required id="tipo_isv" name="tipo_isv" class="form-control">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-percentage"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Tipo de impuesto a configurar</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="valor"><i class="fas fa-dollar-sign mr-1"></i>Valor <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" required id="valor" name="valor" class="form-control" 
                                            placeholder="Ej: 15" maxlength="11">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-percentage"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Porcentaje del impuesto</small>
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
                <button class="btn btn-success" type="submit" id="edi_impuestos" form="formImpuestos">
                    <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE IMPUESTOS-->