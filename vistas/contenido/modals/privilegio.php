<!--INICIO MODAL PRIVILEGIOS-->
<div class="modal fade" id="modal_registrar_privilegios">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Privilegios</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formPrivilegios" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="privilegio_id_" name="privilegio_id_" class="form-control">
                                <input type="text" id="proceso_privilegios" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="prefijo">Nombre <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <input type="text" name="privilegios_nombre" id="privilegios_nombre"
                                    class="form-control" placeholder="Nombre" maxlength="20"
                                    oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fas fa-id-card-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="estado_privilegios">
						<span class="mr-2">Estado:</span>
                        <div class="col-md-12">
                            <label class="switch">
                                <input type="checkbox" id="privilegio_activo" name="privilegio_activo" value="1"
                                    checked>
                                <div class="slider round"></div>
                            </label>
                            <span class="question mb-2" id="label_privilegio_activo"></span>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_privilegios"
                    form="formPrivilegios">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
                <button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_privilegios"
                    form="formPrivilegios">
                    <div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar
                </button>
                <button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;"
                    id="delete_privilegios" form="formPrivilegios">
                    <div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PRIVILEGIOS-->

<!--INICIO AGREGAR MENUS-->
<div class="modal fade" id="modal_registrar_menuaccesos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Privilegios - Menus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formMenuAccesos" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos"
                                    class="form-control">
                                <input type="text" id="proceso_privilegios" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="server">Privilegio </label>
                            <input type="text" class="form-control" id="privilegio" name="privilegio" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label>Menu <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="menus" name="menus[] class=" selectpicker" data-width="100%" multiple
                                    data-live-search="true" title="Menu" required>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="table-responsive">
                                <table id="dataTableMenuAccesos" class="table table-striped table-condensed table-hover"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Privilegio</th>
                                            <th>Menu</th>
                                            <th>Eliminar</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_menuAccesos"
                    form="formMenuAccesos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN AGREGAR MENUS-->

<!--INICIO AGREGAR SUBMENUS-->
<div class="modal fade" id="modal_registrar_submenuaccesos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Privilegios - Submenus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSubMenuAccesos" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos"
                                    class="form-control">
                                <input type="hidden" id="menu_id_accesos" name="menu_id_accesos" class="form-control">
                                <input type="text" id="proceso_privilegios" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="server">Privilegio </label>
                            <input type="text" class="form-control" id="privilegio" name="privilegio" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label>Menu <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="menus" name="menus" class="selectpicker" data-width="100%"
                                    data-live-search="true" title="Menu" required>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>SubMenu <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="submenus" name="submenus[]" class="selectpicker" data-width="100%" multiple
                                    data-live-search="true" title="SubMenu" required>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="table-responsive">
                                <table id="dataTableSubMenuAccesos"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Privilegio</th>
                                            <th>Menu</th>
                                            <th>SubMenu</th>
                                            <th>Eliminar</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_SubmenuAccesos" form="formSubMenuAccesos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN AGREGAR SUBMENUS-->

<!--INICIO AGREGAR SUBMENUS1-->
<div class="modal fade" id="modal_registrar_submenu1accesos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Privilegios - Submenus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="container"></div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSubMenu1Accesos" action="" method="POST" data-form=""
                    autocomplete="off" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="input-group mb-3">
                                <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos"
                                    class="form-control">
                                <input type="text" id="proceso_privilegios" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <label for="server">Privilegio </label>
                            <input type="text" class="form-control" id="privilegio" name="privilegio" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 mb-3">
                            <label>Menu <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="menus" name="menus" class="selectpicker" data-width="100%" multiple
                                    data-live-search="true" title="Menu" required>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>SubMenu <span class="priority">*<span /></label>
                            <div class="input-group mb-3">
                                <select id="submenus" name="submenus[]" class="selectpicker" multiple data-width="100%"
                                    data-live-search="true" title="SubMenu" required>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12 mb-3">
                            <div class="table-responsive">
                                <table id="dataTableSubMenu1Accesos"
                                    class="table table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Privilegio</th>
                                            <th>Menu</th>
                                            <th>SubMenu</th>
                                            <th>Eliminar</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="guardar btn btn-primary ml-2" type="submit" style="display: none;"
                    id="reg_Submenu1Accesos" form="formSubMenu1Accesos">
                    <div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN AGREGAR SUBMENUS1-->