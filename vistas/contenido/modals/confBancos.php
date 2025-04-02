<!--INICIO MODAL PARA EL INGRESO DE BANCOS-->
<div class="modal fade" id="modalConfBancos">
	<div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Bancos</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal FormularioAjax" id="formBancos" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-12 mb-3">
					    <input type="hidden" required="required" readonly id="banco_id" name="banco_id"/>
						<div class="input-group mb-3">
							<input type="text" required readonly id="pro_bancos" name="pro_bancos" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label>Banco <span class="priority">*<span/></label>
					  <div class="input-group mb-3">
							<input type="text" required id="confbanco" name="confbanco" class="form-control" placeholder="Banco" class="form-control"  maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fas fa-university"></i></span>
							</div>
						</div>
					</div>				
				</div>
				<div class="form-group" id="estado_bancos">	
				 <span class="mr-2">Estado:</span>				
				  <div class="col-md-12">			
						<label class="switch">
							<input type="checkbox" id="confbanco_activo" name="confbanco_activo" value="1" checked>
							<div class="slider round"></div>
						</label>
						<span class="question mb-2" id="label_confbanco_activo"></span>				
				  </div>				  
				</div>					
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
			<button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_banco" form="formBancos"><div class="guardar sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar</button>
			<button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_banco" form="formBancos"><div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar</button>
			<button class="eliminar btn btn-danger ml-2" type="submit" style="display: none;" id="delete_banco" form="formBancos"><div class="sb-nav-link-icon"></div><i class="fa fa-trash fa-lg"></i> Eliminar</button>					
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE BANCOS-->
