<div class="modal fade" id="modalLogin">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-sign-in-alt"></i> Autorización</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form id="formLogin" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="text" id="autorizacion_user" name="autorizacion_user" class="form-control"
                                placeholder="login">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="text" id="autorizacion_pass" name="autorizacion_pass" class="form-control"
                                placeholder="password">
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <input type="submit" class="btn btn-primary ml-2" value="Log In">
            </div>
        </div>
    </div>
</div>

<!--INICIO MODAL BUSQUEDA DE CUENTAS CONTABLES-->
<div class="modal fade" id="modal_buscar_cuentas_contables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Cuentas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cuentas_contables">
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaCuentasContables"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Seleccione</th>
                                            <th>Código</th>
                                            <th>Nombre</th>
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
<!--FIN MODAL BUSQUEDA DE CUENTAS CONTABLES-->

<!--INICIO MODAL PARA EL INGRESO DE CUENTAS CONTABLES-->
<div class="modal fade" id="modalCuentascontables">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Cuentas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formCuentasContables" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="cuentas_id" name="cuentas_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_cuentas" name="pro_cuentas"
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
                        <div class="col-md-4 mb-3" style="display: none">
                            <label for="cuenta_codigo">Código</label>
                            <input type="text" id="cuenta_codigo" name="cuenta_codigo" placeholder="Código"
                                class="form-control" maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">Cuenta <span class="priority">*</span></label>
                            <input type="text" required id="cuenta_nombre" name="cuenta_nombre" placeholder="Cuenta"
                                class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="form-group" id="estado_cuentas_contables">
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="cuentas_activo" name="cuentas_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_cuentas_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_cuentas"
                    form="formCuentasContables">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_cuentas"
                    form="formCuentasContables">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_cuentas"
                    form="formCuentasContables">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CUENTAS CONTABLES-->

