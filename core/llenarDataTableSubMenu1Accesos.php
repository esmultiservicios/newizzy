<?php
// llenarDataTableSubMenu1Accesos.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();    
$privilegio_id = $_POST['privilegio_id_accesos'];

// Primero obtenemos el planes_id usando el nuevo método
$planes_id = $insMainModel->obtener_planes_id_por_plan_id();

if ($planes_id !== null) {
    // Ahora consultamos los submenús de nivel 2 disponibles para este 'planes_id'
    $querySubMenu1 = "
        SELECT assm1.acceso_submenu1_id, sm1.submenu1_id, sm1.submenu_id, sm1.name AS submenu1_name, sm.name AS submenu_name, sm.descripcion, sm1.descripcion AS submenu_descripcion,
            COALESCE(assm1.estado, 2) AS submenu1_estado
        FROM plan p
        INNER JOIN submenu1_plan sp1 ON p.planes_id = sp1.planes_id
        INNER JOIN submenu1 sm1 ON sp1.submenu1_id = sm1.submenu1_id
        INNER JOIN submenu sm ON sm1.submenu_id = sm.submenu_id
        LEFT JOIN acceso_submenu1 assm1 ON sm1.submenu1_id = assm1.submenu1_id AND assm1.privilegio_id = ?
        WHERE p.planes_id = ?;
    ";

    $conexion = $insMainModel->connection();
    $stmt = $conexion->prepare($querySubMenu1);

    if ($stmt === false) {
        die('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param('ii', $privilegio_id, $planes_id);
    $stmt->execute();
    $resultSubMenu1 = $stmt->get_result();

    $data = [];
    while ($row = $resultSubMenu1->fetch_assoc()) {
        $asignado = ($row['submenu1_estado'] == 1);

        $data[] = array( 
            "menu"         => $row['submenu_name'],        // Submenú de nivel 1
            "submenu"      => $row['submenu1_name'],       // Submenú de nivel 2
            "submenu1_id"  => $row['submenu1_id'],
            "descripcion"  => $row['descripcion'],
            "submenu_descripcion"  => $row['submenu_descripcion'],
			"submenu_id"  => $row['acceso_submenu1_id'],		   // id que se usara
            "estado"       => $row['submenu1_estado'],     // Estado (1 = Mostrar, 2 = Ocultar)
            "asignado"     => $asignado
        );
    }

    echo json_encode([
        "echo" => 1,
        "totalrecords" => count($data),
        "totaldisplayrecords" => count($data),
        "data" => $data
    ]);
} else {
    echo json_encode([
        "echo" => 1,
        "totalrecords" => 0,
        "totaldisplayrecords" => 0,
        "data" => []
    ]);
}
