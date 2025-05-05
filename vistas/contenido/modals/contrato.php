
<!--INICIO MODAL CONTRATO-->
<div class="modal fade" id="modal_registrar_contrato">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-file-contract mr-2"></i>Registro de Contrato a Empleados</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="" id="formContrato" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
					<input type="hidden" id="contrato_id" name="contrato_id" class="form-control">								
                    
                    <!-- Sección de Datos del Contrato -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-id-card mr-2"></i>Datos del Contrato</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_colaborador_id"><i class="fas fa-user-tie mr-1"></i>Empleado <span class="priority">*</span></label>
                                    <select id="contrato_colaborador_id" name="contrato_colaborador_id" class="selectpicker form-control" data-live-search="true" title="Seleccione empleado" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Empleado para el contrato</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_tipo_contrato_id"><i class="fas fa-file-signature mr-1"></i>Tipo Contrato <span class="priority">*</span></label>
                                    <select id="contrato_tipo_contrato_id" name="contrato_tipo_contrato_id" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo de contrato" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Tipo de contrato laboral</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_pago_planificado_id"><i class="fas fa-calendar-alt mr-1"></i>Pago Planificado <span class="priority">*</span></label>
                                    <select id="contrato_pago_planificado_id" name="contrato_pago_planificado_id" class="selectpicker form-control" data-live-search="true" title="Seleccione pago planificado" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Frecuencia de pago del contrato</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_tipo_empleado_id"><i class="fas fa-users mr-1"></i>Tipo Empleado <span class="priority">*</span></label>
                                    <select id="contrato_tipo_empleado_id" name="contrato_tipo_empleado_id" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo empleado" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Clasificación del empleado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Detalles del Contrato -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Detalles del Contrato</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_salario_mensual"><i class="fas fa-money-bill-wave mr-1"></i>Salario Mensual <span class="priority">*</span></label>
                                    <input type="number" required id="contrato_salario_mensual" name="contrato_salario_mensual" placeholder="Salario" class="form-control" step="0.01">
                                    <small class="form-text text-muted">Salario mensual acordado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_salario"><i class="fas fa-calculator mr-1"></i>Salario Calculado</label>
                                    <input type="number" required id="contrato_salario" name="contrato_salario" readonly placeholder="Salario" class="form-control" step="0.01">
                                    <small class="form-text text-muted">Salario según frecuencia de pago</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_fecha_inicio"><i class="fas fa-calendar-day mr-1"></i>Fecha Inicio <span class="priority">*</span></label>
                                    <input type="date" required id="contrato_fecha_inicio" name="contrato_fecha_inicio" value="<?php echo date("Y-m-d"); ?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de inicio del contrato</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="contrato_fecha_fin"><i class="fas fa-calendar-times mr-1"></i>Fecha Fin</label>
                                    <input type="date" id="contrato_fecha_fin" name="contrato_fecha_fin" value="" class="form-control">
                                    <small class="form-text text-muted">Fecha de finalización (si aplica)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Notas -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i>Notas</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="contrato_notas"><i class="fas fa-align-left mr-1"></i>Notas</label>
                                    <div class="input-group">
                                        <textarea id="contrato_notas" name="contrato_notas" placeholder="Notas" class="form-control" maxlength="1000" rows="3"></textarea>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" type="button" id="search_contrato_notas_start">
                                                <i class="fas fa-microphone-alt"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="search_contrato_notas_stop" style="display: none;">
                                                <i class="fas fa-microphone-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="charNum_contrato_notas" class="text-muted">254 Caracteres restantes</p>
                                    <small class="form-text text-muted">Notas adicionales sobre el contrato</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Configuración -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs mr-2"></i>Configuración</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3" id="estado_contrato">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="contrato_activo" name="contrato_activo" value="1" checked>
                                        <label class="custom-control-label" for="contrato_activo"><i class="fas fa-power-off mr-1"></i>Estado del Contrato</label>
                                    </div>
                                    <small class="form-text text-muted">Activar/Desactivar este contrato</small>
                                </div>
                                <div class="col-md-6 mb-3" id="estado_base_semanal" style="display: none">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="calculo_semanal" name="calculo_semanal" value="1">
                                        <label class="custom-control-label" for="calculo_semanal"><i class="fas fa-calendar-week mr-1"></i>¿Basado en la semana?</label>
                                    </div>
                                    <small class="form-text text-muted">Calcular salario semanalmente</small>
                                </div>
                            </div>
                        </div>
                    </div>                
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" id="reg_contrato" form="formContrato">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" id="edi_contrato" form="formContrato">
                    <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CONTRATO-->