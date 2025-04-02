<!--INICIO MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->
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
                <form class="form-horizontal FormularioAjax" id="formEgresosContables" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="egresos_id" name="egresos_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_egresos_contabilidad"
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
                        <div class="col-md-3 mb-3">
                            <label for="fecha_egresos">Fecha Factura <span class="priority">*<span /></label>
                            <input type="date" required id="fecha_egresos" name="fecha_egresos"
                                value="<?php echo date ("Y-m-d");?>" class="form-control" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="proveedor_egresos">Proveedor <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="proveedor_egresos" name="proveedor_egresos" data-width="100%"
                                    class="selectpicker" data-size="10" data-live-search="true" title="Proveedor">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="cuenta_egresos">Cuenta <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="cuenta_egresos" name="cuenta_egresos" class="selectpicker" data-width="100%"
                                    data-size="10" data-live-search="true" title="Cuenta">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" style="display: none">
                            <label for="empresa_egresos">Empresa <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="empresa_egresos" name="empresa_egresos" class="selectpicker"
                                    data-width="100%" data-size="10" data-live-search="true" title="Empresa">
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
                            <label for="isv_egresos">ISV </label>
                            <input type="number" id="isv_egresos" name="isv_egresos" placeholder="ISV"
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;"
                    id="edi_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

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
                <form class="form-horizontal FormularioAjax" id="formEgresosContables" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="egresos_id" name="egresos_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_egresos_contabilidad"
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;"
                    id="edi_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_egresosContabilidad" form="formEgresosContables">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
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
                <form class="form-horizontal FormularioAjax" id="formCategoriaEgresos" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="categoria_gastos_id"
                                name="categoria_gastos_id" />
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
                                                class="table table-striped table-condensed table-hover"
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="regCategoriaEgresos" form="formCategoriaEgresos">
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
                <form class="form-horizontal FormularioAjax" id="formUpdateCategoriaEgresos" action="" method="POST"
                    data-form="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="categoria_gastos_id"
                                name="categoria_gastos_id" />
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

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;"
                    id="ediCategoriaEgresos" form="formUpdateCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
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
                <form class="form-horizontal FormularioAjax" id="formCategoriaEgresos" action="" method="POST"
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
                                                class="table table-striped table-condensed table-hover"
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
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="regCategoriaEgresos" form="formCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;"
                    id="ediCategoriaEgresos" form="formCategoriaEgresos">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
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
                                    class="table table-striped table-condensed table-hover" style="width:100%">
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