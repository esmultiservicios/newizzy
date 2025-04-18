<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Reporte de Ventas</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-body">
            <form id="form_main_ventas">
                <div class="form-row">
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Factura</span>
                                <select id="factura_reporte" name="factura_reporte" class="selectpicker" title="Factura" data-live-search="true">
                                    <option value="1">Electronica</option>
                                    <option value="4">Proforma</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Tipo Factura</span>
                                <select id="tipo_factura_reporte" name="tipo_factura_reporte" class="selectpicker" title="Tipo de Factura" data-live-search="true"></select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Facturador</span>
                                <select id="facturador" name="facturador" class="selectpicker" title="Facturador" data-live-search="true">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Vendedor</span>
                                <select id="vendedor" name="vendedor" class="selectpicker" title="Vendedor" data-live-search="true">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Inicio</span>
                            </div>
                            <input type="date" required id="fechai" name="fechai" value="<?php 
                                $fecha = date ("Y-m-d");
                                $año = date("Y", strtotime($fecha));
                                $mes = date("m", strtotime($fecha));
                                $dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
                                $dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
                                $dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES
                                $fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
                                echo $fecha_inicial;
                            ?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Inicio">
                        </div>
                    </div>
                    
                    <div class="form-group col-md-3 mb-2">
                        <div class="input-group">
                            <div class="input-group-append">
                                <span class="input-group-text"><div class="sb-nav-link-icon"></div>Fin</span>
                            </div>
                            <input type="date" required id="fechaf" name="fechaf" value="<?php echo date ("Y-m-d");?>" class="form-control" data-toggle="tooltip" data-placement="top" title="Fecha Fin">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-invoice-dollar fa-lg mr-1"></i>
            Reporte de Ventas
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
                            <td colspan="6"></td>
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
<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Reporte de Ventas");
?>