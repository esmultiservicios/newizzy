<?php
// Ubicación: vistas/contenido/modals/notaCredito-modals.php
?>
<div class="modal fade izzy-nc-modal" id="modalNotaCredito" tabindex="-1" role="dialog" aria-labelledby="modalNotaCreditoTitulo" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header izzy-nc-header">
                <div class="izzy-nc-title-wrap">
                    <span class="izzy-nc-title-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div>
                        <h4 class="modal-title" id="modalNotaCreditoTitulo">Nota de Crédito</h4>
                        <small>Documento complementario asociado a una factura emitida.</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="nc_facturas_id">

                <section class="izzy-nc-summary">
                    <div class="izzy-nc-summary-main">
                        <span class="izzy-nc-kicker">Factura original</span>
                        <strong id="nc_factura_numero">—</strong>
                        <small id="nc_factura_fecha">—</small>
                    </div>
                    <div class="izzy-nc-summary-item">
                        <span>Cliente</span>
                        <strong id="nc_cliente">—</strong>
                        <small id="nc_rtn">—</small>
                    </div>
                    <div class="izzy-nc-summary-item">
                        <span>Total factura</span>
                        <strong id="nc_total_factura">L 0.00</strong>
                    </div>
                    <div class="izzy-nc-summary-item">
                        <span>Ya acreditado</span>
                        <strong id="nc_total_previo">L 0.00</strong>
                    </div>
                    <div class="izzy-nc-summary-item izzy-nc-summary-available">
                        <span>Disponible</span>
                        <strong id="nc_total_disponible">L 0.00</strong>
                    </div>
                </section>

                <section class="izzy-nc-toolbar">
                    <div>
                        <h6><i class="fas fa-box-open"></i> Conceptos a acreditar</h6>
                        <small>Ingrese el monto base a acreditar. IZZY calcula el ISV proporcional automáticamente.</small>
                    </div>
                    <div class="izzy-nc-toolbar-actions">
                        <button type="button" class="btn btn-primary btn-sm" id="btnNcCreditoTotal">
                            <i class="fas fa-check-double"></i> Crédito total
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnNcLimpiar">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                    </div>
                </section>

                <div id="nc_detalle_loading" class="izzy-nc-state">
                    <i class="fas fa-spinner fa-spin"></i> Cargando factura...
                </div>

                <div id="nc_detalle_empty" class="izzy-nc-state d-none">
                    <i class="fas fa-info-circle"></i> Esta factura ya no tiene saldo disponible para acreditar.
                </div>

                <div id="nc_detalle_listado" class="izzy-nc-lines"></div>

                <section class="izzy-nc-bottom-grid">
                    <div class="izzy-nc-reason-card">
                        <label for="nc_motivo"><i class="fas fa-comment-alt"></i> Motivo de la Nota de Crédito <span class="text-danger">*</span></label>
                        <textarea id="nc_motivo" class="form-control" maxlength="500" rows="4" placeholder="Explique claramente el motivo del ajuste fiscal."></textarea>
                        <small><span id="nc_motivo_count">0</span>/500 caracteres</small>
                    </div>

                    <div class="izzy-nc-totals-card">
                        <div><span>Base acreditada</span><strong id="nc_base_total">L 0.00</strong></div>
                        <div><span>ISV 15%</span><strong id="nc_isv15_total">L 0.00</strong></div>
                        <div><span>ISV 18%</span><strong id="nc_isv18_total">L 0.00</strong></div>
                        <div class="izzy-nc-grand"><span>Total Nota de Crédito</span><strong id="nc_gran_total">L 0.00</strong></div>
                    </div>
                </section>

                <section class="izzy-nc-history-card">
                    <div class="izzy-nc-history-head">
                        <div>
                            <h6><i class="fas fa-history"></i> Notas de Crédito emitidas</h6>
                            <small>Historial relacionado con esta factura.</small>
                        </div>
                    </div>
                    <div id="nc_historial" class="izzy-nc-history-list"></div>
                </section>

                <div class="izzy-nc-warning">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>La factura original no se modifica.</strong>
                        <span>Una Nota de Crédito emitida queda registrada como documento independiente y consume su propia secuencia fiscal.</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer izzy-nc-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnEmitirNotaCredito">
                    <i class="fas fa-file-signature"></i> Emitir Nota de Crédito
                </button>
            </div>
        </div>
    </div>
</div>
