<?php
// core/getDraftBills.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // facturas_id seguro
    $facturas_id = isset($_POST['facturas_id']) ? intval($_POST['facturas_id']) : 0;
    if ($facturas_id <= 0) {
        throw new Exception('facturas_id inválido');
    }

    $cn = $insMainModel->connection();

    // ====== ENCABEZADO ======
    // Ajusta nombres de tablas/campos de clientes y colaboradores si difieren
    $sqlHeader = "
        SELECT
            f.facturas_id,
            f.clientes_id,
            f.colaboradores_id,
            f.secuencia_facturacion_id,
            f.apertura_id,
            f.number,
            f.tipo_factura,         -- 1=Contado, 2=Crédito
            f.importe,
            f.notas,
            f.fecha,
            f.estado,               -- 1=Borrador
            f.usuario,
            f.empresa_id,
            f.fecha_registro,
            f.fecha_dolar,
            f.no_orden,
            f.constancia,
            f.identificativo_sag,
            f.numero_interno,

            -- Cliente
            COALESCE(c.nombre, '')              AS cliente_nombre,
            COALESCE(c.rtn, '')                 AS cliente_rtn,

            -- Vendedor
            COALESCE(col.nombre, '')            AS vendedor_nombre

        FROM facturas f
        LEFT JOIN clientes c       ON f.clientes_id = c.clientes_id
        LEFT JOIN colaboradores col ON f.colaboradores_id = col.colaboradores_id
        WHERE f.facturas_id = ?
        LIMIT 1;
    ";
    $stmtH = $cn->prepare($sqlHeader);
    $stmtH->bind_param("i", $facturas_id);
    $stmtH->execute();
    $resH = $stmtH->get_result();
    if ($resH->num_rows === 0) {
        throw new Exception('Factura no encontrada');
    }
    $header = $resH->fetch_assoc();

    // ====== DETALLE ======
    // Usa tu misma consulta, pero en prepared y devolviendo campos útiles
    $sqlDetalle = "
        SELECT
            p.barCode                 AS barCode,
            p.nombre                  AS producto,
            p.precio_compra           AS costo,
            p.precio_venta            AS precio_venta,
            p.cantidad_mayoreo        AS cantidad_mayoreo,
            p.precio_mayoreo          AS precio_mayoreo,
            p.isv_venta               AS isv_venta,
            p.almacen_id              AS almacen_id,
            p.medida_id               AS medida_id,
            fd.facturas_detalle_id,
            fd.productos_id,
            SUM(fd.cantidad)          AS cantidad,
            fd.precio                 AS precio,
            SUM(fd.descuento)         AS descuento,
            SUM(fd.isv_valor)         AS isv_valor,
            med.nombre                AS medida
        FROM facturas_detalles fd
        INNER JOIN productos p   ON fd.productos_id = p.productos_id
        INNER JOIN medida med    ON p.medida_id = med.medida_id
        WHERE fd.facturas_id = ?
        GROUP BY fd.productos_id, fd.precio, med.nombre, fd.facturas_detalle_id, p.barCode, p.nombre, p.precio_compra,
                 p.precio_venta, p.cantidad_mayoreo, p.precio_mayoreo, p.isv_venta, p.almacen_id, p.medida_id
        ORDER BY fd.facturas_detalle_id ASC;
    ";
    $stmtD = $cn->prepare($sqlDetalle);
    $stmtD->bind_param("i", $facturas_id);
    $stmtD->execute();
    $resD = $stmtD->get_result();

    $detalle = [];
    $subtotal = 0.0;
    $descuento = 0.0;
    $isv = 0.0;

    while ($row = $resD->fetch_assoc()) {
        $detalle[] = $row;
        $subtotal  += floatval($row['precio']) * floatval($row['cantidad']);
        $descuento += floatval($row['descuento']);
        $isv       += floatval($row['isv_valor']);
    }
    $total = ($subtotal - $descuento) + $isv;

    echo json_encode([
        'type'    => 'success',
        'estado'  => true,
        'message' => 'Factura borrador encontrada',
        'header'  => $header,
        'detalle' => $detalle,
        'totales' => [
            'subtotal'  => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'isv'       => round($isv, 2),
            'total'     => round($total, 2)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'type'    => 'error',
        'estado'  => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}