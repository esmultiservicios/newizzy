<div class="container-fluid">
    <!-- Breadcrumb (se mantiene igual) -->
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
    
    <!-- Cards de Métricas - Versión Mejorada -->
    <div class="row mb-4">
        <!-- Card Clientes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>clientes/" class="card-link">
                <div class="card dashboard-card bg-gradient-primary hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Clientes</h6>
                                <h2 class="mb-0" id="main_clientes">0</h2>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="card-footer-text">
                                <i class="fas fa-info-circle mr-1"></i> Nuestros Clientes
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card Proveedores -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>proveedores/" class="card-link">
                <div class="card dashboard-card bg-gradient-success hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Proveedores</h6>
                                <h2 class="mb-0" id="main_proveedores">0</h2>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-user-alt"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="card-footer-text">
                                <i class="fas fa-info-circle mr-1"></i> Nuestros Proveedores
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card Facturas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/" class="card-link">
                <div class="card dashboard-card bg-gradient-warning hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Facturas</h6>
                                <h2 class="mb-0" id="main_facturas">0</h2>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="card-footer-text" id="mes_factura">
                                <i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card Compras -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/" class="card-link">
                <div class="card dashboard-card bg-gradient-danger hover-effect">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Compras</h6>
                                <h2 class="mb-0" id="main_compras">0</h2>
                            </div>
                            <div class="icon-circle">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="card-footer-text" id="mes_compra">
                                <i class="fas fa-calendar-alt mr-1"></i> <?= date('F Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
	<!-- Sección de Gráficos Mejorada -->
	<div class="row mb-4">
		<!-- Gráfico Ventas - Versión Premium -->
		<div class="col-xl-6 col-md-12 mb-4">
			<div class="chart-card h-100">
				<div class="chart-header">
					<h3 class="chart-title">
						<i class="fas fa-chart-bar"></i>
						Reporte Ventas
					</h3>
					<div class="chart-actions">
						<div class="year-selector btn-group btn-group-sm">
							<button class="btn btn-year-ventas active" data-year="<?php echo date("Y"); ?>">
								<?php echo date("Y"); ?>
							</button>
							<button class="btn btn-year-ventas" data-year="<?php echo date("Y")-1; ?>">
								<?php echo date("Y")-1; ?>
							</button>
						</div>
						<a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/" 
						class="chart-btn" 
						title="Ver reporte completo">
							<i class="fas fa-arrow-right"></i>
						</a>
						<button class="chart-btn download-ventas" title="Descargar gráfico">
							<i class="fas fa-download"></i>
						</button>
					</div>
				</div>
				<div class="chart-container bar-chart">
					<canvas id="graphVentas" height="250"></canvas>
				</div>
				<div class="chart-legend" id="ventas-legend">
					<!-- Leyenda generada dinámicamente -->
				</div>
			</div>
		</div>
		
		<!-- Gráfico Compras - Versión Premium -->
		<div class="col-xl-6 col-md-12 mb-4">
			<div class="chart-card h-100">
				<div class="chart-header">
					<h3 class="chart-title">
						<i class="fas fa-chart-bar"></i>
						Reporte Compras
					</h3>
					<div class="chart-actions">
						<div class="year-selector btn-group btn-group-sm">
							<button class="btn btn-year-compras active" data-year="<?php echo date("Y"); ?>">
								<?php echo date("Y"); ?>
							</button>
							<button class="btn btn-year-compras" data-year="<?php echo date("Y")-1; ?>">
								<?php echo date("Y")-1; ?>
							</button>
						</div>
						<a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/" 
						class="chart-btn" 
						title="Ver reporte completo">
							<i class="fas fa-arrow-right"></i>
						</a>
						<button class="chart-btn download-compras" title="Descargar gráfico">
							<i class="fas fa-download"></i>
						</button>
					</div>
				</div>
				<div class="chart-container bar-chart">
					<canvas id="graphCompras" height="250"></canvas>
				</div>
				<div class="chart-legend" id="compras-legend">
					<!-- Leyenda generada dinámicamente -->
				</div>
			</div>
		</div>
		
		<!-- Gráfico Top Productos - Versión Premium -->
		<div class="col-12 mb-4">
			<div class="chart-card">
				<div class="chart-header">
					<h3 class="chart-title">
						<i class="fas fa-star"></i>
						Top 5 Productos Más Vendidos en 3 Meses
					</h3>
					<div class="chart-actions">
						<div class="year-selector btn-group btn-group-sm">
							<button class="btn btn-year-productos active" data-months="3">
								Últimos 3 Meses
							</button>
							<button class="btn btn-year-productos" data-months="6">
								Últimos 6 Meses
							</button>
						</div>
						<a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/" 
						class="chart-btn" 
						title="Ver reporte completo">
							<i class="fas fa-arrow-right"></i>
						</a>
						<button class="chart-btn download-top-productos" title="Descargar gráfico">
							<i class="fas fa-download"></i>
						</button>
					</div>
				</div>
				<div class="chart-container bar-chart">
					<canvas id="graphTopProductosporAno" height="120"></canvas>
				</div>
				<div class="chart-legend" id="top-products-legend">
					<!-- La leyenda se generará dinámicamente con JavaScript -->
				</div>
			</div>
		</div>

	</div>
    
    <!-- Tabla Documentos Fiscales -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-sliders-h mr-1"></i>
                            Documentos Fiscales
                        </h6>
                        <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>secuencia/" class="btn btn-sm btn-link">
                            Ver más <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dataTableSecuenciaDashboard" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Documento</th>
                                    <th>Rango Inicio</th>
                                    <th>Rango Fin</th>    
                                    <th>Actual</th>                                        
                                    <th>Fecha Expiración</th>                    
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
    </div>
</div>