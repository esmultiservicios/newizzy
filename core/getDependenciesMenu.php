<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$tipo = $_POST['tipo'];
$menu_id = isset($_POST['menu_id']) ? $_POST['menu_id'] : null;

if ($tipo == 'getMenus') {
    $query = "SELECT menu_id as id, name as nombre FROM menu ORDER BY name";
} elseif ($tipo == 'getAllSubmenus') {
    $query = "SELECT submenu_id as id, name as nombre FROM submenu ORDER BY name";
} elseif ($tipo == 'getSubmenusByMenu' && isset($menu_id)) {
    $query = "SELECT submenu_id as id, name as nombre FROM submenu WHERE menu_id = '$menu_id' ORDER BY name";
}

$result = $insMainModel->ejecutar_consulta_simple($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row; // Guarda cada fila como un elemento del array
}

echo json_encode(["data" => $data]); // Devuelve un JSON con los datos
