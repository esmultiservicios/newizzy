<!--INICIO MODAL PARA EL INGRESO DE ALMACENES-->
<div class="modal fade" id="modal_almacen">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-warehouse mr-2"></i>Gestión de Almacenes</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formAlmacen" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="almacen_id" name="almacen_id">				
                    
                    <!-- Sección de Datos del Almacén -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-warehouse mr-2"></i>Datos del Almacén</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="almacen_empresa_id"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="almacen_empresa_id" name="almacen_empresa_id" required class="selectpicker" data-width="100%" data-live-search="true" title="Empresa">
                                    </select>
                                    <small class="form-text text-muted">Empresa asociada al almacén</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="almacen_almacen"><i class="fas fa-signature mr-1"></i>Almacén <span class="priority">*</span></label>
                                    <input type="text" required class="form-control" name="almacen_almacen" id="almacen_almacen" placeholder="Almacén" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Nombre del almacén (máx. 30 caracteres)</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="ubicacion_almacen"><i class="fas fa-map-marker-alt mr-1"></i>Ubicación <span class="priority">*</span></label>
                                    <select id="ubicacion_almacen" required name="ubicacion_almacen" class="selectpicker" data-width="100%" data-live-search="true" title="Ubicacion">
                                    </select>
                                    <small class="form-text text-muted">Ubicación física del almacén</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Configuración -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs mr-2"></i>Configuración</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <input type="hidden" name="facturar_cero" id="cero" value="1">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="facturar_cero" name="" value="1" checked>
                                        <label class="custom-control-label" for="facturar_cero"><i class="fas fa-cash-register mr-1"></i>Facturar inventario en cero</label>
                                    </div>
                                    <small class="form-text text-muted">Permitir facturar productos con existencia cero</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="hidden" id="almacen_activo" name="almacen_activo" value="1" checked>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="val_almacen_activo" name="val_almacen_activo" value="1" checked>
                                        <label class="custom-control-label" for="val_almacen_activo"><i class="fas fa-power-off mr-1"></i>Estado</label>
                                    </div>
                                    <small class="form-text text-muted">Activar/Desactivar este almacén</small>
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
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_almacen" form="formAlmacen">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_almacen" form="formAlmacen">
                    <i class="fas fa-edit fa-lg mr-1"></i> confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE ALMACENES-->