<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/colaboradores.css">

<div class="container-fluid">
	<!-- Colaboradores -->
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
				<i class="fas fa-users-cog breadcrumb-icon"></i>
				<span>Colaboradores</span>
			</li>
		</ol>
	</div>

	<!-- FILTROS -->
	<section class="card colaboradores-section-card mb-4" id="colaboradoresFiltrosSection">
		<div class="colaboradores-section-header">
			<div class="colaboradores-section-title">
				<span class="colaboradores-section-icon"><i class="fas fa-filter"></i></span>
				<div>
					<h5 class="mb-0">Filtros de Colaboradores</h5>
					<small>Consulte colaboradores por estado sin alterar la información registrada.</small>
				</div>
			</div>
			<button type="button" class="btn btn-primary colaboradores-toggle-btn" id="btnToggleFiltrosColaboradores" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body" id="colaboradoresFiltrosContenido">
			<form id="form_main_colaboradores">
				<div class="row align-items-end">
					<div class="col-lg-4 col-md-6 col-12 mb-3">
						<label class="colaboradores-filter-label" for="estado_colaboradores">
							<i class="fas fa-toggle-on mr-1"></i> Estado
						</label>
						<select id="estado_colaboradores" name="estado_colaboradores"
							class="form-control selectpicker" title="Estado" data-live-search="true" data-width="100%">
							<option value="1">Activo</option>
							<option value="0">Inactivo</option>
						</select>
					</div>

					<div class="col-lg-8 col-md-6 col-12 mb-3">
						<div class="colaboradores-filter-actions">
							<button type="submit" class="btn btn-primary" id="search">
								<i class="fas fa-filter mr-1"></i> Filtrar
							</button>
							<button type="reset" class="btn btn-secondary">
								<i class="fas fa-broom mr-1"></i> Limpiar
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</section>

	<!-- KPIs -->
	<section class="card colaboradores-section-card mb-4" id="colaboradoresKpisSection">
		<div class="colaboradores-section-header">
			<div class="colaboradores-section-title">
				<span class="colaboradores-section-icon"><i class="fas fa-chart-pie"></i></span>
				<div>
					<h5 class="mb-0">Resumen de Colaboradores</h5>
					<small>Indicadores calculados sobre el resultado filtrado.</small>
				</div>
			</div>
			<button type="button" class="btn btn-primary colaboradores-toggle-btn" id="btnToggleKpisColaboradores" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
			</button>
		</div>

		<div class="card-body" id="colaboradoresKpisContenido">
			<div class="row colaboradores-kpi-row">
				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="colaboradores-kpi colaboradores-kpi-primary">
						<div class="colaboradores-kpi-copy">
							<span>Colaboradores</span>
							<strong id="colaboradoresKpiTotal">0</strong>
							<small>Registros filtrados</small>
						</div>
						<span class="colaboradores-kpi-icon"><i class="fas fa-users"></i></span>
					</div>
				</div>

				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="colaboradores-kpi colaboradores-kpi-success">
						<div class="colaboradores-kpi-copy">
							<span>Activos</span>
							<strong id="colaboradoresKpiActivos">0</strong>
							<small>Actualmente activos</small>
						</div>
						<span class="colaboradores-kpi-icon"><i class="fas fa-user-check"></i></span>
					</div>
				</div>

				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="colaboradores-kpi colaboradores-kpi-danger">
						<div class="colaboradores-kpi-copy">
							<span>Inactivos</span>
							<strong id="colaboradoresKpiInactivos">0</strong>
							<small>Actualmente inactivos</small>
						</div>
						<span class="colaboradores-kpi-icon"><i class="fas fa-user-times"></i></span>
					</div>
				</div>

				<div class="col-xl-3 col-md-6 col-12 mb-3">
					<div class="colaboradores-kpi colaboradores-kpi-purple">
						<div class="colaboradores-kpi-copy">
							<span>Empresas</span>
							<strong id="colaboradoresKpiEmpresas">0</strong>
							<small>Empresas representadas</small>
						</div>
						<span class="colaboradores-kpi-icon"><i class="fas fa-building"></i></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- LISTADO -->
	<section class="card colaboradores-section-card mb-4" id="colaboradoresListadoSection">
		<div class="colaboradores-directory-header">
			<div class="colaboradores-section-title">
				<span class="colaboradores-section-icon"><i class="fas fa-users-cog"></i></span>
				<div>
					<h5 class="mb-0">Colaboradores</h5>
					<small>Empresa, identidad, teléfono, puesto, estado y acciones.</small>
				</div>
			</div>
		</div>

		<div class="card-body">
			<div class="colaboradores-list-toolbar">
				<div class="colaboradores-toolbar-left">
					<button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnColaboradoresActualizar">
						<i class="fas fa-sync-alt mr-1"></i> Actualizar
					</button>
					<button type="button" class="btn btn-primary table_crear ocultar" id="btnColaboradoresIngresar">
						<i class="fas fa-plus mr-1"></i> Ingresar
					</button>
					<button type="button" class="btn btn-success table_reportes ocultar" id="btnColaboradoresExcel">
						<i class="fas fa-file-excel mr-1"></i> Excel
					</button>
					<button type="button" class="btn btn-danger table_reportes ocultar" id="btnColaboradoresPdf">
						<i class="fas fa-file-pdf mr-1"></i> PDF
					</button>
				</div>

				<div class="colaboradores-toolbar-right">
					<label class="colaboradores-page-size mb-0">
						<span>Mostrar</span>
						<select id="colaboradoresPageSize" class="form-control form-control-sm"></select>
						<span>registros</span>
					</label>

					<div class="colaboradores-view-switch">
						<button type="button" class="colaboradores-view-btn active" data-view="detalle">
							<i class="fas fa-list"></i><span>Detalle</span>
						</button>
						<button type="button" class="colaboradores-view-btn" data-view="miniatura">
							<i class="fas fa-th-large"></i><span>Miniatura</span>
						</button>
					</div>

					<div class="colaboradores-search">
						<span class="colaboradores-search-icon"><i class="fas fa-search"></i></span>
						<input type="search" id="buscarColaboradores" class="form-control"
							placeholder="Buscar colaborador..." autocomplete="off">
						<button type="button" id="limpiarBuscarColaboradores" class="colaboradores-search-clear">
							<i class="fas fa-times"></i>
						</button>
					</div>
				</div>
			</div>

			<div id="colaboradoresListado" class="colaboradores-listado vista-detalle"></div>

			<div class="colaboradores-list-footer">
				<span id="colaboradoresInfo">0 registros</span>
				<div id="colaboradoresPaginacion" class="colaboradores-pagination"></div>
			</div>
		</div>

		<div class="card-footer small text-muted">
			<?php
				require_once "./core/mainModel.php";

				$insMainModel = new mainModel();
				$entidad = "colaboradores";

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
	</section>
<?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "colaboradores";

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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Colaboradores");
?>