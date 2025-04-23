<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "Database.php";

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

$database = new Database();

$tablaCategoriaGastos = "categoria_gastos";
$camposCategoriaGastos = ["categoria_gastos_id", "nombre", "estado"];  // Agregado campo "estado"
$condicionesCategoriaGastos = ["estado" => 1];  // Condición para el estado
$orderByCategoriaGastos = "";
$tablaJoinCategoriaGastos = "";
$condicionesJoinCategoriaGastos = [];
$resultadoCategoriaGastos = $database->consultarTabla(
    $tablaCategoriaGastos,
    $camposCategoriaGastos,
    $condicionesCategoriaGastos,
    $orderByCategoriaGastos,
    $tablaJoinCategoriaGastos,
    $condicionesJoinCategoriaGastos
);

$nombre = "";

$arreglo = array();
$data = array();

if (!empty($resultadoCategoriaGastos)) {
    foreach ($resultadoCategoriaGastos as $fila) {
        $data[] = array(
            "categoria_gastos_id" => $fila['categoria_gastos_id'],
            "nombre" => $fila['nombre'],
            "estado" => $fila['estado'],
        );
    }
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);
?>