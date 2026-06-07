<div class="container-fluid cuentas-premium-page">
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
    
    <div class="card mb-4 cuentas-filter-card">
        <div class="card-body">
            <form id="formMainCuentasContabilidad">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-search mr-1"></i> Buscar cuenta
                            </label>

                            <input type="text" class="form-control" id="buscar_cuenta" name="buscar_cuenta" placeholder="Nombre, código o saldo">
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>

                            <select id="estado_cuentasContabilidad" name="estado_cuentasContabilidad" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-seedling mr-1"></i> Tipo
                            </label>

                            <select id="tipo_cuenta" name="tipo_cuenta" class="form-control selectpicker" title="Tipo" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="normal">Normal</option>
                                <option value="inversion">Inversión</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-balance-scale mr-1"></i> Saldo
                            </label>

                            <select id="tipo_saldo" name="tipo_saldo" class="form-control selectpicker" title="Saldo" data-live-search="true">
                                <option value="" selected>Todos</option>
                                <option value="positivo">Positivo</option>
                                <option value="negativo">Negativo</option>
                                <option value="cero">En cero</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-sort-amount-down mr-1"></i> Ordenar por
                            </label>

                            <select id="orden_cuentas" name="orden_cuentas" class="form-control selectpicker" title="Ordenar" data-live-search="true">
                                <option value="neto_desc" selected>Mayor saldo total</option>
                                <option value="neto_asc">Menor saldo total</option>
                                <option value="nombre_asc">Nombre A-Z</option>
                                <option value="nombre_desc">Nombre Z-A</option>
                                <option value="ingreso_desc">Mayor ingreso</option>
                                <option value="egreso_desc">Mayor egreso</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-alt mr-1"></i> Fecha Inicio
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
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
                    
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-check mr-1"></i> Fecha Fin
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>

                                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d');?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-4 col-md-12 col-sm-12 mb-3">
                        <div class="cuentas-toolbar">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter fa-lg"></i> Filtrar
                            </button>

                            <button type="reset" class="btn btn-secondary" id="btnLimpiarCuentas">
                                <i class="fas fa-broom fa-lg"></i> Limpiar
                            </button>  
                        </div>                      
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 cuentas-summary-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="cuentas-summary-title">
                <i class="fas fa-wallet"></i>
                Resumen de Cuentas
            </h5>

            <div class="cuentas-summary-actions">
                <button class="btn btn-sm btn-primary" onclick="modal_cuentas_contables()">
                    <i class="fas fa-plus mr-1"></i> Nueva Cuenta
                </button>

                <button class="btn btn-sm btn-secondary" onclick="listar_cuentas_contabilidad()">
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