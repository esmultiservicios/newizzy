<!--INICIO MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->
<div class="modal fade" id="modalEgresosContables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Registro de Egresos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formEgresosContables" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required readonly id="egresos_id" name="egresos_id">              
                    
                    <!-- Sección de Datos del Egreso -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Datos del Egreso</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_egresos"><i class="fas fa-calendar-day mr-1"></i>Fecha Factura <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_egresos" name="fecha_egresos" data-remember="date" data-rem-key="egresos:lastFecha" value="<?php echo date ("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha de la factura del egreso</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="proveedor_egresos"><i class="fas fa-user-tie mr-1"></i>Entregado a <span class="priority">*</span></label>
                                    <select id="proveedor_egresos" name="proveedor_egresos" class="selectpicker form-control" data-live-search="true" title="Seleccione proveedor" required>
                                        <option value="">Seleccione</option>
                                        <!-- Las opciones se llenarán con JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">Seleccione el cliente o ingrese uno nuevo</small>
                                    <button type="button" class="btn btn-sm btn-primary mt-2" id="btnNuevoProveedor">
                                        <i class="fas fa-plus-circle mr-1"></i> Agregar Nuevo Proveedor
                                    </button>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="cuenta_egresos"><i class="fas fa-piggy-bank mr-1"></i>Cuenta <span class="priority">*</span></label>
                                    <select id="cuenta_egresos" name="cuenta_egresos" class="selectpicker form-control" data-live-search="true" title="Seleccione cuenta" required>
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Cuenta contable asociada</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Detalles de Factura -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Detalles de Factura</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="categoria_gastos"><i class="fas fa-tags mr-1"></i>Categoría</label>
                                    <select id="categoria_gastos" name="categoria_gastos" class="selectpicker form-control" data-live-search="true" title="Seleccione categoría">
                                        <option value="">Seleccione</option>
                                    </select>
                                    <small class="form-text text-muted">Categoría del gasto</small>
                                </div>

                                <div class="col-md-9 mb-3">
                                    <label for="factura_egresos"><i class="fas fa-file-invoice mr-1"></i>Factura <span class="priority">*</span></label>

                                    <div class="input-group">
                                        <input type="text" required id="factura_egresos" name="factura_egresos" placeholder="Número de factura" class="form-control" maxlength="19" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">

                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="btnGenerarFacturaEgresos" title="Generar documento automático">
                                                <i class="fas fa-magic mr-1"></i> Generar
                                            </button>
                                        </div>
                                    </div>

                                    <small class="form-text text-muted">Número de factura o documento. Formato automático: OUTYYYYMMDDHHMMSS</small>
                                </div>

                                <div class="col-md-12 col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label class="small mb-1"><strong>Factura PDF</strong></label>

                                        <div class="file-upload-container">
                                            <div class="file-upload-area" id="fileDropArea">
                                                <i class="fas fa-file-pdf fa-3x mb-2"></i>
                                                <p class="mb-2">
                                                    <span class="drag-text">Arrastra tu archivo PDF aquí,</span>
                                                    <button type="button" class="btn btn-primary btn-sm ml-1 btn-file-chooser" id="btnSelectPdf">
                                                        <i class="fas fa-file-pdf mr-1"></i> Seleccionar PDF
                                                    </button>
                                                    <span class="paste-text ml-1">o pega en cualquier área</span>
                                                </p>

                                                <!-- input file oculto, disparado por el botón -->
                                                <input type="file" id="factura_pdf" name="factura_pdf" accept=".pdf" class="file-upload-input d-none">
                                                <div class="file-preview" id="filePreview"></div>
                                            </div>

                                            <div class="file-info" id="fileInfo">Ningún archivo seleccionado</div>
                                        </div>

                                        <small class="form-text text-muted">Documento PDF de la factura (Máx. 5MB)</small>
                                    </div>
                                </div>                       
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Montos -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i>Montos</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="subtotal_egresos"><i class="fas fa-receipt mr-1"></i>Subtotal <span class="priority">*</span></label>
                                    <input type="number" required id="subtotal_egresos" name="subtotal_egresos" placeholder="0.00" class="form-control" step="0.01">
                                    <small class="form-text text-muted">Subtotal antes de impuestos</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="isv_egresos"><i class="fas fa-percent mr-1"></i>ISV</label>
                                    <input type="number" id="isv_egresos" name="isv_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Impuesto sobre ventas</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="descuento_egresos"><i class="fas fa-tag mr-1"></i>Descuento</label>
                                    <input type="number" id="descuento_egresos" name="descuento_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Descuentos aplicados</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="nc_egresos"><i class="fas fa-file-invoice-dollar mr-1"></i>Nota Crédito</label>
                                    <input type="number" id="nc_egresos" name="nc_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Notas de crédito aplicadas</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="total_egresos"><i class="fas fa-money-bill-wave mr-1"></i>Total</label>
                                    <input type="number" readonly id="total_egresos" name="total_egresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Total a pagar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Observaciones -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-clipboard mr-2"></i>Observaciones</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="observacion_egresos"><i class="fas fa-align-left mr-1"></i>Observación</label>
                                    <input type="text" id="observacion_egresos" name="observacion_egresos" placeholder="Observaciones" class="form-control" maxlength="150" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Observaciones adicionales (máx. 150 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>

            <div class="modal-footer">

                <!-- RESUMEN PREMIUM DE CUENTA SELECCIONADA -->
                <div class="modal-footer-context context-egreso is-empty" id="footerCuentaEgresosResumen">
                    <div class="modal-footer-context-left">
                        <div class="modal-footer-context-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>

                        <div class="modal-footer-context-info">
                            <span class="modal-footer-context-label">Cuenta seleccionada para este egreso</span>
                            <span class="modal-footer-context-value" id="footerCuentaEgresosTexto">
                                Seleccione una cuenta contable
                            </span>
                        </div>
                    </div>

                    <div class="modal-footer-context-right">
                        <span class="modal-footer-context-badge">
                            <i class="fas fa-info-circle"></i>
                            Egreso
                        </span>
                    </div>
                </div>

                <div class="modal-footer-actions">
                    <button class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                    </button>

                    <button class="btn btn-success" type="submit" style="display: none;" id="reg_egresosContabilidad" form="formEgresosContables">
                        <i class="far fa-save fa-lg mr-1"></i> Registrar
                    </button>

                    <button class="btn btn-success" type="submit" style="display: none;" id="edi_egresosContabilidad" form="formEgresosContables">
                        <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL FORMULARIO DE EGRESOS CONTABLES-->

<!--INICIO MODAL REGISTRO CATEGORIAS-->
<div class="modal fade modal-categorias-premium" id="modalCategoriasEgresos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-layer-group mr-2"></i>
                    Categorías de Gastos
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="categoria-hero">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="categoria-hero-title">
                                <i class="fas fa-tags mr-1"></i>
                                Administración de categorías
                            </h5>
                            <p class="categoria-hero-text">
                                Registre, edite, active, inactive o elimine categorías. Solo una categoría puede quedar marcada como inversión/reposición.
                            </p>
                        </div>

                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge-cat-inversion">
                                <i class="fas fa-seedling"></i>
                                Inversión única
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card categoria-premium-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-plus-circle mr-1"></i>
                        Nueva Categoría
                    </div>

                    <div class="card-body">
                        <form class="form-horizontal" id="formCategoriaEgresos" action="<?php echo SERVERURL;?>ajax/addCategoriaEgresos.php" method="POST" data-form="" enctype="multipart/form-data">
                            <input type="hidden" readonly id="categoria_gastos_id" name="categoria_gastos_id">

                            <div class="row">
                                <div class="col-lg-6 col-md-12 mb-3">
                                    <label for="categoria">
                                        <i class="fas fa-tag mr-1"></i>
                                        Categoría <span class="priority">*</span>
                                    </label>
                                    <input type="text" required id="categoria" name="categoria" placeholder="Ej: Combustible" class="form-control" maxlength="30">
                                    <small class="form-text text-muted">
                                        Máximo 30 caracteres.
                                    </small>
                                </div>

                                <div class="col-lg-6 col-md-12 mb-3">
                                    <label for="es_inversion">
                                        <i class="fas fa-seedling mr-1"></i>
                                        Clasificación especial
                                    </label>

                                    <div class="categoria-invest-box d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="categoria-invest-title">Marcar como inversión/reposición</div>
                                            <p class="categoria-invest-help">
                                                Si activa esta opción, cualquier otra categoría marcada como inversión se desactivará automáticamente.
                                            </p>
                                        </div>

                                        <label class="categoria-switch ml-3 mb-0">
                                            <input type="checkbox" id="es_inversion" name="es_inversion" value="1">
                                            <span class="categoria-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="RespuestaAjax"></div>
                        </form>
                    </div>

                    <div class="card-footer bg-white text-right">
                        <button class="btn btn-secondary" type="button" id="btnLimpiarCategoriaEgresos">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>

                        <button class="guardar btn btn-success ml-2" type="submit" id="regCategoriaEgresos" form="formCategoriaEgresos">
                            <i class="far fa-save fa-lg mr-1"></i> Registrar
                        </button>
                    </div>
                </div>

                <div class="card categoria-premium-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>
                            <i class="fas fa-list mr-1"></i>
                            Categorías registradas
                        </span>

                        <span class="text-muted small">
                            Use acciones para editar, eliminar, activar/inactivar o cambiar inversión.
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="categoria-table-wrap">
                            <div class="table-responsive">
                                <table id="DatatableCategoriaEgresos" class="table table-header-gradient table-striped table-condensed table-hover mb-0" style="width:100%"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-categorias-premium" id="modalUpdateCategoriasEgresos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Categoría
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form class="form-horizontal" id="formUpdateCategoriaEgresos" action="<?php echo SERVERURL;?>ajax/modificarCategoriaEgresos.php" method="POST" data-form="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" required readonly id="categoria_gastos_id" name="categoria_gastos_id">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="categoria">
                                <i class="fas fa-tag mr-1"></i>
                                Categoría <span class="priority">*</span>
                            </label>
                            <input type="text" required id="categoria" name="categoria" placeholder="Categoría" class="form-control" maxlength="30">
                            <small class="form-text text-muted">
                                Máximo 30 caracteres.
                            </small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="es_inversion">
                                <i class="fas fa-seedling mr-1"></i>
                                Clasificación especial
                            </label>

                            <div class="categoria-invest-box d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="categoria-invest-title">Marcar como inversión/reposición</div>
                                    <p class="categoria-invest-help">
                                        Si activa esta opción, esta será la única categoría marcada como inversión.
                                    </p>
                                </div>

                                <label class="categoria-switch ml-3 mb-0">
                                    <input type="checkbox" id="es_inversion" name="es_inversion" value="1">
                                    <span class="categoria-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </div>

                <div class="modal-footer bg-light">
                    <button class="btn btn-danger" data-dismiss="modal" type="button">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>

                    <button class="editar btn btn-success ml-2" type="submit" id="ediCategoriaEgresos">
                        <i class="fas fa-edit fa-lg mr-1"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--FIN MODAL REGISTRO CATEGORIAS-->

<!--INICIO MODAL REPORTE CATEGORIAS-->
<div class="modal fade" id="modalReporteCategorias">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Reporte Categorías</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formularioReporteCategorias">
                    <div class="form-row">
                        <div class="col-md-5 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Inicio
                                    </span>
                                </div>
                                <input type="date" required id="fechai" name="fechai" value="<?php 
								$fecha = date ("Y-m-d");
								
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
								
								
								echo $fecha_inicial;
							?>" class="form-control ml-1" data-toggle="tooltip" data-placement="top" title="Fecha Inicio">
                            </div>
                        </div>
                        <div class="col-md-5 mb-3">
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div>Fecha Fin
                                    </span>
                                </div>
                                <input type="date" required id="fechaf" name="fechaf"
                                    value="<?php echo date ("Y-m-d");?>" class="form-control ml-1" data-toggle="tooltip"
                                    data-placement="top" title="Fecha Fin">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="consultar btn btn-secondary ml-1" type="submit" id="search">
                                <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="overflow-auto">
                                <table id="DatatableReporteCategorias"
                                    class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Categoría</th>
                                            <th>Monto</th>
                                        </tr>
                                    </thead>
                                    <tfoot class="bg-info text-white font-weight-bold">
                                        <tr>
                                            <td>Total</td>
                                            <td id="monto-i"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<!--FIN MODAL REPORTE CATEGORIAS-->