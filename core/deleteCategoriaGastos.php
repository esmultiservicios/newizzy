<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "Database.php";
$database = new Database();

$categoria_gastos_id = $_POST['categoria_gastos_id'];
$categoria = $_POST['categoria'];

// CONSULTAMOS EL EGRESO
$tablEgresos = "egresos";
$camposEgresos = ["egresos_id"];
$condicionesEgresos = ["categoria_gastos_id" => $categoria_gastos_id]; 
$orderBy = "";
$tablaJoin = "";
$condicionesJoin = [];
$resultadoEgresos = $database->consultarTabla($tablEgresos, $camposEgresos, $condicionesEgresos, $orderBy, $tablaJoin, $condicionesJoin);

if (empty($resultadoEgresos)) {
	$condiciones_eliminar = ["categoria_gastos_id" => $categoria_gastos_id];

	// Llamar a la función para eliminar los registros
	if ($database->eliminarRegistros('categoria_gastos', $condiciones_eliminar)) {
		echo "success"; // Envía 'error' si hubo un error en la eliminación
	} else {
		echo "error: Error al eliminar el registro"; // Envía 'error' si hubo un error en la eliminación
	} 
}else{
    echo "error-existe: La categoria $categoria, cuenta con información en los gastos, no se puede eliminar"; // Envía 'existe' si registro cuenta con información
}
?>