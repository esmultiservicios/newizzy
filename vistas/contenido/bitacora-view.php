<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/bitacora.css">

<div class="container-fluid bitacora-page">
    <!-- Bitácora -->
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
                <i class="fas fa-clipboard-list breadcrumb-icon"></i>
                <span>Bitácora</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 bitacora-section-card bitacora-filtro-card" id="bitacoraFiltrosSection">
        <div class="bitacora-section-header">
            <div class="bitacora-section-title">
                <span class="bitacora-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Bitácora</h5>
                    <small>Consulte los registros de bitácora por período.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary bitacora-toggle-btn" id="btnToggleFiltrosBitacora" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="bitacoraFiltrosContenido">
            <form id="formMainBitacora" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                <div class="row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 bitacora-label-filter" for="fechai">Fecha Inicio</label>
                            <div class="input-group bitacora-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="date" class="form-control" id="fechai" name="fechai" value="<?php
                                    $fecha = date("Y-m-d");
                                    $año = date("Y", strtotime($fecha));
                                    $mes = date("m", strtotime($fecha));
                                    $dia = date("d", mktime(0, 0, 0, $mes + 1, 0, $año));
                                    $dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));
                                    $fecha_inicial = date("Y-m-d", strtotime($año . "-" . $mes . "-" . $dia1));
                                    echo $fecha_inicial;
                                ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 bitacora-label-filter" for="fechaf">Fecha Fin</label>
                            <div class="input-group bitacora-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-12 mb-3 d-flex align-items-end">
                        <div class="bitacora-filtro-actions w-100">
                            <button type="submit" class="btn btn-primary" id="search">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                            <button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
                                <i class="fas fa-broom mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card mb-4 bitacora-section-card" id="bitacoraKpisSection">
        <div class="bitacora-section-header">
            <div class="bitacora-section-title">
                <span class="bitacora-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Bitácora</h5>
                    <small>Indicadores calculados sobre el resultado filtrado.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary bitacora-toggle-btn" id="btnToggleKpisBitacora" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="bitacoraKpisContenido">
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="bitacora-resumen-card bitacora-resumen-registros">
                        <div>
                            <div class="bitacora-resumen-label">Total registros</div>
                            <h3 id="bitacora-card-registros">0</h3>
                            <p>Eventos encontrados</p>
                        </div>
                        <div class="bitacora-resumen-icon"><i class="fas fa-list-ol"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="bitacora-resumen-card bitacora-resumen-colaboradores">
                        <div>
                            <div class="bitacora-resumen-label">Colaboradores</div>
                            <h3 id="bitacora-card-colaboradores">0</h3>
                            <p>Colaboradores con actividad</p>
                        </div>
                        <div class="bitacora-resumen-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="bitacora-resumen-card bitacora-resumen-tipos">
                        <div>
                            <div class="bitacora-resumen-label">Tipos</div>
                            <h3 id="bitacora-card-tipos">0</h3>
                            <p>Tipos de registro distintos</p>
                        </div>
                        <div class="bitacora-resumen-icon"><i class="fas fa-tags"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="bitacora-resumen-card bitacora-resumen-finalizados">
                        <div>
                            <div class="bitacora-resumen-label">Con hora final</div>
                            <h3 id="bitacora-card-finalizados">0</h3>
                            <p>Registros con cierre de hora</p>
                        </div>
                        <div class="bitacora-resumen-icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 bitacora-section-card bitacora-table-card">
        <div class="bitacora-section-header">
            <div class="bitacora-section-title">
                <span class="bitacora-section-icon"><i class="fas fa-clipboard-list"></i></span>
                <div>
                    <h5 class="mb-0">Bitácora</h5>
                    <small>Registro de actividad filtrado por período.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="bitacora-list-toolbar">
                <div class="bitacora-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnBitacoraActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnBitacoraExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnBitacoraPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="bitacora-toolbar-right">
                    <label class="bitacora-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="bitacoraPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="bitacora-view-switch">
                        <button type="button" class="bitacora-view-btn active" data-view="detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="bitacora-view-btn" data-view="miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="bitacora-search-wrap">
                        <span class="bitacora-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarBitacoraListado" class="form-control" placeholder="Buscar en bitácora..." autocomplete="off">
                        <button type="button" id="limpiarBuscarBitacoraListado" class="bitacora-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="bitacoraListado" class="bitacora-listado vista-detalle"></div>

            <div class="bitacora-list-footer">
                <span id="bitacoraInfo">0 registros</span>
                <div id="bitacoraPaginacion" class="bitacora-pagination"></div>
            </div>
        </div>

        <div class="card-footer small bitacora-card-footer">
            <div class="row">
                <div class="col-12">
                    <?php
                        require_once "./core/mainModel.php";

                        $insMainModel = new mainModel();
                        $entidad = "bitacora";

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
    </section>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Bitacora");
?>
