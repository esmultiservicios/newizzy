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

<!--INICIO MODAL AJUSTE DE INVENTARIO-->
<div class="modal fade" id="modal_ajuste_inventario" tabindex="-1" role="dialog" aria-labelledby="modal_ajuste_inventario_label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title text-white" id="modal_ajuste_inventario_label">
                    <i class="fas fa-balance-scale mr-2"></i>Ajuste de Inventario por Conteo Físico
                </h4>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="formAjusteInventario" autocomplete="off">
                    <input type="hidden" id="ajuste_saldo_sistema" name="ajuste_saldo_sistema" value="0">
                    <input type="hidden" id="ajuste_diferencia" name="ajuste_diferencia" value="0">
                    <input type="hidden" id="ajuste_tipo" name="ajuste_tipo" value="sin_cambio">

                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-boxes mr-2"></i>Producto a ajustar
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_barcode">
                                        <i class="fas fa-barcode mr-1"></i>Código de barra / producto
                                    </label>

                                    <div class="input-group">
                                        <input type="text" id="ajuste_barcode" name="ajuste_barcode" class="form-control" placeholder="Escanee o escriba el código de barra">

                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="btnBuscarProductoAjuste" title="Buscar producto">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <small class="form-text text-muted">Puede escanear el código o buscar el producto manualmente.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_tipo_producto_id">
                                        <i class="fas fa-cubes mr-1"></i>Tipo Producto
                                    </label>

                                    <select id="ajuste_tipo_producto_id" name="ajuste_tipo_producto_id" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo">
                                    </select>

                                    <small class="form-text text-muted">Categoría del producto.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_producto">
                                        <i class="fas fa-box-open mr-1"></i>Producto <span class="priority">*</span>
                                    </label>

                                    <select id="ajuste_producto" name="ajuste_producto" class="selectpicker form-control" data-live-search="true" title="Seleccione producto" required>
                                    </select>

                                    <small class="form-text text-muted">Producto que será ajustado.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_almacen">
                                        <i class="fas fa-warehouse mr-1"></i>Bodega <span class="priority">*</span>
                                    </label>

                                    <select id="ajuste_almacen" name="ajuste_almacen" class="selectpicker form-control" data-live-search="true" title="Seleccione bodega" required>
                                    </select>

                                    <small class="form-text text-muted">Por defecto: Almacén principal.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_lote">
                                        <i class="fas fa-tags mr-1"></i>Lote
                                    </label>

                                    <select id="ajuste_lote" name="ajuste_lote" class="selectpicker form-control" data-live-search="true" title="Seleccione lote">
                                    </select>

                                    <small class="form-text text-muted">Seleccione lote si el producto maneja lote.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ajuste_fecha_vencimiento">
                                        <i class="fas fa-calendar-times mr-1"></i>Fecha vencimiento
                                    </label>

                                    <input type="date" id="ajuste_fecha_vencimiento" name="ajuste_fecha_vencimiento" class="form-control">

                                    <small class="form-text text-muted">Opcional, aplica si el producto maneja vencimiento.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-clipboard-check mr-2"></i>Conteo físico
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="ajuste_saldo_visible">
                                        <i class="fas fa-database mr-1"></i>Stock actual sistema
                                    </label>

                                    <input type="text" id="ajuste_saldo_visible" class="form-control text-right font-weight-bold" value="0.00" readonly>

                                    <small class="form-text text-muted">Saldo actual registrado.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="ajuste_conteo_fisico">
                                        <i class="fas fa-boxes mr-1"></i>Conteo físico <span class="priority">*</span>
                                    </label>

                                    <input type="number" id="ajuste_conteo_fisico" name="ajuste_conteo_fisico" class="form-control text-right font-weight-bold" step="0.01" min="0" required>

                                    <small class="form-text text-muted">Cantidad real encontrada.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="ajuste_diferencia_visible">
                                        <i class="fas fa-exchange-alt mr-1"></i>Diferencia
                                    </label>

                                    <input type="text" id="ajuste_diferencia_visible" class="form-control text-right font-weight-bold" value="0.00" readonly>

                                    <small class="form-text text-muted">Cantidad que se sumará o restará.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="ajuste_nueva_existencia">
                                        <i class="fas fa-check-circle mr-1"></i>Nueva existencia
                                    </label>

                                    <input type="text" id="ajuste_nueva_existencia" class="form-control text-right font-weight-bold" value="0.00" readonly>

                                    <small class="form-text text-muted">Debe quedar igual al conteo físico.</small>
                                </div>
                            </div>

                            <div class="alert alert-secondary mb-0" id="ajuste_resultado_info">
                                <i class="fas fa-info-circle mr-1"></i>
                                Seleccione un producto y escriba el conteo físico para calcular el ajuste.
                            </div>
                        </div>
                    </div>

                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-comment-alt mr-2"></i>Observación
                            </h5>
                        </div>

                        <div class="card-body">
                            <textarea id="ajuste_comentario" name="ajuste_comentario" class="form-control" rows="3" maxlength="255" placeholder="Ejemplo: Ajuste por conteo físico de inventario"></textarea>
                            <small class="form-text text-muted">Esta observación quedará guardada para auditoría.</small>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>

            <div class="modal-footer">
                <div class="mr-auto">
                    <span class="badge badge-primary p-2">
                        <i class="fas fa-history mr-1"></i>Este ajuste quedará en auditoría
                    </span>
                </div>

                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>

                <button type="button" class="btn btn-success" id="btnRegistrarAjusteInventario">
                    <i class="fas fa-save fa-lg mr-1"></i> Registrar Ajuste
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL AJUSTE DE INVENTARIO-->


