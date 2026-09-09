<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>IZZY | Reporte de Ventas</title>
  <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/reporte_ventas.css">
</head>
<body>

<script>
  var PLANES_ID = <?php echo isset($_SESSION['planes_id']) ? intval($_SESSION['planes_id']) : 0; ?>;
  var PLAN_ALLOW_MULTI = [3,4,5,7].indexOf(PLANES_ID) !== -1;
  var EMPRESA_ID_ACTIVA = <?php echo isset($_SESSION['empresa_id_sd']) ? intval($_SESSION['empresa_id_sd']) : 0; ?>;

  document.addEventListener('DOMContentLoaded', function(){
    if (PLAN_ALLOW_MULTI) {
      var filaAmbito = document.getElementById('fila-ambito');
      if (filaAmbito) filaAmbito.style.display = '';
      var filaEmpresas = document.getElementById('fila-empresas');
      if (filaEmpresas) filaEmpresas.style.display = 'none';
    }
  });
</script>

<div class="container-fluid rv-page">
  <div class="breadcrumb-container">
    <ol class="breadcrumb-harmony">
      <li class="breadcrumb-item">
        <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
          <i class="fas fa-home breadcrumb-icon"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="breadcrumb-separator">/</li>
      <li class="breadcrumb-item active">
        <i class="fas fa-chart-line breadcrumb-icon"></i> <span>Reporte de Ventas</span>
      </li>
    </ol>
  </div>

  <section class="rv-section-card" id="rvFiltrosSection">
    <div class="rv-section-header">
      <div>
        <h5><i class="fas fa-filter mr-1"></i> Filtros de ventas</h5>
        <small>Refine el reporte por documento, categoría, responsable, empresa y fechas.</small>
      </div>
      <button type="button" class="btn rv-toggle-section" data-target="#rvFiltrosBody">
        <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
      </button>
    </div>
    <div class="rv-section-body" id="rvFiltrosBody">
      <form id="form_main_ventas">
        <div class="row">
          <!-- Tipo Factura -->
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Tipo Factura</label>
              <select id="factura_reporte" name="factura_reporte" class="form-control selectpicker"
                      title="Factura" data-live-search="true">
                <option value="1">Electrónica</option>
                <option value="4">Proforma</option>
              </select>
            </div>
          </div>

          <!-- Categoría Factura -->
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Categoría Factura</label>
              <select id="tipo_factura_reporte" name="tipo_factura_reporte"
                      class="form-control selectpicker" title="Tipo de Factura" data-live-search="true">
              </select>
            </div>
          </div>

          <!-- Facturador -->
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Facturador</label>
              <select id="facturador" name="facturador" class="form-control selectpicker"
                      title="Facturador" data-live-search="true">
                <option value="">Seleccione</option>
              </select>
            </div>
          </div>

          <!-- Vendedor -->
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Vendedor</label>
              <select id="vendedor" name="vendedor" class="form-control selectpicker" title="Vendedor"
                      data-live-search="true">
                <option value="">Seleccione</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ====== ÁMBITO y EMPRESAS (visible sólo si plan permite multi) ====== -->
        <div class="row" id="fila-ambito" style="display:none;">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Ámbito</label>
              <select id="ambito_reporte" name="ambito_reporte" class="form-control selectpicker" title="Ámbito" data-live-search="false">
                <option value="empresa" selected>Solo esta empresa</option>
                <option value="consolidado">Consolidado (mis empresas)</option>
              </select>
            </div>
          </div>

          <div class="col-md-9 col-sm-6 mb-3" id="fila-empresas" style="display:none;">
            <div class="form-group">
              <label class="small mb-1">Empresas (dejar vacío para TODAS)</label>
              <!-- multiple para elegir específicas cuando es consolidado -->
              <select id="empresas_ids" name="empresas_ids[]" class="form-control selectpicker"
                      multiple data-live-search="true" title="Seleccione una o más empresas">
              </select>
            </div>
          </div>
        </div>

        <!-- Fechas + Rango rápido -->
        <div class="row">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Fecha Inicio</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" id="fechai" name="fechai" value="<?php 
                  $fecha = date ("Y-m-d");
                  $año = date("Y", strtotime($fecha));
                  $mes = date("m", strtotime($fecha));
                  $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                  $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                  $fecha_inicial = date("Y-m-d", strtotime($año.'-'.$mes.'-'.$dia1));
                  echo $fecha_inicial;
                ?>">
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Fecha Fin</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group">
              <label class="small mb-1">Rango rápido</label>
              <select id="rango_rapido" class="form-control selectpicker" title="Personalizado" data-live-search="false">
                <option value="">Personalizado</option>
                <option value="hoy">Hoy</option>
                <option value="semana">Semana actual</option>
                <option value="mes">Mes actual</option>
              </select>
            </div>
          </div>

          <div class="col-md-3 col-sm-12 d-flex align-items-end justify-content-end mb-3">
            <button type="submit" class="btn btn-primary mr-2" id="search">
              <i class="fas fa-filter fa-lg"></i> Filtrar
            </button>
            <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
              <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>

  <section class="rv-section-card" id="rvKpisSection">
    <div class="rv-section-header">
      <div>
        <h5><i class="fas fa-chart-pie mr-1"></i> Indicadores</h5>
        <small>Resumen de todos los registros que cumplen los filtros actuales.</small>
      </div>
      <button type="button" class="btn rv-toggle-section" data-target="#rvKpisBody">
        <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
      </button>
    </div>
    <div class="rv-section-body" id="rvKpisBody">
      <div class="rv-kpi-grid">
        <div class="rv-kpi rv-kpi-registros">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-file-invoice"></i> Registros</span>
            <strong id="rvKpiRegistros">0</strong>
            <small>Ventas filtradas</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-file-invoice"></i></div>
        </div>

        <div class="rv-kpi rv-kpi-subtotal">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-coins"></i> Subtotal</span>
            <strong id="rvKpiSubtotal">L. 0.00</strong>
            <small>Antes de impuestos</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-coins"></i></div>
        </div>

        <div class="rv-kpi rv-kpi-isv">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-percentage"></i> ISV</span>
            <strong id="rvKpiIsv">L. 0.00</strong>
            <small>Impuesto sobre ventas</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-percentage"></i></div>
        </div>

        <div class="rv-kpi rv-kpi-descuento">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-tags"></i> Descuento</span>
            <strong id="rvKpiDescuento">L. 0.00</strong>
            <small>Descuentos aplicados</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-tags"></i></div>
        </div>

        <div class="rv-kpi rv-kpi-primary rv-kpi-total">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-money-bill-wave"></i> Total ventas</span>
            <strong id="rvKpiTotal">L. 0.00</strong>
            <small>Ingresos del período</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>

        <div class="rv-kpi rv-kpi-ganancia">
          <div class="rv-kpi-copy">
            <span class="rv-kpi-label"><i class="fas fa-chart-line"></i> Ganancia</span>
            <strong id="rvKpiGanancia">L. 0.00</strong>
            <small>Resultado estimado</small>
          </div>
          <div class="rv-kpi-icon"><i class="fas fa-chart-line"></i></div>
        </div>
      </div>
    </div>
  </section>

  <section class="rv-section-card" id="rvListadoSection">
    <div class="rv-section-header">
      <div>
        <h5><i class="fas fa-file-invoice-dollar mr-1"></i> Reporte de Ventas</h5>
        <small>Facturas y proformas según los filtros aplicados.</small>
      </div>
      <div class="rv-section-header-actions">
