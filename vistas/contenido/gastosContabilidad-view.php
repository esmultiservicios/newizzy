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

  <!-- Filtros -->
  <div class="card mb-4 egresos-filtro-card">
    <div class="card-body">
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
                  <span class="input-group-text">
                    <i class="fas fa-calendar-alt"></i>
                  </span>
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
                  <span class="input-group-text">
                    <i class="fas fa-calendar-alt"></i>
                  </span>
                </div>
                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d'); ?>">
              </div>
            </div>
          </div>

          <div class="col-md-3 col-sm-6 mb-3 d-flex align-items-end">
            <div class="egresos-filtro-actions w-100">
              <button type="submit" class="btn btn-primary egresos-btn-filtrar" id="search">
                <i class="fas fa-filter fa-lg"></i> Filtrar
              </button>
              <button type="reset" class="btn btn-secondary egresos-btn-limpiar">
                <i class="fas fa-broom fa-lg"></i> Limpiar
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Cards resumen -->
  <div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
      <div class="egresos-resumen-card egresos-resumen-registros">
        <div>
          <div class="egresos-resumen-label">Registros</div>
          <h3 id="egresos-card-registros">0</h3>
          <p>Total de egresos encontrados</p>
        </div>
        <div class="egresos-resumen-icon">
          <i class="fas fa-list-ol"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="egresos-resumen-card egresos-resumen-subtotal">
        <div>
          <div class="egresos-resumen-label">Subtotal</div>
          <h3 id="egresos-card-subtotal">L 0.00</h3>
          <p>Subtotal del período</p>
        </div>
        <div class="egresos-resumen-icon">
          <i class="fas fa-coins"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="egresos-resumen-card egresos-resumen-impuesto">
        <div>
          <div class="egresos-resumen-label">Impuesto</div>
          <h3 id="egresos-card-impuesto">L 0.00</h3>
          <p>ISV acumulado</p>
        </div>
        <div class="egresos-resumen-icon">
          <i class="fas fa-percentage"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
      <div class="egresos-resumen-card egresos-resumen-total">
        <div>
          <div class="egresos-resumen-label">Total</div>
          <h3 id="egresos-card-total">L 0.00</h3>
          <p>Total de egresos</p>
        </div>
        <div class="egresos-resumen-icon">
          <i class="fas fa-file-invoice-dollar"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card mb-4 egresos-table-card">
    <div class="card-header egresos-card-header">
      <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
          <strong>
            <i class="fas fa-file-invoice-dollar fa-lg mr-1"></i>
            Egresos
          </strong>
          <br>
          <small class="text-muted">
            Registro de egresos contables filtrados por período y estado
          </small>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive egresos-table-responsive">
        <table id="dataTableGastosContabilidad" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
          <thead>
            <tr>
              <th>Acciones</th>
              <th>Fecha Registro</th>
              <th>Número</th>
              <th>Categoría</th>
              <th>Fecha Factura</th>
              <th>Forma de Pago</th>
              <th>Proveedor</th>
              <th>Número Factura</th>
              <th>Subtotal</th>
              <th>Impuesto</th>
              <th>Descuento</th>
              <th>Nota de Crédito</th>
              <th>Total</th>
              <th>Observación</th>
              <th>Estado</th>
            </tr>
          </thead>

          <tfoot class="bg-secondary text-white font-weight-bold">
            <tr>
              <td colspan="1">Total</td>
              <td colspan="7"></td>
              <td id="subtotal-g"></td>
              <td id="impuesto-g"></td>
              <td id="descuento-g"></td>
              <td id="nc-g"></td>
              <td id="total-footer-gastos"></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
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
