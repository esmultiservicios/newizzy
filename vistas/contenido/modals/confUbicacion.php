<!--INICIO MODAL PARA EL INGRESO DE UBICACION-->
<div class="modal fade" id="modal_ubicacion">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-map-marker-alt mr-2"></i>Ubicación</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formUbicacion" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="ubicacion_id" name="ubicacion_id">				
                    
                    <!-- Sección de Datos de Ubicación -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-map-marked-alt mr-2"></i>Datos de Ubicación</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="ubicacion_ubicacion"><i class="fas fa-signature mr-1"></i>Ubicación <span class="priority">*</span></label>
                                    <input type="text" required class="form-control" name="ubicacion_ubicacion" id="ubicacion_ubicacion" placeholder="Ubicación" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Nombre de la ubicación (máx. 30 caracteres)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="empresa_ubicacion"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="empresa_ubicacion" name="empresa_ubicacion" class="selectpicker form-control" data-live-search="true" title="Seleccione empresa" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Empresa asociada a esta ubicación</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_ubicacion">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_ubicacion_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ubicacion_activo" name="ubicacion_activo" value="1" checked>
                                <label class="custom-control-label" for="ubicacion_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_ubicacion_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Activar/Desactivar esta ubicación en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_ubicacion" form="formUbicacion">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_ubicacion" form="formUbicacion">
                    <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE UBICACION-->