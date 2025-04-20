<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
		<li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item" id="movimientos">Novimientos</li>
        <li class="breadcrumb-item active" id="registroMovimientos">Registro Movimiento de Productos</li>
    </ol>

    <div id="main_inventario">
    <div class="card mb-4">
            <div class="card-body">
                <form class="" id="form_main_movimientos">
                    <div class="row">
                        <!-- Primera fila con 4 campos -->
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Categoría</label>
                                <select id="inventario_tipo_productos_id" name="inventario_tipo_productos_id"
                                    class="form-control selectpicker" data-live-search="true" data-toggle='tooltip' 
                                    data-placement='top' title="Categoría de Productos">
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Bodega</label>
                                <select id="almacen" name="almacen" class="form-control selectpicker" 
                                    data-live-search="true" title="Bodega">
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Producto</label>
                                <select id="producto_movimiento_filtro" name="producto_movimiento_filtro"
                                    class="form-control selectpicker" data-live-search="true" title="Producto">
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Cliente</label>
                                <select id="cliente_movimiento_filtro" name="cliente_movimiento_filtro"
                                    class="form-control selectpicker" data-live-search="true" title="Cliente">
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Segunda fila con 2 campos de fecha -->
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Fecha Inicio</label>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
                                    $fecha = date ("Y-m-d");
                                    
                                    $año = date("Y", strtotime($fecha));
                                    $mes = date("m", strtotime($fecha));
                                    $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

                                    $dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
                                    $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

                                    $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                    $fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
                                    
                                    echo $fecha_inicial;
                                ?>" class="form-control" data-toggle="tooltip" data-placement="top"
                                    title="Fecha Inicio">
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mb-3">
                                <label class="small mb-1">Fecha Fin</label>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control"
                                    data-toggle="tooltip" data-placement="top" title="Fecha Fin">
                            </div>
                        </div>
                        
                        <!-- Puedes agregar más campos aquí si es necesario -->
                        <div class="col-md-6 col-sm-12 d-flex align-items-end justify-content-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter fa-lg"></i> Filtrar
                            </button>
                            <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                <i class="fas fa-broom fa-lg"></i> Limpiar
                            </button>  
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exchange-alt fa-lg mr-1"></i>
                    Movimiento de Productos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dataTablaMovimientos" class="table table-header-gradient table-striped table-condensed table-hover"
                            style="width:100%">
                            <thead>
                                <tr>                                    
                                    <th>Fecha</th>
                                    <th>Imagen</th>
                                    <th>Número de Lote</th>
                                    <th>Bar Code</th>
                                    <th>Cliente</th>
                                    <th>Producto</th>
                                    <th>Medida</th>
                                    <th>Documento</th>
                                    <th>Anterior</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Saldo</th>
                                    <th>Comentario</th>
                                    <th>Bodega</th>
                                </tr>
                            </thead>
                            <tfoot class="bg-info text-white font-weight-bold">
                                <tr>
                                    <td colspan="7"></td>
                                    <td colspan='1' class="text-center">Total</td> 
                                    <td id="anterior-footer-movimiento"></td>                                   
                                    <td id="entrada-footer-movimiento"></td>
                                    <td id='salida-footer-movimiento'></td>
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
                        $entidad = "movimientos";
                        
                        if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                            $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                            
                            $fecha_registro = $consulta_last_update['fecha_registro'];
                            $hora = date('g:i:s a',strtotime($fecha_registro));
                                            
                            echo "Última Actualización ".$insMainModel->getTheDay($fecha_registro, $hora);						
                        }else{
                            echo "No se encontraron registros ";
                        }				
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div id="movimiento_inventario" style="display: none;">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fab fa-servicestack mr-1"></i>
                Movimiento Productos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <form class="FormularioAjax" id="formMovimientoInventario"
                        action="<?php echo SERVERURL;?>ajax/addComprasAjax.php" method="POST" data-form="save"
                        autocomplete="off" enctype="multipart/form-data">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <button class="btn btn-primary" type="submit" id="reg_factura"
                                    form="formMovimientoInventario" data-toggle="tooltip" data-placement="top"
                                    title="Registrar Factura de Compra">
                                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                                </button>
                            </div>
                            <label for="inputFecha" class="col-sm-1 col-form-label-md">Fecha <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d');?>" required
                                    id="fechaMovimientoInventario" name="fechaMovimientoInventario"
                                    data-toggle="tooltip" data-placement="top" title="Fecha de Facturación">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Operación <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <select id="movimiento_producto" name="movimiento_producto" required
                                        class="selectpicker col-12" title="Operación" data-size="7"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Factura </label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" placeholder="Número de Factura de Registro"
                                    id="facturaMovimientoInventario"
                                    name="facturaMovimientoInventario required data-toggle=" tooltip"
                                    data-placement="top" title="Factura Compra" maxlength="19" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Cliente </label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <select id="cliente_movimientos" name="cliente_movimientos"
                                        class="selectpicker col-12" title="Cliente" data-size="7"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>
                            <label for="inputCliente" class="col-sm-1 col-form-label-md">Almacén <span
                                    class="priority">*<span /></label>
                            <div class="col-sm-4">
                                <div class="input-group mb-3">
                                    <select id="almacen_modal" name="almacen_modal" required class="selectpicker col-12"
                                        title="Almacén" data-size="7" data-live-search="true">
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row table-responsive-xl tableFixHead table table-hover">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <table class="table table-bordered table-hover" id="MovimientoInventarioItem">
                                    <thead align="center" class="table-success">
                                        <tr>
                                            <th width="2%" scope="col"><input id="checkAllMovimientoInventario"
                                                    class="formcontrol" type="checkbox"></th>
                                            <th width="23.5%">Nombre Producto</th>
                                            <th width="9.5%">Cantidad</th>
                                            <th width="11.5%">Medida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input class="itemRowInventario" type="checkbox"></td>
                                            <td>
                                                <div class="input-group mb-3">
                                                    <input type="hidden" name="productos_idInventario[]"
                                                        id="productos_idInventario_0" class="form-control"
                                                        autocomplete="off">
                                                    <input type="text" name="productNameInventario[]"
                                                        id="productNameInventario_0" class="form-control"
                                                        autocomplete="off" required>
                                                    <div class="input-group-append">
                                                        <span data-toggle="tooltip" data-placement="top"
                                                            title="Búsqueda de Productos"><a data-toggle="modal"
                                                                href="#"
                                                                class="btn btn-outline-success form-control buscar_productos_Inventario">
                                                                <div class="sb-nav-link-icon"></div><i
                                                                    class="fas fa-search-plus fa-lg"></i>
                                                            </a></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><input type="number" name="quantityInventario[]"
                                                    id="quantityInventario_0"
                                                    class="buscar_cantidad_Inventario form-control" autocomplete="off"
                                                    step="0.01"></td>
                                            <td>
                                                <input type="text" name="medidaInventario[]" id="medidaInventario_0"
                                                    readonly class="form-control" autocomplete="off">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr class="line_table" />
                        <div class="form-group row">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <button class="btn btn-success ml-3 bill-bottom-add" id="addRowsInventario"
                                    type="button" data-toggle="tooltip" data-placement="top"
                                    title="Agregar filas en la factura">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-plus fa-lg"></i> Agregar
                                </button>
                                <button class="btn btn-success delete bill-bottom-remove" id="removeRowsInventario"
                                    type="button" data-toggle="tooltip" data-placement="top"
                                    title="Remover filas en la factura">
                                    <div class="sb-nav-link-icon"></div><i class="fas fa-minus fa-lg"></i> Quitar
                                </button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-row col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="col-sm-12 col-md-12">
                                    <h3>Notas: </h3>
                                    <div class="form-group">
                                        <textarea class="form-control txt" rows="6" name="notesInventario"
                                            id="notesInventario" placeholder="Notas" maxlength="2000"></textarea>
                                        <p id="charNum_notasInventario">2000 Caracteres</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="RespuestaAjax"></div>
                    </form>
                </div>
            </div>
            <div class="card-footer small text-muted">
                <?php
				require_once "./core/mainModel.php";
				
				$insMainModel = new mainModel();
				$entidad = "movimientos";
				
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
	$insMainModel->guardar_historial_accesos("Ingreso al modulo Inventario");
?>