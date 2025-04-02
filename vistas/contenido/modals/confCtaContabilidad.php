<!--INICIO MODAL PARA EL INGRESO DE DIARIOS-->
<div class="modal fade" id="modalConfEntidades">
    <div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Configurar Cuentas para las Entidades</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formConfCuentasEntidades" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="diarios_id" name="diarios_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_ConfCuentasEntidades"
                                    name="pro_ConfCuentasEntidades" class="form-control" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Entidad <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" required id="confEntidad" name="confEntidad" class="form-control"
                                    placeholder="Banco" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-university"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">							
                            <label>Cuenta <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="confCuenta" name="confCuenta" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Cuenta">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_confEntidades"
                    form="formConfCuentasEntidades">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE DIARIOS-->