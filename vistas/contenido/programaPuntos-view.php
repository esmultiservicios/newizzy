<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/programaPuntos.css">

<div class="container-fluid programa-puntos-page">
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
                <i class="fas fa-gift breadcrumb-icon"></i>
                <span>Programa de Puntos</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card mb-4 programa-puntos-section-card programa-puntos-filtro-card" id="programa-puntos-filtros-section">
        <div class="programa-puntos-section-header">
            <div class="programa-puntos-section-title">
                <span class="programa-puntos-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Programa de Puntos</h5>
                    <small>Consulte los registros por estado sin alterar la información existente.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary programa-puntos-toggle-btn" id="programa-puntos-toggle-filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="programa-puntos-filtros-contenido">
            <form id="form_main_programa_puntos" autocomplete="off">
                <div class="row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 programa-puntos-label-filter" for="estado_programa_puntos">Estado</label>
                            <select id="estado_programa_puntos" name="estado_programa_puntos" class="form-control izzy-select2">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-8 col-sm-6 mb-3 d-flex align-items-end">
                        <div class="programa-puntos-filter-actions w-100">
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
    <section class="card mb-4 programa-puntos-section-card" id="programa-puntos-kpis-section">
        <div class="programa-puntos-section-header">
            <div class="programa-puntos-section-title">
                <span class="programa-puntos-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Programa de Puntos</h5>
                    <small>Indicadores calculados sobre los registros actualmente filtrados.</small>
                </div>
            </div>

            <button type="button" class="btn btn-primary programa-puntos-toggle-btn" id="programa-puntos-toggle-kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body pb-0" id="programa-puntos-kpis-contenido">
            <div class="row mb-4">

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="programa-puntos-kpi-card programa-puntos-kpi-blue">
                        <div>
                            <div class="programa-puntos-kpi-label">Programas</div>
                            <h3 id="programa-puntos-kpi-total">0</h3>
                            <p>Total de programas encontrados</p>
                        </div>
                        <div class="programa-puntos-kpi-icon"><i class="fas fa-list-ol"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="programa-puntos-kpi-card programa-puntos-kpi-green">
                        <div>
                            <div class="programa-puntos-kpi-label">Activos</div>
                            <h3 id="programa-puntos-kpi-activos">0</h3>
                            <p>Programas actualmente activos</p>
                        </div>
                        <div class="programa-puntos-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="programa-puntos-kpi-card programa-puntos-kpi-teal">
                        <div>
                            <div class="programa-puntos-kpi-label">Por monto</div>
                            <h3 id="programa-puntos-kpi-monto">0</h3>
                            <p>Cálculo configurado por monto</p>
                        </div>
                        <div class="programa-puntos-kpi-icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="programa-puntos-kpi-card programa-puntos-kpi-purple">
                        <div>
                            <div class="programa-puntos-kpi-label">Por porcentaje</div>
                            <h3 id="programa-puntos-kpi-porcentaje">0</h3>
                            <p>Cálculo configurado por porcentaje</p>
                        </div>
                        <div class="programa-puntos-kpi-icon"><i class="fas fa-percentage"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card mb-4 programa-puntos-section-card programa-puntos-list-card">
        <div class="programa-puntos-section-header">
            <div class="programa-puntos-section-title">
                <span class="programa-puntos-section-icon"><i class="fas fa-gift"></i></span>
                <div>
                    <h5 class="mb-0">Programa de Puntos</h5>
                    <small>Listado administrable con vista Detalle y Miniatura.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="programa-puntos-toolbar">
                <div class="programa-puntos-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="programa-puntos-btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-primary table_crear ocultar" id="programa-puntos-btn-create">
                        <i class="fas fa-plus mr-1"></i> Ingresar
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="programa-puntos-btn-excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger table_reportes ocultar" id="programa-puntos-btn-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="programa-puntos-toolbar-right">
                    <label class="programa-puntos-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="programa-puntos-page-size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="programa-puntos-view-switch">
                        <button type="button" class="programa-puntos-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="programa-puntos-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="programa-puntos-search-wrap">
                        <span class="programa-puntos-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="programa-puntos-search" class="form-control" placeholder="Buscar..." autocomplete="off">
                        <button type="button" id="programa-puntos-search-clear" class="programa-puntos-search-clear" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="programa-puntos-listado" class="programa-puntos-listado vista-detalle"></div>

            <div class="programa-puntos-list-footer">
                <span id="programa-puntos-info">0 registros</span>
                <div id="programa-puntos-pagination" class="programa-puntos-pagination"></div>
            </div>

            <!-- DataTable fuente oculto: conserva endpoints, permisos y acciones existentes sin ser listado visible. -->
            <div class="programa-puntos-source-table" aria-hidden="true">
                <table id="dataTableProgramaPuntos" class="table" style="width:100%"></table>
            </div>
        </div>
        <div class="card-footer small izzy-modern-card-footer">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "puestos";

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
    </section>
