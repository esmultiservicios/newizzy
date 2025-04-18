<?php
// llenarDataTableSubMenuAccesos.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();    
$privilegio_id = $_POST['privilegio_id_accesos'];

// Primero obtenemos el planes_id usando el nuevo método
$planes_id = $insMainModel->obtener_planes_id_por_plan_id();

if ($planes_id !== null) {
    // Ahora consultamos los submenús de nivel 1 disponibles para este 'planes_id'
    $querySubMenu = "
        SELECT DISTINCT sm.submenu_id, sm.name AS submenu_name, m.name AS menu_name, 
            COALESCE(asm.estado, 2) AS submenu_estado, sm.descripcion, m.descripcion AS descripcion_padre
        FROM plan p
        INNER JOIN submenu_plan sp ON p.planes_id = sp.planes_id
        INNER JOIN submenu sm ON sp.submenu_id = sm.submenu_id
        INNER JOIN menu m ON sm.menu_id = m.menu_id
        LEFT JOIN acceso_submenu asm ON sm.submenu_id = asm.submenu_id AND asm.privilegio_id = ?
        WHERE p.planes_id = ?;
    ";

    // Conexión y ejecución directa de la consulta
    $conexion = $insMainModel->connection(); // Llamada a la conexión
    $stmt = $conexion->prepare($querySubMenu);
    if ($stmt === false) {
        die('Error al preparar la consulta: ' . $conexion->error);
    }

    // Vinculamos los parámetros a la consulta
    $stmt->bind_param('ii', $privilegio_id, $planes_id);

    // Ejecutamos la consulta
    $stmt->execute();

    // Obtenemos el resultado
    $resultSubMenu = $stmt->get_result();
    $data = [];
    
    while ($row = $resultSubMenu->fetch_assoc()) {
        // Estado: 1 = Asignado, 2 = No asignado
        $asignado = ($row['submenu_estado'] == 1) ? true : false;

        $data[] = array( 
            "menu"         => $row['menu_name'],  // Nombre del menú
            "submenu"      => $row['submenu_name'],  // Nombre del submenú
            "submenu_id"   => $row['submenu_id'],
            "descripcion"   => $row['descripcion'],
            "descripcion_padre"   => $row['descripcion_padre'],
            "estado"       => $row['submenu_estado'],  // Estado (1 = Mostrar, 2 = Ocultar)
            "asignado"     => $asignado  // Si está asignado o no
        );        
    }

    echo json_encode([
        "echo" => 1,
        "totalrecords" => count($data),
        "totaldisplayrecords" => count($data),
        "data" => $data
    ]);
} else {
    // Si no se encuentra el plan, enviar una respuesta vacía o error
    echo json_encode([
        "echo" => 1,
        "totalrecords" => 0,
        "totaldisplayrecords" => 0,
        "data" => []
    ]);
}