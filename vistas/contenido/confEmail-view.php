<div class="container-fluid">
	<!-- Configurar Correos -->
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
				<i class="fas fa-envelope breadcrumb-icon"></i>
				<span>Configurar Correos</span>
			</li>
		</ol>
	</div>

    <div class="card mb-4">
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-envelope fa-lg mr-1"></i>
				Configurar Correos
			</div>
			<div class="card-body"> 
				<div class="table-responsive">
					<table id="dataTableConfCorreos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
						<thead>
							<tr>
							    <th>Tipo Correo</th>
							    <th>Servidor</th>
								<th>Correo</th>	
								<th>Puerto</th>
								<th>SMTPSecure</th>														
								<th>Editar</th>
							</tr>
						</thead>
					</table>  
				</div>                   
				</div>
			<div class="card-footer small text-muted">
 			<?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "correo";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Medidas");
?>