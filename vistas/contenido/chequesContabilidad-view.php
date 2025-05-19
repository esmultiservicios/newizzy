	<div class="container-fluid">
	<!-- Cheques -->
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
				<i class="fas fa-money-check breadcrumb-icon"></i>
				<span>Cheques</span>
			</li>
		</ol>
	</div>

	<div class="card mb-4">
		<div class="card-body">
			<form id="formMainChequesContabilidad">
				<div class="row">
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
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
					
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Fecha Fin</label>
							<div class="input-group">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
								</div>
								<input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
							</div>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-12 text-right">
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

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-money-check fa-lg mr-1"></i>
            Cheques
        </div>
        <div class="card-body"> 
            <div class="table-responsive">
                <table id="dataTableChequesContabilidad" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
							<th>Proveedor</th>
							<th>Factura</th>
                            <th>Importe</th>	
                            <th>Codigo</th>		
                            <th>Nombre</th>	
                            <th>Observacion</th>						
                        </tr>
                    </thead>
                </table>  
            </div>                   
            </div>
        <div class="card-footer small text-muted">
 			<?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "egresos";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Cuentas por Pagar Proveedores");
?>