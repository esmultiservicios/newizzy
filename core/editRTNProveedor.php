<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$proveedores_id = $_POST['proveedores_id'];
	$rtn = $_POST['rtn'];

	//CONSULTAMOS EL RTN DEL CLIENTE
	$result = $insMainModel->getRTNProveedor($proveedores_id, $rtn);

	if($result->num_rows==0){
		//EDITAMOS EL RTN DEL CLIENTE
		$query = $insMainModel->actualizarRTNProveedor($proveedores_id, $rtn);
		
		if($query){
			echo 1;//RTN EDITADO CORRECTAMENTE
		}else{
			echo 2;//RTN NO SE PUEDE EDITAR
		}
	}else{
		echo 3;//EL RTN YA EXISTE NO SE PUEDE EDITAR
	}
?>	