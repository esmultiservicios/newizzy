<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/gastosContabilidad.css">

<div class="container-fluid egresos-page">
  <!-- Egresos -->
  <div class="breadcrumb-container">
    <ol class="breadcrumb-harmony">
      <li class="breadcrumb-item">
        <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
          <i class="fas fa-home breadcrumb-icon"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="breadcrumb-separator">/</li>
      <li class="breadcrumb-item active">
        <i class="fas fa-money-bill-wave breadcrumb-icon"></i>
        <span>Egresos</span>
      </li>
    </ol>
  </div>

  <!-- FILTROS -->
  <section class="card mb-4 egresos-section-card egresos-filtro-card" id="egresosFiltrosSection">
    <div class="egresos-section-header">
      <div class="egresos-section-title">
        <span class="egresos-section-icon"><i class="fas fa-filter"></i></span>
        <div>
          <h5 class="mb-0">Filtros de Egresos</h5>
          <small>Consulte egresos por estado y período sin alterar los registros.</small>
        </div>
      </div>

      <button type="button" class="btn btn-primary egresos-toggle-btn"
              id="btnToggleFiltrosEgresos" aria-expanded="true">
        <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
      </button>
    </div>

    <div class="card-body" id="egresosFiltrosContenido">
      <form id="formMainGastosContabilidad">
        <div class="row">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group mb-0">
              <label class="small mb-1 egresos-label-filter">Estado</label>
              <select id="estado_egresos" name="estado_egresos"
                      class="form-control selectpicker"
                      title="Estado"
                      data-live-search="true">
                <option value="1">Activas</option>
                <option value="0">Anuladas</option>
              </select>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group mb-0">
              <label class="small mb-1 egresos-label-filter">Fecha Inicio</label>
              <div class="input-group egresos-input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" id="fechai" name="fechai" value="<?php
                  $fecha = date("Y-m-d");
                  $año = date("Y", strtotime($fecha));
                  $mes = date("m", strtotime($fecha));
                  $dia = date("d", mktime(0, 0, 0, $mes + 1, 0, $año));
                  $dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));
                  $dia2 = date('d', mktime(0, 0, 0, $mes, $dia, $año));
                  $fecha_inicial = date("Y-m-d", strtotime($año . "-" . $mes . "-" . $dia1));
                  echo $fecha_inicial;
                ?>">
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3">
            <div class="form-group mb-0">
              <label class="small mb-1 egresos-label-filter">Fecha Fin</label>
              <div class="input-group egresos-input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d'); ?>">
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3 d-flex align-items-end">
            <div class="egresos-filtro-actions w-100">
              <button type="submit" class="btn btn-primary egresos-btn-filtrar" id="search">
                <i class="fas fa-filter mr-1"></i> Filtrar
              </button>
              <button type="reset" class="btn btn-secondary egresos-btn-limpiar">
                <i class="fas fa-broom mr-1"></i> Limpiar
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

  <!-- KPIs -->
  <section class="card egresos-section-card mb-4" id="egresosKpisSection">
    <div class="egresos-section-header">
      <div class="egresos-section-title">
        <span class="egresos-section-icon"><i class="fas fa-chart-pie"></i></span>
        <div>
          <h5 class="mb-0">Resumen de Egresos</h5>
          <small>Indicadores calculados sobre el resultado filtrado.</small>
        </div>
      </div>

      <button type="button" class="btn btn-primary egresos-toggle-btn"
              id="btnToggleKpisEgresos" aria-expanded="true">
        <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
      </button>
    </div>

    <div class="card-body pb-0" id="egresosKpisContenido">
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
          <div class="egresos-resumen-card egresos-resumen-registros">
            <div>
              <div class="egresos-resumen-label">Registros</div>
              <h3 id="egresos-card-registros">0</h3>
              <p>Total de egresos encontrados</p>
            </div>
            <div class="egresos-resumen-icon"><i class="fas fa-list-ol"></i></div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
          <div class="egresos-resumen-card egresos-resumen-subtotal">
            <div>
              <div class="egresos-resumen-label">Subtotal</div>
              <h3 id="egresos-card-subtotal">L 0.00</h3>
              <p>Subtotal del período</p>
            </div>
            <div class="egresos-resumen-icon"><i class="fas fa-coins"></i></div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
          <div class="egresos-resumen-card egresos-resumen-impuesto">
            <div>
              <div class="egresos-resumen-label">Impuesto</div>
              <h3 id="egresos-card-impuesto">L 0.00</h3>
              <p>ISV acumulado</p>
            </div>
            <div class="egresos-resumen-icon"><i class="fas fa-percentage"></i></div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
          <div class="egresos-resumen-card egresos-resumen-total">
            <div>
              <div class="egresos-resumen-label">Total</div>
              <h3 id="egresos-card-total">L 0.00</h3>
              <p>Total de egresos</p>
            </div>
            <div class="egresos-resumen-icon"><i class="fas fa-file-invoice-dollar"></i></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- LISTADO -->
  <section class="card mb-4 egresos-section-card egresos-table-card">
    <div class="egresos-section-header">
      <div class="egresos-section-title">
        <span class="egresos-section-icon"><i class="fas fa-file-invoice-dollar"></i></span>
        <div>
          <h5 class="mb-0">Egresos</h5>
          <small>Registro de egresos contables filtrados por período y estado.</small>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="egresos-list-toolbar">
        <div class="egresos-toolbar-left">
          <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnEgresosActualizar">
            <i class="fas fa-sync-alt mr-1"></i> Actualizar
          </button>

          <button type="button" class="btn btn-primary table_crear ocultar" id="btnEgresosIngresar">
            <i class="fas fa-plus mr-1"></i> Ingresar
          </button>

          <button type="button" class="btn btn-primary table_crear ocultar" id="btnEgresosCategorias">
            <i class="fas fa-layer-group mr-1"></i> Categorías
          </button>

          <button type="button" class="btn btn-success table_reportes ocultar" id="btnEgresosExcel">
            <i class="fas fa-file-excel mr-1"></i> Excel
          </button>

          <button type="button" class="btn btn-danger table_reportes ocultar" id="btnEgresosPdf">
            <i class="fas fa-file-pdf mr-1"></i> PDF
          </button>
        </div>

        <div class="egresos-toolbar-right">
          <label class="egresos-page-size mb-0">
            <span>Mostrar</span>
            <select id="egresosPageSize" class="form-control form-control-sm"></select>
            <span>registros</span>
          </label>

          <div class="egresos-view-switch">
            <button type="button" class="egresos-view-btn active" data-view="detalle">
              <i class="fas fa-list"></i><span>Detalle</span>
            </button>
            <button type="button" class="egresos-view-btn" data-view="miniatura">
              <i class="fas fa-th-large"></i><span>Miniatura</span>
            </button>
          </div>

          <div class="egresos-search-wrap">
            <span class="egresos-search-icon"><i class="fas fa-search"></i></span>
            <input type="search" id="buscarEgresosListado" class="form-control"
                   placeholder="Buscar egreso..." autocomplete="off">
            <button type="button" id="limpiarBuscarEgresosListado" class="egresos-search-clear">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="egresosListado" class="egresos-listado vista-detalle"></div>

      <div class="egresos-totales-grid">
        <div class="egresos-total-card">
          <span>Subtotal</span><strong id="egresosTotalSubtotal">L 0.00</strong>
        </div>
        <div class="egresos-total-card egresos-total-info">
          <span>Impuesto</span><strong id="egresosTotalImpuesto">L 0.00</strong>
        </div>
        <div class="egresos-total-card egresos-total-warning">
          <span>Descuento</span><strong id="egresosTotalDescuento">L 0.00</strong>
        </div>
        <div class="egresos-total-card egresos-total-purple">
          <span>Nota de Crédito</span><strong id="egresosTotalNC">L 0.00</strong>
        </div>
        <div class="egresos-total-card egresos-total-current">
          <span>Total</span><strong id="egresosTotalGeneral">L 0.00</strong>
        </div>
      </div>

      <div class="egresos-list-footer">
        <span id="egresosInfo">0 registros</span>
        <div id="egresosPaginacion" class="egresos-pagination"></div>
      </div>
    </div>

<div class="card-footer small egresos-card-footer">
      <div class="row">
        <div class="col-12">
          <?php
            require_once "./core/mainModel.php";

            $insMainModel = new mainModel();
            $entidad = "egresos";

            if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
              $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
              $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
              $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');

              echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
            } else {
              echo "No se encontraron registros ";
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>



<?php
  $insMainModel->guardar_historial_accesos("Ingreso al modulo Gastos Contabilidad");
?>
