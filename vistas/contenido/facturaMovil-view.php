<div class="container-fluid factura-movil-container">
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header card-header-movile d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Facturación Móvil</h5>
                    <div id="factura-counter" class="badge-counter">
                        <i class="fas fa-file-invoice"></i>
                        <span class="counter-value" id="factura-disponibles">Cargando...</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Barra de botones fija superior - ahora más pequeños -->
                    <div class="action-buttons-top mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-danger btn-sm" id="cancelar-factura-top">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" id="procesar-factura-top">
                            <i class="fas fa-save"></i> Registrar
                        </button>
                    </div>

                    <form id="factura-form">
                        <!-- Sección de Cliente y Vendedor juntos -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cliente-select" class="form-label">Cliente</label>
                                    <select class="form-control selectpicker" id="cliente-select" data-live-search="true" title="Seleccione un cliente" required>
                                        <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vendedor-select" class="form-label">Vendedor</label>
                                    <select class="form-control selectpicker" id="vendedor-select" data-live-search="true" title="Seleccione un vendedor" required>
                                        <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Factura -->
                        <div class="form-group mb-3">
                            <label class="form-label">Tipo de Factura</label>
                            <div class="d-flex">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="tipo-factura" id="contado" value="1" checked>
                                    <label class="form-check-label" for="contado">Contado</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo-factura" id="credito" value="2">
                                    <label class="form-check-label" for="credito">Crédito</label>
                                </div>
                            </div>
                        </div>

                        <!-- Agregar Productos con escáner -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codigo-barra" class="form-label">Código de Barras</label>
                                    <input type="text" class="form-control" id="codigo-barra" placeholder="Escanear código de barras" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="producto-select" class="form-label">Producto</label>
                                    <select class="form-control selectpicker" id="producto-select" data-live-search="true" title="Seleccione un producto">
                                    <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <label for="cantidad" class="form-label me-2 mb-0">Cantidad:</label>
                                    <div class="cantidad-group">
                                        <button type="button" class="btn btn-cantidad-minus btn-sm p-0" style="width: 28px; height: 28px;">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" min="1" value="1" class="form-control input-cantidad" id="cantidad" style="width: 50px;">
                                        <button type="button" class="btn btn-cantidad-plus btn-sm p-0" style="width: 28px; height: 28px;">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="descuento" class="form-label">Descuento (L.)</label>
                                <input type="number" min="0" value="0" step="0.01" class="form-control" id="descuento" placeholder="0.00">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 mb-3" id="agregar-producto">
                            <i class="fas fa-plus-circle"></i> Agregar Producto
                        </button>

                        <!-- Lista de Productos -->
                        <div class="mb-3">
                            <h6 class="section-title">Productos Agregados</h6>
                            <div id="productos-agregados">
                                <div class="alert alert-info">No hay productos agregados</div>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="total-display">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotal">L. 0.00</span>
                            </div>
                            <div class="total-row">
                                <span>ISV:</span>
                                <span id="isv">L. 0.00</span>
                            </div>
                            <div class="total-row">
                                <span>Descuento:</span>
                                <span id="total-descuento">L. 0.00</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total:</span>
                                <span id="total">L. 0.00</span>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="form-group mb-3 mt-2">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" rows="2" placeholder="Agregue notas adicionales aquí"></textarea>
                        </div>

                        <!-- Barra de botones inferior -->
                        <div class="action-buttons-bottom d-grid gap-2">
                            <button type="button" class="btn btn-danger" id="cancelar-factura-bottom">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-success" id="procesar-factura-bottom">
                                <i class="fas fa-save"></i> Registrar Factura
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pago -->
<div class="modal fade" id="pagoModal" tabindex="-1" aria-labelledby="pagoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pagoModalLabel">Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="pago-form">
                    <input type="hidden" id="factura-id-pago">
                    <div class="form-group mb-3">
                        <label for="monto-pago" class="form-label">Monto a Pagar</label>
                        <input type="text" class="form-control" id="monto-pago" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label for="efectivo-pago" class="form-label">Efectivo Recibido</label>
                        <input type="text" class="form-control" id="efectivo-pago" placeholder="0.00" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="cambio-pago" class="form-label">Cambio</label>
                        <input type="text" class="form-control" id="cambio-pago" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label for="tarjeta-pago" class="form-label">Pago con Tarjeta</label>
                        <input type="text" class="form-control" id="tarjeta-pago" placeholder="0.00">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="registrar-pago">Registrar Pago</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar descuento -->
<div class="modal fade" id="editarDescuentoModal" tabindex="-1" aria-labelledby="editarDescuentoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h4 class="modal-title" id="editarDescuentoModalLabel"><i class="fas fa-percentage"></i> Editar Descuento</h4>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
      <div class="modal-body">
        <form id="editar-descuento-form">
          <input type="hidden" id="producto-index" value="">

          <ul class="nav nav-tabs" id="descuento-tab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="monto-tab" data-bs-toggle="tab" data-bs-target="#monto-tab-pane" type="button" role="tab" aria-controls="monto-tab-pane" aria-selected="true">
                <i class="fas fa-coins"></i> Por monto
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="porcentaje-tab" data-bs-toggle="tab" data-bs-target="#porcentaje-tab-pane" type="button" role="tab" aria-controls="porcentaje-tab-pane" aria-selected="false">
                <i class="fas fa-percentage"></i> Por porcentaje
              </button>
            </li>
          </ul>
          
          <div class="tab-content mt-3" id="myTabContent">
            <div class="tab-pane fade show active" id="monto-tab-pane" role="tabpanel" aria-labelledby="monto-tab" tabindex="0">
              <div class="mb-3">
                <label for="nuevo-descuento-monto" class="form-label">Descuento (L.)</label>
                <div class="input-group">
                  <span class="input-group-text">L.</span>
                  <input type="number" class="form-control" id="nuevo-descuento-monto" min="0" step="0.01" placeholder="0.00">
                </div>
              </div>
            </div>
            
            <div class="tab-pane fade" id="porcentaje-tab-pane" role="tabpanel" aria-labelledby="porcentaje-tab" tabindex="0">
              <div class="mb-3">
                <label for="nuevo-descuento-porcentaje" class="form-label">Descuento (%)</label>
                <div class="input-group">
                  <input type="number" class="form-control" id="nuevo-descuento-porcentaje" min="0" max="100" step="0.01" placeholder="0.00">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="descuento-total" class="form-label">Descuento total</label>
            <input type="text" class="form-control" id="descuento-total" readonly>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times fa-lg mr-1"></i> Cancelar</button>
        <button type="button" class="btn btn-primary" id="guardar-descuento"><i class="far fa-save fa-lg mr-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>