<style>
/* INICIO CARDS RESUMEN AUDITORIA INVENTARIO */
#modal_consultar_inventario .auditoria-ajustes-resumen-row {
    margin-left: -8px;
    margin-right: -8px;
}

#modal_consultar_inventario .auditoria-ajustes-resumen-col {
    padding-left: 8px;
    padding-right: 8px;
}

#modal_consultar_inventario .auditoria-resumen-card {
    position: relative;
    overflow: hidden;
    min-height: 118px;
    border: 0;
    border-radius: 16px;
    padding: 18px 18px;
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#modal_consultar_inventario .auditoria-resumen-card::after {
    content: "";
    position: absolute;
    right: -28px;
    top: -28px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.16);
}

#modal_consultar_inventario .auditoria-resumen-card::before {
    content: "";
    position: absolute;
    right: 38px;
    bottom: -42px;
    width: 95px;
    height: 95px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.10);
}

#modal_consultar_inventario .auditoria-resumen-info {
    position: relative;
    z-index: 2;
}

#modal_consultar_inventario .auditoria-resumen-label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    opacity: .92;
    margin-bottom: 8px;
}

#modal_consultar_inventario .auditoria-resumen-value {
    font-size: 32px;
    line-height: 1;
    font-weight: 900;
    color: #ffffff;
    margin: 0;
}

#modal_consultar_inventario .auditoria-resumen-help {
    display: block;
    font-size: 12px;
    margin-top: 8px;
    opacity: .88;
}

#modal_consultar_inventario .auditoria-resumen-icon {
    position: relative;
    z-index: 2;
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.20);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
}

#modal_consultar_inventario .auditoria-resumen-icon i {
    font-size: 24px;
    color: #ffffff;
}

#modal_consultar_inventario .auditoria-card-total {
    background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);
}

#modal_consultar_inventario .auditoria-card-entradas {
    background: linear-gradient(135deg, #16a34a 0%, #0f766e 100%);
}

#modal_consultar_inventario .auditoria-card-sincambio {
    background: linear-gradient(135deg, #64748b 0%, #334155 100%);
}

#modal_consultar_inventario .auditoria-card-balance {
    background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
}

@media (max-width: 991.98px) {
    #modal_consultar_inventario .auditoria-resumen-card {
        min-height: 105px;
    }

    #modal_consultar_inventario .auditoria-resumen-value {
        font-size: 26px;
    }
}
/* FIN CARDS RESUMEN AUDITORIA INVENTARIO */
</style>

