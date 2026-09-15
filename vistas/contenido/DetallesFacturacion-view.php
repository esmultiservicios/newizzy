<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/DetallesFacturacion.css">

<div class="container-fluid facturacion-cliente-page df-page">
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

    <!-- =====================================================
         FILTROS
         ===================================================== -->
    <section class="df-section-card df-filter-card" id="dfFiltrosCard">
        <div class="df-section-header">
            <div class="df-section-heading">
                <span class="df-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5>Filtros de búsqueda</h5>
                    <small>Seleccione los criterios para consultar el historial de facturación.</small>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-secondary df-toggle-section" data-target="#dfFiltrosBody" aria-expanded="true">
                <i class="fas fa-chevron-up"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="df-section-body" id="dfFiltrosBody">
            <form id="form-filtros-facturas" autocomplete="off">
                <div class="df-filter-grid">
                    <div class="df-field">
                        <label for="fecha_inicio"><i class="fas fa-calendar-alt"></i> Fecha inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                    </div>

                    <div class="df-field">
                        <label for="fecha_fin"><i class="fas fa-calendar-check"></i> Fecha fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                    </div>

                    <div class="df-field">
                        <label for="tipo_factura"><i class="fas fa-file-invoice"></i> Tipo de factura</label>
                        <select class="form-control izzy-select2" id="tipo_factura" name="tipo_factura" data-placeholder="Todos los tipos">
                            <option value="todos">Todos los tipos</option>
                            <option value="1">Contado</option>
                            <option value="2">Crédito</option>
                        </select>
                    </div>

                    <div class="df-field">
                        <label for="estado_factura"><i class="fas fa-toggle-on"></i> Estado</label>
                        <select class="form-control izzy-select2" id="estado_factura" name="estado_factura" data-placeholder="Todos los estados">
                            <option value="todos">Todos los estados</option>
                            <option value="pendiente_pago">Solo pendientes de pago</option>
                            <option value="1">Borrador / Pendiente de pago</option>
                            <option value="2">Pagada al contado</option>
                            <option value="3">Crédito pendiente / con abono</option>
                            <option value="4">Anulada / Cancelada</option>
                        </select>
                    </div>

                    <div class="df-field df-field-wide">
                        <label for="numero_factura"><i class="fas fa-search"></i> Buscar factura</label>
                        <input type="text" class="form-control" id="numero_factura" name="numero_factura" placeholder="Número, cliente, estado, tipo o monto">
                    </div>

                    <div class="df-filter-actions">
                        <button type="submit" id="btn-buscar-facturas" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- =====================================================
         KPI
         ===================================================== -->
    <section class="df-section-card df-kpi-section" id="dfKpiCard">
        <div class="df-section-header">
            <div class="df-section-heading">
                <span class="df-section-icon"><i class="fas fa-chart-line"></i></span>
                <div>
                    <h5>Resumen de facturación</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-secondary df-toggle-section" data-target="#dfKpiBody" aria-expanded="true">
                <i class="fas fa-chevron-up"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="df-section-body" id="dfKpiBody">
            <div class="df-kpi-grid">
                <article class="df-kpi-card">
                    <span class="df-kpi-icon df-kpi-blue"><i class="fas fa-file-invoice"></i></span>
                    <div class="df-kpi-copy">
                        <span>Facturas</span>
                        <strong id="dfKpiFacturas">0</strong>
                        <small>Registros filtrados</small>
                    </div>
                </article>

                <article class="df-kpi-card">
                    <span class="df-kpi-icon df-kpi-green"><i class="fas fa-coins"></i></span>
                    <div class="df-kpi-copy">
                        <span>Total</span>
                        <strong id="dfKpiTotal">L. 0.00</strong>
                        <small>Monto facturado</small>
                    </div>
                </article>

                <article class="df-kpi-card">
                    <span class="df-kpi-icon df-kpi-teal"><i class="fas fa-receipt"></i></span>
                    <div class="df-kpi-copy">
                        <span>ISV</span>
                        <strong id="dfKpiIsv">L. 0.00</strong>
                        <small>Impuesto acumulado</small>
                    </div>
                </article>

                <article class="df-kpi-card">
                    <span class="df-kpi-icon df-kpi-red"><i class="fas fa-tags"></i></span>
                    <div class="df-kpi-copy">
                        <span>Descuento</span>
                        <strong id="dfKpiDescuento">L. 0.00</strong>
                        <small>Descuento acumulado</small>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- =====================================================
         LISTADO DIV / GRID / FLEX
         ===================================================== -->
    <section class="df-list-card">
        <div class="df-list-header">
            <div class="df-section-heading">
                <span class="df-section-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <div>
                    <h5>Historial de facturación</h5>
                    <small>Consulte, exporte y gestione las facturas disponibles en su plan.</small>
                </div>
            </div>
        </div>

        <div class="df-list-body">
            <div class="df-list-toolbar">
                <div class="df-toolbar-left">
                    <button type="button" class="btn btn-secondary" id="btn-actualizar-facturas">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-success" id="btn-excel-facturas">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btn-pdf-facturas">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>

                <div class="df-toolbar-right">
                    <label class="df-page-size" for="dfPageSize">
                        <span>Mostrar</span>
                        <select id="dfPageSize" class="form-control form-control-sm" data-search="false">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </label>

                    <div class="df-view-switch" role="group" aria-label="Cambiar vista">
                        <button type="button" class="df-view-btn active" data-df-view="detalle" title="Vista detalle" aria-label="Vista detalle">
                            <i class="fas fa-list"></i>
                            <span>Detalle</span>
                        </button>
                        <button type="button" class="df-view-btn" data-df-view="miniatura" title="Vista miniatura" aria-label="Vista miniatura">
                            <i class="fas fa-th-large"></i>
                            <span>Miniatura</span>
                        </button>
                    </div>

                    <div class="df-search-wrap">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" id="dfListadoSearch" class="form-control" placeholder="Buscar en resultados..." autocomplete="off">
                        <button type="button" class="df-search-clear" id="dfListadoSearchClear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="facturasListado" class="df-listado df-view-detalle" aria-live="polite"></div>

            <div id="facturasEmpty" class="df-empty" style="display:none;">
                <i class="fas fa-file-invoice"></i>
                <strong>No hay facturas para mostrar</strong>
                <span>Ajuste los filtros o la búsqueda e intente nuevamente.</span>
            </div>

            <div class="df-list-footer">
                <div class="df-results-info" id="dfResultsInfo">Mostrando 0 registros</div>
                <nav class="df-pagination" id="dfPagination" aria-label="Paginación de facturas"></nav>
            </div>
        </div>

        <div class="df-update-footer small text-muted">
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
    </section>
