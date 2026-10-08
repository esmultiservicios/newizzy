<?php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');
ob_start();

require_once __DIR__ . "/configGenerales.php";
require_once __DIR__ . "/mainModel.php";

$insMainModel = new mainModel();

function nomina_json_out(array $data)
{
    if (ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function nomina_error_log_seguro(Throwable $e)
{
    error_log('IZZY generarNomina: ' . $e->getMessage());
}

$validacion = mainModel::validarSesion();
if (!empty($validacion['error'])) {
    nomina_json_out([
        'status' => 10,
        'title' => 'Sesión expirada',
        'message' => $validacion['mensaje'] ?? 'Debes iniciar sesión nuevamente.',
        'redirect' => $validacion['redireccion'] ?? null
    ]);
}

$nomina_id = isset($_POST['nomina_id']) ? (int)$_POST['nomina_id'] : 0;
$empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

if ($nomina_id <= 0 || $empresa_id <= 0) {
    nomina_json_out([
        'status' => 5,
        'title' => 'Datos incompletos',
        'message' => 'No se pudo identificar la nómina o la empresa.'
    ]);
}

try {
    $cn = method_exists('mainModel', 'staticConnection')
        ? mainModel::staticConnection()
        : $insMainModel->connection();

    if (!($cn instanceof mysqli)) {
        throw new RuntimeException('Conexión no disponible.');
    }

    // Validar cabecera antes de modificar cualquier estado.
    $stmt = $cn->prepare(
        "SELECT empresa_id, cuentas_id, estado, detalle
         FROM nomina
         WHERE nomina_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        throw new RuntimeException($cn->error);
    }

    $stmt->bind_param("i", $nomina_id);
    $stmt->execute();
    $stmt->bind_result($dbEmpresaId, $cuentas_id, $estadoNomina, $detalleNomina);

    if (!$stmt->fetch()) {
        $stmt->close();
        nomina_json_out([
            'status' => 12,
            'title' => 'Nómina no encontrada',
            'message' => 'La nómina seleccionada ya no existe.'
        ]);
    }
    $stmt->close();

    $dbEmpresaId = (int)$dbEmpresaId;
    $cuentas_id = (int)$cuentas_id;
    $estadoNomina = (int)$estadoNomina;

    if ($dbEmpresaId !== $empresa_id) {
        nomina_json_out([
            'status' => 13,
            'title' => 'Empresa no válida',
            'message' => 'La nómina no pertenece a la empresa seleccionada.'
        ]);
    }

    if ($estadoNomina === 1) {
        nomina_json_out([
            'status' => 2,
            'title' => 'Nómina ya generada',
            'message' => 'Esta nómina ya fue generada anteriormente.'
        ]);
    }

    if ($cuentas_id <= 0) {
        nomina_json_out([
            'status' => 6,
            'title' => 'Cuenta de pago requerida',
            'message' => 'Selecciona una cuenta de pago antes de generar la nómina.'
        ]);
    }

    $detallesCount = 0;
    $stmt = $cn->prepare("SELECT COUNT(*) FROM nomina_detalles WHERE nomina_id = ? AND estado = 0");
    if (!$stmt) {
        throw new RuntimeException($cn->error);
    }
    $stmt->bind_param("i", $nomina_id);
    $stmt->execute();
    $stmt->bind_result($detallesCount);
    $stmt->fetch();
    $stmt->close();

    if ((int)$detallesCount === 0) {
        nomina_json_out([
            'status' => 8,
            'title' => 'Sin empleados',
            'message' => 'Agrega al menos un empleado al detalle antes de generar la nómina.'
        ]);
    }

    $neto_total = 0.0;
    $stmt = $cn->prepare(
        "SELECT COALESCE(SUM(neto), 0)
         FROM nomina_detalles
         WHERE nomina_id = ? AND estado = 0"
    );
    if (!$stmt) {
        throw new RuntimeException($cn->error);
    }
    $stmt->bind_param("i", $nomina_id);
    $stmt->execute();
    $stmt->bind_result($neto_total);
    $stmt->fetch();
    $stmt->close();

    $neto_total = (float)$neto_total;
    if ($neto_total <= 0) {
        nomina_json_out([
            'status' => 4,
            'title' => 'Total inválido',
            'message' => 'El total neto de la nómina debe ser mayor que cero antes de generarla.'
        ]);
    }

    $colaboradores_id = (int)($_SESSION['colaborador_id_sd'] ?? 0);
    if ($colaboradores_id <= 0) {
        nomina_json_out([
            'status' => 10,
            'title' => 'Sesión inválida',
            'message' => 'No se pudo identificar al usuario que genera la nómina.'
        ]);
    }

    $tipo_egreso = 2;
    $fecha = date("Y-m-d");
    $fecha_registro = date("Y-m-d H:i:s");
    $factura = "Nomina " . $nomina_id;
    $factura_pdf = '';
    $subtotal = $neto_total;
    $descuento = 0.00;
    $nc = 0.00;
    $impuesto = 0.00;
    $total = $neto_total;
    $observacion = "Pago de Nómina " . $nomina_id;
    $estado = 1;
    $categoria_gastos_id = 0;
    $proveedores_id = 1;

    $locked = false;

    try {
        $lockSql = "LOCK TABLES
            egresos WRITE,
            movimientos_cuentas WRITE,
            nomina WRITE,
            nomina_detalles WRITE";

        if (!$cn->query($lockSql)) {
            throw new RuntimeException('No fue posible preparar la generación de la nómina.');
        }
        $locked = true;

        // Validación final dentro del bloqueo para evitar doble generación.
        $res = $cn->query("SELECT estado FROM nomina WHERE nomina_id = {$nomina_id} LIMIT 1");
        if (!$res || !$row = $res->fetch_assoc()) {
            throw new RuntimeException('La nómina ya no está disponible.');
        }
        if ((int)$row['estado'] === 1) {
            throw new RuntimeException('NOMINA_DUPLICADA');
        }

        $stmtChk = $cn->prepare(
            "SELECT egresos_id
             FROM egresos
             WHERE factura = ? AND tipo_egreso = ? AND empresa_id = ?
             LIMIT 1"
        );
        if (!$stmtChk) {
            throw new RuntimeException($cn->error);
        }
        $stmtChk->bind_param("sii", $factura, $tipo_egreso, $empresa_id);
        $stmtChk->execute();
        $stmtChk->store_result();

        if ($stmtChk->num_rows > 0) {
            $stmtChk->close();
            throw new RuntimeException('NOMINA_DUPLICADA');
        }
        $stmtChk->close();

        $resMaxE = $cn->query("SELECT IFNULL(MAX(egresos_id),0)+1 AS next_id FROM egresos");
        if (!$resMaxE) {
            throw new RuntimeException($cn->error);
        }
        $next_egreso_id = (int)$resMaxE->fetch_assoc()['next_id'];

        $sqlEgreso = $cn->prepare(
            "INSERT INTO egresos
            (egresos_id, cuentas_id, proveedores_id, empresa_id, tipo_egreso, fecha, factura, factura_pdf,
             subtotal, descuento, nc, impuesto, total, observacion, estado, colaboradores_id, fecha_registro, categoria_gastos_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$sqlEgreso) {
            throw new RuntimeException($cn->error);
        }

        $sqlEgreso->bind_param(
            "iiiiisssdddddsiisi",
            $next_egreso_id, $cuentas_id, $proveedores_id, $empresa_id, $tipo_egreso,
            $fecha, $factura, $factura_pdf, $subtotal, $descuento, $nc, $impuesto,
            $total, $observacion, $estado, $colaboradores_id, $fecha_registro, $categoria_gastos_id
        );

        if (!$sqlEgreso->execute()) {
            throw new RuntimeException($sqlEgreso->error);
        }
        $sqlEgreso->close();

        $saldo_anterior = 0.0;
        $stmtSaldo = $cn->prepare(
            "SELECT saldo
             FROM movimientos_cuentas
             WHERE cuentas_id = ?
             ORDER BY movimientos_cuentas_id DESC
             LIMIT 1"
        );
        if (!$stmtSaldo) {
            throw new RuntimeException($cn->error);
        }
        $stmtSaldo->bind_param("i", $cuentas_id);
        $stmtSaldo->execute();
        $stmtSaldo->bind_result($saldoDb);
        if ($stmtSaldo->fetch()) {
            $saldo_anterior = (float)$saldoDb;
        }
        $stmtSaldo->close();

        $ingreso = 0.00;
        $egreso = $total;
        $saldo = $saldo_anterior - $egreso;

        $resMaxM = $cn->query("SELECT IFNULL(MAX(movimientos_cuentas_id),0)+1 AS next_id FROM movimientos_cuentas");
        if (!$resMaxM) {
            throw new RuntimeException($cn->error);
        }
        $next_mov_id = (int)$resMaxM->fetch_assoc()['next_id'];

        $sqlMov = $cn->prepare(
            "INSERT INTO movimientos_cuentas
            (movimientos_cuentas_id, cuentas_id, empresa_id, fecha, ingreso, egreso, saldo, colaboradores_id, fecha_registro)
            VALUES (?,?,?,?,?,?,?,?,?)"
        );
        if (!$sqlMov) {
            throw new RuntimeException($cn->error);
        }

        $sqlMov->bind_param(
            "iiisdddis",
            $next_mov_id, $cuentas_id, $empresa_id, $fecha,
            $ingreso, $egreso, $saldo, $colaboradores_id, $fecha_registro
        );

        if (!$sqlMov->execute()) {
            throw new RuntimeException($sqlMov->error);
        }
        $sqlMov->close();

        $stmtNomina = $cn->prepare(
            "UPDATE nomina SET importe = ?, estado = 1 WHERE nomina_id = ? AND estado = 0"
        );
        if (!$stmtNomina) {
            throw new RuntimeException($cn->error);
        }
        $stmtNomina->bind_param("di", $neto_total, $nomina_id);
        if (!$stmtNomina->execute() || $stmtNomina->affected_rows !== 1) {
            throw new RuntimeException('No se pudo confirmar la cabecera de la nómina.');
        }
        $stmtNomina->close();

        $stmtDetalles = $cn->prepare(
            "UPDATE nomina_detalles SET estado = 1 WHERE nomina_id = ? AND estado = 0"
        );
        if (!$stmtDetalles) {
            throw new RuntimeException($cn->error);
        }
        $stmtDetalles->bind_param("i", $nomina_id);
        if (!$stmtDetalles->execute() || $stmtDetalles->affected_rows < 1) {
            throw new RuntimeException('No se pudieron confirmar los detalles de la nómina.');
        }
        $stmtDetalles->close();

        $cn->query("UNLOCK TABLES");
        $locked = false;
    } catch (Throwable $e) {
        if ($locked) {
            $cn->query("UNLOCK TABLES");
            $locked = false;
        }

        if ($e->getMessage() === 'NOMINA_DUPLICADA') {
            nomina_json_out([
                'status' => 2,
                'title' => 'Nómina ya generada',
                'message' => 'Esta nómina ya tiene un egreso asociado y no se volverá a generar.'
            ]);
        }

        throw $e;
    }

    // Operaciones auxiliares posteriores a la generación principal.
    // Si una falla, la nómina ya queda correctamente generada y se registra el incidente.
    try {
        $result_colaboradores = $insMainModel->GetColaboradoresNomina($nomina_id);
        if ($result_colaboradores) {
            while ($c = $result_colaboradores->fetch_assoc()) {
                $colabId = (int)($c['colaboradores_id'] ?? 0);
                if ($colabId > 0) {
                    $insMainModel->ActualizarEstadoAsistencia($colabId);
                    $insMainModel->actualizarVales([
                        'colaboradores_id' => $colabId,
                        'nomina_id' => $nomina_id,
                        'estado' => '1'
                    ]);
                }
            }
        }
    } catch (Throwable $auxError) {
        nomina_error_log_seguro($auxError);
    }

    nomina_json_out([
        'status' => 1,
        'title' => 'Nómina generada',
        'message' => 'La nómina se generó correctamente y el egreso fue registrado.',
        'nomina_id' => $nomina_id
    ]);
} catch (Throwable $e) {
    nomina_error_log_seguro($e);
    nomina_json_out([
        'status' => 9,
        'title' => 'No se pudo generar',
        'message' => 'No fue posible generar la nómina. Verifica la configuración e intenta nuevamente.'
    ]);
}
