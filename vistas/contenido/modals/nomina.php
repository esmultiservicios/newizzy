<!--INICIO MODAL NOMINA-->
<div class="modal fade" id="modal_registrar_nomina">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Registro de Nomina</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formNomina" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="nomina_id" name="nomina_id" class="form-control">
                                <input type="hidden" id="empresa_id" name="empresa_id" class="form-control">
                                <input type="text" id="proceso_nomina" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nomina_empresa_id">Detalle <span class="priority">*<span /></label>
                            <input type="text" name="nomina_detale" id="nomina_detale" class="form-control"
                                maxlength="100"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nomina_pago_planificado_id">Pago Planificado <span
                                    class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="nomina_pago_planificado_id" name="nomina_pago_planificado_id"
                                    class="selectpicker" data-width="100%" data-live-search="true"
                                    title="Pago Planificado">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nomina_empresa_id">Empresa <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="nomina_empresa_id" name="nomina_empresa_id" class="selectpicker"
                                    data-width="100%" data-width="100%" data-live-search="true" title="Empresa">
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="nomina_empresa_id">Tipo Nomina <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="tipo_nomina" name="tipo_nomina" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Tipo Nomina">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nomina_empresa_id">Pago <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="pago_nomina" name="pago_nomina" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Tipo Nomina">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nomina_fecha_inicio">Fecha Inicio <span class="priority">*<span /></label>
                            <input type="date" required id="nomina_fecha_inicio" name="nomina_fecha_inicio" value="<?php 
							$fecha = date ("Y-m-d");
							
							$año = date("Y", strtotime($fecha));
							$mes = date("m", strtotime($fecha));
							$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

							$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
							$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

							$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
							$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
							
							
							echo $fecha_inicial;
						?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Fin">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="nomina_fecha_fin">Fecha Fin <span class="priority">*<span /></label>

                            <input type="date" required id="nomina_fecha_fin" name="nomina_fecha_fin" value="<?php 
							$fecha = date ("Y-m-d");
							
							$año = date("Y", strtotime($fecha));
							$mes = date("m", strtotime($fecha));
							$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

							$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
							$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

							$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
							$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
							
							
							echo $fecha_final;
						?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Fin">
                        </div>
                        <div class="col-md-3 mb-3" id="grupo_salario">
                            <label for="nomina_importe">Importe <span class="priority">*<span /></label>
                            <input type="number" id="nomina_importe" name="nomina_importe" placeholder="Salario"
                                class="form-control" step="0.01" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="nomina_notas">Notas</label>
                            <div class="input-group">
                                <textarea id="nomina_notas" name="nomina_notas" placeholder="Notas" class="form-control"
                                    maxlength="1000" rows="3"></textarea>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="btn btn-outline-success fas fa-microphone-alt"
                                            id="search_nomina_notas_start"></i>
                                        <i class="btn btn-outline-success fas fa-microphone-slash"
                                            id="search_nomina_notas_stop"></i>
                                    </span>
                                </div>
                            </div>
                            <p id="charNum_nomina_notas">254 Caracteres</p>
                        </div>
                    </div>

                    <div class="form-group" id="estado_nomina">
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="nomina_activo" name="nomina_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_nomina_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_nomina"
                    form="formNomina">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_nomina"
                    form="formNomina">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_nomina"
                    form="formNomina">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
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
            <div class="modal-header">
                <h4 class="modal-title">Registro de Nomina Empleados</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formNominaDetalles" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="nomina_id" name="nomina_id" class="form-control">

                                <input type="hidden" id="hrse25_valor" name="hrse25_valor" class="form-control">
                                <input type="hidden" id="hrse50_valor" name="hrse50_valor" class="form-control">
                                <input type="hidden" id="hrse75_valor" name="hrse75_valor" class="form-control">
                                <input type="hidden" id="hrse100_valor" name="hrse100_valor" class="form-control">
                                <input type="hidden" id="fecha_inicio" name="fecha_inicio" class="form-control">
                                <input type="hidden" id="fecha_fin" name="fecha_fin" class="form-control">
                                <input type="hidden" id="salario" name="salario" class="form-control">
                                <input type="hidden" id="validar_semanal" name="validar_semanal" class="form-control">
                                <input type="hidden" id="nomina_detalles_id" name="nomina_detalles_id"
                                    class="form-control">
                                <input type="hidden" id="pago_planificado_id" name="pago_planificado_id"
                                    class="form-control">
                                <input type="hidden" id="colaboradores_id" name="colaboradores_id" class="form-control">
                                <input type="text" id="proceso_nomina_detalles" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Nomina
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_numero">N° Nomina <span class="priority">*<span /></label>
                                    <input type="text" id="nominad_numero" name="nominad_numero" class="form-control"
                                        placeholder="Nomina" readonly>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="nominad_puesto">Detalle <span class="priority">*<span /></label>
                                    <input type="text" id="nominad_detalle" name="nominad_detalle" class="form-control"
                                        placeholder="Detalle" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Datos Generales
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_empleados">Empleado</label>
                                    <div class="input-group mb-3">
                                        <select class="selectpicker" id="nominad_empleados" name="nominad_empleados"
                                            data-width="100%" data-width="100%" data-size="7" data-live-search="true"
                                            title="Empleado">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_puesto">Puesto <span class="priority">*<span /></label>
                                    <input type="text" id="nominad_puesto" name="nominad_puesto" class="form-control"
                                        placeholder="Puesto" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_identidad">Identidad <span class="priority">*<span /></label>
                                    <input type="text" id="nominad_identidad" name="nominad_identidad"
                                        class="form-control" placeholder="Identidad" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_contrato_id">Contrato <span class="priority">*<span /></label>
                                    <input type="text" id="nominad_contrato_id" name="nominad_contrato_id"
                                        class="form-control" placeholder="Contrato" readonly>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_fecha_ingreso">Fecha Ingreso <span
                                            class="priority">*<span /></label>
                                    <input type="date" id="nominad_fecha_ingreso" name="nominad_fecha_ingreso"
                                        class="form-control" value="<?php echo date("Y-m-d");?>" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_salario">Sueldo <span class="priority">*<span /></label>
                                    <input type="number" id="nominad_salario" name="nominad_salario"
                                        class="form-control" placeholder="Salario" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_fecha_ingreso">Sueldo Diario <span
                                            class="priority">*<span /></label>
                                    <input type="number" id="nominad_sueldo_diario" name="nominad_sueldo_diario"
                                        class="form-control" placeholder="Sueldo Diario" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_fecha_ingreso">Sueldo por Hora <span
                                            class="priority">*<span /></label>
                                    <input type="number" id="nominad_sueldo_hora" name="nominad_sueldo_hora"
                                        class="form-control" placeholder="Sueldo por Hora" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Ingresos
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_diast">Días Trabajados</label>
                                    <input type="number" id="nominad_diast" name="nominad_diast" class="form-control"
                                        placeholder="Dias Trabajados" step="0.01" value="0.0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_retroactivo">Retroactivo</label>
                                    <input type="text" id="nominad_retroactivo" name="nominad_retroactivo"
                                        class="form-control" placeholder="Rectroactivo" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_bono">Bono</label>
                                    <input type="text" id="nominad_bono" name="nominad_bono" class="form-control"
                                        placeholder="Bono" step="0.01" value="0.0">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_identidad">Otros Ingesos</label>
                                    <input type="text" id="nominad_otros_ingresos" name="nominad_otros_ingresos"
                                        class="form-control" placeholder="Otros Ingresos" step="0.01" value="0.00">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas25">Horas Extras 25% <span
                                            class="priority">*<span /></label>
                                    <input type="text" id="nominad_horas25" name="nominad_horas25" class="form-control"
                                        placeholder="Horas Extras 25%" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas50">Horas Extras 50% <span
                                            class="priority">*<span /></label>
                                    <input type="text" id="nominad_horas50" name="nominad_horas50" class="form-control"
                                        placeholder="Horas Extras 50%" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas75">Horas Extras 75% <span
                                            class="priority">*<span /></label>
                                    <input type="text" id="nominad_horas75" name="nominad_horas75" class="form-control"
                                        placeholder="Horas Extras 75%" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_horas100">Horas Extras 100% <span
                                            class="priority">*<span /></label>
                                    <input type="text" id="nominad_horas100" name="nominad_horas100"
                                        class="form-control" placeholder="Horas Extras 100%" step="0.01" value="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Egresos
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_deducciones">Deducciones</label>
                                    <input type="number" id="nominad_deducciones" name="nominad_deducciones"
                                        class="form-control" placeholder="Deducciones" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_prestamo">Prestamo</label>
                                    <input type="text" id="nominad_prestamo" name="nominad_prestamo"
                                        class="form-control" placeholder="Prestamo" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_ihss">IHSS</label>
                                    <input type="text" id="nominad_ihss" name="nominad_ihss" class="form-control"
                                        placeholder="IHSS" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_rap">RAP</label>
                                    <input type="text" id="nominad_rap" name="nominad_rap" class="form-control"
                                        placeholder="RAP" step="0.01" value="0.00">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_isr">ISR</label>
                                    <input type="number" id="nominad_isr" name="nominad_isr" class="form-control"
                                        placeholder="ISR" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_incapacidad_ihss">Incapacidad IHSS</label>
                                    <input type="text" id="nominad_incapacidad_ihss" name="nominad_incapacidad_ihss"
                                        class="form-control" placeholder="Incapacidad IHSS" step="0.01" value="0.00">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nominad_vale">Vale</label>
                                    <input type="number" id="nominad_vale" name="nominad_vale" class="form-control"
                                        placeholder="Vale" step="0.01" value="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Resumen
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto_ingreso">Neto Ingresos</label>
                                    <input type="number" id="nominad_neto_ingreso" name="nominad_neto_ingreso"
                                        class="form-control" placeholder="Neto Ingresos" step="0.01" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto_egreso">Neto Egresos</label>
                                    <input type="text" id="nominad_neto_egreso" name="nominad_neto_egreso"
                                        class="form-control" placeholder="Neto Deducciones" step="0.01" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="nominad_neto">Neto</label>
                                    <input type="text" id="nominad_neto" name="nominad_neto" class="form-control"
                                        placeholder="Neto" step="0.01" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header text-white bg-info mb-3" align="center">
                            Notas
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="nomina_detalles_notas">Notas</label>
                                    <div class="input-group">
                                        <textarea id="nomina_detalles_notas" name="nomina_detalles_notas"
                                            placeholder="Notas" class="form-control" maxlength="1000"
                                            rows="3"></textarea>
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="btn btn-outline-success fas fa-microphone-alt"
                                                    id="search_nomina_detalles_notas_start"></i>
                                                <i class="btn btn-outline-success fas fa-microphone-slash"
                                                    id="search_nomina_detalles_notas_stop"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <p id="charNum_nomina_detales_notas">254 Caracteres</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="estado_nomina_detalles">
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="nomina_detalles_activo" name="nomina_detalles_activo"
                                    value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_nomina_detalles_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="form-row col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="col-md-3 mb-3">
                        <label>Neto Ingresos:</label>
                        <div class="input-group">
                            <div class="input-group-append mb-1">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>L</i>
                                </span>
                            </div>
                            <input type="number" id="nominad_neto_ingreso1" name="nominad_neto_ingreso1"
                                class="form-control" placeholder="Neto Ingresos" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Neto Egresos:</label>
                        <div class="input-group">
                            <div class="input-group-append mb-1">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>L</i>
                                </span>
                            </div>
                            <input type="text" id="nominad_neto_egreso1" name="nominad_neto_egreso1"
                                class="form-control" placeholder="Neto Deducciones" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Neto:</label>
                        <div class="input-group mb-1">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>L</i>
                                </span>
                            </div>
                            <input type="text" id="nominad_neto1" name="nominad_neto1" class="form-control"
                                placeholder="Neto" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 d-flex flex-column">
                        <button class="guardar btn btn-primary mt-4" type="submit" style="display: none;"
                            id="reg_nominaD" form="formNominaDetalles">
                            <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                        </button>
                        <button class="editar btn btn-warning mt-4" type="submit" style="display: none;"
                            id="edi_nominaD" form="formNominaDetalles">
                            <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                        </button>
                        <button class="eliminar btn btn-danger mt-4" type="submit" style="display: none;"
                            id="delete_nominaD" form="formNominaDetalles">
                            <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
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
            <div class="modal-header">
                <h4 class="modal-title">Registro de Nomina</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formVales" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="vale_id" name="vale_id" class="form-control">
                                <input type="text" id="proceso_vale" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="vale_monto">Fecha <span class="priority">*<span /></label>
                            <input type="date" required id="vale_fecha" name="vale_fecha"
                                value="<?php echo date ("Y-m-d");?>" class="form-control" data-toggle="tooltip"
                                data-placement="top" title="Fecha">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vale_empleado" data-toggle="tooltip" data-placement="top"
                                title="Aquí se muestran todos los empleados que tengan un contrato activo.">Empleado con
                                Contrato Activo
                                <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="vale_empleado" name="vale_empleado" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Empleado con Contrato Activo" required>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="vale_monto">Monto del Vale <span class="priority">*<span /></label>
                            <input type="number" id="vale_monto" name="vale_monto" placeholder="Monto del Vale"
                                class="form-control" step="0.01" required />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="vale_notas">Notas</label>
                            <div class="input-group">
                                <textarea id="vale_notas" name="vale_notas" placeholder="Notas" class="form-control"
                                    maxlength="1000" rows="3"></textarea>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="btn btn-outline-success fas fa-microphone-alt"
                                            id="search_vale_notas_start"></i>
                                        <i class="btn btn-outline-success fas fa-microphone-slash"
                                            id="search_vale_notas_stop"></i>
                                    </span>
                                </div>
                            </div>
                            <p id="charNumvale_notas">254 Caracteres</p>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="overflow-auto">
                            <table id="DatatableVale" class="table table-striped table-condensed table-hover"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Monto</th>
                                        <th>Notas</th>
                                        <th>Anular</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_vale"
                    form="formVales">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_vale"
                    form="formVales">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_vale"
                    form="formVales">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL REGISTRO VALES-->