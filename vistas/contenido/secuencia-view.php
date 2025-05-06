<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Secuencia Facturación</li>
    </ol>

	<div class="card mb-4">
        <div class="card-body">
            <form id="form_main_secuencia">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Estado</label>
                            <select id="estado_secuencia" name="estado_secuencia" class="form-control selectpicker" title="Estado" data-live-search="true">
								<option value="1">Activo</option>
								<option value="0">Inactivo</option>
                            </select>
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
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-sliders-h fa-lg mr-1"></i>
				Secuencia Facturación
			</div>
			<div class="card-body"> 
				<div class="table-responsive">
					<table id="dataTableSecuencia" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
						<thead>
							<tr>
								<th>Empresa</th>
								<th>Documento</th>
								<th>CAI</th>
								<th>Prefijo</th>
								<th>Siguiente</th>
								<th>Rango Inicial</th>
								<th>Rango Final</th>
								<th>Fecha Limite</th>
								<th>Estado</th>
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
				$entidad = "secuencia_facturacion";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Secuencia de Facturación");
?>