<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        </li>
        <li class="breadcrumb-item active">Contrato</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body">
            <form id="form_main_contrato">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Estado</label>
                            <select id="estado" name="estado" 
                                class="form-control selectpicker" title="Tipo Contrato" data-live-search="true">
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Tipo Contrato</label>
                            <select id="tipo_contrato" name="tipo_contrato" 
                                class="form-control selectpicker" title="Tipo de Contrato" data-live-search="true">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Pago Planificado</label>
                            <select id="pago_planificado" name="pago_planificado" 
                                class="form-control selectpicker" title="Pago Planificado" data-live-search="true">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Tipo Empleado</label>
                            <select id="tipo_empleado" name="tipo_empleado" 
                                class="form-control selectpicker" title="Tipo Empleado" data-live-search="true">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search fa-lg mr-1"></i> Buscar
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
                <i class="fas fa-file-signature fa-lg mr-1"></i>
                Contrato
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dataTableContrato" class="table table-header-gradient table-striped table-condensed table-hover"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Tipo Empleado</th>
                                <th>Empleado</th>
                                <th>Tipo Contrato</th>
                                <th>Pago Planificado</th>
                                <th>Salario</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Notas</th>
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
				$entidad = "contrato";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Contratos");
?>