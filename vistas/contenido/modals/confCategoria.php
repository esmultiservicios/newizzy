<!--INICIO MODAL PARA EL INGRESO DE CATEGORIAS DE PRODUCTOS-->
<div class="modal fade" id="modalcategoria_productos">
    <div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><i class="fas fa-tags mr-2"></i>Categorías de Productos</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal FormularioAjax" id="formCategoriaProductos" action="" method="POST" data-form="" enctype="multipart/form-data">
					<input type="hidden" required readonly id="categoria_id" name="categoria_id">				
                    
                    <!-- Sección de Datos de la Categoría -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-tag mr-2"></i>Datos de la Categoría</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-12 mb-3">
                                    <label for="categoria_productos"><i class="fas fa-signature mr-1"></i>Categoría <span class="priority">*</span></label>
                                    <input type="text" required id="categoria_productos" name="categoria_productos" placeholder="Categoría" class="form-control" maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                                    <small class="form-text text-muted">Nombre de la categoría (máx. 30 caracteres)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Estado -->
                    <div class="card border-primary" id="estado_categoria_productos">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i><span class="question mb-2" id="label_categoria_producto_activo"></span></h5>
                        </div>
                        <div class="card-body">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="categoria_producto_activo" name="categoria_producto_activo" value="1" checked>
                                <label class="custom-control-label" for="categoria_producto_activo"><i class="fas fa-check-circle mr-1"></i><span class="question mb-2" id="label_categoria_producto_activo"></span></label>
                            </div>
                            <small class="form-text text-muted">Activar/Desactivar esta categoría en el sistema</small>
                        </div>
                    </div>
                    
                    <div class="RespuestaAjax"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button class="btn btn-primary" type="submit" style="display: none;" id="reg_catProd" form="formCategoriaProductos">
                    <i class="far fa-save mr-1"></i> Registrar
                </button>
                <button class="btn btn-warning" type="submit" style="display: none;" id="edi_catProd" form="formCategoriaProductos">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <button class="btn btn-danger" type="submit" style="display: none;" id="delete_catProd" form="formCategoriaProductos">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CATEGORIAS DE PRODUCTOS-->