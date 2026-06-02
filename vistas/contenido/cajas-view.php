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

    <div class="card mb-4">
        <div class="card-body">
            <form id="formMainCajas">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Estado</label>
                            <select id="estado_cajas" name="estado_cajas" class="form-control selectpicker" title="Estado" data-live-search="true">
                                <option value="0">Todas</option>
                                <option value="1">Activas</option>
                                <option value="2">Cerrada</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Inicial</label>
                            <input type="date" class="form-control" id="fecha_cajas" name="fecha_cajas" value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="form-group">
                            <label class="small mb-1">Fecha Final</label>
                            <input type="date" class="form-control" id="fecha_cajas_f" name="fecha_cajas_f" value="<?php echo date('Y-m-d');?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-right">
                        <button type="button" class="btn btn-info mr-2" id="btnGananciaPeriodo">
                            <i class="fas fa-chart-pie fa-lg"></i> Ganancia del Período
                        </button>

                        <button type="button" class="btn btn-warning mr-2" id="btnRetirosPeriodo">
                            <i class="fas fa-money-bill-wave fa-lg"></i> Retiros del Período
                        </button>

                        <button type="submit" class="btn btn-primary mr-2" id="search">
                            <i class="fas fa-filter fa-lg"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cash-register fa-lg mr-1"></i>
            Cajas
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableCajas" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                </table>
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

