<!--INICIO MODAL BUSQUEDA DE PROVEEDORES EN COMPRAS-->
<div class="modal fade" id="modal_buscar_proveedores_compras">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Buscar Proveedores</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">
			<form class="FormularioAjax" id="formulario_busqueda_proveedores_compras">				
				<div class="form-group">				  
					<div class="col-md-12">			
						<div class="overflow-auto">											
							<table id="DatatableProveedoresBusquedaProveedores" class="table table-striped table-condensed table-hover" style="width:100%">
								<thead>
									<tr>
										<th>Seleccione</th>
										<th>Proveedor</th>
										<th>RTN</th>
										<th>Teléfono</th>
										<th>correo</th>
									</tr>
								</thead>
							</table>
						</div>				
					</div>				  
				</div>
			</form>
        </div>
		<div class="modal-footer">
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL BUSQUEDA DE PROVEEDORES EN COMPRAS-->