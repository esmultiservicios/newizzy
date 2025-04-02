<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}

    $estado = false;
	
	$insMainModel = new mainModel();
	$almacen_id = $_POST['almacen_id'];
	
	
	$result = $insMainModel->getAlmacenId($almacen_id);

	if($result->num_rows>0){
		$res = $result->fetch_assoc();
        if($res['facturar_cero']){
            $estado = true;		

        }
	}		
	
	echo json_encode($estado);
?>	