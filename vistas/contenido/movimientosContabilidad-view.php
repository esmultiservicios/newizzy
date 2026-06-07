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

	<div class="card movimientos-filter-box mb-4">
		<div class="card-body">
			<form id="formMainMovimientosContabilidad" autocomplete="off">
				<div class="row">
					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
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
									$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));

									$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
									echo $fecha_inicial;
								?>">
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1">Fecha Fin</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1">Cuenta / Código / Nombre</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-search"></i></span>
								</div>
								<input type="text" class="form-control" id="cuenta_busqueda" name="cuenta_busqueda" placeholder="Ej: Caja, Banco, 101...">
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1">Tipo de Movimiento</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-random"></i></span>
								</div>
								<select class="form-control selectpicker" id="tipo_movimiento" name="tipo_movimiento" title="Seleccione">
									<option value="">Todos</option>
									<option value="ingreso">Solo ingresos</option>
									<option value="egreso">Solo egresos</option>
									<option value="saldo_positivo">Saldo positivo</option>
									<option value="saldo_negativo">Saldo negativo</option>
									<option value="saldo_cero">Saldo cero</option>
									<option value="inversion">Cuentas de inversión</option>
								</select>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1">Monto Desde</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
								</div>
								<input type="text" inputmode="decimal" class="form-control" id="monto_desde" name="monto_desde" placeholder="0.00">
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6 col-sm-12 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1">Monto Hasta</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
								</div>
								<input type="text" inputmode="decimal" class="form-control" id="monto_hasta" name="monto_hasta" placeholder="0.00">
							</div>
						</div>
					</div>

					<div class="col-lg-6 col-md-12 col-sm-12 mb-3 d-flex align-items-end justify-content-end">
						<button type="submit" class="btn btn-primary mr-2" id="search">
							<i class="fas fa-filter fa-lg"></i> Filtrar
						</button>
						<button type="reset" class="btn btn-secondary">
							<i class="fas fa-broom fa-lg"></i> Limpiar
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="row mb-4">
		<div class="col-xl-3 col-md-6 col-sm-12 mb-3">
			<div class="movimientos-summary-card summary-income">
				<div class="movimientos-summary-label">
					<i class="fas fa-arrow-circle-down"></i> Total Ingresos
				</div>
				<p class="movimientos-summary-value" id="resumen_total_ingresos">L 0.00</p>
				<p class="movimientos-summary-help">Suma real de ingresos filtrados</p>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 col-sm-12 mb-3">
			<div class="movimientos-summary-card summary-expense">
				<div class="movimientos-summary-label">
					<i class="fas fa-arrow-circle-up"></i> Total Egresos
				</div>
				<p class="movimientos-summary-value" id="resumen_total_egresos">L 0.00</p>
				<p class="movimientos-summary-help">Suma real de egresos filtrados</p>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 col-sm-12 mb-3">
			<div class="movimientos-summary-card summary-balance">
				<div class="movimientos-summary-label">
					<i class="fas fa-balance-scale"></i> Balance del Período
				</div>
				<p class="movimientos-summary-value" id="resumen_balance_periodo">L 0.00</p>
				<p class="movimientos-summary-help">Ingresos menos egresos</p>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 col-sm-12 mb-3">
			<div class="movimientos-summary-card summary-accounts">
				<div class="movimientos-summary-label">
					<i class="fas fa-wallet"></i> Saldo Final Consultado
				</div>
				<p class="movimientos-summary-value" id="resumen_saldo_final">L 0.00</p>
				<p class="movimientos-summary-help">
					<span id="resumen_movimientos">0 movimientos</span> · 
					<span id="resumen_cuentas">0 cuentas</span>
				</p>
			</div>
		</div>
	</div>

    <div class="card movimientos-premium-card mb-4">
        <div class="movimientos-premium-header">
            <h5 class="movimientos-premium-title">
				<i class="fas fa-file-invoice-dollar"></i>
				Movimiento de Cuentas
			</h5>
			<p class="movimientos-premium-subtitle">
				Consulta detallada de ingresos, egresos y saldo por cuenta contable.
			</p>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableMovimientosContabilidad" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                </table>
            </div>
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
				} else {
					echo "No se encontraron registros ";
				}
			?>
        </div>
    </div>
</div>

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Movimientos Contabilidad");
?>