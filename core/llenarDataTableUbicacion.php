<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// 1. Validar sesión
$insMainModel = new mainModel();
$validacion = $insMainModel->validarSesion();

if($validacion['error']) {
    echo json_encode([
        'error' => true,
        'mensaje' => $validacion['mensaje'],
        'redireccion' => $validacion['redireccion']
    ]);
    exit();
}

// 2. Obtener datos del usuario
$privilegio_id = $_SESSION['privilegio_sd'];
$colaborador_id = $_SESSION['colaborador_id_sd'];
$empresa_id = $_SESSION['empresa_id_sd'];
$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

// 3. Consultar nombre del privilegio (sin Database.php)
$query_privilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
$result_privilegio = $insMainModel->ejecutar_consulta_simple($query_privilegio);
$privilegio_colaborador = ($result_privilegio->num_rows > 0) ? $result_privilegio->fetch_assoc()['nombre'] : "";

// 4. Obtener ubicaciones
$datos = [
    "privilegio_id" => $privilegio_id,
    "colaborador_id" => $colaborador_id,
    "privilegio_colaborador" => $privilegio_colaborador,
    "empresa_id" => $empresa_id,
	"estado" => $estado
];

$result = $insMainModel->getUbicacion($datos);

// 5. Formatear respuesta
$ubicaciones = [];
while($row = $result->fetch_assoc()) {
    $ubicaciones[] = [
        "ubicacion_id" => $row['ubicacion_id'],
        "ubicacion" => $row['ubicacion'],
        "empresa" => $row['empresa'],
        "estado" => $row['estado']
    ];
}

// 6. Enviar JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $ubicaciones
]);