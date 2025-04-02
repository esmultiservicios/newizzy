<!--INICIO MODAL HOST-->
<div class="modal fade" id="modal_registrar_host">
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
                <form class="FormularioAjax" id="formHost" action="" method="POST" data-form="" autocomplete="off"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="host_id" name="host_id" class="form-control">
                                <input type="text" id="proceso_host" class="form-control" readonly>
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
                            <label for="server">Server <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="server" name="server" placeholder="Server"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db">DB <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="db" name="db" placeholder="DB" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="user">User <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="user" name="user" placeholder="User" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pass">Password <span class="priority">*<span /></label>
                            <input type="text" class="form-control" id="pass" name="pass" placeholder="Password"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente">Cliente <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="cliente" name="cliente" class="selectpicker" data-size="10"
                                    data-live-search="true" title="Clientes">
                                </select>
                                <div class="input-group-append" id="buscar_clientes_host">
                                    <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="planes">Plan <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="planes" name="planes" class="custom-select" data-toggle="tooltip"
                                    data-placement="top" title="Planes">
                                    <option value="">Seleccione</option>
                                </select>
                                <div class="input-group-append" id="buscar_planes_host">
                                    <a data-toggle="modal" href="#" class="btn btn-outline-success">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_host">
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="host_activo" name="host_activo" value="1" checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_host_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_host"
                    form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_host"
                    form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_host"
                    form="formHost">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL HOST-->