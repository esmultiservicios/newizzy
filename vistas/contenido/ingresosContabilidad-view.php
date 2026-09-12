<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/ingresosContabilidad.css">

<div class="container-fluid ingresos-page">
	<!-- Ingresos -->
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
				<i class="fas fa-sign-in-alt breadcrumb-icon"></i>
				<span>Ingresos</span>
			</li>
		</ol>
	</div>

	<!-- Filtros -->
	<div class="card mb-4 ingresos-filtro-card ingresos-section-card">
        <div class="ingresos-section-header">
            <div class="ingresos-section-title">
                <span class="ingresos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Ingresos</h5>
                    <small>Consulte ingresos por estado y período sin alterar los registros.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary ingresos-toggle-btn" id="btnToggleFiltrosIngresos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>
        <div class="card-body" id="ingresosFiltrosContenido">
			<form id="formMainIngresosContabilidad">
				<div class="row">
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1 ingresos-label-filter">Estado</label>
							<select id="estado_ingresos" name="estado_ingresos"
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
							<label class="small mb-1 ingresos-label-filter">Fecha Inicio</label>
							<div class="input-group ingresos-input-group">
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
							<label class="small mb-1 ingresos-label-filter">Fecha Fin</label>
							<div class="input-group ingresos-input-group">
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
						<div class="ingresos-filtro-actions w-100">
							<button type="submit" class="btn btn-primary ingresos-btn-filtrar" id="search">
								<i class="fas fa-filter fa-lg"></i> Filtrar
							</button>
							<button type="reset" class="btn btn-secondary ingresos-btn-limpiar">
								<i class="fas fa-broom fa-lg"></i> Limpiar
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<!-- KPIs -->
    <section class="card ingresos-section-card mb-4" id="ingresosKpisSection">
        <div class="ingresos-section-header">
            <div class="ingresos-section-title">
                <span class="ingresos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Ingresos</h5>
                    <small>Indicadores calculados sobre el resultado filtrado.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary ingresos-toggle-btn" id="btnToggleKpisIngresos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>
        <div class="card-body pb-0" id="ingresosKpisContenido">
	<div class="row mb-4">
		<div class="col-xl-3 col-md-6 mb-3">
			<div class="ingresos-resumen-card ingresos-resumen-registros">
				<div>
					<div class="ingresos-resumen-label">Registros</div>
					<h3 id="ingresos-card-registros">0</h3>
					<p>Total de ingresos encontrados</p>
				</div>
				<div class="ingresos-resumen-icon">
					<i class="fas fa-list-ol"></i>
				</div>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 mb-3">
			<div class="ingresos-resumen-card ingresos-resumen-subtotal">
				<div>
					<div class="ingresos-resumen-label">Subtotal</div>
					<h3 id="ingresos-card-subtotal">L 0.00</h3>
					<p>Subtotal del período</p>
				</div>
				<div class="ingresos-resumen-icon">
					<i class="fas fa-coins"></i>
				</div>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 mb-3">
			<div class="ingresos-resumen-card ingresos-resumen-impuesto">
				<div>
					<div class="ingresos-resumen-label">Impuesto</div>
					<h3 id="ingresos-card-impuesto">L 0.00</h3>
					<p>ISV acumulado</p>
				</div>
				<div class="ingresos-resumen-icon">
					<i class="fas fa-percentage"></i>
				</div>
			</div>
		</div>

		<div class="col-xl-3 col-md-6 mb-3">
			<div class="ingresos-resumen-card ingresos-resumen-total">
				<div>
					<div class="ingresos-resumen-label">Total</div>
					<h3 id="ingresos-card-total">L 0.00</h3>
					<p>Total de ingresos</p>
				</div>
				<div class="ingresos-resumen-icon">
					<i class="fas fa-hand-holding-usd"></i>
				</div>
			</div>
		</div>
	</div>

        </div>
    </section>

	<!-- LISTADO -->
    <div class="card mb-4 ingresos-table-card ingresos-section-card">
        <div class="ingresos-section-header">
            <div class="ingresos-section-title">
                <span class="ingresos-section-icon"><i class="fas fa-hand-holding-usd"></i></span>
                <div>
                    <h5 class="mb-0">Ingresos</h5>
                    <small>Registro de ingresos contables filtrados por período y estado.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="ingresos-list-toolbar">
                <div class="ingresos-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnIngresosActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnIngresosIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnIngresosExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnIngresosPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="ingresos-toolbar-right">
                    <label class="ingresos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="ingresosPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="ingresos-view-switch">
                        <button type="button" class="ingresos-view-btn active" data-view="detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="ingresos-view-btn" data-view="miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="ingresos-search-wrap">
                        <span class="ingresos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarIngresosListado" class="form-control" placeholder="Buscar ingreso..." autocomplete="off">
                        <button type="button" id="limpiarBuscarIngresosListado" class="ingresos-search-clear"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>

            <div id="ingresosListado" class="ingresos-listado vista-detalle"></div>

            <div class="ingresos-totales-grid">
                <div class="ingresos-total-card"><span>Subtotal</span><strong id="ingresosTotalSubtotal">L 0.00</strong></div>
                <div class="ingresos-total-card ingresos-total-info"><span>Impuesto</span><strong id="ingresosTotalImpuesto">L 0.00</strong></div>
                <div class="ingresos-total-card ingresos-total-warning"><span>Descuento</span><strong id="ingresosTotalDescuento">L 0.00</strong></div>
                <div class="ingresos-total-card ingresos-total-current"><span>Total</span><strong id="ingresosTotalGeneral">L 0.00</strong></div>
            </div>

            <div class="ingresos-list-footer">
                <span id="ingresosInfo">0 registros</span>
                <div id="ingresosPaginacion" class="ingresos-pagination"></div>
            </div>
        </div>

		<div class="card-footer small ingresos-card-footer">
			<div class="row">
				<div class="col-12">
					<?php
						require_once "./core/mainModel.php";

						$insMainModel = new mainModel();
						$entidad = "ingresos";

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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Ingresos Contabilidad");
?>