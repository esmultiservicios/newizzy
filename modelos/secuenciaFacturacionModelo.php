<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class secuenciaFacturacionModelo extends mainModel{
		protected function agregar_secuencia_facturacion_modelo($datos){
			$secuencia_facturacion_id  = mainModel::correlativo("secuencia_facturacion_id ", "secuencia_facturacion");
			$insert = "INSERT INTO secuencia_facturacion VALUES('$secuencia_facturacion_id','".$datos['empresa_id']."', '".$datos['cai']."','".$datos['prefijo']."','".$datos['relleno']."','".$datos['incremento']."','".$datos['siguiente']."','".$datos['rango_inicial']."','".$datos['rango_final']."','".$datos['fecha_activacion']."','".$datos['fecha_limite']."','".$datos['activo']."','".$datos['usuario']."','".$datos['fecha_registro']."','".$datos['documento_id']."')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_secuencia_facturacion($empresa_id, $documento_id){
			$query = "SELECT secuencia_facturacion_id FROM secuencia_facturacion WHERE activo = 1 AND empresa_id = '$empresa_id' AND documento_id = '$documento_id'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function edit_secuencia_facturacion_modelo($datos){
			$update = "UPDATE secuencia_facturacion
			SET 
				cai = '".$datos['cai']."',
				prefijo = '".$datos['prefijo']."',
				relleno = '".$datos['relleno']."',
				incremento = '".$datos['incremento']."',
				siguiente = '".$datos['siguiente']."',
				rango_inicial = '".$datos['rango_inicial']."',
				rango_final = '".$datos['rango_final']."',
				fecha_activacion = '".$datos['fecha_activacion']."',
				fecha_limite = '".$datos['fecha_limite']."',
				activo = '".$datos['activo']."'
			WHERE secuencia_facturacion_id = '".$datos['secuencia_facturacion_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;				
		}
		
		protected function delete_secuencia_facturacion_modelo($secuencia_facturacion_id){
			$delete = "DELETE FROM secuencia_facturacion WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;				
		}
		
		protected function valid_secuencia_facturacion_facturas($secuencia_facturacion_id){
			$query = "SELECT facturas_id FROM facturas WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}
	}
?>