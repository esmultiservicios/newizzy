<?php
//llenarDataTablePlanes.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$query = "SELECT * FROM planes ORDER BY nombre";
$result = $insMainModel->ejecutar_consulta_simple($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $configDisplay = "Sin configuraciones";
    $configsArray = [];
    
    if (!empty($row['configuraciones'])) {
        try {
            $configsArray = json_decode($row['configuraciones'], true);
            if (is_array($configsArray)) {  // AQUÍ FALTABA EL PARÉNTESIS DE CIERRE
                $configDisplay = "<ul class='list-unstyled mb-0'>";
                foreach ($configsArray as $key => $value) {
                    $configDisplay .= "<li><strong>{$key}:</strong> {$value}</li>";
                }
                $configDisplay .= "</ul>";
            }
        } catch (Exception $e) {
            error_log("Error al decodificar configuraciones para plan ID {$row['planes_id']}: " . $e->getMessage());
        }
    }

    $queryMenus = "SELECT COUNT(*) as total FROM menu_plan WHERE planes_id = '{$row['planes_id']}'";
    $querySubmenus = "SELECT COUNT(*) as total FROM submenu_plan WHERE planes_id = '{$row['planes_id']}'";
    $querySubmenus2 = "SELECT COUNT(*) as total FROM submenu1_plan WHERE planes_id = '{$row['planes_id']}'";

    $menusCount = $insMainModel->ejecutar_consulta_simple($queryMenus)->fetch_assoc()['total'];
    $submenusCount = $insMainModel->ejecutar_consulta_simple($querySubmenus)->fetch_assoc()['total'];
    $submenus2Count = $insMainModel->ejecutar_consulta_simple($querySubmenus2)->fetch_assoc()['total'];


    $data[] = [
        "planes_id" => $row['planes_id'],
        "nombre" => htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'),
        "estado" => $row['estado'],
        "configuraciones" => $configDisplay,
        "configuraciones_json" => $configsArray,
        "menus_asignados" => $menusCount,
        "submenus_asignados" => $submenusCount,
        "submenus2_asignados" => $submenus2Count        
    ];
}

header('Content-Type: application/json');
echo json_encode(["data" => $data]);
exit();