<?php
// core/inventario/addAjusteInventario.php
// Ajuste de inventario por conteo físico.
// Este archivo NO modifica el modelo existente.
// Reutiliza la lógica actual de movimientoProductosModelo para registrar entradas/salidas
// y luego guarda la auditoría en inventario_ajustes.

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

/*
  IMPORTANTE:
  movimientoProductosModelo.php internamente usa rutas relativas como ../core/mainModel.php
  cuando $peticionAjax = true. Para respetar esa lógica sin tocar el modelo,
  cargamos el modelo desde una ubicación compatible y luego restauramos el directorio actual.
*/
$cwdAjusteInventario = getcwd();
@chdir(__DIR__ . '/../../ajax');
require_once __DIR__ . '/../../modelos/movimientoProductosModelo.php';
@chdir($cwdAjusteInventario);

class ajusteInventarioBridge extends movimientoProductosModelo
{
    public function registrarEntradaAjusteInventario($datos)
    {
        return $this->registrar_entrada_lote_modelo($datos);
    }

    public function registrarSalidaAjusteInventario($datos)
    {
        return $this->registrar_salida_lote_modelo($datos);
    }
}

$insMainModel = new mainModel();
$insMovimiento = new ajusteInventarioBridge();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'title' => 'Sesión inválida',
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
        exit;
    }
}

function limpiarTextoAjusteInventario($texto)
{
    $texto = trim((string)$texto);
    $texto = strip_tags($texto);
    $texto = str_replace(["\\", "'", '"', ';'], ['', '', '', ''], $texto);
    return $texto;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$productos_id = isset($_POST['productos_id']) ? (int)$_POST['productos_id'] : 0;
$almacen_id = isset($_POST['almacen_id']) ? (int)$_POST['almacen_id'] : 0;
$lote_id = isset($_POST['lote_id']) && $_POST['lote_id'] !== '' ? (int)$_POST['lote_id'] : 0;

$saldo_sistema = isset($_POST['saldo_sistema']) ? (float)$_POST['saldo_sistema'] : 0;
$conteo_fisico = isset($_POST['conteo_fisico']) ? (float)$_POST['conteo_fisico'] : 0;
$fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? limpiarTextoAjusteInventario($_POST['fecha_vencimiento']) : null;

$comentario = isset($_POST['comentario']) ? limpiarTextoAjusteInventario($_POST['comentario']) : '';
$comentario = mb_substr($comentario, 0, 255, 'UTF-8');

if ($comentario === '') {
    $comentario = 'Ajuste por conteo físico de inventario';
}

$fecha_registro = date('Y-m-d H:i:s');
$estado = 1;

if ($empresa_id <= 0 || $colaboradores_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Error',
        'message' => 'No se pudo identificar la empresa o el usuario de la sesión.'
    ]);
    exit;
}

if ($productos_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Producto requerido',
        'message' => 'No se recibió un producto válido para el ajuste.'
    ]);
    exit;
}

if ($almacen_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Bodega requerida',
        'message' => 'No se recibió una bodega válida para el ajuste.'
    ]);
    exit;
}

if ($conteo_fisico < 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Conteo inválido',
        'message' => 'El conteo físico no puede ser negativo.'
    ]);
    exit;
}

$sqlProducto = "
    SELECT productos_id
    FROM productos
    WHERE productos_id = '$productos_id'
    LIMIT 1
";

$resProducto = $insMainModel->ejecutar_consulta_simple($sqlProducto);

if (!$resProducto || $resProducto->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Producto no encontrado',
        'message' => 'El producto seleccionado no existe.'
    ]);
    exit;
}

/*
  La diferencia se recalcula aquí para que el backend sea quien decida.
  Ejemplo:
  saldo_sistema = 144, conteo_fisico = 100
  diferencia = -44 => salida de 44.
*/
$diferencia = $conteo_fisico - $saldo_sistema;
$tipo_ajuste = 'sin_cambio';
$cantidad_movimiento = 0;
$movimientos_id = 0;
$movimiento_registrado = false;

if ($diferencia > 0.0001) {
    $tipo_ajuste = 'entrada';
    $cantidad_movimiento = abs($diferencia);
} elseif ($diferencia < -0.0001) {
    $tipo_ajuste = 'salida';
    $cantidad_movimiento = abs($diferencia);
}

