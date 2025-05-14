<div class="container-fluid">
    <!--<ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo SERVERURL; ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Compras</li>
    </ol>-->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-invoice-dollar fa-lg mr-1"></i>
            Compras
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <form class="FormularioAjax" id="purchase-form"
                    action="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/addComprasAjax.php"
                    method="POST" data-form="save" autocomplete="off" enctype="multipart/form-data">
                    <div class="form-group row">
                        <div class="col-sm-6">
                            <button class="btn btn-success" type="submit" id="reg_factura" form="purchase-form"
                                data-toggle="tooltip" data-placement="top" title="Ingresar Factura de Compra">
                                <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                            </button>
                        </div>
                        <label for="inputFecha" class="col-sm-1 col-form-label-md">Fecha <span
                                class="priority">*<span /></label>
                        <div class="col-sm-4">
                            <input type="date" class="form-control" value="<?php echo date('Y-m-d');?>" required
                                id="fechaPurchase" name="fechaPurchase" data-toggle="tooltip" data-placement="top"
                                title="Fecha de Facturación">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputCliente" class="col-sm-1 col-form-label-md">Proveedor <span
                                class="priority">*<span /></label>
                        <div class="col-sm-5">
                            <div class="input-group mb-3">
                                <input type="hidden" class="form-control" placeholder="Proceso" id="proceso_Purchase"
                                    name="proceso_Purchase" readonly>
                                <input type="hidden" class="form-control" placeholder="Compra" id="compras_id"
                                    name="compras_id" readonly>
                                <input type="hidden" class="form-control" placeholder="row" id="pucharse_row"
                                    name="pucharse_row" readonly value="0">
                                <input type="hidden" class="form-control" placeholder="Proveedor" id="proveedores_id"
                                    name="proveedores_id" required>
                                <select id="proveedor" name="proveedor" required class="selectpicker col-12"
                                    title="Proveedor" data-size="7" data-live-search="true">
                                </select>
                            </div>
                        </div>
                        <label for="inputCliente" class="col-sm-1 col-form-label-md">Factura <span
                                class="priority">*<span /></label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" placeholder="Número de Factura de Compra"
                                id="facturaPurchase" name="facturaPurchase" required data-toggle="tooltip"
                                data-placement="top" title="Factura Compra" maxlength="19" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputCliente" class="col-sm-1 col-form-label-md">Usuario <span
                                class="priority">*<span /></label>
                        <div class="col-sm-3">
                            <div class="input-group mb-3">
                                <input type="hidden" class="form-control" placeholder="Usuario" id="colaborador_id"
                                    name="colaborador_id" aria-label="Colaborador" aria-describedby="basic-addon2"
                                    readonly required>
                                <select id="colaborador" name="colaborador" class="selectpicker col-12" title="Usuario"
                                    data-size="7" data-live-search="true">
                                </select>
                            </div>
                        </div>
                        <label for="inputCliente" class="col-sm-1 col-form-label-md" data-toggle="tooltip"
                            data-placement="top"
                            title="Es para definir si la factura de compra será al crédito o al contado">Tipo
                            Factura<span class="priority">*<span /></label>
                        <div class="col-md-2">
                            <label class="switch">
                                <input type="checkbox" id="tipoPurchase" name="tipoPurchase" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_tipoPurchase"></span>
                        </div>
                        <label for="inputCliente" class="col-sm-1 col-form-label-md recordatorio" data-toggle="tooltip"
                            style="display: none;" data-placement="top"
                            title="Selecciona el número de días para establecer un recordatorio mensual. El recordatorio se activará automáticamente a partir del próximo mes después del registro de la factura.">Recordatorio</label>
                        <div class="col-md-3 recordatorio" style="display: none;">
                            <select class="selectpicker" id="recordatorio" name="recordatorio" data-width="100%"
                                data-size="7" data-live-search="true" title="Seleccionar un recordatorio mensual"
                                data-toggle="tooltip">
                            </select>
                        </div>
                    </div>

                    <div class="form-group row table-responsive-xl table table-hover">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <table class="table-header-pro table-footer-pro" id="purchaseItem">
                                <thead class="text-align: center">
                                    <tr>
                                        <th width="2%" scope="col"><input id="checkAllPurchase" class="formcontrol"
                                                type="checkbox"></th>
                                        <th width="18%">Código</th>
                                        <th width="17%">Nombre Producto</th>
                                        <th width="6%">Cantidad</th>
                                        <th width="7%">Almacén</th>
                                        <th width="10%">Vencimiento</th>
                                        <th width="10%">Precio</th>
                                        <th width="10%">ISV</th>
                                        <th width="10%">Descuento</th>
                                        <th width="10%">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input class="itemRowPurchase" type="checkbox"></td>
                                        <td>
                                            <div class="input-group mb-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <button type="button" data-toggle="modal"
                                                            class="btn btn-link buscar_productos_purchase p-0"
                                                            data-toggle="tooltip" data-placement="top"
                                                            title="Búsqueda de Productos" id="icon-search-bar_0">
                                                            <i class="fas fa-search icon-color"
                                                                style="font-size: 0.875rem;"></i>
                                                        </button>
                                                    </div>
                                                    <input type="text" name="bar-code-idPurchase[]"
                                                        id="bar-code-idPurchase_0"
                                                        class="form-control product-bar-codePurchase inputfield-details1"
                                                        placeholder="Código del Producto" autocomplete="off">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group mb-3">
                                                <input type="hidden" name="isvPurchase[]" id="isvPurchase_0"
                                                    class="form-control" placeholder="Producto ISV" autocomplete="off">
                                                <input type="hidden" name="valor_isvPurchase[]" id="valor_isvPurchase_0"
                                                    class="form-control" placeholder="Valor ISV" autocomplete="off">
                                                <input type="hidden" name="productos_idPurchase[]"
                                                    id="productos_idPurchase_0" class="form-control" autocomplete="off">
                                                <input type="text" name="productNamePurchase[]"
                                                    id="productNamePurchase_0" class="form-control" autocomplete="off"
                                                    required>
                                            </div>
                                        </td>
                                        <td><input type="number" name="quantityPurchase[]" id="quantityPurchase_0"
                                                class="buscar_cantidad_purchase form-control" autocomplete="off"
                                                step="0.01"></td>
                                        <td><select id="almacenPurchase_0" name="almacenPurchase[]" class="selectpicker"
                                                title="Almacén" data-live-search="true" required data-size="5">
                                            </select>
                                        </td>
                                        <td>
                                            <input type="hidden" name="medidaPurchase[]" id="medidaPurchase_0" readonly
                                                class="form-control buscar_medida_purchase" autocomplete="off">
                                            <input type="date" name="vencimientoPurchase[]" id="vencimientoPurchase_0"
                                                class="form-control buscar_vencimiento_purchase" autocomplete="off">
                                            <input type="hidden" name="bodegaPurchase[]" id="bodegaPurchase_0" readonly
                                                class="form-control buscar_bodega_purchase" autocomplete="off">
                                        </td>
                                        <td><input type="number" name="pricePurchase[]" id="pricePurchase_0"
                                                class="buscar_price_purchase form-control" autocomplete="off"
                                                step="0.01"></td>
                                        <td><input type="number" name="isvPurchaseWrite[]" id="isvPurchaseWrite_0"
                                                class="form-control" autocomplete="off" step="0.01"></td>
                                        <td><input type="number" name="discountPurchase[]" id="discountPurchase_0"
                                                class="form-control" autocomplete="off" step="0.01"></td>
                                        <td><input type="number" name="totalPurchase[]" id="totalPurchase_0"
                                                class="form-control total" readonly autocomplete="off" step="0.01"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <hr class="line_table" />
                    <div class="form-group row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <button class="btn btn-success ml-3 bill-bottom-add" id="addRowsPurchase" type="button"
                                data-toggle="tooltip" data-placement="top" title="Agregar filas en la factura">
                                <div class="sb-nav-link-icon"></div><i class="fas fa-plus fa-lg"></i> Agregar
                            </button>
                            <button class="btn btn-success delete bill-bottom-remove" id="removeRowsPurchase"
                                type="button" data-toggle="tooltip" data-placement="top"
                                title="Remover filas en la factura">
                                <div class="sb-nav-link-icon"></div><i class="fas fa-minus fa-lg"></i> Quitar
                            </button>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="form-row col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="col-sm-12 col-md-12">
                                <h3>Notas: </h3>
                                <div class="form-group">
                                    <textarea class="form-control txt" rows="6" name="notesPurchase" id="notesPurchase"
                                        placeholder="Notas" maxlength="2000"></textarea>
                                    <p id="charNum_notasPurchase">2000 Caracteres</p>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-4" style="display:none">
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
                                                name="subTotalImportePurchase" id="subTotalImportePurchase" readonly
                                                placeholder="Importe">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-3 form-inline">
                                        <label>Descuento:</label>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="input-group mb-1">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <div class="sb-nav-link-icon"></div>L</i>
                                                </span>
                                            </div>
                                            <input value="" type="number" class="form-control"
                                                name="taxDescuentoPurchase" id="taxDescuentoPurchase" readonly
                                                placeholder="Descuento">
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
                                            <input value="" type="number" class="form-control" name="subTotalPurchase"
                                                id="subTotalPurchase" readonly placeholder="Subtotal">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="display: none;">
                                    <div class="col-sm-3 form-inline">
                                        <label>Porcentaje:</label>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="input-group mb-1">
                                            <input value="15" type="number" class="form-control" name="taxRatePurchase"
                                                id="taxRatePurchase" readonly placeholder="Tasa de Impuestos">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <div class="sb-nav-link-icon"></div>%</i>
                                                </span>
                                            </div>
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
                                            <input value="" type="number" class="form-control" name="taxAmountPurchase"
                                                id="taxAmountPurchase" readonly placeholder="Monto del Impuesto">
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
                                            <input value="" type="number" class="form-control"
                                                name="totalAftertaxPurchase" id="totalAftertaxPurchase" readonly
                                                placeholder="Total">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="display: none;">
                                    <div class="col-sm-3 form-inline">
                                        <label>Cantidad pagada:</label>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="input-group mb-1">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <div class="sb-nav-link-icon"></div>L</i>
                                                </span>
                                            </div>
                                            <input value="" type="number" class="form-control" name="amountPaidPurchase"
                                                id="amountPaidPurchase" readonly placeholder="Cantidad pagada">
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="display: none;">
                                    <div class="col-sm-3 form-inline">
                                        <label>Cantidad debida:</label>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="input-group mb-1">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <div class="sb-nav-link-icon"></div>L</i>
                                                </span>
                                            </div>
                                            <input value="" type="number" class="form-control" name="amountDuePurchase"
                                                id="amountDuePurchase" readonly placeholder="Cantidad debida">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
        </div>
        <div class="card-footer small text-muted">
            <?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "compras";
				
				if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
					$consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
					$fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
					$hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
					echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
				} else {
					echo "No se encontraron registros ";
				}				
			?>
        </div>
    </div>
</div>

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Compras");
?>