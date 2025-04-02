<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
		<li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventario</li>
    </ol>
    <div class="card mb-4">
        <div class="card-body">
            <form class="" id="form_main_movimientos">
                <div class="row">
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Categoría
                                </span>
                                <select id="inventario_tipo_productos_id" name="inventario_tipo_productos_id"
                                    class="selectpicker" data-live-search="true" title="Categoría de Productos">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Categoría
                                </span>
                                <select id="inventario_productos_id" name="inventario_productos_id" class="selectpicker"
                                    data-live-search="true" title="Productos">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mx-sm-3 mb-1">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <div class="sb-nav-link-icon"></div>Categoría
                                </span>
                                <select id="almacen" name="almacen" class="selectpicker" data-live-search="true"
                                    title="Almacen">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-boxes fa-lg mr-1"></i> Inventario
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTablaMovimientos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Cambiar Vencimiento</th>
                            <th>Fecha</th>
                            <th>Imagen</th>
                            <th>Número de Lote</th>
                            <th>Bar Code</th>
                            <th>Producto</th>
                            <th>Medida</th>
                            <th>Anterior</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Saldo</th>
                            <th>Bodega</th>
                            <th>Transferencia</th>
                        </tr>
                    </thead>
                    <tfoot class="bg-info text-white font-weight-bold">
                        <tr>
                            <td colspan="6"></td>
                            <td colspan='1' class="text-center">Total</td> 
                            <td id="anterior-footer-movimiento"></td> 
                            <td id="entrada-footer-movimiento"></td>
                            <td id="salida-footer-movimiento"></td>
                            <td id="total-footer-movimiento"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "productos";
                $consulta_last_update = $insMainModel->getlastUpdate($entidad);

                if ($consulta_last_update->num_rows > 0) {
                    $row = $consulta_last_update->fetch_assoc();
                    $fecha_registro = htmlspecialchars($row['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = date('g:i:s a', strtotime($fecha_registro));
                    echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </div>

    <?php
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Inventario");
?>