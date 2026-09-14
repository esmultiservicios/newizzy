<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/historialAccesos.css">

<div class="container-fluid historial-page">
	<!-- Historial de Accesos -->
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
				<i class="fas fa-history breadcrumb-icon"></i>
				<span>Historial de Accesos</span>
			</li>
		</ol>
	</div>

	<!-- FILTROS -->
	<section class="card mb-4 historial-section-card historial-filtro-card" id="historialFiltrosSection">
		<div class="historial-section-header">
			<div class="historial-section-title">
				<span class="historial-section-icon"><i class="fas fa-filter"></i></span>
				<div>
					<h5 class="mb-0">Filtros de Historial</h5>
					<small>Consulte los accesos registrados por período.</small>
				</div>
			</div>

			<button type="button" class="btn btn-primary historial-toggle-btn" id="btnToggleFiltrosHistorial" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body" id="historialFiltrosContenido">
			<form id="formMainHistorialAcceso" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
				<div class="row align-items-end">
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1 historial-label-filter" for="fechai">Fecha Inicio</label>
							<div class="input-group historial-input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechai" name="fechai" value="<?php
									$fecha = date("Y-m-d");
									$año = date("Y", strtotime($fecha));
									$mes = date("m", strtotime($fecha));
									$dia = date("d", mktime(0, 0, 0, $mes + 1, 0, $año));
									$dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));
									$fecha_inicial = date("Y-m-d", strtotime($año . "-" . $mes . "-" . $dia1));
									echo $fecha_inicial;
								?>">
							</div>
						</div>
					</div>

					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group mb-0">
							<label class="small mb-1 historial-label-filter" for="fechaf">Fecha Fin</label>
							<div class="input-group historial-input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d'); ?>">
							</div>
						</div>
					</div>

					<div class="col-md-6 col-sm-12 mb-3 d-flex align-items-end">
						<div class="historial-filtro-actions w-100">
							<button type="submit" class="btn btn-primary" id="search">
								<i class="fas fa-filter mr-1"></i> Filtrar
							</button>
							<button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
								<i class="fas fa-broom mr-1"></i> Limpiar
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</section>

	<!-- KPIs -->
	<section class="card mb-4 historial-section-card" id="historialKpisSection">
		<div class="historial-section-header">
			<div class="historial-section-title">
				<span class="historial-section-icon"><i class="fas fa-chart-pie"></i></span>
				<div>
					<h5 class="mb-0">Resumen de Accesos</h5>
					<small>Indicadores calculados sobre el resultado filtrado.</small>
				</div>
			</div>

			<button type="button" class="btn btn-primary historial-toggle-btn" id="btnToggleKpisHistorial" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body pb-0" id="historialKpisContenido">
			<div class="row mb-4">
				<div class="col-xl-4 col-md-6 mb-3">
					<div class="historial-resumen-card historial-resumen-accesos">
						<div>
							<div class="historial-resumen-label">Total accesos</div>
							<h3 id="historial-card-accesos">0</h3>
							<p>Accesos encontrados</p>
						</div>
						<div class="historial-resumen-icon"><i class="fas fa-sign-in-alt"></i></div>
					</div>
				</div>

				<div class="col-xl-4 col-md-6 mb-3">
					<div class="historial-resumen-card historial-resumen-colaboradores">
						<div>
							<div class="historial-resumen-label">Colaboradores</div>
							<h3 id="historial-card-colaboradores">0</h3>
							<p>Colaboradores con actividad</p>
						</div>
						<div class="historial-resumen-icon"><i class="fas fa-users"></i></div>
					</div>
				</div>

				<div class="col-xl-4 col-md-6 mb-3">
					<div class="historial-resumen-card historial-resumen-ips">
						<div>
							<div class="historial-resumen-label">IPs registradas</div>
							<h3 id="historial-card-ips">0</h3>
							<p>Direcciones IP distintas</p>
						</div>
						<div class="historial-resumen-icon"><i class="fas fa-network-wired"></i></div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- LISTADO -->
	<section class="card mb-4 historial-section-card historial-table-card">
		<div class="historial-section-header">
			<div class="historial-section-title">
				<span class="historial-section-icon"><i class="fas fa-history"></i></span>
				<div>
					<h5 class="mb-0">Historial de Accesos</h5>
					<small>Registro de accesos filtrados por período.</small>
				</div>
			</div>
		</div>

		<div class="card-body">
			<div class="historial-list-toolbar">
				<div class="historial-toolbar-left">
					<button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnHistorialActualizar">
						<i class="fas fa-sync-alt mr-1"></i> Actualizar
					</button>

					<button type="button" class="btn btn-success table_reportes ocultar" id="btnHistorialExcel">
						<i class="fas fa-file-excel mr-1"></i> Excel
					</button>

					<button type="button" class="btn btn-danger table_reportes ocultar" id="btnHistorialPdf">
						<i class="fas fa-file-pdf mr-1"></i> PDF
					</button>
				</div>

				<div class="historial-toolbar-right">
					<label class="historial-page-size mb-0">
						<span>Mostrar</span>
						<select id="historialPageSize" class="form-control form-control-sm"></select>
						<span>registros</span>
					</label>

					<div class="historial-view-switch">
						<button type="button" class="historial-view-btn active" data-view="detalle">
							<i class="fas fa-list"></i><span>Detalle</span>
						</button>
						<button type="button" class="historial-view-btn" data-view="miniatura">
							<i class="fas fa-th-large"></i><span>Miniatura</span>
						</button>
					</div>

					<div class="historial-search-wrap">
						<span class="historial-search-icon"><i class="fas fa-search"></i></span>
						<input type="search" id="buscarHistorialListado" class="form-control" placeholder="Buscar acceso..." autocomplete="off">
						<button type="button" id="limpiarBuscarHistorialListado" class="historial-search-clear" aria-label="Limpiar búsqueda">
							<i class="fas fa-times"></i>
						</button>
					</div>
				</div>
			</div>

			<div id="historialListado" class="historial-listado vista-detalle"></div>

			<div class="historial-list-footer">
				<span id="historialInfo">0 registros</span>
				<div id="historialPaginacion" class="historial-pagination"></div>
			</div>
		</div>

		<div class="card-footer small historial-card-footer">
			<div class="row">
				<div class="col-12">
					<?php
						require_once "./core/mainModel.php";

						$insMainModel = new mainModel();

						if ($insMainModel->getlastUpdateHistorialAccessos()->num_rows > 0) {
							$consulta_last_update = $insMainModel->getlastUpdateHistorialAccessos()->fetch_assoc();
							$fecha_registro = htmlspecialchars($consulta_last_update['fecha'], ENT_QUOTES, 'UTF-8');
							$hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');

							echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
						} else {
							echo "No se encontraron registros ";
						}
					?>
				</div>
			</div>
		</div>
	</section>
</div>

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Historial de Accesos");
?>
