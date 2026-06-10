<?php
// core/inventario/getProductosMovimientosCategoriaInventario.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

$validacion = $insMainModel->validarSesion();
if (!empty($validacion['error'])) {
    echo '<option value="">Sesión inválida</option>';
    exit;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;

$where = "p.empresa_id = '$empresa_id' AND p.estado = 1";

if ($categoria_id > 0) {
    $where .= " AND p.categoria_id = '$categoria_id'";
}

$sql = "
    SELECT p.productos_id, p.nombre
    FROM productos p
    WHERE $where
    ORDER BY p.nombre ASC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

if ($result && $result->num_rows > 0) {
    echo '<option value="0">Todo</option>';

    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['productos_id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
} else {
    echo '<option value="0">Todo</option>';
}