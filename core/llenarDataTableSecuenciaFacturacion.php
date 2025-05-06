<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// 1. Validar sesión
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

// 2. Obtener datos básicos de sesión
$privilegio_id = $_SESSION['privilegio_sd'];
$colaborador_id = $_SESSION['colaborador_id_sd'];
$empresa_id = $_SESSION['empresa_id_sd'];

// 3. Consultar nombre del privilegio (versión simplificada)
$query_privilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
$result_privilegio = $insMainModel->ejecutar_consulta_simple($query_privilegio);
$privilegio_colaborador = ($result_privilegio->num_rows > 0) ? $result_privilegio->fetch_assoc()['nombre'] : "";

// 4. Obtener secuencias de facturación
$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

$datos = [
    "privilegio_id" => $privilegio_id,
    "colaborador_id" => $colaborador_id,
    "privilegio_colaborador" => $privilegio_colaborador,
    "empresa_id" => $empresa_id,
	"estado" => $estado
];

$result = $insMainModel->getSecuenciaFacturacion($datos);

// 5. Procesar resultados
$secuencias = [];
while($row = $result->fetch_assoc()) {
    $secuencias[] = [
        "secuencia_facturacion_id" => $row['secuencia_facturacion_id'],
        "empresa" => $row['empresa'],
        "documento" => $row['documento'],
        "cai" => $row['cai'],
        "prefijo" => $row['prefijo'],
        "siguiente" => $row['siguiente'],
        "rango_inicial" => $row['rango_inicial'],
        "rango_final" => $row['rango_final'],
        "fecha_limite" => $row['fecha_limite'],
		"estado" => $row['estado']
    ];
}

// 6. Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $secuencias,
    'total' => count($secuencias)
]);