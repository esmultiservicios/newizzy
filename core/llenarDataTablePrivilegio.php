<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
	return $insMainModel->showNotification([
		"title" => "Error de sesión",
		"text" => $validacion['mensaje'],
		"type" => "error",
		"funcion" => "window.location.href = '".$validacion['redireccion']."'"
	]);
}

$privilegio_id = $_SESSION['privilegio_sd'];
$colaborador_id = $_SESSION['colaborador_id_sd'];
$db_cliente = $_SESSION['db_cliente'];

// Obtener el nombre del privilegio
$queryPrivilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
$resultadoPrivilegio = $insMainModel->ejecutar_consulta_simple($queryPrivilegio);

$privilegio_colaborador = "";

if ($resultadoPrivilegio && $resultadoPrivilegio->num_rows > 0) {
	$row = $resultadoPrivilegio->fetch_assoc();
	$privilegio_colaborador = $row['nombre'];
}

$datos = [
	"privilegio_id" => $privilegio_id,
	"colaborador_id" => $colaborador_id,
	"privilegio_colaborador" => $privilegio_colaborador,
	"DB_MAIN" => $db_cliente,	
];

$result = $insMainModel->getPrivilegio($datos);

$data = [];

while ($row = $result->fetch_assoc()) {
	$privilegioActual = $row['privilegio_id'];

	// Contar cada tipo de acceso por separado
	$queryCounts = "
    SELECT 
        (SELECT COUNT(DISTINCT m.menu_id) FROM acceso_menu am
            INNER JOIN menu m ON am.menu_id = m.menu_id
            WHERE am.privilegio_id = '$privilegioActual' AND am.estado = 1) AS menus,
        
        (SELECT COUNT(DISTINCT sm.submenu_id) FROM acceso_submenu asm
            INNER JOIN submenu sm ON asm.submenu_id = sm.submenu_id
            WHERE asm.privilegio_id = '$privilegioActual' AND asm.estado = 1) AS submenus,
        
        (SELECT COUNT(DISTINCT sm1.submenu1_id) FROM acceso_submenu1 assm1
            INNER JOIN submenu1 sm1 ON assm1.submenu1_id = sm1.submenu1_id
            WHERE assm1.privilegio_id = '$privilegioActual' AND assm1.estado = 1) AS submenus1";

	$countResult = $insMainModel->ejecutar_consulta_simple($queryCounts);

	$menus = 0;
	$submenus = 0;
	$submenus1 = 0;

	if ($countResult && $countResult->num_rows > 0) {
		$countRow = $countResult->fetch_assoc();
		$menus = $countRow['menus'];
		$submenus = $countRow['submenus'];
		$submenus1 = $countRow['submenus1'];
	}

	$data[] = [
		"privilegio_id" => $row['privilegio_id'],
		"planes_id" => $row['privilegio_id'], // si este campo lo necesitas para los botones
		"nombre" => $row['nombre'],
		"menus_asignados" => $menus,
		"submenus_asignados" => $submenus,
		"submenus1_asignados" => $submenus1
	];
}

$arreglo = [
	"echo" => 1,
	"totalrecords" => count($data),
	"totaldisplayrecords" => count($data),
	"data" => $data
];

echo json_encode($arreglo);
