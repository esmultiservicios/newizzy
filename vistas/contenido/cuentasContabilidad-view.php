<div class="container-fluid">
    <!-- Cuentas -->
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
                <i class="fas fa-wallet breadcrumb-icon"></i>
                <span>Cuentas</span>
            </li>
        </ol>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form id="formMainCuentasContabilidad">
                <div class="row">
                    <div class="col-md-3 col-sm-3 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Estado</label>
                            <select id="estado_cuentasContabilidad" name="estado_cuentasContabilidad" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

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
                    
                    <div class="col-md-3 col-sm-3 mb-3">
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-wallet mr-2"></i>
                Resumen de Cuentas
            </div>
            <div>
                <button class="btn btn-sm btn-primary" onclick="modal_cuentas_contables()">
                    <i class="fas fa-plus mr-1"></i> Nueva Cuenta
                </button>
                <button class="btn btn-sm btn-secondary ml-2" onclick="listar_cuentas_contabilidad()">
                    <i class="fas fa-sync-alt mr-1"></i> Actualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="cuentas-container" class="row"></div>
        </div>
        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                
                $insMainModel = new mainModel();
                $entidad = "cuentas";
                
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
$insMainModel->guardar_historial_accesos("Ingreso al modulo Cuentas Cuentas Contabilidad");
?>