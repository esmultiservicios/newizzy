<?php
// core/llenarDataTableSecuenciaFacturacion.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'title' => 'Error de sesión',
        'message' => $validacion['mensaje'],
        'redirect' => $validacion['redireccion'],
        'data' => []
    ]);
    exit();
}

$privilegio_id = $_SESSION['privilegio_sd'];
$colaborador_id = $_SESSION['colaborador_id_sd'];
$empresa_id = $_SESSION['empresa_id_sd'];

$query_privilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = '$privilegio_id'";
$result_privilegio = $insMainModel->ejecutar_consulta_simple($query_privilegio);
$privilegio_colaborador = ($result_privilegio->num_rows > 0) ? $result_privilegio->fetch_assoc()['nombre'] : "";

$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

$datos = [
    "privilegio_id" => $privilegio_id,
    "colaborador_id" => $colaborador_id,
    "privilegio_colaborador" => $privilegio_colaborador,
    "empresa_id" => $empresa_id,
	"estado" => $estado
];

$result = $insMainModel->getSecuenciaFacturacion($datos);

$secuencias = [];

while ($row = $result->fetch_assoc()) {
    $secuencias[] = [
        "secuencia_facturacion_id" => $row['secuencia_facturacion_id'],
        "empresa" => $row['empresa'],
        "documento" => $row['documento'],
        "cai" => $row['cai'],
        "prefijo" => $row['prefijo'],
        "relleno" => $row['relleno'],
        "incremento" => $row['incremento'],
        "siguiente" => $row['siguiente'],
        "rango_inicial" => $row['rango_inicial'],
        "rango_final" => $row['rango_final'],
        "fecha_activacion" => $row['fecha_activacion'],
        "fecha_limite" => $row['fecha_limite'],
        "fecha_registro" => $row['fecha_registro'],
		"estado" => $row['estado']
    ];
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'data' => $secuencias,
    'total' => count($secuencias)
]);