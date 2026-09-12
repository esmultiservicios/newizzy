<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/transferencia.css">

<div class="container-fluid inventario-transferencia-page">
    <!-- Inventario -->
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
                <i class="fas fa-boxes breadcrumb-icon"></i>
                <span>Inventario</span>
            </li>
        </ol>
    </div>

    <!-- Vista principal inventario -->
    <div id="vistaInventarioPrincipal">

        <!-- FILTROS -->
        <section class="card transferencia-section-card mb-4" id="transferenciaFiltrosSection">
            <div class="transferencia-section-header">
                <div class="transferencia-section-title">
                    <span class="transferencia-section-icon"><i class="fas fa-filter"></i></span>
                    <div>
                        <h5 class="mb-0">Filtros de Inventario</h5>
                        <small>Consulte existencias por categoría, producto y almacén.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-primary transferencia-toggle-btn"
                        id="btnToggleFiltrosTransferencia" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>

            <div class="card-body" id="transferenciaFiltrosContenido">
                <form id="form_main_movimientos_transferencia">
                    <div class="row align-items-end">
                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="transferencia-label-filter">
                                <i class="fas fa-tags mr-1"></i> Categoría
                            </label>
                            <select id="inventario_tipo_productos_id" name="inventario_tipo_productos_id"
                                    class="form-control selectpicker" data-live-search="true"
                                    title="Categoría de Productos" data-width="100%">
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="transferencia-label-filter">
                                <i class="fas fa-box-open mr-1"></i> Producto
                            </label>
                            <select id="inventario_productos_id" name="inventario_productos_id"
                                    class="form-control selectpicker" data-live-search="true"
                                    title="Productos" data-width="100%">
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6 col-12 mb-3">
                            <label class="transferencia-label-filter">
                                <i class="fas fa-warehouse mr-1"></i> Almacén
                            </label>
                            <select id="almacen" name="almacen"
                                    class="form-control selectpicker" data-live-search="true"
                                    title="Almacén" data-width="100%">
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="transferencia-filter-actions">
                                <button type="button" class="btn btn-info" id="btn_ver_resumen_inventario">
                                    <i class="fas fa-chart-pie mr-1"></i> Valor del Inventario
                                </button>
                                <button type="submit" class="btn btn-primary" id="search">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-broom mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- KPIs -->
        <section class="card transferencia-section-card mb-4" id="transferenciaKpisSection">
            <div class="transferencia-section-header">
                <div class="transferencia-section-title">
                    <span class="transferencia-section-icon"><i class="fas fa-chart-pie"></i></span>
                    <div>
                        <h5 class="mb-0">Resumen de Inventario</h5>
                        <small>Indicadores del inventario filtrado actualmente.</small>
                    </div>
                </div>
                <button type="button" class="btn btn-primary transferencia-toggle-btn"
                        id="btnToggleKpisTransferencia" aria-expanded="true">
                    <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
                </button>
            </div>

            <div class="card-body" id="transferenciaKpisContenido">
                <div class="row transferencia-kpi-row">
                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="transferencia-kpi transferencia-kpi-primary">
                            <div class="transferencia-kpi-copy">
                                <span>Registros</span>
                                <strong id="inventario_total_registros">0</strong>
                                <small>Productos filtrados</small>
                            </div>
                            <span class="transferencia-kpi-icon"><i class="fas fa-boxes"></i></span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="transferencia-kpi transferencia-kpi-success">
                            <div class="transferencia-kpi-copy">
                                <span>Entradas</span>
                                <strong id="inventario_total_entrada">0.00</strong>
                                <small>Total acumulado</small>
                            </div>
                            <span class="transferencia-kpi-icon"><i class="fas fa-sign-in-alt"></i></span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="transferencia-kpi transferencia-kpi-danger">
                            <div class="transferencia-kpi-copy">
                                <span>Salidas</span>
                                <strong id="inventario_total_salida">0.00</strong>
                                <small>Total acumulado</small>
                            </div>
                            <span class="transferencia-kpi-icon"><i class="fas fa-sign-out-alt"></i></span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-12 mb-3">
                        <div class="transferencia-kpi transferencia-kpi-purple">
                            <div class="transferencia-kpi-copy">
                                <span>Saldo</span>
                                <strong id="inventario_total_saldo">0.00</strong>
                                <small>Saldo disponible</small>
                            </div>
                            <span class="transferencia-kpi-icon"><i class="fas fa-cubes"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- LISTADO -->
        <section class="card transferencia-section-card mb-4" id="transferenciaListadoSection">
            <div class="transferencia-directory-header">
                <div class="transferencia-section-title">
                    <span class="transferencia-section-icon"><i class="fas fa-boxes"></i></span>
                    <div>
                        <h5 class="mb-0">Inventario</h5>
                        <small>Existencias por producto, lote, bodega y último movimiento.</small>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="transferencia-list-toolbar">
                    <div class="transferencia-toolbar-left">
                        <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnActualizarTransferencia">
                            <i class="fas fa-sync-alt mr-1"></i> Actualizar
                        </button>
                        <button type="button" class="btn btn-info table_reportes ocultar" id="btnResumenTransferencia">
                            <i class="fas fa-chart-pie mr-1"></i> Resumen
                        </button>
                        <button type="button" class="btn btn-success table_reportes ocultar" id="btnExcelTransferencia">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </button>
                        <button type="button" class="btn btn-danger table_reportes ocultar" id="btnPdfTransferencia">
                            <i class="fas fa-file-pdf mr-1"></i> PDF
                        </button>
                    </div>

                    <div class="transferencia-toolbar-right">
                        <label class="transferencia-page-size mb-0">
                            <span>Mostrar</span>
                            <select id="transferenciaPageSize" class="form-control form-control-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>registros</span>
                        </label>

                        <div class="transferencia-view-switch">
                            <button type="button" class="transferencia-view-btn active" data-view="detalle">
                                <i class="fas fa-list"></i><span>Detalle</span>
                            </button>
                            <button type="button" class="transferencia-view-btn" data-view="miniatura">
                                <i class="fas fa-th-large"></i><span>Miniatura</span>
                            </button>
                        </div>

                        <div class="transferencia-search">
                            <span class="transferencia-search-icon"><i class="fas fa-search"></i></span>
                            <input type="search" id="buscar_transferencia_general" class="form-control"
                                   placeholder="Buscar producto..." autocomplete="off">
                            <button type="button" id="limpiarBuscarTransferencia" class="transferencia-search-clear">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="transferenciaListado" class="transferencia-listado vista-detalle"></div>

                <div class="transferencia-totales-strip">
                    <div><span>Saldo anterior</span><strong id="transferencia_total_anterior_listado">0.00</strong></div>
                    <div><span>Entradas</span><strong id="transferencia_total_entrada_listado">0.00</strong></div>
                    <div><span>Salidas</span><strong id="transferencia_total_salida_listado">0.00</strong></div>
                    <div><span>Saldo</span><strong id="transferencia_total_saldo_listado">0.00</strong></div>
                </div>

                <div class="transferencia-list-footer">
                    <span id="transferenciaInfo">0 registros</span>
                    <div id="transferenciaPaginacion" class="transferencia-pagination"></div>
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
        </section>
    </div>

    <!-- Vista resumen inventario -->
    <div id="vistaResumenInventario" style="display:none;">

        <div class="card mb-4 inventario-filtro-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2">
                        <h5 class="mb-1">
                            <i class="fas fa-chart-pie mr-1"></i> Resumen de inventario
                        </h5>
                        <small class="text-muted">
                            Valor estimado de mercadería disponible e histórico vendido.
                        </small>
                    </div>

                    <div class="mb-2">
                        <button type="button" class="btn btn-secondary" id="btn_volver_inventario">
                            <i class="fas fa-arrow-left"></i> Volver al inventario
                        </button>
                    </div>
                </div>

                <hr>

                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                        <label class="small mb-1 inventario-label-filter">
                            <i class="fas fa-calculator mr-1"></i> Valorar inventario por
                        </label>
                        <select id="inventario_tipo_valorizacion" class="form-control selectpicker" title="Tipo de valorización">
                            <option value="venta" selected>Precio de venta</option>
                            <option value="costo">Costo del producto</option>
                        </select>
                    </div>

                    <div class="col-lg-8 col-md-6 col-sm-12 mb-3 text-right">
                        <button type="button" class="btn btn-primary mr-2" id="btn_actualizar_resumen_inventario">
                            <i class="fas fa-sync-alt"></i> Actualizar resumen
                        </button>

                        <button type="button" class="btn btn-info" id="btn_ver_historico_vendido">
                            <i class="fas fa-receipt"></i> Ver histórico vendido
                        </button>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    El valor disponible se calcula con la existencia actual del inventario multiplicada por el precio seleccionado.
                    El histórico vendido se calcula con las facturas registradas.
                </div>
            </div>
        </div>

        <div class="row inventario-resumen-row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="inventario-resumen-card inventario-resumen-registros">
                    <div>
                        <span class="inventario-resumen-label">
                            <i class="fas fa-boxes mr-1"></i> Productos
                        </span>
                        <h3 id="resumen_total_productos">0</h3>
                        <p>Productos con existencia</p>
                    </div>
                    <div class="inventario-resumen-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="inventario-resumen-card inventario-resumen-saldo">
                    <div>
                        <span class="inventario-resumen-label">
                            <i class="fas fa-cubes mr-1"></i> Unidades
                        </span>
                        <h3 id="resumen_total_unidades">0.00</h3>
                        <p>Disponibles en tienda</p>
                    </div>
                    <div class="inventario-resumen-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="inventario-resumen-card inventario-resumen-entrada">
                    <div>
                        <span class="inventario-resumen-label">
                            <i class="fas fa-money-bill-wave mr-1"></i> Valor disponible
                        </span>
                        <h3 id="resumen_valor_disponible">L. 0.00</h3>
                        <p id="resumen_texto_valorizacion">Calculado por precio de venta</p>
                    </div>
                    <div class="inventario-resumen-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="inventario-resumen-card inventario-resumen-salida">
                    <div>
                        <span class="inventario-resumen-label">
                            <i class="fas fa-receipt mr-1"></i> Histórico vendido
                        </span>
                        <h3 id="resumen_total_historico_vendido">L. 0.00</h3>
                        <p>Total facturado de productos</p>
                    </div>
                    <div class="inventario-resumen-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 inventario-table-card">
            <div class="card-header inventario-card-header">
                <div>
                    <i class="fas fa-store fa-lg mr-1"></i>
                    <strong>Mercadería disponible actualmente</strong>
                    <small class="d-block text-muted mt-1">
                        Existencia actual multiplicada por el precio seleccionado.
                    </small>
                </div>
            </div>

            <div class="card-body">
                <div id="resumenInventarioListado" class="transferencia-resumen-listado"></div>
                <div class="transferencia-list-footer">
                    <span id="resumenInventarioInfo">0 registros</span>
                    <div id="resumenInventarioPaginacion" class="transferencia-pagination"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4 inventario-table-card" id="cardHistoricoVendidoInventario" style="display:none;">
            <div class="card-header inventario-card-header">
                <div>
                    <i class="fas fa-receipt fa-lg mr-1"></i>
                    <strong>Histórico de productos vendidos</strong>
                    <small class="d-block text-muted mt-1">
                        Productos facturados, cantidad vendida y valor real facturado.
                    </small>
                </div>
            </div>

            <div class="card-body">
                <div id="historicoInventarioListado" class="transferencia-historico-listado"></div>
                <div class="transferencia-list-footer">
                    <span id="historicoInventarioInfo">0 registros</span>
                    <div id="historicoInventarioPaginacion" class="transferencia-pagination"></div>
                </div>
            </div>
        </div>
    </div>

    <?php
        $insMainModel->guardar_historial_accesos("Ingreso al modulo Inventario");
    ?>
</div>