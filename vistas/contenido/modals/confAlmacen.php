<!--INICIO MODAL PARA EL INGRESO DE ALMACENES-->
<div class="modal fade" id="modal_almacen">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Nuevo Almacén</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formAlmacen" action="" method="POST" data-form=""
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <input type="hidden" required="required" readonly id="almacen_id" name="almacen_id" />
                            <div class="input-group mb-3">
                                <input type="text" required readonly id="pro_almacen" name="pro_almacen"
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
                        <div class="col-md-4 mb-3">
                            <label>Empresa <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="almacen_empresa_id" name="almacen_empresa_id" required class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Empresa">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="almacen_almacen">Almacén <span class="priority">*<span /></label>
                            <input type="text" required class="form-control" name="almacen_almacen" id="almacen_almacen"
                                placeholder="Almacén" maxlength="30"
                                oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="ubicacion_almacen">Ubicación <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="ubicacion_almacen" required name="ubicacion_almacen" class="selectpicker"
                                    data-width="100%" data-live-search="true" title="Ubicacion">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <input type="hidden" name="facturar_cero" id="cero" value="1">
                        <div class="col-md-6">
                            <p for="">Facturar inventario en cero?</p>

                            <label class="switch">
                                <input type="checkbox" id="facturar_cero" name="" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_facturar_cero">si</span>
                        </div>
                        <input type="hidden" id="almacen_activo" name="almacen_activo" value="1" checked>
                        <div class="form-group" id="estado_almacen">
                            <div class="col-md-12">
                                <p for="">Estado</p>
                                <label class="switch">
                                    <input type="checkbox" id="val_almacen_activo" name="val_almacen_activo" value="1"
                                        checked>
                                    <div class="slider round"></div>
                                </label>
                                <span class="question mb-2" id="label_almacen_activo"></span>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_almacen"
                    form="formAlmacen">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_almacen"
                    form="formAlmacen">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_almacen"
                    form="formAlmacen">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE ALMACENES-->