<!--INICIO MODAL USUARIOS-->
<div class="modal fade" id="modal_registrar_usuarios">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Registro de Usuarios</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formUsers" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">

                    <input type="hidden" id="usuarios_id" name="usuarios_id" class="form-control">
                    <input type="hidden" id="server_customers_id" name="server_customers_id" class="form-control">
                    <input type="hidden" id="usuarios_colaborador_id" name="usuarios_colaborador_id" class="form-control" required>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="colaborador_id_usuario">Colaboradores <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="colaborador_id_usuario" name="colaborador_id_usuario" data-width="100%"
                                    class="selectpicker" data-live-search="true" data-size="7" title="Colaboradores">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="correo_usuario">Correo <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Correo" id="correo_usuario"
                                    name="correo_usuario" aria-label="Correo" aria-describedby="basic-addon2" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-envelope-square"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="empresa_usuario">Empresa <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="empresa_usuario" name="empresa_usuario" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Empresa">
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="tipo_user" data-toggle="tooltip" data-placement="top"
                                title="'Permisos' definen lo que puedes hacer: guardar, crear, modificar, eliminar, etc. Son las acciones que tienes permitidas en el sistema.">Perrmisos
                                <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="tipo_user" name="tipo_user" class="selectpicker" data-live-search="true"
                                    data-width="100%" title="Tipo Usuario">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="privilegio_id" data-toggle="tooltip" data-placement="top"
                                title="'Privilegio' determina qué áreas y menús puedes acceder. Es tu permiso para entrar a distintas partes.">Privilegio
                                <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="privilegio_id" name="privilegio_id" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Tipo Usuario">
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="estado_usuarios">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="usuarios_activo" name="usuarios_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_usuarios_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
                <br />
                <br />
                <br />
                <br />
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_usuario"
                    form="formUsers">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_usuario"
                    form="formUsers">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_usuario"
                    form="formUsers">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL USUARIOS-->

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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Modificar Contraseña</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="form-cambiarcontra" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="text" required="required" readonly id="id-registro" name="id-registro"
                                    readonly="readonly" style="display: none;" class="form-control" />
                                <input type="password" name="contranaterior" class="form-control" id="contranaterior"
                                    placeholder="Contraseña Anterior" required="required">
                                <div class="input-group-append">
                                    <span class="btn btn-outline-success" id="show_password1" style="cursor:pointer;"><i
                                            id="icon1" class="fa fa-eye-slash icon fa-la"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="password" name="nuevacontra" class="form-control" id="nuevacontra"
                                    placeholder="Nueva Contraseña" required="required">
                                <div class="input-group-append">
                                    <span class="btn btn-outline-success" id="show_password2" style="cursor:pointer;"><i
                                            id="icon1" class="fa fa-eye-slash icon fa-la"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="password" name="repcontra" class="form-control" id="repcontra"
                                    placeholder="Repetir Contraseña" required="required">
                                <div class="input-group-append">
                                    <span class="btn btn-outline-success" id="show_password3" style="cursor:pointer;"><i
                                            id="icon1" class="fa fa-eye-slash icon fa-la"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div id="mensaje_cambiar_contra"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <ul title="La contraseña debe cumplir con todas estas características" id="list">
                                <li id="mayus"> 1 Mayúscula</li>
                                <li id="special">1 Caracter Especial (Símbolo)</li>
                                <li id="numbers">Números</li>
                                <li id="lower">Minúsculas</li>
                                <li id="len">Mínimo 8 Caracteres</li>
                            </ul>
                        </div>
                    </div>
                    <input type="hidden" name="id" class="form-control" id="id"
                        value="<?php echo $_SESSION['colaborador_id_sd']; ?>">
                    <div class="modal-footer">
                        <button class="cambiar btn btn-success ml-2" type="submit" style="display: none;"
                            id="Modalcambiarcontra_Edit">
                            <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Editar
                        </button>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
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
            <div class="modal-header">
                <h4 class="modal-title">Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formClientes" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <input type="hidden" id="clientes_id" name="clientes_id" class="form-control">
                    
                    <div class="form-row">
                        <div class="col-md-8 mb-3">
                            <label for="nombre_clientes">Cliente <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="nombre_clientes" name="nombre_clientes"
                                placeholder="Nombre" maxlength="100" required data-toggle="tooltip" data-placement="top"
                                title="Razón Social (Como aparece en el RTN)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="identidad_clientes">Identidad o RTN</label>
                            <div class="input-group mb-3">
                                <input type="number" class="form-control" id="identidad_clientes"
                                    name="identidad_clientes" placeholder="Identidad o RTN" maxlength="14"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                <div class="input-group-append" id="grupo_editar_rtn">
                                    <span data-toggle="tooltip" data-placement="top" title="Editar RTN"><a
                                            data-toggle="modal" href="#"
                                            class="btn btn-outline-success form-control editar_rtn">
                                            <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i>
                                        </a></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="fecha_clientes">Fecha <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="date" required id="fecha_clientes" name="fecha_clientes"
                                    value="<?php echo date('Y-m-d'); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="departamento_cliente">Departamento</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="departamento_cliente" name="departamento_cliente"
                                    data-width="100%" data-width="100%" data-size="7" data-live-search="true"
                                    title="Departamentos">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="municipio_cliente">Municipio </label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="municipio_cliente" name="municipio_cliente"
                                    data-width="100%" data-size="7" data-live-search="true" title="Municipio">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="telefono_clientes">Teléfono</label>
                            <input type="number" class="form-control" id="telefono_clientes" name="telefono_clientes"
                                placeholder="Teléfono" maxlength="8"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="dirección_clientes">Dirección</label>
                            <input type="text" class="form-control" id="dirección_clientes" name="dirección_clientes"
                                placeholder="Dirección" maxlength="150">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="correo_clientes">Correo</label>
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Correo" id="correo_clientes"
                                    name="correo_clientes" aria-label="Correo" aria-describedby="basic-addon2"
                                    maxlength="70">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>@algo.com
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_clientes">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="clientes_activo" name="clientes_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_clientes_activo"></span>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_cliente"
                    form="formClientes">
                    <div class="guardar sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_cliente"
                    form="formClientes">
                    <div class="editar sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_cliente"
                    form="formClientes">
                    <div class="eliminar sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
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
            <div class="modal-header">
                <h4 class="modal-title">Proveedores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProveedores" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="proveedores_id" name="proveedores_id" class="form-control">
                                <input type="text" id="proceso_proveedores" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-8 mb-3">
                            <label for="nombre_proveedores">Proveedor <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="nombre_proveedores" name="nombre_proveedores"
                                placeholder="Proveedor" required maxlength="150" placeholder="RTN"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="rtn_proveedores">Identidad o RTN</label>
                            <div class="input-group mb-3">
                                <input type="number" class="form-control" id="rtn_proveedores" name="rtn_proveedores"
                                    maxlength="14" placeholder="RTN"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                <div class="input-group-append" id="grupo_editar_rtn">
                                    <span data-toggle="tooltip" data-placement="top" title="Editar RTN"><a
                                            data-toggle="modal" href="#"
                                            class="btn btn-outline-success form-control editar_rtn">
                                            <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i>
                                        </a></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="fecha_proveedores">Fecha <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="date" required id="fecha_proveedores" name="fecha_proveedores"
                                    value="<?php echo date('Y-m-d'); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="departamento_proveedores">Departamento </label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="departamento_proveedores"
                                    name="departamento_proveedores" data-size="7" data-live-search="true"
                                    data-width="100%" title="Departamentos">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="municipio_proveedores">Municipio </label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="municipio_proveedores" name="municipio_proveedores"
                                    data-width="100%" data-size="7" data-live-search="true" title="Municipios">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="telefono_proveedores">Teléfono</label>
                            <input type="text" class="form-control" id="telefono_proveedores"
                                name="telefono_proveedores" placeholder="Teléfono">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="dirección_proveedores">Dirección</label>
                            <input type="text" class="form-control" id="dirección_proveedores"
                                name="dirección_proveedores" placeholder="Dirección" maxlength="150">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="correo_proveedores">Correo</label>
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Correo" id="correo_proveedores"
                                    name="correo_proveedores" aria-label="Correo" aria-describedby="basic-addon2"
                                    maxlength="70">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>@algo.com
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="estado_proveedores">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="proveedores_activo" name="proveedores_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_proveedores_activo"></span>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_proveedor"
                    form="formProveedores">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_proveedor"
                    form="formProveedores">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_proveedor"
                    form="formProveedores">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PROVEEDORES-->

