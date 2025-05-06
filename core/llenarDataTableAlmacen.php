<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// 1. Initialize and validate session
$insMainModel = new mainModel();
$validacion = $insMainModel->validarSesion();

if($validacion['error']) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'title' => 'Error de sesión',
        'message' => $validacion['mensaje'],
        'redirect' => $validacion['redireccion']
    ]);
    exit();
}

// 2. Get session data
$privilegio_id = $_SESSION['privilegio_sd'];
$colaborador_id = $_SESSION['colaborador_id_sd'];
$empresa_id = $_SESSION['empresa_id_sd'];

// 3. Get privilege name (simplified query)
$query_privilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
$result_privilegio = $insMainModel->ejecutar_consulta_simple($query_privilegio);
$privilegio_colaborador = ($result_privilegio->num_rows > 0) ? $result_privilegio->fetch_assoc()['nombre'] : "";

// 4. Get almacenes data
$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

$datos = [
    "privilegio_id" => $privilegio_id,
    "colaborador_id" => $colaborador_id,
    "privilegio_colaborador" => $privilegio_colaborador,
    "empresa_id" => $empresa_id,
	"estado" => $estado
];

$result = $insMainModel->getAlmacen($datos);

// 5. Process and format data
$almacenes = [];
while($row = $result->fetch_assoc()) {
    $almacenes[] = [
        "almacen_id" => $row['almacen_id'],
        "empresa" => $row['empresa'],
        "facturarCero" => ($row['facturar_cero'] == 1) ? 'Si' : 'No',
        "almacen" => $row['almacen'],
        "ubicacion" => $row['ubicacion'],
		"estado" => $row['estado']
    ];
}

// 6. Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $almacenes,
    'total' => count($almacenes)
]);
?>