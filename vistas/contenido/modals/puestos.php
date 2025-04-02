<!--INICIO MODAL PUESTO-->
<div class="modal fade" id="modal_registrar_puestos">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Puestos</h4>    
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">
			<form class="FormularioAjax" id="formPuestos" action="" method="POST" data-form="" autocomplete="off" enctype="multipart/form-data">
				<div class="form-row">
					<div class="col-md-12 mb-3">
						<div class="input-group mb-3">
							<input type="hidden" id="puestos_id" name="puestos_id" class="form-control">						
							<input type="text" id="proceso_puestos" class="form-control" readonly>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>					
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label for="puesto">Puesto <span class="priority">*<span/></label>
					  <input type="text" class="form-control" id="puesto" name="puesto" placeholder="Puesto" required>		  
					</div>		
				</div>
				<div class="form-group" id="estado_puestos">
				  <span class="mr-2">Estado:</span>			
				  <div class="col-md-12">			
						<label class="switch">
							<input type="checkbox" id="puestos_activo" name="puestos_activo" value="1" checked>
							<div class="slider round"></div>
						</label>
						<span class="question mb-2" id="label_puestos_activo"></span>				
				  </div>				  
				</div>				
				<div class="RespuestaAjax"></div>  
			</form>
        </div>
		<div class="modal-footer">
			<button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_puestos" form="formPuestos"><div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar</button>
			<button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_puestos" form="formPuestos"><div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar</button>
			<button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_puestos" form="formPuestos"><div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar</button>					
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PUESTO-->
