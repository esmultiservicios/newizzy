<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/productos.css">

<div class="container-fluid productos-page">
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

    <!-- FILTROS -->
    <section class="card productos-section-card mb-4" id="productosFiltrosSection">
        <div class="productos-section-header">
            <div class="productos-section-title">
                <span class="productos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros</h5>
                    <small>Refine el catálogo de productos.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary productos-toggle-btn" id="btnToggleFiltrosProductos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="productosFiltrosContenido">
            <form id="form_main_productos" autocomplete="off">
                <div class="form-row align-items-end">
                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="productos-filter-label" for="estado_producto">
                            <i class="fas fa-toggle-on mr-1"></i> Estado
                        </label>
                        <select id="estado_producto" name="estado_producto"
                                class="form-control selectpicker"
                                title="Estado"
                                data-live-search="true"
                                data-width="100%">
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="productos-filter-label" for="categoria_producto_filtro">
                            <i class="fas fa-layer-group mr-1"></i> Categoría
                        </label>
                        <select id="categoria_producto_filtro" name="categoria_producto_filtro"
                                class="form-control selectpicker"
                                title="Categoría"
                                data-live-search="true"
                                data-width="100%">
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 col-12 mb-3">
                        <label class="productos-filter-label" for="isv_producto_filtro">
                            <i class="fas fa-percent mr-1"></i> Impuesto venta
                        </label>
                        <select id="isv_producto_filtro" name="isv_producto_filtro"
                                class="form-control selectpicker"
                                title="ISV"
                                data-live-search="true"
                                data-width="100%">
                            <option value="">Todos</option>
                            <option value="si">Con ISV</option>
                            <option value="no">Sin ISV</option>
                        </select>
                    </div>

                    <div class="col-12 mb-1">
                        <div class="productos-filter-actions">
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
    <section class="card productos-section-card mb-4" id="productosKpisSection">
        <div class="productos-section-header">
            <div class="productos-section-title">
                <span class="productos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Productos</h5>
                    <small>Indicadores del catálogo filtrado actualmente.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary productos-toggle-btn" id="btnToggleKpisProductos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="productosKpisContenido">
            <div class="row productos-kpi-row">
                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="productos-kpi productos-kpi-primary">
                        <div class="productos-kpi-copy">
                            <span class="productos-kpi-label">Productos</span>
                            <strong id="productos_total_registros">0</strong>
                            <small>Registros filtrados</small>
                        </div>
                        <span class="productos-kpi-icon"><i class="fas fa-boxes"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="productos-kpi productos-kpi-success">
                        <div class="productos-kpi-copy">
                            <span class="productos-kpi-label">Activos</span>
                            <strong id="productos_total_activos">0</strong>
                            <small>Productos disponibles</small>
                        </div>
                        <span class="productos-kpi-icon"><i class="fas fa-check-circle"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="productos-kpi productos-kpi-info">
                        <div class="productos-kpi-copy">
                            <span class="productos-kpi-label">Con ISV</span>
                            <strong id="productos_total_isv">0</strong>
                            <small>Calculan impuesto</small>
                        </div>
                        <span class="productos-kpi-icon"><i class="fas fa-percent"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="productos-kpi productos-kpi-purple">
                        <div class="productos-kpi-copy">
                            <span class="productos-kpi-label">Valor venta</span>
                            <strong id="productos_total_venta">L 0.00</strong>
                            <small>Suma de precios de venta</small>
                        </div>
                        <span class="productos-kpi-icon"><i class="fas fa-coins"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card productos-section-card productos-directory-card mb-4" id="productosListadoSection">
        <div class="productos-directory-header">
            <div class="productos-section-title">
                <span class="productos-section-icon"><i class="fab fa-product-hunt"></i></span>
                <div>
                    <h5 class="mb-0">Directorio de Productos</h5>
                    <small>Productos, precios, impuestos, reglas de inventario y estado.</small>
                </div>
            </div>
        </div>

        <div class="card-body productos-directory-body">
            <div class="productos-list-toolbar">
                <div class="productos-toolbar-left">
                    <button type="button" id="btnActualizarProductos"
                            class="btn btn-secondary table_actualizar ocultar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" id="btnNuevoProducto"
                            class="btn btn-primary table_crear ocultar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" id="btnExcelProductos"
                            class="btn btn-success table_reportes ocultar">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" id="btnPdfProductos"
                            class="btn btn-danger table_reportes ocultar">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="productos-list-tools-right">
                    <label class="productos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="productosPageSize" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </label>

                    <div class="productos-view-switch" role="group" aria-label="Tipo de vista">
                        <button type="button" class="productos-view-btn active"
                                data-view="detalle" aria-pressed="true" title="Vista detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="productos-view-btn"
                                data-view="miniatura" aria-pressed="false" title="Vista miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="productos-search">
                        <span class="productos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search"
                               id="buscar_productos_general"
                               name="buscar_productos_general"
                               class="form-control"
                               placeholder="Buscar producto..."
                               autocomplete="off">
                        <button type="button" id="limpiarBuscarProductos"
                                class="productos-search-clear"
                                title="Limpiar búsqueda"
                                aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="productosListado"
                 class="productos-listado vista-detalle"
                 aria-live="polite"></div>

            <div class="productos-list-footer">
                <span id="productosInfo" class="productos-list-info">0 registros</span>
                <div id="productosPaginacion" class="productos-pagination"></div>
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
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>

    <?php
        $insMainModel->guardar_historial_accesos("Ingreso al modulo Productos");
    ?>
</div>
