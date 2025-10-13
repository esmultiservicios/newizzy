<div class="modal fade" id="ModalDetalleVentas">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <!-- Header igual que Clientes -->
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Detalle de Ventas</h4>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- OJO: se elimina el <div class="container"></div> vacío -->

      <div class="modal-body">
        <form class="form-horizontal FormularioAjax" id="FormDetalleVentas" action="" method="POST" data-form="" enctype="multipart/form-data">

          <!-- Filtros -->
          <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="DetallesFechai">Fecha Inicio</label>
                <input type="date" id="DetallesFechai" name="DetallesFechai" value="<?php 
                  $fecha = date('Y-m-d');
                  $año = date('Y', strtotime($fecha));
                  $mes = date('m', strtotime($fecha));
                  $dia1 = date('d', mktime(0,0,0,$mes,1,$año));
                  $fecha_inicial = date('Y-m-d', strtotime($año.'-'.$mes.'-'.$dia1));
                  echo $fecha_inicial;
                ?>" class="form-control">
              </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="DetallesFechaf">Fecha Fin</label>
                <input type="date" id="DetallesFechaf" name="DetallesFechaf" value="<?php echo date('Y-m-d');?>" class="form-control">
              </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="DetallesProductos">Productos</label>
                <select class="form-control selectpicker" id="DetallesProductos" name="DetallesProductos"
                        data-size="7" data-live-search="true" title="Productos"></select>
              </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="DetalleVendedores">Vendedores</label>
                <select class="form-control selectpicker" id="DetalleVendedores" name="DetalleVendedores"
                        data-size="7" data-live-search="true" title="Vendedores"></select>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="row">
            <div class="col-md-12 d-flex justify-content-end mb-3">
              <button type="submit" class="btn btn-primary mr-2">
                <i class="fas fa-filter fa-lg mr-1"></i> Filtrar
              </button>
              <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table id="DatatableDetalleVentas"
                   class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Producto</th>
                  <th>Factura</th>
                  <th>Cliente</th>
                  <th>Precio</th>
                  <th>Cantidad</th>
                  <th>ISV</th>
                  <th>Descuento</th>
                  <th>Total</th>
                  <th>Vendedor</th>
                </tr>
              </thead>

              <!-- IMPORTANTE: ajustar colspan a 3 para coincidir con 9 columnas -->
              <tfoot class="bg-secondary">
                <tr>
                  <th colspan="3">Totales:</th>
                  <th id="total-precio"></th>
                  <th id="total-cantidad"></th>
                  <th id="total-isv"></th>
                  <th id="total-descuento"></th>
                  <th id="total-total"></th>
                  <th></th> <!-- Vendedor -->
                </tr>
              </tfoot>
            </table>
          </div>

          <div class="RespuestaAjax"></div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal">
          <i class="fas fa-times fa-lg mr-1"></i> Cancelar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Pagos por Cliente -->
<div class="modal fade" id="ModalPagosCliente">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title"><i class="fas fa-receipt mr-2"></i>Pagos del Cliente</h4>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form class="form-horizontal FormularioAjax" id="FormPagosCliente" action="" method="POST" data-form="" enctype="multipart/form-data">

          <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="PagosFechai">Fecha Inicio</label>
                <input type="date" id="PagosFechai" name="PagosFechai" class="form-control">
              </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="PagosFechaf">Fecha Fin</label>
                <input type="date" id="PagosFechaf" name="PagosFechaf" class="form-control">
              </div>
            </div>

            <div class="col-md-6 col-sm-12 mb-3">
              <div class="form-group">
                <label class="small mb-1" for="ClientePagos">Cliente</label>
                <select class="form-control selectpicker" id="ClientePagos" name="ClientePagos"
                        data-size="7" data-live-search="true" title="Seleccione un cliente"></select>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-start align-items-center mb-3">
            <button type="button" id="btnFiltrarPagosCliente" class="btn btn-primary mr-2">
              <i class="fas fa-filter fa-lg mr-1"></i> Filtrar
            </button>
            <button type="button" id="btnLimpiarPagosCliente" class="btn btn-secondary">
              <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
            </button>
          </div>

          <div class="table-responsive">
            <table id="DataTablePagosCliente" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
              <thead>
                <tr>
                  <th>Acción</th>
                  <th>Fecha Pago</th>
                  <th>Factura</th>
                  <th>Fecha Factura</th>
                  <th>Total Factura</th>
                  <th>Aplicado</th>
                  <th>Efectivo</th>
                  <th>Tarjeta</th>
                  <th>Cambio</th>
                  <th>Método</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th>Usuario</th>
                </tr>
              </thead>
              <tfoot class="bg-secondary">
                <tr>
                  <td colspan='1'>Total</td>
                  <td colspan="3"></td>
                  <td id="pg_total_factura" class="text-right"></td>
                  <td id="pg_aplicado" class="text-right"></td>
                  <td id="pg_efectivo" class="text-right"></td>
                  <td id="pg_tarjeta" class="text-right"></td>
                  <td id="pg_cambio" class="text-right"></td>
                  <td colspan="4"></td>
                </tr>
              </tfoot>
              <tbody></tbody>
            </table>
          </div>

          <div class="RespuestaAjax"></div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal">
          <i class="fas fa-times fa-lg mr-1"></i> Cancelar
        </button>
      </div>
    </div>
  </div>
</div>