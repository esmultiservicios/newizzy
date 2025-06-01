<?php	
    if(!isset($_SESSION['user_sd'])){ 
        session_start(['name'=>'SD']); 
    }

	$db_cliente = $_SESSION['db_cliente'];

	$datos = array(
		0 => $db_cliente, 					
	);
	
	echo json_encode($datos);