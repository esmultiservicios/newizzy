<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IZZY | Cuentas por Pagar Proveedores</title>
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/cuentas_pagar_proveedores.css">
</head>
<body>

<div class="container-fluid rv-page cxp-page">
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
                <i class="fas fa-file-invoice-dollar breadcrumb-icon"></i>
                <span>Cuentas por Pagar Proveedores</span>
            </li>
        </ol>
    </div>

    <section class="rv-section-card" id="cxpFiltrosSection">
        <div class="rv-section-header">
            <div>
                <h5><i class="fas fa-filter mr-1"></i> Filtros de cuentas por pagar</h5>
                <small>Consulte por estado, proveedor y período sin alterar la información original.</small>
            </div>
            <button type="button" class="btn rv-toggle-section cxp-toggle-section" data-target="#cxpFiltrosBody">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>
        <div class="rv-section-body" id="cxpFiltrosBody">
            <form id="form_main_pagar_proveedores">
					<div class="row">
						<div class="col-md-3 col-sm-6 mb-3">
							<div class="form-group">
								<label class="small mb-1">Estado</label>
								<select id="pagar_proveedores_estado" name="pagar_proveedores_estado" class="form-control selectpicker" title="Estado" data-live-search="true">
									<option value="1">Pendientes</option>
									<option value="2">Pagadas</option>
								</select>
							</div>
						</div>
						
						<div class="col-md-3 col-sm-6 mb-3">
							<div class="form-group">
								<label class="small mb-1">Proveedores</label>
								<select id="pagar_proveedores" name="pagar_proveedores" 
									class="form-control selectpicker" title="Proveedores" data-live-search="true">
									<option value="">Seleccione</option>
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
							<button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                           		<i class="fas fa-broom fa-lg"></i> Limpiar
                        	</button>     							
						</div>
					</div>
				</form>
        </div>
    </section>

    <section class="rv-section-card" id="cxpKpisSection">
        <div class="rv-section-header">
            <div>
                <h5><i class="fas fa-chart-pie mr-1"></i> Indicadores</h5>
                <small>Resumen gerencial de las cuentas por pagar filtradas.</small>
            </div>
            <button type="button" class="btn rv-toggle-section cxp-toggle-section" data-target="#cxpKpisBody">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="rv-section-body" id="cxpKpisBody">
            <div class="rv-kpi-grid">
                <div class="rv-kpi cxp-kpi-registros">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-file-invoice-dollar"></i> Registros</span>
                        <strong id="cxpKpiRegistros">0</strong>
                        <small>Cuentas filtradas</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>

                <div class="rv-kpi cxp-kpi-credito">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-coins"></i> Crédito</span>
                        <strong id="cxpKpiCredito">L. 0.00</strong>
                        <small>Total comprometido</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-coins"></i></div>
                </div>

                <div class="rv-kpi cxp-kpi-abonos">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-hand-holding-usd"></i> Abonos</span>
                        <strong id="cxpKpiAbonos">L. 0.00</strong>
                        <small>Total pagado</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
                </div>

                <div class="rv-kpi rv-kpi-primary cxp-kpi-saldo">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-wallet"></i> Saldo pendiente</span>
                        <strong id="cxpKpiSaldo">L. 0.00</strong>
                        <small>Obligación pendiente</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-wallet"></i></div>
                </div>

                <div class="rv-kpi cxp-kpi-recuperacion">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-chart-line"></i> Pagado</span>
                        <strong id="cxpKpiPagado">0.00%</strong>
                        <small>Porcentaje cancelado</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-chart-line"></i></div>
                </div>

                <div class="rv-kpi cxp-kpi-promedio">
                    <div class="rv-kpi-copy">
                        <span class="rv-kpi-label"><i class="fas fa-calculator"></i> Saldo promedio</span>
                        <strong id="cxpKpiPromedio">L. 0.00</strong>
                        <small>Promedio por cuenta</small>
                    </div>
                    <div class="rv-kpi-icon"><i class="fas fa-calculator"></i></div>
                </div>
            </div>
        </div>
    </section>

    <section class="rv-section-card" id="cxpListadoSection">
        <div class="rv-section-header">
            <div>
                <h5><i class="fas fa-file-invoice-dollar mr-1"></i> Cuentas por Pagar Proveedores</h5>
                <small>Crédito, abonos, saldo y acciones disponibles según permisos.</small>
            </div>
            <button type="button" class="btn rv-toggle-section cxp-toggle-section" data-target="#cxpListadoBody">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="rv-section-body" id="cxpListadoBody">
            <div class="rv-list-toolbar">
                <div class="rv-toolbar-left">
                    <button type="button" id="cxpBtnActualizar" class="btn btn-info table_actualizar ocultar">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button type="button" id="cxpBtnExcel" class="btn btn-success table_reportes ocultar">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" id="cxpBtnPdf" class="btn btn-danger table_reportes ocultar">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>

                <div class="rv-toolbar-right">
                    <label class="rv-page-size">Mostrar
                        <select id="cxpPageSize" class="form-control form-control-sm">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        registros
                    </label>

                    <div class="rv-view-switch">
                        <button type="button" class="rv-view-btn active" data-cxp-view="detalle">
                            <i class="fas fa-list"></i> Detalle
                        </button>
                        <button type="button" class="rv-view-btn" data-cxp-view="miniatura">
                            <i class="fas fa-th-large"></i> Miniatura
                        </button>
                    </div>

                    <div class="rv-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="search" id="cxpSearch" class="form-control form-control-sm" placeholder="Buscar...">
                        <button type="button" id="cxpSearchClear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="cxpListado" class="rv-list"></div>
            <div id="cxpTotales" class="rv-total-row"></div>

            <div class="rv-list-footer">
                <span id="cxpInfo">0 registros</span>
                <div id="cxpPagination" class="rv-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted rv-last-update">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "pagar_proveedores";

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
    </section>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Cuentas por Pagar Proveedores");
?>

</body>
</html>
