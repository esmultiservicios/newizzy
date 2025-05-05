<!--INICIO MODAL TIPO USUARIO-->
<div class="modal fade" id="modal_registrar_tipoUsuario">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-user-tag mr-2"></i>Tipo de Usuario</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formTipoUsuario" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
					<input type="hidden" id="tipo_user_id" name="tipo_user_id" class="form-control">
					
                    <!-- Sección de Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="tipo_usuario_nombre"><i class="fas fa-id-card-alt mr-1"></i>Nombre <span class="priority">*</span></label>
                                    <input type="text" name="tipo_usuario_nombre" id="tipo_usuario_nombre" class="form-control" placeholder="Nombre" maxlength="20" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" required>
                                    <small class="form-text text-muted">Nombre del tipo de usuario (máx. 20 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary mb-4" id="estado_tipo_usuario">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_tipo_usuario_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="tipo_usuario_activo" name="tipo_usuario_activo" value="1" checked>
                                <label class="custom-control-label" for="tipo_usuario_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_tipo_usuario_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Activar/Desactivar este tipo de usuario</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_tipo_usuario" form="formTipoUsuario">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_tipo_usuario" form="formTipoUsuario">
                    <i class="fas fa-edit fa-lg mr-1"></i> Editar
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
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-key mr-2"></i>Permisos de Usuario</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formPermisos" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="permisos_tipo_user_id" name="permisos_tipo_user_id">
					<input type="hidden" required readonly id="permisos_nombre" name="permisos_nombre">
									                    
                    <!-- Sección de Permisos Básicos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-shield mr-2"></i>Permisos Básicos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_guardar" name="opcion_guardar" value="1">
                                        <label class="custom-control-label" for="opcion_guardar"><i class="fas fa-save mr-1"></i>Guardar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite guardar registros</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_editar" name="opcion_editar" value="1">
                                        <label class="custom-control-label" for="opcion_editar"><i class="fas fa-edit mr-1"></i>Modificar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite editar registros</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_eliminar" name="opcion_eliminar" value="1">
                                        <label class="custom-control-label" for="opcion_eliminar"><i class="fas fa-trash mr-1"></i>Eliminar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite eliminar registros</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_consultar" name="opcion_consultar" value="1">
                                        <label class="custom-control-label" for="opcion_consultar"><i class="fas fa-search mr-1"></i>Consultar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite consultar registros</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Permisos Avanzados -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-cog mr-2"></i>Permisos Avanzados</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_imprimir" name="opcion_imprimir" value="1">
                                        <label class="custom-control-label" for="opcion_imprimir"><i class="fas fa-print mr-1"></i>Imprimir</label>
                                    </div>
                                    <small class="form-text text-muted">Permite imprimir documentos</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_crear" name="opcion_crear" value="1">
                                        <label class="custom-control-label" for="opcion_crear"><i class="fas fa-plus-circle mr-1"></i>Crear</label>
                                    </div>
                                    <small class="form-text text-muted">Permite crear nuevos registros</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_reportes" name="opcion_reportes" value="1">
                                        <label class="custom-control-label" for="opcion_reportes"><i class="fas fa-chart-bar mr-1"></i>Reportes</label>
                                    </div>
                                    <small class="form-text text-muted">Permite generar reportes</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_actualizar" name="opcion_actualizar" value="1">
                                        <label class="custom-control-label" for="opcion_actualizar"><i class="fas fa-sync-alt mr-1"></i>Actualizar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite actualizar registros</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Permisos Especiales -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-lock mr-2"></i>Permisos Especiales</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_view" name="opcion_view" value="1">
                                        <label class="custom-control-label" for="opcion_view"><i class="fas fa-eye mr-1"></i>Seleccionar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite seleccionar registros</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_pay" name="opcion_pay" value="1">
                                        <label class="custom-control-label" for="opcion_pay"><i class="fas fa-money-bill-wave mr-1"></i>Cobrar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite realizar cobros y pagos</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_cambiar" name="opcion_cambiar" value="1">
                                        <label class="custom-control-label" for="opcion_cambiar"><i class="fas fa-key mr-1"></i>Cambiar Contraseña</label>
                                    </div>
                                    <small class="form-text text-muted">Permite cambiar la contraseña</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_cancelar" name="opcion_cancelar" value="1">
                                        <label class="custom-control-label" for="opcion_cancelar"><i class="fas fa-ban mr-1"></i>Cancelar</label>
                                    </div>
                                    <small class="form-text text-muted">Permite cancelar transacciones</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_sistema" name="opcion_sistema" value="1">
                                        <label class="custom-control-label" for="opcion_sistema"><i class="fas fa-desktop mr-1"></i>Sistema</label>
                                    </div>
                                    <small class="form-text text-muted">Permite ver sistemas del cliente</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="opcion_generar" name="opcion_generar" value="1">
                                        <label class="custom-control-label" for="opcion_generar"><i class="fas fa-cogs mr-1"></i>Generar Sistema</label>
                                    </div>
                                    <small class="form-text text-muted">Permite generar sistemas para clientes</small>
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
                <button class="btn btn-success" type="submit" id="reg_permisos" form="formPermisos">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE PERMISOS-->