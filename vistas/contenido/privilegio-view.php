<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Privilegio</li>
    </ol>
	<div class="card mb-4">
		<div class="card-header">
				<i class="fas fa-key fa-lg mr-1"></i>
				Privilegio
		</div>
		<div class="card-body"> 
			<div class="table-responsive">
				<table id="dataTablePrivilegio" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
					<thead>
						<tr>
							<th>Privilegio</th>
							<th>Menu</th>
							<th>Submenu</th>
							<th>Submenu1</th>
							<th>Editar</th>	
							<th>Eliminar</th>
						</tr>
					</thead>
				</table>  
			</div>                   
		</div>
		<div class="card-footer small text-muted">
		<?php
			require_once "./core/mainModel.php";
			
			$insMainModel = new mainModel();
			$entidad = "privilegio";
			
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Privilegios");
?>