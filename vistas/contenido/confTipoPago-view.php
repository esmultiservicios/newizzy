<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confTipoPago.css">

<div class="container-fluid tipopago-page">
    <!-- Tipo de Pago -->
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
                <i class="fas fa-credit-card breadcrumb-icon"></i>
                <span>Tipo de Pago</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card tipopago-section-card mb-4" id="tipoPagoFiltrosSection">
        <div class="tipopago-section-header">
            <div class="tipopago-section-heading">
                <span class="tipopago-section-icon">
                    <i class="fas fa-filter"></i>
                </span>
                <div>
                    <h5>Filtros de Tipo de Pago</h5>
                    <p>Consulte los tipos de pago por estado sin alterar los registros.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary tipopago-toggle-section" data-target="#tipoPagoFiltrosBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="tipopago-section-body" id="tipoPagoFiltrosBody">
            <form id="form_main_conf_tipoPagos" autocomplete="off">
                <div class="tipopago-filter-grid">
                    <div class="tipopago-filter-field">
                        <label for="estado_conf_tipoPagos">
                            <i class="fas fa-toggle-on mr-1"></i>
                            Estado
                        </label>

                        <select id="estado_conf_tipoPagos"
                                name="estado_conf_tipoPagos"
                                class="form-control tipopago-select2"
                                data-placeholder="Todos los estados">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="tipopago-filter-actions">
                        <button type="submit" class="btn btn-primary" id="search">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>

                        <button type="reset" class="btn btn-info" id="btnTipoPagoLimpiarFiltros">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card tipopago-section-card mb-4" id="tipoPagoKpisSection">
        <div class="tipopago-section-header">
            <div class="tipopago-section-heading">
                <span class="tipopago-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <div>
                    <h5>Resumen de Tipos de Pago</h5>
                    <p>Indicadores calculados sobre el resultado filtrado.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary tipopago-toggle-section" data-target="#tipoPagoKpisBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="tipopago-section-body" id="tipoPagoKpisBody">
            <div class="tipopago-kpi-grid">
                <article class="tipopago-kpi tipopago-kpi-primary">
                    <div class="tipopago-kpi-copy">
                        <span class="tipopago-kpi-label">REGISTROS</span>
                        <strong id="tipoPagoKpiRegistros">0</strong>
                        <small>Tipos de pago encontrados</small>
                    </div>
                    <span class="tipopago-kpi-icon"><i class="fas fa-list-ol"></i></span>
                </article>

                <article class="tipopago-kpi tipopago-kpi-success">
                    <div class="tipopago-kpi-copy">
                        <span class="tipopago-kpi-label">ACTIVOS</span>
                        <strong id="tipoPagoKpiActivos">0</strong>
                        <small>Disponibles para utilizar</small>
                    </div>
                    <span class="tipopago-kpi-icon"><i class="fas fa-check-circle"></i></span>
                </article>

                <article class="tipopago-kpi tipopago-kpi-danger">
                    <div class="tipopago-kpi-copy">
                        <span class="tipopago-kpi-label">INACTIVOS</span>
                        <strong id="tipoPagoKpiInactivos">0</strong>
                        <small>Registros deshabilitados</small>
                    </div>
                    <span class="tipopago-kpi-icon"><i class="fas fa-ban"></i></span>
                </article>

                <article class="tipopago-kpi tipopago-kpi-info">
                    <div class="tipopago-kpi-copy">
                        <span class="tipopago-kpi-label">CUENTAS</span>
                        <strong id="tipoPagoKpiCuentas">0</strong>
                        <small>Cuentas contables en uso</small>
                    </div>
                    <span class="tipopago-kpi-icon"><i class="fas fa-wallet"></i></span>
                </article>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card tipopago-section-card mb-4">
        <div class="tipopago-section-header">
            <div class="tipopago-section-heading">
                <span class="tipopago-section-icon">
                    <i class="fas fa-credit-card"></i>
                </span>
                <div>
                    <h5>Tipo de Pago</h5>
                    <p>Administre los medios de pago, cuenta contable y estado.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary tipopago-toggle-section" data-target="#tipoPagoListadoBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="tipopago-section-body" id="tipoPagoListadoBody">
            <div class="tipopago-toolbar">
                <div class="tipopago-toolbar-left">
                    <button type="button" class="btn btn-info table_actualizar ocultar" id="btnTipoPagoActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnTipoPagoIngresar">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnTipoPagoExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnTipoPagoPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="tipopago-toolbar-right">
                    <label class="tipopago-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="tipoPagoPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="tipopago-view-switch" role="group" aria-label="Vista de tipos de pago">
                        <button type="button" class="tipopago-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="tipopago-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="tipopago-search-wrap">
                        <span class="tipopago-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="tipoPagoBuscar" class="form-control" placeholder="Buscar tipo de pago..." autocomplete="off">
                        <button type="button" id="tipoPagoBuscarLimpiar" class="tipopago-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="tipopago-detail-header" aria-hidden="true">
                <div>Acciones</div>
                <div>Nombre</div>
                <div>Código</div>
                <div>Cuenta</div>
                <div>Estado</div>
            </div>

            <div id="tipoPagoListado" class="tipopago-listado vista-detalle"></div>

            <div class="tipopago-list-footer">
                <span id="tipoPagoInfo">0 registros</span>
                <div id="tipoPagoPaginacion" class="tipopago-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted tipopago-last-update">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "tipo_pago";

                if($insMainModel->getlastUpdate($entidad)->num_rows > 0){
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización ".htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                }else{
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración Tipo de Pago");
?>
