<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Reporte de Ventas</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-body">
            <form id="form_main_ventas">
                <div class="row">
                    <!-- Primera fila con 4 campos -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Tipo Factura</label>
                            <select id="factura_reporte" name="factura_reporte" 
                                class="form-control selectpicker" title="Factura" data-live-search="true">
                                <option value="1">Electrónica</option>
                                <option value="4">Proforma</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Categoría Factura</label>
                            <select id="tipo_factura_reporte" name="tipo_factura_reporte" 
                                class="form-control selectpicker" title="Tipo de Factura" data-live-search="true">
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Facturador</label>
                            <select id="facturador" name="facturador" 
                                class="form-control selectpicker" title="Facturador" data-live-search="true">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Vendedor</label>
                            <select id="vendedor" name="vendedor" 
                                class="form-control selectpicker" title="Vendedor" data-live-search="true">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Segunda fila con fechas y botón -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Inicio</label>
                            <input type="date" required id="fechai" name="fechai" value="<?php 
                                $fecha = date ("Y-m-d");
                                $año = date("Y", strtotime($fecha));
                                $mes = date("m", strtotime($fecha));
                                $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                $dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
                                $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
                                $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                echo $fecha_inicial;
                            ?>" class="form-control" title="Fecha Inicio">
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Fin</label>
                            <input type="date" required id="fechaf" name="fechaf" 
                                value="<?php echo date ("Y-m-d");?>" class="form-control" title="Fecha Fin">
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-sm-12 d-flex align-items-end justify-content-end mb-3">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-file-invoice fa-lg mr-1"></i> Generar Reporte
                        </button>
                        <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">
                            <i class="fas fa-broom fa-lg mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-invoice-dollar fa-lg mr-1"></i>
            Reporte de Ventas
            <div class="float-right">
                <span class="badge bg-light text-dark">
                    <i class="fas fa-sync-alt mr-1 fa-lg"></i>
                    <span id="contador-actualizacion"></span>
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTablaReporteVentas" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Factura</th>
                            <th>SubTotal</th>
                            <th>ISV</th>
                            <th>Descuento</th>
                            <th>Total Ventas</th>
                            <th>Ganancia</th>
                            <th>Vendedor</th>
                            <th>Facturador</th>
                            <th>Ver Detalle</th>
                            <th>Factura</th>
                            <th>Comprobante</th>
                            <th>Enviar</th>
                            <th>Anular</th>
                        </tr>
                    </thead>
                    <tfoot class="bg-info text-white font-weight-bold">
                        <tr>
                            <td colspan='1'>Total</td>
                            <td colspan="3"></td>
                            <td id="subtotal-i"></td>
                            <td id="impuesto-i"></td>
                            <td id="descuento-i"></td>
                            <td colspan='1' id='total-footer-ingreso'></td>
                            <td colspan='1' id='ganancia'></td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
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

<!-- Modal para ver detalles de factura -->
<div class="modal fade" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Factura <span id="numero-factura-modal"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6><strong>Fecha:</strong> <span id="fecha-factura"></span></h6>
                        <h6><strong>Cliente:</strong> <span id="cliente-factura"></span></h6>
                    </div>
                    <div class="col-md-4">
                        <h6><strong>Tipo:</strong> <span id="tipo-factura"></span></h6>
                        <h6><strong>Estado:</strong> <span id="estado-factura"></span></h6>
                    </div>
                    <div class="col-md-4 text-right">
                        <h6><strong>Subtotal:</strong> <span id="subtotal-factura"></span></h6>
                        <h6><strong>Total:</strong> <span id="total-factura"></span></h6>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-header-gradient table-striped table-condensed table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto/Servicio</th>
                                <th width="10%">Cantidad</th>
                                <th width="15%">Precio Unitario</th>
                                <th width="15%">ISV</th>
                                <th width="15%">Descuento</th>
                                <th width="15%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalle-factura-body">
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><strong>Notas:</strong></h6>
                        <p id="notas-factura" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
                <button type="button" id="btn-imprimir-factura" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de factura -->
<div class="modal fade" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Factura <span id="numero-factura-modal"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6><strong>Fecha:</strong> <span id="fecha-factura"></span></h6>
                        <h6><strong>Cliente:</strong> <span id="cliente-factura"></span></h6>
                    </div>
                    <div class="col-md-4">
                        <h6><strong>Tipo:</strong> <span id="tipo-factura"></span></h6>
                        <h6><strong>Estado:</strong> <span id="estado-factura"></span></h6>
                    </div>
                    <div class="col-md-4 text-right">
                        <h6><strong>Subtotal:</strong> <span id="subtotal-factura"></span></h6>
                        <h6><strong>Total:</strong> <span id="total-factura"></span></h6>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-header-gradient table-striped table-condensed table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto/Servicio</th>
                                <th width="10%">Cantidad</th>
                                <th width="15%">Precio Unitario</th>
                                <th width="15%">ISV</th>
                                <th width="15%">Descuento</th>
                                <th width="15%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalle-factura-body">
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><strong>Notas:</strong></h6>
                        <p id="notas-factura" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
                <button type="button" id="btn-imprimir-factura" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Reporte de Ventas");
?>