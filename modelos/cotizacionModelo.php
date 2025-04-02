<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }

	class cotizacionModelo extends mainModel{		
		protected function agregar_cotizacion_modelo($datos){
			$insert = "INSERT INTO cotizacion VALUES('".$datos['cotizacion_id']."','".$datos['clientes_id']."','".$datos['numero']."','".$datos['tipo_factura']."','".$datos['colaboradores_id']."','".$datos['importe']."','".$datos['notas']."','".$datos['fecha']."','".$datos['estado']."','".$datos['vigencia_quote']."','".$datos['usuario']."','".$datos['empresa']."','".$datos['fecha_registro']."','".$datos['fecha_dolar']."')";

			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);		

			return $result;			
		}		

		protected function agregar_detalle_cotizacion($datos){
			$cotizacion_detalle_id = mainModel::correlativo("cotizacion_detalle_id", "cotizacion_detalles");
			$insert = "INSERT INTO cotizacion_detalles VALUES('$cotizacion_detalle_id','".$datos['cotizacion_id']."','".$datos['productos_id']."','".$datos['cantidad']."','".$datos['precio']."','".$datos['isv_valor']."','".$datos['descuento']."')";

			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);			

			return $result;			
		}
		
		protected function actualizar_detalle_cotizacion($datos){
			$update = "UPDATE cotizacion_detalles
				SET 
					cantidad = '".$datos['cantidad']."',
					precio = '".$datos['precio']."',
					isv_valor = '".$datos['isv_valor']."',
					descuento = '".$datos['descuento']."'
				WHERE cotizacion_id = '".$datos['cotizacion_id']."' AND productos_id = '".$datos['productos_id']."'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);			

			return $result;					
		}		

		protected function actualizar_cotizacion_importe($datos){
			$update = "UPDATE cotizacion
				SET
					importe = '".$datos['importe']."'
				WHERE cotizacion_id = '".$datos['cotizacion_id']."'";
				
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
		
			return $result;				
		}
		
		protected function cancelar_cotizacion_modelo($cotizacion_id){

			$estado = 4;//COTIZACIÓN CANCELADA

			$update = "UPDATE cotizacion

				SET

					estado = '$estado'

				WHERE cotizacion_id = '$cotizacion_id'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		

			return $result;			

		}

		

		protected function validDetalleCotizacion($cotizacion_id, $productos_id){

			$query = "SELECT cotizacion_detalle_id

				FROM cotizacion_detalles

				WHERE cotizacion_id = '$cotizacion_id' AND productos_id  = '$productos_id'";

			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		
			return $result;			
		}


		protected function getISV_modelo(){
			$result = mainModel::getISV('Facturas');

			
			return $result;

		}

		
		protected function getISVEstadoProducto_modelo($productos_id){

			$result = mainModel::getISVEstadoProducto($productos_id);

			

			return $result;			

		}		

	}