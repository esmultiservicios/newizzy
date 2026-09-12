<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/inventario.css">

<div class="container-fluid">
    <!-- Movimientos y Registro -->
    <div class="breadcrumb-container">
        <ol class="breadcrumb-harmony">
            <li class="breadcrumb-item">
                <a class="breadcrumb-link" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
                    <i class="fas fa-home breadcrumb-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-separator">/</li>
            <li class="breadcrumb-item" id="movimientos">
                <i class="fas fa-exchange-alt breadcrumb-icon"></i>
                <span>Movimientos</span>
            </li>
            <li class="breadcrumb-separator">/</li>
            <li class="breadcrumb-item active" id="registroMovimientos">
                <i class="fas fa-clipboard-list breadcrumb-icon"></i>
                <span>Registro Movimiento de Productos</span>
            </li>
        </ol>
    </div>

    
<div id="main_inventario" class="movimientos-page">

    <!-- FILTROS -->
    <section class="card movimientos-section-card mb-4" id="movimientosFiltrosSection">
        <div class="movimientos-section-header">
            <div class="movimientos-section-title">
                <span class="movimientos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Movimientos</h5>
                    <small>Consulte movimientos por categoría, bodega, producto, cliente y fechas.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary movimientos-toggle-btn"
                    id="btnToggleFiltrosMovimientos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="movimientosFiltrosContenido">
            <form id="form_main_movimientos" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="inventario_tipo_productos_id">
                            <i class="fas fa-tags mr-1"></i> Categoría
                        </label>
                        <select id="inventario_tipo_productos_id" name="inventario_tipo_productos_id"
                                class="form-control selectpicker" data-live-search="true"
                                data-toggle="tooltip" data-placement="top"
                                title="Categoría de Productos" data-width="100%">
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="almacen">
                            <i class="fas fa-warehouse mr-1"></i> Bodega
                        </label>
                        <select id="almacen" name="almacen"
                                class="form-control selectpicker"
                                data-live-search="true"
                                title="Bodega" data-width="100%">
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="producto_movimiento_filtro">
                            <i class="fas fa-box-open mr-1"></i> Producto
                        </label>
                        <select id="producto_movimiento_filtro" name="producto_movimiento_filtro"
                                class="form-control selectpicker"
                                data-live-search="true"
                                title="Producto" data-width="100%">
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="cliente_movimiento_filtro">
                            <i class="fas fa-user mr-1"></i> Cliente
                        </label>
                        <select id="cliente_movimiento_filtro" name="cliente_movimiento_filtro"
                                class="form-control selectpicker"
                                data-live-search="true"
                                title="Cliente" data-width="100%">
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="fechai">
                            <i class="fas fa-calendar-alt mr-1"></i> Fecha Inicio
                        </label>
                        <div class="input-group movimientos-date-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                            </div>
                            <input type="date" class="form-control" id="fechai" name="fechai" value="<?php
                                $fecha = date("Y-m-d");
                                $año = date("Y", strtotime($fecha));
                                $mes = date("m", strtotime($fecha));
                                $dia = date("d", mktime(0, 0, 0, $mes + 1, 0, $año));
                                $dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));
                                $dia2 = date('d', mktime(0, 0, 0, $mes, $dia, $año));
                                $fecha_inicial = date("Y-m-d", strtotime($año . "-" . $mes . "-" . $dia1));
                                echo htmlspecialchars($fecha_inicial, ENT_QUOTES, 'UTF-8');
                            ?>">
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-3">
                        <label class="movimientos-label-filter" for="fechaf">
                            <i class="fas fa-calendar-check mr-1"></i> Fecha Fin
                        </label>
                        <div class="input-group movimientos-date-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar-check"></i>
                                </span>
                            </div>
                            <input type="date" class="form-control" id="fechaf" name="fechaf"
                                   value="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-4 col-md-12 col-12 mb-3">
                        <div class="movimientos-filter-actions">
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
    <section class="card movimientos-section-card mb-4" id="movimientosKpisSection">
        <div class="movimientos-section-header">
            <div class="movimientos-section-title">
                <span class="movimientos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Movimientos</h5>
                    <small>Indicadores calculados sobre los registros filtrados actualmente.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary movimientos-toggle-btn"
                    id="btnToggleKpisMovimientos" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="movimientosKpisContenido">
            <div class="row movimientos-kpi-row">
                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="movimientos-kpi movimientos-kpi-primary">
                        <div class="movimientos-kpi-copy">
                            <span class="movimientos-kpi-label">Movimientos</span>
                            <strong id="movimientos_total_registros">0</strong>
                            <small>Registros filtrados</small>
                        </div>
                        <span class="movimientos-kpi-icon"><i class="fas fa-exchange-alt"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="movimientos-kpi movimientos-kpi-success">
                        <div class="movimientos-kpi-copy">
                            <span class="movimientos-kpi-label">Entradas</span>
                            <strong id="movimientos_total_entrada">0.00</strong>
                            <small>Total de entradas</small>
                        </div>
                        <span class="movimientos-kpi-icon"><i class="fas fa-sign-in-alt"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="movimientos-kpi movimientos-kpi-danger">
                        <div class="movimientos-kpi-copy">
                            <span class="movimientos-kpi-label">Salidas</span>
                            <strong id="movimientos_total_salida">0.00</strong>
                            <small>Total de salidas</small>
                        </div>
                        <span class="movimientos-kpi-icon"><i class="fas fa-sign-out-alt"></i></span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 col-12 mb-3">
                    <div class="movimientos-kpi movimientos-kpi-purple">
                        <div class="movimientos-kpi-copy">
                            <span class="movimientos-kpi-label">Balance</span>
                            <strong id="movimientos_total_balance">0.00</strong>
                            <small>Entradas menos salidas</small>
                        </div>
                        <span class="movimientos-kpi-icon"><i class="fas fa-balance-scale"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card movimientos-section-card movimientos-directory-card mb-4" id="movimientosListadoSection">
        <div class="movimientos-directory-header">
            <div class="movimientos-section-title">
                <span class="movimientos-section-icon"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <h5 class="mb-0">Movimiento de Productos</h5>
                    <small>Entradas, salidas, saldos, producto, documento, lote, cliente y bodega.</small>
                </div>
            </div>
        </div>

        <div class="card-body movimientos-directory-body">
            <div class="movimientos-list-toolbar">
                <div class="movimientos-toolbar-left">
                    <button type="button" id="btnActualizarMovimientos"
                            class="btn btn-secondary table_actualizar ocultar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" id="btnNuevoMovimiento"
                            class="btn btn-primary table_crear ocultar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" id="btnAjusteInventario"
                            class="btn btn-warning table_crear ocultar">
                        <i class="fas fa-balance-scale mr-1"></i> Ajuste Inventario
                    </button>

                    <button type="button" id="btnAuditoriaAjustes"
                            class="btn btn-info table_crear ocultar">
                        <i class="fas fa-clipboard-check mr-1"></i> Auditoría Ajustes
                    </button>

                    <button type="button" id="btnExcelMovimientos"
                            class="btn btn-success table_reportes ocultar">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" id="btnPdfMovimientos"
                            class="btn btn-danger table_reportes ocultar">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="movimientos-list-tools-right">
                    <label class="movimientos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="movimientosPageSize" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </label>

                    <div class="movimientos-view-switch" role="group" aria-label="Tipo de vista">
                        <button type="button" class="movimientos-view-btn active"
                                data-view="detalle" aria-pressed="true" title="Vista detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="movimientos-view-btn"
                                data-view="miniatura" aria-pressed="false" title="Vista miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="movimientos-search">
                        <span class="movimientos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscar_movimientos_general"
                               class="form-control"
                               placeholder="Buscar movimiento..."
                               autocomplete="off">
                        <button type="button" id="limpiarBuscarMovimientos"
                                class="movimientos-search-clear"
                                title="Limpiar búsqueda"
                                aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="movimientosListado"
                 class="movimientos-listado vista-detalle"
                 aria-live="polite"></div>

            <div class="movimientos-totales-strip" id="movimientosTotalesStrip">
                <div>
                    <span>Saldo anterior</span>
                    <strong id="movimientos_total_anterior_listado">0.00</strong>
                </div>
                <div>
                    <span>Entradas</span>
                    <strong id="movimientos_total_entrada_listado">0.00</strong>
                </div>
                <div>
                    <span>Salidas</span>
                    <strong id="movimientos_total_salida_listado">0.00</strong>
                </div>
                <div>
                    <span>Saldo final</span>
                    <strong id="movimientos_total_saldo_listado">0.00</strong>
                </div>
            </div>

            <div class="movimientos-list-footer">
                <span id="movimientosInfo" class="movimientos-list-info">0 registros</span>
                <div id="movimientosPaginacion" class="movimientos-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "movimientos";

                if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');

                    echo "Última Actualización " .
                        htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>
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
                        action="<?php echo SERVERURL; ?>ajax/addComprasAjax.php" method="POST" data-form="save"
                        autocomplete="off" enctype="multipart/form-data">

                        <div class="form-group row">
                            <div class="col-sm-6">
                                <button class="btn btn-primary" type="submit" id="reg_factura"
                                    form="formMovimientoInventario" data-toggle="tooltip" data-placement="top"
                                    title="Registrar Factura de Compra">
                                    <div class="sb-nav-link-icon"></div>
                                    <i class="far fa-save fa-lg"></i> Registrar
                                </button>
                            </div>

                            <label for="fechaMovimientoInventario" class="col-sm-1 col-form-label-md">
                                Fecha <span class="priority">*</span>
                            </label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required
                                    id="fechaMovimientoInventario" name="fechaMovimientoInventario"
                                    data-toggle="tooltip" data-placement="top" title="Fecha de Facturación">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="movimiento_producto" class="col-sm-1 col-form-label-md">
                                Operación <span class="priority">*</span>
                            </label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <select id="movimiento_producto" name="movimiento_producto" required
                                        class="selectpicker col-12" title="Operación" data-size="7"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <label for="facturaMovimientoInventario" class="col-sm-1 col-form-label-md">
                                Factura
                            </label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" placeholder="Número de Factura de Registro"
                                    id="facturaMovimientoInventario" name="facturaMovimientoInventario"
                                    data-toggle="tooltip" data-placement="top" title="Factura Compra" maxlength="19" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="cliente_movimientos" class="col-sm-1 col-form-label-md">
                                Cliente
                            </label>
                            <div class="col-sm-5">
                                <div class="input-group mb-3">
                                    <select id="cliente_movimientos" name="cliente_movimientos"
                                        class="selectpicker col-12" title="Cliente" data-size="7"
                                        data-live-search="true">
                                    </select>
                                </div>
                            </div>

                            <label for="almacen_modal" class="col-sm-1 col-form-label-md">
                                Almacén <span class="priority">*</span>
                            </label>
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
                                            <th width="2%" scope="col">
                                                <input id="checkAllMovimientoInventario" class="formcontrol" type="checkbox">
                                            </th>
                                            <th width="23.5%">Nombre Producto</th>
                                            <th width="9.5%">Cantidad</th>
                                            <th width="11.5%">Medida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input class="itemRowInventario" type="checkbox">
                                            </td>
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
                                                            title="Búsqueda de Productos">
                                                            <a data-toggle="modal" href="#"
                                                                class="btn btn-outline-success form-control buscar_productos_Inventario">
                                                                <div class="sb-nav-link-icon"></div>
                                                                <i class="fas fa-search-plus fa-lg"></i>
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" name="quantityInventario[]"
                                                    id="quantityInventario_0"
                                                    class="buscar_cantidad_Inventario form-control" autocomplete="off"
                                                    step="0.01">
                                            </td>
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
                                    <div class="sb-nav-link-icon"></div>
                                    <i class="fas fa-plus fa-lg"></i> Agregar
                                </button>

                                <button class="btn btn-success delete bill-bottom-remove" id="removeRowsInventario"
                                    type="button" data-toggle="tooltip" data-placement="top"
                                    title="Remover filas en la factura">
                                    <div class="sb-nav-link-icon"></div>
                                    <i class="fas fa-minus fa-lg"></i> Quitar
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

                    if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                        $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                        $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                        $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');

                        echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
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
</div>