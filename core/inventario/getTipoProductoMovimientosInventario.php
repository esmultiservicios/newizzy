<?php
// core/inventario/getTipoProductoMovimientosInventario.php

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

$sql = "
    SELECT categoria_id, nombre
    FROM categoria
    WHERE estado = 1
    ORDER BY nombre ASC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

if ($result && $result->num_rows > 0) {
    echo '<option value="0">Todo</option>';

    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['categoria_id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
} else {
    echo '<option value="0">Todo</option>';
}