<div class="container-fluid productos-page">
	<!-- Productos -->
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
                <i class="fas fa-box breadcrumb-icon"></i>
				<span>Productos</span>
			</li>
		</ol>
	</div>

    <!-- Filtros -->
    <div class="card mb-4 productos-filtro-card">
        <div class="card-body">
            <form id="form_main_productos">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 productos-label-filter">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_producto" name="estado_producto" 
                                class="form-control selectpicker" title="Estado" data-live-search="true">
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 productos-label-filter">
                                <i class="fas fa-layer-group mr-1"></i> Categoría
                            </label>
                            <select id="categoria_producto_filtro" name="categoria_producto_filtro" 
                                class="form-control selectpicker" title="Categoría" data-live-search="true">
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 productos-label-filter">
                                <i class="fas fa-percent mr-1"></i> Impuesto venta
                            </label>
                            <select id="isv_producto_filtro" name="isv_producto_filtro" 
                                class="form-control selectpicker" title="ISV" data-live-search="true">
                                <option value="">Todos</option>
                                <option value="si">Con ISV</option>
                                <option value="no">Sin ISV</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-12 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 productos-label-filter">
                                <i class="fas fa-search mr-1"></i> Búsqueda rápida
                            </label>
                            <div class="input-group productos-search-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-barcode"></i>
                                    </span>
                                </div>
                                <input type="text" id="buscar_productos_general" name="buscar_productos_general" class="form-control" placeholder="Producto, código, categoría, descripción...">
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
    <div class="row productos-resumen-row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="productos-resumen-card productos-resumen-registros">
                <div>
                    <span class="productos-resumen-label">
                        <i class="fas fa-boxes mr-1"></i> Productos
                    </span>
                    <h3 id="productos_total_registros">0</h3>
                    <p>Registros filtrados</p>
                </div>
                <div class="productos-resumen-icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="productos-resumen-card productos-resumen-activos">
                <div>
                    <span class="productos-resumen-label">
                        <i class="fas fa-check-circle mr-1"></i> Activos
                    </span>
                    <h3 id="productos_total_activos">0</h3>
                    <p>Productos disponibles</p>
                </div>
                <div class="productos-resumen-icon">
                    <i class="fas fa-toggle-on"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="productos-resumen-card productos-resumen-isv">
                <div>
                    <span class="productos-resumen-label">
                        <i class="fas fa-percent mr-1"></i> Con ISV
                    </span>
                    <h3 id="productos_total_isv">0</h3>
                    <p>Calculan impuesto</p>
                </div>
                <div class="productos-resumen-icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="productos-resumen-card productos-resumen-venta">
                <div>
                    <span class="productos-resumen-label">
                        <i class="fas fa-tags mr-1"></i> Valor venta
                    </span>
                    <h3 id="productos_total_venta">L 0.00</h3>
                    <p>Suma de precios venta</p>
                </div>
                <div class="productos-resumen-icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card mb-4 productos-table-card">
        <div class="card-header productos-card-header">
            <div>
                <i class="fab fa-product-hunt fa-lg mr-1"></i>
                <strong>Productos</strong>
                <small class="d-block text-muted mt-1">
                    Información general del producto, precios, impuestos, reglas de inventario y estado.
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive productos-table-responsive">
                <table id="dataTableProductos" class="table table-header-gradient table-striped table-condensed table-hover productos-table" style="width:100%">
                </table>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "productos";
				
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

    <?php
	    $insMainModel->guardar_historial_accesos("Ingreso al modulo Productos");
    ?>
</div>