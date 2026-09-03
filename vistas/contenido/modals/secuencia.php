<!-- INICIO MODAL SECUENCIA DE FACTURACION -->
<div class="modal fade" id="modal_registrar_secuencias" tabindex="-1" role="dialog" aria-labelledby="modalSecuenciaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content secuencia-modal-content">
            <div class="modal-header secuencia-modal-header">
                <h4 class="modal-title" id="modalSecuenciaTitulo">
                    <i class="fas fa-file-invoice mr-2"></i>Secuencia de Facturación
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form class="FormularioAjax" id="formSecuencia" method="POST" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="secuencia_facturacion_id" name="secuencia_facturacion_id">

                    <!-- Información General -->
                    <section class="secuencia-form-section mb-4">
                        <div class="secuencia-section-header">
                            <h5><i class="fas fa-info-circle mr-2"></i>Información General</h5>
                        </div>
                        <div class="secuencia-section-body">
                            <div class="form-row">
                                <div class="col-12 col-md-6 col-xl-3 mb-3">
                                    <label for="empresa_secuencia"><i class="fas fa-building mr-1"></i>Empresa <span class="priority">*</span></label>
                                    <select id="empresa_secuencia" name="empresa_secuencia" class="selectpicker" data-live-search="true" data-width="100%" data-container="#modal_registrar_secuencias" title="Seleccione una empresa" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Empresa asociada a esta secuencia</small>
                                </div>

                                <div class="col-12 col-md-6 col-xl-3 mb-3">
                                    <label for="documento_secuencia"><i class="fas fa-file-alt mr-1"></i>Documento <span class="priority">*</span></label>
                                    <select id="documento_secuencia" name="documento_secuencia" class="selectpicker" data-live-search="true" data-width="100%" data-container="#modal_registrar_secuencias" title="Seleccione un documento" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <div class="secuencia-field-help-row">
                                        <small class="form-text text-muted">Solo se muestran documentos activos</small>
                                        <button type="button" class="btn btn-link btn-sm p-0" id="btn_administrar_documentos_desde_modal">
                                            Administrar
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12 col-xl-6 mb-3">
                                    <label for="cai_secuencia"><i class="fas fa-id-card mr-1"></i>CAI</label>
                                    <input type="text" name="cai_secuencia" id="cai_secuencia" class="form-control" placeholder="CAI" maxlength="37">
                                    <small class="form-text text-muted">Código de Autorización de Impresión (máximo 37 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Configuración de Secuencia -->
                    <section class="secuencia-form-section mb-4">
                        <div class="secuencia-section-header">
                            <h5><i class="fas fa-sliders-h mr-2"></i>Configuración de Secuencia</h5>
                        </div>
                        <div class="secuencia-section-body">
                            <div class="form-row">
                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="prefijo_secuencia"><i class="fas fa-font mr-1"></i>Prefijo</label>
                                    <input type="text" name="prefijo_secuencia" id="prefijo_secuencia" class="form-control" placeholder="Prefijo" maxlength="15">
                                    <small class="form-text text-muted">Texto inicial del número de documento</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="relleno_secuencia"><i class="fas fa-text-width mr-1"></i>Relleno <span class="priority">*</span></label>
                                    <input type="number" min="1" max="20" name="relleno_secuencia" id="relleno_secuencia" class="form-control" placeholder="Relleno" required>
                                    <small class="form-text text-muted">Cantidad de dígitos para el número</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="incremento_secuencia"><i class="fas fa-plus mr-1"></i>Incremento <span class="priority">*</span></label>
                                    <input type="number" min="1" name="incremento_secuencia" id="incremento_secuencia" class="form-control" placeholder="Incremento" required>
                                    <small class="form-text text-muted">Valor de incremento por documento</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="siguiente_secuencia"><i class="fas fa-arrow-right mr-1"></i>Siguiente <span class="priority">*</span></label>
                                    <input type="number" min="0" name="siguiente_secuencia" id="siguiente_secuencia" class="form-control" placeholder="Siguiente" required>
                                    <small class="form-text text-muted">Próximo número a utilizar</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="rango_inicial_secuencia"><i class="fas fa-list-ol mr-1"></i>Rango Inicial <span class="priority">*</span></label>
                                    <input type="text" inputmode="numeric" name="rango_inicial_secuencia" id="rango_inicial_secuencia" class="form-control" placeholder="Rango Inicial" maxlength="11" required>
                                    <small class="form-text text-muted">Primer número autorizado</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="rango_final_secuencia"><i class="fas fa-list-ol mr-1"></i>Rango Final <span class="priority">*</span></label>
                                    <input type="text" inputmode="numeric" name="rango_final_secuencia" id="rango_final_secuencia" class="form-control" placeholder="Rango Final" maxlength="11" required>
                                    <small class="form-text text-muted">Último número autorizado</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="fecha_activacion_secuencia"><i class="fas fa-calendar-alt mr-1"></i>Fecha Activación <span class="priority">*</span></label>
                                    <input type="date" id="fecha_activacion_secuencia" name="fecha_activacion_secuencia" value="<?php echo date('Y-m-d');?>" class="form-control" required>
                                    <small class="form-text text-muted">Fecha de inicio de uso</small>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                    <label for="fecha_limite_secuencia"><i class="fas fa-calendar-times mr-1"></i>Fecha Límite <span class="priority">*</span></label>
                                    <input type="date" id="fecha_limite_secuencia" name="fecha_limite_secuencia" value="<?php echo date('Y-m-d');?>" class="form-control" required>
                                    <small class="form-text text-muted">Fecha máxima de uso</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Estado -->
                    <section class="secuencia-form-section mb-2" id="estado_secuencia_container">
                        <div class="secuencia-section-header">
                            <h5><i class="fas fa-power-off mr-2"></i>Estado de la Secuencia</h5>
                        </div>
                        <div class="secuencia-section-body">
                            <div class="secuencia-switch-row">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="estado_secuencia" name="estado_secuencia" checked>
                                    <label class="custom-control-label" for="estado_secuencia">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <span id="label_estado_secuencia">Activo</span>
                                    </label>
                                </div>
                                <small class="form-text text-muted">Active o desactive esta secuencia en el sistema</small>
                            </div>
                        </div>
                    </section>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>

            <div class="modal-footer secuencia-modal-footer">
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display:none;" id="reg_secuencia" form="formSecuencia">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display:none;" id="edi_secuencia" form="formSecuencia">
                    <i class="fas fa-edit mr-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- FIN MODAL SECUENCIA DE FACTURACION -->

<!-- INICIO MODAL CATALOGO DE DOCUMENTOS -->
<div class="modal fade" id="modal_documentos_secuencia" tabindex="-1" role="dialog" aria-labelledby="modalDocumentosSecuenciaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content secuencia-modal-content">
            <div class="modal-header secuencia-modal-header">
                <h4 class="modal-title" id="modalDocumentosSecuenciaTitulo">
                    <i class="fas fa-folder-open mr-2"></i>Documentos de Facturación
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="documentos-layout">
                    <section class="secuencia-form-section documentos-form-panel">
                        <div class="secuencia-section-header">
                            <h5><i class="fas fa-plus-circle mr-2"></i><span id="documento_form_titulo">Nuevo Documento</span></h5>
                        </div>
                        <div class="secuencia-section-body">
                            <form id="formDocumentoSecuencia" autocomplete="off">
                                <input type="hidden" id="documento_id_secuencia" name="documento_id" value="0">

                                <div class="form-group">
                                    <label for="documento_nombre_secuencia">
                                        <i class="fas fa-file-alt mr-1"></i>Nombre del documento <span class="priority">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="documento_nombre_secuencia" name="nombre" maxlength="30" placeholder="Ej. Nota de Crédito" required>
                                    <small class="form-text text-muted">El nombre debe ser único y tendrá un máximo de 30 caracteres.</small>
                                </div>

                                <div class="documentos-form-actions">
                                    <button type="button" class="btn btn-secondary d-none" id="btn_cancelar_edicion_documento">
                                        <i class="fas fa-undo mr-1"></i> Cancelar edición
                                    </button>
                                    <button type="submit" class="btn btn-success" id="btn_guardar_documento_secuencia">
                                        <i class="fas fa-save mr-1"></i> Guardar documento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="secuencia-form-section documentos-list-panel">
                        <div class="secuencia-section-header documentos-header-flex">
                            <div>
                                <h5><i class="fas fa-copy mr-2"></i>Documentos Disponibles</h5>
                                <small>Los documentos activos estarán disponibles al crear una secuencia.</small>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn_refrescar_documentos_secuencia">
                                <i class="fas fa-sync-alt mr-1"></i> Actualizar
                            </button>
                        </div>
                        <div class="secuencia-section-body">
                            <div id="documentos_secuencia_loading" class="secuencia-state-box d-none">
                                <i class="fas fa-spinner fa-spin"></i><span>Cargando documentos...</span>
                            </div>
                            <div id="documentos_secuencia_empty" class="secuencia-state-box d-none">
                                <i class="fas fa-folder-open"></i>
                                <div><strong>No hay documentos registrados</strong><small>Registra el primer documento para comenzar.</small></div>
                            </div>
                            <div id="documentos_secuencia_listado" class="documentos-secuencia-listado"></div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="modal-footer secuencia-modal-footer">
                <button class="btn btn-primary" type="button" data-dismiss="modal">
                    <i class="fas fa-check mr-1"></i> Finalizar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- FIN MODAL CATALOGO DE DOCUMENTOS -->
