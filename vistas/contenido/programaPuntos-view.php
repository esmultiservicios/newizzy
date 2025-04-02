<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Programa Puntos</li>
    </ol>

	<div class="card mb-4">
        <div class="card-body">
            <form class="form-inline" id="form_main_programa_puntos">
             <div class="form-group mx-sm-3 mb-1">
                    <div class="input-group">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <div class="sb-nav-link-icon"></div>Estado
                            </span>
                            <select id="estado_programa_puntos" name="estado_programa_puntos" class="selectpicker" title="Estado" data-live-search="true">
								<option value="" disabled>Seleccione una opción</option>
								<option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
		<div class="card mb-4">
			<div class="card-header">
				<i class="fa-solid fa-money-bill-trend-up fa-lg mr-1"></i>
				Programa Puntos
			</div>
			<div class="card-body"> 
				<div class="table-responsive">
					<table id="dataTableProgramaPuntos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
						<thead>
							<tr>
								<th>Nombre</th>
								<th>Tipo Calculo</th>
								<th>Monto</th>
								<th>Procentaje</th>
								<th>Estado</th>
								<th>Fecha Creación</th>
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
				$entidad = "puestos";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Puestos");
?>