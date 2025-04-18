<?php
// llenarDataTableMenuAccesos.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
$privilegio_id = $_POST['privilegio_id_accesos'];

$conexion = $insMainModel->connection(); // Llamamos al método no estático de la instancia

try {
    // Desactivar autocommit para la transacción
    $conexion->autocommit(false);

    // Primero obtenemos el planes_id usando el nuevo método
    $planes_id = $insMainModel->obtener_planes_id_por_plan_id();

    $planes_id = (int) $planes_id;
    $privilegio_id = (int) $privilegio_id;

    if ($planes_id !== null) {
        // Ahora consultamos los menús disponibles para este 'planes_id'
        $queryMenu = "
            SELECT DISTINCT m.menu_id, m.name AS menu_name, COALESCE(am.estado, 2) AS menu_estado, m.descripcion
            FROM plan p
            INNER JOIN menu_plan mp ON p.planes_id = mp.planes_id
            INNER JOIN menu m ON mp.menu_id = m.menu_id
            LEFT JOIN acceso_menu am ON m.menu_id = am.menu_id AND am.privilegio_id = ?
            WHERE p.planes_id = ?;
        ";

        // Preparamos la consulta
        $stmt = $conexion->prepare($queryMenu);
        if ($stmt === false) {
            die('Error al preparar la consulta: ' . $conexion->error);
        }

        // Vinculamos los parámetros a la consulta
        $stmt->bind_param('ii', $privilegio_id, $planes_id);

        // Ejecutamos la consulta
        $stmt->execute();

        // Obtenemos el resultado
        $resultMenu = $stmt->get_result();
        $data = [];
        
        while ($row = $resultMenu->fetch_assoc()) {
            // Estado: 1 = Asignado, 2 = No asignado
            $asignado = ($row['menu_estado'] == 1) ? true : false;

            $data[] = array(
                "menu_id"      => $row['menu_id'],
                "menu_name"    => $row['menu_name'],
                "descripcion"    => $row['descripcion'],
                "estado"       => $row['menu_estado'],  // Estado (1 = Mostrar, 2 = Ocultar)
                "asignado"     => $asignado  // Si está asignado o no
            );
        }

        // Confirmar la transacción
        $conexion->commit();

        // Devolver los resultados como JSON
        echo json_encode(["data" => $data]);
    } else {
        // Si no se encuentra el plan, enviar una respuesta vacía o error
        echo json_encode(["data" => []]);
    }
} catch (Exception $e) {
    // Si ocurre algún error, revertir la transacción
    $conexion->rollback();

    // Retornar un mensaje de error en formato JSON
    echo json_encode(["error" => "Error al obtener los menús: " . $e->getMessage()]);
} finally {
    // Asegurarse de restaurar el autocommit y cerrar la conexión
    $conexion->autocommit(true);
    $conexion->close();
}