<!--INICIO MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->
<div class="modal fade" id="modalEgresosContables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Registro de Egresos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEgresosContables" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="egresos_id" name="egresos_id">				
                    
                    <!-- Sección de Datos del Egreso -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Datos del Egreso</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_egresos"><i class="fas fa-calendar-day mr-1"></i>Fecha Factura <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_egresos" name="fecha_egresos" value="<?php echo date ("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de la factura del egreso</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="proveedor_egresos"><i class="fas fa-user-tie mr-1"></i>Recibí de <span class="priority">*</span></label>
                                    <select id="proveedor_egresos" name="proveedor_egresos" class="selectpicker form-control" data-live-search="true" title="Seleccione proveedor" required>
                                        <option value="">Seleccione</option>
                                        <!-- Las opciones se llenarán con JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">Seleccione el cliente o ingrese uno nuevo</small>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNuevoProveedor">
                                        <i class="fas fa-plus-circle mr-1"></i> Agregar Nuevo Proveedor
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cuenta_egresos"><i class="fas fa-piggy-bank mr-1"></i>Cuenta <span class="priority">*</span></label>
                                    <select id="cuenta_egresos" name="cuenta_egresos" class="selectpicker form-control" data-live-search="true" title="Seleccione cuenta" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Cuenta contable asociada</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Detalles de Factura -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Detalles de Factura</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="categoria_gastos"><i class="fas fa-tags mr-1"></i>Categoría</label>
                                    <select id="categoria_gastos" name="categoria_gastos" class="selectpicker form-control" data-live-search="true" title="Seleccione categoría">
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Categoría del gasto</small>
                                </div>
                                <div class="col-md-9 mb-3">
                                    <label for="factura_egresos"><i class="fas fa-file-invoice mr-1"></i>Factura <span class="priority">*</span></label>
                                    <input type="text" required id="factura_egresos" name="factura_egresos" placeholder="Número de factura" class="form-control" maxlength="19" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Número de factura (máx. 19 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Montos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i>Montos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="subtotal_egresos"><i class="fas fa-receipt mr-1"></i>Subtotal <span class="priority">*</span></label>
                                    <input type="number" required id="subtotal_egresos" name="subtotal_egresos" placeholder="0.00" class="form-control" step="0.01">
                                    <small class="form-text text-muted">Subtotal antes de impuestos</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="isv_egresos"><i class="fas fa-percent mr-1"></i>ISV</label>
                                    <input type="number" id="isv_egresos" name="isv_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Impuesto sobre ventas</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="descuento_egresos"><i class="fas fa-tag mr-1"></i>Descuento</label>
                                    <input type="number" id="descuento_egresos" name="descuento_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Descuentos aplicados</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nc_egresos"><i class="fas fa-file-invoice-dollar mr-1"></i>Nota Crédito</label>
                                    <input type="number" id="nc_egresos" name="nc_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Notas de crédito aplicadas</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="total_egresos"><i class="fas fa-money-bill-wave mr-1"></i>Total</label>
                                    <input type="number" readonly id="total_egresos" name="total_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Total a pagar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Observaciones -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard mr-2"></i>Observaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="observacion_egresos"><i class="fas fa-align-left mr-1"></i>Observación</label>
                                    <input type="text" id="observacion_egresos" name="observacion_egresos" placeholder="Observaciones" class="form-control" maxlength="150" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Observaciones adicionales (máx. 150 caracteres)</small>
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
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_egresosContabilidad" form="formEgresosContables">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_egresosContabilidad" form="formEgresosContables">
                    <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->

<div class="modal fade" id="modalEgresosContables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Egresos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEgresosContables" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required="required" readonly id="egresos_id" name="egresos_id" />					
					
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="fecha_egresos">Fecha Factura <span class="priority">*<span /></label>
                            <input type="date" required id="fecha_egresos" name="fecha_egresos"
                                value="<?php echo date ("Y-m-d");?>" class="form-control" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="proveedor_egresos">Proveedor <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="proveedor_egresos" name="proveedor_egresos" class="selectpicker"
                                    data-width="100%" data-size="10" data-live-search="true" title="Proveedor">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="cuenta_egresos">Cuenta <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="cuenta_egresos" name="cuenta_egresos" class="selectpicker" data-size="10"
                                    data-width="100%" data-live-search="true" title="Cuenta">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="empresa_egresos">Empresa <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="empresa_egresos" name="empresa_egresos" class="selectpicker" data-size="10"
                                    data-width="100%" data-live-search="true" title="Empresa">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="prefijo">Categoria</label>
                            <div class="input-group mb-3">
                                <select id="categoria_gastos" name="categoria_gastos" class="selectpicker"
                                    data-width="100%" data-size="10" data-live-search="true" title="Categoria">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-9 mb-3">
                            <label for="factura_egresos">Factura <span class="priority">*<span /></label>
                            <input type="text" required id="factura_egresos" name="factura_egresos"
                                placeholder="Factura" class="form-control" maxlength="19"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="subtotal_egresos">Subtotal <span class="priority">*<span /></label>
                            <input type="text" required id="subtotal_egresos" name="subtotal_egresos"
                                placeholder="Subtotal" class="form-control" step="0.01" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="isv_egresos">ISV <span class="priority">*<span /></label>
                            <input type="number" required id="isv_egresos" name="isv_egresos" placeholder="ISV"
                                class="form-control" step="0.01" value="0" />
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="descuento_egresos">Descuento</label>
                            <input type="number" id="descuento_egresos" name="descuento_egresos" placeholder="Descuento"
                                class="form-control" step="0.01" value="0" />
                        </div>
                        <div class="col-md-3 mb-4">
                            <label for="nc_egresos">Nota Crédito </label>
                            <input type="number" id="nc_egresos" name="nc_egresos" placeholder="NC" class="form-control"
                                step="0.01" value="0" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-3 mb-4">
                            <label for="total_egresos">Total </label>
                            <input type="number" readonly id="total_egresos" name="total_egresos" placeholder="Total"
                                class="form-control" step="0.01" value="0" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-12 mb-4">
                            <label for="observacion_egresos">Observación </label>
                            <input type="text" id="observacion_egresos" name="observacion_egresos"
                                placeholder="Observacion" class="form-control" step="0.01" maxlength="150"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>                   
                <button class="guardar btn btn-success ml-2" type="submit" style="display: none;" id="reg_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-success ml-2" type="submit" style="display: none;" id="edi_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg fa-lg"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->

<!--INICIO MODAL REGISTRO CATEGORIAS-->
<div class="modal fade" id="modalCategoriasEgresos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Categorías</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formCategoriaEgresos" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required="required" readonly id="categoria_gastos_id" name="categoria_gastos_id" />					

                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="factura_egresos">Categoría <span class="priority">*<span /></label>
                            <input type="text" required id="categoria" name="categoria" placeholder="Categoria"
                                class="form-control" maxlength="19" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="modal-body">
                            <form class="FormularioAjax" id="formularioCategoriaEgresos">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <div class="overflow-auto">
                                            <table id="DatatableCategoriaEgresos"
                                                class="table table-header-gradient table-header-gradient table-striped table-condensed table-hover"
                                                style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Categoría</th>
                                                        <th>Editar</th>
                                                        <th>Eliminar</th>
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
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>                
                <button class="guardar btn btn-success ml-2" type="submit" style="display: none;" id="regCategoriaEgresos" form="formCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUpdateCategoriasEgresos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Categorías</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formUpdateCategoriaEgresos" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required="required" readonly id="categoria_gastos_id" name="categoria_gastos_id" />
                    
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="factura_egresos">Categoría <span class="priority">*<span /></label>
                            <input type="text" required id="categoria" name="categoria" placeholder="Categoria"
                                class="form-control" maxlength="19" />
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="editar btn btn-success ml-2" type="submit" style="display: none;" id="ediCategoriaEgresos" form="formUpdateCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->

<!--INICIO MODAL REGISTRO CATEGORIAS-->
<div class="modal fade" id="modalCategoriasEgresos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Categorías</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal" id="formCategoriaEgresos" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="categoria_gastos_id "
                                name="categoria_gastos_id " />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_categoriaEgresos"
                                    name="pro_egresos_contabilidad" class="form-control" />
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
                            <label for="factura_egresos">Categoría <span class="priority">*<span /></label>
                            <input type="text" required id="categoria" name="categoria" placeholder="Categoria"
                                class="form-control" maxlength="19" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="modal-body">
                            <form class="FormularioAjax" id="formularioCategoriaEgresos">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <div class="overflow-auto">
                                            <table id="DatatableCategoriaEgresos"
                                                class="table table-header-gradient table-striped table-condensed table-hover"
                                                style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Categoría</th>
                                                        <th>Editar</th>
                                                        <th>Eliminar</th>
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
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-success ml-2" type="submit" style="display: none;" id="regCategoriaEgresos" form="formCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-success ml-2" type="submit" style="display: none;" id="ediCategoriaEgresos" form="formCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL REGISTRO CATEGORIAS-->

<!--INICIO MODAL REPORTE CATEGORIAS-->
<div class="modal fade" id="modalReporteCategorias">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Reporte Categorías</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formularioReporteCategorias">
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
                                        <div class="sb-nav-link-icon"></div>Fecha Fin
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
                                <table id="DatatableReporteCategorias"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Categoría</th>
                                            <th>Monto</th>
                                        </tr>
                                    </thead>
                                    <tfoot class="bg-info text-white font-weight-bold">
                                        <tr>
                                            <td>Total</td>
                                            <td id="monto-i"></td>
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
<!--FIN MODAL REPORTE CATEGORIAS-->