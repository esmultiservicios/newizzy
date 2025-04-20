<!--INICIO MODAL NOMINA-->
<div class="modal fade" id="modal_registrar_nomina">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Registro de Nómina</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formNomina" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="nomina_id" name="nomina_id" class="form-control">
                    <input type="hidden" id="empresa_id" name="empresa_id" class="form-control">
                    
                    <!-- Sección de Información General -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">                            
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="nomina_detale"><i class="fas fa-align-left mr-1"></i>Detalle <span class="priority">*</span></label>
                                    <input type="text" name="nomina_detale" id="nomina_detale" class="form-control" maxlength="100" required>
                                    <small class="form-text text-muted">Descripción detallada de la nómina</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nomina_pago_planificado_id"><i class="fas fa-calendar-check mr-1"></i>Pago Planificado <span class="priority">*</span></label>
                                    <select id="nomina_pago_planificado_id" name="nomina_pago_planificado_id" class="selectpicker form-control" data-live-search="true" title="Seleccione pago planificado" required>
                                    </select>
                                    <small class="form-text text-muted">Periodicidad del pago de nómina</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nomina_empresa_id"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="nomina_empresa_id" name="nomina_empresa_id" class="selectpicker form-control" data-live-search="true" title="Seleccione empresa" required>
                                    </select>
                                    <small class="form-text text-muted">Empresa asociada a la nómina</small>
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
                                <div class="col-md-3 mb-3">
                                    <label for="tipo_nomina"><i class="fas fa-list-alt mr-1"></i>Tipo Nómina <span class="priority">*</span></label>
                                    <select id="tipo_nomina" name="tipo_nomina" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo nómina" required>
                                    </select>
                                    <small class="form-text text-muted">Tipo de nómina a procesar</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="pago_nomina"><i class="fas fa-money-bill-wave mr-1"></i>Pago <span class="priority">*</span></label>
                                    <select id="pago_nomina" name="pago_nomina" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo de pago" required>
                                    </select>
                                    <small class="form-text text-muted">Método de pago de la nómina</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nomina_fecha_inicio"><i class="fas fa-calendar-day mr-1"></i>Fecha Inicio <span class="priority">*</span></label>
                                    <input type="date" required id="nomina_fecha_inicio" name="nomina_fecha_inicio" value="<?php 
                                        $fecha = date ("Y-m-d");
                                        $año = date("Y", strtotime($fecha));
                                        $mes = date("m", strtotime($fecha));
                                        $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

                                        $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                        $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));

                                        $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                        echo $fecha_inicial;
                                    ?>" class="form-control">
                                    <small class="form-text text-muted">Fecha inicial del período de pago</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nomina_fecha_fin"><i class="fas fa-calendar-day mr-1"></i>Fecha Fin <span class="priority">*</span></label>
                                    <input type="date" required id="nomina_fecha_fin" name="nomina_fecha_fin" value="<?php 
                                        $fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));
                                        echo $fecha_final;
                                    ?>" class="form-control">
                                    <small class="form-text text-muted">Fecha final del período de pago</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3" id="grupo_salario">
                                    <label for="nomina_importe"><i class="fas fa-coins mr-1"></i>Importe <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nomina_importe" name="nomina_importe" placeholder="0.00" class="form-control" step="0.01">
                                    </div>
                                    <small class="form-text text-muted">Importe total de la nómina</small>
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
                                    <label for="nomina_notas"><i class="fas fa-comment-alt mr-1"></i>Notas</label>
                                    <div class="input-group">
                                        <textarea id="nomina_notas" name="nomina_notas" placeholder="Notas" class="form-control" maxlength="254" rows="3"></textarea>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" type="button" id="search_nomina_notas_start">
                                                <i class="fas fa-microphone-alt"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="search_nomina_notas_stop" style="display: none;">
                                                <i class="fas fa-microphone-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="charNum_nomina_notas" class="text-muted">254 caracteres restantes</p>
                                    <small class="form-text text-muted">Observaciones o comentarios adicionales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="nomina_activo" name="nomina_activo" checked>
                                    <label class="custom-control-label" for="nomina_activo"><i class="fas fa-check-circle mr-1"></i>Nómina Activa</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado de la nómina</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_nomina" form="formNomina">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_nomina" form="formNomina">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_nomina" form="formNomina">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL NOMINA-->

