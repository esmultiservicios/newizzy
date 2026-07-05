<!-- MisFacturas-view.php -->
<div class="container-fluid facturacion-cliente-page">
    <div class="breadcrumb-harmony-container">
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
                <span>Detalles de facturación</span>
            </li>
        </ol>
    </div>

    <!-- Filtros de búsqueda -->
    <div class="card mb-4 factura-filter-card">
        <div class="card-header">
            <div class="factura-card-title">
                <i class="fas fa-filter"></i>
                <span>Filtros de Búsqueda</span>
            </div>
        </div>

        <div class="card-body">
            <form id="form-filtros-facturas">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label for="fecha_inicio">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Fecha Inicio
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>

                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label for="fecha_fin">
                                <i class="fas fa-calendar-check mr-1"></i>
                                Fecha Fin
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>

                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label for="tipo_factura">
                                <i class="fas fa-file-invoice mr-1"></i>
                                Tipo de Factura
                            </label>

                            <select class="form-control selectpicker" id="tipo_factura" name="tipo_factura" title="Todos los tipos" data-live-search="true" data-none-selected-text="Todos los tipos">
                                <option value="">Todos los tipos</option>
                                <option value="1">Contado</option>
                                <option value="2">Crédito</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label for="estado_factura">
                                <i class="fas fa-toggle-on mr-1"></i>
                                Estado
                            </label>

                            <select class="form-control selectpicker" id="estado_factura" name="estado_factura" title="Todos los estados" data-live-search="true" data-none-selected-text="Todos los estados">
                                <option value="">Todos los estados</option>
                                <option value="pendiente_pago">Solo pendientes de pago</option>
                                <option value="1">Borrador / Pendiente de pago</option>
                                <option value="2">Pagada al contado</option>
                                <option value="3">Crédito pendiente / con abono</option>
                                <option value="4">Anulada / Cancelada</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-8 col-md-12 col-sm-12 mb-3">
                        <div class="form-group mb-0">
                            <label for="numero_factura">
                                <i class="fas fa-search mr-1"></i>
                                Buscar factura
                            </label>

                            <input type="text" class="form-control" id="numero_factura" name="numero_factura" placeholder="Número, cliente, estado, tipo o monto">
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-4 col-md-12 col-sm-12 mb-3">
                        <div class="factura-toolbar">
                            <button type="submit" id="btn-buscar-facturas" class="btn btn-primary">
                                <i class="fas fa-filter mr-1 fa-lg"></i> Filtrar
                            </button>

                            <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">
                                <i class="fas fa-broom mr-1 fa-lg"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable para mostrar facturas -->
    <div class="card mb-4 factura-table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="factura-card-title">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Historial de Facturación en su plan</span>
            </div>

            <button type="button" class="btn btn-sm btn-secondary" id="btn-actualizar-facturas">
                <i class="fas fa-sync-alt mr-1"></i> Actualizar
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive factura-table-responsive">
                <table id="dataTableFacturas" class="table table-header-gradient table-striped table-condensed factura-premium-table" style="width:100%"></table>
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
<div class="modal fade" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content factura-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Detalle de Factura <span id="numero-factura-modal"></span>
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="factura-detalle-resumen mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="factura-detalle-item">
                                <span>Fecha</span>
                                <strong id="fecha-factura"></strong>
                            </div>

                            <div class="factura-detalle-item">
                                <span>Cliente</span>
                                <strong id="cliente-factura"></strong>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="factura-detalle-item">
                                <span>Tipo</span>
                                <strong id="tipo-factura"></strong>
                            </div>

                            <div class="factura-detalle-item">
                                <span>Estado</span>
                                <strong id="estado-factura"></strong>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="factura-detalle-item text-md-right">
                                <span>Subtotal</span>
                                <strong id="subtotal-factura"></strong>
                            </div>

                            <div class="factura-detalle-item text-md-right">
                                <span>Total</span>
                                <strong id="total-factura" class="text-success"></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-header-gradient table-striped table-condensed factura-premium-table">
                        <thead>
                            <tr>
                                <th>Producto/Servicio</th>
                                <th width="10%">Cantidad</th>
                                <th width="15%">Precio Unitario</th>
                                <th width="15%">ISV</th>
                                <th width="15%">Descuento</th>
                                <th width="15%">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody id="detalle-factura-body"></tbody>
                    </table>
                </div>
                
                <div class="factura-notas-box mt-3">
                    <h6>
                        <i class="fas fa-sticky-note mr-1"></i>
                        Notas
                    </h6>

                    <p id="notas-factura" class="text-muted mb-0"></p>
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