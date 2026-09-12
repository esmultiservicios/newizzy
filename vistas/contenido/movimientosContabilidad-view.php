<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/movimientosContabilidad.css">

<div class="container-fluid">
	<!-- Movimiento de Cuentas -->
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
				<i class="fas fa-exchange-alt breadcrumb-icon"></i>
				<span>Movimiento de Cuentas</span>
			</li>
		</ol>
	</div>

	<!-- FILTROS -->
	<section class="card movimientos-section-card mb-4" id="movimientosFiltrosSection">
		<div class="movimientos-section-header">
			<div class="movimientos-section-title">
				<span class="movimientos-section-icon"><i class="fas fa-filter"></i></span>
				<div>
					<h5 class="mb-0">Filtros de Movimientos</h5>
					<small>Consulte movimientos por período, cuenta, tipo y rango de monto.</small>
				</div>
			</div>
			<button type="button" class="btn btn-primary movimientos-toggle-btn" id="btnToggleFiltrosMovimientos" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body" id="movimientosFiltrosContenido">
			<form id="formMainMovimientosContabilidad" autocomplete="off">
				<div class="row align-items-end">
					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="fechai"><i class="fas fa-calendar-alt mr-1"></i> Fecha Inicio</label>
						<input type="date" class="form-control" id="fechai" name="fechai" value="<?php 
							$fecha = date ("Y-m-d");
							$año = date("Y", strtotime($fecha));
							$mes = date("m", strtotime($fecha));
							$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
							$dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
							$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
							$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
							echo $fecha_inicial;
						?>">
					</div>

					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="fechaf"><i class="fas fa-calendar-check mr-1"></i> Fecha Fin</label>
						<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
					</div>

					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="cuenta_busqueda"><i class="fas fa-search mr-1"></i> Cuenta / Código / Nombre</label>
						<input type="text" class="form-control" id="cuenta_busqueda" name="cuenta_busqueda" placeholder="Ej: Caja, Banco, 101...">
					</div>

					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="tipo_movimiento"><i class="fas fa-random mr-1"></i> Tipo de Movimiento</label>
						<select class="form-control selectpicker" id="tipo_movimiento" name="tipo_movimiento" title="Seleccione" data-width="100%">
							<option value="">Todos</option>
							<option value="ingreso">Solo ingresos</option>
							<option value="egreso">Solo egresos</option>
							<option value="saldo_positivo">Saldo positivo</option>
							<option value="saldo_negativo">Saldo negativo</option>
							<option value="saldo_cero">Saldo cero</option>
							<option value="inversion">Cuentas de inversión</option>
						</select>
					</div>

					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="monto_desde"><i class="fas fa-money-bill-wave mr-1"></i> Monto Desde</label>
						<input type="text" inputmode="decimal" class="form-control" id="monto_desde" name="monto_desde" placeholder="0.00">
					</div>

					<div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
						<label class="movimientos-filter-label" for="monto_hasta"><i class="fas fa-money-bill-wave mr-1"></i> Monto Hasta</label>
						<input type="text" inputmode="decimal" class="form-control" id="monto_hasta" name="monto_hasta" placeholder="0.00">
					</div>

					<div class="col-xl-6 col-lg-8 col-md-12 col-12 mb-3">
						<div class="movimientos-filter-actions">
							<button type="submit" class="btn btn-primary" id="search"><i class="fas fa-filter mr-1"></i> Filtrar</button>
							<button type="reset" class="btn btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</section>

	<!-- KPIs -->
	<section class="card movimientos-section-card mb-4" id="movimientosKpisSection">
		<div class="movimientos-section-header">
			<div class="movimientos-section-title">
				<span class="movimientos-section-icon"><i class="fas fa-chart-pie"></i></span>
				<div>
					<h5 class="mb-0">Resumen de Movimientos</h5>
					<small>Indicadores calculados sobre el resultado filtrado.</small>
				</div>
			</div>
			<button type="button" class="btn btn-primary movimientos-toggle-btn" id="btnToggleKpisMovimientos" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body" id="movimientosKpisContenido">
			<div class="row movimientos-kpi-row">
				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="movimientos-kpi movimientos-kpi-success">
						<div class="movimientos-kpi-copy"><span>Total Ingresos</span><strong id="resumen_total_ingresos">L 0.00</strong><small>Suma real de ingresos filtrados</small></div>
						<span class="movimientos-kpi-icon"><i class="fas fa-arrow-down"></i></span>
					</div>
				</div>
				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="movimientos-kpi movimientos-kpi-danger">
						<div class="movimientos-kpi-copy"><span>Total Egresos</span><strong id="resumen_total_egresos">L 0.00</strong><small>Suma real de egresos filtrados</small></div>
						<span class="movimientos-kpi-icon"><i class="fas fa-arrow-up"></i></span>
					</div>
				</div>
				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="movimientos-kpi movimientos-kpi-primary">
						<div class="movimientos-kpi-copy"><span>Balance del Período</span><strong id="resumen_balance_periodo">L 0.00</strong><small>Ingresos menos egresos</small></div>
						<span class="movimientos-kpi-icon"><i class="fas fa-balance-scale"></i></span>
					</div>
				</div>
				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="movimientos-kpi movimientos-kpi-purple">
						<div class="movimientos-kpi-copy"><span>Saldo Final Consultado</span><strong id="resumen_saldo_final">L 0.00</strong><small><span id="resumen_movimientos">0 movimientos</span> · <span id="resumen_cuentas">0 cuentas</span></small></div>
						<span class="movimientos-kpi-icon"><i class="fas fa-wallet"></i></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- LISTADO -->
	<section class="card movimientos-section-card mb-4" id="movimientosListadoSection">
		<div class="movimientos-directory-header">
			<div class="movimientos-section-title">
				<span class="movimientos-section-icon"><i class="fas fa-file-invoice-dollar"></i></span>
				<div>
					<h5 class="mb-0">Movimiento de Cuentas</h5>
					<small>Consulta detallada de ingresos, egresos y saldo por cuenta contable.</small>
				</div>
			</div>
		</div>

		<div class="card-body">
			<div class="movimientos-list-toolbar">
				<div class="movimientos-toolbar-left">
					<button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnMovimientosActualizar"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
					<button type="button" class="btn btn-success table_reportes ocultar" id="btnMovimientosExcel"><i class="fas fa-file-excel mr-1"></i> Excel</button>
					<button type="button" class="btn btn-danger table_reportes ocultar" id="btnMovimientosPdf"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
				</div>

				<div class="movimientos-toolbar-right">
					<label class="movimientos-page-size mb-0"><span>Mostrar</span><select id="movimientosPageSize" class="form-control form-control-sm"></select><span>registros</span></label>
					<div class="movimientos-view-switch">
						<button type="button" class="movimientos-view-btn active" data-view="detalle"><i class="fas fa-list"></i><span>Detalle</span></button>
						<button type="button" class="movimientos-view-btn" data-view="miniatura"><i class="fas fa-th-large"></i><span>Miniatura</span></button>
					</div>
					<div class="movimientos-search"><span class="movimientos-search-icon"><i class="fas fa-search"></i></span><input type="search" id="buscarMovimientos" class="form-control" placeholder="Buscar movimiento..." autocomplete="off"><button type="button" id="limpiarBuscarMovimientos" class="movimientos-search-clear"><i class="fas fa-times"></i></button></div>
				</div>
			</div>

			<div id="movimientosListado" class="movimientos-listado vista-detalle"></div>

			<div class="movimientos-totales-grid">
				<div class="movimientos-total-card movimientos-total-success"><span>Ingresos</span><strong id="movimientosTotalIngresos">L 0.00</strong></div>
				<div class="movimientos-total-card movimientos-total-danger"><span>Egresos</span><strong id="movimientosTotalEgresos">L 0.00</strong></div>
				<div class="movimientos-total-card movimientos-total-balance"><span>Balance</span><strong id="movimientosTotalBalance">L 0.00</strong></div>
				<div class="movimientos-total-card movimientos-total-current"><span>Saldo Final Consultado</span><strong id="movimientosTotalSaldo">L 0.00</strong></div>
			</div>

			<div class="movimientos-list-footer"><span id="movimientosInfo">0 registros</span><div id="movimientosPaginacion" class="movimientos-pagination"></div></div>
		</div>

		<div class="card-footer small text-muted">
			<?php
				require_once "./core/mainModel.php";
				$insMainModel = new mainModel();
				$entidad = "movimientos_cuentas";
				if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
					$consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
					$fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
					$hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
					echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
				} else { echo "No se encontraron registros "; }
			?>
		</div>
	</section>
</div>

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Movimientos Contabilidad");
?>