<!--INICIO MODAL DETALLES-->
<div class="modal fade" id="modal_registrar_nomina_detalles">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-users mr-2"></i>Registro de Nómina Empleados</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formNominaDetalles" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="nomina_id" name="nomina_id" class="form-control">
                    <input type="hidden" id="hrse25_valor" name="hrse25_valor" class="form-control">
                    <input type="hidden" id="hrse50_valor" name="hrse50_valor" class="form-control">
                    <input type="hidden" id="hrse75_valor" name="hrse75_valor" class="form-control">
                    <input type="hidden" id="hrse100_valor" name="hrse100_valor" class="form-control">
                    <input type="hidden" id="fecha_inicio" name="fecha_inicio" class="form-control">
                    <input type="hidden" id="fecha_fin" name="fecha_fin" class="form-control">
                    <input type="hidden" id="salario" name="salario" class="form-control">
                    <input type="hidden" id="validar_semanal" name="validar_semanal" class="form-control">
                    <input type="hidden" id="nomina_detalles_id" name="nomina_detalles_id" class="form-control">
                    <input type="hidden" id="pago_planificado_id" name="pago_planificado_id" class="form-control">
                    <input type="hidden" id="colaboradores_id" name="colaboradores_id" class="form-control">
                    
                    <!-- Sección de Información General -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="proceso_nomina_detalles"><i class="fas fa-tasks mr-1"></i>Proceso de Nómina</label>
                                    <div class="input-group">
                                        <input type="text" id="proceso_nomina_detalles" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-plus-square"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Descripción del proceso de nómina</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Nómina -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Nómina</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_numero"><i class="fas fa-hashtag mr-1"></i>N° Nómina <span class="priority">*</span></label>
                                    <input type="text" id="nominad_numero" name="nominad_numero" class="form-control" placeholder="Nómina" readonly>
                                    <small class="form-text text-muted">Número de identificación de la nómina</small>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="nominad_detalle"><i class="fas fa-align-left mr-1"></i>Detalle <span class="priority">*</span></label>
                                    <input type="text" id="nominad_detalle" name="nominad_detalle" class="form-control" placeholder="Detalle" readonly>
                                    <small class="form-text text-muted">Descripción detallada de la nómina</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Datos Generales -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Datos Generales</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_empleados"><i class="fas fa-user mr-1"></i>Empleado</label>
                                    <select class="selectpicker form-control" id="nominad_empleados" name="nominad_empleados" data-live-search="true" title="Seleccione empleado">
                                    </select>
                                    <small class="form-text text-muted">Empleado a incluir en la nómina</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_puesto"><i class="fas fa-briefcase mr-1"></i>Puesto <span class="priority">*</span></label>
                                    <input type="text" id="nominad_puesto" name="nominad_puesto" class="form-control" placeholder="Puesto" readonly>
                                    <small class="form-text text-muted">Puesto del empleado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_identidad"><i class="fas fa-id-card mr-1"></i>Identidad <span class="priority">*</span></label>
                                    <input type="text" id="nominad_identidad" name="nominad_identidad" class="form-control" placeholder="Identidad" readonly>
                                    <small class="form-text text-muted">Número de identidad del empleado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_contrato_id"><i class="fas fa-file-contract mr-1"></i>Contrato <span class="priority">*</span></label>
                                    <input type="text" id="nominad_contrato_id" name="nominad_contrato_id" class="form-control" placeholder="Contrato" readonly>
                                    <small class="form-text text-muted">Contrato del empleado</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_fecha_ingreso"><i class="fas fa-calendar-alt mr-1"></i>Fecha Ingreso <span class="priority">*</span></label>
                                    <input type="date" id="nominad_fecha_ingreso" name="nominad_fecha_ingreso" class="form-control" value="<?php echo date("Y-m-d");?>" readonly>
                                    <small class="form-text text-muted">Fecha de ingreso del empleado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_salario"><i class="fas fa-money-bill-wave mr-1"></i>Sueldo <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_salario" name="nominad_salario" class="form-control" placeholder="Salario" readonly>
                                    </div>
                                    <small class="form-text text-muted">Salario base del empleado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_sueldo_diario"><i class="fas fa-coins mr-1"></i>Sueldo Diario <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_sueldo_diario" name="nominad_sueldo_diario" class="form-control" placeholder="Sueldo Diario" readonly>
                                    </div>
                                    <small class="form-text text-muted">Sueldo calculado por día</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_sueldo_hora"><i class="fas fa-clock mr-1"></i>Sueldo por Hora <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_sueldo_hora" name="nominad_sueldo_hora" class="form-control" placeholder="Sueldo por Hora" readonly>
                                    </div>
                                    <small class="form-text text-muted">Sueldo calculado por hora</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Ingresos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Ingresos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_diast"><i class="fas fa-calendar-day mr-1"></i>Días Trabajados</label>
                                    <input type="number" id="nominad_diast" name="nominad_diast" class="form-control" placeholder="Días Trabajados" step="0.01" value="0.0">
                                    <small class="form-text text-muted">Días trabajados en el período</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_retroactivo"><i class="fas fa-history mr-1"></i>Retroactivo</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_retroactivo" name="nominad_retroactivo" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Pagos retroactivos</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_bono"><i class="fas fa-gift mr-1"></i>Bono</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_bono" name="nominad_bono" class="form-control" placeholder="0.00" step="0.01" value="0.0">
                                    </div>
                                    <small class="form-text text-muted">Bonos adicionales</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_otros_ingresos"><i class="fas fa-plus mr-1"></i>Otros Ingesos</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_otros_ingresos" name="nominad_otros_ingresos" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Otros ingresos adicionales</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas25"><i class="fas fa-clock mr-1"></i>Horas Extras 25% <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_horas25" name="nominad_horas25" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Horas extras al 25%</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas50"><i class="fas fa-clock mr-1"></i>Horas Extras 50% <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_horas50" name="nominad_horas50" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Horas extras al 50%</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas75"><i class="fas fa-clock mr-1"></i>Horas Extras 75% <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_horas75" name="nominad_horas75" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Horas extras al 75%</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas100"><i class="fas fa-clock mr-1"></i>Horas Extras 100% <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_horas100" name="nominad_horas100" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Horas extras al 100%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Egresos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-minus-circle mr-2"></i>Egresos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_deducciones"><i class="fas fa-hand-holding-usd mr-1"></i>Deducciones</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_deducciones" name="nominad_deducciones" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Deducciones varias</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_prestamo"><i class="fas fa-hand-holding-usd mr-1"></i>Préstamo</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_prestamo" name="nominad_prestamo" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Préstamos del empleado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_ihss"><i class="fas fa-shield-alt mr-1"></i>IHSS</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_ihss" name="nominad_ihss" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Aportación al IHSS</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_rap"><i class="fas fa-piggy-bank mr-1"></i>RAP</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_rap" name="nominad_rap" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Aportación al RAP</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_isr"><i class="fas fa-file-invoice-dollar mr-1"></i>ISR</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_isr" name="nominad_isr" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Impuesto sobre la renta</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_incapacidad_ihss"><i class="fas fa-procedures mr-1"></i>Incapacidad IHSS</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_incapacidad_ihss" name="nominad_incapacidad_ihss" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Deducciones por incapacidad</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_vale"><i class="fas fa-money-bill-wave mr-1"></i>Vale</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_vale" name="nominad_vale" class="form-control" placeholder="0.00" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Vales entregados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Resumen -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i>Resumen</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto_ingreso"><i class="fas fa-plus-circle mr-1"></i>Neto Ingresos</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="nominad_neto_ingreso" name="nominad_neto_ingreso" class="form-control" placeholder="0.00" step="0.01" readonly>
                                    </div>
                                    <small class="form-text text-muted">Total de ingresos</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto_egreso"><i class="fas fa-minus-circle mr-1"></i>Neto Egresos</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_neto_egreso" name="nominad_neto_egreso" class="form-control" placeholder="0.00" step="0.01" readonly>
                                    </div>
                                    <small class="form-text text-muted">Total de deducciones</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto"><i class="fas fa-equals mr-1"></i>Neto</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="text" id="nominad_neto" name="nominad_neto" class="form-control" placeholder="0.00" step="0.01" readonly>
                                    </div>
                                    <small class="form-text text-muted">Total neto a pagar</small>
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
                                    <label for="nomina_detalles_notas"><i class="fas fa-comment-alt mr-1"></i>Notas</label>
                                    <div class="input-group">
                                        <textarea id="nomina_detalles_notas" name="nomina_detalles_notas" placeholder="Notas" class="form-control" maxlength="254" rows="3"></textarea>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" type="button" id="search_nomina_detalles_notas_start">
                                                <i class="fas fa-microphone-alt"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="search_nomina_detalles_notas_stop" style="display: none;">
                                                <i class="fas fa-microphone-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="charNum_nomina_detalles_notas" class="text-muted">254 caracteres restantes</p>
                                    <small class="form-text text-muted">Observaciones o comentarios adicionales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="nomina_detalles_activo" name="nomina_detalles_activo" checked>
                                    <label class="custom-control-label" for="nomina_detalles_activo"><i class="fas fa-check-circle mr-1"></i>Detalle Activo</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado del detalle</small>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="form-row col-12">
                    <div class="col-md-3 mb-3">
                        <label><i class="fas fa-plus-circle mr-1"></i>Neto Ingresos:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">L</span>
                            </div>
                            <input type="number" id="nominad_neto_ingreso1" name="nominad_neto_ingreso1" class="form-control" placeholder="0.00" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label><i class="fas fa-minus-circle mr-1"></i>Neto Egresos:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">L</span>
                            </div>
                            <input type="text" id="nominad_neto_egreso1" name="nominad_neto_egreso1" class="form-control" placeholder="0.00" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label><i class="fas fa-equals mr-1"></i>Neto:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">L</span>
                            </div>
                            <input type="text" id="nominad_neto1" name="nominad_neto1" class="form-control" placeholder="0.00" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button class="btn btn-primary ml-2" type="submit" style="display: none;" id="reg_nominaD" form="formNominaDetalles">
                            <i class="far fa-save mr-1"></i> Registrar
                        </button>
                        <button class="btn btn-warning ml-2" type="submit" style="display: none;" id="edi_nominaD" form="formNominaDetalles">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </button>
                        <button class="btn btn-danger ml-2" type="submit" style="display: none;" id="delete_nominaD" form="formNominaDetalles">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL NOMINA DETALLES-->

