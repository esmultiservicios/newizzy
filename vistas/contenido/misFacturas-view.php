<!-- MisFacturas-view.php -->
<div class="container-fluid">
    <ol class="breadcrumb mt-2 mb-4">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="<?php echo SERVERURL; ?>dashboard/">Dashboard</a></li>
        <li class="breadcrumb-item active">Mis Facturas</li>
    </ol>

    <!-- Filtros de búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter mr-1"></i>
            Filtros de Búsqueda
        </div>
        <div class="card-body">
            <form id="form-filtros-facturas">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="fecha_inicio">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fecha_fin">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="tipo_factura">Tipo de Factura</label>
                        <select class="form-control" id="tipo_factura" name="tipo_factura">
                            <option value="">Todos</option>
                            <option value="1">Contado</option>
                            <option value="2">Crédito</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="estado_factura">Estado</label>
                        <select class="form-control" id="estado_factura" name="estado_factura">
                            <option value="2">Pagadas</option>
                            <option value="3">Crédito</option>
                            <option value="4">Canceladas</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="numero_factura">Número de Factura</label>
                        <input type="text" class="form-control" id="numero_factura" name="numero_factura" placeholder="Buscar por número">
                    </div>
                    <div class="form-group col-md-6 text-right align-self-end">
                        <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary mr-2">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                        <button type="submit" id="btn-buscar-facturas" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable para mostrar facturas -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <i class="fas fa-file-invoice-dollar mr-1"></i>
            Facturas Registradas
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableFacturas" class="table table-striped table-header-gradient table-condensed table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Subtotal</th>
                            <th>ISV</th>
                            <th>Descuento</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "facturas";
                
                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha = htmlspecialchars($consulta['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = date('g:i:s a', strtotime($fecha));
                    echo "Última actualización: " . $insMainModel->getTheDay($fecha, $hora);
                } else {
                    echo "No hay registros recientes";
                }
            ?>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de factura -->
<div class="modal fade" id="modalDetalleFactura">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detalle de Factura <span id="numero-factura-modal"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
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
                        <h6><strong>Subtotal:</strong> $<span id="subtotal-factura"></span></h6>
                        <h6><strong>Total:</strong> $<span id="total-factura"></span></h6>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" id="btn-imprimir-factura" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para pagos de factura (solo para crédito) -->
<div class="modal fade" id="modalPagosFactura">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Pagos de Factura <span id="numero-factura-pagos"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="factura-id-pagos">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="pagos-factura-body">
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><strong>Total Factura:</strong> $<span id="total-factura-pagos"></span></h6>
                            <h6><strong>Pagado:</strong> $<span id="pagado-factura"></span></h6>
                            <h6><strong>Saldo Pendiente:</strong> $<span id="saldo-factura"></span></h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>