<!--INICIO MODAL PARA EL INGRESO DE TIPO DE PAGO-->
<div class="modal fade" id="modalConfTipoPago">
    <div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tipo de Pago</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formConfTipoPago" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" class="form-control" readonly id="tipo_pago_id"
                                name="tipo_pago_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_tipoPago" name="pro_tipoPago"
                                    class="form-control" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label>Tipo de Cuenta <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="confTipoCuenta" name="confTipoCuenta" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Tipo Cuenta">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Cuenta <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="confCuentaTipoPago" name="confCuentaTipoPago" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Cuenta">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Tipo de Pago <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" required id="confTipoPago" name="confTipoPago" class="form-control"
                                    placeholder="Banco" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-money-check-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_tipo_pago">
						<span class="mr-2">Estado:</span>					
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="confTipoPago_activo" name="confTipoPago_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_confTipoPago_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_formTipoPago"
                    form="formConfTipoPago">
                    <div class="guardar sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_formTipoPago"
                    form="formConfTipoPago">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_formTipoPago" form="formConfTipoPago">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE TIPO DE PAGO-->