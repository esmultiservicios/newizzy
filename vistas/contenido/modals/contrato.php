<!--INICIO MODAL CONTRATO-->
<div class="modal fade" id="modal_registrar_contrato">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Registro de Contrato a Empleados</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formContrato" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="contrato_id" name="contrato_id" class="form-control">
                                <input type="hidden" id="colaborador_id" name="colaborador_id" class="form-control">
                                <input type="text" id="proceso_contrato" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="contrato_colaborador_id">Empleado <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="contrato_colaborador_id" name="contrato_colaborador_id" class="selectpicker"
                                    data-size="7" data-width="100%" data-live-search="true" title="Empleado">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_tipo_contrato_id">Tipo Contrato <span
                                    class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="contrato_tipo_contrato_id" name="contrato_tipo_contrato_id"
                                    class="selectpicker" data-width="100%" data-live-search="true"
                                    title="Tipo de Contrato">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_pago_planificado_id">Pago Planificado <span
                                    class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="contrato_pago_planificado_id" name="contrato_pago_planificado_id"
                                    class="selectpicker" data-width="100%" data-live-search="true"
                                    title="Pago Planificado">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_tipo_empleado_id">Tipo Empleado <span
                                    class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="contrato_tipo_empleado_id" name="contrato_tipo_empleado_id"
                                    class="selectpicker" data-width="100%" data-live-search="true" data-size="7"
                                    title="Tipo Empleado">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="contrato_salario">Salario Mensual <span class="priority">*<span /></label>
                            <input type="number" required id="contrato_salario_mensual" name="contrato_salario_mensual"
                                placeholder="Salario" class="form-control" step="0.01" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_salario">Salario</label>
                            <input type="number" required id="contrato_salario" name="contrato_salario" readonly
                                placeholder="Salario" class="form-control" step="0.01" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_fecha_inicio">Fecha Inicio <span class="priority">*<span /></label>
                            <input type="date" required id="contrato_fecha_inicio" name="contrato_fecha_inicio"
                                value="<?php echo date("Y-m-d"); ?>" class="form-control" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contrato_fecha_fin">Fecha Fin</label>
                            <input type="date" id="contrato_fecha_fin" name="contrato_fecha_fin" value=""
                                class="form-control" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="contrato_notas">Notas</label>
                            <div class="input-group">
                                <textarea id="contrato_notas" name="contrato_notas" placeholder="Notas"
                                    class="form-control" maxlength="1000" rows="3"></textarea>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="btn btn-outline-success fas fa-microphone-alt"
                                            id="search_contrato_notas_start"></i>
                                        <i class="btn btn-outline-success fas fa-microphone-slash"
                                            id="search_contrato_notas_stop"></i>
                                    </span>
                                </div>
                            </div>
                            <p id="charNum_contrato_notas">254 Caracteres</p>
                        </div>
                    </div>

                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="estado_contrato">
                        <div class="col-md-12">
                            <label class="form-check-label mr-1" for="defaultCheck1">Estado</label>
                            <label class="switch">
                                <input type="checkbox" id="contrato_activo" name="contrato_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_contrato_activo"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" style="display: none"
                        id="estado_base_semanal">
                        <div class="col-md-12">
                            <label class="form-check-label mr-1" for="defaultCheck1">¿Basado en la semana?</label>
                            <label class="switch">
                                <input type="checkbox" id="calculo_semanal" name="calculo_semanal" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_calculo_semanal"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_contrato"
                    form="formContrato">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_contrato"
                    form="formContrato">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_contrato"
                    form="formContrato">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CONTRATO-->