</div>

<!-- Modal para ver detalles de factura -->
<div class="modal fade" id="modalDetalleFactura" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content factura-modal-content df-modal-content">
            <div class="modal-header df-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Detalle de Factura <span id="numero-factura-modal"></span>
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="factura-detalle-resumen df-detail-summary mb-4">
                    <div class="df-detail-summary-grid">
                        <div class="factura-detalle-item"><span>Fecha</span><strong id="fecha-factura"></strong></div>
                        <div class="factura-detalle-item"><span>Cliente</span><strong id="cliente-factura"></strong></div>
                        <div class="factura-detalle-item"><span>Tipo</span><strong id="tipo-factura"></strong></div>
                        <div class="factura-detalle-item"><span>Estado</span><strong id="estado-factura"></strong></div>
                        <div class="factura-detalle-item"><span>Subtotal</span><strong id="subtotal-factura"></strong></div>
                        <div class="factura-detalle-item"><span>Total</span><strong id="total-factura" class="text-success"></strong></div>
                    </div>
                </div>

                <div class="df-modal-directory-card">
                    <div class="df-list-toolbar df-modal-list-toolbar">
                        <div class="df-toolbar-left">
                            <button type="button" class="btn btn-success" id="dfModalExcel">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button type="button" class="btn btn-danger" id="dfModalPdf">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>

                        <div class="df-toolbar-right">
                            <label class="df-page-size df-modal-page-size" for="dfModalPageSize">
                                <span>Mostrar</span>
                                <select id="dfModalPageSize" class="form-control form-control-sm" data-search="false">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                                <span>registros</span>
                            </label>

                            <div class="df-view-switch df-modal-view-switch" role="group" aria-label="Cambiar vista del detalle">
                                <button type="button" class="df-view-btn active" data-df-modal-view="detalle" title="Vista detalle" aria-label="Vista detalle">
                                    <i class="fas fa-list"></i>
                                    <span>Detalle</span>
                                </button>
                                <button type="button" class="df-view-btn" data-df-modal-view="miniatura" title="Vista miniatura" aria-label="Vista miniatura">
                                    <i class="fas fa-th-large"></i>
                                    <span>Miniatura</span>
                                </button>
                            </div>

                            <div class="df-search-wrap df-modal-search-wrap">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <input type="search" id="dfModalSearch" class="form-control" placeholder="Buscar en detalle..." autocomplete="off">
                                <button type="button" class="df-search-clear" id="dfModalSearchClear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="detalle-factura-body" class="df-modal-listado df-modal-view-detalle" aria-live="polite"></div>

                    <div id="dfModalEmpty" class="df-modal-empty" style="display:none;">
                        <i class="fas fa-box-open"></i>
                        <strong>No hay productos para mostrar</strong>
                        <span>Ajuste la búsqueda e intente nuevamente.</span>
                    </div>

                    <div class="df-list-footer df-modal-list-footer">
                        <div class="df-results-info" id="dfModalResultsInfo">Mostrando 0 registros</div>
                        <nav class="df-pagination" id="dfModalPagination" aria-label="Paginación del detalle de factura"></nav>
                    </div>
                </div>

                <div class="factura-notas-box mt-3">
                    <h6><i class="fas fa-sticky-note mr-1"></i> Notas</h6>
                    <p id="notas-factura" class="text-muted mb-0"></p>
                </div>
            </div>

            <div class="modal-footer df-modal-footer">
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
