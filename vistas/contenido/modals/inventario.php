<!--INICIO MODAL MOVIMIENTO DE PRODUCTOS-->
<div class="modal fade" id="modal_movimientos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Movimiento de Productos</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formMovimientos" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="movimientos_id" name="movimientos_id" class="form-control" />
                                <input type="text" id="proceso_movimientos" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Operación (Entrada o Salida) -->
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label>Tipo de Operación <span class="priority">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="movimiento_operacion" id="entrada" value="entrada" required>
                                <label class="form-check-label" for="entrada">Entrada</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="movimiento_operacion" id="salida" value="salida" required>
                                <label class="form-check-label" for="salida">Salida</label>
                            </div>
                        </div>
                    </div>

                    <!-- Escaneo de Producto (Opcional) -->
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="produto_barcode">Producto</label>
                            <input type="text" id="produto_barcode" name="produto_barcode" class="form-control" data-toggle="tooltip" data-placement="top"
                                title="Escanea el código de barras del producto para autocompletar los campos. También puedes ingresar manualmente seleccionando el tipo y nombre del producto.">
                        </div>

                        <!-- Tipo y Nombre del Producto -->
                        <div class="col-md-3 mb-3">
                            <label for="movimientos_tipo_producto_id">Tipo Producto <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="movimientos_tipo_producto_id" name="movimientos_tipo_producto_id" class="selectpicker" data-width="100%" data-live-search="true" title="Tipo Producto" required>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="movimiento_producto">Nombre Producto <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="movimiento_producto" name="movimiento_producto" class="selectpicker" data-width="100%" data-live-search="true" title="Producto" required data-size="10">
                                </select>
                            </div>
                        </div> 

                        <!-- Cliente (Solo si es Salida) -->
                        <div class="col-md-3 mb-3">
                            <label for="cliente_movimientos">Cliente</label>
                            <div class="input-group mb-3">
                                <select id="cliente_movimientos" name="cliente_movimientos" class="selectpicker" data-width="100%" data-live-search="true" title="Cliente" data-size="5">
                                </select>
                            </div>
                        </div>                                               
                    </div>            

                    <!-- Lote, Cantidad, Bodega y Fechas -->
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="movimiento_lote">Lote</label>
                            <select id="movimiento_lote" name="movimiento_lote" class="selectpicker" data-width="100%" data-live-search="true" title="Seleccionar Lote">
                            </select>
                        </div>                        
                        <div class="col-md-3 mb-3">
                            <label for="movimiento_cantidad">Cantidad <span class="priority">*</span></label>
                            <input type="number" required id="movimiento_cantidad" name="movimiento_cantidad" class="form-control" step="0.01" >
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="almacen_modal">Bodega <span class="priority">*</span></label>
                            <div class="input-group mb-3">
                                <select id="almacen_modal" name="almacen_modal" class="selectpicker" data-width="100%" data-live-search="true" title="Bodega" required >
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="movimiento_fecha_vencimiento">Fecha de Vencimiento</label>
                            <div class="input-group mb-3">
                                <input type="date" id="movimiento_fecha_vencimiento" name="movimiento_fecha_vencimiento" class="form-control" >
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

                    <div class="RespuestaAjax"></div>
                </form>

                <div class="modal-footer">
                    <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="modal_movimientos" form="formMovimientos">
                        <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL MOVIMIENTO DE PRODUCTOS-->