<!--INICIO MODALS BUSQUEDA-->
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
                                class="table table-striped table-condensed table-hover" style="width:100%">
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
                                class="table table-striped table-condensed table-hover" style="width:100%">
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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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

<!--INICIO MODAL COLABORADORES-->
<div class="modal fade" id="modal_registrar_colaboradores">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Colaboradores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formColaboradores" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">

                    <input type="hidden" id="colaborador_id" name="colaborador_id" class="form-control" placeholder="Colaborador">

                    <div class="form-row" id="datosClientes" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_codigo_colaborador">Código Cliente</label>
                            <input type="text" class="form-control" id="cliente_codigo_colaborador"
                                name="cliente_codigo_colaborador" placeholder="Código Cliente" readonly
                                data-toggle="tooltip" data-placement="top"
                                title="Este código es exclusivo para su cuenta y será necesario proporcionarlo cada vez que necesite asistencia técnica">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="pin_colaborador">Pin</label>
                            <input type="text" class="form-control" id="pin_colaborador" name="pin_colaborador"
                                placeholder="Pin" readonly data-toggle="tooltip" data-placement="top"
                                title="Este es un código único, que se le solicitará después de iniciar sesión en su cuenta. Este código tiene una duración de 60 segundos antes de que expire.">
                        </div>
                        <div class="col-md-2 mb-3 align-self-end">
                            <button class="btn btn-primary" type="button" id="generarPin"><i
                                    class="fas fa-sync-alt"></i> Generar</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre">Nombre <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="nombre_colaborador" name="nombre_colaborador"
                                placeholder="Nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido">Apellido <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="apellido_colaborador"
                                name="apellido_colaborador" placeholder="Apellido" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="identidad_colaborador">Identidad <span class="priority">*</span></label>
                            <input type="number" class="form-control" id="identidad_colaborador"
                                name="identidad_colaborador" placeholder="Identidad" maxlength="13"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono">Teléfono <span class="priority">*</span></label>
                            <input type="number" class="form-control" id="telefono_colaborador"
                                name="telefono_colaborador" placeholder="Teléfono" maxlength="8"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_ingreso_colaborador">Fecha Ingreso <span
                                    class="priority">*</span></label>
                            <input type="date" class="form-control" id="fecha_ingreso_colaborador"
                                name="fecha_ingreso_colaborador" value="<?php echo date('Y-m-d'); ?>"
                                placeholder="Fecha Ingreso" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_egreso_colaborador">Fecha Egreso</label>
                            <input type="date" class="form-control" id="fecha_egreso_colaborador"
                                name="fecha_egreso_colaborador" placeholder="Fecha Egreso">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label>Puesto <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="puesto_colaborador" name="puesto_colaborador" class="selectpicker"
                                    data-width="100%" title="Puesto" data-live-search="true" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Empresa <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="colaborador_empresa_id" name="colaborador_empresa_id" class="selectpicker"
                                    data-width="100%" title="Empresa" data-live-search="true" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="estado_colaboradores">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="colaboradores_activo" name="colaboradores_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_colaboradores_activo"></span>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_colaborador"
                    form="formColaboradores">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_colaborador"
                    form="formColaboradores">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_colaborador" form="formColaboradores">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL COLABORADORES-->

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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
<!--INICIO MODALS BUSQUEDA-->

