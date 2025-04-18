<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$query = "SELECT 
            m.menu_id as id, 
            m.name as name, 
            m.icon,
            m.orden,
            m.descripcion,
            m.visible,
            'Menú Principal' as type, 
            'Sin dependencia' as dependency
          FROM menu m
          UNION ALL
          SELECT 
            s.submenu_id as id, 
            s.name as name, 
            s.icon,
            s.orden,
            s.descripcion,
            s.visible,
            'Submenú Nivel 1' as type, 
            m.descripcion as dependency
          FROM submenu s
          JOIN menu m ON s.menu_id = m.menu_id
          UNION ALL
          SELECT 
            s1.submenu1_id as id, 
            s1.name as name, 
            s1.icon,
            s1.orden,
            s1.descripcion,
            s1.visible,
            'Submenú Nivel 2' as type, 
            s.descripcion as dependency
          FROM submenu1 s1
          JOIN submenu s ON s1.submenu_id = s.submenu_id
          ORDER BY type, name";

$result = $insMainModel->ejecutar_consulta_simple($query);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data
]);