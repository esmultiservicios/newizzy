<!-- =========================================================
     IZZY | REPORTE DE VENTAS - MODALES SIN TABLAS
     ========================================================= -->

<div class="modal fade rv-modal" id="ModalDetalleVentas" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header rv-modal-header">
        <div>
          <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Detalle de Ventas</h4>
          <small>Detalle por producto, factura, cliente y vendedor.</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        <form class="form-horizontal FormularioAjax" id="FormDetalleVentas" action="" method="POST" data-form="" enctype="multipart/form-data">
          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div>
                <h5><i class="fas fa-filter mr-1"></i> Filtros</h5>
                <small>Refine el detalle de ventas.</small>
              </div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvDetalleFiltrosBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvDetalleFiltrosBody">
              <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                  <label class="small mb-1" for="DetallesFechai">Fecha Inicio</label>
                  <input type="date" id="DetallesFechai" name="DetallesFechai" value="<?php
                    $fecha = date('Y-m-d');
                    $año = date('Y', strtotime($fecha));
                    $mes = date('m', strtotime($fecha));
                    $dia1 = date('d', mktime(0,0,0,$mes,1,$año));
                    echo date('Y-m-d', strtotime($año.'-'.$mes.'-'.$dia1));
                  ?>" class="form-control">
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <label class="small mb-1" for="DetallesFechaf">Fecha Fin</label>
                  <input type="date" id="DetallesFechaf" name="DetallesFechaf" value="<?php echo date('Y-m-d');?>" class="form-control">
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <label class="small mb-1" for="DetallesProductos">Productos</label>
                  <select class="form-control selectpicker" id="DetallesProductos" name="DetallesProductos" data-size="7" data-live-search="true" title="Productos"></select>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <label class="small mb-1" for="DetalleVendedores">Vendedores</label>
                  <select class="form-control selectpicker" id="DetalleVendedores" name="DetalleVendedores" data-size="7" data-live-search="true" title="Vendedores"></select>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</button>
              </div>
            </div>
          </section>

          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div><h5><i class="fas fa-chart-pie mr-1"></i> KPIs</h5><small>Totales del detalle filtrado.</small></div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvDetalleKpisBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvDetalleKpisBody">
              <div class="rv-kpi-grid rv-kpi-grid-5">
                <div class="rv-kpi"><span>Precio</span><strong id="rvDetKpiPrecio">L. 0.00</strong></div>
                <div class="rv-kpi"><span>Cantidad</span><strong id="rvDetKpiCantidad">0</strong></div>
                <div class="rv-kpi"><span>ISV</span><strong id="rvDetKpiIsv">L. 0.00</strong></div>
                <div class="rv-kpi"><span>Descuento</span><strong id="rvDetKpiDescuento">L. 0.00</strong></div>
                <div class="rv-kpi rv-kpi-primary"><span>Total</span><strong id="rvDetKpiTotal">L. 0.00</strong></div>
              </div>
            </div>
          </section>

          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div><h5><i class="fas fa-list mr-1"></i> Listado</h5><small>Detalle de productos vendidos.</small></div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvDetalleListadoBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvDetalleListadoBody">
              <div class="rv-list-toolbar">
                <div class="rv-toolbar-left">
                  <button type="button" id="rvDetActualizar" class="btn btn-info table_actualizar ocultar"><i class="fas fa-sync-alt"></i> Actualizar</button>
                  <button type="button" id="rvDetExcel" class="btn btn-success table_reportes ocultar"><i class="fas fa-file-excel"></i> Excel</button>
                  <button type="button" id="rvDetPdf" class="btn btn-danger table_reportes ocultar"><i class="fas fa-file-pdf"></i> PDF</button>
                </div>
                <div class="rv-toolbar-right">
                  <label class="rv-page-size">Mostrar <select id="rvDetPageSize" class="form-control form-control-sm"><option>10</option><option>25</option><option>50</option><option>100</option></select> registros</label>
                  <div class="rv-view-switch"><button type="button" class="rv-view-btn active" data-rv-detail-view="detalle"><i class="fas fa-list"></i> Detalle</button><button type="button" class="rv-view-btn" data-rv-detail-view="miniatura"><i class="fas fa-th-large"></i> Miniatura</button></div>
                  <div class="rv-search-wrap"><i class="fas fa-search"></i><input type="search" id="rvDetSearch" class="form-control form-control-sm" placeholder="Buscar..."><button type="button" id="rvDetSearchClear"><i class="fas fa-times"></i></button></div>
                </div>
              </div>
              <div id="rvDetalleListado" class="rv-list"></div>
              <div id="rvDetalleTotales" class="rv-total-row"></div>
              <div class="rv-list-footer"><span id="rvDetInfo">0 registros</span><div id="rvDetPagination" class="rv-pagination"></div></div>
            </div>
          </section>
          <div class="RespuestaAjax"></div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade rv-modal" id="ModalPagosCliente" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header rv-modal-header">
        <div>
          <h4 class="modal-title"><i class="fas fa-receipt mr-2"></i>Pagos del Cliente</h4>
          <small>Pagos aplicados a facturas dentro del período seleccionado.</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        <form class="form-horizontal FormularioAjax" id="FormPagosCliente" action="" method="POST" data-form="" enctype="multipart/form-data">
          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div><h5><i class="fas fa-filter mr-1"></i> Filtros</h5><small>Seleccione fechas y cliente.</small></div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvPagosFiltrosBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvPagosFiltrosBody">
              <div class="row">
                <div class="col-md-3 col-sm-6 mb-3"><label class="small mb-1">Fecha Inicio</label><input type="date" id="PagosFechai" name="PagosFechai" class="form-control"></div>
                <div class="col-md-3 col-sm-6 mb-3"><label class="small mb-1">Fecha Fin</label><input type="date" id="PagosFechaf" name="PagosFechaf" class="form-control"></div>
                <div class="col-md-6 col-sm-12 mb-3"><label class="small mb-1">Cliente</label><select class="form-control selectpicker" id="ClientePagos" name="ClientePagos" data-size="7" data-live-search="true" title="Seleccione un cliente"></select></div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" id="btnFiltrarPagosCliente" class="btn btn-primary mr-2"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                <button type="button" id="btnLimpiarPagosCliente" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</button>
              </div>
            </div>
          </section>

          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div><h5><i class="fas fa-chart-pie mr-1"></i> KPIs</h5><small>Totales de pagos filtrados.</small></div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvPagosKpisBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvPagosKpisBody">
              <div class="rv-kpi-grid rv-kpi-grid-5">
                <div class="rv-kpi"><span>Total Facturas</span><strong id="rvPagKpiFactura">L. 0.00</strong></div>
                <div class="rv-kpi rv-kpi-primary"><span>Aplicado</span><strong id="rvPagKpiAplicado">L. 0.00</strong></div>
                <div class="rv-kpi"><span>Efectivo</span><strong id="rvPagKpiEfectivo">L. 0.00</strong></div>
                <div class="rv-kpi"><span>Tarjeta</span><strong id="rvPagKpiTarjeta">L. 0.00</strong></div>
                <div class="rv-kpi"><span>Cambio</span><strong id="rvPagKpiCambio">L. 0.00</strong></div>
              </div>
            </div>
          </section>

          <section class="rv-section-card rv-inner-section">
            <div class="rv-section-header">
              <div><h5><i class="fas fa-list mr-1"></i> Listado</h5><small>Movimientos de pago encontrados.</small></div>
              <button type="button" class="btn rv-toggle-section" data-target="#rvPagosListadoBody"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>
            </div>
            <div class="rv-section-body" id="rvPagosListadoBody">
              <div class="rv-list-toolbar">
                <div class="rv-toolbar-left">
                  <button type="button" id="rvPagActualizar" class="btn btn-info table_actualizar ocultar"><i class="fas fa-sync-alt"></i> Actualizar</button>
                  <button type="button" id="rvPagExcel" class="btn btn-success table_reportes ocultar"><i class="fas fa-file-excel"></i> Excel</button>
                  <button type="button" id="rvPagPdf" class="btn btn-danger table_reportes ocultar"><i class="fas fa-file-pdf"></i> PDF</button>
                </div>
                <div class="rv-toolbar-right">
                  <label class="rv-page-size">Mostrar <select id="rvPagPageSize" class="form-control form-control-sm"><option>10</option><option>25</option><option>50</option><option>100</option></select> registros</label>
                  <div class="rv-view-switch"><button type="button" class="rv-view-btn active" data-rv-pay-view="detalle"><i class="fas fa-list"></i> Detalle</button><button type="button" class="rv-view-btn" data-rv-pay-view="miniatura"><i class="fas fa-th-large"></i> Miniatura</button></div>
                  <div class="rv-search-wrap"><i class="fas fa-search"></i><input type="search" id="rvPagSearch" class="form-control form-control-sm" placeholder="Buscar..."><button type="button" id="rvPagSearchClear"><i class="fas fa-times"></i></button></div>
                </div>
              </div>
              <div id="rvPagosListado" class="rv-list"></div>
              <div id="rvPagosTotales" class="rv-total-row"></div>
              <div class="rv-list-footer"><span id="rvPagInfo">0 registros</span><div id="rvPagPagination" class="rv-pagination"></div></div>
            </div>
          </section>

          <div class="RespuestaAjax"></div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button>
      </div>
    </div>
  </div>
</div>
