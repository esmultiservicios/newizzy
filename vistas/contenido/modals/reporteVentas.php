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

				<div class="row">
					<!-- Fila de filtros -->
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1" for="DetallesFechai">Fecha Inicio</label>
							<input type="date" id="DetallesFechai" name="DetallesFechai" value="<?php 
								$fecha = date ("Y-m-d");
								$año = date("Y", strtotime($fecha));
								$mes = date("m", strtotime($fecha));
								$dia = date("d", mktime(0,0,0, $mes+1, 0, $año));
								$dia1 = date('d', mktime(0,0,0, $mes, 1, $año));
								$dia2 = date('d', mktime(0,0,0, $mes, $dia, $año));
								$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-".$dia1));
								echo $fecha_inicial;
							?>" class="form-control">
						</div>
					</div>
					
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1" for="DetallesFechaf">Fecha Fin</label>
							<input type="date" id="DetallesFechaf" name="DetallesFechaf"
								value="<?php echo date ("Y-m-d");?>" class="form-control">
						</div>
					</div>
					
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1" for="DetallesProductos">Productos</label>
							<select class="form-control selectpicker" id="DetallesProductos" name="DetallesProductos"
								data-size="7" data-live-search="true" title="Productos">
							</select>
						</div>
					</div>
					
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="form-group">
							<label class="small mb-1" for="DetalleVendedores">Vendedores</label>
							<select class="form-control selectpicker" id="DetalleVendedores" name="DetalleVendedores"
								data-size="7" data-live-search="true" title="Vendedores">
							</select>
						</div>
					</div>
				</div>

				<!-- Fila de botones (si es necesario) -->
				<div class="row">
					<div class="col-md-12 d-flex justify-content-end mb-3">
						<button type="submit" class="btn btn-primary mr-2">
							<i class="fas fa-filter fa-lg mr-1"></i> Filtrar
						</button>
						<button type="reset" id="btn-limpiar-filtros" class="btn btn-secondary">
							<i class="fas fa-broom fa-lg mr-1"></i> Limpiar
						</button>
					</div>
				</div>

				<div class="col-md-12">
					<div class="overflow-auto">
						<table id="DatatableDetalleVentas"
							class="table table-header-gradient table-striped table-condensed table-hover" style="width:100%">
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
							<tfoot class="bg-secondary">
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
			<button class="btn btn-danger" data-dismiss="modal">
				<i class="fas fa-times fa-lg mr-1"></i> Cancelar
			</button>
		</div>			
      </div>
    </div>
</div>
<!--FIN MODAL PARA EL INGRESO DE BANCOS-->