</div>
<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Programa de Puntos");
?>

<!--INICIO MODAL PROGRAMA PUNTOS-->
<div class="modal fade" id="modalProgramaPuntos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Programa de Puntos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formProgramaPuntos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="programa_puntos_id" name="programa_puntos_id">
                    
                    <!-- Sección de Configuración del Programa -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Configuración del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Columna para el nombre del programa -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre"><i class="fas fa-tag mr-1"></i>Nombre del Programa <span class="priority">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del Programa" required>
                                        <small class="form-text text-muted">Ingrese un nombre descriptivo para el programa</small>
                                    </div>
                                </div>
                                
                                <!-- Columna para el tipo de cálculo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_calculo"><i class="fas fa-calculator mr-1"></i>Tipo de Cálculo <span class="priority">*</span></label>
                                        <select id="tipo_calculo" name="tipo_calculo" required class="izzy-select2 form-control" data-live-search="true" title="Seleccione un tipo de cálculo">
                                            <option value="" disabled>Seleccione una opción</option>
                                            <option value="monto">Por Monto</option>
                                            <option value="porcentaje">Por Porcentaje</option>
                                        </select>
                                        <small class="form-text text-muted">Seleccione cómo se calcularán los puntos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Cálculo de Puntos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Cálculo de Puntos</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group" id="calculo_monto" style="display: none;">
                                <label for="monto"><i class="fas fa-money-bill-wave mr-1"></i>Monto en Lempiras para 1 punto</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="monto" name="monto" placeholder="Ejemplo: 25">
                                    <div class="input-group-append">
                                        <span class="input-group-text">L.</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el monto en Lempiras equivalente a 1 punto</small>
                            </div>
                            
                            <div class="form-group" id="calculo_porcentaje" style="display: none;">
                                <label for="porcentaje"><i class="fas fa-percent mr-1"></i>Porcentaje del Consumo</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="porcentaje" name="porcentaje" placeholder="Ejemplo: 10" min="0" max="100">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Ingrese el porcentaje del consumo que se convertirá en puntos</small>
                            </div>                
                            
                            <div id="ejemplo_calculo" class="form-group" style="display: none;">
                                <div class="alert alert-info">
                                    <p class="mb-0"><i class="fas fa-info-circle mr-2"></i><strong>Ejemplo:</strong> <span id="ejemploTexto"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Estado del Programa</h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ProgramaPuntos_activo" name="ProgramaPuntos_activo" checked>
                                <label class="custom-control-label" for="ProgramaPuntos_activo">Programa Activo</label>
                            </div>
                            <small class="form-text text-muted">Active o desactive el programa de puntos en el sistema</small>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    Cancelar
                </button>
                <button class="btn btn-primary" type="submit" id="reg_ProgramaPuntos" form="formProgramaPuntos">
                    Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_ProgramaPuntos" form="formProgramaPuntos">
                    Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_ProgramaPuntos" form="formProgramaPuntos">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PROGRAMA PUNTOS-->

<!--INICIO MODAL HISTORICO PROGRAMA PUNTOS-->
<div class="modal fade" id="modalHistoricoPuntos" tabindex="-1" role="dialog" aria-labelledby="modalHistoricoPuntosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalHistoricoPuntosLabel"><i class="fas fa-history mr-2"></i>Historial de Puntos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Detalle de acumulación y redención de puntos</h5>
                    </div>
                    <div class="card-body"> 
                        <div class="table-responsive">
                            <table id="tablaHistoricoPuntos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user mr-1"></i> Cliente</th>
                                        <th><i class="fas fa-exchange-alt mr-1"></i> Tipo Movimiento</th>
                                        <th><i class="fas fa-star mr-1"></i> Puntos</th>
                                        <th><i class="fas fa-align-left mr-1"></i> Descripción</th>
                                        <th><i class="fas fa-calendar-alt mr-1"></i> Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán aquí -->
                                </tbody>
                            </table>  
                        </div>                   
                    </div>
                    <div class="card-footer small text-muted">
                        <i class="fas fa-clock mr-1"></i> Última actualización: <span id="fecha-actualizacion"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL HISTORICO PROGRAMA PUNTOS-->
