<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$username = $_POST['email'];
	$password = $insMainModel->encryption($_POST['pass']);
	$estatus = 1;
	
	$mysqli = $insMainModel->connectionDBLocal(DB_MAIN);

	$where = "";
	$where1 = "";

	if($username === "admin"){
		$where = "WHERE BINARY u.username = '$username' AND u.password = '$password' AND u.estado = '$estatus'";
		$where1 = "WHERE BINARY u.username = '$username'";
	}else{
		$where = "WHERE BINARY u.email = '$username' AND u.password = '$password' AND u.estado = '$estatus'";
		$where1 = "WHERE BINARY u.email = '$username'";
	}
	
	$query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
		FROM users AS u
		INNER JOIN tipo_user AS tu
		ON u.tipo_user_id = tu.tipo_user_id 
		INNER JOIN colaboradores AS c
		ON u.colaboradores_id = c.colaboradores_id
		".$where."
		GROUP by u.tipo_user_id";

	$result = $mysqli->query($query) or die($mysqli->error);
	
	if($result->num_rows>0){
		//OBTENEMOS LA BASE DE DATOS
		$query_db = "SELECT COALESCE(s.server_customers_id, '0') AS server_customers_id, COALESCE(s.db, '" . DB_MAIN . "') AS db, codigo_cliente
		FROM users AS u
		LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
		".$where1;
		
	
		$resultDb = $mysqli->query($query_db) or die($mysqli->error);
		$consultaDB = $resultDb->fetch_assoc();
			
		//COSULTAMOS SI EL CLIENTE Y EL PIN SON CORRECTOS Y OBTENEMOS LA BASE DE DATOS PARA INICIAR AHI				
		$DB_Cliente = $consultaDB['db'];

		if($DB_Cliente === DB_MAIN){
			echo 1;
		}else{
			echo 0;
		}		
	}else{
		echo 0;
	}
?>