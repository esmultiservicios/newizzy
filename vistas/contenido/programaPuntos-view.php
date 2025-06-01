<!--programaPuntos-view.php-->
<div class="container-fluid">
    <!-- Programa de Puntos -->
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
            <i class="fas fa-gift breadcrumb-icon"></i>
            <span>Programa Puntos</span>
            </li>
        </ol>
    </div>


	<div class="card mb-4">
		<div class="card-body">
			<form id="form_main_programa_puntos">
				<div class="row">
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1">Estado</label>
							<select id="estado_programa_puntos" name="estado_programa_puntos" 
								class="form-control selectpicker" title="Estado" data-live-search="true">
								<option value="" disabled>Seleccione una opción</option>
								<option value="">Todos</option>
								<option value="1">Activo</option>
								<option value="0">Inactivo</option>
							</select>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-12 text-right">
						<button type="submit" class="btn btn-primary mr-2" id="search">
							<i class="fas fa-filter fa-lg mr-1"></i> Filtrar
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


<!--INICIO MODAL PROGRAMA PUNTOS-->
<div class="modal fade" id="modalProgramaPuntos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Programa de Puntos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formProgramaPuntos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="programa_puntos_id" name="programa_puntos_id">
                    
                    <!-- Sección de Configuración del Programa -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Configuración del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Columna para el nombre del programa -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre"><i class="fas fa-tag mr-1"></i>Nombre del Programa <span class="priority">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del Programa" required>
                                        <small class="form-text text-muted">Ingrese un nombre descriptivo para el programa</small>
                                    </div>
                                </div>
                                
                                <!-- Columna para el tipo de cálculo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_calculo"><i class="fas fa-calculator mr-1"></i>Tipo de Cálculo <span class="priority">*</span></label>
                                        <select id="tipo_calculo" name="tipo_calculo" required class="selectpicker form-control" data-live-search="true" title="Seleccione un tipo de cálculo">
                                            <option value="" disabled>Seleccione una opción</option>
                                            <option value="monto">Por Monto</option>
                                            <option value="porcentaje">Por Porcentaje</option>
                                        </select>
                                        <small class="form-text text-muted">Seleccione cómo se calcularán los puntos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Cálculo de Puntos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Cálculo de Puntos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group" id="calculo_monto" style="display: none;">
                                <label for="monto"><i class="fas fa-money-bill-wave mr-1"></i>Monto en Lempiras para 1 punto</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="monto" name="monto" placeholder="Ejemplo: 25">
                                    <div class="input-group-append">
                                        <span class="input-group-text">L.</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el monto en Lempiras equivalente a 1 punto</small>
                            </div>
                            
                            <div class="form-group" id="calculo_porcentaje" style="display: none;">
                                <label for="porcentaje"><i class="fas fa-percent mr-1"></i>Porcentaje del Consumo</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="porcentaje" name="porcentaje" placeholder="Ejemplo: 10" min="0" max="100">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el porcentaje del consumo que se convertirá en puntos</small>
                            </div>                
                            
                            <div id="ejemplo_calculo" class="form-group" style="display: none;">
                                <div class="alert alert-info">
                                    <p class="mb-0"><i class="fas fa-info-circle mr-2"></i><strong>Ejemplo:</strong> <span id="ejemploTexto"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Estado del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ProgramaPuntos_activo" name="ProgramaPuntos_activo" checked>
                                <label class="custom-control-label" for="ProgramaPuntos_activo">Programa Activo</label>
                            </div>
                            <small class="form-text text-muted">Active o desactive el programa de puntos en el sistema</small>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    Cancelar
                </button>
                <button class="btn btn-primary" type="submit" id="reg_ProgramaPuntos" form="formProgramaPuntos">
                    Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_ProgramaPuntos" form="formProgramaPuntos">
                    Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_ProgramaPuntos" form="formProgramaPuntos">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PROGRAMA PUNTOS-->

<!--INICIO MODAL HISTORICO PROGRAMA PUNTOS-->
<div class="modal fade" id="modalHistoricoPuntos" tabindex="-1" role="dialog" aria-labelledby="modalHistoricoPuntosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalHistoricoPuntosLabel"><i class="fas fa-history mr-2"></i>Historial de Puntos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Detalle de acumulación y redención de puntos</h5>
                    </div>
                    <div class="card-body"> 
                        <div class="table-responsive">
                            <table id="tablaHistoricoPuntos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user mr-1"></i> Cliente</th>
                                        <th><i class="fas fa-exchange-alt mr-1"></i> Tipo Movimiento</th>
                                        <th><i class="fas fa-star mr-1"></i> Puntos</th>
                                        <th><i class="fas fa-align-left mr-1"></i> Descripción</th>
                                        <th><i class="fas fa-calendar-alt mr-1"></i> Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán aquí -->
                                </tbody>
                            </table>  
                        </div>                   
                    </div>
                    <div class="card-footer small text-muted">
                        <i class="fas fa-clock mr-1"></i> Última actualización: <span id="fecha-actualizacion"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL HISTORICO PROGRAMA PUNTOS-->

<?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Programa de Puntos");
?>