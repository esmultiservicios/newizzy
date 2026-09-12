<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/chequesContabilidad.css">

<div class="container-fluid cheques-page">
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
                <i class="fas fa-money-check breadcrumb-icon"></i>
                <span>Cheques</span>
            </li>
        </ol>
    </div>

    <!-- FILTROS -->
    <section class="card cheques-section-card mb-4">
        <div class="cheques-section-header">
            <div class="cheques-section-title">
                <span class="cheques-section-icon"><i class="fas fa-filter"></i></span>
                <div>
                    <h5 class="mb-0">Filtros de Cheques</h5>
                    <small>Consulte cheques por estado, período, cuenta, proveedor y categoría.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary cheques-toggle-btn" id="btnToggleFiltrosCheques" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="chequesFiltrosContenido">
            <form id="formMainChequesContabilidad">
                <div class="row">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Estado</label>
                        <select id="estado_cheques" class="form-control selectpicker" data-width="100%" title="Todos">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Anulados</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Fecha Inicio</label>
                        <div class="input-group cheques-input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" class="form-control" id="fechai" name="fechai" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Fecha Fin</label>
                        <div class="input-group cheques-input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" class="form-control" id="fechaf" name="fechaf" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Cuenta</label>
                        <select id="filtro_cuenta_cheques" class="form-control selectpicker" data-live-search="true" data-width="100%" title="Todas"></select>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Proveedor</label>
                        <select id="filtro_proveedor_cheques" class="form-control selectpicker" data-live-search="true" data-width="100%" title="Todos"></select>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <label class="small mb-1">Categoría</label>
                        <select id="filtro_categoria_cheques" class="form-control selectpicker" data-live-search="true" data-width="100%" title="Todas"></select>
                    </div>
                </div>

                <div class="cheques-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-broom mr-1"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- KPI -->
    <section class="card cheques-section-card mb-4" id="chequesKpisSection">
        <div class="cheques-section-header">
            <div class="cheques-section-title">
                <span class="cheques-section-icon"><i class="fas fa-chart-pie"></i></span>
                <div>
                    <h5 class="mb-0">Resumen de Cheques</h5>
                    <small>Indicadores calculados sobre el resultado filtrado.</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary cheques-toggle-btn" id="btnToggleKpisCheques" aria-expanded="true">
                <i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span>
            </button>
        </div>

        <div class="card-body" id="chequesKpisContenido">
            <div class="cheques-kpi-grid">
                <div class="cheques-kpi cheques-kpi-registros">
                    <div class="cheques-kpi-copy">
                        <span>Registros</span>
                        <strong id="chequesKpiRegistros">0</strong>
                        <small>Cheques filtrados</small>
                    </div>
                    <span class="cheques-kpi-icon"><i class="fas fa-money-check"></i></span>
                </div>

                <div class="cheques-kpi cheques-kpi-activos">
                    <div class="cheques-kpi-copy">
                        <span>Activos</span>
                        <strong id="chequesKpiActivos">0</strong>
                        <small>Cheques vigentes</small>
                    </div>
                    <span class="cheques-kpi-icon"><i class="fas fa-check-circle"></i></span>
                </div>

                <div class="cheques-kpi cheques-kpi-anulados">
                    <div class="cheques-kpi-copy">
                        <span>Anulados</span>
                        <strong id="chequesKpiAnulados">0</strong>
                        <small>Cheques reintegrados</small>
                    </div>
                    <span class="cheques-kpi-icon"><i class="fas fa-ban"></i></span>
                </div>

                <div class="cheques-kpi cheques-kpi-total">
                    <div class="cheques-kpi-copy">
                        <span>Total Activo</span>
                        <strong id="chequesKpiTotal">L. 0.00</strong>
                        <small>Monto comprometido</small>
                    </div>
                    <span class="cheques-kpi-icon"><i class="fas fa-coins"></i></span>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTADO -->
    <section class="card cheques-section-card mb-4">
        <div class="cheques-section-header">
            <div class="cheques-section-title">
                <span class="cheques-section-icon"><i class="fas fa-money-check-alt"></i></span>
                <div>
                    <h5 class="mb-0">Cheques</h5>
                    <small>Emisión, consulta, comprobantes y anulación con reintegro contable.</small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="cheques-list-toolbar">
                <div class="cheques-toolbar-left">
                    <button type="button" class="btn btn-secondary table_actualizar ocultar" id="btnChequesActualizar">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnNuevoCheque">
                        <i class="fas fa-plus mr-1"></i> Nuevo Cheque
                    </button>
                    <button type="button" class="btn btn-primary table_crear ocultar" id="btnNuevaCategoriaCheque">
                        <i class="fas fa-layer-group mr-1"></i> Categoría
                    </button>
                    <button type="button" class="btn btn-info table_editar ocultar" id="btnConfigCuentaCheque">
                        <i class="fas fa-cog mr-1"></i> Cuenta de Cheques
                    </button>
                    <button type="button" class="btn btn-success table_reportes ocultar" id="btnChequesExcel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger table_reportes ocultar" id="btnChequesPdf">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                </div>

                <div class="cheques-toolbar-right">
                    <label class="cheques-page-size mb-0">
                        <span>Mostrar</span>
                        <select id="chequesPageSize" class="form-control form-control-sm"></select>
                        <span>registros</span>
                    </label>

                    <div class="cheques-view-switch">
                        <button type="button" class="cheques-view-btn active" data-view="detalle">
                            <i class="fas fa-list"></i><span>Detalle</span>
                        </button>
                        <button type="button" class="cheques-view-btn" data-view="miniatura">
                            <i class="fas fa-th-large"></i><span>Miniatura</span>
                        </button>
                    </div>

                    <div class="cheques-search-wrap">
                        <span class="cheques-search-icon"><i class="fas fa-search"></i></span>
                        <input type="search" id="buscarChequesListado" class="form-control" placeholder="Buscar cheque..." autocomplete="off">
                        <button type="button" id="limpiarBuscarChequesListado" class="cheques-search-clear" aria-label="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="chequesListado" class="cheques-listado vista-detalle"></div>

            <div class="cheques-totales">
                <div>
                    <span>Total Activo</span>
                    <strong id="chequesTotalActivo">L. 0.00</strong>
                </div>
                <div>
                    <span>Total Anulado</span>
                    <strong id="chequesTotalAnulado">L. 0.00</strong>
                </div>
                <div class="cheques-total-destacado">
                    <span>Total Emitido</span>
                    <strong id="chequesTotalEmitido">L. 0.00</strong>
                </div>
            </div>

            <div class="cheques-list-footer">
                <span id="chequesInfo">0 registros</span>
                <div id="chequesPaginacion" class="cheques-pagination"></div>
            </div>
        </div>

        <div class="card-footer small text-muted">
            <?php
                require_once "./core/mainModel.php";
                $insMainModel = new mainModel();
                $entidad = "cheque";

                if ($insMainModel->getlastUpdate($entidad)->num_rows > 0) {
                    $consulta_last_update = $insMainModel->getlastUpdate($entidad)->fetch_assoc();
                    $fecha_registro = htmlspecialchars($consulta_last_update['fecha_registro'], ENT_QUOTES, 'UTF-8');
                    $hora = htmlspecialchars(date('g:i:s a', strtotime($fecha_registro)), ENT_QUOTES, 'UTF-8');
                    echo "Última Actualización " . htmlspecialchars($insMainModel->getTheDay($fecha_registro, $hora), ENT_QUOTES, 'UTF-8');
                } else {
                    echo "No se encontraron registros";
                }
            ?>
        </div>
    </section>
