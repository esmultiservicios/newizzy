<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$productos_id = $_POST['productos_id'];
	$barcode = $_POST['barcode'];

	//CONSULTAMOS EL RTN DEL CLIENTE
	$result = $insMainModel->getBarCode($productos_id, $barcode);

	if($result->num_rows==0){
		//EDITAMOS EL RTN DEL CLIENTE
		$query = $insMainModel->actualizarBarCode($productos_id, $barcode);

		if($query){
			echo 1;//RTN EDITADO CORRECTAMENTE
		}else{
			echo 2;//RTN NO SE PUEDE EDITAR
		}
	}else{
		echo 3;//EL RTN YA EXISTE NO SE PUEDE EDITAR
	}