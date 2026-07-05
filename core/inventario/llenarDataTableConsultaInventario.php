<?php
// core/inventario/llenarDataTableConsultaInventario.php
// Auditoría de ajustes de inventario.
// Consulta únicamente la tabla inventario_ajustes y devuelve JSON para DataTables.

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

function consultaInventarioJson($data = [], $extra = [])
{
    echo json_encode(array_merge(['data' => $data], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function consultaInventarioLimpiar($mysqli, $valor)
{
    return $mysqli->real_escape_string(trim((string)$valor));
}

function consultaInventarioTableExists($mysqli, $table)
{
    $table = $mysqli->real_escape_string($table);
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

function consultaInventarioColumnExists($mysqli, $table, $column)
{
    $table = $mysqli->real_escape_string($table);
    $column = $mysqli->real_escape_string($column);
    $result = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function consultaInventarioPrimerColumna($mysqli, $table, $columns)
{
    foreach ($columns as $column) {
        if (consultaInventarioColumnExists($mysqli, $table, $column)) {
            return $column;
        }
    }

    return null;
}

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        consultaInventarioJson([], [
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
    }
}

$mysqli = $insMainModel->connection();

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

if ($empresa_id <= 0) {
    consultaInventarioJson([], [
        'success' => false,
        'message' => 'No se pudo identificar la empresa de la sesión.'
    ]);
}

if (!consultaInventarioTableExists($mysqli, 'inventario_ajustes')) {
    consultaInventarioJson([], [
        'success' => false,
        'message' => 'No existe la tabla inventario_ajustes.'
    ]);
}

if (!consultaInventarioTableExists($mysqli, 'productos')) {
    consultaInventarioJson([], [
        'success' => false,
        'message' => 'No existe la tabla productos.'
    ]);
}

$fechai = isset($_POST['fechai']) ? consultaInventarioLimpiar($mysqli, $_POST['fechai']) : '';
$fechaf = isset($_POST['fechaf']) ? consultaInventarioLimpiar($mysqli, $_POST['fechaf']) : '';
$almacen = isset($_POST['almacen']) && $_POST['almacen'] !== '' ? (int)$_POST['almacen'] : 0;
$tipo_producto_id = isset($_POST['tipo_producto_id']) && $_POST['tipo_producto_id'] !== '' ? (int)$_POST['tipo_producto_id'] : 0;
$producto = isset($_POST['producto']) && $_POST['producto'] !== '' ? (int)$_POST['producto'] : 0;
$tipo_ajuste = isset($_POST['tipo_ajuste']) ? consultaInventarioLimpiar($mysqli, $_POST['tipo_ajuste']) : '';
$barcode = isset($_POST['barcode']) ? consultaInventarioLimpiar($mysqli, $_POST['barcode']) : '';

$productoNombreCol = consultaInventarioPrimerColumna($mysqli, 'productos', ['nombre', 'producto', 'descripcion']);
$productoBarcodeCol = consultaInventarioPrimerColumna($mysqli, 'productos', ['barCode', 'barcode', 'codigo_barra', 'codigo']);
$productoImageCol = consultaInventarioPrimerColumna($mysqli, 'productos', ['image', 'imagen', 'foto']);
$productoTipoCol = consultaInventarioPrimerColumna($mysqli, 'productos', ['tipo_producto_id', 'tipo_productos_id']);
$productoEstadoCol = consultaInventarioPrimerColumna($mysqli, 'productos', ['estado']);

if (!$productoNombreCol) {
    $productoNombreCol = 'productos_id';
}

if (!$productoBarcodeCol) {
    $productoBarcodeCol = 'productos_id';
}

$imageSelect = $productoImageCol ? "COALESCE(p.`$productoImageCol`, '') AS image" : "'' AS image";
$tipoProductoSelect = $productoTipoCol ? "COALESCE(p.`$productoTipoCol`, 0) AS tipo_producto_id" : "0 AS tipo_producto_id";

$tipoJoin = '';
$tipoSelect = "'' AS tipo_producto_nombre";

if ($productoTipoCol) {
    if (consultaInventarioTableExists($mysqli, 'tipo_producto')) {
        $tipoPk = consultaInventarioPrimerColumna($mysqli, 'tipo_producto', ['tipo_producto_id', 'tipo_productos_id']);
        $tipoNombre = consultaInventarioPrimerColumna($mysqli, 'tipo_producto', ['nombre', 'tipo_producto', 'descripcion']);

        if ($tipoPk && $tipoNombre) {
            $tipoJoin = " LEFT JOIN tipo_producto tp ON tp.`$tipoPk` = p.`$productoTipoCol` ";
            $tipoSelect = "COALESCE(tp.`$tipoNombre`, '') AS tipo_producto_nombre";
        }
    } elseif (consultaInventarioTableExists($mysqli, 'tipo_productos')) {
        $tipoPk = consultaInventarioPrimerColumna($mysqli, 'tipo_productos', ['tipo_producto_id', 'tipo_productos_id']);
        $tipoNombre = consultaInventarioPrimerColumna($mysqli, 'tipo_productos', ['nombre', 'tipo_producto', 'descripcion']);

        if ($tipoPk && $tipoNombre) {
            $tipoJoin = " LEFT JOIN tipo_productos tp ON tp.`$tipoPk` = p.`$productoTipoCol` ";
            $tipoSelect = "COALESCE(tp.`$tipoNombre`, '') AS tipo_producto_nombre";
        }
    }
}

$almacenJoin = '';
$bodegaSelect = "CONCAT('Bodega ', COALESCE(ia.almacen_id, 0)) AS bodega";

if (consultaInventarioTableExists($mysqli, 'almacen')) {
    $almacenNombreCol = consultaInventarioPrimerColumna($mysqli, 'almacen', ['nombre', 'almacen', 'descripcion']);

    if ($almacenNombreCol) {
        $almacenJoin = " LEFT JOIN almacen a ON a.almacen_id = ia.almacen_id ";
        $bodegaSelect = "COALESCE(a.`$almacenNombreCol`, CONCAT('Bodega ', COALESCE(ia.almacen_id, 0))) AS bodega";
    }
}

$loteJoin = '';
$loteSelect = "'' AS numero_lote, '' AS fecha_vencimiento";

if (consultaInventarioTableExists($mysqli, 'lotes')) {
    $loteNumeroCol = consultaInventarioPrimerColumna($mysqli, 'lotes', ['numero_lote', 'lote', 'codigo_lote']);
    $loteVenceCol = consultaInventarioPrimerColumna($mysqli, 'lotes', ['fecha_vencimiento', 'fecha_caducidad', 'vencimiento']);
    $numeroSelect = $loteNumeroCol ? "COALESCE(l.`$loteNumeroCol`, '')" : "''";
    $venceSelect = $loteVenceCol ? "COALESCE(l.`$loteVenceCol`, '')" : "''";

    $loteJoin = " LEFT JOIN lotes l ON l.lote_id = COALESCE(ia.lote_id, 0) ";
    $loteSelect = "$numeroSelect AS numero_lote, $venceSelect AS fecha_vencimiento";
}

$colaboradorJoin = '';
$colaboradorSelect = "'' AS colaborador";

if (consultaInventarioTableExists($mysqli, 'colaboradores')) {
    $colaboradorNombre = consultaInventarioPrimerColumna($mysqli, 'colaboradores', ['colaborador', 'nombre', 'nombres', 'nombre_completo']);
    $colaboradorApellido = consultaInventarioPrimerColumna($mysqli, 'colaboradores', ['apellido', 'apellidos']);

    if ($colaboradorNombre) {
        $colaboradorJoin = " LEFT JOIN colaboradores c ON c.colaboradores_id = ia.colaboradores_id ";

        if ($colaboradorApellido) {
            $colaboradorSelect = "TRIM(CONCAT(COALESCE(c.`$colaboradorNombre`, ''), ' ', COALESCE(c.`$colaboradorApellido`, ''))) AS colaborador";
        } else {
            $colaboradorSelect = "COALESCE(c.`$colaboradorNombre`, '') AS colaborador";
        }
    }
}

$movimientoJoin = '';
$movimientoSelect = "'' AS documento_movimiento";

if (consultaInventarioTableExists($mysqli, 'movimientos')) {
    $movimientoJoin = " LEFT JOIN movimientos m ON m.movimientos_id = ia.movimientos_id ";
    $movimientoSelect = "COALESCE(m.documento, '') AS documento_movimiento";
}

$where = [];
$where[] = "ia.empresa_id = '$empresa_id'";
$where[] = "ia.estado = 1";

if ($fechai !== '') {
    $where[] = "DATE(ia.fecha_registro) >= '$fechai'";
}

if ($fechaf !== '') {
    $where[] = "DATE(ia.fecha_registro) <= '$fechaf'";
}

if ($almacen > 0) {
    $where[] = "ia.almacen_id = '$almacen'";
}

if ($producto > 0) {
    $where[] = "ia.productos_id = '$producto'";
}

if ($tipo_producto_id > 0 && $productoTipoCol) {
    $where[] = "p.`$productoTipoCol` = '$tipo_producto_id'";
}

if ($tipo_ajuste !== '') {
    $tiposPermitidos = ['entrada', 'salida', 'sin_cambio'];

    if (in_array($tipo_ajuste, $tiposPermitidos, true)) {
        $where[] = "ia.tipo_ajuste = '$tipo_ajuste'";
    }
}

if ($barcode !== '') {
    $where[] = "p.`$productoBarcodeCol` LIKE '%$barcode%'";
}

if ($productoEstadoCol) {
    $where[] = "p.`$productoEstadoCol` = 1";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        ia.inventario_ajustes_id,
        ia.productos_id,
        COALESCE(p.`$productoNombreCol`, '') AS producto,
        COALESCE(p.`$productoBarcodeCol`, '') AS barCode,
        $imageSelect,
        $tipoProductoSelect,
        $tipoSelect,
        ia.almacen_id,
        $bodegaSelect,
        COALESCE(ia.lote_id, 0) AS lote_id,
        $loteSelect,
        ia.saldo_sistema,
        ia.conteo_fisico,
        ia.diferencia,
        ia.tipo_ajuste,
        ia.movimientos_id,
        $movimientoSelect,
        ia.comentario,
        ia.colaboradores_id,
        $colaboradorSelect,
        ia.fecha_registro,
        ia.estado
    FROM inventario_ajustes ia
    INNER JOIN productos p ON p.productos_id = ia.productos_id
    $almacenJoin
    $loteJoin
    $tipoJoin
    $colaboradorJoin
    $movimientoJoin
    $whereSql
    ORDER BY ia.fecha_registro DESC, ia.inventario_ajustes_id DESC
";

$result = $mysqli->query($sql);

if (!$result) {
    consultaInventarioJson([], [
        'success' => false,
        'message' => 'Error al consultar auditoría de ajustes: ' . $mysqli->error
    ]);
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'inventario_ajustes_id' => (int)$row['inventario_ajustes_id'],
        'productos_id' => (int)$row['productos_id'],
        'nombre' => $row['producto'],
        'producto' => $row['producto'],
        'barCode' => $row['barCode'],
        'barcode' => $row['barCode'],
        'image' => $row['image'],
        'tipo_producto_id' => (int)$row['tipo_producto_id'],
        'tipo_producto_nombre' => $row['tipo_producto_nombre'] !== '' ? $row['tipo_producto_nombre'] : $row['tipo_producto_id'],
        'almacen_id' => (int)$row['almacen_id'],
        'bodega' => $row['bodega'],
        'lote_id' => (int)$row['lote_id'],
        'numero_lote' => $row['numero_lote'],
        'fecha_vencimiento' => $row['fecha_vencimiento'],
        'saldo_sistema' => (float)$row['saldo_sistema'],
        'conteo_fisico' => (float)$row['conteo_fisico'],
        'diferencia' => (float)$row['diferencia'],
        'tipo_ajuste' => $row['tipo_ajuste'],
        'movimientos_id' => $row['movimientos_id'] !== null ? (int)$row['movimientos_id'] : 0,
        'documento_movimiento' => $row['documento_movimiento'],
        'comentario' => $row['comentario'],
        'colaboradores_id' => (int)$row['colaboradores_id'],
        'colaborador' => trim((string)$row['colaborador']) !== '' ? $row['colaborador'] : 'Usuario #' . (int)$row['colaboradores_id'],
        'fecha_registro' => $row['fecha_registro'],
        'estado' => (int)$row['estado']
    ];
}

consultaInventarioJson($data, [
    'success' => true,
    'totalrecords' => count($data),
    'totaldisplayrecords' => count($data)
]);