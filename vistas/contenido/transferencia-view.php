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

    <!-- Filtros -->
    <div class="card mb-4 inventario-filtro-card">
        <div class="card-body">
            <form id="form_main_movimientos_transferencia">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 inventario-label-filter">
                                <i class="fas fa-tags mr-1"></i> Categoría
                            </label>
                            <select id="inventario_tipo_productos_id" name="inventario_tipo_productos_id"
                                class="form-control selectpicker" data-live-search="true" title="Categoría de Productos">
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 inventario-label-filter">
                                <i class="fas fa-box-open mr-1"></i> Producto
                            </label>
                            <select id="inventario_productos_id" name="inventario_productos_id"
                                class="form-control selectpicker" data-live-search="true" title="Productos">
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 inventario-label-filter">
                                <i class="fas fa-warehouse mr-1"></i> Almacén
                            </label>
                            <select id="almacen" name="almacen" class="form-control selectpicker"
                                data-live-search="true" title="Almacén">
                            </select>
                        </div>
                    </div>

                    <div class="col-12 mb-3 text-right">
                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter fa-lg"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-broom fa-lg"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row inventario-resumen-row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="inventario-resumen-card inventario-resumen-registros">
                <div>
                    <span class="inventario-resumen-label">
                        <i class="fas fa-list mr-1"></i> Registros
                    </span>
                    <h3 id="inventario_total_registros">0</h3>
                    <p>Productos filtrados</p>
                </div>
                <div class="inventario-resumen-icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="inventario-resumen-card inventario-resumen-entrada">
                <div>
                    <span class="inventario-resumen-label">
                        <i class="fas fa-arrow-down mr-1"></i> Entradas
                    </span>
                    <h3 id="inventario_total_entrada">0.00</h3>
                    <p>Total acumulado</p>
                </div>
                <div class="inventario-resumen-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="inventario-resumen-card inventario-resumen-salida">
                <div>
                    <span class="inventario-resumen-label">
                        <i class="fas fa-arrow-up mr-1"></i> Salidas
                    </span>
                    <h3 id="inventario_total_salida">0.00</h3>
                    <p>Total acumulado</p>
                </div>
                <div class="inventario-resumen-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="inventario-resumen-card inventario-resumen-saldo">
                <div>
                    <span class="inventario-resumen-label">
                        <i class="fas fa-balance-scale mr-1"></i> Saldo
                    </span>
                    <h3 id="inventario_total_saldo">0.00</h3>
                    <p>Saldo disponible</p>
                </div>
                <div class="inventario-resumen-icon">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card mb-4 inventario-table-card">
        <div class="card-header inventario-card-header">
            <div>
                <i class="fas fa-boxes fa-lg mr-1"></i>
                <strong>Inventario</strong>
                <small class="d-block text-muted mt-1">
                    Consulta de existencias por producto, lote, bodega, entradas, salidas y saldo disponible.
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive inventario-table-responsive">
                <table id="dataTablaMovimientos" class="table table-header-gradient table-striped table-condensed table-hover inventario-table" style="width:100%">
                    <tfoot class="inventario-table-footer">
                        <tr>
                            <td colspan="4" class="text-right inventario-footer-label">Totales</td>
                            <td id="anterior-footer-movimiento" class="text-center"></td>
                            <td id="entrada-footer-movimiento" class="text-center"></td>
                            <td id="salida-footer-movimiento" class="text-center"></td>
                            <td id="total-footer-movimiento" class="text-center"></td>
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
</div>