<!--INICIO MODAL REGISTRO VALES-->
<div class="modal fade" id="modalRegistrarVales">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Registro de Vales</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formVales" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="vale_id" name="vale_id" class="form-control">
                    
                    <!-- Sección de Información General -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="proceso_vale"><i class="fas fa-tasks mr-1"></i>Proceso de Vale</label>
                                    <div class="input-group">
                                        <input type="text" id="proceso_vale" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-plus-square"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Descripción del proceso de vale</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Datos del Vale -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-money-bill-alt mr-2"></i>Datos del Vale</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="vale_fecha"><i class="fas fa-calendar-day mr-1"></i>Fecha <span class="priority">*</span></label>
                                    <input type="date" required id="vale_fecha" name="vale_fecha" value="<?php echo date("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de emisión del vale</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="vale_empleado"><i class="fas fa-user-tie mr-1"></i>Empleado con Contrato Activo <span class="priority">*</span></label>
                                    <select id="vale_empleado" name="vale_empleado" class="selectpicker form-control" data-live-search="true" title="Seleccione empleado" required>
                                    </select>
                                    <small class="form-text text-muted">Empleado que recibirá el vale</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="vale_monto"><i class="fas fa-coins mr-1"></i>Monto del Vale <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" id="vale_monto" name="vale_monto" placeholder="0.00" class="form-control" step="0.01" required>
                                    </div>
                                    <small class="form-text text-muted">Monto total del vale</small>
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
                                    <label for="vale_notas"><i class="fas fa-comment-alt mr-1"></i>Notas</label>
                                    <div class="input-group">
                                        <textarea id="vale_notas" name="vale_notas" placeholder="Notas" class="form-control" maxlength="1000" rows="3"></textarea>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" type="button" id="search_vale_notas_start">
                                                <i class="fas fa-microphone-alt"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="search_vale_notas_stop" style="display: none;">
                                                <i class="fas fa-microphone-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="charNum_vale_notas" class="text-muted">1000 caracteres restantes</p>
                                    <small class="form-text text-muted">Observaciones o comentarios adicionales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Historial de Vales -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Historial de Vales</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="DatatableVale" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user-tie mr-1"></i>Empleado</th>
                                            <th><i class="fas fa-coins mr-1"></i>Monto</th>
                                            <th><i class="fas fa-comment-alt mr-1"></i>Notas</th>
                                            <th><i class="fas fa-ban mr-1"></i>Anular</th>
                                        </tr>
                                    </thead>
                                </table>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_vale" form="formVales">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_vale" form="formVales">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_vale" form="formVales">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL REGISTRO VALES-->