<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/asistencia.css">

<div class="container-fluid asistencia-page">
	<!-- Asistencia -->
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
				<i class="fas fa-user-clock breadcrumb-icon"></i>
				<span>Asistencia</span>
			</li>
		</ol>
	</div>

	<!-- FILTROS -->
	<section class="card asistencia-section-card mb-4">
		<div class="asistencia-section-header">
			<div class="asistencia-section-heading">
				<span class="asistencia-section-icon"><i class="fas fa-filter"></i></span>
				<div>
					<h5>Filtros de asistencia</h5>
					<p>Consulte marcajes por estado, colaborador y período.</p>
				</div>
			</div>

			<button type="button" class="btn btn-primary asistencia-toggle-section" data-target="#asistenciaFiltrosBody" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i>
				<span>Ocultar</span>
			</button>
		</div>

		<div class="asistencia-section-body" id="asistenciaFiltrosBody">
			<form id="form_main_asistencia" autocomplete="off">
				<div class="asistencia-filter-grid">
					<div class="asistencia-filter-field">
						<label for="estado"><i class="fas fa-toggle-on mr-1"></i> Estado</label>
						<select id="estado" name="estado" class="form-control izzy-select2" title="Estado" data-placeholder="Estado">
							<option value="0">Pendiente</option>
							<option value="1">Pagada</option>
						</select>
					</div>

					<div class="asistencia-filter-field">
						<label for="colaborador"><i class="fas fa-user mr-1"></i> Colaborador</label>
						<select id="colaborador" name="colaborador" class="form-control izzy-select2" title="Colaborador" data-placeholder="Todos los colaboradores">
							<option value="">Todos los colaboradores</option>
						</select>
					</div>

					<div class="asistencia-filter-field">
						<label for="fechai"><i class="fas fa-calendar-alt mr-1"></i> Fecha inicio</label>
						<div class="input-group asistencia-date-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
							</div>
							<input type="date" class="form-control" id="fechai" name="fechai" value="<?php
								$fecha = date("Y-m-d");
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								echo htmlspecialchars($fecha_inicial, ENT_QUOTES, 'UTF-8');
							?>">
						</div>
					</div>

					<div class="asistencia-filter-field">
						<label for="fechaf"><i class="fas fa-calendar-check mr-1"></i> Fecha fin</label>
						<div class="input-group asistencia-date-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
							</div>
							<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>

					<div class="asistencia-filter-actions">
						<button type="submit" class="btn btn-primary" id="search">
							<i class="fas fa-filter mr-1"></i> Filtrar
						</button>

						<button type="reset" class="btn btn-info" id="btnAsistenciaLimpiar">
							<i class="fas fa-broom mr-1"></i> Limpiar
						</button>
					</div>
				</div>
			</form>
		</div>
	</section>

	<!-- KPIs -->
	<section class="card asistencia-section-card mb-4">
		<div class="asistencia-section-header">
			<div class="asistencia-section-heading">
				<span class="asistencia-section-icon"><i class="fas fa-chart-pie"></i></span>
				<div>
					<h5>Indicadores</h5>
					<p>Resumen de los registros que cumplen los filtros actuales.</p>
				</div>
			</div>

			<button type="button" class="btn btn-primary asistencia-toggle-section" data-target="#asistenciaKpisBody" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i>
				<span>Ocultar</span>
			</button>
		</div>

		<div class="asistencia-section-body" id="asistenciaKpisBody">
			<div class="asistencia-kpi-grid">
				<article class="asistencia-kpi asistencia-kpi-primary">
					<div>
						<span>REGISTROS</span>
						<strong id="asistenciaKpiRegistros">0</strong>
						<small>Asistencias encontradas</small>
					</div>
					<i class="fas fa-clipboard-list"></i>
				</article>

				<article class="asistencia-kpi asistencia-kpi-success">
					<div>
						<span>CON ENTRADA</span>
						<strong id="asistenciaKpiEntrada">0</strong>
						<small>Marcajes con hora de inicio</small>
					</div>
					<i class="fas fa-sign-in-alt"></i>
				</article>

				<article class="asistencia-kpi asistencia-kpi-info">
					<div>
						<span>CON SALIDA</span>
						<strong id="asistenciaKpiSalida">0</strong>
						<small>Marcajes con hora de salida</small>
					</div>
					<i class="fas fa-sign-out-alt"></i>
				</article>

				<article class="asistencia-kpi asistencia-kpi-purple">
					<div>
						<span>HORAS</span>
						<strong id="asistenciaKpiHoras">0:00</strong>
						<small>Total registrado</small>
					</div>
					<i class="fas fa-clock"></i>
				</article>
			</div>
		</div>
	</section>

	<!-- LISTADO -->
	<section class="card asistencia-section-card mb-4">
		<div class="asistencia-section-header">
			<div class="asistencia-section-heading">
				<span class="asistencia-section-icon"><i class="fas fa-user-clock"></i></span>
				<div>
					<h5>Asistencia</h5>
					<p>Marcajes, horas trabajadas, comentarios y acciones.</p>
				</div>
			</div>

			<button type="button" class="btn btn-primary asistencia-toggle-section" data-target="#asistenciaListadoBody" aria-expanded="true">
				<i class="fas fa-chevron-up mr-1"></i>
				<span>Ocultar</span>
			</button>
		</div>

		<div class="asistencia-section-body" id="asistenciaListadoBody">
			<div class="asistencia-toolbar">
				<div class="asistencia-toolbar-left">
					<button type="button" class="btn btn-info table_actualizar ocultar" id="btnAsistenciaActualizar">
						<i class="fas fa-sync-alt mr-1"></i> Actualizar
					</button>

					<button type="button" class="btn btn-primary table_crear ocultar" id="btnAsistenciaIngresar">
						<i class="fas fa-plus mr-1"></i> Ingresar
					</button>

					<button type="button" class="btn btn-success table_reportes ocultar" id="btnAsistenciaExcel">
						<i class="fas fa-file-excel mr-1"></i> Excel
					</button>

					<button type="button" class="btn btn-danger table_reportes ocultar" id="btnAsistenciaPdf">
						<i class="fas fa-file-pdf mr-1"></i> PDF
					</button>
				</div>

				<div class="asistencia-toolbar-right">
					<label class="asistencia-page-size mb-0">
						<span>Mostrar</span>
						<select id="asistenciaPageSize" class="form-control form-control-sm izzy-select2">
							<option value="10">10</option>
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
						<span>registros</span>
					</label>

					<div class="asistencia-view-switch" role="group" aria-label="Vista de asistencia">
						<button type="button" class="asistencia-view-btn active" data-view="detalle" aria-pressed="true">
							<i class="fas fa-list"></i><span>Detalle</span>
						</button>
						<button type="button" class="asistencia-view-btn" data-view="miniatura" aria-pressed="false">
							<i class="fas fa-th-large"></i><span>Miniatura</span>
						</button>
					</div>

					<div class="asistencia-search-wrap">
						<span class="asistencia-search-icon"><i class="fas fa-search"></i></span>
						<input type="search" id="asistenciaBuscar" class="form-control" placeholder="Buscar asistencia..." autocomplete="off">
						<button type="button" id="asistenciaBuscarLimpiar" class="asistencia-search-clear" aria-label="Limpiar búsqueda">
							<i class="fas fa-times"></i>
						</button>
					</div>
				</div>
			</div>

			<div class="asistencia-detail-header" aria-hidden="true">
				<div>Acciones</div>
				<div>Colaborador</div>
				<div>Fecha</div>
				<div>Entrada</div>
				<div>Salida</div>
				<div>Horas</div>
				<div>Comentario</div>
			</div>

			<div id="asistenciaListado" class="asistencia-listado vista-detalle"></div>

			<div class="asistencia-list-footer">
				<span id="asistenciaInfo">0 registros</span>
				<div id="asistenciaPaginacion" class="asistencia-pagination"></div>
			</div>
		</div>

		<div class="card-footer small text-muted asistencia-last-update">
			<?php
				require_once "./core/mainModel.php";

				$insMainModel = new mainModel();
				$entidad = "asistencia";

				if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
					$consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
					$fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
					$hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
					echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
				}else{
					echo "No se encontraron registros";
				}
			?>
		</div>
	</section>
</div>

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Asistencia");
?>
