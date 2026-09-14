<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/confCtaContabilidad.css">

<div class="container-fluid confcta-page">
    <!-- Configuración de Cuenta -->
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
                <i class="fas fa-cog breadcrumb-icon"></i>
                <span>Configuración de Cuenta</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card confcta-section-card mb-4" id="confCtaFiltrosSection">
        <div class="confcta-section-header">
            <div class="confcta-section-heading">
                <span class="confcta-section-icon">
                    <i class="fas fa-filter"></i>
                </span>
                <div>
                    <h5>Filtros de Configuración</h5>
                    <p>Consulte las entidades y cuentas configuradas sin alterar los registros.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary confcta-toggle-section" data-target="#confCtaFiltrosBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="confcta-section-body" id="confCtaFiltrosBody">
            <form id="formConfCtaFiltros" autocomplete="off">
                <div class="confcta-filter-grid">
                    <div class="confcta-filter-field">
                        <label for="confCtaFiltroEntidad">
                            <i class="fas fa-sitemap mr-1"></i>
                            Entidad
                        </label>
                        <select id="confCtaFiltroEntidad" class="form-control confcta-select2" data-placeholder="Todas las entidades">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="confcta-filter-field">
                        <label for="confCtaFiltroCuenta">
                            <i class="fas fa-wallet mr-1"></i>
                            Cuenta
                        </label>
                        <select id="confCtaFiltroCuenta" class="form-control confcta-select2" data-placeholder="Todas las cuentas">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="confcta-filter-actions">
                        <button type="submit" class="btn btn-primary" id="btnConfCtaFiltrar">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>

                        <button type="reset" class="btn btn-info" id="btnConfCtaLimpiarFiltros">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- KPIs -->
    <section class="card confcta-section-card mb-4" id="confCtaKpisSection">
        <div class="confcta-section-header">
            <div class="confcta-section-heading">
                <span class="confcta-section-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                <div>
                    <h5>Resumen de Configuración</h5>
                    <p>Estado general de las cuentas asociadas a las entidades contables.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary confcta-toggle-section" data-target="#confCtaKpisBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="confcta-section-body" id="confCtaKpisBody">
            <div class="confcta-kpi-grid">
                <article class="confcta-kpi confcta-kpi-primary">
                    <div class="confcta-kpi-copy">
                        <span class="confcta-kpi-label">ENTIDADES</span>
                        <strong id="confCtaKpiEntidades">0</strong>
                        <small>Configuraciones registradas</small>
                    </div>
                    <span class="confcta-kpi-icon"><i class="fas fa-sitemap"></i></span>
                </article>

                <article class="confcta-kpi confcta-kpi-success">
                    <div class="confcta-kpi-copy">
                        <span class="confcta-kpi-label">CONFIGURADAS</span>
                        <strong id="confCtaKpiConfiguradas">0</strong>
                        <small>Entidades con cuenta asignada</small>
                    </div>
                    <span class="confcta-kpi-icon"><i class="fas fa-check-circle"></i></span>
                </article>

                <article class="confcta-kpi confcta-kpi-info">
                    <div class="confcta-kpi-copy">
                        <span class="confcta-kpi-label">CUENTAS ÚNICAS</span>
                        <strong id="confCtaKpiCuentas">0</strong>
                        <small>Cuentas contables en uso</small>
                    </div>
                    <span class="confcta-kpi-icon"><i class="fas fa-wallet"></i></span>
                </article>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card confcta-section-card mb-4">
        <div class="confcta-section-header">
            <div class="confcta-section-heading">
                <span class="confcta-section-icon">
                    <i class="fas fa-tasks"></i>
                </span>
                <div>
                    <h5>Configuración de Cuenta</h5>
                    <p>Administre la cuenta contable asociada a cada entidad.</p>
                </div>
            </div>

            <button type="button" class="btn btn-primary confcta-toggle-section" data-target="#confCtaListadoBody" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i>
                <span>Ocultar</span>
            </button>
        </div>

        <div class="confcta-section-body" id="confCtaListadoBody">
            <div class="confcta-toolbar">
                <div class="confcta-toolbar-left">
                    <button type="button" class="btn btn-info ocultar" id="btnConfCtaActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>

                    <button type="button" class="btn btn-success ocultar" id="btnConfCtaExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>

                    <button type="button" class="btn btn-danger ocultar" id="btnConfCtaPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="confcta-toolbar-right">
                    <label class="confcta-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="confCtaPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="confcta-view-switch" role="group" aria-label="Vista de configuración de cuentas">
                        <button type="button" class="confcta-view-btn active" data-view="detalle" aria-pressed="true">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="confcta-view-btn" data-view="miniatura" aria-pressed="false">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="confcta-search-wrap">
                        <span class="confcta-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="confCtaBuscar" class="form-control" placeholder="Buscar configuración..." autocomplete="off">
                        <button type="button" id="confCtaBuscarLimpiar" class="confcta-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="confcta-detail-header" aria-hidden="true">
                <div>Acciones</div>
                <div>Entidad</div>
                <div>Cuenta</div>
            </div>

            <div id="confCtaListado" class="confcta-listado vista-detalle"></div>

            <div class="confcta-list-footer">
                <span id="confCtaInfo">0 registros</span>
                <div id="confCtaPaginacion" class="confcta-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted confcta-last-update">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "diarios";

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
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Configuración de Cuentas");
?>
