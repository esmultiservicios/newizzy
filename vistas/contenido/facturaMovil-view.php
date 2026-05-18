<div class="container-fluid factura-movil-container">
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header card-header-movile d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Facturación Móvil</h5>
                    <div id="factura-counter" class="badge-counter counter-normal">
                        <i class="fas fa-file-invoice"></i>
                        <span class="counter-value" id="factura-disponibles">Cargando...</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Barra de botones fija superior -->
                    <div class="action-buttons-top mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-apertura-caja">
                            <i class="fas fa-lock-open"></i> Aperturar Caja
                        </button>
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
                                    <select class="form-control selectpicker" id="cliente-select" data-live-search="true" title="Seleccione un cliente" data-size="5" required>
                                        <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vendedor-select" class="form-label">Vendedor</label>
                                    <select class="form-control selectpicker" id="vendedor-select" data-live-search="true" title="Seleccione un vendedor" data-size="5" required>
                                        <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Factura -->
                        <div class="form-group mb-3">
                            <label class="form-label">Tipo de Factura</label>
                            <div class="d-flex flex-wrap">
                                <div class="form-check me-3 mr-3">
                                    <input class="form-check-input" type="radio" name="tipo-factura" id="contado" value="1" checked>
                                    <label class="form-check-label" for="contado">Contado</label>
                                </div>
                                <div class="form-check me-3 mr-3">
                                    <input class="form-check-input" type="radio" name="tipo-factura" id="credito" value="2">
                                    <label class="form-check-label" for="credito">Crédito</label>
                                </div>
                            </div>

                            <!-- Checkbox de Proforma: se muestra y se marca por defecto si config.Activar Proforma = 1 -->
                            <div class="form-check mt-2 d-none" id="tipo-proforma-wrapper">
                                <input class="form-check-input" type="checkbox" id="proforma" value="1" disabled>
                                <label class="form-check-label font-weight-bold" for="proforma">
                                    Proforma
                                </label>
                                <small class="text-muted d-block">
                                    Si está marcado, se registrará como proforma y no abrirá modal de pago.
                                </small>
                            </div>

                            <!-- Opciones solo para proforma -->
                            <div class="alert alert-warning mt-2 mb-0 d-none" id="proforma-opciones">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="proforma-bajar-inventario" value="1">
                                    <label class="form-check-label" for="proforma-bajar-inventario">
                                        Rebajar inventario con esta proforma
                                    </label>
                                </div>
                                <small class="d-block mt-1">
                                    Normalmente una proforma no rebaja inventario. Marque esta opción solo si desea descontar existencia.
                                </small>
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
                                    <select class="form-control selectpicker" id="producto-select" data-live-search="true" title="Seleccione un producto" data-size="5">
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
                                <span>ISV 15%:</span>
                                <span id="isv-15">L. 0.00</span>
                            </div>
                            <div class="total-row">
                                <span>ISV 18%:</span>
                                <span id="isv-18">L. 0.00</span>
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

<!-- Modal de Pago (Bootstrap 4.6) -->
<div class="modal fade" id="pagoModal" tabindex="-1" role="dialog" aria-labelledby="pagoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="pagoModalLabel">
          <i class="fas fa-cash-register mr-2"></i> Registrar Pago
        </h5>
        <!-- X de cerrar para BS4 -->
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="opacity: .9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="pago-form">
          <input type="hidden" id="factura-id-pago">

          <div class="form-group mb-3">
            <label for="monto-pago" class="form-label">Monto a Pagar</label>
            <input type="text" class="form-control" id="monto-pago" readonly>
            <small class="text-muted">Total de la factura.</small>
          </div>

          <div class="form-row">
            <div class="col-12 col-sm-4">
              <div class="form-group mb-3">
                <label for="efectivo-pago" class="form-label">Efectivo</label>
                <input type="number" min="0" step="0.01" class="form-control" id="efectivo-pago" placeholder="0.00">
              </div>
            </div>
            <div class="col-12 col-sm-4">
              <div class="form-group mb-3">
                <label for="transferencia-pago" class="form-label">Transferencia</label>
                <input type="number" min="0" step="0.01" class="form-control" id="transferencia-pago" placeholder="0.00">
              </div>
            </div>
            <div class="col-12 col-sm-4">
              <div class="form-group mb-3">
                <label for="tarjeta-pago" class="form-label">Tarjeta</label>
                <input type="number" min="0" step="0.01" class="form-control" id="tarjeta-pago" placeholder="0.00">
              </div>
            </div>
          </div>

          <div class="form-group mb-2">
            <label for="cambio-pago" class="form-label">Cambio</label>
            <input type="text" class="form-control" id="cambio-pago" readonly>
            <small class="text-muted">Se calcula automáticamente con base en Efectivo + Transferencia + Tarjeta.</small>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <div class="d-flex flex-wrap flex-sm-nowrap w-100 justify-content-between">
          <button type="button" class="btn btn-secondary mr-sm-2 mb-2 mb-sm-0" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Cancelar
          </button>
          <button type="button" class="btn btn-primary" id="registrar-pago">
            <i class="fas fa-check mr-1"></i> Registrar Pago
          </button>
        </div>
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
              <button class="nav-link active" id="monto-tab" data-toggle="tab" data-target="#monto-tab-pane" type="button" role="tab" aria-controls="monto-tab-pane" aria-selected="true">
                <i class="fas fa-coins"></i> Por monto
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="porcentaje-tab" data-toggle="tab" data-target="#porcentaje-tab-pane" type="button" role="tab" aria-controls="porcentaje-tab-pane" aria-selected="false">
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

<!-- Modal para editar precio -->
<div class="modal fade" id="editarPrecioModal" tabindex="-1" role="dialog" aria-labelledby="editarPrecioModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title" id="editarPrecioModalLabel">
          <i class="fas fa-dollar-sign"></i> Editar Precio
        </h4>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="editar-precio-form">
          <input type="hidden" id="producto-precio-index" value="">

          <div class="form-group mb-3">
            <label for="nuevo-precio-producto" class="form-label">Nuevo precio de venta</label>
            <div class="input-group">
              <span class="input-group-text">L.</span>
              <input type="number" class="form-control" id="nuevo-precio-producto" min="0.01" step="0.01" placeholder="0.00">
            </div>
            <small class="text-muted">Este cambio aplica solo a la línea actual de la factura/proforma.</small>
          </div>

          <div class="form-group mb-0">
            <label class="form-label">Vista previa</label>

            <div id="precio-total-preview" class="precio-preview-box">
              <div class="precio-preview-item">
                <span>Subtotal</span>
                <strong id="preview-subtotal">L. 0.00</strong>
              </div>

              <div class="precio-preview-item">
                <span>ISV 15%</span>
                <strong id="preview-isv15">L. 0.00</strong>
              </div>

              <div class="precio-preview-item">
                <span>ISV 18%</span>
                <strong id="preview-isv18">L. 0.00</strong>
              </div>

              <div class="precio-preview-item total">
                <span>Total</span>
                <strong id="preview-total">L. 0.00</strong>
              </div>
            </div>
          </div>
          
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fas fa-times fa-lg mr-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary" id="guardar-precio">
          <i class="far fa-save fa-lg mr-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>