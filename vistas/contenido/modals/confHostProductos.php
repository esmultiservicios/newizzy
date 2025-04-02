<!--INICIO MODAL PUESTO-->
<div class="modal fade" id="modal_registrar_host_productos">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Host</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formHostProductos" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="host_detalles_id" name="host_detalles_id" class="form-control">
                                <input type="text" id="proceso_hostProductos" class="form-control" readonly>
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
                            <label for="cliente">Cliente <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="cliente" name="cliente" class="custom-select" data-width="100%"
                                    data-toggle="tooltip" data-placement="top" title="Clientes">
                                    <option value="">Seleccione</option>
                                </select>
                                <div class="input-group-append" id="buscar_clientes_productos_host">
                                    <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db">Plan <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="plan" name="plan" placeholder="Plan">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="producto">Producto <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="productos" name="productos" class="custom-select" data-width="100%"
                                    data-toggle="tooltip" data-placement="top" title="Clientes">
                                    <option value="">Seleccione</option>
                                </select>
                                <div class="input-group-append" id="buscar_productos_host">
                                    <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pass">Cantidad <span class="priority">*<span /></label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad"
                                placeholder="Cantidad">
                        </div>
                    </div>
                    <div class="form-group" id="estado_hostProductos">
						<span class="mr-2">Estado:</span>					
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="hostProductos_activo" name="hostProductos_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_hostProductos_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_hostProductos"
                    form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_hostProductos"
                    form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_hostProductos" form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PUESTO-->