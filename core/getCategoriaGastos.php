<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "Database.php";
$database = new Database();

// CONSULTAMOS LOS USUARIOS DEL PLAN Y EL NOMBRE DEL PLAN QUE SELECCIONÓ EL CLIENTE
$tablaCategoriaGastos = "categoria_gastos";
$camposCategoriaGastos = ["categoria_gastos_id", "nombre"];
$condicionesCategoriaGastos = ["estado" => 1]; 
$orderBy = "";
$tablaJoin = "";
$condicionesJoin = [];
$resultadoCategoriaGastos = $database->consultarTabla($tablaCategoriaGastos, $camposCategoriaGastos, $condicionesCategoriaGastos, $orderBy, $tablaJoin, $condicionesJoin);

if (!empty($resultadoCategoriaGastos)) {
    foreach ($resultadoCategoriaGastos as $fila) {
        echo '<option value="' . $fila['categoria_gastos_id'] . '">' . $fila['nombre'] . '</option>';
    }
} else {
    echo '<option value="">No hay datos que mostrar</option>';
}