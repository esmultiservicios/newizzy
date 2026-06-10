<?php
// core/inventario/getAlmacenInventario.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo '<option value="">Sesión inválida</option>';
        exit;
    }
}

$datos = [
    "empresa_id" => isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0,
    "privilegio_colaborador" => isset($_SESSION['privilegio_sd']) ? $_SESSION['privilegio_sd'] : 0
];

$result = $insMainModel->getAlmacen($datos);

if ($result && $result->num_rows > 0) {
    echo '<option value="0">Todo</option>';

    while ($consulta2 = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($consulta2['almacen_id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($consulta2['almacen'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
} else {
    echo '<option value="">No hay datos que mostrar</option>';
}