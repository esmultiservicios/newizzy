<!--INICIO MODAL SECUENCIA DE FACTURACION-->
<div class="modal fade" id="modal_registrar_secuencias">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Secuencia de Facturación</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSecuencia" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="secuencia_facturacion_id" name="secuencia_facturacion_id"
                                    class="form-control">
                                <input type="text" id="proceso_secuencia_facturacion" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="empresa">Empresa <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="empresa_secuencia" name="empresa_secuencia" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Empresa">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="empresa">Documento <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="documento_secuencia" name="documento_secuencia" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Documento" required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prefijo">CAI</label>
                            <div class="input-group mb-3">
                                <input type="text" name="cai_secuencia" id="cai_secuencia" class="form-control"
                                    placeholder="CAI" maxlength="37"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="far fa-id-card"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="prefijo">Prefijo</label>
                            <div class="input-group mb-3">
                                <input type="text" name="prefijo_secuencia" id="prefijo_secuencia" class="form-control"
                                    placeholder="Prefijo">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fab fa-autoprefixer fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="relleno">Relleno <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="number" name="relleno_secuencia" id="relleno_secuencia"
                                    class="form-control" placeholder="Relleno" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-fill fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="incremento">Incremento <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="number" name="incremento_secuencia" id="incremento_secuencia"
                                    class="form-control" placeholder="Incremento" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-arrow-right fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="siguiente">Siguiente <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="number" name="siguiente_secuencia" id="siguiente_secuencia"
                                    class="form-control" title="Número Siguiente" placeholder="Siguiente" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-caret-right fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3 mb-3">
                            <label for="rango_inicial">Rango Inicial <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" name="rango_inicial_secuencia" id="rango_inicial_secuencia"
                                    class="form-control" placeholder="Rango Inicial" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-list-ol fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="rango_final">Rango Final <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" name="rango_final_secuencia" id="rango_final_secuencia"
                                    class="form-control" placeholder="Rango Final" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-list-ol fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="fecha_limite">Fecha Activación <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="date" required id="fecha_activacion_secuencia"
                                    name="fecha_activacion_secuencia" value="<?php echo date ("Y-m-d");?>"
                                    class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="fecha_limite">Fecha Límite <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="date" required id="fecha_limite_secuencia" name="fecha_limite_secuencia"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="estado_secuencia_container" style="display:none;">
                        <span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="estado_secuencia" name="estado_secuencia" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_estado_secuencia"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_secuencia"
                    form="formSecuencia">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_secuencia"
                    form="formSecuencia">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_secuencia"
                    form="formSecuencia">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL SECUENCIA DE FACTURACION-->