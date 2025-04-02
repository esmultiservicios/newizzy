<!--INICIO MODAL MODAL PARA AYUDA-->
<div class="modal fade" id="modalAyuda">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-question-circle"></i> Ayuda</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formAyuda" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label>
                                <center><b>Las teclas de función solo se pueden utilizar posicionándose en el área de la
                                        factura, especificamente en el campo Código del Producto</b></center>
                            </label>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite Guardar una factura para dejarla abierta, esto le da la posibilidad de seguir agregando más items"><b>F2</b>
                                Guardar</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite realizar una búsqueda de productos, e inclusive permite crear nuevos productos en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F3</b>
                                Búsqueda de Productos</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite aplicar descuentos a los productos, con previa autorización de un supervisor o un administrador del sistema"><b>F4</b>
                                Agregar Descuentos a los Productos</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite actualizar la página, si utiliza esta función tenga en cuenta que perderá todo el contenido agregado en esta pantalla"><b>F5</b>
                                Actualizar</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Esta opción puede ser útil para ingresar un nuevo precio a un producto siempre y cuando el documento original de compra y/o cotización muestre un precio diferente al que se muestra en el sistema"><b>F6</b>
                                Modificar Precio a los Productos</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite realizar el cobro de la factura, si la factura es al contado, la misma ermita registrar el pago, de ser al crédito solo almacena una cuenta por cobrar a clientes"><b>F7</b>
                                Cobrar Factura</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite realizar una búsqueda de clientes, e inclusive permite crear nuevos clientes en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F8</b>
                                Clientes</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite realizar una búsqueda de vendedores y/o colaboradores, e inclusive permite crear nuevos vendedores y/o colaboradores en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F9</b>
                                Colaboradores</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite poner en disponible la caja para realizar las ventas del día, y poder llevar un registro de los cajeros disponibles y el total facturado"><b>F10</b>
                                Apertura de Caja</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Cierra la caja, realizando un conteo de todas las ventas realizadas en el día, desde el comienzo del número de factura hasta la factura final emitida durante el día."><b>F11</b>
                                Cierre de Caja</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite aumentar la cantidad, se debe posicionar en el código del producto y presionar la tecla más (+) para que surja efecto"><b>+</b>
                                Aumentar Cantidad</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Permite disminuir la cantidad, se debe posicionar en el código del producto y presionar la tecla menos (-) para que surja efecto"><b>-</b>
                                Disminuir Cantidad</label>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label data-toggle="tooltip" data-placement="top"
                                title="Este comodín permite agregar un valor en la cantidad, para hacerlo debemos escribir la cantidad que requerimos, seguido del comodín y luego el código del producto para que surja efecto, por ejemplo: 10*cod_produto esto agregará un 10 automáticamente en la cantidad"><b>*</b>
                                Comodin Asterisco</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--INICIO MODAL PARA MODIFICAR PRECIO FACTURAS-->
<div class="modal fade" id="modalModificarPrecioFacturacion">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Modificar Precio Producto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formModificarPrecioFacturacion" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="modificar_precio_productos_id"
                                name="modificar_precio_productos_id" class="form-control" />
                            <input type="hidden" required="required" readonly id="modificar_precio_clientes_id"
                                name="modificar_precio_clientes_id" class="form-control" />
                            <input type="hidden" required="required" readonly id="modificar_precio_fecha"
                                name="modificar_precio_fecha" class="form-control" />
                            <input type="hidden" required="required" readonly id="row_index" name="row_index"
                                class="form-control" />
                            <input type="hidden" required="required" readonly id="col_index" name="col_index"
                                class="form-control" />
                            <input type="hidden" required="required" readonly id="modificar_precio_isv_aplica"
                                name="modificar_precio_isv_aplica" class="form-control" />
                            <input type="hidden" required="required" readonly id="modificar_precio_isv_valor"
                                name="modificar_precio_isv_valor" class="form-control" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_modificar_precio"
                                    name="pro_modificar_precio" class="form-control" />
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
                            <label for="producto_modificar_precio_fact">Producto <span
                                    class="priority">*<span /></label>
                            <input type="text" readonly required id="producto_modificar_precio_fact"
                                name="producto_modificar_precio_fact" placeholder="Producto" class="form-control"
                                maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="referencia_modificar_precio_fact">Referencia <span
                                    class="priority">*<span /></label>
                            <input type="text" required id="referencia_modificar_precio_fact"
                                name="referencia_modificar_precio_fact" placeholder="Referencia y/o Número de Documento"
                                class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="precio_modificar_precio_fact">Precio <span class="priority">*<span /></label>
                            <input type="text" required id="precio_modificar_precio_fact"
                                name="precio_modificar_precio_fact" placeholder="Precio" class="form-control"
                                maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_modificar_precio_fact" form="formModificarPrecioFacturacion">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA MODIFICAR PRECIO FACTURAS-->

