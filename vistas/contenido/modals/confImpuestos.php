<!--INICIO MODAL PARA EL INGRESO DE IMPUESTOS-->
<div class="modal fade" id="modalImpuestos">
	<div class="modal-dialog modal modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Impuestos</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal FormularioAjax" id="formImpuestos" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-12 mb-3">
					    <input type="hidden" required="required" readonly id="isv_id" name="isv_id"/>
						<div class="input-group mb-3">
							<input type="text" required readonly id="pro_impuestos" name="pro_impuestos" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label>Tipo ISV <span class="priority">*<span/></label>
					  <div class="input-group mb-3">
							<input type="text" readonly required id="tipo_isv" name="tipo_isv" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fas fa-percentage"></i></span>
							</div>
						</div>
					</div>				
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label>Valor <span class="priority">*<span/></label>
					  <div class="input-group mb-3">
							<input type="text" required id="valor" name="valor" class="form-control" placeholder="Valor" class="form-control"  maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fas fa-percentage"></i></span>
							</div>
						</div>
					</div>				
				</div>
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
			<button class="editar btn btn-warning ml-2" type="submit" style="display: none;" id="edi_impuestos" form="formImpuestos"><div class="sb-nav-link-icon"></div><i class="fas fa-edit fa-lg"></i> Editar</button>				
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE IMPUESTOS-->
