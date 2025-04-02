<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
		<li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Productos</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body">
            <form class="form-inline" id="form_main_productos">
                <div class="form-group mx-sm-3 mb-1">
                    <div class="input-group">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <div class="sb-nav-link-icon"></div>Estado
                            </span>
                            <select id="estado_producto" name="estado_producto" class="selectpicker" title="Estado"
                                data-live-search="true">
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mx-sm-3 mb-1">
                    <button class="guardar btn btn-secondary" type="submit" id="buscar_productos">
                        <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fab fa-product-hunt fa-lg mr-1"></i>
                Productos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dataTableProductos" class="table table-header-gradient table-striped table-condensed table-hover"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Bar Code</th>
                                <th>Producto</th>
                                <th>Medida</th>
                                <th>Categoria</th>
                                <th>Precio Compra</th>
                                <th>Precio Venta</th>
                                <th>Ganancia</th>
                                <th>ISV Venta</th>
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
    </div>
    <?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Productos");
?>