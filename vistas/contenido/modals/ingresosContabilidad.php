<!--INICIO MODAL PARA EL FORMULARIO DE INGRESOS CONTABLES-->
<div class="modal fade" id="modalIngresosContables">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Registro de Ingresos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formIngresosContables" action="" method="POST" data-form="" enctype="multipart/form-data">
                    <input type="hidden" required readonly id="ingresos_id" name="ingresos_id">
                    
                    <!-- Sección de Datos del Ingreso -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Datos del Ingreso</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                    <label for="fecha_ingresos"><i class="fas fa-calendar-day mr-1"></i>Fecha Factura <span class="priority">*</span></label>
                                    <input type="date" required id="fecha_ingresos" name="fecha_ingresos" value="<?php echo date ("Y-m-d");?>" class="form-control">
                                    <small class="form-text text-muted">Fecha del documento del ingreso</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="recibide_ingresos"><i class="fas fa-user-tie mr-1"></i>Recibí de <span class="priority">*</span></label>
                                    <select id="recibide_ingresos" name="recibide_ingresos" class="selectpicker form-control" data-live-search="true" title="Seleccione cliente" required>
                                        <option value="">Seleccione</option>
                                        <!-- Las opciones se llenarán con JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">Seleccione el cliente o ingrese uno nuevo</small>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnNuevoCliente">
                                        <i class="fas fa-plus-circle mr-1"></i> Agregar Nuevo Cliente
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cuenta_ingresos"><i class="fas fa-piggy-bank mr-1"></i>Cuenta <span class="priority">*</span></label>
                                    <select id="cuenta_ingresos" name="cuenta_ingresos" class="selectpicker form-control" data-live-search="true" title="Seleccione cuenta" required>
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
                            <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Detalles de Documento</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-6 mb-3">
                                    <label for="factura_ingresos"><i class="fas fa-file-invoice mr-1"></i>Factura</label>
                                    <input type="text" id="factura_ingresos" name="factura_ingresos" placeholder="Método de pago" class="form-control" maxlength="19" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Numero de Factura o de documento</small>
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
                                    <label for="subtotal_ingresos"><i class="fas fa-receipt mr-1"></i>Subtotal <span class="priority">*</span></label>
                                    <input type="number" required id="subtotal_ingresos" name="subtotal_ingresos" placeholder="0.00" class="form-control" step="0.01">
                                    <small class="form-text text-muted">Subtotal antes de impuestos</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="isv_ingresos"><i class="fas fa-percent mr-1"></i>ISV</label>
                                    <input type="number" id="isv_ingresos" name="isv_ingresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Impuesto sobre ventas</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="descuento_ingresos"><i class="fas fa-tag mr-1"></i>Descuento</label>
                                    <input type="number" id="descuento_ingresos" name="descuento_ingresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Descuentos aplicados</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nc_ingresos"><i class="fas fa-money-bill-wave mr-1"></i>Nota Ingreso</label>
                                    <input type="number" id="nc_ingresos" name="nc_ingresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Nota de Credito</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-3 mb-3">
                                <label for="total_ingresos"><i class="fas fa-money-bill-wave mr-1"></i>Total</label>
                                    <input type="number" readonly id="total_ingresos" name="total_ingresos" placeholder="0.00" class="form-control" step="0.01" value="0">
                                    <small class="form-text text-muted">Total recibido</small>
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
                                    <label for="observacion_ingresos"><i class="fas fa-align-left mr-1"></i>Observación</label>
                                    <input type="text" id="observacion_ingresos" name="observacion_ingresos" placeholder="Observaciones" class="form-control" maxlength="150" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Observaciones adicionales (máx. 150 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" data-dismiss="modal">
                    <i class="fas fa-times fa-lg mr-1"></i> Cancelar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="reg_ingresosContabilidad" form="formIngresosContables">
                    <i class="far fa-save fa-lg mr-1"></i> Registrar
                </button>
                <button class="btn btn-success" type="submit" style="display: none;" id="edi_ingresosContabilidad" form="formIngresosContables">
                    <i class="fas fa-edit fa-lg mr-1"></i> confirmar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL FORMULARIO DE INGRESOS CONTABLES-->