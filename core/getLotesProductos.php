<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}


if(isset($_POST['producto_id']) && !empty($_POST['producto_id'])) {
    try {
        $producto_id = $_POST['producto_id'];
        $empresa_id = $_SESSION['empresa_id_sd'];

        // Obtener conexión y configurar timeout
        $conexion = $insMainModel->connection();
        $conexion->query("SET SESSION wait_timeout = 600");

        // Consulta para obtener los lotes del producto seleccionado
        $sql = "SELECT lote_id, numero_lote FROM lotes WHERE productos_id = ? AND empresa_id = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("ii", $producto_id, $empresa_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<option value="">Seleccione un lote</option>';
            while ($consulta = $result->fetch_assoc()) {
                echo '<option value="'.$consulta['lote_id'].'">'.$consulta['numero_lote'].'</option>';
            }
        } else {
            echo '<option value="">No hay lotes disponibles</option>';
        }
    } catch (Exception $e) {
        error_log("Error en getLotesProductos: " . $e->getMessage());
        echo '<option value="">Error al cargar lotes</option>';
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    echo '<option value="">Seleccione un producto</option>';
}