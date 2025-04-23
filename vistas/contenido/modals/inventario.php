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
                    <!-- Sección Información Básica -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>
                        </div>
						
						<input type="hidden" id="movimientos_id" name="movimientos_id" class="form-control">
						
                        <div class="card-body">                           
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label><i class="fas fa-random mr-1"></i>Tipo de Operación <span class="priority">*</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="movimiento_operacion" id="entrada" value="entrada" required>
                                        <label class="form-check-label" for="entrada"><i class="fas fa-sign-in-alt mr-1"></i>Entrada</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="movimiento_operacion" id="salida" value="salida" required>
                                        <label class="form-check-label" for="salida"><i class="fas fa-sign-out-alt mr-1"></i>Salida</label>
                                    </div>
                                    <small class="form-text text-muted">Seleccione el tipo de movimiento</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección Producto -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-boxes mr-2"></i>Información del Producto</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="produto_barcode"><i class="fas fa-barcode mr-1"></i>Producto</label>
                                    <input type="text" id="produto_barcode" name="produto_barcode" class="form-control" 
                                        title="Escanea el código de barras del producto para autocompletar">
                                    <small class="form-text text-muted">Escanee o busque el producto</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="movimientos_tipo_producto_id"><i class="fas fa-cubes mr-1"></i>Tipo Producto <span class="priority">*</span></label>
                                    <select id="movimientos_tipo_producto_id" name="movimientos_tipo_producto_id" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione tipo" required>
                                    </select>
                                    <small class="form-text text-muted">Categoría del producto</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="movimiento_producto"><i class="fas fa-box-open mr-1"></i>Nombre Producto <span class="priority">*</span></label>
                                    <select id="movimiento_producto" name="movimiento_producto" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione producto" required>
                                    </select>
                                    <small class="form-text text-muted">Producto específico</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="cliente_movimientos"><i class="fas fa-user-tie mr-1"></i>Cliente</label>
                                    <select id="cliente_movimientos" name="cliente_movimientos" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione cliente">
                                    </select>
                                    <small class="form-text text-muted">Requerido para salidas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección Detalles Movimiento -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Detalles del Movimiento</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="movimiento_lote"><i class="fas fa-tags mr-1"></i>Lote</label>
                                    <select id="movimiento_lote" name="movimiento_lote" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione lote">
                                    </select>
                                    <small class="form-text text-muted">Número de lote del producto</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="movimiento_cantidad"><i class="fas fa-sort-numeric-up mr-1"></i>Cantidad <span class="priority">*</span></label>
                                    <input type="number" required id="movimiento_cantidad" name="movimiento_cantidad" 
                                        class="form-control" step="0.01">
                                    <small class="form-text text-muted">Cantidad a mover</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="almacen_modal"><i class="fas fa-warehouse mr-1"></i>Bodega <span class="priority">*</span></label>
                                    <select id="almacen_modal" name="almacen_modal" 
                                        class="selectpicker form-control" data-live-search="true" title="Seleccione bodega" required>
                                    </select>
                                    <small class="form-text text-muted">Ubicación destino/origen</small>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="movimiento_fecha_vencimiento"><i class="fas fa-calendar-times mr-1"></i>Fecha Vencimiento</label>
                                    <input type="date" id="movimiento_fecha_vencimiento" name="movimiento_fecha_vencimiento" 
                                        class="form-control">
                                    <small class="form-text text-muted">Fecha de caducidad del producto</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="movimiento_comentario"><i class="fas fa-comment mr-1"></i>Comentario</label>
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
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" id="modal_movimientos" form="formMovimientos">
                    <i class="fas fa-save mr-1"></i> Registrar Movimiento
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL MOVIMIENTO DE PRODUCTOS-->