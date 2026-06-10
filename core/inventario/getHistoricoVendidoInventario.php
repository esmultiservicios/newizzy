<?php
// core/inventario/getHistoricoVendidoInventario.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida',
            'resumen' => [
                'cantidad_vendida' => 0,
                'total_vendido' => 0
            ],
            'data' => []
        ]);
        exit;
    }
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$tipo_producto_id = isset($_POST['tipo_producto_id']) ? (int)$_POST['tipo_producto_id'] : 0;
$productos_id = isset($_POST['productos_id']) ? (int)$_POST['productos_id'] : 0;

if ($empresa_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar la empresa de la sesión.',
        'resumen' => [
            'cantidad_vendida' => 0,
            'total_vendido' => 0
        ],
        'data' => []
    ]);
    exit;
}

$where = " f.empresa_id = '$empresa_id' AND f.estado IN (2,3) ";

if ($productos_id > 0) {
    $where .= " AND p.productos_id = '$productos_id' ";
}

if ($tipo_producto_id > 0) {
    $where .= " AND p.tipo_producto_id = '$tipo_producto_id' ";
}

$sql = "
    SELECT
        p.productos_id,
        p.barCode,
        p.nombre AS producto,
        COALESCE(c.nombre, 'Sin categoría') AS categoria,
        COALESCE(tp.nombre, 'Sin tipo') AS tipo_producto,
        COALESCE(SUM(fd.cantidad), 0) AS cantidad_vendida,
        COALESCE(SUM(fd.cantidad * fd.precio), 0) AS total_vendido
    FROM facturas f
    INNER JOIN facturas_detalles fd
        ON fd.facturas_id = f.facturas_id
    INNER JOIN productos p
        ON p.productos_id = fd.productos_id
       AND p.empresa_id = f.empresa_id
    LEFT JOIN categoria c
        ON p.categoria_id = c.categoria_id
    LEFT JOIN tipo_producto tp
        ON p.tipo_producto_id = tp.tipo_producto_id
    WHERE $where
    GROUP BY
        p.productos_id,
        p.barCode,
        p.nombre,
        c.nombre,
        tp.nombre
    HAVING cantidad_vendida > 0
    ORDER BY total_vendido DESC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];
$total_cantidad = 0;
$total_vendido = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cantidad_vendida = (float)$row['cantidad_vendida'];
        $valor_vendido = (float)$row['total_vendido'];

        $total_cantidad += $cantidad_vendida;
        $total_vendido += $valor_vendido;

        $data[] = [
            'productos_id' => (int)$row['productos_id'],
            'barCode' => $row['barCode'],
            'producto' => $row['producto'],
            'categoria' => $row['categoria'],
            'tipo_producto' => $row['tipo_producto'],
            'cantidad_vendida' => $cantidad_vendida,
            'total_vendido' => $valor_vendido
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Histórico vendido generado correctamente.',
    'resumen' => [
        'cantidad_vendida' => $total_cantidad,
        'total_vendido' => $total_vendido
    ],
    'data' => $data
]);