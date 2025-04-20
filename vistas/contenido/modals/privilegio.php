<!--INICIO MODAL PRIVILEGIOS-->
<div class="modal fade" id="modal_registrar_privilegios">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-user-shield mr-2"></i>Privilegios</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formPrivilegios" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="privilegio_id_" name="privilegio_id_" class="form-control">
                    
                    <!-- Sección de Datos del Privilegio -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Datos del Privilegio</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="privilegios_nombre"><i class="fas fa-id-card-alt mr-1"></i>Nombre <span class="priority">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="privilegios_nombre" id="privilegios_nombre" class="form-control" placeholder="Nombre" maxlength="20" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Nombre del privilegio (máx. 20 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_privilegios">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_privilegio_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="privilegio_activo" name="privilegio_activo" checked>
                                    <label class="custom-control-label" for="privilegio_activo"><i class="fas fa-check-circle mr-1"></i>Privilegio Activo</label>
                                </div>
                                <small class="form-text text-muted">Active o desactive el estado del privilegio</small>
                            </div>
                        </div>
                    </div>

                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_privilegios" form="formPrivilegios">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_privilegios" form="formPrivilegios">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_privilegios" form="formPrivilegios">
                    <i class="fas fa-trash mr-1"></i> Eliminar
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
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Privilegios - Menús</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formMenuAccesos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos" class="form-control">
                    
                    <!-- Sección de Menús -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bars mr-2"></i>Menús Disponibles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTableMenuAccesos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag mr-1"></i>#</th>
                                            <th><i class="fas fa-list mr-1"></i>Menú</th>
                                            <th><i class="fas fa-power-off mr-1"></i>Estado</th>
                                            <th><i class="fas fa-cogs mr-1"></i>Acciones</th>
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
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN AGREGAR MENUS-->

<!--INICIO AGREGAR MENUS-->
<div class="modal fade" id="modal_registrar_menuaccesos">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Privilegios - Menús</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formMenuAccesos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos" class="form-control">
                    
                    <!-- Sección de Menús -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bars mr-2"></i>Menús Disponibles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTableMenuAccesos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag mr-1"></i>#</th>
                                            <th><i class="fas fa-list mr-1"></i>Menú</th>
                                            <th><i class="fas fa-power-off mr-1"></i>Estado</th>
                                            <th><i class="fas fa-cogs mr-1"></i>Acciones</th>
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
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
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
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Privilegios - Submenús</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSubMenuAccesos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos" class="form-control">
                    <input type="hidden" id="menu_id_accesos" name="menu_id_accesos" class="form-control">
                    
                    <!-- Sección de Submenús -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list-ul mr-2"></i>Submenús Disponibles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTableSubMenuAccesos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag mr-1"></i>#</th>
                                            <th><i class="fas fa-list mr-1"></i>Menú</th>
                                            <th><i class="fas fa-list-ul mr-1"></i>Submenú</th>
                                            <th><i class="fas fa-power-off mr-1"></i>Estado</th>
                                            <th><i class="fas fa-cogs mr-1"></i>Acciones</th>
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
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
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
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-list mr-2"></i>Privilegios - Submenús Nivel 2</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="FormularioAjax" id="formSubMenu1Accesos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" id="privilegio_id_accesos" name="privilegio_id_accesos" class="form-control">
                    
                    <!-- Sección de Submenús Nivel 2 -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list-ol mr-2"></i>Submenús Nivel 2 Disponibles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTableSubMenu1Accesos" class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag mr-1"></i>#</th>
                                            <th><i class="fas fa-list-ul mr-1"></i>Submenú</th>
                                            <th><i class="fas fa-list-ol mr-1"></i>Submenú Nivel 2</th>
                                            <th><i class="fas fa-power-off mr-1"></i>Estado</th>
                                            <th><i class="fas fa-cogs mr-1"></i>Acciones</th>
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
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN AGREGAR SUBMENUS1-->