/*
  1) Registrar movimiento real usando la misma lógica existente del modelo.
     No se toca agregarMovimientoProductosAjax.php.
     No se toca movimientoProductosControlador.php.
     No se toca movimientoProductosModelo.php.
*/
if ($tipo_ajuste !== 'sin_cambio') {
    $comentario_movimiento = mb_substr(
        $comentario .
        ' | Ajuste inventario' .
        ' | Stock sistema: ' . number_format($saldo_sistema, 2, '.', '') .
        ' | Conteo físico: ' . number_format($conteo_fisico, 2, '.', '') .
        ' | Diferencia: ' . number_format($diferencia, 2, '.', ''),
        0,
        255,
        'UTF-8'
    );

    $datosMovimiento = [
        'productos_id' => $productos_id,
        'clientes_id' => 0,
        'comentario' => $comentario_movimiento,
        'almacen_id' => $almacen_id,
        'fecha_vencimiento' => $fecha_vencimiento,
        'cantidad' => $cantidad_movimiento,
        'empresa_id' => $empresa_id,
        'movimiento_lote' => $lote_id > 0 ? $lote_id : null
    ];

    if ($tipo_ajuste === 'entrada') {
        $resultadoMovimiento = $insMovimiento->registrarEntradaAjusteInventario($datosMovimiento);

        if (!is_array($resultadoMovimiento) || empty($resultadoMovimiento['success'])) {
            echo json_encode([
                'success' => false,
                'title' => 'Error en movimiento',
                'message' => is_array($resultadoMovimiento) && !empty($resultadoMovimiento['message'])
                    ? $resultadoMovimiento['message']
                    : 'No se pudo registrar la entrada por ajuste de inventario.'
            ]);
            exit;
        }

        $movimientos_id = isset($resultadoMovimiento['movimientos_id']) ? (int)$resultadoMovimiento['movimientos_id'] : 0;
        $movimiento_registrado = $movimientos_id > 0;
    }

    if ($tipo_ajuste === 'salida') {
        $resultadoMovimiento = $insMovimiento->registrarSalidaAjusteInventario($datosMovimiento);

        if (!is_array($resultadoMovimiento) || ($resultadoMovimiento['status'] ?? '') !== 'success') {
            echo json_encode([
                'success' => false,
                'title' => 'Error en movimiento',
                'message' => is_array($resultadoMovimiento) && !empty($resultadoMovimiento['message'])
                    ? $resultadoMovimiento['message']
                    : 'No se pudo registrar la salida por ajuste de inventario.'
            ]);
            exit;
        }

        $movimientos_id = isset($resultadoMovimiento['movimientos_id']) ? (int)$resultadoMovimiento['movimientos_id'] : 0;
        $movimiento_registrado = $movimientos_id > 0;
    }
}

/*
  2) Guardar auditoría del ajuste.
*/
$inventario_ajustes_id = $insMainModel->correlativo('inventario_ajustes_id', 'inventario_ajustes');
$loteSql = $lote_id > 0 ? "'$lote_id'" : 'NULL';
$movimientoSql = $movimientos_id > 0 ? "'$movimientos_id'" : 'NULL';

$insertAuditoria = "
    INSERT INTO inventario_ajustes (
        inventario_ajustes_id,
        productos_id,
        almacen_id,
        lote_id,
        saldo_sistema,
        conteo_fisico,
        diferencia,
        tipo_ajuste,
        movimientos_id,
        comentario,
        empresa_id,
        colaboradores_id,
        fecha_registro,
        estado
    ) VALUES (
        '$inventario_ajustes_id',
        '$productos_id',
        '$almacen_id',
        $loteSql,
        '$saldo_sistema',
        '$conteo_fisico',
        '$diferencia',
        '$tipo_ajuste',
        $movimientoSql,
        '$comentario',
        '$empresa_id',
        '$colaboradores_id',
        '$fecha_registro',
        '$estado'
    )
";

$okAuditoria = $insMainModel->ejecutar_consulta_simple($insertAuditoria);

if (!$okAuditoria) {
    echo json_encode([
        'success' => false,
        'title' => 'Error en auditoría',
        'message' => 'El movimiento pudo registrarse, pero no se pudo guardar la auditoría del ajuste.'
    ]);
    exit;
}

$mensaje = 'El ajuste de inventario se registró correctamente.';

if ($tipo_ajuste === 'entrada') {
    $mensaje = 'Ajuste registrado: se agregó una entrada de ' . number_format($cantidad_movimiento, 2) . ' unidades.';
} elseif ($tipo_ajuste === 'salida') {
    $mensaje = 'Ajuste registrado: se registró una salida de ' . number_format($cantidad_movimiento, 2) . ' unidades.';
} else {
    $mensaje = 'No hubo diferencia. Se guardó la auditoría sin movimiento.';
}

echo json_encode([
    'success' => true,
    'title' => 'Ajuste registrado',
    'message' => $mensaje,
    'movimiento_registrado' => $movimiento_registrado,
    'movimientos_id' => $movimientos_id,
    'inventario_ajustes_id' => $inventario_ajustes_id,
    'tipo_ajuste' => $tipo_ajuste,
    'saldo_sistema' => $saldo_sistema,
    'conteo_fisico' => $conteo_fisico,
    'diferencia' => $diferencia
]);
exit;