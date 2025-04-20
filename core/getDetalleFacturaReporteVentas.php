<?php
// getDetalleFacturaReporteVentas.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

// Verificar si se recibió el parámetro necesario
if(!isset($_POST['facturas_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de factura no proporcionado',
        'data' => []
    ]);
    exit;
}

$mainModel = new mainModel();
$factura_id = $_POST['facturas_id'];

// 1. Primero obtener los datos principales de la factura
$query_cabecera = "SELECT 
    f.facturas_id,
    CONCAT(sf.prefijo, '', LPAD(f.number, sf.relleno, '0')) AS numero_factura,
    DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
    c.nombre AS cliente,
    CASE 
        WHEN f.tipo_factura = 1 THEN 'Contado' 
        ELSE 'Crédito' 
    END AS tipo_factura,
    f.estado,
    (SELECT SUM(fd.cantidad * fd.precio) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS subtotal,
    (SELECT SUM(fd.isv_valor) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS isv,
    (SELECT SUM(fd.descuento) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS descuento,
    f.importe AS total,
    f.notas
FROM 
    facturas AS f
    INNER JOIN clientes AS c ON f.clientes_id = c.clientes_id
    INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
    INNER JOIN documento AS d ON sf.documento_id = d.documento_id
WHERE f.facturas_id = '$factura_id'";

$factura = $mainModel->ejecutar_consulta_simple($query_cabecera)->fetch_assoc();

if (!$factura) {
    echo json_encode([
        'success' => false,
        'message' => 'Factura no encontrada',
        'data' => []
    ]);
    exit;
}

// 2. Obtener el detalle de los productos de la factura
$query_detalle = "SELECT 
    fd.productos_id,
    p.nombre AS producto,
    fd.cantidad,
    fd.precio,
    fd.isv_valor,
    fd.descuento,
    m.nombre AS medida,
    (fd.cantidad * fd.precio) AS subtotal
FROM 
    facturas_detalles AS fd
    LEFT JOIN productos AS p ON fd.productos_id = p.productos_id
    LEFT JOIN medida AS m ON p.medida_id = m.medida_id
WHERE fd.facturas_id = '$factura_id'";

$detalle_result = $mainModel->ejecutar_consulta_simple($query_detalle);
$items = [];

while($item = $detalle_result->fetch_assoc()) {
    $items[] = [
        'producto' => $item['producto'] ?? 'Producto no especificado',
        'cantidad' => $item['cantidad'],
        'precio' => $item['precio'],
        'isv_valor' => $item['isv_valor'],
        'descuento' => $item['descuento'],
        'medida' => $item['medida'] ?? '',
        'subtotal' => $item['subtotal']
    ];
}

// 3. Preparar la respuesta final
$response = [
    'success' => true,
    'data' => [
        'cabecera' => [
            'numero_factura' => $factura['numero_factura'],
            'fecha' => $factura['fecha'],
            'cliente' => $factura['cliente'],
            'tipo_factura' => $factura['tipo_factura'],
            'estado' => $factura['estado'],
            'subtotal' => $factura['subtotal'],
            'isv' => $factura['isv'],
            'descuento' => $factura['descuento'],
            'total' => $factura['total'],
            'notas' => $factura['notas']
        ],
        'detalle' => $items
    ]
];

echo json_encode($response);