<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "Database.php";
$database = new Database();

header('Content-Type: application/json'); // Especificamos que la respuesta es JSON

try {
    $categoria_gastos_id = $_POST['categoria_gastos_id'] ?? null;
    $categoria = $_POST['categoria'] ?? '';

    if (!$categoria_gastos_id) {
        echo json_encode([
            'success' => false,
            'title' => 'Error',
            'text' => 'ID de categoría no proporcionado'
        ]);
        exit();
    }

    // Consultamos si la categoría tiene egresos asociados
    $tablaEgresos = "egresos";
    $camposEgresos = ["egresos_id"];
    $condicionesEgresos = ["categoria_gastos_id" => $categoria_gastos_id]; 
    $resultadoEgresos = $database->consultarTabla($tablaEgresos, $camposEgresos, $condicionesEgresos);

    if (empty($resultadoEgresos)) {
        $condiciones_eliminar = ["categoria_gastos_id" => $categoria_gastos_id];

        if ($database->eliminarRegistros('categoria_gastos', $condiciones_eliminar)) {
            echo json_encode([
                'success' => true,
                'title' => 'Éxito',
                'text' => 'Categoría eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'title' => 'Error',
                'text' => 'Error al eliminar el registro'
            ]);
        } 
    } else {
        echo json_encode([
            'success' => false,
            'title' => 'Error',
            'text' => "La categoría $categoria cuenta con información en los gastos, no se puede eliminar"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'title' => 'Error',
        'text' => 'Error inesperado: ' . $e->getMessage()
    ]);
}