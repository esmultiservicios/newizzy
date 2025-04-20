<!--INICIO MODAL LOGIN-->
<div class="modal fade" id="modalLogin">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-sign-in-alt mr-2"></i>Autorización</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formLogin" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección de Credenciales -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-shield mr-2"></i>Credenciales de Acceso</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="autorizacion_user"><i class="fas fa-user mr-1"></i>Usuario <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        </div>
                                        <input type="text" id="autorizacion_user" name="autorizacion_user" class="form-control" placeholder="Nombre de usuario" required>
                                    </div>
                                    <small class="form-text text-muted">Ingrese su nombre de usuario registrado</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="autorizacion_pass"><i class="fas fa-key mr-1"></i>Contraseña <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" id="autorizacion_pass" name="autorizacion_pass" class="form-control" placeholder="Contraseña" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="show_password_login">
                                                <i class="fas fa-eye" id="togglePasswordLogin"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Ingrese su contraseña de acceso</small>
                                </div>
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
                <button type="submit" class="btn btn-primary" form="formLogin">
                    <i class="fas fa-sign-in-alt mr-1"></i> Iniciar Sesión
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL LOGIN-->

<!--INICIO MODAL PARA EL INGRESO DE CUENTAS CONTABLES-->
<div class="modal fade" id="modalCuentascontables">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-book mr-2"></i>Registro de Cuentas Contables</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formCuentasContables" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección de Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <input type="hidden" required="required" readonly id="cuentas_id" name="cuentas_id" />
                                    <label for="pro_cuentas"><i class="fas fa-search mr-1"></i>Buscar Cuenta</label>
                                    <div class="input-group">
                                        <input type="text" required readonly id="pro_cuentas" name="pro_cuentas" class="form-control" placeholder="Seleccione una cuenta contable">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" type="button" data-toggle="modal" data-target="#modal_buscar_cuentas_contables">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Seleccione una cuenta contable existente o registre una nueva</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-4 mb-3" style="display: none">
                                    <label for="cuenta_codigo"><i class="fas fa-barcode mr-1"></i>Código</label>
                                    <input type="text" id="cuenta_codigo" name="cuenta_codigo" placeholder="Código" class="form-control" maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Código único de la cuenta contable</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="cuenta_nombre"><i class="fas fa-file-signature mr-1"></i>Nombre de Cuenta <span class="priority">*</span></label>
                                    <input type="text" required id="cuenta_nombre" name="cuenta_nombre" placeholder="Nombre de la cuenta contable" class="form-control" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Nombre descriptivo de la cuenta contable (máx. 30 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado de la Cuenta</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="cuentas_activo" name="cuentas_activo" value="1" checked>
                                <label class="custom-control-label" for="cuentas_activo">Cuenta Activa</label>
                                <small class="form-text text-muted">Active o desactive el estado de la cuenta contable en el sistema</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_cuentas" form="formCuentasContables">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_cuentas" form="formCuentasContables">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_cuentas" form="formCuentasContables">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CUENTAS CONTABLES-->

<!--INICIO MODAL BUSQUEDA DE CUENTAS CONTABLES-->
<div class="modal fade" id="modal_buscar_cuentas_contables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-search mr-2"></i> Buscar Cuentas Contables</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cuentas_contables">
                    <!-- Sección de Resultados -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list-alt mr-2"></i>Resultados de Búsqueda</h5>
                        </div>
                        <div class="card-body">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaCuentasContables" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-check-circle mr-1"></i> Seleccione</th>
                                            <th><i class="fas fa-barcode mr-1"></i> Código</th>
                                            <th><i class="fas fa-file-signature mr-1"></i> Nombre</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <small class="form-text text-muted">Seleccione una cuenta contable de la lista</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE CUENTAS CONTABLES-->

<!-- MODAL REGISTRAR USUARIOS -->
<div class="modal fade" id="modal_registrar_usuarios">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Registro de Usuario del Sistema</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formUsers" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="usuarios_id" name="usuarios_id">
                    <input type="hidden" id="server_customers_id" name="server_customers_id">
                    <input type="hidden" id="colaboradores_id" name="colaboradores_id">
                    <input type="hidden" id="es_nuevo_colaborador" name="es_nuevo_colaborador" value="0">
                    
                    <!-- Pestañas para selección de colaborador -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Selección del Colaborador</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="existente-tab" data-toggle="tab" href="#existente" role="tab">
                                        <i class="fas fa-users"></i> Colaboradores Existentes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="nuevo-tab" data-toggle="tab" href="#nuevo" role="tab">
                                        <i class="fa-solid fa-user-plus"></i> Registrar Nuevo Colaborador
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="myTabContent">
                                <!-- Pestaña Colaborador Existente -->
                                <div class="tab-pane fade show active" id="existente" role="tabpanel">
                                    <div class="form-group">
                                        <label for="buscar_colaborador">Buscar Colaborador <span class="priority">*</span></label>
                                        <select id="buscar_colaborador" name="buscar_colaborador" class="selectpicker form-control"
                                            data-live-search="true" data-size="7" data-width="100%"
                                            title="Seleccione un colaborador de la lista">
                                        </select>
                                        <small class="form-text text-muted">Seleccione un colaborador existente para asignarle credenciales de usuario</small>
                                    </div>
                                        
                                    <!-- Información del colaborador seleccionado -->
                                    <div class="card border-light" id="info_colaborador" style="display: none;">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Información del Colaborador Seleccionado</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p><strong>Nombre:</strong> <span id="info_nombre"></span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Identidad:</strong> <span id="info_identidad"></span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Teléfono:</strong> <span id="info_telefono"></span></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p><strong>Fecha Ingreso:</strong> <span id="info_fecha_ingreso"></span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Estado:</strong> <span id="info_estado"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pestaña Nuevo Colaborador -->
                                <div class="tab-pane fade" id="nuevo" role="tabpanel">
                                    <div class="form-group">
                                        <label for="nombre_colaborador">Nombre Completo <span class="priority">*</span></label>
                                        <input type="text" class="form-control" id="nombre_colaborador" name="nombre_colaborador" required>
                                        <small class="form-text text-muted">Nombre completo del nuevo colaborador</small>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6 form-group">
                                            <label for="identidad_colaborador">Identidad</label>
                                            <input type="text" class="form-control" id="identidad_colaborador" name="identidad_colaborador">
                                            <small class="form-text text-muted">Número de identificación personal</small>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="telefono_colaborador">Teléfono</label>
                                            <input type="text" class="form-control" id="telefono_colaborador" name="telefono_colaborador">
                                            <small class="form-text text-muted">Número de contacto</small>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6 form-group">
                                            <label for="fecha_ingreso_colaborador">Fecha Ingreso <span class="priority">*</span></label>
                                            <input type="date" class="form-control" id="fecha_ingreso_colaborador" name="fecha_ingreso_colaborador">
                                            <small class="form-text text-muted">Fecha de ingreso a la empresa</small>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="puesto_colaborador">Puesto <span class="priority">*</span></label>
                                            <select class="selectpicker form-control" id="puesto_colaborador" name="puesto_colaborador" 
                                                data-live-search="true" title="Seleccione un puesto" required>
                                            </select>
                                            <small class="form-text text-muted">Cargo o posición del colaborador</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Credenciales de Usuario -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Credenciales de Acceso al Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label for="correo_usuario">Correo Electrónico <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" id="correo_usuario" name="correo_usuario" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Será utilizado como nombre de usuario para iniciar sesión</small>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="empresa_usuario">Empresa Asignada <span class="priority">*</span></label>
                                    <select id="empresa_usuario" name="empresa_usuario" class="selectpicker form-control" 
                                        data-live-search="true" title="Seleccione una empresa" required>
                                    </select>
                                    <small class="form-text text-muted">Empresa principal del usuario</small>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="privilegio_id">Nivel de Privilegio <span class="priority">*</span></label>
                                    <select id="privilegio_id" name="privilegio_id" class="selectpicker form-control" 
                                        data-live-search="true" title="Seleccione un privilegio" required>
                                    </select>
                                    <small class="form-text text-muted">Determina el nivel de acceso en el sistema</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label for="tipo_user">Tipo de Permisos <span class="priority">*</span></label>
                                    <select id="tipo_user" name="tipo_user" class="selectpicker form-control" 
                                        data-live-search="true" title="Seleccione permisos" required>
                                    </select>
                                    <small class="form-text text-muted">Define las funciones específicas disponibles</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Estado de la Cuenta</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="estado_usuario" name="estado_usuario" checked>
                                        <label class="custom-control-label" for="estado_usuario">Cuenta Activa</label>
                                    </div>
                                    <small class="form-text text-muted">Habilite o deshabilite el acceso al sistema</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="reg_usuario" form="formUsers" style="display: none;">
                    <i class="fas fa-save"></i> Registrar Usuario
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA BUSCAR COLABORADORES -->
<div class="modal fade" id="modal_buscar_colaboradores">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Colaborador</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" id="buscar_colaborador_input" placeholder="Buscar por nombre, identidad o teléfono">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="btn_buscar_colaborador">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabla_colaboradores">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Identidad</th>
                                <th>Teléfono</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="resultados_colaboradores">
                            <!-- Resultados de búsqueda se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!--INICIO MODAL BUSQUEDA DE COLABORADORES-->
<div class="modal fade" id="modal_buscar_colaboradores_usuarios">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Colaboradores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_coloboradores">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableColaboradoresBusqueda"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Nombre</th>
                                            <th>Identidad</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE COLABORADORES-->

<!--INICIO MODAL BUSQUEDA DE CLIENTES EN FACTURACION-->
<div class="modal fade" id="modal_buscar_clientes_facturacion">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_clientes_facturacion">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableClientesBusquedaFactura"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Cliente</th>
                                            <th>RTN</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE COLABORADORES EN FACTURACION-->

<!--INICIO MODAL CAMBIAR CONTRASEÑA -->
<div class="modal fade" id="ModalContraseña">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-key mr-2"></i>Modificar Contraseña</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="form-cambiarcontra" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <!-- Sección de Credenciales Actuales -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-lock mr-2"></i>Seguridad</h5>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="id" id="id" value="<?php echo $_SESSION['colaborador_id_sd']; ?>">
                            <input type="hidden" required="required" readonly id="id-registro" name="id-registro" style="display: none;">
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="contranaterior"><i class="fas fa-lock mr-1"></i>Contraseña Actual <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="contranaterior" class="form-control" id="contranaterior" placeholder="Ingrese su contraseña actual" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="show_password1">
                                                <i class="fas fa-eye" id="icon1"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Ingrese su contraseña actual para verificar su identidad</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Nueva Contraseña -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Nueva Contraseña</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="nuevacontra"><i class="fas fa-key mr-1"></i>Nueva Contraseña <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="nuevacontra" class="form-control" id="nuevacontra" placeholder="Ingrese su nueva contraseña" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="show_password2">
                                                <i class="fas fa-eye" id="icon2"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Cree una nueva contraseña segura</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="repcontra"><i class="fas fa-redo mr-1"></i>Confirmar Contraseña <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="repcontra" class="form-control" id="repcontra" placeholder="Confirme su nueva contraseña" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="show_password3">
                                                <i class="fas fa-eye" id="icon3"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Vuelva a ingresar la nueva contraseña para confirmar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Requisitos -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard-check mr-2"></i>Requisitos de Seguridad</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <div id="mensaje_cambiar_contra"></div>
                                    <ul title="La contraseña debe cumplir con todas estas características" id="list">
                                        <li id="mayus"><i class="fas fa-check-circle mr-1"></i> 1 Mayúscula</li>
                                        <li id="special"><i class="fas fa-check-circle mr-1"></i> 1 Caracter Especial (Símbolo)</li>
                                        <li id="numbers"><i class="fas fa-check-circle mr-1"></i> Números</li>
                                        <li id="lower"><i class="fas fa-check-circle mr-1"></i> Minúsculas</li>
                                        <li id="len"><i class="fas fa-check-circle mr-1"></i> Mínimo 8 Caracteres</li>
                                    </ul>
                                    <small class="form-text text-muted">Su contraseña debe cumplir con todos estos requisitos de seguridad</small>
                                </div>
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
                <button class="btn btn-success" type="submit" style="display: none;" id="Modalcambiarcontra_Edit" form="form-cambiarcontra">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CAMBIAR CONTRASEÑA -->

<!--INICIO MODAL PAGOS COMPRAS---->
<div class="modal fade" id="modal_pagosPurchase">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Método de pago</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row justify-content-center">
                    <div class="col-lg-12 col-12">
                        <div class="card card0">
                            <div class="d-flex" id="wrapper">
                                <!-- Sidebar -->
                                <!--<div class="bg-light border-right" id="sidebar-wrapper">
                                    <div class="sidebar-heading pt-5 pb-4"><strong>Método de pago</strong></div>
                                    <div class="list-group list-group-flush">

                                        <a data-toggle="tab" href="#menu1Purchase" id="tab1Purchase"
                                            class="tabs list-group-item bg-light active1">
                                            <div class="list-div my-2">
                                                <div class="fas fa-money-bill-alt fa-lg"></div> &nbsp;&nbsp; Efectivo
                                            </div>
                                        </a>
                                        <!--<a data-toggle="tab" href="#menu2Purchase" id="tab2Purchase"
                                            class="tabs list-group-item">
                                            <div class="list-div my-2">
                                                <div class="far fa-credit-card fa-lg"></div> &nbsp;&nbsp; Tarjeta
                                            </div>
                                        </a>
                                        <a data-toggle="tab" href="#menu3Purchase" id="tab3Purchase"
                                            class="tabs list-group-item bg-light">
                                            <div class="list-div my-2">
                                                <div class="fas fa-exchange-alt fa-lg"></div> &nbsp;&nbsp; Transferencia
                                            </div>
                                        </a>
                                        <a data-toggle="tab" href="#menu4Purchase" id="tab4Purchase"
                                            class="tabs list-group-item bg-light">
                                            <div class="list-div my-2">
                                                <div class="fas fa-money-check fa-lg"></div> &nbsp;&nbsp; Cheque
                                            </div>
                                        </a>
                                        <div class="container mt-md-0" id="GrupoPagosMultiples" style="display: none;">
                                            <p class="mb-0 mt-3">Pagos Multiples:</p>
                                            <label class="switch mb-2" data-toggle="tooltip" data-placement="top">
                                                <input type="checkbox" id="pagos_multiples_switch"
                                                    name="pagos_multiples_switch" value="0">
                                                <div class="slider round"></div>
                                            </label>
                                            <span class="question mb-2 label_pagos_multiples"
                                                id="label_pagos_multiples"></span>
                                        </div>-->

                            </div>
                            <!-- </div> Page Content -->
                            <div id="page-content-wrapper">
                                <div class="row pt-3" id="border-btm">
                                    <div class="col-2" style="display: none;">
                                        <i id="menu-toggle1Purchase"
                                            class="fas fa-angle-double-left fa-2x menu-toggle1"></i>
                                        <i id="menu-toggle2Purchase"
                                            class="fas fa-angle-double-right fa-2x menu-toggle2"></i>
                                    </div>
                                    <div class="col-10">
                                        <div class="row justify-content-right">
                                            <div class="col-12">
                                                <p class="mb-0 mr-4 mt-4 text-right" id="customer-name-Purchase">
                                                </p>
                                                <input type="hidden" name="customer_bill_pay" id="customer_Purchase_pay"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="row justify-content-right">
                                            <div class="col-12">
                                                <p class="mb-0 mr-4 text-right color-text-white"><b>Pagar</b> <span
                                                        class="top-highlight" id="Purchase-pay"></span> </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-content" style="height: auto; overflow-y:auto;">
                                    <div id="menu1Purchase" class="tab-pane in active">
                                        <div class="row justify-content-center">
                                            <div class="col-11">
                                                <div class="form-card">
                                                    <h3 class="mt-0 mb-4 text-center">Ingrese detalles del Pago</h3>
                                                    <form class="FormularioAjax" id="formEfectivoPurchase"
                                                        action="<?php echo SERVERURL; ?>ajax/addPagoComprasEfectivoAjax.php"
                                                        method="POST" data-form="save" autocomplete="off"
                                                        enctype="multipart/form-data">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label for="fecha_compras_efectivo">Fecha</label>
                                                                    <input type="date" name="fecha_compras_efectivo"
                                                                        id="fecha_compras_efectivo" class="inputfield"
                                                                        value="<?php echo date('Y-m-d'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <label>Método de Pago</label>
                                                                <div class="input-group">
                                                                    <select id="metodopago_efectivo_compras"
                                                                        name="metodopago_efectivo_compras"
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="Método de Pago" required>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label for="monto_efectivo">Pago</label>
                                                                    <input type="hidden" class="multiple_pago"
                                                                        name="multiple_pago" value="0">
                                                                    <input type="hidden" name="compras_id_efectivo"
                                                                        id="compras_id_efectivo"
                                                                        placeholder="Compra Codigo">
                                                                    <input type="hidden" name="monto_efectivoPurchase"
                                                                        id="monto_efectivoPurchase" placeholder="0.00">
                                                                    <input type="text" name="efectivo_Purchase"
                                                                        id="efectivo_Purchase" class="inputfield"
                                                                        placeholder="0.00" step="0.01">
                                                                    <input type="hidden" name="tipo_factura"
                                                                        id="tipo_purchase_efectivo" value="1">

                                                                </div>
                                                            </div>
                                                            <div class="col-12" id="grupo_cambio_compras">
                                                                <div class="input-group">
                                                                    <label for="cambio_efectivo">Cambio</label>
                                                                    <input type="number" readonly
                                                                        name="cambio_efectivoPurchase"
                                                                        id="cambio_efectivoPurchase" class="inputfield"
                                                                        step="0.01" placeholder="0.00">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label>Quien Paga</label>
                                                                <div class="input-group">
                                                                    <select id="usuario_efectivo_compras"
                                                                        name="usuario_efectivo_compras"
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="Usuario que Paga">
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <input type="submit" value="Efectuar Pago"
                                                                    id="pago_efectivo"
                                                                    class="mt-3 btn btn-info placeicon"
                                                                    form="formEfectivoPurchase">
                                                            </div>
                                                        </div>
                                                        <div class="RespuestaAjax"></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="menu2Purchase" class="tab-pane" style="display: none;">
                                        <div class="row justify-content-center">
                                            <div class="col-11">
                                                <div class="form-card">
                                                    <h3 class="mt-0 mb-4 text-center">Ingrese detalles de la Tarjeta
                                                    </h3>
                                                    <form class="FormularioAjax" id="formTarjetaPurchase" method="POST"
                                                        data-form="save"
                                                        action="<?php echo SERVERURL; ?>ajax/addPagoComprasTarjetaAjax.php"
                                                        autocomplete="off" enctype="multipart/form-data">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label for="fecha_compras_tarjeta">Fecha</label>
                                                                    <input type="date" name="fecha_compras_tarjeta"
                                                                        id="fecha_compras_tarjeta" class="inputfield"
                                                                        value="<?php echo date('Y-m-d'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label>Número de Tarjeta</label>
                                                                    <input type="hidden" class="multiple_pago"
                                                                        name="multiple_pago" value="0">
                                                                    <input type="hidden" name="compras_id_tarjeta"
                                                                        id="compras_id_tarjeta"
                                                                        placeholder="Compra Codigo">
                                                                    <input type="text" id="cr_Purchase"
                                                                        name="cr_Purchase" class="inputfield"
                                                                        placeholder="XXXX">
                                                                    <input type="hidden" name="monto_efectivoPurchase"
                                                                        id="monto_efectivoPurchase" placeholder="0.00">
                                                                    <input type="hidden" name="monto_efectivo_tarjeta"
                                                                        id="monto_efectivo_tarjeta" class="inputfield"
                                                                        step="0.01" placeholder="0.00"
                                                                        data-toggle="tooltip" data-placement="top"
                                                                        title="Ingrese el monto">
                                                                    <input type="hidden" name="tipo_factura"
                                                                        id="tipo_purchase_efectivo" value="1">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="input-group">
                                                                    <label> Fecha de Expiración</label>
                                                                    <input type="text" name="exp" id="exp"
                                                                        class="mask inputfield" placeholder="MM/YY">
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="input-group">
                                                                    <label>Número Aprobación</label>
                                                                    <input type="text" name="cvcpwd" id="cvcpwd"
                                                                        class="placeicon inputfield">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label>Quien Paga</label>
                                                                <div class="input-group">
                                                                    <select id="usuario_tarjeta_compras"
                                                                        name="usuario_tarjeta_compras"
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="Usuario que Paga">
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <input type="submit" value="Efectuar Pago"
                                                                    id="pago_tarjeta"
                                                                    class="mt-3 btn btn-info placeicon"
                                                                    form="formTarjetaPurchase">
                                                            </div>
                                                        </div>
                                                        <div class="RespuestaAjax"></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="menu3Purchase" class="tab-pane" style="display: none;">
                                        <div class="row justify-content-center">
                                            <div class="col-11">
                                                <div class="form-card">
                                                    <h3 class="mt-0 mb-4 text-center">Ingrese detalles de la
                                                        Transferencia</h3>
                                                    <form class="FormularioAjax" id="formTransferenciaPurchase"
                                                        method="POST" data-form="save"
                                                        action="<?php echo SERVERURL; ?>ajax/addPagoComprasTransferenciaAjax.php"
                                                        autocomplete="off" enctype="multipart/form-data">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label
                                                                        for="fecha_compras_transferencia">Fecha</label>
                                                                    <input type="date"
                                                                        name="fecha_compras_transferencia"
                                                                        id="fecha_compras_transferencia"
                                                                        class="inputfield"
                                                                        value="<?php echo date('Y-m-d'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <label>Banco</label>
                                                                <div class="input-group">
                                                                    <input type="hidden" name="compras_id_transferencia"
                                                                        id="compras_id_transferencia"
                                                                        placeholder="Compra Codigo">
                                                                    <select id="bk_nm" name="bk_nm" required
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="banco">
                                                                    </select>
                                                                    <input type="hidden" class="multiple_pago"
                                                                        name="multiple_pago" value="0">
                                                                    <input type="hidden" name="importe_transferencia"
                                                                        id="importe_transferencia"
                                                                        class="inputfield mt-5" step="0.01"
                                                                        placeholder="0.00" data-toggle="tooltip"
                                                                        data-placement="top" title="Ingrese el monto">
                                                                    <input type="hidden" name="monto_efectivoPurchase"
                                                                        id="monto_efectivoPurchase" placeholder="0.00">
                                                                    <input type="hidden" name="tipo_factura"
                                                                        id="tipo_purchase_efectivo" value="1">

                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label>Número de Autorización</label>
                                                                    <input type="text" name="ben_nm" id="ben_nm"
                                                                        class="inputfield"
                                                                        placeholder="Número de Autorización">
                                                                </div>
                                                            </div>
                                                            <div class="col-12" style="display: none;">
                                                                <div class="input-group">
                                                                    <input type="text" name="scode"
                                                                        placeholder="ABCDAB1S" class="placeicon"
                                                                        minlength="8" maxlength="11">
                                                                    <label>SWIFT CODE</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label>Quien Paga</label>
                                                                <div class="input-group">
                                                                    <select id="usuario_transferencia_compras"
                                                                        name="usuario_transferencia_compras"
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="Usuario que Paga">
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <input type="submit" value="Efectuar Pago"
                                                                    id="pago_transferencia"
                                                                    class="mt-3 btn btn-info placeicon"
                                                                    form="formTransferenciaPurchase">
                                                            </div>
                                                        </div>
                                                        <div class="RespuestaAjax"></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="menu4Purchase" class="tab-pane" style="display: none;">
                                        <div class="row justify-content-center">
                                            <div class="col-11">
                                                <div class="form-card">
                                                    <h3 class="mt-0 mb-4 text-center">Ingrese detalles del Cheque
                                                    </h3>
                                                    <form class="FormularioAjax" id="formChequePurchase" method="POST"
                                                        data-form="save"
                                                        action="<?php echo SERVERURL; ?>ajax/addPagoComprasChequeAjax.php"
                                                        autocomplete="off" enctype="multipart/form-data">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label for="fecha_compras_cheque">Fecha</label>
                                                                    <input type="date" name="fecha_compras_cheque"
                                                                        id="fecha_compras_cheque" class="inputfield"
                                                                        value="<?php echo date('Y-m-d'); ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <label>Banco</label>
                                                                <div class="input-group">
                                                                    <input type="hidden" name="compras_id_cheque"
                                                                        id="compras_id_cheque">
                                                                    <select id="bk_nm_chk" name="bk_nm_chk" required
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="banco">
                                                                    </select>
                                                                    <input type="hidden" class="multiple_pago"
                                                                        name="multiple_pago" value="0">
                                                                    <input type="number" name="importe_cheque"
                                                                        id="importe_cheque" class="inputfield mt-5"
                                                                        step="0.01" placeholder="0.00"
                                                                        data-toggle="tooltip" data-placement="top"
                                                                        title="Ingrese el monto">
                                                                    <input type="hidden" name="tipo_factura"
                                                                        id="tipo_purchase_efectivo" value="1">
                                                                    <input type="hidden" name="monto_efectivoPurchase"
                                                                        id="monto_efectivoPurchase" placeholder="0.00">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <label>Número de Cheque</label>
                                                                    <input type="text" name="check_num" id="check_num"
                                                                        class="inputfield"
                                                                        placeholder="Número de Cheque">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label>Quien Paga</label>
                                                                <div class="input-group">
                                                                    <select id="usuario_cheque_compras"
                                                                        name="usuario_cheque_compras"
                                                                        class="selectpicker col-12" data-size="5"
                                                                        data-width="100%" data-live-search="true"
                                                                        title="Usuario que Paga">
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <input type="submit" value="Efectuar Pago"
                                                                    id="pago_transferencia"
                                                                    class="mt-3 btn btn-info placeicon"
                                                                    form="formChequePurchase">
                                                            </div>
                                                        </div>
                                                        <div class="RespuestaAjax"></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>
</div>
<!--FIN MODAL PAGOS COMPRAS-->

<!--INICIO MODAL PAGOS FACTURACION---->
<div class="modal fade" id="modal_pagos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Método de pago</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row justify-content-center">
                    <div class="col-lg-12 col-12">
                        <div class="card card0">
                            <div class="d-flex" id="wrapper">
                                <!-- Sidebar -->
                                <div class="bg-light border-right" id="sidebar-wrapper"
                                    style="scroll-behavior: smooth;">
                                    <div class="sidebar-heading pt-5 pb-4"><strong>Método de pago</strong></div>
                                    <div class="list-group list-group-flush">

                                        <a data-toggle="tab" href="#menu1" id="tab1"
                                            class="tabs list-group-item bg-light active1">
                                            <div class="list-div my-2">
                                                <div class="fas fa-money-bill-alt fa-lg"></div> &nbsp;&nbsp; Efectivo
                                            </div>
                                        </a>
                                        <a data-toggle="tab" href="#menu2" id="tab2" class="tabs list-group-item">
                                            <div class="list-div my-2">
                                                <div class="far fa-credit-card fa-lg"></div> &nbsp;&nbsp; Tarjeta
                                            </div>
                                        </a>
                                        <a data-toggle="tab" href="#menu3" id="tab3"
                                            class="tabs list-group-item bg-light">
                                            <div class="list-div my-2">
                                                <div class="fas fa-exchange-alt fa-lg"></div> &nbsp;&nbsp; Transferencia
                                            </div>
                                        </a>
                                        <a data-toggle="tab" href="#menu4" id="tab4"
                                            class="tabs list-group-item bg-light">
                                            <div class="list-div my-2">
                                                <div class="fas fa-money-check fa-lg"></div> &nbsp;&nbsp; Cheque
                                            </div>
                                        </a>
                                        <div class="container mt-md-0">
                                            <p class="mb-0 mt-3">Imprimir Comprobante de Entrega:</p>
                                            <label class="switch mb-2" data-toggle="tooltip" data-placement="top">
                                                <input type="checkbox" id="" name="comprobante_print_switch" value="0">
                                                <div class="slider round"></div>
                                            </label>
                                            <span class="question mb-2" id="label_print_comprobant"></span>
                                        </div>
                                        <div class="container mt-md-0" id="GrupoPagosMultiplesFacturas">
                                            <p class="mb-0 mt-3">Pagos Multiples:</p>
                                            <label class="switch mb-2" data-toggle="tooltip" data-placement="top">
                                                <input type="checkbox" id="pagos_multiples_switch"
                                                    name="pagos_multiples_switch" value="0">
                                                <div class="slider round"></div>
                                            </label>
                                            <span class="question mb-2" id="label_pagos_multiples"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Page Content -->
                                <div id="page-content-wrapper" style="scroll-behavior: smooth;">
                                    <div class="row pt-3" id="border-btm">
                                        <div class="col-2">
                                            <i id="menu-toggle1"
                                                class="fas fa-angle-double-left fa-2x menu-toggle1"></i>
                                            <i id="menu-toggle2"
                                                class="fas fa-angle-double-right fa-2x menu-toggle2"></i>
                                        </div>
                                        <div class="col-10">
                                            <div class="row justify-content-right">
                                                <div class="col-12">
                                                    <p class="mb-0 mr-4 mt-4 text-right" id="customer-name-bill"></p>
                                                    <input type="hidden" name="customer_bill_pay" id="customer_bill_pay"
                                                        placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="row justify-content-right">
                                                <div class="col-12">
                                                    <p class="mb-0 mr-4 text-right color-text-white"><b>Pagar</b> <span
                                                            class="top-highlight" id="bill-pay"></span> </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-content">
                                        <div id="menu1" class="tab-pane in active">
                                            <div class="row justify-content-center">
                                                <div class="col-11">
                                                    <div class="form-card">
                                                        <h3 class="mt-0 mb-4 text-center">Ingrese detalles del Pago</h3>
                                                        <form class="FormularioAjax" id="formEfectivoBill"
                                                            action="<?php echo SERVERURL; ?>ajax/addPagoFacturasEfectivoAjax.php"
                                                            method="POST" data-form="save" autocomplete="off"
                                                            enctype="multipart/form-data">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="fecha_efectivo">Fecha</label>
                                                                        <input type="date" name="fecha_efectivo"
                                                                            id="fecha_efectivo" class="inputfield"
                                                                            value="<?php echo date('Y-m-d'); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="monto_efectivo">Efectivo</label>
                                                                        <input type="hidden"
                                                                            class="comprobante_print_value"
                                                                            name="comprobante_print" value="0">
                                                                        <input type="hidden" class="multiple_pago"
                                                                            name="multiple_pago" value="0">
                                                                        <input type="hidden" name="factura_id_efectivo"
                                                                            id="factura_id_efectivo">
                                                                        <input type="hidden" name="tipo_factura"
                                                                            id="tipo_factura" value="1">
                                                                        <input type="hidden" name="monto_efectivo"
                                                                            id="monto_efectivo" step="0.01"
                                                                            placeholder="0.00">
                                                                        <input type="number" name="efectivo_bill"
                                                                            id="efectivo_bill" class="inputfield"
                                                                            step="0.01" placeholder="0.00" step="0.01">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="input-group" id="grupo_cambio_efectivo">
                                                                        <label for="cambio_efectivo">Cambio</label>
                                                                        <input type="number" readonly
                                                                            name="cambio_efectivo" id="cambio_efectivo"
                                                                            class="inputfield" step="0.01"
                                                                            placeholder="0.00">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <label>Quien Recibe</label>
                                                                    <div class="input-group">
                                                                        <select id="usuario_efectivo"
                                                                            name="usuario_efectivo"
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Usuario que Recibe">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <input type="submit" value="Efectuar Pago"
                                                                        id="pago_efectivo"
                                                                        class="mt-3 btn btn-info placeicon"
                                                                        form="formEfectivoBill">
                                                                </div>
                                                            </div>
                                                            <div class="RespuestaAjax"></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="menu2" class="tab-pane">
                                            <div class="row justify-content-center">
                                                <div class="col-11">
                                                    <div class="form-card">
                                                        <h3 class="mt-0 mb-4 text-center">Ingrese detalles de la Tarjeta
                                                        </h3>
                                                        <form class="FormularioAjax" id="formTarjetaBill" method="POST"
                                                            data-form="save"
                                                            action="<?php echo SERVERURL; ?>ajax/addPagoFacturasTarjetaAjax.php"
                                                            autocomplete="off" enctype="multipart/form-data">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="fecha_tarjeta">Fecha</label>
                                                                        <input type="date" name="fecha_tarjeta"
                                                                            id="fecha_tarjeta" class="inputfield"
                                                                            value="<?php echo date('Y-m-d'); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label>Número de Tarjeta</label>
                                                                        <input type="hidden" name="factura_id_tarjeta"
                                                                            id="factura_id_tarjeta">
                                                                        <input type="hidden"
                                                                            class="comprobante_print_value"
                                                                            name="comprobante_print" value="0">
                                                                        <input type="hidden" class="multiple_pago"
                                                                            name="multiple_pago" value="0">
                                                                        <input type="text" id="cr_bill" name="cr_bill"
                                                                            class="inputfield" placeholder="XXXX">
                                                                        <input type="number" style="display:none;"
                                                                            name="monto_efectivo"
                                                                            id="monto_efectivo_tarjeta"
                                                                            class="inputfield" step="0.01"
                                                                            placeholder="0.00" data-toggle="tooltip"
                                                                            data-placement="top"
                                                                            title="Ingrese el monto">
                                                                        <input type="hidden" name="importe"
                                                                            id="importe_tarjeta" class="inputfield"
                                                                            step="0.01" placeholder="0.00">
                                                                        <input type="hidden" name="tipo_factura"
                                                                            id="tipo_factura" value="1">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="input-group">
                                                                        <label> Fecha de Expiración</label>
                                                                        <input type="text" name="exp" id="exp"
                                                                            class="mask inputfield" placeholder="MM/YY">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="input-group">
                                                                        <label>Número Aprobación</label>
                                                                        <input type="text" name="cvcpwd" id="cvcpwd"
                                                                            class="placeicon inputfield">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label>Quien Recibe</label>
                                                                    <div class="input-group">
                                                                        <select id="usuario_tarjeta"
                                                                            name="usuario_tarjeta"
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Usuario que Recibe">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <input type="submit" value="Efectuar Pago"
                                                                        id="pago_tarjeta"
                                                                        class="mt-3 btn btn-info placeicon"
                                                                        form="formTarjetaBill">
                                                                </div>
                                                            </div>
                                                            <div class="RespuestaAjax"></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="menu5" class="tab-pane">
                                            <div class="row justify-content-center">
                                                <div class="col-11">
                                                    <div class="form-card">
                                                        <h6 class="mt-0 mb-4 text-center">Ingrese Pago Mixto</h6>
                                                        <form class="FormularioAjax" id="formMixtoBill"
                                                            action="<?php echo SERVERURL; ?>ajax/addPagoMixtoAjax.php"
                                                            method="POST" data-form="save" autocomplete="off"
                                                            enctype="multipart/form-data">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="fecha_efectivo_mixto">Fecha</label>
                                                                        <input type="date" name="fecha_efectivo_mixto"
                                                                            id="fecha_efectivo_mixto" class="inputfield"
                                                                            value="<?php echo date('Y-m-d'); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-md-6">
                                                                    <div class="input-group">
                                                                        <label for="monto_efectivo">Efectivo</label>
                                                                        <input type="hidden"
                                                                            class="comprobante_print_value"
                                                                            name="comprobante_print" value="0">
                                                                        <input type="hidden" class="multiple_pago"
                                                                            name="multiple_pago" value="0">
                                                                        <input type="hidden" name="factura_id_mixto"
                                                                            id="factura_id_mixto">
                                                                        <input type="hidden" name="monto_efectivo"
                                                                            id="monto_efectivo_mixto" step="0.01"
                                                                            placeholder="0.00" data-toggle="tooltip"
                                                                            data-placement="top"
                                                                            title="Ingrese el monto">
                                                                        <input type="number" name="efectivo_bill"
                                                                            id="efectivo_bill_mixto" class="inputfield"
                                                                            step="0.01" placeholder="0.00" step="0.01">
                                                                        <input type="hidden" readonly
                                                                            name="cambio_efectivo"
                                                                            id="cambio_efectivo_mixto"
                                                                            class="inputfield" step="0.01"
                                                                            placeholder="0.00">
                                                                    </div>
                                                                </div>

                                                                <div class="col-12 col-md-6">
                                                                    <div class="input-group">
                                                                        <label for="monto_tarjeta">Tarjeta</label>
                                                                        <input type="number" readonly
                                                                            name="monto_tarjeta" id="monto_tarjeta"
                                                                            class="inputfield" step="0.01"
                                                                            placeholder="0.00">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label>Número de Tarjeta</label>
                                                                        <input type="text" id="cr_bill_mixto"
                                                                            name="cr_bill" class="inputfield"
                                                                            placeholder="XXXX">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="input-group">
                                                                        <label> Fecha de Expiración</label>
                                                                        <input type="text" name="exp" id="exp_mixto"
                                                                            class="mask inputfield" placeholder="MM/YY">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="input-group">
                                                                        <label>Número Aprobación</label>
                                                                        <input type="text" name="cvcpwd"
                                                                            id="cvcpwd_mixto"
                                                                            class="placeicon inputfield">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label>Quien Recibe</label>
                                                                    <div class="input-group">
                                                                        <select id="usuario_pago_mixto"
                                                                            name="usuario_pago_mixto"
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Usuario que Recibe">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <input type="submit" value="Efectuar Pago"
                                                                        id="pago_efectivo_mixto"
                                                                        class="mt-3 btn btn-info placeicon"
                                                                        form="formMixtoBill">
                                                                </div>
                                                            </div>
                                                            <div class="RespuestaAjax"></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="menu3" class="tab-pane">
                                            <div class="row justify-content-center">
                                                <div class="col-11">
                                                    <div class="form-card">
                                                        <h3 class="mt-0 mb-4 text-center">Ingrese detalles de la
                                                            Transferencia</h3>
                                                        <form class="FormularioAjax" id="formTransferenciaBill"
                                                            method="POST" data-form="save"
                                                            action="<?php echo SERVERURL; ?>ajax/addPagoFacturasTransferenciaAjax.php"
                                                            autocomplete="off" enctype="multipart/form-data">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="fecha_transferencia">Fecha</label>
                                                                        <input type="date" name="fecha_transferencia"
                                                                            id="fecha_transferencia" class="inputfield"
                                                                            value="<?php echo date('Y-m-d'); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12 mb-3">
                                                                    <label>Banco</label>
                                                                    <div class="input-group">
                                                                        <input type="hidden"
                                                                            name="factura_id_transferencia"
                                                                            id="factura_id_transferencia">
                                                                        <select id="bk_nm" name="bk_nm" required
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Banco">
                                                                        </select>
                                                                        <input type="hidden" class="multiple_pago"
                                                                            name="multiple_pago" value="0">
                                                                        <input type="hidden"
                                                                            class="comprobante_print_value"
                                                                            name="comprobante_print" value="0">
                                                                        <input type="hidden" name="monto_efectivo"
                                                                            id="monto_efectivo" placeholder="0.00">
                                                                        <input type="number" name="importe"
                                                                            id="importe_transferencia"
                                                                            class="inputfield mt-5" step="0.01"
                                                                            placeholder="0.00" data-toggle="tooltip"
                                                                            data-placement="top"
                                                                            title="Ingrese el monto">
                                                                        <input type="hidden" name="tipo_factura"
                                                                            id="tipo_factura_transferencia" value="1"
                                                                            step="0.01" placeholder="0.00">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label>Número de Autorización</label>
                                                                        <input type="text" name="ben_nm" id="ben_nm"
                                                                            class="inputfield"
                                                                            placeholder="Número de Autorización">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12" style="display: none;">
                                                                    <div class="input-group">
                                                                        <input type="text" name="scode"
                                                                            placeholder="ABCDAB1S" class="placeicon"
                                                                            minlength="8" maxlength="11">
                                                                        <label>SWIFT CODE</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label>Quien Recibe</label>
                                                                    <div class="input-group">
                                                                        <select id="usuario_transferencia"
                                                                            name="usuario_transferencia"
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Usuario que Recibe">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <input type="submit" value="Efectuar Pago"
                                                                        id="pago_transferencia"
                                                                        class="mt-3 btn btn-info placeicon"
                                                                        form="formTransferenciaBill">
                                                                </div>
                                                            </div>
                                                            <div class="RespuestaAjax"></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="menu4" class="tab-pane">
                                            <div class="row justify-content-center">
                                                <div class="col-11">
                                                    <div class="form-card">
                                                        <h3 class="mt-0 mb-4 text-center">Ingrese detalles del Cheque
                                                        </h3>
                                                        <form class="FormularioAjax" id="formChequeBill" method="POST"
                                                            data-form="save"
                                                            action="<?php echo SERVERURL; ?>ajax/addPagoFacturasChequeAjax.php"
                                                            autocomplete="off" enctype="multipart/form-data">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label for="fecha_cheque">Fecha</label>
                                                                        <input type="date" name="fecha_cheque"
                                                                            id="fecha_cheque" class="inputfield"
                                                                            value="<?php echo date('Y-m-d'); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <label>Banco</label>
                                                                    <div class="input-group">
                                                                        <input type="hidden" class="multiple_pago"
                                                                            name="multiple_pago" value="0">
                                                                        <input type="hidden"
                                                                            class="comprobante_print_value"
                                                                            name="comprobante_print" value="0">
                                                                        <input type="hidden" name="factura_id_cheque"
                                                                            id="factura_id_cheque">
                                                                        <select id="bk_nm_chk" name="bk_nm_chk" required
                                                                            data-size="5" class="selectpicker col-12"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Banco">
                                                                        </select>
                                                                        <input type="hidden" name="monto_efectivo"
                                                                            id="monto_efectivo" placeholder="0.00">
                                                                        <input type="number" name="importe"
                                                                            id="importe_cheque" class="inputfield mt-5"
                                                                            step="0.01" placeholder="0.00"
                                                                            data-toggle="tooltip" data-placement="top"
                                                                            title="Ingrese el monto">
                                                                        <input type="hidden" name="tipo_factura"
                                                                            id="tipo_factura_cheque" value="1"
                                                                            step="0.01" placeholder="0.00">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="input-group">
                                                                        <label>Número de Cheque</label>
                                                                        <input type="text" name="check_num"
                                                                            id="check_num" class="inputfield"
                                                                            placeholder="Número de Cheque">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label>Quien Recibe</label>
                                                                    <div class="input-group">
                                                                        <select id="usuario_cheque"
                                                                            name="usuario_cheque"
                                                                            class="selectpicker col-12" data-size="5"
                                                                            data-width="100%" data-live-search="true"
                                                                            title="Usuario que Recibe">
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <input type="submit" value="Efectuar Pago"
                                                                        id="pago_transferencia"
                                                                        class="mt-3 btn btn-info placeicon"
                                                                        form="formChequeBill">
                                                                </div>
                                                            </div>
                                                            <div class="RespuestaAjax"></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PAGOS FACTURACION-->

<!-- MODAL PAGOS UNIFICADO MEJORADO -->
<div class="modal fade" id="modal_pagos_unificado" tabindex="-1" aria-hidden="true" data-keyboard="false" data-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered payment-modal">
    <div class="modal-content payment-content">
      <!-- Header con progreso -->
      <div class="modal-header payment-header">
        <div class="payment-steps">
          <div class="step active" data-step="1">
            <div class="step-icon"><i class="fas fa-wallet"></i></div>
            <div class="step-label">Método</div>
          </div>
          <div class="step" data-step="2">
            <div class="step-icon"><i class="fas fa-edit"></i></div>
            <div class="step-label">Detalles</div>
          </div>
          <div class="step" data-step="3">
            <div class="step-icon"><i class="fas fa-check"></i></div>
            <div class="step-label">Confirmar</div>
          </div>
        </div>
        <button type="button" class="btn-close payment-close" data-dismiss="modal" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <!-- Cuerpo del modal -->
      <div class="modal-body payment-body">
        <!-- Información de pago común -->
        <div class="payment-info-card">
          <div class="customer-info">
            <span id="customer-name-payment"></span>
            <input type="hidden" name="customer_payment_id" id="customer_payment_id">
          </div>
          <div class="amount-info">
            <span>Total a pagar:</span>
            <span class="amount" id="payment-amount">L 0.00</span>
          </div>
        </div>
        <!-- Paso 1: Selección de método -->
        <div class="payment-step active" data-step-content="1">
          <div class="payment-methods-container">
            <div class="payment-methods-grid">
              <!-- Efectivo -->
              <div class="method-card selected" data-method="cash">
                <div class="method-icon">
                  <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="method-name">Efectivo</div>
                <div class="method-badge">Rápido</div>
                <i class="fas fa-info-circle info-icon" id="cash-info"></i>
              </div>
              <!-- Tarjeta -->
              <div class="method-card" data-method="card">
                <div class="method-icon">
                  <i class="far fa-credit-card"></i>
                </div>
                <div class="method-name">Tarjeta</div>
                <div class="method-badge">Seguro</div>
                <i class="fas fa-info-circle info-icon" id="card-info"></i>
              </div>
              <!-- Transferencia -->
              <div class="method-card" data-method="transfer">
                <div class="method-icon">
                  <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="method-name">Transferencia</div>
                <i class="fas fa-info-circle info-icon" id="transfer-info"></i>
              </div>
              <!-- Cheque -->
              <div class="method-card" data-method="check">
                <div class="method-icon">
                  <i class="fas fa-money-check"></i>
                </div>
                <div class="method-name">Cheque</div>
                <i class="fas fa-info-circle info-icon" id="check-info"></i>
              </div>
              <!-- Puntos -->
              <div class="method-card premium" data-method="points" style="display: none;">
                <div class="method-icon">
                  <i class="fas fa-coins"></i>
                </div>
                <div class="method-name">Puntos</div>
                <div class="method-badge">Exclusivo</div>
                <i class="fas fa-info-circle info-icon" id="points-info"></i>
              </div>
            </div>
          </div>
          <!-- Configuración adicional -->
          <div class="payment-options-card" id="factura-options" style="display: none;">
            <div class="option-item">
              <label class="payment-switch">
                <input type="checkbox" id="pagos_multiples_switch" name="pagos_multiples_switch">
                <div class="payment-slider round"></div>
                <span class="switch-label">Pagos múltiples</span>
              </label>
            </div>
            <div class="option-item">
              <label class="payment-switch">
                <input type="checkbox" id="comprobante_print_switch" name="comprobante_print_switch">
                <div class="payment-slider round"></div>
                <span class="switch-label">Imprimir comprobante</span>
              </label>
            </div>
          </div>
        </div>
        <!-- Paso 2: Detalles de pago -->
        <div class="payment-step" data-step-content="2">
          <!-- Contenido dinámico -->
          <div class="payment-details-container">
            <!-- Efectivo -->
            <div class="payment-details active" data-method="cash">
              <div class="detail-header">
                <div class="method-display">
                  <i class="fas fa-money-bill-wave"></i>
                  <span>Pago en Efectivo</span>
                </div>
              </div>
              <form id="form-efectivo" class="detail-form">
                <div class="payment-form-group">
                  <input type="number" id="cash_amount" class="payment-form-control" placeholder=" " step="0.01" required>
                  <label for="cash_amount">Monto recibido</label>
                  <div class="currency-symbol">L</div>
                </div>
                <div class="change-display">
                  <span>Cambio:</span>
                  <span class="change-amount">L 0.00</span>
                </div>
              </form>
            </div>
            <!-- Tarjeta -->
            <div class="payment-details" data-method="card">
              <div class="detail-header">
                <div class="method-display">
                  <i class="far fa-credit-card"></i>
                  <span>Pago con Tarjeta</span>
                </div>
              </div>
              <form id="form-tarjeta" class="detail-form">
                <div class="payment-form-group">
                  <select id="card_type" class="payment-form-control" required>
                    <option value=""></option>
                    <option value="visa">Visa</option>
                    <option value="mastercard">Mastercard</option>
                    <option value="amex">American Express</option>
                  </select>
                  <label for="card_type">Tipo de tarjeta</label>
                </div>
                <div class="payment-form-group">
                  <input type="text" id="card_last_four" class="payment-form-control" placeholder=" " maxlength="4" required>
                  <label for="card_last_four">Últimos 4 dígitos</label>
                </div>
                <div class="payment-form-group">
                  <input type="text" id="card_auth_code" class="payment-form-control" placeholder=" " required>
                  <label for="card_auth_code">Código de autorización</label>
                </div>
                <div class="payment-form-group">
                  <input type="number" id="card_amount" class="payment-form-control" placeholder=" " step="0.01" required>
                  <label for="card_amount">Monto</label>
                  <div class="currency-symbol">L</div>
                </div>
              </form>
            </div>
            <!-- Transferencia -->
            <div class="payment-details" data-method="transfer">
              <div class="detail-header">
                <div class="method-display">
                  <i class="fas fa-exchange-alt"></i>
                  <span>Transferencia Bancaria</span>
                </div>
              </div>
              <form id="form-transferencia" class="detail-form">
                <div class="payment-form-group">
                  <select id="transfer_bank" class="payment-form-control" required>
                    <option value=""></option>
                    <option value="BAC">BAC</option>
                    <option value="Ficohsa">Ficohsa</option>
                    <option value="Lafise">Lafise</option>
                  </select>
                  <label for="transfer_bank">Banco</label>
                </div>
                <div class="payment-form-group">
                  <input type="text" id="transfer_reference" class="payment-form-control" placeholder=" " required>
                  <label for="transfer_reference">Número de referencia</label>
                </div>
                <div class="payment-form-group">
                  <input type="number" id="transfer_amount" class="payment-form-control" placeholder=" " step="0.01" required>
                  <label for="transfer_amount">Monto</label>
                  <div class="currency-symbol">L</div>
                </div>
              </form>
            </div>
            <!-- Cheque -->
            <div class="payment-details" data-method="check">
              <div class="detail-header">
                <div class="method-display">
                  <i class="fas fa-money-check"></i>
                  <span>Pago con Cheque</span>
                </div>
              </div>
              <form id="form-cheque" class="detail-form">
                <div class="payment-form-group">
                  <select id="check_bank" class="payment-form-control" required>
                    <option value=""></option>
                    <option value="BAC">BAC</option>
                    <option value="Ficohsa">Ficohsa</option>
                    <option value="Lafise">Lafise</option>
                  </select>
                  <label for="check_bank">Banco</label>
                </div>
                <div class="payment-form-group">
                  <input type="text" id="check_number" class="payment-form-control" placeholder=" " required>
                  <label for="check_number">Número de cheque</label>
                </div>
                <div class="payment-form-group">
                  <input type="number" id="check_amount" class="payment-form-control" placeholder=" " step="0.01" required>
                  <label for="check_amount">Monto</label>
                  <div class="currency-symbol">L</div>
                </div>
              </form>
            </div>
            <!-- Puntos -->
            <div class="payment-details" data-method="points">
              <div class="detail-header">
                <div class="method-display">
                  <i class="fas fa-coins"></i>
                  <span>Pago con Puntos</span>
                </div>
              </div>
              <form id="form-puntos" class="detail-form">
                <div class="points-balance">
                  <span>Puntos disponibles:</span>
                  <span class="points-amount">0 pts (L 0.00)</span>
                </div>
                <div class="payment-form-group">
                  <input type="number" id="points_amount" class="payment-form-control" placeholder=" " step="100" required>
                  <label for="points_amount">Puntos a usar</label>
                  <div class="points-symbol">pts</div>
                </div>
                <div class="points-conversion">
                  <span>Equivalente:</span>
                  <span class="converted-amount">L 0.00</span>
                </div>
              </form>
            </div>
          </div>
          <!-- Sección común -->
          <div class="payment-receiver-section">
            <div class="payment-form-group">
              <select id="payment_receiver" name="payment_receiver" class="payment-form-control" required>
                <option value=""></option>
                <option value="1">Cajero Principal</option>
                <option value="2">Asistente de Ventas</option>
                <option value="3">Gerente</option>
              </select>
              <label for="payment_receiver">Quien recibe</label>
            </div>
          </div>
        </div>
        <!-- Paso 3: Confirmación -->
        <div class="payment-step" data-step-content="3">
          <div class="payment-complete">
            <div class="complete-icon">
              <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
              </svg>
            </div>
            <h3 class="complete-title">¡Pago completado!</h3>
            <p class="receipt-amount">L 0.00</p>
            <div class="receipt-details">
              <div class="detail">
                <span>Método:</span>
                <span class="method-used">-</span>
              </div>
              <div class="detail">
                <span>Transacción:</span>
                <span class="transaction-id">#PAY-0000</span>
              </div>
              <div class="detail">
                <span>Fecha:</span>
                <span class="transaction-date">-</span>
              </div>
              <div class="detail">
                <span>Recibido por:</span>
                <span class="receiver-name">-</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Footer del modal -->
      <div class="modal-footer payment-actions">
        <button type="button" class="btn payment-btn payment-btn-prev" disabled>
          <i class="fas fa-arrow-left"></i> Atrás
        </button>
        <button type="button" class="btn payment-btn payment-btn-next">
          Continuar <i class="fas fa-arrow-right"></i>
        </button>
        <button type="submit" class="btn payment-btn payment-btn-complete" style="display: none;">
          Finalizar pago <i class="fas fa-check"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!--INICIO MODAL CLIENTES-->
<div class="modal fade" id="modal_registrar_clientes">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-user-tie mr-2"></i>Registro de Clientes</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formClientes" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="clientes_id" name="clientes_id" class="form-control">
                    
                    <!-- Sección de Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-8 mb-3">
                                    <label for="nombre_clientes">Nombre/Razón Social <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-signature"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="nombre_clientes" name="nombre_clientes" placeholder="Nombre completo o razón social" maxlength="100" required>
                                    </div>
                                    <small class="form-text text-muted">Ingrese el nombre completo o razón social (como aparece en documentos legales)</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="identidad_clientes">Identidad/RTN</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="number" class="form-control" id="identidad_clientes" name="identidad_clientes" placeholder="Número de identidad o RTN" maxlength="14" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                        <div class="input-group-append" id="grupo_editar_rtn">
                                            <button type="button" class="btn btn-outline-success editar_rtn" data-toggle="tooltip" title="Editar RTN">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Número de identidad (13 dígitos) o RTN (14 dígitos)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_clientes">Fecha de Registro <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" required id="fecha_clientes" name="fecha_clientes" value="<?php echo date('Y-m-d'); ?>" class="form-control">
                                    </div>
                                    <small class="form-text text-muted">Fecha en que se registra al cliente</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="departamento_cliente">Departamento</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                        </div>
                                        <select class="selectpicker form-control" id="departamento_cliente" name="departamento_cliente" data-live-search="true" title="Seleccione un departamento">
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">Departamento donde reside el cliente</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="municipio_cliente">Municipio</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        </div>
                                        <select class="selectpicker form-control" id="municipio_cliente" name="municipio_cliente" data-live-search="true" title="Seleccione un municipio">
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">Municipio donde reside el cliente</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="telefono_clientes">Teléfono</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="number" class="form-control" id="telefono_clientes" name="telefono_clientes" placeholder="Número de teléfono" maxlength="8" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    </div>
                                    <small class="form-text text-muted">Número de contacto principal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Contacto y Ubicación -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Contacto y Ubicación</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="dirección_clientes">Dirección Completa</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="dirección_clientes" name="dirección_clientes" placeholder="Dirección exacta" maxlength="150">
                                    </div>
                                    <small class="form-text text-muted">Dirección exacta incluyendo puntos de referencia</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="correo_clientes">Correo Electrónico</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" class="form-control" placeholder="Correo electrónico" id="correo_clientes" name="correo_clientes" maxlength="70">
                                        <div class="input-group-append">
                                            <span class="input-group-text">@ejemplo.com</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Correo electrónico para contacto y notificaciones</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado del Cliente</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="clientes_activo" name="clientes_activo" checked>
                                    <label class="custom-control-label" for="clientes_activo"><i class="fas fa-user-check mr-1"></i>Cliente Activo</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado del cliente en el sistema</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_cliente" form="formClientes">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_cliente" form="formClientes">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_cliente" form="formClientes">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CLIENTES-->

<!--INICIO MODAL PROVEEDORES-->
<div class="modal fade" id="modal_registrar_proveedores">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-truck mr-2"></i>Registro de Proveedores</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProveedores" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="proveedores_id" name="proveedores_id" class="form-control">
   
					
                    <!-- Sección de Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
                        <div class="card-body">                           
                            <div class="form-row">
                                <div class="col-md-8 mb-3">
                                    <label for="nombre_proveedores"><i class="fas fa-truck mr-1"></i>Proveedor <span class="priority">*</span></label>
                                    <input type="text" class="form-control" id="nombre_proveedores" name="nombre_proveedores" placeholder="Nombre del proveedor" maxlength="150" required>
                                    <small class="form-text text-muted">Nombre completo o razón social del proveedor</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="rtn_proveedores"><i class="fas fa-id-card mr-1"></i>Identidad o RTN</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="rtn_proveedores" name="rtn_proveedores" placeholder="RTN" maxlength="14" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                        <div class="input-group-append" id="grupo_editar_rtn">
                                            <button type="button" class="btn btn-outline-success editar_rtn" data-toggle="tooltip" title="Editar RTN">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Número de identidad (13 dígitos) o RTN (14 dígitos)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_proveedores"><i class="fas fa-calendar-alt mr-1"></i>Fecha <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_proveedores" name="fecha_proveedores" value="<?php echo date('Y-m-d'); ?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de registro del proveedor</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="departamento_proveedores"><i class="fas fa-map-marker-alt mr-1"></i>Departamento</label>
                                    <select class="selectpicker form-control" id="departamento_proveedores" name="departamento_proveedores" data-live-search="true" title="Seleccione departamento">
                                    </select>
                                    <small class="form-text text-muted">Departamento donde se ubica el proveedor</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="municipio_proveedores"><i class="fas fa-map-marked-alt mr-1"></i>Municipio</label>
                                    <select class="selectpicker form-control" id="municipio_proveedores" name="municipio_proveedores" data-live-search="true" title="Seleccione municipio">
                                    </select>
                                    <small class="form-text text-muted">Municipio donde se ubica el proveedor</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="telefono_proveedores"><i class="fas fa-phone-alt mr-1"></i>Teléfono</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="telefono_proveedores" name="telefono_proveedores" placeholder="Número de teléfono">
                                    </div>
                                    <small class="form-text text-muted">Número de contacto principal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Contacto y Ubicación -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Contacto y Ubicación</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="dirección_proveedores"><i class="fas fa-home mr-1"></i>Dirección</label>
                                    <input type="text" class="form-control" id="dirección_proveedores" name="dirección_proveedores" placeholder="Dirección completa" maxlength="150">
                                    <small class="form-text text-muted">Dirección exacta del proveedor</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="correo_proveedores"><i class="fas fa-envelope mr-1"></i>Correo</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                                        </div>
                                        <input type="email" class="form-control" placeholder="Correo electrónico" id="correo_proveedores" name="correo_proveedores" maxlength="70">
                                        <div class="input-group-append">
                                            <span class="input-group-text">@ejemplo.com</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Correo electrónico para contacto</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado del Proveedor</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="proveedores_activo" name="proveedores_activo" value="1" checked>
                                    <label class="custom-control-label" for="proveedores_activo">Proveedor Activo</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado del proveedor en el sistema</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_proveedor" form="formProveedores">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_proveedor" form="formProveedores">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_proveedor" form="formProveedores">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PROVEEDORES-->

<!--INICIO MODAL BUSQUEDA DE PRODUCTOS EN FACTURACION-->
<div class="modal fade" id="modal_buscar_productos_facturacion">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Productos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_productos_facturacion">
                    <input type="hidden" id="row" name="row" class="form-control" />
                    <input type="hidden" id="col" name="col" class="form-control" />

                    <div class="form-group">
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Bodega
                                    </span>
                                    <select id="almacen" name="almacen" class="selectpicker" title="Bodega"
                                        data-width="100%" data-size="5" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="overflow-auto">
                            <table id="DatatableProductosBusquedaFactura"
                                class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Seleccione</th>
                                        <th class="table-image">Imagen</th>
                                        <th>Bar Code</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Medida</th>
                                        <th>Categoria</th>
                                        <th>Venta</th>
                                        <th>Almacén</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE PRODUCTOS EN FACTURACION-->

<!--INICIO MODAL BUSQUEDA DE PRODUCTOS EN COMPRAS-->
<div class="modal fade" id="modal_buscar_productos_compras">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Productos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_productos_compras">
                    <input type="hidden" id="row" name="row" class="form-control" />
                    <input type="hidden" id="col" name="col" class="form-control" />

                    <div class="col-md-12">
                        <div class="overflow-auto">
                            <table id="DatatableProductosBusquedaCompra"
                                class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Seleccione</th>
                                        <th class="table-image">Imagen</th>
                                        <th>Bar Code</th>
                                        <th>Producto</th>
                                        <th>Medida</th>
                                        <th>Categoria</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE PRODUCTOS EN COMPRAS-->

<!--INICIO MODAL BUSQUEDA DE PRODUCTOS MOVIMIENTOS-->
<div class="modal fade" id="modal_buscar_productos_movimientos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Productos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_productos_movimientos">
                    <input type="hidden" id="row" name="row" class="form-control" />
                    <input type="hidden" id="col" name="col" class="form-control" />
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableProductosBusquedaMovimientos"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Medida</th>
                                            <th>Categoria</th>
                                            <th>Precio Venta</th>
                                            <th>Almacén</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE PRODUCTOS MOVIMIENTOS-->

<!--INICIO MODAL BUSQUEDA DE COLABORADORES EN FACTURACION-->
<div class="modal fade" id="modal_buscar_colaboradores_facturacion">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Colaboradores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_colaboradores_facturacion">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableColaboradoresBusquedaFactura"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Colaborador</th>
                                            <th>Identidad</th>
                                            <th>Teléfono</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE COLABORADORES EN FACTURACION-->

<!-- MODAL COLABORADORES -->
<div class="modal fade" id="modal_registrar_colaboradores">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Gestión de Colaboradores</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <form class="FormularioAjax" id="formColaboradores" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="colaborador_id" name="colaborador_id">

                    <!-- Sección de Datos de Seguridad -->
                    <div class="card mb-3" id="datosClientes" style="display: none;">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Credenciales de Seguridad</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label for="cliente_codigo_colaborador">Código de Cliente</label>
                                    <input type="text" class="form-control" id="cliente_codigo_colaborador" name="cliente_codigo_colaborador" readonly>
                                    <small class="form-text text-muted">Identificador único para soporte técnico</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="pin_colaborador">PIN de Verificación</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="pin_colaborador" name="pin_colaborador" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="generarPin">
                                                <i class="fas fa-sync-alt"></i> Generar Nuevo PIN
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Código temporal válido por 60 segundos</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Información Básica -->
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Información Personal del Colaborador</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="nombre_colaborador">Nombre Completo<span class="priority">*</span></label>
                                <input type="text" class="form-control" id="nombre_colaborador" name="nombre_colaborador" required>
                                <small class="form-text text-muted">Nombre y apellidos del colaborador</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label for="identidad_colaborador">Número de Identidad <span class="priority">*</span></label>
                                    <input type="number" class="form-control" id="identidad_colaborador" name="identidad_colaborador" 
                                           maxlength="13" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" required>
                                    <small class="form-text text-muted">Documento de identificación oficial</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="telefono_colaborador">Teléfono de Contacto <span class="priority">*</span></label>
                                    <input type="number" class="form-control" id="telefono_colaborador" name="telefono_colaborador" 
                                           maxlength="8" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" required>
                                    <small class="form-text text-muted">Número celular o telefónico principal</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Datos Laborales -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Información Laboral</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label for="fecha_ingreso_colaborador">Fecha de Ingreso <span class="priority">*</span></label>
                                    <input type="date" class="form-control" id="fecha_ingreso_colaborador" name="fecha_ingreso_colaborador" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                    <small class="form-text text-muted">Fecha en que inició labores en la empresa</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="fecha_egreso_colaborador">Fecha de Egreso</label>
                                    <input type="date" class="form-control" id="fecha_egreso_colaborador" name="fecha_egreso_colaborador">
                                    <small class="form-text text-muted">Fecha de retiro (dejar vacío si sigue activo)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label>Puesto o Cargo <span class="priority">*</span></label>
                                    <select id="puesto_colaborador" name="puesto_colaborador" class="selectpicker form-control" 
                                            data-live-search="true" title="Seleccione el puesto" required>
                                        <option value="">Seleccione un puesto</option>
                                    </select>
                                    <small class="form-text text-muted">Posición dentro de la organización</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Empresa Asignada <span class="priority">*</span></label>
                                    <select id="colaborador_empresa_id" name="colaborador_empresa_id" class="selectpicker form-control" 
                                            data-live-search="true" title="Seleccione la empresa" required>
                                        <option value="">Seleccione una empresa</option>
                                    </select>
                                    <small class="form-text text-muted">Empresa o sucursal principal</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Estado Laboral</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="colaboradores_activo" name="colaboradores_activo" checked>
                                    <label class="custom-control-label" for="colaboradores_activo">Colaborador Activo</label>
                                </div>
                                <small class="form-text text-muted">Desactive si el colaborador ya no labora en la empresa</small>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="reg_colaborador" form="formColaboradores" style="display: none;">
                    <i class="far fa-save"></i> Guardar Colaborador
                </button>
                <button type="submit" class="btn btn-warning" id="edi_colaborador" form="formColaboradores" style="display: none;">
                    <i class="fas fa-edit"></i> Actualizar Datos
                </button>
                <button type="submit" class="btn btn-danger" id="delete_colaborador" form="formColaboradores" style="display: none;">
                    <i class="fas fa-trash"></i> Eliminar Registro
                </button>
            </div>
        </div>
    </div>
</div>

<!--INICIO MODAL BUSQUEDA DE EMPRESAS-->
<div class="modal fade" id="modal_buscar_empresa">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Empresa</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_empreasa">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaEmpresas"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Razón Social</th>
                                            <th>Empresa</th>
                                            <th>Correo</th>
                                            <th>RTN</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE EMPRESAS-->

<!--INICIO MODAL EMPRESA-->
<div class="modal fade" id="modal_registrar_empresa">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-building mr-2"></i>Registro de Empresa</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formEmpresa" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="empresa_id" name="empresa_id" class="form-control">
                    
                    <!-- Sección de Información General -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <div class="input-group mb-3">
                                        <input type="hidden" id="empresa_id" name="empresa_id" class="form-control">
                                        <input type="text" id="proceso_empresa" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="empresa_razon_social"><i class="fas fa-file-signature mr-1"></i>Razón Social <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="empresa_razon_social" id="empresa_razon_social" class="form-control" placeholder="Razón Social" maxlength="100" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="far fa-building"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre legal de la empresa</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="empresa_empresa"><i class="fas fa-signature mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="empresa_empresa" id="empresa_empresa" class="form-control" placeholder="Nombre comercial" maxlength="50" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-store"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre comercial de la empresa</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="rtn_empresa"><i class="fas fa-id-card-alt mr-1"></i>RTN <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="rtn_empresa" id="rtn_empresa" class="form-control" placeholder="RTN" maxlength="14" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Registro Tributario Nacional (14 dígitos)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sitioweb_empresa"><i class="fas fa-globe-americas mr-1"></i>Sitio WEB</label>
                                    <div class="input-group">
                                        <input type="text" name="sitioweb_empresa" id="sitioweb_empresa" class="form-control" placeholder="Sitio WEB" maxlength="150">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-link"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">URL del sitio web de la empresa</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Contacto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-address-book mr-2"></i>Información de Contacto</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefono_empresa"><i class="fas fa-phone-alt mr-1"></i>Teléfono <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="telefono_empresa" id="telefono_empresa" class="form-control" placeholder="Teléfono" maxlength="8" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Teléfono principal de contacto</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="empresa_celular"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</label>
                                    <div class="input-group">
                                        <input type="text" name="empresa_celular" id="empresa_celular" class="form-control" placeholder="Teléfono" maxlength="8">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Número de WhatsApp para contacto</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="correo_empresa"><i class="fas fa-envelope mr-1"></i>Correo</label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" placeholder="Correo" id="correo_empresa" name="correo_empresa" maxlength="70">
                                        <div class="input-group-append">
                                            <span class="input-group-text">@correo.com</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Correo electrónico principal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Imágenes -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-images mr-2"></i>Imágenes y Logos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="logotipo"><i class="fas fa-image mr-1"></i>Logotipo</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="logotipo" id="logotipo" accept="image/*">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-file-image"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Logo principal de la empresa</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="firma_documento"><i class="fas fa-signature mr-1"></i>Firma Documento</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="firma_documento" id="firma_documento" accept="image/*">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-file-signature"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Firma para documentos oficiales</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Información Adicional -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Adicional</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="facebook_empresa"><i class="fab fa-facebook mr-1"></i>Facebook</label>
                                    <div class="input-group">
                                        <textarea id="facebook_empresa" name="facebook_empresa" placeholder="Facebook" class="form-control" maxlength="100" rows="2"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">URL o información de Facebook</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="horario_empresa"><i class="fas fa-clock mr-1"></i>Horario</label>
                                    <div class="input-group">
                                        <textarea id="horario_empresa" name="horario_empresa" placeholder="Horario de atención" class="form-control" maxlength="100" rows="2"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Horario de atención al público</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="empresa_eslogan"><i class="fas fa-quote-left mr-1"></i>Eslogan</label>
                                    <div class="input-group">
                                        <textarea id="empresa_eslogan" name="empresa_eslogan" placeholder="Eslogan o lema" class="form-control" maxlength="100" rows="2"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Eslogan o lema de la empresa</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="empresa_otra_informacion"><i class="fas fa-info-circle mr-1"></i>Otra Información</label>
                                    <div class="input-group">
                                        <textarea id="empresa_otra_informacion" name="empresa_otra_informacion" placeholder="Información adicional" class="form-control" maxlength="100" rows="4"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-info"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Cualquier información adicional relevante</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="direccion_empresa"><i class="fas fa-map-marked-alt mr-1"></i>Dirección</label>
                                    <div class="input-group">
                                        <textarea id="direccion_empresa" name="direccion_empresa" placeholder="Dirección física" class="form-control" maxlength="100" rows="4"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Dirección física completa de la empresa</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="empresa_activo" name="empresa_activo" value="1" checked>
                                    <label class="custom-control-label" for="empresa_activo">Empresa Activa</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado de la empresa en el sistema</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_empresa" form="formEmpresa">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_empresa" form="formEmpresa">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_empresa" form="formEmpresa">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EMPRESA-->

<!--INICIO MODAL CAJAS-->
<div class="modal fade" id="modal_registrar_cajas">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-cash-register mr-2"></i>Registro de Cajas</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formCajas" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <!-- Sección de Información de la Caja -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información de la Caja</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <div class="input-group mb-3">
                                        <input type="hidden" id="cajas_id" name="cajas_id" class="form-control">
                                        <input type="text" id="proceso_cajas" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="nombre_caja"><i class="fas fa-cash-register mr-1"></i>Caja <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Nombre de la caja" id="nombre_caja" name="nombre_caja" readonly required>
                                        <div class="input-group-append" id="obtener_caja">
                                            <button type="button" class="btn btn-outline-success" data-toggle="tooltip" title="Actualizar caja">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre identificador de la caja</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="descripcion_caja"><i class="fas fa-align-left mr-1"></i>Descripción <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="descripcion_caja" id="descripcion_caja" class="form-control" placeholder="Descripción de la caja" maxlength="50" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Descripción detallada de la caja</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Estado de la Caja</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="caja_estado" name="caja_estado" value="1" checked>
                                    <label class="custom-control-label" for="caja_estado">Caja Activa</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado de la caja en el sistema</small>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_caja" form="formCajas">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_caja" form="formCajas">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_caja" form="formCajas">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CAJAS-->

<!--INICIO MODAL APERTURA CAJA-->
<div class="modal fade" id="modal_apertura_caja">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-cash-register mr-2"></i>Apertura de Caja</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formAperturaCaja" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <!-- Sección de Información de Apertura -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Datos de Apertura</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <div class="input-group mb-3">
                                        <input type="hidden" id="apertura_id" name="apertura_id" class="form-control">
                                        <input type="hidden" id="colaboradores_id_apertura" name="colaboradores_id_apertura" class="form-control">
                                        <input type="text" id="proceso_aperturaCaja" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="usuario_apertura"><i class="fas fa-user mr-1"></i>Usuario</label>
                                    <input type="text" class="form-control" placeholder="Usuario responsable" id="usuario_apertura" name="usuario_apertura" readonly required>
                                    <small class="form-text text-muted">Usuario que realizará la apertura</small>
                                </div>
                            </div>
                            
                            <div class="form-row" id="monto_apertura_grupo">
                                <div class="col-md-12 mb-3">
                                    <label for="monto_apertura"><i class="fas fa-money-bill-wave mr-1"></i>Monto Apertura</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" class="form-control" placeholder="0.00" id="monto_apertura" name="monto_apertura" step="0.01" value="0.00">
                                    </div>
                                    <small class="form-text text-muted">Monto inicial en la caja</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="fecha_apertura"><i class="fas fa-calendar-alt mr-1"></i>Fecha</label>
                                    <input type="date" name="fecha_apertura" id="fecha_apertura" class="form-control" value="<?php echo date('Y-m-d'); ?>" required readonly>
                                    <small class="form-text text-muted">Fecha de apertura de caja</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="open_caja" form="formAperturaCaja">
                    <i class="fas fa-lock-open mr-1"></i> Aperturar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="close_caja" form="formAperturaCaja">
                    <i class="fas fa-lock mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL APERTURA CAJA-->

<!--INICIO MODAL PRODUCTOS-->
<div class="modal fade" id="modal_registrar_productos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-boxes mr-2"></i>Registro de Productos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProductos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="productos_id" name="productos_id" class="form-control">
                    <input type="hidden" id="productos_id" name="productos_id" class="form-control">

                    <!-- Sección de Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
                        <div class="card-body">                           
                            <div class="form-row">
                                <div class="col-md-1 mb-3">
                                    <input type="file" name="file" class="file" accept=".png, .jpeg, .jpg, .jfif">
                                    <img type="button" src="<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png" id="preview" class="browse img-thumbnail" data-toggle="tooltip" data-placement="top" title="Cargar Imagen">
                                    <input type="hidden" class="form-control" disabled placeholder="Cargar Imágen" id="file_product" name="file_product">
                                    <small class="form-text text-muted">Imagen del producto</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="bar_code_product"><i class="fas fa-barcode mr-1"></i>Código de Barra</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="bar_code_product" name="bar_code_product" placeholder="Código de Barra" data-toggle="tooltip" data-placement="top" title="Si este campo está vacío o tiene el número cero el sistema genera un código de barra automáticamente siendo un valor único">
                                        <div class="input-group-append" id="grupo_editar_bacode">
                                            <button type="button" class="btn btn-outline-success editar_barcode" data-toggle="tooltip" title="Editar Código de Barra">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Dejar en blanco para generación automática</small>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="producto"><i class="fas fa-tag mr-1"></i>Producto <span class="priority">*</span></label>
                                    <input type="text" class="form-control" id="producto" name="producto" maxlength="50" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="Nombre del producto" required>
                                    <small class="form-text text-muted">Nombre completo del producto (máx. 50 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Clasificación -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-tags mr-2"></i>Clasificación</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3" style="display: none;">
                                    <label for="producto_empresa_id"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="producto_empresa_id" name="producto_empresa_id" class="selectpicker form-control" data-live-search="true" title="Seleccione una empresa">
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="producto_superior" data-toggle="tooltip" data-placement="top" title="El campo 'Producto Superior' se emplea cuando estás creando un producto que tiene una conexión con otro. Imagina que estás diseñando un 'Kit de Jardinería', aquí puedes elegir 'Semillas' como el producto superior, indicando que el kit depende de las semillas para su existencia.">
                                        <i class="fas fa-sitemap mr-1"></i>Superior
                                    </label>
                                    <select class="selectpicker form-control" id="producto_superior" name="producto_superior" data-live-search="true" title="Seleccione producto superior">
                                    </select>
                                    <small class="form-text text-muted">Producto padre o relacionado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="tipo_producto"><i class="fas fa-cubes mr-1"></i>Tipo Producto <span class="priority">*</span></label>
                                    <select id="tipo_producto" name="tipo_producto" required class="selectpicker form-control" data-live-search="true" title="Seleccione tipo de producto">
                                    </select>
                                    <small class="form-text text-muted">Tipo o clasificación del producto</small>
                                </div>
                                <div class="col-md-3 mb-3 confCategoria" style="display:none;">
                                    <label for="producto_categoria"><i class="fas fa-folder mr-1"></i>Categoria</label>
                                    <select class="selectpicker form-control" id="producto_categoria" name="producto_categoria" required data-live-search="true" title="Seleccione categoría">
                                    </select>
                                    <small class="form-text text-muted">Categoría del producto</small>
                                </div>
                                <div class="col-md-3 mb-3" style="display: none;">
                                    <label for="almacen"><i class="fas fa-warehouse mr-1"></i>Almacén</label>
                                    <select id="almacen" name="almacen" class="selectpicker form-control" data-live-search="true" title="Seleccione almacén">
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="medida"><i class="fas fa-ruler-combined mr-1"></i>Medida <span class="priority">*</span></label>
                                    <select id="medida" name="medida" required class="selectpicker form-control" data-live-search="true" title="Seleccione medida">
                                    </select>
                                    <small class="form-text text-muted">Unidad de medida del producto</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Precios -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i>Precios y Stock</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="precio_compra"><i class="fas fa-shopping-cart mr-1"></i>Precio Compra</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" class="form-control" id="precio_compra" name="precio_compra" placeholder="0.00" step="0.00001">
                                    </div>
                                    <small class="form-text text-muted">Precio de compra del producto</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="precio_venta"><i class="fas fa-tag mr-1"></i>Precio Venta <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" class="form-control" id="precio_venta" name="precio_venta" placeholder="0.00" step="0.00001" required>
                                    </div>
                                    <small class="form-text text-muted">Precio de venta al público</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="porcentaje_venta"><i class="fas fa-percentage mr-1"></i>Ganancia</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <input type="number" class="form-control" id="porcentaje_venta" name="porcentaje_venta" placeholder="0.00" step="0.00001" readonly>
                                    </div>
                                    <small class="form-text text-muted">Margen de ganancia calculado</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cantidad_mayoreo"><i class="fas fa-boxes mr-1"></i>Cantidad Mayoreo</label>
                                    <input type="number" class="form-control" id="cantidad_mayoreo" name="cantidad_mayoreo" placeholder="0" step="0.00001" value="">
                                    <small class="form-text text-muted">Cantidad mínima para venta al por mayor</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="precio_mayoreo"><i class="fas fa-tags mr-1"></i>Precio Mayoreo</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">L</span>
                                        </div>
                                        <input type="number" class="form-control" id="precio_mayoreo" name="precio_mayoreo" placeholder="0.00" step="0.00001">
                                    </div>
                                    <small class="form-text text-muted">Precio para ventas al por mayor</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cantidad_minima"><i class="fas fa-arrow-down mr-1"></i>Cantidad Mínima</label>
                                    <input type="number" id="cantidad_minima" name="cantidad_minima" placeholder="0" class="form-control" step="0.00001">
                                    <small class="form-text text-muted">Stock mínimo permitido</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cantidad_maxima"><i class="fas fa-arrow-up mr-1"></i>Cantidad Máxima</label>
                                    <input type="number" id="cantidad_maxima" name="cantidad_maxima" placeholder="0" class="form-control" step="0.00001">
                                    <small class="form-text text-muted">Stock máximo permitido</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Configuración Adicional -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs mr-2"></i>Configuración Adicional</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="descripcion"><i class="fas fa-align-left mr-1"></i>Descripción</label>
                                    <textarea id="descripcion" name="descripcion" placeholder="Descripción del producto" class="form-control" maxlength="100" rows="2"></textarea>
                                    <p id="charNum_descripcion" class="text-muted">100 Caracteres restantes</p>
                                    <small class="form-text text-muted">Descripción detallada del producto</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="producto_activo" name="producto_activo" value="1" checked>
                                        <label class="custom-control-label" for="producto_activo"><i class="fas fa-power-off mr-1"></i>Estado del Producto</label>
                                        <small class="form-text text-muted">Activar/Desactivar producto en el sistema</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="producto_isv_factura" name="producto_isv_factura" value="1">
                                        <label class="custom-control-label" for="producto_isv_factura"><i class="fas fa-percent mr-1"></i>Calcular ISV en Factura</label>
                                        <small class="form-text text-muted">Aplicar impuesto en ventas</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3" style="display: none;">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="producto_isv_compra" name="producto_isv_compra" value="1">
                                        <label class="custom-control-label" for="producto_isv_compra"><i class="fas fa-percent mr-1"></i>Calcular ISV en Compra</label>
                                        <small class="form-text text-muted">Aplicar impuesto en compras</small>
                                    </div>
                                </div>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_producto" form="formProductos">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_producto" form="formProductos">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_producto" form="formProductos">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PRODUCTOS-->

<!--INICIO MODAL TRANSFERENCIA DE PRODUCTO/BODEGA-->
<div class="modal fade" id="modal_transferencia_producto">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-exchange-alt mr-2"></i>Transferir Producto</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formTransferencia" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <!-- Sección de Información del Producto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-box mr-2"></i>Información del Producto</h5>
                        </div>
                        <div class="card-body text-center">
                            <input type="hidden" id="productos_id" name="productos_id">
                            <input type="hidden" id="id_bodega_actual" name="id_bodega_actual">
                            <input type="hidden" id="lote_id_productos" name="lote_id_productos">
                            <input type="hidden" id="empresa_id_productos" name="empresa_id_productos">
                            <input type="hidden" required readonly id="pro_trasferencia" name="pro_trasferencia">
                            
                            <h5 class="text-primary" id="nameProduct"></h5>
                            <small class="form-text text-muted">Producto seleccionado para transferencia</small>
                        </div>
                    </div>
                    
                    <!-- Sección de Datos de Transferencia -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-truck-loading mr-2"></i>Datos de Transferencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_bodega"><i class="fas fa-warehouse mr-1"></i>Bodega Destino <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <select id="id_bodega" name="id_bodega" class="selectpicker form-control" data-live-search="true" title="Seleccione bodega destino" required>
                                        </select>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Seleccione la bodega a la que transferirá el producto</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cantidad_movimiento"><i class="fas fa-sort-amount-up mr-1"></i>Cantidad <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="number" id="cantidad_movimiento" name="cantidad_movimiento" placeholder="Cantidad a transferir" class="form-control" step="0.01" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Ingrese la cantidad del producto a transferir</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Comentarios -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-comment mr-2"></i>Comentarios</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="movimiento_comentario"><i class="fas fa-align-left mr-1"></i>Observaciones</label>
                                    <textarea id="movimiento_comentario" name="movimiento_comentario" class="form-control" rows="3" maxlength="254" placeholder="Motivo de la transferencia u observaciones importantes"></textarea>
                                    <div class="char-count"><small class="text-muted">254 caracteres restantes</small></div>
                                    <small class="form-text text-muted">Describa el motivo de la transferencia o cualquier observación relevante</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" id="putEditarBodega" form="formTransferencia">
                    <i class="fas fa-exchange-alt mr-1"></i> Transferir
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL TRANSFERENCIA DE PRODUCTO/BODEGA-->

<!--INICIO MODAL CAMBIAR FECHA DE CADUCIDAD-->
<div class="modal fade" id="modalCambiarFechaProducto">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-calendar-alt mr-2"></i>Cambiar Fecha de Caducidad</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formTransferenciaCambiarFecha" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <!-- Sección de Información del Producto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-box mr-2"></i>Información del Producto</h5>
                        </div>
                        <div class="card-body text-center">
                            <input type="hidden" id="productos_id" name="productos_id">
                            <input type="hidden" value="" id="id_bodega_actual" name="id_bodega_actual">
                            <input type="hidden" id="cantidad_productos" name="cantidad_productos">
                            <input type="hidden" id="empresa_id_productos" name="empresa_id_productos">
                            <input type="hidden" id="lote_id_productos" name="lote_id_productos">
                            <input type="hidden" required readonly id="pro_cambiar_fecha" name="pro_cambiar_fecha">
                            
                            <h5 class="text-primary" id="nameProduct"></h5>
                            <small class="form-text text-muted">Producto seleccionado para modificar fecha de caducidad</small>
                        </div>
                    </div>
                    
                    <!-- Sección de Nueva Fecha -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-day mr-2"></i>Nueva Fecha de Caducidad</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="fecha_caducidad"><i class="fas fa-calendar-check mr-1"></i>Fecha <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="date" id="fecha_caducidad" name="fecha_caducidad" class="form-control" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Seleccione la nueva fecha de caducidad para el producto</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" id="EditarFechaVencimiento" form="formTransferenciaCambiarFecha">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CAMBIAR FECHA DE CADUCIDAD-->

<!-- modal de abonos cxc -->
<div class="modal fade" id="ver_abono_cxc">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Abonos Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_ver_abono_cxc">
                    <div class="form-group">
                        <input type="hidden" name="abono_facturas_id" id="abono_facturas_id" class="form-control">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="table-modal-abonos" class="table table-striped table-header-gradient table-condensed table-hover"
                                    style="width:100%">
                                    <h5 id="ver_abono_cxcTitle"></h5>
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo Pago</th>
                                            <th>Descripcion</th>
                                            <th>Abono</th>
                                            <th>Usuario que recibe</th>
                                        </tr>
                                    </thead>
                                    <tfoot class="bg-info text-white font-weight-bold">
                                        <tr>
                                            <td colspan='2' class="text-left">Total</td>
                                            <td colspan="1"></td>
                                            <td colspan='1' id='total-footer-modal-cxc' class="text-right"></td>
                                            <td colspan="1"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!-- FIN modal de abonos cxc -->

<!-- modal de abonos cxp -->
<div class="modal fade" id="ver_abono_cxp">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Abono Proveedores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_ver_abono_cxp">
                    <div class="form-group">
                        <input type="hidden" name="abono_compras_id" id="abono_compras_id" class="form-control">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="table-modal-abonosCXP"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <h5 id="ver_abono_cxPTitle"></h5>
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo Pago</th>
                                            <th>Descripcion</th>
                                            <th>Abono</th>
                                            <th>Usuario que Entrega</th>
                                        </tr>
                                    </thead>
                                    <tfoot class="bg-info text-white font-weight-bold">
                                        <tr>
                                            <td colspan='2' class="text-left">Total</td>
                                            <td colspan="1"></td>
                                            <td colspan='1' id='total-footer-modal-cxp' class="text-right"></td>
                                            <td colspan="1"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!-- FIN modal de abonos cxp -->

<!--INICIO MODAL EDITAR RTN CLIENTES-->
<div class="modal fade" id="modalEditarRTNClientes">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-id-card mr-2"></i>Editar RTN de Cliente</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarRTNClientes" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección de Información del Cliente -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Datos del Cliente</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <input type="hidden" required="required" readonly id="clientes_id" name="clientes_id" />
                                    <div class="input-group mb-3">
                                        <input type="text" required readonly id="pro_clientes" name="pro_clientes" class="form-control" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="cliente"><i class="fas fa-user mr-1"></i>Cliente <span class="priority">*</span></label>
                                    <input type="text" required id="cliente" name="cliente" placeholder="Cliente" readonly class="form-control" />
                                    <small class="form-text text-muted">Cliente seleccionado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de RTN -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-id-card mr-2"></i>Registro Tributario (RTN)</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="rtn_cliente"><i class="fas fa-id-card mr-1"></i>RTN <span class="priority">*</span></label>
                                    <input type="number" required id="rtn_cliente" name="rtn_cliente" placeholder="RTN" class="form-control" maxlength="14" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                    <small class="form-text text-muted">Ingrese el RTN (14 dígitos)</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" id="editar_rtn_clientes" form="formEditarRTNClientes">
                    <i class="far fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EDITAR RTN CLIENTES-->

<!--INICIO MODAL EDITAR RTN PROVEEDORES-->
<div class="modal fade" id="modalEditarRTNProveedores">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-truck mr-2"></i>Editar RTN de Proveedor</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarRTNProveedores" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección de Información del Proveedor -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-truck mr-2"></i>Datos del Proveedor</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <input type="hidden" required="required" readonly id="proveedores_id" name="proveedores_id" />
                                    <div class="input-group mb-3">
                                        <input type="text" required readonly id="pro_proveedores" name="pro_proveedores" class="form-control" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="proveedor"><i class="fas fa-truck mr-1"></i>Proveedor <span class="priority">*</span></label>
                                    <input type="text" required id="proveedor" name="proveedor" readonly placeholder="Proveedor" class="form-control" />
                                    <small class="form-text text-muted">Proveedor seleccionado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de RTN -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-id-card mr-2"></i>Registro Tributario (RTN)</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="rtn_proveedor"><i class="fas fa-id-card mr-1"></i>RTN <span class="priority">*</span></label>
                                    <input type="number" required id="rtn_proveedor" name="rtn_proveedor" placeholder="RTN" class="form-control" maxlength="14" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                    <small class="form-text text-muted">Ingrese el RTN (14 dígitos)</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" id="editar_rtn_proveedores" form="formEditarRTNProveedores">
                    <i class="far fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EDITAR RTN PROVEEDORES-->

<!--INICIO MODAL EDITAR BARCODE-->
<div class="modal fade" id="modalEditarBarcode">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-barcode mr-2"></i>Editar Código de Barras</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarBarcode" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <!-- Sección de Información del Producto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información del Producto</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <input type="hidden" required="required" readonly id="productos_id" name="productos_id" />
                                    <div class="input-group mb-3">
                                        <input type="text" required readonly id="pro_barcode" name="pro_barcode" class="form-control" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-plus-square"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="producto"><i class="fas fa-tag mr-1"></i>Producto <span class="priority">*</span></label>
                                    <input type="text" required id="producto" name="producto" readonly placeholder="Producto" class="form-control" />
                                    <small class="form-text text-muted">Producto seleccionado</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Código de Barras -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-barcode mr-2"></i>Código de Barras</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="barcode"><i class="fas fa-barcode mr-1"></i>Código de Barra <span class="priority">*</span></label>
                                    <input type="text" required id="barcode" name="barcode" placeholder="Código de Barra" class="form-control" maxlength="20" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                                    <small class="form-text text-muted">Ingrese el nuevo código de barras (máx. 20 caracteres)</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" id="editar_barcode" form="formEditarBarcode">
                    <i class="far fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EDITAR BARCODE-->

<!--INICIO CONSULTAR FACTURADORES-->
<div class="modal fade" id="modal_consultar_facturadores">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Facturadores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_consultar_facturadores">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaConsultaFacturadores"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Nombre</th>
                                            <th>Identidad</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN CONSULTAR FACTURADORES-->

<!--INICIO MODAL ASISTENCIA-->
<div class="modal fade" id="modal_registrar_asistencia">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-user-clock mr-2"></i>Registro de Asistencia</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formAsistencia" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">

                    <input type="hidden" id="asistencia_id" name="asistencia_id">
                    <input type="hidden" id="marcarAsistencia_id" name="marcarAsistencia_id">
                    
                    <!-- Sección de Datos del Empleado -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Datos del Empleado</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="asistencia_empleado"><i class="fas fa-users mr-1"></i>Empleado <span class="priority">*</span></label>
                                    <select id="asistencia_empleado" name="asistencia_empleado" class="selectpicker form-control" data-live-search="true" title="Seleccione un empleado" required>
                                    </select>
                                    <small class="form-text text-muted">Seleccione el empleado que registrará asistencia</small>
                                </div>
                                <div class="col-md-6 mb-3" id="fechaAsistencia">
                                    <label for="fecha"><i class="fas fa-calendar-day mr-1"></i>Fecha <span class="priority">*</span></label>
                                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                                    <small class="form-text text-muted">Fecha del registro de asistencia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Horarios -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clock mr-2"></i>Control de Horarios</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3" id="grupoHora">
                                    <label for="hora" id="registro_hora"><i class="fas fa-sign-in-alt mr-1"></i>Hora Entrada <span class="priority">*</span></label>
                                    <input type="time" class="form-control" id="hora" name="hora" step="any" required>
                                    <small class="form-text text-muted">Hora de entrada del empleado</small>
                                </div>
                                <div class="col-md-4 mb-3" id="grupoHorai">
                                    <label for="horagi" id="registro_horai"><i class="fas fa-sign-in-alt mr-1"></i>Hora Entrada Registrada</label>
                                    <input type="time" class="form-control" id="horagi" name="horagi" value="<?php echo date('H:i'); ?>" step="any" readonly>
                                    <small class="form-text text-muted">Hora de entrada previamente registrada</small>
                                </div>
                                <div class="col-md-4 mb-3" id="grupoHoraf">
                                    <label for="horagf"><i class="fas fa-sign-out-alt mr-1"></i>Hora Salida Registrada</label>
                                    <input type="time" class="form-control" id="horagf" name="horagf" step="any" readonly>
                                    <small class="form-text text-muted">Hora de salida previamente registrada</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Comentarios -->
                    <div class="card border-primary" id="grupoHoraComentario">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-comment-dots mr-2"></i>Observaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="comentario"><i class="fas fa-align-left mr-1"></i>Comentarios</label>
                                    <input type="text" class="form-control" id="comentario" name="comentario" placeholder="Ingrese cualquier observación relevante">
                                    <small class="form-text text-muted">Registre cualquier observación sobre la asistencia</small>
                                </div>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_asistencia" form="formAsistencia">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_asistencia" form="formAsistencia">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL ASISTENCIA-->

<!--INICIO MODAL GENERAR SISTEMA CLIENTE-->
<div class="modal fade" id="modal_generar_sistema">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-cogs mr-2"></i>Generar Sistema</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formGenerarSistema" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="clientes_id" name="clientes_id" class="form-control">
                    <input type="hidden" id="clientes_telefono" name="clientes_telefono" class="form-control">
                    <input type="hidden" id="clientes_correo" name="clientes_correo" class="form-control">
                    <input type="hidden" id="clientes_ubicacion" name="clientes_ubicacion" class="form-control">

                    <!-- Sección Información Cliente -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Información del Cliente</h5>
                        </div>
                        <div class="card-body">                           
                            <div class="form-row">
                                <div class="col-md-5 mb-3">
                                    <label for="cliente"><i class="fas fa-user mr-1"></i>Cliente <span class="priority">*</span></label>
                                    <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Cliente" required>
                                    <small class="form-text text-muted">Nombre completo del cliente</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="rtn"><i class="fas fa-id-card mr-1"></i>RTN <span class="priority">*</span></label>
                                    <input type="text" class="form-control" id="rtn" name="rtn" placeholder="RTN" required>
                                    <small class="form-text text-muted">Número de identificación tributaria</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="empresa"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <input type="text" class="form-control" id="empresa" name="empresa" placeholder="Empresa" required maxlength="30">
                                    <small class="form-text text-muted">Nombre de la empresa</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="eslogan"><i class="fas fa-quote-left mr-1"></i>Eslogan</label>
                                    <input type="text" class="form-control" id="eslogan" name="eslogan" placeholder="Eslogan" maxlength="50">
                                    <small class="form-text text-muted">Frase representativa de la empresa</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="otra_informacion"><i class="fas fa-info-circle mr-1"></i>Otra Información</label>
                                    <input type="text" class="form-control" id="otra_informacion" name="otra_informacion" placeholder="Otra Información" maxlength="50">
                                    <small class="form-text text-muted">Información adicional relevante</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="whatsApp"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</label>
                                    <input type="number" class="form-control" id="whatsApp" name="whatsApp" placeholder="WhatsApp" maxlength="8">
                                    <small class="form-text text-muted">Número de contacto WhatsApp</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección Configuración Sistema -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-server mr-2"></i>Configuración del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label for="sistema"><i class="fas fa-cube mr-1"></i>Sistema</label>
                                    <select class="selectpicker form-control" id="sistema" name="sistema" data-live-search="true" title="Seleccione Sistema">
                                    </select>
                                    <small class="form-text text-muted">Tipo de sistema a generar</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="plan"><i class="fas fa-layer-group mr-1"></i>Plan</label>
                                    <select class="selectpicker form-control" id="plan" name="plan" data-live-search="true" title="Seleccione Plan">
                                    </select>
                                    <small class="form-text text-muted">Plan de servicio</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="validar"><i class="fas fa-check-circle mr-1"></i>Validar Facturación</label>
                                    <select class="selectpicker form-control" id="validar" name="validar" data-live-search="true" title="Seleccione Validación">
                                    </select>
                                    <small class="form-text text-muted">Configuración de validación</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección Tabla Sistemas -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Sistemas Registrados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="DatatableGenerarSistema" class="table table-striped table-hover" style="width:100%">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th><i class="fas fa-user"></i> Cliente</th>
                                            <th><i class="fas fa-database"></i> DB</th>
                                            <th><i class="fas fa-cube"></i> Sistema</th>
                                            <th><i class="fas fa-layer-group"></i> Plan</th>
                                            <th><i class="fas fa-check-circle"></i> Validar Facturación</th>
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
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_generarSitema" form="formGenerarSistema">
                    <i class="fas fa-cogs mr-1"></i> Generar Sistema
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL GENERAR SISTEMA CLIENTE-->


<!-- Modal de carga -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Generando Sistema...</span>
                    </div>
                    <p id="loadingMessage">Cargando...</p>
                </div>
            </div>
        </div>
    </div>
</div>