<!--INICIO MODAL AUDITORIA DE AJUSTES DE INVENTARIO-->
<div class="modal fade" id="modal_consultar_inventario" tabindex="-1" role="dialog" aria-labelledby="modal_consultar_inventario_label" aria-hidden="true">
    <div class="modal-dialog modal-auditoria-ajustes-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title text-white" id="modal_consultar_inventario_label">
                    <i class="fas fa-clipboard-check mr-2"></i>Auditoría de Ajustes de Inventario
                </h4>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="formConsultaInventario" autocomplete="off">
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-filter mr-2"></i>Filtros de auditoría
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="consulta_fechai">
                                        <i class="fas fa-calendar-alt mr-1"></i>Fecha inicio
                                    </label>
                                    <input type="date" id="consulta_fechai" name="consulta_fechai" class="form-control">
                                    <small class="form-text text-muted">Inicio del rango a consultar.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="consulta_fechaf">
                                        <i class="fas fa-calendar-check mr-1"></i>Fecha fin
                                    </label>
                                    <input type="date" id="consulta_fechaf" name="consulta_fechaf" class="form-control">
                                    <small class="form-text text-muted">Fin del rango a consultar.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="consulta_almacen">
                                        <i class="fas fa-warehouse mr-1"></i>Bodega
                                    </label>
                                    <select id="consulta_almacen" name="consulta_almacen" class="selectpicker form-control" data-live-search="true" title="Seleccione bodega">
                                    </select>
                                    <small class="form-text text-muted">Bodega auditada.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="consulta_tipo_ajuste">
                                        <i class="fas fa-random mr-1"></i>Tipo ajuste
                                    </label>
                                    <select id="consulta_tipo_ajuste" name="consulta_tipo_ajuste" class="selectpicker form-control" title="Seleccione tipo">
                                        <option value="">Todos</option>
                                        <option value="entrada">Entrada</option>
                                        <option value="salida">Salida</option>
                                        <option value="sin_cambio">Sin cambio</option>
                                    </select>
                                    <small class="form-text text-muted">Entrada, salida o sin cambio.</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="consulta_barcode">
                                        <i class="fas fa-barcode mr-1"></i>Código de barra
                                    </label>
                                    <input type="text" id="consulta_barcode" name="consulta_barcode" class="form-control" placeholder="Escanee o escriba el código">
                                    <small class="form-text text-muted">Presione Enter para buscar.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="consulta_tipo_producto_id">
                                        <i class="fas fa-cubes mr-1"></i>Tipo Producto
                                    </label>
                                    <select id="consulta_tipo_producto_id" name="consulta_tipo_producto_id" class="selectpicker form-control" data-live-search="true" title="Seleccione tipo">
                                    </select>
                                    <small class="form-text text-muted">Categoría del producto.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="consulta_producto">
                                        <i class="fas fa-box-open mr-1"></i>Producto
                                    </label>
                                    <select id="consulta_producto" name="consulta_producto" class="selectpicker form-control" data-live-search="true" title="Seleccione producto">
                                    </select>
                                    <small class="form-text text-muted">Producto auditado.</small>
                                </div>

                                <div class="col-md-3 mb-3 d-flex align-items-end justify-content-end">
                                    <button type="button" class="btn btn-secondary mr-2" id="btnLimpiarConsultaInventario">
                                        <i class="fas fa-eraser fa-lg mr-1"></i> Limpiar
                                    </button>

                                    <button type="button" class="btn btn-info" id="btnBuscarConsultaInventario">
                                        <i class="fas fa-search fa-lg mr-1"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row mb-4 auditoria-ajustes-resumen-row">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3 auditoria-ajustes-resumen-col">
                            <div class="auditoria-resumen-card auditoria-card-total">
                                <div class="auditoria-resumen-info">
                                    <span class="auditoria-resumen-label">Ajustes encontrados</span>
                                    <h4 class="auditoria-resumen-value" id="consulta_total_productos">0</h4>
                                    <span class="auditoria-resumen-help">Registros de auditoría</span>
                                </div>
                                <div class="auditoria-resumen-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3 auditoria-ajustes-resumen-col">
                            <div class="auditoria-resumen-card auditoria-card-entradas">
                                <div class="auditoria-resumen-info">
                                    <span class="auditoria-resumen-label">Entradas / Salidas</span>
                                    <h4 class="auditoria-resumen-value" id="consulta_total_saldo">0 / 0</h4>
                                    <span class="auditoria-resumen-help">Movimientos generados</span>
                                </div>
                                <div class="auditoria-resumen-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3 auditoria-ajustes-resumen-col">
                            <div class="auditoria-resumen-card auditoria-card-sincambio">
                                <div class="auditoria-resumen-info">
                                    <span class="auditoria-resumen-label">Sin cambio</span>
                                    <h4 class="auditoria-resumen-value" id="consulta_total_sin_saldo">0</h4>
                                    <span class="auditoria-resumen-help">Conteos sin diferencia</span>
                                </div>
                                <div class="auditoria-resumen-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3 auditoria-ajustes-resumen-col">
                            <div class="auditoria-resumen-card auditoria-card-balance">
                                <div class="auditoria-resumen-info">
                                    <span class="auditoria-resumen-label">Balance diferencia</span>
                                    <h4 class="auditoria-resumen-value" id="consulta_total_balance_ajuste">0.00</h4>
                                    <span class="auditoria-resumen-help">Entradas menos salidas</span>
                                </div>
                                <div class="auditoria-resumen-icon">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Esta consulta muestra únicamente lo guardado en <strong>inventario_ajustes</strong> para auditoría: saldo sistema, conteo físico, diferencia, tipo de ajuste, movimiento relacionado, comentario, usuario y fecha.
                    </div>

                    <div class="auditoria-ajustes-table-wrapper">
                        <table id="dataTablaConsultaInventario" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Ajuste / Fecha</th>
                                    <th>Producto</th>
                                    <th>Bodega / Lote</th>
                                    <th>Stock Sistema</th>
                                    <th>Conteo Físico</th>
                                    <th>Diferencia / Tipo</th>
                                    <th>Usuario / Comentario</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="modal-footer modal-footer-movimiento">
                <div class="mr-auto">
                    <span class="badge badge-info p-2">
                        <i class="fas fa-database mr-1"></i>Consulta basada en inventario_ajustes
                    </span>
                </div>

                <div class="movimiento-footer-botones">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times fa-lg mr-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL AUDITORIA DE AJUSTES DE INVENTARIO-->

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