<div class="modal fade" id="modalAyudaQuote">
	<div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fas fa-question-circle"></i> Ayuda</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal" id="formAyudaQuote" action="" method="POST" data-form="" enctype="multipart/form-data">
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label><center><b>Las teclas de función solo se pueden utilizar posicionándose en el área de la factura</b></center></label>
					</div>	
					
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite realizar una búsqueda de productos, e inclusive permite crear nuevos productos en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F2</b> Búsqueda de Productos</label>
					</div>	
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite aplicar descuentos a los productos, con previa autorización de un supervisor o un administrador del sistema"><b>F3</b> Agregar Descuentos a los Productos</label>
					</div>
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Esta opción puede ser útil para ingresar un nuevo precio a un producto siempre y cuando el documento original de compra y/o cotización muestre un precio diferente al que se muestra en el sistema"><b>F4</b> Modificar Precio a los Productos</label>
					</div>		
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite Registrar la cotización"><b>F6</b> Registrar Cotización</label>
					</div>
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite realizar una búsqueda de clientes, e inclusive permite crear nuevos clientes en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F7</b> Clientes</label>
					</div>	
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite realizar una búsqueda de vendedores y/o colaboradores, e inclusive permite crear nuevos vendedores y/o colaboradores en el sistema, siempre hacer uso del botón actualizar para refrescar la lista cuando se realiza un nuevo registro"><b>F8</b> Colaboradores</label>
					</div>	
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite aumentar la cantidad, se debe posicionar en el código del producto y presionar la tecla más (+) para que surja efecto"><b>+</b> Aumentar Cantidad</label>
					</div>	
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Permite disminuir la cantidad, se debe posicionar en el código del producto y presionar la tecla menos (-) para que surja efecto"><b>-</b> Disminuir Cantidad</label>
					</div>	
					<div class="col-md-12 mb-3">
					  <label data-toggle="tooltip" data-placement="top" title="Este comodín permite agregar un valor en la cantidad, para hacerlo debemos escribir la cantidad que requerimos, seguido del comodín y luego el código del producto para que surja efecto, por ejemplo: 10*cod_produto esto agregará un 10 automáticamente en la cantidad"><b>*</b> Comodin Asterisco</label>
					</div>															
				</div>
			</form>
        </div>		
      </div>
    </div>
</div>
<!--FIN MODAL PARA AYUDA-->
<!--INICIO MODAL PARA MODIFICAR PRECIO COTIZACIONES-->
<div class="modal fade" id="modalModificarPrecioCotizaciones">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Modificar Precio Producto</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal" id="formModificarPrecioCotizaciones" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-12 mb-3">
					    <input type="hidden" required="required" readonly id="modificar_precio_productos_id" name="modificar_precio_productos_id" class="form-control"/>
					    <input type="hidden" required="required" readonly id="modificar_precio_clientes_id" name="modificar_precio_clientes_id" class="form-control"/>
					    <input type="hidden" required="required" readonly id="modificar_precio_fecha" name="modificar_precio_fecha" class="form-control"/>						
						<input type="hidden" required="required" readonly id="row_index" name="row_index" class="form-control"/>
						<input type="hidden" required="required" readonly id="col_index" name="col_index" class="form-control"/>
						<input type="hidden" required="required" readonly id="modificar_precio_isv_aplica" name="modificar_precio_isv_aplica" class="form-control"/>
						<input type="hidden" required="required" readonly id="modificar_precio_isv_valor" name="modificar_precio_isv_valor" class="form-control"/>						
						<div class="input-group mb-3">
							<input type="text" required readonly id="pro_modificar_precio" name="pro_modificar_precio" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>
				<div class="form-row">
					<div class="col-md-12 mb-3">
					  <label for="producto_modificar_precio_fact">Producto <span class="priority">*<span/></label>
					  <input type="text" readonly required id="producto_modificar_precio_fact" name="producto_modificar_precio_fact" placeholder="Producto" class="form-control" maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>				
				</div>
				<div class="form-row">
					<div class="col-md-6 mb-3">
					  <label for="referencia_modificar_precio_fact">Referencia <span class="priority">*<span/></label>
					  <input type="text" required id="referencia_modificar_precio_fact" name="referencia_modificar_precio_fact" placeholder="Referencia y/o Número de Documento" class="form-control"  maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>					
					<div class="col-md-6 mb-3">
					  <label for="precio_modificar_precio_fact">Precio <span class="priority">*<span/></label>
					  <input type="text" required id="precio_modificar_precio_fact" name="precio_modificar_precio_fact" placeholder="Precio" class="form-control" maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>			
				</div>					
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
			<button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_modificar_precio_quote" form="formModificarPrecioCotizaciones"><div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar</button>				
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA MODIFICAR PRECIO COTIZACIONES-->
<!--INICIO MODAL PARA FORMULARIO DESCENTOS EN COTIZACIONES-->
<div class="modal fade" id="modalDescuentoCotizaciones">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Descuento</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal" id="formDescuentoCotizaciones" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-12 mb-3">
					    <input type="hidden" required="required" readonly id="descuento_productos_id" name="descuento_productos_id"/>
					    <input type="hidden" required="required" readonly id="descuento_cantidad" name="descuento_cantidad"/>
						<input type="hidden" required="required" readonly id="row_index" name="row_index"/>
						<input type="hidden" required="required" readonly id="col_index" name="col_index"/>
						<div class="input-group mb-3">
							<input type="text" required readonly id="pro_descuento_fact" name="pro_descuento_fact" class="form-control"/>
							<div class="input-group-append">				
								<span class="input-group-text"><div class="sb-nav-link-icon"></div><i class="fa fa-plus-square fa-lg"></i></span>
							</div>
						</div>	 
					</div>							
				</div>
				<div class="form-row">
					<div class="col-md-8 mb-3">
					  <label for="producto_descuento_fact">Producto <span class="priority">*<span/></label>
					  <input type="text" readonly required id="producto_descuento_fact" name="producto_descuento_fact" placeholder="Producto" class="form-control" maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>
					<div class="col-md-4 mb-3">
					  <label for="precio_descuento_fact">Precio <span class="priority">*<span/></label>
					  <input type="text" readonly required id="precio_descuento_fact" name="precio_descuento_fact" placeholder="Precio" class="form-control"  maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" step="0.01"/>
					</div>					
				</div>
				<div class="form-row">
					<div class="col-md-4 mb-3">
					  <label for="porcentaje_descuento_fact">% Descuento <span class="priority">*<span/></label>
					  <input type="text" required id="porcentaje_descuento_fact" name="porcentaje_descuento_fact" placeholder="Porcentaje de Descuento" class="form-control" maxlength="11" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"/>
					</div>
					<div class="col-md-4 mb-3">
					  <label for="descuento_fact">Valor Descuento <span class="priority">*<span/></label>
					  <input type="text" required id="descuento_fact" name="descuento_fact" placeholder="Descuento" class="form-control"  maxlength="30" oninput="if(this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" step="0.01"/>
					</div>				
				</div>					
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
			<button class="guardar btn btn-primary ml-2" type="submit" style="display: none;" id="reg_DescuentoQuote" form="formDescuentoCotizaciones"><div class="sb-nav-link-icon"></div><i class="far fa-save fa-lg"></i> Registrar</button>				
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA FORMULARIO DESCENTOS EN COTIZACIONES-->