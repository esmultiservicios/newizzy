<div class="container-fluid">
    <!-- Empresa -->
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
                <i class="fas fa-building breadcrumb-icon"></i>
                <span>Empresa</span>
            </li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 empresa-filtro-card">
        <div class="card-body">
            <form id="form_main_empresa">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 empresa-label-filter">
                                <i class="fas fa-toggle-on mr-1"></i> Estado
                            </label>
                            <select id="estado_empresa" name="estado_empresa" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-5 col-md-5 col-sm-6 mb-3">
                        <div class="form-group mb-0">
                            <label class="small mb-1 empresa-label-filter">
                                <i class="fas fa-search mr-1"></i> Búsqueda rápida
                            </label>
                            <div class="input-group empresa-search-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-building"></i>
                                    </span>
                                </div>
                                <input type="text" id="filtro_empresa_general" class="form-control" placeholder="Buscar por nombre, RTN, correo, teléfono, ubicación...">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-3 col-sm-12 mb-3 text-right">
                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter fa-lg"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary" id="limpiar_empresa">
                            <i class="fas fa-broom fa-lg"></i> Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row empresa-resumen-row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="empresa-resumen-card empresa-resumen-activa">
                <div>
                    <span class="empresa-resumen-label">
                        <i class="fas fa-check-circle mr-1"></i> Empresas activas
                    </span>
                    <h3 id="empresa_total_activas">0</h3>
                    <p>Registros activos filtrados</p>
                </div>
                <div class="empresa-resumen-icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="empresa-resumen-card empresa-resumen-contacto">
                <div>
                    <span class="empresa-resumen-label">
                        <i class="fas fa-phone-alt mr-1"></i> Con contacto
                    </span>
                    <h3 id="empresa_total_contacto">0</h3>
                    <p>Teléfono, celular o correo</p>
                </div>
                <div class="empresa-resumen-icon">
                    <i class="fas fa-address-book"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="empresa-resumen-card empresa-resumen-web">
                <div>
                    <span class="empresa-resumen-label">
                        <i class="fas fa-globe mr-1"></i> Presencia digital
                    </span>
                    <h3 id="empresa_total_web">0</h3>
                    <p>Sitio web o Facebook</p>
                </div>
                <div class="empresa-resumen-icon">
                    <i class="fas fa-wifi"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="empresa-resumen-card empresa-resumen-firma">
                <div>
                    <span class="empresa-resumen-label">
                        <i class="fas fa-file-signature mr-1"></i> Firma visible
                    </span>
                    <h3 id="empresa_total_firma">0</h3>
                    <p>Documento con firma activa</p>
                </div>
                <div class="empresa-resumen-icon">
                    <i class="fas fa-pen-nib"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card mb-4 empresa-table-card">
        <div class="card-header empresa-card-header">
            <div>
                <i class="fas fa-building fa-lg mr-1"></i>
                <strong>Empresa</strong>
                <small class="d-block text-muted mt-1">Información general, contacto, ubicación, redes y configuración fiscal.</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive empresa-table-responsive">
                <table id="dataTableEmpresa" class="table table-header-gradient table-striped table-condensed table-hover empresa-table" style="width:100%"></table>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";

                $insMainModel = new mainModel();
                $entidad = "empresa";

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
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Empresas");
?>