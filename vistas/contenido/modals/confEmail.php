<!--INICIO MODAL PARA EL INGRESO DE CORREOS-->
<div class="modal fade" id="modalConfEmails">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Correo</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formConfEmails" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="correo_id" name="correo_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_correos" name="pro_correos"
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
                        <div class="col-md-3 mb-3">
                            <label>Tipo correo <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="tipo_correo_confEmail" name="tipo_correo_confEmail" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Tipo Correo">
                                </select>
                                <div class="input-group-append" id="buscar_empresa_ubicacion">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-inbox fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Servidor <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" required id="serverConfEmail" name="serverConfEmail"
                                    class="form-control" placeholder="Banco" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-server fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Correo <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" required id="correoConfEmail" name="correoConfEmail"
                                    class="form-control" placeholder="Correo" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-envelope-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Contraseña <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="password" required id="passConfEmail" name="passConfEmail"
                                    class="form-control" placeholder="Contraseña" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-key fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label>Puerto <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" required id="puertoConfEmail" name="puertoConfEmail"
                                    class="form-control" placeholder="Puerto" class="form-control" maxlength="30"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-network-wired fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>SMTP Secure <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="smtpSecureConfEmail" name="smtpSecureConfEmail" class="selectpicker"
                                    data-live-search="true" title="SMTP">
                                </select>
                                <div class="input-group-append" id="buscar_empresa_ubicacion">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-shield-alt fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="editar btn btn-primary ml-2" type="submit" style="display: none;" id="test_confEmails"
                    form="formConfEmails">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-mail-bulk fa-lg"></i> Probar Conexión
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_confEmails"
                    form="formConfEmails">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CORREOS-->

<!--INICIO MODAL DESTINATARIOS-->
<div class="modal fade" id="modalRegistrarDestinatarios">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Destinatarios</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formDestinatarios" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="notificaciones_id" name="notificaciones_id"
                                    class="form-control">
                                <input type="text" id="proceso_destinatarios" class="form-control" readonly>
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
                            <label for="correo">Correo <span class="priority">*<span /></label>
                            <input type="email" class="form-control" id="correo" name="correo" placeholder="Correo"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="correo">Nombre <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableDestinatarios"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Correo</th>
                                            <th>Nombre</th>
                                            <th>Eliminar</th>
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_destinatarios"
                    form="formDestinatarios">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL DESTINATARIOS-->