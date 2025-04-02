<!--INICIO MODAL PARA EL INGRESO DE BANCOS-->
<div class="modal fade" id="ModalDetalleVentas">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Detalle de Ventas</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
        </div><div class="container"></div>
        <div class="modal-body">		
			<form class="form-horizontal FormularioAjax" id="FormDetalleVentas" action="" method="POST" data-form="" enctype="multipart/form-data">				
				<div class="form-row">
					<div class="col-md-3 mb-3">
						<label for="DetallesFechai">Fecha Inicio</label>
						<div class="input-group mb-3">
							<input type="date" id="DetallesFechai" name="DetallesFechai"
								value="<?php 
									$fecha = date ("Y-m-d");
									
									$año = date("Y", strtotime($fecha));
									$mes = date("m", strtotime($fecha));
									$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));

									$dia1 = date('d', mktime(0,0,0, $mes, 1, $año)); //PRIMER DIA DEL MES
									$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año)); // ULTIMO DIA DEL MES

									$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
									$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-".$dia2));						
									
									
									echo $fecha_inicial;
								?>" 
							class="form-control" />
						</div>
					</div>
					<div class="col-md-3 mb-3">
						<label for="DetallesFechaf">Fecha Fin</label>
						<div class="input-group mb-3">
							<input type="date" id="DetallesFechaf" name="DetallesFechaf"
								value="<?php echo date ("Y-m-d");?>" class="form-control" />
						</div>
					</div>					
					<div class="col-md-3 mb-3">
						<label for="DetallesProductos">Productos</label>
						<div class="input-group mb-3">
							<select class="selectpicker" id="DetallesProductos" name="DetallesProductos"
								data-width="100%" data-width="100%" data-size="7" data-live-search="true"
								title="Productos">
							</select>
						</div>
					</div>
					<div class="col-md-3 mb-3">
						<label for="DetalleVendedores">Vendedores </label>
						<div class="input-group mb-3">
							<select class="selectpicker" id="DetalleVendedores" name="DetalleVendedores"
								data-width="100%" data-size="7" data-live-search="true" title="Vendedores">
							</select>
						</div>
					</div>
				</div>

				<div class="col-md-12">
					<div class="overflow-auto">
						<table id="DatatableDetalleVentas"
							class="table table-striped table-condensed table-hover" style="width:100%">
							<thead>
								<tr>
									<th>Producto</th>
									<th>Factura</th>
									<th>Precio</th>
									<th>Cantidad</th>
									<th>ISV</th>
									<th>Descuento</th>
									<th>Total</th>
									<th>Vendedor</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th colspan="2">Totales:</th>
									<th id="total-precio"></th>
									<th id="total-cantidad"></th>
									<th id="total-isv"></th>
									<th id="total-descuento"></th>
									<th id="total-total"></th>
									<th></th>
								</tr>
							</tfoot>							
						</table>
					</div>
				</div>
												
				<div class="RespuestaAjax"></div> 
			</form>
        </div>	
		<div class="modal-footer">
				
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE BANCOS-->