<div class="modal fade" id="modalDesgloseGananciaCaja" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header izzy-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    <span id="titulo_modal_ganancia">Resumen de caja y ganancia</span>
                    <small id="dg_contexto_consulta" class="d-block mt-1 text-light" style="opacity:.85;"></small>
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="dg_apertura_id" value="0">
                <input type="hidden" id="dg_modo" value="caja">

                <div class="izzy-note mb-3">
                    <strong>Resumen claro:</strong>
                    este reporte separa el dinero cobrado, el dinero físico en caja y el dinero que se debe guardar para reponer inventario.
                </div>

                <div class="mb-4">
                    <div class="izzy-section-title">
                        <i class="fas fa-check-circle"></i>
                        1. Resultado principal del día
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card izzy-kpi-card-highlight">
                                <div class="izzy-kpi-label">Total cobrado</div>
                                <p class="izzy-kpi-value izzy-kpi-success" id="dg_total_cobrado">L. 0.00</p>
                                <small>Todo el dinero cobrado: efectivo, transferencia, tarjeta y cheque.</small>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Debe guardar para reponer inventario</div>
                                <p class="izzy-kpi-value izzy-kpi-danger" id="dg_costo_productos">L. 0.00</p>
                                <small>Este es el costo de los productos vendidos.</small>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card izzy-kpi-card-highlight">
                                <div class="izzy-kpi-label">Dinero después de reponer</div>
                                <p class="izzy-kpi-value izzy-kpi-primary" id="dg_dinero_despues_reponer">L. 0.00</p>
                                <small>Total cobrado menos costo de productos vendidos.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="izzy-section-title">
                        <i class="fas fa-wallet"></i>
                        2. Cómo se cobró el dinero
                    </div>

                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Efectivo</div>
                                <p class="izzy-kpi-value" id="dg_efectivo">L. 0.00</p>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Transferencia</div>
                                <p class="izzy-kpi-value" id="dg_transferencia">L. 0.00</p>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Tarjeta</div>
                                <p class="izzy-kpi-value" id="dg_tarjeta">L. 0.00</p>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Cheque</div>
                                <p class="izzy-kpi-value" id="dg_cheque">L. 0.00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="izzy-section-title">
                        <i class="fas fa-cash-register"></i>
                        3. Dinero físico esperado en caja
                    </div>

                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Monto apertura</div>
                                <p class="izzy-kpi-value" id="dg_monto_apertura">L. 0.00</p>
                                <small>Dinero con el que inició la caja.</small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Efectivo cobrado</div>
                                <p class="izzy-kpi-value" id="dg_efectivo_caja">L. 0.00</p>
                                <small>Solo lo cobrado en efectivo.</small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Retiros de caja</div>
                                <p class="izzy-kpi-value izzy-kpi-danger" id="dg_retiro_caja">L. 0.00</p>
                                <small>Dinero retirado antes del cierre.</small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="izzy-kpi-card izzy-kpi-card-highlight">
                                <div class="izzy-kpi-label">Efectivo esperado en caja</div>
                                <p class="izzy-kpi-value izzy-kpi-primary" id="dg_efectivo_esperado_caja">L. 0.00</p>
                                <small>Apertura + efectivo cobrado - retiros.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="izzy-section-title">
                        <i class="fas fa-boxes"></i>
                        4. Ganancia de productos
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Venta base de productos</div>
                                <p class="izzy-kpi-value" id="dg_total_vendido_detalle">L. 0.00</p>
                                <small>Suma de cantidad por precio guardado en el detalle.</small>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Costo productos vendidos</div>
                                <p class="izzy-kpi-value izzy-kpi-danger" id="dg_costo_productos_2">L. 0.00</p>
                                <small>Costo real de lo vendido.</small>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="izzy-kpi-card">
                                <div class="izzy-kpi-label">Ganancia base de productos</div>
                                <p class="izzy-kpi-value izzy-kpi-success" id="dg_ganancia_bruta">L. 0.00</p>
                                <small>Venta base de productos menos costo.</small>
                            </div>
                        </div>
                    </div>

                    <div class="izzy-note izzy-note-warning mb-3">
                        <strong>Impuestos / ajustes incluidos en factura:</strong>
                        <span id="dg_diferencia_conciliacion">L. 0.00</span>.
                        Este valor explica la diferencia entre el total facturado y la venta base de productos.
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dataTableDetalleGananciaCaja" class="table table-striped table-hover table-condensed" style="width:100%">
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="refrescarDesgloseGananciaCaja();">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>

                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalleRetirosCaja" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header izzy-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave mr-1"></i>
                    Detalle de retiros de caja
                    <small id="dr_contexto_caja" class="d-block mt-1 text-light" style="opacity:.85;"></small>
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="dr_apertura_id" value="0">

                <div class="row mb-3">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="izzy-kpi-card">
                            <div class="izzy-kpi-label">Total de retiros activos</div>
                            <p class="izzy-kpi-value izzy-kpi-danger" id="dr_total_retiros">L. 0.00</p>
                            <small>Solo suma retiros activos.</small>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="izzy-kpi-card">
                            <div class="izzy-kpi-label">Estado de caja</div>
                            <p class="izzy-kpi-value" id="dr_estado_caja">-</p>
                            <small>Solo cajas abiertas permiten reintegro.</small>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="izzy-kpi-card">
                            <div class="izzy-kpi-label">Acción permitida</div>
                            <p class="izzy-kpi-value" id="dr_accion_permitida">-</p>
                            <small>Depende del estado de la caja.</small>
                        </div>
                    </div>
                </div>

                <div class="izzy-note izzy-note-warning mb-3">
                    <strong>Importante:</strong>
                    el reintegro solo se puede realizar mientras la caja esté abierta. Puede reintegrar todo el retiro para anularlo, o reintegrar una parte para ajustar el monto retirado.
                </div>

                <div class="table-responsive">
                    <table id="dataTableDetalleRetirosCaja" class="table table-striped table-hover table-condensed" style="width:100%">
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cargarDetalleRetirosCaja($('#dr_apertura_id').val());">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>

                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalReintegroRetiroCaja" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header izzy-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo-alt mr-1"></i>
                    Reintegrar retiro de caja
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formReintegroRetiroCaja" autocomplete="off">
                <div class="modal-body">

                    <input type="hidden" id="reintegro_caja_retiros_id" name="caja_retiros_id" value="0">
                    <input type="hidden" id="reintegro_apertura_id" name="apertura_id" value="0">
                    <input type="hidden" id="reintegro_monto_actual" name="monto_actual" value="0">

                    <div class="alert alert-info">
                        <strong>Retiro actual:</strong>
                        <span id="reintegro_monto_actual_text">L. 0.00</span>
                    </div>

                    <div class="form-group">
                        <label>¿Cuánto dinero desea devolver?</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="reintegro_monto" name="monto_reintegro" placeholder="0.00">
                        <small class="form-text text-muted">
                            Si devuelve el monto completo, el retiro se anula. Si devuelve una parte, el retiro queda ajustado con el saldo restante.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Observación</label>
                        <textarea class="form-control" id="reintegro_observacion" name="observacion" rows="3" maxlength="255" placeholder="Ejemplo: Se devuelve dinero por retiro incorrecto"></textarea>
                    </div>

                    <div class="alert alert-warning mb-0">
                        Este reintegro reversa contablemente el dinero devuelto, registrando un movimiento como ingreso en la misma cuenta del retiro.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="btnGuardarReintegroRetiroCaja">
                        <i class="fas fa-save"></i> Guardar reintegro
                    </button>

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php
    $insMainModel->guardar_historial_accesos("Ingreso al modulo Cajas");
?>