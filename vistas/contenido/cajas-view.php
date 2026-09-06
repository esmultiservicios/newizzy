<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/cajas.css">

<div class="container-fluid">
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
                <i class="fas fa-cash-register breadcrumb-icon"></i>
                <span>Cajas</span>
            </li>
        </ol>
    </div>

    <!-- =====================================================
         FILTROS
         ===================================================== -->
    <div class="card mb-4 cajas-section-card">
        <div class="cajas-section-header">
            <div class="cajas-section-title">
                <div class="cajas-section-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <div>
                    <h5>Filtros de cajas</h5>
                    <p>Consulte cajas por estado y período sin perder las acciones operativas del módulo.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary cajas-toggle-btn"
                    id="btnToggleFiltrosCajas"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="cajas-section-body" id="cajasFiltrosContenido">
            <form id="formMainCajas">
                <div class="row align-items-end">
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_cajas"
                                    name="estado_cajas"
                                    class="form-control selectpicker"
                                    title="Estado"
                                    data-live-search="true">
                                <option value="0">Todas</option>
                                <option value="1">Activas</option>
                                <option value="2">Cerrada</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-day mr-1"></i> Fecha Inicial
                            </label>
                            <input type="date"
                                   class="form-control"
                                   id="fecha_cajas"
                                   name="fecha_cajas"
                                   value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1">
                                <i class="fas fa-calendar-check mr-1"></i> Fecha Final
                            </label>
                            <input type="date"
                                   class="form-control"
                                   id="fecha_cajas_f"
                                   name="fecha_cajas_f"
                                   value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-12 mb-3">
                        <div class="cajas-filter-buttons">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" class="btn btn-secondary" id="btnLimpiarCajas">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="cajas-period-actions">
                    <button type="button" class="btn btn-info" id="btnGananciaPeriodo">
                        <i class="fas fa-chart-pie mr-1"></i> Ganancia del Período
                    </button>

                    <button type="button" class="btn btn-warning" id="btnRetirosPeriodo">
                        <i class="fas fa-money-bill-wave mr-1"></i> Retiros del Período
                    </button>

                    <button type="button" class="btn btn-success" id="btnCuadreDia">
                        <i class="fas fa-balance-scale mr-1"></i> Cuadre del Día
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================
         KPI
         ===================================================== -->
    <div class="card mb-4 cajas-section-card">
        <div class="cajas-section-header">
            <div class="cajas-section-title">
                <div class="cajas-section-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h5>Resumen de cajas</h5>
                    <p>Indicadores calculados sobre los registros actualmente filtrados.</p>
                </div>
            </div>

            <button type="button"
                    class="btn btn-primary cajas-toggle-btn"
                    id="btnToggleKpisCajas"
                    aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="cajas-section-body" id="cajasKpisContenido">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="cajas-kpi-card cajas-kpi-registros">
                        <div>
                            <span class="cajas-kpi-label">
                                <i class="fas fa-list-ul mr-1"></i> Registros
                            </span>
                            <h3 id="cajasKpiRegistros">0</h3>
                            <p>Cajas filtradas</p>
                        </div>
                        <div class="cajas-kpi-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="cajas-kpi-card cajas-kpi-abiertas">
                        <div>
                            <span class="cajas-kpi-label">
                                <i class="fas fa-lock-open mr-1"></i> Cajas abiertas
                            </span>
                            <h3 id="cajasKpiAbiertas">0</h3>
                            <p>Disponibles para operar</p>
                        </div>
                        <div class="cajas-kpi-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="cajas-kpi-card cajas-kpi-ventas">
                        <div>
                            <span class="cajas-kpi-label">
                                <i class="fas fa-receipt mr-1"></i> Venta del período
                            </span>
                            <h3 id="cajasKpiVentas">L. 0.00</h3>
                            <p>Ventas registradas</p>
                        </div>
                        <div class="cajas-kpi-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="cajas-kpi-card cajas-kpi-neto">
                        <div>
                            <span class="cajas-kpi-label">
                                <i class="fas fa-wallet mr-1"></i> Neto de caja
                            </span>
                            <h3 id="cajasKpiNeto">L. 0.00</h3>
                            <p>Apertura + ventas − retiros</p>
                        </div>
                        <div class="cajas-kpi-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================
         LISTADO
         ===================================================== -->
    <div class="card mb-4 cajas-list-card">
        <div class="cajas-section-header">
            <div class="cajas-section-title">
                <div class="cajas-section-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div>
                    <h5>Registro de cajas</h5>
                    <p>Aperturas, ventas, retiros, neto y acciones operativas.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="cajas-list-toolbar">
                <div class="cajas-list-actions">
                    <button type="button"
                            class="btn btn-info table_actualizar ocultar"
                            id="btnActualizarCajas">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button"
                            class="btn btn-success table_reportes ocultar"
                            id="btnExcelCajas">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button"
                            class="btn btn-danger table_reportes ocultar"
                            id="btnPdfCajas">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="cajas-list-tools">
                    <div class="cajas-page-size">
                        <label for="cajasPageSize">Mostrar</label>
                        <select id="cajasPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </div>

                    <div class="cajas-view-switch"
                         role="group"
                         aria-label="Tipo de vista de cajas">
                        <button type="button"
                                class="cajas-view-btn active"
                                data-view="detalle"
                                aria-pressed="true">
                            <i class="fas fa-list-ul"></i>
                            <span>Detalle</span>
                        </button>
                        <button type="button"
                                class="cajas-view-btn"
                                data-view="miniatura"
                                aria-pressed="false">
                            <i class="fas fa-th-large"></i>
                            <span>Miniatura</span>
                        </button>
                    </div>

                    <div class="cajas-search-box">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="search"
                                   id="buscarCajas"
                                   class="form-control"
                                   placeholder="Buscar caja..."
                                   autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="cajasListado"
                 class="cajas-listado vista-detalle"></div>

            <div id="cajasVacio"
                 class="cajas-empty-state"
                 style="display:none;">
                <i class="fas fa-cash-register"></i>
                <h5>No se encontraron cajas</h5>
                <p>No hay registros que coincidan con los filtros aplicados.</p>
            </div>

            <div class="cajas-list-footer">
                <div id="cajasInfo" class="cajas-list-info">0 registros</div>
                <nav id="cajasPaginacion"
                     class="cajas-pagination"
                     aria-label="Paginación de cajas"></nav>
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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Cajas");
?>
