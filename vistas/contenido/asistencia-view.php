<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo SERVERURL; ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Asistencia</li>
    </ol>
	<div class="card mb-4">
        <div class="card-body">
			<form class="form-inline" id="form_main_asistencia" action="<?php echo SERVERURL;?>ajax/addAsistenciaAjax.php">
				<div class="form-group mx-sm-3 mb-1">
					<div class="input-group">
						<div class="input-group-append">
							<span class="input-group-text"><div class="sb-nav-link-icon"></div>Estado</span>
							<select id="estado" name="estado" class="selectpicker" title="Estado" data-live-search="true">
								<option value="0">Pendiente</option>
								<option value="1">Pagada</option>
							</select>
						</div>	
					</div>
				</div>	
				<div class="form-group mx-sm-3 mb-1">
					<div class="input-group">
						<div class="input-group-append">
							<span class="input-group-text"><div class="sb-nav-link-icon"></div>Colaborador</span>
							<select id="colaborador" name="colaborador" class="selectpicker" title="Colaborador" data-live-search="true" required>
								<option value="">Seleccione</option>
								</select>
						</div>	
					</div>
				</div>
				<div class="form-group mx-sm-3 mb-1">
					<div class="input-group">				
						<div class="input-group-append">				
							<span class="input-group-text"><div class="sb-nav-link-icon"></div>Fin</span>
						</div>
						<input type="date" required id="fechai" name="fechai" value="<?php echo date ("Y-m-d");?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Fin" style="width:165px;">
					</div>
				  </div>																		
				<div class="form-group mx-sm-3 mb-1">
				 	<div class="input-group">				
						<div class="input-group-append">				
							<span class="input-group-text"><div class="sb-nav-link-icon"></div>Fecha</span>
						</div>
						<input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Fin" style="width:165px;">
					</div>
				</div> 				  
			</form>          
        </div>
    </div>		
    <div class="card mb-4">
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-user-clock fa-lg mr-1"></i>
				Asistencia
			</div>
			<div class="card-body"> 
				<div class="table-responsive">
					<table id="dataTableAsistencia" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
						<thead>
							<tr>								
								<th>Colaborador</th>								
								<th>Fecha</th>
								<th>Hora Entrada</th>
								<th>Hora Salida</th>
								<th>Horas Trabajadas</th>
								<th>Comentario</th>
								<th>Editar</th>
								<th>Eliminar Salida</th>
								<th>Eliminar Marcaje</th>
							</tr>
						</thead>
					</table>  
				</div>                   
				</div>
			<div class="card-footer small text-muted">
			<?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "clientes";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Clientes");
?>