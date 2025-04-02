<!--INICIO MODAL PARA EL INGRESO DE UBICACION-->
<div class="modal fade" id="modal_ubicacion">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Ubicación</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formUbicacion" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="ubicacion_id" name="ubicacion_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_ubicacion" name="pro_ubicacion"
                                    class="form-control" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre_proveedores">Ubicación <span class="priority">*<span /></label>
                            <input type="text" required class="form-control" name="ubicacion_ubicacion"
                                id="ubicacion_ubicacion" placeholder="Ubicación" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="empresa_ubicacion">Empresa <span class="priority">*<span /></label>
                            <div class="input-group">
                                <div class="input-group-append">
                                    <select id="empresa_ubicacion" name="empresa_ubicacion" class="selectpicker"
                                        data-width="100%" title="Empresa" data-live-search="true">
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_ubicacion">
						<span class="mr-2">Estado:</span>					
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="ubicacion_activo" name="ubicacion_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_ubicacion_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_ubicacion"
                    form="formUbicacion">
                    <div class="guardar sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_ubicacion"
                    form="formUbicacion">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_ubicacion"
                    form="formUbicacion">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE UBICACION-->