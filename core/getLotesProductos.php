<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

if(isset($_POST['producto_id']) && !empty($_POST['producto_id'])) {
    $producto_id = $_POST['producto_id'];
    $empresa_id = $_SESSION['empresa_id_sd'];

    // Consulta para obtener los lotes del producto seleccionado
    $sql = "SELECT lote_id, numero_lote FROM lotes WHERE productos_id = ? AND empresa_id = ?";
    $stmt = $insMainModel->connection()->prepare($sql);
    $stmt->bind_param("ii", $producto_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<option value="">Seleccione un lote</option>';
        while ($consulta = $result->fetch_assoc()) {
            echo '<option value="'.$consulta['lote_id'].'">'.$consulta['numero_lote'].'</option>';
        }
    } else {
        echo '<option value="">No hay lotes disponibles</option>';
    }
} else {
    echo '<option value="">Seleccione un producto</option>';
} 