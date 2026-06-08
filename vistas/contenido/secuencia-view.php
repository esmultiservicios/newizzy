<div class="container-fluid">
	<!-- Secuencia Facturación -->
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
				<i class="fas fa-file-invoice breadcrumb-icon"></i>
				<span>Secuencia Facturación</span>
			</li>
		</ol>
	</div>

	<!-- Filtros -->
	<div class="card mb-4 secuencia-filtro-card">
        <div class="card-body">
            <form id="form_main_secuencia">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_secuencia_main" name="estado_secuencia_main" class="form-control selectpicker" title="Estado" data-live-search="true">
								<option value="1">Activo</option>
								<option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter">
                                <i class="fas fa-file-alt mr-1"></i> Documento
                            </label>
                            <select id="documento_secuencia_main" name="documento_secuencia_main" class="form-control selectpicker" title="Documento" data-live-search="true">
                                <option value="">Todos</option>
                                <option value="factura">Factura</option>
                                <option value="proforma">Proforma</option>
                                <option value="comprobante">Comprobante</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter">
                                <i class="fas fa-calendar-times mr-1"></i> Vencimiento
                            </label>
                            <select id="vencimiento_secuencia_main" name="vencimiento_secuencia_main" class="form-control selectpicker" title="Vencimiento" data-live-search="true">
                                <option value="">Todos</option>
                                <option value="vigente">Vigente</option>
                                <option value="por_vencer">Por vencer</option>
                                <option value="vencida">Vencida</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-12 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 secuencia-label-filter">
                                <i class="fas fa-search mr-1"></i> Búsqueda rápida
                            </label>
                            <div class="input-group secuencia-search-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-barcode"></i>
                                    </span>
                                </div>
                                <input type="text" id="filtro_secuencia_general" name="filtro_secuencia_general" class="form-control" placeholder="Empresa, documento, CAI, prefijo, rango...">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-3 text-right">
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

    <!-- Cards resumen -->
    <div class="row secuencia-resumen-row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="secuencia-resumen-card secuencia-resumen-activa">
                <div>
                    <span class="secuencia-resumen-label">
                        <i class="fas fa-check-circle mr-1"></i> Secuencias activas
                    </span>
                    <h3 id="secuencia_total_activas">0</h3>
                    <p>Registros activos filtrados</p>
                </div>
                <div class="secuencia-resumen-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="secuencia-resumen-card secuencia-resumen-fiscal">
                <div>
                    <span class="secuencia-resumen-label">
                        <i class="fas fa-file-invoice-dollar mr-1"></i> Con CAI
                    </span>
                    <h3 id="secuencia_total_cai">0</h3>
                    <p>Documentos fiscales</p>
                </div>
                <div class="secuencia-resumen-icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="secuencia-resumen-card secuencia-resumen-disponible">
                <div>
                    <span class="secuencia-resumen-label">
                        <i class="fas fa-layer-group mr-1"></i> Disponibles
                    </span>
                    <h3 id="secuencia_total_disponibles">0</h3>
                    <p>Correlativos restantes</p>
                </div>
                <div class="secuencia-resumen-icon">
                    <i class="fas fa-list-ol"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="secuencia-resumen-card secuencia-resumen-vencer">
                <div>
                    <span class="secuencia-resumen-label">
                        <i class="fas fa-calendar-times mr-1"></i> Por vencer
                    </span>
                    <h3 id="secuencia_total_por_vencer">0</h3>
                    <p>Vencen en 30 días o menos</p>
                </div>
                <div class="secuencia-resumen-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card mb-4 secuencia-table-card">
        <div class="card-header secuencia-card-header">
            <div>
                <i class="fas fa-sliders-h fa-lg mr-1"></i>
                <strong>Secuencia Facturación</strong>
                <small class="d-block text-muted mt-1">Control de CAI, prefijo, rangos autorizados, correlativo siguiente y vencimiento.</small>
            </div>
        </div>

        <div class="card-body"> 
            <div class="table-responsive secuencia-table-responsive">
                <table id="dataTableSecuencia" class="table table-header-gradient table-striped table-condensed table-hover secuencia-table" style="width:100%"></table>  
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