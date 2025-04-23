<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$clientes_id = $_POST['clientes_id'];
	$rtn = $_POST['rtn'];

	//CONSULTAMOS EL RTN DEL CLIENTE
	$result = $insMainModel->getRTNCliente($clientes_id, $rtn);

	if($result->num_rows==0){
		//EDITAMOS EL RTN DEL CLIENTE
		$query = $insMainModel->actualizarRTNCliente($clientes_id, $rtn);

		if($query){
			echo 1;//RTN EDITADO CORRECTAMENTE
		}else{
			echo 2;//RTN NO SE PUEDE EDITAR
		}
	}else{
		echo 3;//EL RTN YA EXISTE NO SE PUEDE EDITAR
	}