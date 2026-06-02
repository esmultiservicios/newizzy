<div class="container-fluid">
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
                <i class="fas fa-cash-register breadcrumb-icon"></i>
                <span>Cajas</span>
            </li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="formMainCajas">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Estado</label>
                            <select id="estado_cajas" name="estado_cajas" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="0">Todas</option>
                                <option value="1">Activas</option>
                                <option value="2">Cerrada</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Inicial</label>
                            <input type="date" class="form-control" id="fecha_cajas" name="fecha_cajas" value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Final</label>
                            <input type="date" class="form-control" id="fecha_cajas_f" name="fecha_cajas_f" value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-right">
                        <button type="button" class="btn btn-info mr-2" id="btnGananciaPeriodo">
                            <i class="fas fa-chart-pie fa-lg"></i> Ganancia del Período
                        </button>

                        <button type="button" class="btn btn-warning mr-2" id="btnRetirosPeriodo">
                            <i class="fas fa-money-bill-wave fa-lg"></i> Retiros del Período
                        </button>

                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter fa-lg"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cash-register fa-lg mr-1"></i>
            Cajas
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableCajas" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                </table>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "facturas";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Cajas");
?>