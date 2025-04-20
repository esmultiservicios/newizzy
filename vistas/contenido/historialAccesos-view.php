	<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Historial de Accesos</li>
    </ol>

	<div class="card mb-4">
		<div class="card-body">
			<form id="formMainHistorialAcceso" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
				<div class="row align-items-end">
					<!-- Campos de fecha -->
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Fecha Inicio</label>
							<input type="date" required id="fechai" name="fechai" value="<?php 
								$fecha = date ("Y-m-d");
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								echo $fecha_inicial;
							?>" class="form-control" title="Fecha Inicio">
						</div>
					</div>
					
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Fecha Fin</label>
							<input type="date" required id="fechaf" name="fechaf" 
								value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
						</div>
					</div>
					
					<!-- Botones alineados a la derecha -->
					<div class="col-md-6 col-sm-12 d-flex align-items-end justify-content-end mb-3">
						<button type="submit" class="btn btn-primary mr-2">
							<i class="fas fa-history fa-lg mr-1"></i> Consultar Historial
						</button>
						<button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
							<i class="fas fa-broom fa-lg mr-1"></i> Limpiar
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-sliders-h fa-lg mr-1"></i>
            Historial de Accesos
        </div>
        <div class="card-body"> 
            <div class="table-responsive">
                <table id="dataTableHistorialAccesos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Colaborador</th>
                            <th>IP</th>
                            <th>Acceso</th>						
                        </tr>
                    </thead>
                </table>  
            </div>                   
            </div>
        <div class="card-footer small text-muted">
			<?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				
				if($insMainModel->getlastUpdateHistorialAccessos()->num_rows > 0){
					$consulta_last_update = $insMainModel->getlastUpdateHistorialAccessos()->fetch_assoc();					
					$fecha_registro = htmlspecialchars($consulta_last_update['fecha'], ENT_QUOTES, 'UTF-8');
					$hora = htmlspecialchars(date('g:i:s a',strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');;
									
					echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');;						
				}else{
					echo "No se encontraron registros ";
				}	
			?>
        </div>
    </div>
</div>
<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Historial de Accesos");
?>