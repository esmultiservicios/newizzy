<!--INICIO MODAL PARA EL INGRESO DE MEDIDAS-->
<div class="modal fade" id="modal_medidas">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-ruler-combined mr-2"></i>Medidas</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formMedidas" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required="required" readonly id="medida_id" name="medida_id"/>
                    
                    <!-- Sección de Datos de Medidas -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Datos de Medidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label>Medida <span class="priority">*<span/></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                                        </div>
                                        <input type="text" required id="medidas_medidas" name="medidas_medidas" placeholder="Medida" class="form-control" maxlength="4" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
                                    </div>
                                    <small class="form-text text-muted">Código de la medida (ej. KG, LT)</small>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="apellido_proveedores">Descripción <span class="priority">*<span/></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                        </div>
                                        <input type="text" required id="descripcion_medidas" name="descripcion_medidas" placeholder="Descripción" class="form-control" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
                                    </div>
                                    <small class="form-text text-muted">Descripción completa de la medida</small>
                                </div>                    
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_medidas">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="medidas_activo" name="medidas_activo" value="1" checked>
                                    <label class="custom-control-label" for="medidas_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_medidas_activo"></span>	</label>
                                </div>
                                <small class="form-text text-muted">Activar o desactivar esta medida en el sistema</small>
                            </div>                  
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div> 
                </form>
            </div>    
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_medidas" form="formMedidas">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_medidas" form="formMedidas">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_medidas" form="formMedidas">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>                
            </div>            
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE MEDIDAS-->
