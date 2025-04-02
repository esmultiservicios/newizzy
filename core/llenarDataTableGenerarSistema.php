<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "Database.php";
	
	$database = new Database();

	$clientes_id = $_POST['clientes_id'];

	$tablaServerCustomer = "server_customers";
	$camposServerCustomer = ["server_customers_id", "clientes_id", "db", "validar", "sistema_id", "planes_id", "estado"];
	$condicionesServerCustomer = ["clientes_id" => $clientes_id];
	$orderBy = "";
	$resultadoServerCustomer = $database->consultarTabla($tablaServerCustomer, $camposServerCustomer, $condicionesServerCustomer, $orderBy);
	
	// Crear un arreglo en formato JSON
	$arreglo = array();
	$data = array();	

	foreach ($resultadoServerCustomer as $row) {
		//OBTENER EL NOMBRE DEL CLIENTE
		$tablaClientes = "clientes";
		$camposClientes = ["nombre", "telefono", "correo", "localidad", "eslogan", "otra_informacion", "whatsapp"];
		$condicionesClientes = ["clientes_id" => $row['clientes_id']];
		$orderBy = "";
		$resultadoClientes = $database->consultarTabla($tablaClientes, $camposClientes, $condicionesClientes, $orderBy);
		$nombre = "";
		$telefono = "";
		$correo = "";
		$ubicacion = "";
		$eslogan = "";
		$otra_informacion = "";
		$whatsapp = "";		

		if (!empty($resultadoClientes)) {
			$nombre = $resultadoClientes[0]['nombre'];
			$telefono = $resultadoClientes[0]['telefono'];
			$correo = $resultadoClientes[0]['correo'];
			$ubicacion = $resultadoClientes[0]['localidad'];
			$eslogan = $resultadoClientes[0]['eslogan'];
			$otra_informacion = $resultadoClientes[0]['otra_informacion'];
			$whatsapp = $resultadoClientes[0]['whatsapp'];			
		}

		//OBTENER EL NOMBRE DEL SISTEMA
		$tablaSistema = "sistema";
		$camposSistema = ["nombre"];
		$condicionesSistema = ["sistema_id" => $row['sistema_id']];
		$orderBy = "";
		$resultadoSistema = $database->consultarTabla($tablaSistema, $camposSistema, $condicionesSistema, $orderBy);
		$sistema = "";

		if (!empty($resultadoSistema)) {
			$sistema = $resultadoSistema[0]['nombre'];
		}		

		//OBTENER EL NOMBRE DEL PLAN
		$tablaPlanes = "planes";
		$camposPlanes = ["nombre"];
		$condicionesPlanes = ["planes_id" => $row['planes_id']];
		$orderBy = "";
		$resultadoPlanes = $database->consultarTabla($tablaPlanes, $camposPlanes, $condicionesPlanes, $orderBy);
		$plan = "";

		if (!empty($resultadoPlanes)) {
			$plan = $resultadoPlanes[0]['nombre'];
		}

		$data[] = array( 
			"server_customers_id" => $row['server_customers_id'],
			"clientes_id" => $row['clientes_id'],
			"db" => $row['db'],
			"validar" => $row['validar'] == 0 ? 'Si' : 'No',
			"estado" => $row['estado'],
			"nombre" => $nombre,
			"sistema" => $sistema,
			"plan" => $plan,
			"telefono" => $telefono,
			"correo" => $correo,
			"ubicacion" => $ubicacion,
			"eslogan" => $eslogan,
			"otra_informacion" => $otra_informacion,
			"whatsapp" => $otra_informacion
		);	
	}		

	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	// Devolver el arreglo en formato JSON
	header('Content-Type: application/json');
	echo json_encode($arreglo);
?>	