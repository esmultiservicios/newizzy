<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$id = $_POST['id'];
$tipo = $_POST['tipo'];

if($tipo == 'menu'){
    $query = "SELECT menu_id as id, name as nombre, 'menu' as type, NULL as dependency FROM menu WHERE menu_id = '$id'";
}elseif($tipo == 'submenu'){
    $query = "SELECT submenu_id as id, name as nombre, 'submenu' as type, menu_id as dependency FROM submenu WHERE submenu_id = '$id'";
}else{
    $query = "SELECT submenu1_id as id, name as nombre, 'submenu1' as type, submenu_id as dependency FROM submenu1 WHERE submenu1_id = '$id'";
}

$result = $insMainModel->ejecutar_consulta_simple($query);
$data = $result->fetch_assoc();

echo json_encode($data);