<?php
//getColaboradorInfo.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

if(isset($_POST['colaborador_id']) && $_POST['colaborador_id'] != "") {
    $colaborador_id = $_POST['colaborador_id'];
    
    $query = "SELECT c.colaboradores_id, c.nombre, c.apellido, c.identidad, c.telefono, 
                     DATE_FORMAT(c.fecha_ingreso, '%d/%m/%Y') as fecha_ingreso, c.estado
              FROM colaboradores c
              WHERE c.colaboradores_id = '$colaborador_id'";
              
    $result = $insMainModel->ejecutar_consulta_simple($query);
    
    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Colaborador no encontrado'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID de colaborador no recibido'
    ]);
}