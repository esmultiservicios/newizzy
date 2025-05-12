<?php	
//editarClientes.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$clientes_id = $_POST['clientes_id'];

// Obtener datos básicos del cliente
$result = $insMainModel->getClientesEdit($clientes_id);
$valores2 = $result->fetch_assoc();

// Obtener puntos del cliente
$query_puntos = "SELECT 
                    IFNULL(pc.total_puntos, 0) as total_puntos,
                    IFNULL(MAX(hp.fecha), 'No existe') as ultima_actualizacion
                 FROM clientes c
                 LEFT JOIN puntos_cliente pc ON c.clientes_id = pc.cliente_id
                 LEFT JOIN historial_puntos hp ON pc.cliente_id = hp.cliente_id 
                 WHERE c.clientes_id = '$clientes_id'";
$result_puntos = $insMainModel->ejecutar_consulta_simple($query_puntos);
$puntos_data = $result_puntos->fetch_assoc();

$datos = array(
    'nombre' => $valores2['nombre'],  
    'rtn' => $valores2['rtn'],
    'fecha' => $valores2['fecha'],
    'departamentos_id' => $valores2['departamentos_id'],
    'municipios_id' => $valores2['municipios_id'],
    'localidad' => $valores2['localidad'],
    'telefono' => $valores2['telefono'],
    'correo' => $valores2['correo'],	
    'estado' => $valores2['estado'],
    'puntos' => $puntos_data ? $puntos_data['total_puntos'] : 0,
    'ultima_actualizacion' => $puntos_data ? $puntos_data['ultima_actualizacion'] : 'No disponible'
);

echo json_encode($datos);