</div>

<!-- MODAL NUEVO CHEQUE -->
<div class="modal fade" id="modalCheque" data-backdrop="static" data-keyboard="true" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content cheques-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-check-alt mr-2"></i>Emitir Cheque</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
            </div>

            <form id="formCheque">
                <div class="modal-body">
                    <div class="cheques-modal-alert">
                        <i class="fas fa-info-circle"></i>
                        <span>Al emitir el cheque se crea automáticamente un egreso y un movimiento de cuenta. La cuenta se toma de <strong>Tipo de Pago → Cheque</strong>.</span>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label>Fecha</label>
                            <input type="date" class="form-control" id="cheque_fecha" name="fecha" required>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-3">
                            <label>Número de Cheque</label>
                            <input type="text" class="form-control" id="cheque_numero" name="numero_cheque" maxlength="30" placeholder="Ej. 000125" required>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-3">
                            <label>Cuenta de Origen</label>
                            <select class="form-control selectpicker" id="cheque_cuenta" data-live-search="true" data-width="100%" disabled></select>
                            <small class="form-text text-muted" id="cheque_saldo_cuenta">Saldo disponible: L. 0.00</small>
                        </div>

                        <div class="col-lg-6 col-md-6 mb-3">
                            <label>Proveedor / Beneficiario</label>
                            <select class="form-control selectpicker" id="cheque_proveedor" name="proveedores_id" data-live-search="true" data-width="100%" title="Seleccione proveedor" required></select>
                        </div>

                        <div class="col-lg-6 col-md-6 mb-3">
                            <label>Categoría de Gasto</label>
                            <div class="cheques-select-action">
                                <select class="form-control selectpicker" id="cheque_categoria" name="categoria_gastos_id" data-live-search="true" data-width="100%" title="Seleccione categoría" required></select>
                                <button type="button" class="btn btn-primary" id="btnCategoriaDesdeCheque" title="Crear categoría">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 mb-3">
                            <label>Factura / Referencia</label>
                            <input type="text" class="form-control" id="cheque_factura" name="factura" maxlength="50" placeholder="Opcional">
                        </div>

                        <div class="col-lg-6 col-md-6 mb-3">
                            <label>Importe</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">L.</span></div>
                                <input type="number" class="form-control" id="cheque_importe" name="importe" min="0.01" step="0.01" required>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label>Observación / Concepto</label>
                            <textarea class="form-control" id="cheque_observacion" name="observacion" rows="3" maxlength="255" placeholder="Detalle del pago realizado con cheque"></textarea>
                        </div>
                    </div>

                    <div class="cheques-account-summary">
                        <div><span>Cuenta configurada</span><strong id="resumenCuentaCheque">No configurada</strong></div>
                        <div><span>Saldo actual</span><strong id="resumenSaldoCheque">L. 0.00</strong></div>
                        <div><span>Después del cheque</span><strong id="resumenSaldoDespues">L. 0.00</strong></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnGuardarCheque">
                        <i class="fas fa-save mr-1"></i> Emitir Cheque
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CONFIGURACIÓN CUENTA -->
<div class="modal fade" id="modalConfigCuentaCheque" data-backdrop="static" data-keyboard="true" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered cheques-config-dialog">
        <div class="modal-content cheques-modal cheques-config-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog mr-2"></i>Cuenta para Cheques</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
            </div>

            <form id="formConfigCuentaCheque">
                <div class="modal-body">
                    <div class="cheques-config-hero">
                        <div class="cheques-config-hero-icon"><i class="fas fa-money-check-alt"></i></div>
                        <div>
                            <h6>Configuración de la cuenta de origen</h6>
                            <p>Esta cuenta se utilizará automáticamente para debitar los nuevos cheques. El cambio no modifica cheques ni movimientos históricos.</p>
                        </div>
                    </div>

                    <div class="cheques-config-grid">
                        <section class="cheques-config-card cheques-config-current">
                            <div class="cheques-config-card-head">
                                <span class="cheques-config-card-icon"><i class="fas fa-wallet"></i></span>
                                <div>
                                    <span>Cuenta actual</span>
                                    <small>Configuración activa en Tipo de Pago → Cheque</small>
                                </div>
                            </div>

                            <div class="cheques-config-account">
                                <strong id="configCuentaActualNombre">No configurada</strong>
                                <span id="configCuentaActualCodigo">Sin código</span>
                            </div>

                            <div class="cheques-config-balance">
                                <span>Saldo actual</span>
                                <strong id="configCuentaActualSaldo">L. 0.00</strong>
                            </div>
                        </section>

                        <section class="cheques-config-card cheques-config-new">
                            <div class="cheques-config-card-head">
                                <span class="cheques-config-card-icon"><i class="fas fa-exchange-alt"></i></span>
                                <div>
                                    <span>Nueva cuenta</span>
                                    <small>Seleccione la cuenta que utilizarán los próximos cheques</small>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="config_cuenta_cheque"><i class="fas fa-university mr-1"></i>Cuenta contable</label>
                                <select class="form-control selectpicker"
                                        id="config_cuenta_cheque"
                                        name="cuentas_id"
                                        data-live-search="true"
                                        data-size="6"
                                        data-width="100%"
                                        title="Seleccione una cuenta"
                                        required></select>
                            </div>

                            <div class="cheques-config-selected">
                                <div>
                                    <span>Cuenta seleccionada</span>
                                    <strong id="configCuentaNuevaNombre">Seleccione una cuenta</strong>
                                </div>
                                <div>
                                    <span>Saldo disponible</span>
                                    <strong id="configCuentaNuevaSaldo">L. 0.00</strong>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="cheques-modal-alert cheques-modal-alert-warning mb-0">
                        <i class="fas fa-shield-alt"></i>
                        <span>Por seguridad, guardar este cambio requiere la validación administrativa realizada antes de abrir esta ventana.</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarConfigCuentaCheque"><i class="fas fa-save mr-1"></i> Guardar Configuración</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Cheques Contabilidad");
?>
