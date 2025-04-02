<!--INICIO MODAL TIPO USUARIO-->
<div class="modal fade" id="modal_registrar_tipoUsuario">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Permisos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formTipoUsuario" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="tipo_user_id" name="tipo_user_id" class="form-control">
                                <input type="text" id="proceso_tipo_usuario" class="form-control" readonly>
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
                            <label for="prefijo">Nombre <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" name="tipo_usuario_nombre" id="tipo_usuario_nombre"
                                    class="form-control" placeholder="Nombre" maxlength="20"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-id-card-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_tipo_usuario">
						<span class="mr-2">Estado:</span>					
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="tipo_usuario_activo" name="tipo_usuario_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_tipo_usuario_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_tipo_usuario"
                    form="formTipoUsuario">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_tipo_usuario"
                    form="formTipoUsuario">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_tipo_usuario" form="formTipoUsuario">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL TIPO USAURIO-->
<!--INICIO MODAL PARA EL INGRESO DE PERMISOS-->
<div class="modal fade" id="modal_permisos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Permisos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formPermisos" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="permisos_tipo_user_id"
                                name="permisos_tipo_user_id" />
                            <input type="hidden" required="required" readonly id="permisos_nombre"
                                name="permisos_nombre" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_permisos" name="pro_permisos"
                                    class="form-control" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_guardar">Guardar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_guardar" name="opcion_guardar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_guardar"></span>
                        </div>
                        <div class="col-md-8">
                            <label for="opcion_editar">Modificar&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_editar" name="opcion_editar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_editar"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_eliminar">Eliminar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_eliminar" name="opcion_eliminar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_eliminar"></span>
                        </div>
                        <div class="col-md-8">
                            <label for="opcion_consultar">Consultar&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_consultar" name="opcion_consultar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_consultar"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_imprimir">Imprimir&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_imprimir" name="opcion_imprimir" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_imprimir"></span>
                        </div>
                        <div class="col-md-8">
                            <label
                                for="opcion_crear">Crear&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_crear" name="opcion_crear" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_crear"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_reportes">Reportes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_reportes" name="opcion_reportes" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_reportes"></span>
                        </div>
                        <div class="col-md-8">
                            <label for="opcion_actualizar">Actualizar&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_actualizar" name="opcion_actualizar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_actualizar"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_view">Seleccionar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch">
                                <input type="checkbox" id="opcion_view" name="opcion_view" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_view"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="opcion_pay">Cobrar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Realizar cobros y pagos">
                                <input type="checkbox" id="opcion_pay" name="opcion_pay" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_pay"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline" id="subMenuVentas">
                        <div class="col-md-7">
                            <label
                                for="opcion_cambiar">Cambiar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Permiso que facilita al usuario cambiar su contraseña">
                                <input type="checkbox" id="opcion_cambiar" name="opcion_cambiar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_cambiar"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="opcion_cancelar">Cancelar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Permiso que facilita al usuario cambiar su contraseña">
                                <input type="checkbox" id="opcion_cancelar" name="opcion_cancelar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_cancelar"></span>
                        </div>
                    </div>
                    <div class="form-group custom-control custom-checkbox custom-control-inline">
                        <div class="col-md-7">
                            <label
                                for="opcion_sistema">Sistema&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Permiso que facilita al administrador ver los sistemas del clietne">
                                <input type="checkbox" id="opcion_sistema" name="opcion_sistema" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_sistema"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="opcion_generar">Generar&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Permiso que facilita al administrador generar un sistema para el cliente">
                                <input type="checkbox" id="opcion_generar" name="opcion_generar" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_opcion_generar"></span>
                        </div>
                    </div>                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary ml-2" type="submit" id="reg_permisos" form="formPermisos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE PERMISOS-->