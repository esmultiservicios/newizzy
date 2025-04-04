<body id="view_bill">
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-file-invoice fa-lg mr-1"></i>
                Facturas
            </div>
            <div class="card-body">
                <form class="FormularioAjax" id="invoice-form" method="POST" action="" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-group row customer-bill-box-left">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="bill-header-row">
                                <span id="rtn-customers-bill"></span>
                                <span id="client-customers-bill"></span>
                            </div>
                            <div class="bill-row"> <!-- Si necesitas otra fila para Vendedor -->
                                <span id="vendedor-customers-bill"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Después del card-header y antes del customer-bill-box-left -->
                    <div class="form-group row customer-bill-box-center">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <div id="mensajeFacturas" class="facturas-counter alert-normal">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span class="counter-text">Verificando estado de facturas...</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row customer-bill-box-right">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <span id="fecha-customers-bill"></span>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <span id="hora-customers-bill"></span>
                        </div>
                    </div>
                    <div class="bill">
                        <div class="form-group row">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <!-- Botones -->
                                <button class="btn btn-secondary" type="submit" id="help_factura" form="invoice-form"
                                    data-toggle="tooltip" data-placement="top" title="Ayuda">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-question-circle fa-lg"></i>
                                    [F1] Ayuda
                                </button>
                                <button class="btn btn-secondary" type="submit" id="guardar_factura"
                                    data-toggle="tooltip" data-placement="top" title="Guardar">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-save fa-lg"></i> [F2] Guardar
                                </button>
                                <button class="btn btn-secondary" type="submit" id="reg_factura" data-toggle="tooltip"
                                    data-placement="top" title="Facturar">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-hand-holding-usd fa-lg"></i>
                                    [F7] Facturar
                                </button>
                                <button class="btn btn-secondary" type="submit" id="add_cliente" data-toggle="tooltip"
                                    data-placement="top" title="Agregar Cliente">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-user-plus fa-lg"></i> [F8]
                                    Cliente
                                </button>
                                <button class="btn btn-secondary" type="submit" id="add_vendedor" data-toggle="tooltip"
                                    data-placement="top" title="Agregar Vendeor o Empleado">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-plus-circle fa-lg"></i> [F9]
                                    Vendedor
                                </button>
                                <button class="btn btn-secondary" type="submit" id="btn_apertura" data-toggle="tooltip"
                                    data-placement="top" title="Aperturar Caja">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-cash-register fa-lg"></i> [F10]
                                    Aperturar
                                </button>
                                <button class="btn btn-secondary" type="submit" id="btn_cierre" data-toggle="tooltip"
                                    data-placement="top" title="Cerrar Caja" style="display:none;">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-cash-register fa-lg"></i> [F11]
                                    Cerrar
                                </button>
                                <!-- Otros botones aquí -->

                                <!-- Texto antes del primer checkbox -->
                                <label class="col-form-label mr-2" for="facturas_activo">Tipo:</label>
                                <!-- Primer checkbox -->
                                <label class="switch mb-2" data-toggle="tooltip" data-placement="top"
                                    title="Tipo de Factura, Contado o Crédito">
                                    <input type="checkbox" id="facturas_activo" name="facturas_activo" value="1"
                                        checked>
                                    <div class="slider round"></div>
                                </label>
                                <span class="question mb-2" id="label_facturas_activo"></span>

                                <span id="facturas_proforma_container">
                                    <label class="col-form-label mr-2" for="facturas_proforma">Proforma:</label>
                                    <!-- Segundo checkbox -->
                                    <label class="switch mb-2" data-toggle="tooltip" data-placement="top"
                                        title="Factura Proforma">
                                        <input type="checkbox" id="facturas_proforma" name="facturas_proforma"
                                            value="1">
                                        <div class="slider round"></div>
                                    </label>
                                    <span class="question mb-2" id="label_facturas_proforma"></span>
                                </span>
                            </div>
                        </div>

                        <div class="form-group row" style="display:none">
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Cliente <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <input type="hidden" class="form-control" placeholder="Proceso" id="proceso_factura"
                                        name="proceso_factura" readonly>
                                    <input type="text" class="form-control" placeholder="Factura" id="facturas_id"
                                        name="facturas_id" readonly>
                                    <input type="text" class="form-control" placeholder="row" id="bill_row"
                                        name="bill_row" readonly value="0">
                                    <input type="hidden" class="form-control" placeholder="Cliente" id="cliente_id"
                                        name="cliente_id" readonly required>
                                    <input type="text" class="form-control" placeholder="Cliente" id="cliente"
                                        name="cliente" required readonly data-toggle="tooltip" data-placement="top"
                                        title="Cliente">
                                    <div class="input-group-append" id="grupo_buscar_colaboradores">
                                        <span data-toggle="tooltip" data-placement="top"
                                            title="Búsqueda de Empleados"><a data-toggle="modal" href="#"
                                                class="btn btn-outline-success" id="buscar_clientes">
                                                <div class="sb-nav-link-icon"></div><i
                                                    class="fas fa-search-plus fa-lg"></i>
                                            </a></span>
                                    </div>
                                </div>
                            </div>
                            <label for="inputFecha" class="col-sm-1 col-form-label-md">Fecha <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-3">
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required
                                    id="fecha" name="fecha" data-toggle="tooltip" data-placement="top"
                                    title="Fecha de Facturación" style="width:165px">
                            </div>
                        </div>
                        <div class="form-group row" style="display:none">
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Vendedor <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <input type="hidden" class="form-control" placeholder="Vendedor" id="colaborador_id"
                                        name="colaborador_id" aria-label="Colaborador" aria-describedby="basic-addon2"
                                        readonly required>
                                    <input type="text" class="form-control" placeholder="Vendedor" id="colaborador"
                                        name="colaborador" aria-label="Colaborador" aria-describedby="basic-addon2"
                                        required readonly data-toggle="tooltip" data-placement="top" title="Vendedor">
                                    <div class="input-group-append" id="grupo_buscar_colaboradores">
                                        <span data-toggle="tooltip" data-placement="top"
                                            title="Búsqueda de Colaboradores"><a data-toggle="modal" href="#"
                                                class="btn btn-outline-success" id="buscar_colaboradores">
                                                <div class="sb-nav-link-icon"></div><i
                                                    class="fas fa-search-plus fa-lg"></i>
                                            </a><span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row table-responsive-xl table table-hover">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <table id="invoiceItem" class="table-header-pro table-footer-pro">
                                    <thead class="text-align: center">
                                        <tr>
                                            <th width="2%" scope="col"><input id="checkAll" class="formcontrol" type="checkbox"></th>
                                            <th width="17.28%">Código</th>
                                            <th width="24.28%">Descripción del Producto</th>
                                            <th width="10.28%">Cantidad</th>
                                            <th width="10.28%">Medida</th>
                                            <th width="11.28%">Precio</th>
                                            <th width="11.28%">Descuento</th>
                                            <th width="11.28%">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input class="itemRow" type="checkbox"></td>
                                            <td>
                                                <input type="hidden" name="referenciaProducto[]"
                                                    id="referenciaProducto_0" class="form-control inputfield-details1"
                                                    placeholder="Referencia Producto Precio" autocomplete="off">
                                                <input type="hidden" name="isv[]" id="isv_0"
                                                    class="form-control inputfield-details1" placeholder="Producto ISV"
                                                    autocomplete="off">
                                                <input type="hidden" name="valor_isv[]" id="valor_isv_0"
                                                    class="form-control inputfield-details1" placeholder="Valor ISV"
                                                    autocomplete="off">
                                                <input type="hidden" name="facturas_detalle_id[]"
                                                    id="facturas_detalle_id_0" class="form-control"
                                                    placeholder="Código Producto" autocomplete="off">
                                                <input type="hidden" name="productos_id[]" id="productos_id_0"
                                                    class="form-control inputfield-details1"
                                                    placeholder="Código del Producto" autocomplete="off">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <button type="button" data-toggle="modal" 
                                                                class="btn btn-link buscar_productos p-0"
                                                                data-toggle="tooltip" data-placement="top" 
                                                                title="Búsqueda de Productos"
                                                                id="icon-search-bar_0">
                                                            <i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i>
                                                        </button>
                                                    </div>
                                                    <input type="text" name="bar-code-id[]" id="bar-code-id_0"
                                                        class="form-control product-bar-code inputfield-details1"
                                                        placeholder="Código del Producto" autocomplete="off">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="productName[]" id="productName_0"
                                                    placeholder="Descripción del Producto" readonly
                                                    class="form-control inputfield-details1" autocomplete="off">
                                            </td>
                                            <td>
                                                <input type="number" name="quantity[]" id="quantity_0"
                                                    placeholder="Cantidad"
                                                    class="buscar_cantidad form-control inputfield-details"
                                                    autocomplete="off" step="0.01">
                                                <input type="hidden" name="cantidad_mayoreo[]" id="cantidad_mayoreo_0"
                                                    placeholder="Cantidad Mayoreo"
                                                    class="buscar_cantidad form-control inputfield-details"
                                                    autocomplete="off" step="0.01">
                                            </td>
                                            <td>
                                                <input type="text" name="medida[]" id="medida_0" readonly
                                                    class="form-control buscar_medida" autocomplete="off"
                                                    placeholder="Medida">
                                                <input type="hidden" name="bodega[]" id="bodega_0" readonly
                                                    class="form-control buscar_bodega" autocomplete="off">

                                            </td>
                                            <td>
                                                <input type="hidden" name="precio_real[]" id="precio_real_0"
                                                    placeholder="Precio Real" class="form-control inputfield-details"
                                                    step="0.01" readonly autocomplete="off">
                                                <div class="input-group mb-3">
                                                    <input type="number" name="price[]" id="price_0"
                                                        class="form-control" step="0.01" placeholder="Precio" readonly
                                                        autocomplete="off">
                                                    <div id="suggestions_producto_0" class="suggestions"></div>
                                                    <div class="input-group-append">
                                                        <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                                            <div class="sb-nav-link-icon"></div><i
                                                                class="aplicar_precio fas fa-plus fa-lg"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="precio_mayoreo[]" id="precio_mayoreo_0"
                                                    placeholder="Precio mayoreo" step="0.01"
                                                    class="form-control inputfield-details" readonly autocomplete="off">
                                            </td>
                                            <td>
                                                <div class="input-group mb-3">
                                                    <input type="number" name="discount[]" id="discount_0"
                                                        class="form-control" step="0.01" placeholder="Descuento"
                                                        readonly autocomplete="off">
                                                    <div id="suggestions_producto_0" class="suggestions"></div>
                                                    <div class="input-group-append">
                                                        <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                                            <div class="sb-nav-link-icon"></div><i
                                                                class="aplicar_descuento fas fa-plus fa-lg"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><input type="number" name="total[]" id="total_0" placeholder="Total"
                                                    class="form-control total inputfield-details" step="0.01" readonly
                                                    autocomplete="off"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr class="line_table" />
                        <div class="form-group row">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <button class="btn btn-secondary ml-3 bill-bottom-add" id="addRows" type="button"
                                    data-toggle="tooltip" data-placement="top" title="Agregar filas en la factura">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-plus fa-lg"></i> Agregar
                                </button>
                                <button class="btn btn-secondary delete bill-bottom-remove" id="removeRows"
                                    type="button" data-toggle="tooltip" data-placement="top"
                                    title="Remover filas en la factura">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-minus fa-lg"></i> Quitar
                                </button>
                                <button class="btn btn-secondary bill-bottom-remove" id="addQuotetoBill" type="button"
                                    data-toggle="tooltip" data-placement="top" title="Convertir Cotizacion en Factura">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-file-invoice-dollar fa-lg"></i>
                                    Convertir
                                </button>
                                <button class="btn btn-secondary bill-bottom-remove" id="addPayCustomers" type="button"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Cobrar Cuentas por Pagar Clientes">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-hand-holding-usd fa-lg"></i>
                                    CxC
                                </button>
                                <button class="btn btn-secondary bill-bottom-remove" id="addDraft" type="button"
                                    data-toggle="tooltip" data-placement="top" title="Facturas Pendientes">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-file-invoice fa-lg"></i> Pendientes
                                </button>
                                <button class="btn btn-secondary bill-bottom-remove" id="BillReports" type="button"
                                    data-toggle="tooltip" data-placement="top" title="Facturas Guardadas">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-file-invoice fa-lg"></i> Facturas
                                </button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-row col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="col-sm-12 col-md-12">
                                    <h3>Notas: </h3>
                                    <div class="form-group">
                                        <textarea class="form-control txt" rows="6" name="notesBill" id="notesBill"
                                            placeholder="Notas" maxlength="2000"></textarea>
                                        <p id="charNum_notasQuote">2000 Caracteres</p>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group row">
                                        <div class="card-body">
                                            <div class="form-group mx-sm-3 mb-1">
                                                <div class="input-group">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">
                                                            <div class="sb-nav-link-icon"></div>Fecha Cambio Dolar
                                                        </span>
                                                    </div>
                                                    <input type="date" class="form-control" id="fecha_dolar"
                                                        name="fecha_dolar" value="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-4" style="display: none;">
                                    <div class="row">
                                        <div class="col-sm-3 form-inline">
                                            <label>Importe:</label>
                                        </div>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-append mb-1">
                                                    <span class="input-group-text">
                                                        <div class="sb-nav-link-icon"></div>L</i>
                                                    </span>
                                                </div>
                                                <input value="" type="number" class="form-control"
                                                    name="subTotalImporte" id="subTotalImporte" readonly
                                                    placeholder="Importe">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 form-inline">
                                            <label>Descuento:</label>
                                        </div>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-append mb-1">
                                                    <span class="input-group-text">
                                                        <div class="sb-nav-link-icon"></div>L</i>
                                                    </span>
                                                </div>
                                                <input value="" type="number" class="form-control" name="taxDescuento"
                                                    id="taxDescuento" readonly placeholder="Descuento">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 form-inline">
                                            <label>Subtotal:</label>
                                        </div>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <div class="input-group-append mb-1">
                                                    <span class="input-group-text">
                                                        <div class="sb-nav-link-icon"></div>L</i>
                                                    </span>
                                                </div>
                                                <input value="" type="number" class="form-control" name="subTotal"
                                                    id="subTotal" readonly placeholder="Subtotal">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 form-inline">
                                            <label>ISV:</label>
                                        </div>
                                        <div class="col-sm-9">
                                            <div class="input-group mb-1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <div class="sb-nav-link-icon"></div>L</i>
                                                    </span>
                                                </div>
                                                <input value="" type="number" class="form-control" name="taxAmount"
                                                    id="taxAmount" readonly placeholder="Impuesto">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3 form-inline">
                                            <label>Total:</label>
                                        </div>
                                        <div class="col-sm-9">
                                            <div class="input-group mb-1">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <div class="sb-nav-link-icon"></div>L</i>
                                                    </span>
                                                </div>
                                                <input value="" type="number" class="form-control" name="totalAftertax"
                                                    id="totalAftertax" readonly placeholder="Total">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="card-footer small text-muted">
                <?php
                    require_once './core/mainModel.php';

                    $insMainModel = new mainModel();
                    $entidad = 'facturas';

                    if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                        $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                        $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                        $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                        echo 'Última Actualización ' . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                    } else {
                        echo 'No se encontraron registros ';
                    }
                ?>
            </div>
        </div>
    </div>
</body>

<?php
require_once './core/mainModel.php';

$insMainModel = new mainModel();
$insMainModel->guardar_historial_accesos('Ingreso al modulo Facturas');
?>