<!--INICIO MODAL EMPRESA-->
<div class="modal fade" id="modal_registrar_empresa">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Empresa</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formEmpresa" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="empresa_id" name="empresa_id" class="form-control">
                                <input type="text" id="proceso_empresa" class="form-control" readonly>
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
                            <label>Razón Social <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" name="empresa_razon_social" id="empresa_razon_social"
                                    class="form-control" placeholder="Razón Social" maxlength="100"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="far fa-building fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Empresa <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" name="empresa_empresa" id="empresa_empresa" class="form-control"
                                    placeholder="Empresa" maxlength="50"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="far fa-building fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="prefijo">RTN <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" name="rtn_empresa" id="rtn_empresa" class="form-control"
                                    placeholder="RTN" maxlength="14"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-id-card-alt fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Sitio WEB</label>
                            <div class="input-group mb-3">
                                <input type="text" name="sitioweb_empresa" id="sitioweb_empresa" class="form-control"
                                    placeholder="Sitio WEB" maxlength="150"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-globe-americas fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label>Teléfono <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" name="telefono_empresa" id="telefono_empresa" class="form-control"
                                    placeholder="Teléfono" maxlength="8"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-phone-alt fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>WhatsApp</label>
                            <div class="input-group mb-3">
                                <input type="text" name="empresa_celular" id="empresa_celular" class="form-control"
                                    placeholder="Teléfono" maxlength="8"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fab fa-whatsapp fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-6 col-12 mb-3">
                            <label for="logotipo">Logotipo</label>
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" name="logotipo" id="logotipo" accept="image/*">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-image"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 mb-3">
                            <label for="firma_documento">Firma Documento</label>
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" name="firma_documento" id="firma_documento"
                                    accept="image/*">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-image"></i>
                                    </span>
                                </div>
                                <button id="toggle-firma" class="btn btn-primary">
                                    <!-- El ícono y texto del botón se ajustarán con JS -->
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 col-12 mb-3">
                            <label for="correo_empresa">Correo</label>
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Correo" id="correo_empresa"
                                    name="correo_empresa" aria-label="Correo" aria-describedby="basic-addon2">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>@correo.com
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Facebook</label>
                            <div class="input-group mb-3">
                                <textarea id="facebook_empresa" name="facebook_empresa" placeholder="Facebook"
                                    class="form-control" maxlength="100" rows="2"></textarea>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fab fa-facebook fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Horario</label>
                            <div class="input-group mb-3">
                                <textarea id="horario_empresa" name="horario_empresa" placeholder="Horario"
                                    class="form-control" maxlength="100" rows="2"></textarea>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-clock fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Eslogan</label>
                            <div class="input-group mb-3">
                                <textarea id="empresa_eslogan" name="empresa_eslogan" placeholder="Eslogan"
                                    class="form-control" maxlength="100" rows="2"></textarea>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-file-alt fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>Otra Información</label>
                            <div class="input-group mb-3">
                                <textarea id="empresa_otra_informacion" name="empresa_otra_informacion"
                                    placeholder="Otra Información" class="form-control" maxlength="100"
                                    rows="4"></textarea>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-info-circle fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="direccion_empresa">Dirección </label>
                            <div class="input-group mb-3">
                                <textarea id="direccion_empresa" name="direccion_empresa" placeholder="Dirección "
                                    class="form-control" maxlength="100" rows="4"></textarea>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-address-card fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_empresa">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="empresa_activo" name="empresa_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_empresa_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_empresa"
                    form="formEmpresa">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_empresa"
                    form="formEmpresa">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_empresa"
                    form="formEmpresa">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
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
            <div class="modal-header">
                <h4 class="modal-title">Cajas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formCajas" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="cajas_id" name="cajas_id" class="form-control">
                                <input type="text" id="proceso_cajas" class="form-control" readonly>
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
                            <label for="prefijo">Caja <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Caja" id="nombre_caja"
                                    name="nombre_caja" aria-label="Colaborador" aria-describedby="basic-addon2" readonly
                                    required>
                                <div class="input-group-append" id="obtener_caja">
                                    <span><a data-toggle="modal" href="#" class="btn btn-outline-success form-control">
                                            <div class="sb-nav-link-icon"></div><i class="fas fa-sync-alt fa-lg"></i>
                                        </a></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="prefijo">Descripción <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="text" name="descripcion_caja" id="descripcion_caja" class="form-control"
                                    placeholder="Descripción" maxlength="50"
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
                    <div class="form-group">
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="caja_estado" name="caja_estado" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_caja_estado"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_caja"
                    form="formCajas">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_caja"
                    form="formCajas">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_caja"
                    form="formCajas">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL CAJAS-->

<!--INICIO MODAL APERTURA CAJA-->
<div class="modal fade" id="modal_apertura_caja">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Apertura Caja</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formAperturaCaja" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="apertura_id" name="apertura_id" class="form-control">
                                <input type="hidden" id="colaboradores_id_apertura" name="colaboradores_id_apertura"
                                    class="form-control">
                                <input type="text" id="proceso_aperturaCaja" class="form-control" readonly>
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
                            <label for="prefijo">Usuario</label>
                            <input type="text" class="form-control" placeholder="Usuario" id="usuario_apertura"
                                name="usuario_apertura" aria-label="Usuario" aria-describedby="basic-addon2" readonly
                                required>
                            <input type="hidden" class="form-control" placeholder="Usuario"
                                id="colaboradores_id_apertura" name="colaboradores_id_apertura" aria-label="Usuario"
                                aria-describedby="basic-addon2" readonly required>
                        </div>
                    </div>

                    <div class="form-row" id="monto_apertura_grupo">
                        <div class="col-md-12 mb-3">
                            <label for="prefijo">Monto Apertura</label>
                            <input type="text" class="form-control" placeholder="Monto Apertura" id="monto_apertura"
                                name="monto_apertura" aria-label="Monto Apertura" aria-describedby="basic-addon2"
                                step="0.01" value="0.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="prefijo">Fecha</label>
                            <input type="text" name="fecha_apertura" id="fecha_apertura" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required readonly>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="open_caja"
                    form="formAperturaCaja">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Aperturar
                </button>
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="close_caja"
                    form="formAperturaCaja">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Cerrar
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
            <div class="modal-header">
                <h4 class="modal-title">Productos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formProductos" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="productos_id" name="productos_id" class="form-control">
                                <input type="text" id="proceso_productos" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-1 mb-3">
                            <input type="file" name="file" class="file" accept=".png, .jpeg, .jpg, .jfif">
                            <img type="button"
                                src="<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png"
                                id="preview" class="browse img-thumbnail" data-toggle="tooltip" data-placement="top"
                                title="Cargar Imagen">
                            <input type="hidden" class="form-control" disabled placeholder="Cargar Imágen"
                                id="file_product" name="file_product">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bar_code_product">Código de Barra</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="bar_code_product" name="bar_code_product"
                                    placeholder="Código de Barra" data-toggle="tooltip" data-placement="top"
                                    title="Si este campo esta vacío o tiene el número cero el sistema genera un código de barra automáticamente siendo un valor único">
                                <div class="input-group-append" id="grupo_editar_bacode">
                                    <span data-toggle="tooltip" data-placement="top" title="Editar Código de Barra"><a
                                            data-toggle="modal" href="#"
                                            class="btn btn-outline-success form-control editar_barcode">
                                            <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i>
                                        </a></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="producto">Producto <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="producto" name="producto" maxlength="50"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                placeholder="Producto" required data-toggle="tooltip" data-placement="top">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3" style="display: none;">
                            <label for="producto_empresa_id">Empresa <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="producto_empresa_id" name="producto_empresa_id" data-width="100%"
                                    class="selectpicker" data-size="7" data-live-search="true" title="Empresa">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="producto_superior" data-toggle="tooltip" data-placement="top"
                                title="El campo 'Producto Superior' se emplea cuando estás creando un producto que tiene una conexión con otro. Imagina que estás diseñando un 'Kit de Jardinería', aquí puedes elegir 'Semillas' como el producto superior, indicando que el kit depende de las semillas para su existencia.">Superior</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="producto_superior" name="producto_superior"
                                    data-width="100%" data-size="7" data-live-search="true" title="Superior">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="tipo_producto">Tipo Producto <span class="priority">*</span></label>
                            <select id="tipo_producto" name="tipo_producto" required class="selectpicker" data-size="7"
                                data-width="100%" data-live-search="true" title="Tipo Producto">
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 confCategoria" style="display:none;">
                            <label for="producto_categoria">Categoria</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="producto_categoria" name="producto_categoria" required
                                    data-width="100%" data-size="7" data-live-search="true" title="Categoría">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" style="display: none;">
                            <label for="almacen">Almacén</label>
                            <div class="input-group mb-3">
                                <select id="almacen" name="almacen" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Almacén">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="medida">Medida <span class="priority">*</span></label>
                            <select id="medida" name="medida" required class="selectpicker" data-size="7"
                                data-width="100%" data-live-search="true" title="Medida">
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="departamento_cliente">Precio Compra <span></label>
                            <input type="number" class="form-control" id="precio_compra" name="precio_compra"
                                placeholder="Precio Compra" step="0.00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="precio_venta">Precio Venta <span class="priority">*</span></label>
                            <input type="number" class="form-control" id="precio_venta" name="precio_venta"
                                placeholder="Precio Venta" step="0.00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="porcentaje_venta">Ganancia</label>
                            <input type="number" class="form-control" id="porcentaje_venta" name="porcentaje_venta"
                                placeholder="Ganancia" step="0.00001" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="cantidad_mayoreo">Cantidad Mayoreo </label>
                            <input type="number" class="form-control" id="cantidad_mayoreo" name="cantidad_mayoreo"
                                placeholder="Precio Mayoreo" step="0.00001" value="">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="precio_mayoreo">Precio Mayoreo </label>
                            <input type="number" class="form-control" id="precio_mayoreo" name="precio_mayoreo"
                                placeholder="Precio Mayoreo" step="0.00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Cantidad Mínima</label>
                            <input type="number" id="cantidad_minima" name="cantidad_minima"
                                placeholder="Cantidad Mínima" class="form-control" step="0.00001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Cantidad Máxima</label>
                            <input type="number" id="cantidad_maxima" name="cantidad_maxima"
                                placeholder="Cantidad Máxima" class="form-control" step="0.00001">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" placeholder="Descripción" class="form-control"
                                maxlength="100" rows="2"></textarea>
                            <p id="charNum_descripcion">100 Caracteres</p>
                        </div>
                    </div>

                    <div class="form-group custom-control custom-checkbox custom-control-inline">
                        <div class="col-md-12" id="estado_producto">
                            <span class="mr-2">Estado:</span>
                            <label class="switch">
                                <input type="checkbox" id="producto_activo" name="producto_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_producto_activo"></span>
                        </div>
                    </div>
                    <br />
                    <div class="form-group custom-control custom-checkbox custom-control-inline">
                        <div class="col-md-12">
                            <label class="form-check-label mr-1" for="defaultCheck1" data-toggle="tooltip"
                                data-placement="top"
                                title="Al activar esta opción, el Impuesto sobre Ventas (ISV) se añadirá automáticamente al producto en la factura de venta, basándose en el precio de venta agregado en este formulario.">¿Calcular
                                ISV en Factura Venta?</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Al activar esta opción, el Impuesto sobre Ventas (ISV) se añadirá automáticamente al producto en la factura de venta, basándose en el precio de venta agregado en este formulario.">
                                <input type="checkbox" id="producto_isv_factura" name="producto_isv_factura" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_producto_isv_factura"></span>
                        </div>
                        <div class="col-md-7" style="display: none;">
                            <label class="form-check-label mr-1" for="defaultCheck1" data-toggle="tooltip"
                                data-placement="top"
                                title="Al activar esta opción, el Impuesto sobre Ventas (ISV) se añadirá automáticamente al producto en la factura de compra, basándose en el precio de compra agregado en este formulario.">¿Calcular
                                ISV
                                en Compra?</label>
                            <label class="switch" data-toggle="tooltip" data-placement="top"
                                title="Al activar esta opción, el Impuesto sobre Ventas (ISV) se añadirá automáticamente al producto en la factura de compra, basándose en el precio de compra agregado en este formulario.">
                                <input type="checkbox" id="producto_isv_compra" name="producto_isv_compra" value="1">
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_producto_isv_compra"></span>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_producto"
                    form="formProductos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_producto"
                    form="formProductos">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_producto"
                    form="formProductos">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PRODUCTOS-->

<!--Modal Transferencia de Producto / Bodega-->
<div class="modal fade" tabindex="-1" role="dialog" id="modal_transferencia_producto">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transferir Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="" id="formTransferencia" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-group text-center">
                        <input type="hidden" id="productos_id" name="productos_id">
                        <input type="hidden" id="id_bodega_actual" name="id_bodega_actual">
                        <label class="modal-title" id="nameProduct" class="col-form-label"></label>
                        <input type="hidden" required readonly id="pro_trasferencia" name="pro_trasferencia" class="form-control" />
                        <input type="hidden" id="lote_id_productos" name="lote_id_productos" class="form-control">
                        <input type="hidden" id="empresa_id_productos" name="empresa_id_productos" class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="col-md-5 mb-3">
                            <label for="id_bodega">Bodega <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="id_bodega" name="id_bodega" class="selectpicker" data-live-search="true" style="width: auto;" class="form-control" title="Bodega" required>
                                </select>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-warehouse"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="cantidad_movimiento">Cantidad <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="number" id="cantidad_movimiento" name="cantidad_movimiento" placeholder="Cantidad" class="form-control" step="0.01" requiered>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-sort-amount-up-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="movimiento_comentario">Comentario</label>
                            <textarea id="movimiento_comentario" name="movimiento_comentario" class="form-control" rows="4" charmax="254" ></textarea>
                            <div class="char-count"></div>
                        </div>
                    </div>
                    
                    <br />
                    <br />
                    <br />
                    <br />
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary ml-2" type="submit" id="putEditarBodega">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Mover
                </button>
                <button class="btn btn-secondary ml-2" type="button" data-dismiss="modal">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-window-close"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FN Modal Transferencia de Producto / Bodega-->

<!--Modal Cambiar Fecha de Cadudicad-->
<div class="modal fade" tabindex="-1" role="dialog" id="modalCambiarFechaProducto">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Fecha de Caducidad Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="" id="formTransferenciaCambiarFecha" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-group text-center">
                        <input type="hidden" id="productos_id" name="productos_id" class="form-control">
                        <input type="hidden" value="" id="id_bodega_actual" name="id_bodega_actual" class="form-control">
                        <label class="modal-title" id="nameProduct" class="col-form-label"></label>
                        <input type="hidden" id="cantidad_productos" name="cantidad_productos" class="form-control">
                        <input type="hidden" id="empresa_id_productos" name="empresa_id_productos" class="form-control">
                        <input type="hidden" id="lote_id_productos" name="lote_id_productos" class="form-control">
                        <input type="hidden" required readonly id="pro_cambiar_fecha" name="pro_cambiar_fecha" class="form-control" />
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-4">
                            <label for="fecha_caducidad">Fecha Caducidad <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <input type="date" id="fecha_caducidad" name="fecha_caducidad" placeholder="Cantidad" class="form-control" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />
                    <br />
                    <br />
                    <br />
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary ml-2" type="submit" id="EditarFechaVencimiento">
                    <div class="sb-nav-link-icon"></div><i class="fa-solid fa-floppy-disk fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FN Modal Cambiar Fecha de Cadudicad-->

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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">RTN Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarRTNClientes" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="clientes_id" name="clientes_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_clientes" name="pro_clientes"
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
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">Cliente <span class="priority">*</span></label>
                            <input type="text" required id="cliente" name="cliente" placeholder="Cliente" readonly
                                class="form-control" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">RTN <span class="priority">*</span></label>
                            <input type="number" required id="rtn_cliente" name="rtn_cliente" placeholder="RTN"
                                class="form-control" maxlength="14"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="editar_rtn_clientes" form="formEditarRTNClientes">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EDITAR RTN CLIENTES-->

<!--INICIO MODAL EDITAR RTN PPROVEEDORES-->
<div class="modal fade" id="modalEditarRTNProveedores">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">RTN Proveedores</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarRTNProveedores" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="proveedores_id"
                                name="proveedores_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_proveedores" name="pro_proveedores"
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
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">Proveedor <span class="priority">*</span></label>
                            <input type="text" required id="proveedor" name="proveedor" readonly placeholder="Proveedor"
                                class="form-control" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">RTN <span class="priority">*</span></label>
                            <input type="number" required id="rtn_proveedor" name="rtn_proveedor" placeholder="RTN"
                                class="form-control" maxlength="14"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="editar_rtn_proveedores" form="formEditarRTNProveedores">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL EDITAR RTN PPROVEEDORES-->

<!--INICIO MODAL EDITAR BARCODE-->
<div class="modal fade" id="modalEditarBarcode">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Código de Barras</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEditarBarcode" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="productos_id" name="productos_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_barcode" name="pro_barcode"
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
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">Producto <span class="priority">*</span></label>
                            <input type="text" required id="producto" name="producto" readonly placeholder="Producto"
                                class="form-control" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="cuenta_nombre">Código de Barra <span class="priority">*</span></label>
                            <input type="text" required id="barcode" name="barcode" placeholder="Código de Barra"
                                class="form-control" maxlength="20"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="editar_barcode"
                    form="formEditarBarcode">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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
            <div class="modal-header">
                <h4 class="modal-title">Asistencia</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formAsistencia" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="asistencia_id" name="asistencia_id" class="form-control">
                                <input type="text" id="proceso_asistencia" class="form-control" readonly>
                                <input type="hidden" id="marcarAsistencia_id" name="marcarAsistencia_id"
                                    class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="asistencia_empleado">Empleado <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="asistencia_empleado" name="asistencia_empleado" class="selectpicker"
                                    data-size="7" data-width="100%" data-live-search="true" title="Empleado">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" id="fechaAsistencia">
                            <label for="fecha">Fecha <span class="priority">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha"
                                value="<?php echo date('Y-m-d'); ?>" placeholder="Fecha">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3" id="grupoHora">
                            <label for="fecha" id="registro_hora">Hora Entrada <span class="priority">*</span></label>
                            <input type="time" class="form-control" id="hora" name="hora" step="any">
                        </div>
                        <div class="col-md-3 mb-3" id="grupoHorai">
                            <label for="fecha" id="registro_horai">Hora Entrada <span class="priority">*</span></label>
                            <input type="time" class="form-control" id="horagi" name="horagi"
                                value="<?php echo date('H:i'); ?>" step="any">
                        </div>
                        <div class="col-md-3 mb-3" id="grupoHoraf">
                            <label for="fecha">Hora Salida <span class="priority">*</span></label>
                            <input type="time" class="form-control" id="horagf" name="horagf" step="any">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3" id="grupoHoraComentario">
                            <label for="comentario">Comentario</label>
                            <input type="text" class="form-control" id="comentario" name="comentario">
                        </div>
                    </div>
                    <div class="form-row">
                        <br />
                        <br />
                        <br />
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_asistencia"
                    form="formAsistencia">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_asistencia"
                    form="formAsistencia">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
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
            <div class="modal-header">
                <h4 class="modal-title">Generar Sistema</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formGenerarSistema" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="text" id="clientes_id" name="clientes_id" class="form-control">
                                <input type="hidden" id="clientes_telefono" name="clientes_telefono"
                                    class="form-control">
                                <input type="hidden" id="clientes_correo" name="clientes_correo" class="form-control">
                                <input type="hidden" id="clientes_ubicacion" name="clientes_ubicacion"
                                    class="form-control">
                                <input type="text" id="proceso_GenerarSistema" class="form-control" readonly>
                                <input type="hidden" id="marcarAsistencia_id" name="marcarAsistencia_id"
                                    class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-5 mb-4">
                            <label for="asistencia_empleado">Cliente <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Cliente"
                                required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label for="rtn">RTN <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="rtn" name="rtn" placeholder="RTN" required>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label for="empresa">Empresa <span class="priority">*</span></label>
                            <input type="text" class="form-control" id="empresa" name="empresa" placeholder="Empresa"
                                data-width="100%" required maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-4">
                            <label for="eslogan">Eslogan </label>
                            <input type="text" class="form-control" id="eslogan" name="eslogan" placeholder="Eslogan"
                                maxlength="50"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                        <div class="col-md-4 mb-4">
                            <label for="otra_informacion">Otra Información</label>
                            <input type="text" class="form-control" id="otra_informacion" name="otra_informacion"
                                placeholder="Otra Información" maxlength="50"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                        <div class="col-md-4 mb-4">
                            <label for="whatsApp">WhatsApp </label>
                            <input type="number" class="form-control" id="whatsApp" name="whatsApp"
                                placeholder="WhatsApp" maxlength="8"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="sistema">Sistema</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="sistema" name="sistema" data-width="100%" data-size="7" data-live-search="true"
                                    title="Sistema">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="plan">Plan</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="plan" name="plan" data-width="100%" data-size="7" data-live-search="true"
                                    title="Plan">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="validar">Validar Facturación</label>
                            <div class="input-group mb-3">
                                <select class="selectpicker" id="validar" name="validar" data-width="100%" data-size="7" data-live-search="true"
                                    title="Validar Facturación">
                                </select>
                            </div>
                        </div> 
                    </div>

                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableGenerarSistema"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>DB</th>
                                            <th>Sistema</th>
                                            <th>Plan</th>
                                            <th>Validar Facturación</th>
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_generarSitema"
                    form="formGenerarSistema">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Generar
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