<!--INICIO MODAL PARA FORMULARIO DESCENTOS EN FACTURACION-->
<div class="modal fade" id="modalDescuentoFacturacion">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Descuento</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formDescuentoFacturacion" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="descuento_productos_id"
                                name="descuento_productos_id" />
                            <input type="hidden" required="required" readonly id="row_index" name="row_index" />
                            <input type="hidden" required="required" readonly id="col_index" name="col_index" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_descuento_fact" name="pro_descuento_fact"
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
                        <div class="col-md-8 mb-3">
                            <label for="producto_descuento_fact">Producto <span class="priority">*<span /></label>
                            <input type="text" readonly required id="producto_descuento_fact"
                                name="producto_descuento_fact" placeholder="Producto" class="form-control"
                                maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="precio_descuento_fact">Precio <span class="priority">*<span /></label>
                            <input type="text" readonly required id="precio_descuento_fact" name="precio_descuento_fact"
                                placeholder="Precio" class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                step="0.01" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label for="porcentaje_descuento_fact">% Descuento <span class="priority">*<span /></label>
                            <input type="text" required id="porcentaje_descuento_fact" name="porcentaje_descuento_fact"
                                placeholder="Porcentaje de Descuento" class="form-control" maxlength="11"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="descuento_fact">Valor Descuento <span class="priority">*<span /></label>
                            <input type="text" required id="descuento_fact" name="descuento_fact"
                                placeholder="Descuento" class="form-control" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                step="0.01" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_DescuentoFacturacion" form="formDescuentoFacturacion">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA FORMULARIO DESCENTOS EN FACTURACION-->

<!--INICIO MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->
<div class="modal fade" id="modal_buscar_cotizaciones">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Cotizaciones</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cotizaciones">
                    <div class="form-row">
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Tipo Factura
                                    </span>
                                    <select id="tipo_cotizacion_reporte" name="tipo_cotizacion_reporte"
                                        data-width="100%" class="selectpicker" title="Tipo Factura"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
								$fecha = date ("Y-m-d");
								
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
								
								
								echo $fecha_inicial;
							?>" class="form-control ml-1" data-toggle="tooltip" data-placement="top" title="Fecha Inicio">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Fin
                                    </span>
                                </div>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control ml-1" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Fin">
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button class="consultar btn btn-secondary ml-1" type="submit" id="search">
                                <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaCotizaciones"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Cobrar</th>
                                            <th>Imprimir</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Proveedor</th>
                                            <th>Factura</th>
                                            <th>SubTotal</th>
                                            <th>ISV</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
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
<!--FIN MODAL BUSQUEDA CONVERTIR COTIZACION EN FACTURAS-->

<!--INICIO MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->
<div class="modal fade" id="modal_buscar_cuentas_cobrar_clientes">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Cuentas por Cobrar Clientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_busqueda_cuentas_cobrar_clientes">
                    <div class="form-row">
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Estado
                                    </span>
                                    <select id="cobrar_clientes_estado" name="cobrar_clientes_estado"
                                        class="selectpicker" data-width="100%" title="Estado" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Clientes
                                    </span>
                                    <select id="cobrar_clientes" name="cobrar_clientes" class="selectpicker"
                                        data-width="100%" title="Clientes" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechai" name="fechai"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Inicio" style="width:165px;">
                            </div>
                        </div>
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control ml-1" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Fin" style="width:165px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaCuentasCobrarClientes"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Factura</th>
                                            <th>Crédito</th>
                                            <th>Abonos</th>
                                            <th>Saldo</th>
                                            <th>Abonar</th>
                                            <th>Abonos Realizados</th>
                                            <th>Factura</th>
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
<!--FIN MODAL BUSQUEDA COBRAR CUENTAS POR COBRAR CLIENTES-->

<!--INICIO MODAL BUSQUEDA FACTURAS BORRADOR-->
<div class="modal fade" id="modal_buscar_bill_draft">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Facturas Pendientes</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_bill_draft">
                    <div class="form-row">
                        <div class="col-md-5 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
								$fecha = date ("Y-m-d");
								
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
								
								
								echo $fecha_inicial;
							?>" class="form-control ml-1" data-toggle="tooltip" data-placement="top" title="Fecha Inicio">
                            </div>
                        </div>
                        <div class="col-md-5 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control ml-1" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Fin">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="consultar btn btn-secondary ml-1" type="submit" id="search">
                                <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaBillDraft"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Continuar</th>
                                            <th>Eliminar</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Empresa</th>
                                            <th>Factura</th>
                                            <th>SubTotal</th>
                                            <th>ISV</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
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
<!--FIN MODAL BUSQUEDA FACTURAS BORRADOR-->

<!--INICIO MODAL BUSQUEDA CREDITO Y CONTADO-->
<div class="modal fade" id="modal_buscar_bill">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Facturas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formulario_bill">
                    <div class="form-row">
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Tipo Factura
                                    </span>
                                    <select id="tipo_factura_reporte" name="tipo_factura_reporte" class="selectpicker"
                                        data-width="100%" title="Tipo de Factura" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Facturador
                                    </span>
                                    <select id="facturador" name="facturador" class="selectpicker" title="Facturador"
                                        data-width="100%" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mx-sm-3 mb-1">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Vendedor
                                    </span>
                                    <select id="vendedor" name="vendedor" class="selectpicker" title="Vendedor"
                                        data-width="100%" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
								$fecha = date ("Y-m-d");
								
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
								
								
								echo $fecha_inicial;
							?>" class="form-control ml-1" data-toggle="tooltip" data-placement="top" title="Fecha Inicio">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Fin
                                    </span>
                                </div>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control ml-1" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Fin">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableBusquedaBill"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Cliente</th>
                                            <th>Factura</th>
                                            <th>SubTotal</th>
                                            <th>ISV</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
                                            <th>Factura</th>
                                            <th>Comprobante</th>
                                            <th>Enviar</th>
                                            <th>Anular</th>
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
<!--FIN MODAL BUSQUEDA FACTURAS CREDITO Y CONTADO-->