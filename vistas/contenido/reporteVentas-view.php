<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Sistema de Restaurante</title>
  <!-- Estilos -->
  <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/facturasRestaurante.css">
</head>
<body>
<script>
  // ====== PLANES QUE PERMITEN MULTI-EMPRESA: 3,4,5,7 ======
  var PLANES_ID = <?php echo isset($_SESSION['planes_id']) ? intval($_SESSION['planes_id']) : 0; ?>;
  var PLAN_ALLOW_MULTI = [3,4,5,7].indexOf(PLANES_ID) !== -1;

  // Id de empresa activa (por si lo necesitás)
  var EMPRESA_ID_ACTIVA = <?php echo isset($_SESSION['empresa_id_sd']) ? intval($_SESSION['empresa_id_sd']) : 0; ?>;

  document.addEventListener('DOMContentLoaded', function(){
    if (PLAN_ALLOW_MULTI) {
      const filaAmbito = document.getElementById('fila-ambito');
      if (filaAmbito) filaAmbito.style.display = '';
      const filaEmpresas = document.getElementById('fila-empresas');
      if (filaEmpresas) filaEmpresas.style.display = 'none'; // se muestra sólo si ambito=consolidado
    }
  });
</script>

<div class="container-fluid">
  <!-- Migas -->
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

  <div class="card mb-4">
    <div class="card-body">
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
  </div>

  <!-- Tabla -->
  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-file-invoice-dollar fa-lg mr-1"></i> Reporte de Ventas
      <div class="float-right">
        <span class="badge bg-light text-dark">
          <i class="fas fa-sync-alt mr-1 fa-lg"></i>
          <span id="contador-actualizacion"></span>
        </span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="dataTablaReporteVentas"
               class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Cliente</th>
              <th>Factura</th>
              <th>SubTotal</th>
              <th>ISV</th>
              <th>Descuento</th>
              <th>Total Ventas</th>
              <th>Ganancia</th>
              <th>Vendedor</th>
              <th>Facturador</th>
              <th>Ver Detalle</th>
              <th>Factura</th>
              <th>Comprobante</th>
              <th>Enviar</th>
              <th>Anular</th>
            </tr>
          </thead>
          <tfoot class="bg-secondary">
            <tr>
              <td colspan='1'>Total</td>
              <td colspan="3"></td>
              <td id="subtotal-i"></td>
              <td id="impuesto-i"></td>
              <td id="descuento-i"></td>
              <td id='total-footer-ingreso'></td>
              <td id='ganancia'></td>
              <td colspan="7"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <div class="card-footer small text-muted">
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
  </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de Factura <span id="numero-factura-modal"></span></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-4">
          <div class="col-md-4">
            <h6><strong>Fecha:</strong> <span id="fecha-factura"></span></h6>
            <h6><strong>Cliente:</strong> <span id="cliente-factura"></span></h6>
          </div>
          <div class="col-md-4">
            <h6><strong>Tipo:</strong> <span id="tipo-factura"></span></h6>
            <h6><strong>Estado:</strong> <span id="estado-factura"></span></h6>
          </div>
          <div class="col-md-4 text-right">
            <h6><strong>Subtotal:</strong> <span id="subtotal-factura"></span></h6>
            <h6><strong>Total:</strong> <span id="total-factura"></span></h6>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-header-gradient table-striped table-condensed table-hover">
            <thead class="bg-light">
              <tr>
                <th>Producto/Servicio</th>
                <th width="10%">Cantidad</th>
                <th width="15%">Precio Unitario</th>
                <th width="15%">ISV</th>
                <th width="15%">Descuento</th>
                <th width="15%">Subtotal</th>
              </tr>
            </thead>
            <tbody id="detalle-factura-body"></tbody>
          </table>
        </div>

        <div class="row mt-3">
          <div class="col-md-12">
            <h6><strong>Notas:</strong></h6>
            <p id="notas-factura" class="text-muted"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" data-dismiss="modal">
          <i class="fas fa-times fa-lg mr-1"></i> Cancelar
        </button>
        <button type="button" id="btn-imprimir-factura" class="btn btn-primary">
          <i class="fas fa-print fa-lg mr-1"></i> Imprimir
        </button>
      </div>
    </div>
  </div>
</div>

<?php
  $insMainModel->guardar_historial_accesos("Ingreso al modulo Reporte de Ventas");
?>
</body>
</html>
