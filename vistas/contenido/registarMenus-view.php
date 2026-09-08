<link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/registarMenus.css">

<div class="container-fluid menus-page" id="div_top">
    <!-- Administrar Menús -->
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
                <i class="fas fa-list-alt breadcrumb-icon"></i>
                <span>Administrar Menús</span>
            </li>
        </ol>
    </div>

    <!-- Formulario para registrar/editar elementos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-1"></i>
            <span id="form_title">Registrar Nuevo Elemento de Menú</span>
        </div>
        <div class="card-body">
            <form id="formulario_menu">
                <input type="hidden" id="menu_id" name="menu_id" value="">
                
                <!-- Primera fila: Tipo y Nombre -->
                <div class="form-row" style="margin-bottom: 0.5rem;">
                    <div class="form-group col-md-3">
                        <label for="tipo_menu">Tipo de Elemento</label>
                        <select class="form-control selectpicker" id="tipo_menu" name="tipo_menu" required>
                            <option value="">Seleccionar...</option>
                            <option value="menu">Menú Principal</option>
                            <option value="submenu">Submenú Nivel 1</option>
                            <option value="submenu1">Submenú Nivel 2</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3" id="dependencia_menu_group" style="display:none;">
                        <label id="label_dependencia">Dependencia</label>
                        <select class="form-control selectpicker" id="dependencia_menu" name="dependencia_menu" data-live-search="true">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="nombre_menu">Nombre (Código)</label>
                        <input type="text" class="form-control" id="nombre_menu" name="nombre_menu" required maxlength="25" placeholder="Ej: ventas, clientes">
                        <small class="form-text text-muted" style="margin-top: 0.25rem;">Nombre interno (sin espacios)</small>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="descripcion_menu">Descripción</label>
                        <input type="text" class="form-control" id="descripcion_menu" name="descripcion_menu" required maxlength="50" placeholder="Nombre visible en el menú">
                    </div>
                </div>
                
                <!-- Segunda fila: Ícono, Orden y Visibilidad -->
                <div class="form-row" style="margin-bottom: 0.5rem;">
                    <div class="form-group col-md-4">
                        <label for="icono_menu">Ícono (FontAwesome)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i id="icono_preview" class="fas fa-question"></i></span>
                            </div>
                            <input type="text" class="form-control" id="icono_menu" name="icono_menu" placeholder="Ej: fas fa-home fa-lg">
                        </div>
                        <div style="margin-top: 0.25rem;">
                            <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank" 
                            class="icon-explorer-link">
                                <i class="fas fa-external-link-alt"></i>Explorar íconos disponibles
                            </a>
                        </div>
                    </div>
                                        
                    <div class="form-group col-md-4">
                        <label for="orden_menu">Orden</label>
                        <input type="number" class="form-control" id="orden_menu" name="orden_menu" min="0" value="0">
                        <small class="form-text text-muted" style="margin-top: 0.25rem;">Define el orden de aparición</small>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" id="visible_menu" name="visible_menu" checked>
                            <label class="form-check-label" for="visible_menu">Mostrar en menú lateral</label>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="form-row" style="margin-top: 1rem;">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-secondary mr-2" id="btnCancelarEdicion" style="display:none;">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnAccionMenu">
                            <i class="fas fa-save mr-1"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 menus-section-card">
        <div class="menus-section-header">
            <div class="menus-section-title">
                <div class="menus-section-icon"><i class="fas fa-filter"></i></div>
                <div>
                    <h5>Filtros de menús</h5>
                    <p>Refine los elementos por tipo, visibilidad o búsqueda.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary menus-toggle-btn" id="btn_toggle_menus_filtros" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="menus-section-body" id="menus_filtros_body">
            <form id="form_filtros_menus">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <label for="menus_filtro_tipo">Tipo</label>
                        <select id="menus_filtro_tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="menu">Menú Principal</option>
                            <option value="submenu">Submenú Nivel 1</option>
                            <option value="submenu1">Submenú Nivel 2</option>
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-3">
                        <label for="menus_filtro_visible">Visibilidad</label>
                        <select id="menus_filtro_visible" class="form-control">
                            <option value="">Todos</option>
                            <option value="1">Visible</option>
                            <option value="0">Oculto</option>
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-12 mb-3 menus-filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI -->
    <div class="card mb-4 menus-section-card">
        <div class="menus-section-header">
            <div class="menus-section-title">
                <div class="menus-section-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h5>Resumen de menús</h5>
                    <p>Indicadores calculados sobre los registros filtrados.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary menus-toggle-btn" id="btn_toggle_menus_kpis" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i> Ocultar
            </button>
        </div>

        <div class="menus-section-body" id="menus_kpis_body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="menus-kpi-card">
                        <div><span>Total</span><h3 id="menus_kpi_total">0</h3><p>Elementos filtrados</p></div>
                        <div class="menus-kpi-icon"><i class="fas fa-list"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="menus-kpi-card">
                        <div><span>Visibles</span><h3 id="menus_kpi_visibles">0</h3><p>Disponibles en menú</p></div>
                        <div class="menus-kpi-icon"><i class="fas fa-eye"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="menus-kpi-card">
                        <div><span>Ocultos</span><h3 id="menus_kpi_ocultos">0</h3><p>No visibles</p></div>
                        <div class="menus-kpi-icon"><i class="fas fa-eye-slash"></i></div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="menus-kpi-card">
                        <div><span>Dependientes</span><h3 id="menus_kpi_dependientes">0</h3><p>Submenús configurados</p></div>
                        <div class="menus-kpi-icon"><i class="fas fa-sitemap"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado -->
    <div class="card mb-4 menus-list-card">
        <div class="menus-section-header">
            <div class="menus-section-title">
                <div class="menus-section-icon"><i class="fas fa-bars"></i></div>
                <div>
                    <h5>Menús registrados</h5>
                    <p>Administración de menús principales, submenús y visibilidad.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="menus-toolbar">
                <div class="menus-toolbar-actions">
                    <button type="button" class="btn btn-info" id="btn_actualizar_menus">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary" id="btn_nuevo_menu">
                        <i class="fas fa-plus mr-1"></i> Nuevo
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btn_exportar_menus_excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btn_exportar_menus_pdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="menus-toolbar-tools">
                    <div class="menus-page-size">
                        <label for="menus_page_size">Mostrar</label>
                        <select id="menus_page_size" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </div>

                    <div class="menus-view-switch">
                        <button type="button" class="menus-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list-ul"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="menus-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="menus-search">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="search" class="form-control" id="menus_buscar" placeholder="Buscar menú..." autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <div id="menus_listado" class="menus-listado vista-detalle"></div>

            <div id="menus_empty" class="menus-empty d-none">
                <i class="fas fa-bars"></i>
                <h5>No se encontraron registros</h5>
                <p>No hay elementos que coincidan con los filtros actuales.</p>
            </div>

            <div class="menus-list-footer">
                <div id="menus_resultado_info" class="menus-result-info">Mostrando 0 registros</div>
                <nav id="menus_paginacion" class="menus-pagination"></nav>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "menu";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Menús");
?>