<button type="button" class="btn rv-toggle-section" data-target="#rvListadoBody">
          <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
        </button>
      </div>
    </div>

    <div class="rv-section-body" id="rvListadoBody">
      <div class="rv-list-toolbar">
        <div class="rv-toolbar-left">
          <button type="button" id="rvBtnActualizar" class="btn btn-info table_actualizar ocultar"><i class="fas fa-sync-alt"></i> Actualizar</button>
          <button type="button" id="rvBtnPagos" class="btn btn-primary table_crear ocultar"><i class="fas fa-receipt"></i> Reporte de Pagos</button>
          <button type="button" id="rvBtnDetalle" class="btn btn-primary table_crear ocultar"><i class="fas fa-list"></i> Detalle Ventas</button>
          <button type="button" id="rvBtnExcel" class="btn btn-success table_reportes ocultar"><i class="fas fa-file-excel"></i> Excel</button>
          <button type="button" id="rvBtnPdf" class="btn btn-danger table_reportes ocultar"><i class="fas fa-file-pdf"></i> PDF</button>
        </div>

        <div class="rv-toolbar-right">
          <label class="rv-page-size">Mostrar
            <select id="rvPageSize" class="form-control form-control-sm">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select> registros
          </label>
          <div class="rv-view-switch">
            <button type="button" class="rv-view-btn active" data-view="detalle"><i class="fas fa-list"></i> Detalle</button>
            <button type="button" class="rv-view-btn" data-view="miniatura"><i class="fas fa-th-large"></i> Miniatura</button>
          </div>
          <div class="rv-search-wrap">
            <i class="fas fa-search"></i>
            <input type="search" id="rvSearch" class="form-control form-control-sm" placeholder="Buscar...">
            <button type="button" id="rvSearchClear"><i class="fas fa-times"></i></button>
          </div>
        </div>
      </div>

      <div id="rvListado" class="rv-list"></div>
      <div id="rvTotales" class="rv-total-row"></div>

      <div class="rv-list-footer">
        <span id="rvInfo">0 registros</span>
        <div id="rvPagination" class="rv-pagination"></div>
      </div>
    </div>

    <div class="card-footer small text-muted rv-last-update">
      <?php
        require_once "./core/mainModel.php";
        $insMainModel = new mainModel();
        $entidad = "facturas";
        if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
          $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
          $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
          $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
          echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
        }else{
          echo "No se encontraron registros ";
        }
      ?>
    </div>
  </section>
</div>

<!-- Modal detalle de una factura: SIN TABLA -->
<div class="modal fade rv-modal" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header rv-modal-header">
        <div>
          <h5 class="modal-title">Detalle de Factura <span id="numero-factura-modal"></span></h5>
          <small>Resumen fiscal y productos o servicios asociados.</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="rv-detail-summary">
          <div><span>Fecha</span><strong id="fecha-factura"></strong></div>
          <div><span>Cliente</span><strong id="cliente-factura"></strong></div>
          <div><span>Tipo</span><strong id="tipo-factura"></strong></div>
          <div><span>Estado</span><strong id="estado-factura"></strong></div>
          <div><span>Subtotal</span><strong id="subtotal-factura"></strong></div>
          <div><span>Total</span><strong id="total-factura"></strong></div>
        </div>
        <div id="detalle-factura-body" class="rv-detail-items"></div>
        <div class="rv-notes-card">
          <strong>Notas</strong>
          <p id="notas-factura" class="mb-0 text-muted"></p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button>
        <button type="button" id="btn-imprimir-factura" class="btn btn-primary"><i class="fas fa-print mr-1"></i> Imprimir</button>
      </div>
    </div>
  </div>
</div>

<?php
  $insMainModel->guardar_historial_accesos("Ingreso al modulo Reporte de Ventas");
?>
</body>
</html>
