<!--INICIO MODAL MOVIMIENTO DE PRODUCTOS-->
<div class="modal fade" id="modal_movimientos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-exchange-alt mr-2"></i>Movimiento de Productos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form class="FormularioAjax" id="formMovimientos" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>

                        <input type="hidden" id="movimientos_id" name="movimientos_id" class="form-control">
                        <input type="hidden" id="proceso_movimientos" name="proceso_movimientos" class="form-control">

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label>
                                        <i class="fas fa-random mr-1"></i>Tipo de Operación <span class="priority">*</span>
                                    </label>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="movimiento_operacion" id="entrada" value="entrada" required>
                                        <label class="form-check-label" for="entrada">
                                            <i class="fas fa-sign-in-alt mr-1"></i>Entrada
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="movimiento_operacion" id="salida" value="salida" required>
                                        <label class="form-check-label" for="salida">
                                            <i class="fas fa-sign-out-alt mr-1"></i>Salida
                                        </label>
                                    </div>

                                    <small class="form-text text-muted">
                                        La última operación seleccionada se recordará automáticamente.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-boxes mr-2"></i>Información del Producto</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="produto_barcode">
                                        <i class="fas fa-barcode mr-1"></i>Producto
                                    </label>

                                    <div class="input-group">
                                        <input type="text" id="produto_barcode" name="produto_barcode" class="form-control"
                                               title="Escanee el código de barras del producto para autocompletar">

                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="btnBuscarProductoMovimiento" title="Buscar producto">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <small class="form-text text-muted">Escanee o busque el producto</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="movimientos_tipo_producto_id">
                                        <i class="fas fa-cubes mr-1"></i>Tipo Producto <span class="priority">*</span>
                                    </label>
                                    <select id="movimientos_tipo_producto_id" name="movimientos_tipo_producto_id"
                                            class="selectpicker form-control" data-live-search="true" title="Seleccione tipo" required>
                                    </select>
                                    <small class="form-text text-muted">Categoría del producto</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="movimiento_producto">
                                        <i class="fas fa-box-open mr-1"></i>Nombre Producto <span class="priority">*</span>
                                    </label>
                                    <select id="movimiento_producto" name="movimiento_producto"
                                            class="selectpicker form-control" data-live-search="true" title="Seleccione producto" required>
                                    </select>
                                    <small class="form-text text-muted">Producto específico</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="cliente_movimientos">
                                        <i class="fas fa-user-tie mr-1"></i>Cliente
                                    </label>
                                    <select id="cliente_movimientos" name="cliente_movimientos"
                                            class="selectpicker form-control" data-live-search="true" title="Seleccione cliente">
                                    </select>
                                    <small class="form-text text-muted">Requerido para salidas</small>
                                </div>
                            </div>                            

                            <div class="form-row">
                                <div class="col-md-12">
                                    <div id="saldoProductoMovimientoCard" class="saldo-producto-card saldo-producto-card-empty">
                                        <div class="saldo-producto-icon">
                                            <i id="saldoProductoMovimientoIcon" class="fas fa-box"></i>
                                        </div>

                                        <div class="saldo-producto-content">
                                            <span class="saldo-producto-label">Saldo disponible del producto</span>

                                            <div class="saldo-producto-main">
                                                <strong id="saldoProductoMovimientoValor">Seleccione o escanee un producto</strong>
                                                <span id="saldoProductoMovimientoEstado" class="saldo-producto-badge">Sin producto</span>
                                            </div>

                                            <small id="saldoProductoMovimientoDetalle">
                                                El saldo se mostrará según el producto, bodega y lote seleccionado.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                    </div>

                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Detalles del Movimiento</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="movimiento_lote">
                                        <i class="fas fa-tags mr-1"></i>Lote
                                    </label>
                                    <select id="movimiento_lote" name="movimiento_lote"
                                            class="selectpicker form-control" data-live-search="true" title="Seleccione lote">
                                    </select>
                                    <small class="form-text text-muted">Número de lote del producto</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="movimiento_cantidad">
                                        <i class="fas fa-sort-numeric-up mr-1"></i>Cantidad <span class="priority">*</span>
                                    </label>
                                    <input type="number" required id="movimiento_cantidad" name="movimiento_cantidad"
                                           class="form-control" step="0.01">
                                    <small class="form-text text-muted">Presione Enter para registrar</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="almacen_modal">
                                        <i class="fas fa-warehouse mr-1"></i>Bodega <span class="priority">*</span>
                                    </label>
                                    <select id="almacen_modal" name="almacen_modal"
                                            class="selectpicker form-control" data-live-search="true" title="Seleccione bodega" required>
                                    </select>
                                    <small class="form-text text-muted">Por defecto: Almacén Principal</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="movimiento_fecha_vencimiento">
                                        <i class="fas fa-calendar-times mr-1"></i>Fecha Vencimiento
                                    </label>
                                    <input type="date" id="movimiento_fecha_vencimiento" name="movimiento_fecha_vencimiento"
                                           class="form-control">
                                    <small class="form-text text-muted">Fecha de caducidad del producto</small>
                                </div>
                            </div>                            

                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="movimiento_comentario">
                                        <i class="fas fa-comment mr-1"></i>Comentario
                                    </label>
                                    <textarea id="movimiento_comentario" name="movimiento_comentario"
                                              class="form-control" rows="3" maxlength="254"></textarea>
                                    <small class="form-text text-muted">Observaciones sobre el movimiento</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>

            <div class="modal-footer modal-footer-movimiento">

                <div id="movimientoOperacionInfo" class="movimiento-operacion-info movimiento-operacion-entrada">
                    <div class="movimiento-operacion-icon">
                        <i id="movimientoOperacionIcon" class="fas fa-sign-in-alt"></i>
                    </div>

                    <div class="movimiento-operacion-texto">
                        <span class="movimiento-operacion-label">Operación actual</span>
                        <strong id="movimientoOperacionTitulo">Entrada de producto</strong>
                        <small id="movimientoOperacionDescripcion">
                            Se registrará una entrada al inventario seleccionado.
                        </small>
                    </div>
                </div>

                <div class="movimiento-footer-botones">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                    </button>

                    <button class="btn btn-success" type="button" id="btnRegistrarMovimiento">
                        <i class="fas fa-save fa-lg mr-1"></i> Registrar Movimiento
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL MOVIMIENTO DE PRODUCTOS-->

<!--INICIO MODAL BUSQUEDA GENERAL DE PRODUCTOS PARA MOVIMIENTOS-->
<div class="modal fade" id="modal_buscar_productos_movimientos_general" tabindex="-1" role="dialog" aria-labelledby="modal_buscar_productos_movimientos_general_label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="modal_buscar_productos_movimientos_general_label">
                    <i class="fas fa-search mr-2"></i> Buscar Productos
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Seleccione un producto para cargarlo automáticamente en el movimiento de inventario.
                </div>

                <div class="overflow-auto">
                    <table id="DatatableProductosBusquedaMovimiento"
                           class="table table-header-gradient table-striped table-condensed table-hover"
                           style="width:100%">
                        <thead>
                            <tr>
                                <th>Seleccione</th>
                                <th>Imagen</th>
                                <th>Bar Code</th>
                                <th>Producto</th>
                                <th>Saldo</th>
                                <th>Medida</th>
                                <th>Tipo Producto</th>
                                <th>Precio Venta</th>
                                <th>Bodega</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA GENERAL DE PRODUCTOS PARA MOVIMIENTOS-->