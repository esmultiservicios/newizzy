<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        </li>
        <li class="breadcrumb-item active">Contrato</li>
    </ol>
    <div class="card mb-4">
        <div class="card-body">
            <form class="form-inline" id="form_main_contrato">
                <div class="form-row">
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Estado
                                </span>
                            </div>
                            <select id="estado" name="estado" class="selectpicker " data-toggle="tooltip"
                                title="Tipo Contrato" data-live-search="true">
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Tipo Contrato
                                </span>
                                <select id="tipo_contrato" name="tipo_contrato" class="selectpicker"
                                    title="Tipo de Contrato" data-live-search="true">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Pago Planificado
                                </span>
                                <select id="pago_planificado" name="pago_planificado" class="selectpicker"
                                    title="Pago Planificado" data-live-search="true">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Tipo Empleado
                                </span>
                                <select id="tipo_empleado" name="tipo_empleado" class="selectpicker"
                                    title="Tipo Empleado" data-live-search="true">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
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