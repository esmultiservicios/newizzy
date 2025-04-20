<!--INICIO MODAL SECUENCIA DE FACTURACION-->
<div class="modal fade" id="modal_registrar_secuencias">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-file-invoice mr-2"></i>Secuencia de Facturación</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSecuencia" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="secuencia_facturacion_id" name="secuencia_facturacion_id" class="form-control">
                    
                    <!-- Sección de Información General -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="empresa_secuencia"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="empresa_secuencia" name="empresa_secuencia" class="selectpicker form-control" data-live-search="true" title="Seleccione una empresa" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Empresa asociada a esta secuencia</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="documento_secuencia"><i class="fas fa-file-alt mr-1"></i>Documento <span class="priority">*</span></label>
                                    <select id="documento_secuencia" name="documento_secuencia" class="selectpicker form-control" data-live-search="true" title="Seleccione un documento" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Tipo de documento a secuenciar</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cai_secuencia"><i class="fas fa-id-card mr-1"></i>CAI</label>
                                    <input type="text" name="cai_secuencia" id="cai_secuencia" class="form-control" placeholder="CAI" maxlength="37" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Código de Autorización de Impresión (37 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Configuración de Secuencia -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Configuración de Secuencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="prefijo_secuencia"><i class="fas fa-font mr-1"></i>Prefijo</label>
                                    <input type="text" name="prefijo_secuencia" id="prefijo_secuencia" class="form-control" placeholder="Prefijo">
                                    <small class="form-text text-muted">Texto inicial del número de documento</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="relleno_secuencia"><i class="fas fa-text-width mr-1"></i>Relleno <span class="priority">*</span></label>
                                    <input type="number" name="relleno_secuencia" id="relleno_secuencia" class="form-control" placeholder="Relleno" required>
                                    <small class="form-text text-muted">Cantidad de dígitos para el número</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="incremento_secuencia"><i class="fas fa-plus mr-1"></i>Incremento <span class="priority">*</span></label>
                                    <input type="number" name="incremento_secuencia" id="incremento_secuencia" class="form-control" placeholder="Incremento" required>
                                    <small class="form-text text-muted">Valor de incremento para cada documento</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="siguiente_secuencia"><i class="fas fa-arrow-right mr-1"></i>Siguiente <span class="priority">*</span></label>
                                    <input type="number" name="siguiente_secuencia" id="siguiente_secuencia" class="form-control" title="Número Siguiente" placeholder="Siguiente" required>
                                    <small class="form-text text-muted">Próximo número a utilizar</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="rango_inicial_secuencia"><i class="fas fa-list-ol mr-1"></i>Rango Inicial <span class="priority">*</span></label>
                                    <input type="text" name="rango_inicial_secuencia" id="rango_inicial_secuencia" class="form-control" placeholder="Rango Inicial" required>
                                    <small class="form-text text-muted">Primer número autorizado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="rango_final_secuencia"><i class="fas fa-list-ol mr-1"></i>Rango Final <span class="priority">*</span></label>
                                    <input type="text" name="rango_final_secuencia" id="rango_final_secuencia" class="form-control" placeholder="Rango Final" required>
                                    <small class="form-text text-muted">Último número autorizado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_activacion_secuencia"><i class="fas fa-calendar-alt mr-1"></i>Fecha Activación <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_activacion_secuencia" name="fecha_activacion_secuencia" value="<?php echo date ("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de inicio de uso</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_limite_secuencia"><i class="fas fa-calendar-times mr-1"></i>Fecha Límite <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_limite_secuencia" name="fecha_limite_secuencia" value="<?php echo date ("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha máxima de uso</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary mb-4" id="estado_secuencia_container">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado de la Secuencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="estado_secuencia" name="estado_secuencia" checked>
                                <label class="custom-control-label" for="estado_secuencia"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_estado_secuencia"></span></label>
                            </div>
                            <small class="form-text text-muted">Active o desactive esta secuencia en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_secuencia" form="formSecuencia">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_secuencia" form="formSecuencia">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_secuencia" form="formSecuencia">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL SECUENCIA DE FACTURACION-->