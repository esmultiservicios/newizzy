<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Preparar respuesta JSON
$response = [
    'success' => false,
    'show_db' => false,
    'message' => '',
    'is_test' => (DB_MAIN === DB_PRUEBA)
];

try {
    $username = $_POST['email'] ?? '';
    $password = isset($_POST['pass']) ? $insMainModel->encryption($_POST['pass']) : '';
    $estatus = 1;

    if(empty($username)) {
        throw new Exception('Email es requerido');
    }

    if(empty($password)) {
        throw new Exception('Contraseña es requerida');
    }

    $mysqli = $insMainModel->connectionDBLocal(DB_MAIN);

    // Construir consultas
    $where = ($username === "admin") 
        ? "WHERE BINARY u.username = '$username' AND u.password = '$password' AND u.estado = '$estatus'"
        : "WHERE BINARY u.email = '$username' AND u.password = '$password' AND u.estado = '$estatus'";

    $where1 = ($username === "admin") 
        ? "WHERE BINARY u.username = '$username'"
        : "WHERE BINARY u.email = '$username'";

    // Consulta de autenticación
    $query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
        FROM users AS u
        INNER JOIN tipo_user AS tu ON u.tipo_user_id = tu.tipo_user_id 
        INNER JOIN colaboradores AS c ON u.colaboradores_id = c.colaboradores_id
        $where
        GROUP by u.tipo_user_id";

    $result = $mysqli->query($query);

    if($result && $result->num_rows > 0) {
        $response['success'] = true;
        
        // Solo verificamos DB si no es ambiente de prueba
        if(!$response['is_test']) {
            $query_db = "SELECT COALESCE(s.server_customers_id, '0') AS server_customers_id, 
                         COALESCE(s.db, '" . DB_MAIN . "') AS db, codigo_cliente
                         FROM users AS u
                         LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
                         $where1";
            
            $resultDb = $mysqli->query($query_db);
            if($resultDb) {
                $consultaDB = $resultDb->fetch_assoc();
                $DB_Cliente = $consultaDB['db'];
                $response['show_db'] = ($DB_Cliente === DB_MAIN);
            }
        }
    } else {
        $response['message'] = 'Credenciales incorrectas';
    }
} catch(Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);