<?php
// core/generarNomina.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

// Captura cualquier salida inesperada y asegúrate de devolver SOLO JSON
ob_start();

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

/* ============ Helper JSON ============ */
function json_out($arr){
    $garbage = trim(ob_get_clean()); // descarta cualquier eco previo
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ============ Entrada ============ */
$nomina_id  = isset($_POST['nomina_id'])  ? (int)$_POST['nomina_id']  : 0;
$empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

if ($nomina_id <= 0 || $empresa_id <= 0) {
    json_out([
        'status'  => 5,
        'title'   => 'Datos incompletos',
        'message' => 'Falta nomina_id o empresa_id para generar la nómina.'
    ]);
}

/* ============ Conexión (usa tu helper) ============ */
try {
    if (method_exists('mainModel', 'staticConnection')) {
        $cn = mainModel::staticConnection();
    } elseif (method_exists($insMainModel, 'connection')) {
        $cn = $insMainModel->connection();
    } else {
        throw new Exception('No se encontró método de conexión (staticConnection/connection) en mainModel.');
    }
    if (!($cn instanceof mysqli)) {
        throw new Exception('No se obtuvo un objeto mysqli válido.');
    }
} catch (Throwable $e) {
    json_out([
        'status'  => 7,
        'title'   => 'Conexión',
        'message' => 'No fue posible conectar con la base de datos: '.$e->getMessage()
    ]);
}

/* ============ VALIDAR: Debe existir al menos 1 empleado en nomina_detalles ============ */
$detallesCount = 0;
if ($stmtT = $cn->prepare("SELECT COUNT(*) AS c FROM nomina_detalles WHERE nomina_id = ?")) {
    $stmtT->bind_param("i", $nomina_id);
    $stmtT->execute();
    $stmtT->bind_result($detallesCount);
    $stmtT->fetch();
    $stmtT->close();
} else {
    json_out([
        'status'  => 11,
        'title'   => 'Tabla no encontrada',
        'message' => 'No se encontró la tabla de detalles (nomina_detalles).'
    ]);
}
if ((int)$detallesCount === 0) {
    json_out([
        'status'  => 8,
        'title'   => 'Sin empleados',
        'message' => 'No hay empleados registrados en el detalle de esta nómina. Agrega al menos uno antes de generar.'
    ]);
}

/* ============ Totales del detalle ============ */
$result_saldos = $insMainModel->getTotalesNominaDetalle($nomina_id);
if (!$result_saldos || $result_saldos->num_rows === 0) {
    json_out([
        'status'  => 4,
        'title'   => 'Sin detalles',
        'message' => 'No existe un detalle generado para esta nómina.'
    ]);
}
$row_saldos = $result_saldos->fetch_assoc();
$neto_total = (float)($row_saldos['neto'] ?? 0.00);

/* ============ Actualiza cabecera + detalles ============ */
if (!$insMainModel->actualizarNomina($nomina_id, $neto_total)) {
    json_out([
        'status'  => 3,
        'title'   => 'Actualización fallida',
        'message' => 'No se pudo actualizar el total de la nómina.'
    ]);
}
$insMainModel->actualizarNominaDetalles($nomina_id);

/* ============ Asistencia y vales ============ */
$result_colaboradores = $insMainModel->GetColaboradoresNomina($nomina_id);
if ($result_colaboradores) {
    while ($c = $result_colaboradores->fetch_assoc()) {
        $colabId = (int)$c['colaboradores_id'];
        if ($colabId > 0) {
            $insMainModel->ActualizarEstadoAsistencia($colabId);
            $insMainModel->actualizarVales([
                "colaboradores_id" => $colabId,
                "nomina_id"        => $nomina_id,
                "estado"           => "1"
            ]);
        }
    }
}

/* ============ Cuenta asociada a la nómina ============ */
$consulta_cuenta = $insMainModel->getCuentaIdNomina($nomina_id);
$cuentas_id = 0;
if ($consulta_cuenta && $consulta_cuenta->num_rows > 0) {
    $rowCuenta  = $consulta_cuenta->fetch_assoc();
    $cuentas_id = (int)($rowCuenta['cuentas_id'] ?? 0);
}
if ($cuentas_id <= 0) {
    json_out([
        'status'  => 6,
        'title'   => 'Configuración faltante',
        'message' => 'La nómina no tiene una cuenta asociada. Configúrala antes de generar.'
    ]);
}

/* ============ Sesión para colaborador que genera ============ */
if (session_status() === PHP_SESSION_NONE) {
    if (!empty($GLOBALS['session_name']) && is_string($GLOBALS['session_name'])) {
        @session_name($GLOBALS['session_name']);
    } else {
        @session_name('SD');
    }
    @session_start();
}
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

/* ============ Datos para egresos (según TU esquema) ============ */
$tipo_egreso         = 2; // 1=Compras, 2=Gastos (pago de nómina)
$fecha               = date("Y-m-d");
$fecha_registro      = date("Y-m-d H:i:s");
$factura             = "Nomina ".$nomina_id;
$factura_pdf         = null;     // puede quedar NULL o vacío
$subtotal            = $neto_total;
$descuento           = 0.00;
$nc                  = 0.00;     // nota de crédito
$impuesto            = 0.00;
$total               = $neto_total;
$observacion         = "Pago de Nomina ".$nomina_id;
$estado              = 1;
$categoria_gastos_id = 0;
$proveedores_id      = 1;        // proveedor genérico o el que uses

/* ============================
   BLOQUE CRÍTICO (MyISAM): LOCK TABLES
   - Genera IDs manuales (MAX+1)
   - Verifica duplicados
   - Inserta EGRESOS y MOVIMIENTOS
   ============================ */
$locked = false;
try {
    // Bloquea ambas tablas para evitar carreras al calcular MAX+1
    if (!$cn->query("LOCK TABLES egresos WRITE, movimientos_cuentas WRITE")) {
        throw new Exception("No se pudo bloquear tablas: ".$cn->error);
    }
    $locked = true;

    /* -- 0) Evitar duplicados por factura+tipo+empresa -- */
    $stmtChk = $cn->prepare(
        "SELECT egresos_id
           FROM egresos
          WHERE factura = ? AND tipo_egreso = ? AND empresa_id = ?
          LIMIT 1"
    );
    $stmtChk->bind_param("sii", $factura, $tipo_egreso, $empresa_id);
    $stmtChk->execute();
    $stmtChk->store_result();
    if ($stmtChk->num_rows > 0) {
        $stmtChk->close();
        throw new Exception('Egreso duplicado para esta nómina.', 2002);
    }
    $stmtChk->close();

    /* -- 1) Generar egresos_id (MAX+1) -- */
    $next_egreso_id = 1;
    $resMaxE = $cn->query("SELECT IFNULL(MAX(egresos_id),0)+1 AS next_id FROM egresos");
    if (!$resMaxE) { throw new Exception("No se pudo obtener next egresos_id: ".$cn->error); }
    $rowE = $resMaxE->fetch_assoc();
    $next_egreso_id = (int)$rowE['next_id'];
    $resMaxE->free();

    /* -- 2) Insert en EGRESOS con ID explícito -- */
    $sqlEgreso = "INSERT INTO egresos
        (egresos_id, cuentas_id, proveedores_id, empresa_id, tipo_egreso, fecha, factura, factura_pdf,
         subtotal, descuento, nc, impuesto, total, observacion, estado, colaboradores_id, fecha_registro, categoria_gastos_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmtE = $cn->prepare($sqlEgreso);
    if (!$stmtE) {
        throw new Exception("No se pudo preparar INSERT egresos: ".$cn->error);
    }

    // 18 params (i i i i i s s s d d d d d s i i s i)
    $stmtE->bind_param(
        "iiiiisssdddddsiisi",
        $next_egreso_id,     // i
        $cuentas_id,         // i
        $proveedores_id,     // i
        $empresa_id,         // i
        $tipo_egreso,        // i
        $fecha,              // s
        $factura,            // s
        $factura_pdf,        // s (NULL/'' permitido)
        $subtotal,           // d
        $descuento,          // d
        $nc,                 // d
        $impuesto,           // d
        $total,              // d
        $observacion,        // s
        $estado,             // i
        $colaboradores_id,   // i
        $fecha_registro,     // s
        $categoria_gastos_id // i
    );

    if (!$stmtE->execute()) {
        throw new Exception("Error al insertar egreso: ".$stmtE->error);
    }
    $stmtE->close();

    /* -- 3) Saldo anterior de la cuenta -- */
    $saldo_anterior = 0.00;
    $stmtSaldo = $cn->prepare(
        "SELECT saldo
           FROM movimientos_cuentas
          WHERE cuentas_id = ?
          ORDER BY movimientos_cuentas_id DESC
          LIMIT 1"
    );
    $stmtSaldo->bind_param("i", $cuentas_id);
    $stmtSaldo->execute();
    $stmtSaldo->bind_result($saldo_anterior_db);
    if ($stmtSaldo->fetch()) {
        $saldo_anterior = (float)$saldo_anterior_db;
    }
    $stmtSaldo->close();

    $ingreso = 0.00;
    $egreso  = $total;
    $saldo   = $saldo_anterior - $egreso;

    /* -- 4) Generar movimientos_cuentas_id (MAX+1) -- */
    $next_mov_id = 1;
    $resMaxM = $cn->query("SELECT IFNULL(MAX(movimientos_cuentas_id),0)+1 AS next_id FROM movimientos_cuentas");
    if (!$resMaxM) { throw new Exception("No se pudo obtener next movimientos_cuentas_id: ".$cn->error); }
    $rowM = $resMaxM->fetch_assoc();
    $next_mov_id = (int)$rowM['next_id'];
    $resMaxM->free();

    /* -- 5) Insert en MOVIMIENTOS_CUENTAS con ID explícito -- */
    $sqlMov = "INSERT INTO movimientos_cuentas
        (movimientos_cuentas_id, cuentas_id, empresa_id, fecha, ingreso, egreso, saldo, colaboradores_id, fecha_registro)
        VALUES (?,?,?,?,?,?,?,?,?)";

    $stmtM = $cn->prepare($sqlMov);
    if (!$stmtM) {
        throw new Exception("No se pudo preparar INSERT movimientos_cuentas: ".$cn->error);
    }

    // 9 params (i i i s d d d i s)
    $stmtM->bind_param(
        "iiisdddis",
        $next_mov_id,       // i
        $cuentas_id,        // i
        $empresa_id,        // i
        $fecha,             // s
        $ingreso,           // d
        $egreso,            // d
        $saldo,             // d
        $colaboradores_id,  // i
        $fecha_registro     // s
    );

    if (!$stmtM->execute()) {
        throw new Exception("Error al insertar movimiento de cuenta: ".$stmtM->error);
    }
    $stmtM->close();

    /* -- 6) Desbloqueo y respuesta OK -- */
    if ($locked) { $cn->query("UNLOCK TABLES"); $locked = false; }

    json_out([
        'status'    => 1,
        'title'     => 'Nómina generada',
        'message'   => 'La nómina se ha generado correctamente.',
        'nomina_id' => $nomina_id
    ]);

} catch (Throwable $e) {
    if ($locked) { $cn->query("UNLOCK TABLES"); }
    // Mapea duplicado a status 2
    $status = ($e->getCode() === 2002) ? 2 : 9;
    $title  = ($e->getCode() === 2002) ? 'Egreso duplicado' : 'Error';
    $msg    = ($e->getCode() === 2002) ? 'Ya existe un egreso registrado para esta nómina.' : ('No fue posible generar la nómina: '.$e->getMessage());

    json_out([
        'status'  => $status,
        'title'   => $title,
        'message' => $msg
    ]);
}