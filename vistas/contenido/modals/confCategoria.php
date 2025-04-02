<!--INICIO MODAL PARA EL INGRESO DE CATEGORIAS DE PRODUCTOS-->
<div class="modal fade" id="modalcategoria_productos">
	<div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Categoría de Productos</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal FormularioAjax" id="formCategoriaProductos" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-12 mb-3">
					    <input type="hidden" required="required" readonly id="categoria_id" name="categoria_id"/>
						<div class="input-group mb-3">
							<input type="text" required readonly id="pro_categoria_productos" name="pro_categoria_productos" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label>Categoría <span class="priority">*<span/></label>
					  <input type="text" required id="categoria_productos" name="categoria_productos" placeholder="Categoría" class="form-control"  maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>				
				</div>
				<div class="form-group" id="estado_categoria_productos">
				  <span class="mr-2">Estado:</span>			
				  <div class="col-md-12">			
						<label class="switch">
							<input type="checkbox" id="categoria_producto_activo" name="categoria_producto_activo" value="1" checked>
							<div class="slider round"></div>
						</label>
						<span class="question mb-2" id="label_categoria_producto_activo"></span>				
				  </div>				  
				</div>	
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
			<button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_catProd" form="formCategoriaProductos"><div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar</button>
			<button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_catProd" form="formCategoriaProductos"><div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar</button>
			<button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_catProd" form="formCategoriaProductos"><div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar</button>				
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE CATEGORIAS DE PRODUCTOS-->
