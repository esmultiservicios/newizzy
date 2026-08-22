<?php
// Ubicación: core/facturas/procesarFacturasRecurrentes.php
// Ejecutar exclusivamente desde Cron/CLI.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este proceso solamente puede ejecutarse desde la consola del servidor.');
}

date_default_timezone_set('America/Tegucigalpa');

$appUrl = trim((string)getenv('APP_URL'));
if ($appUrl === '') {
    fwrite(STDERR, "Debe definir APP_URL para ejecutar las facturas recurrentes.\n");
    exit(1);
}

$appUrlPartes = parse_url($appUrl);
$appHost = (string)($appUrlPartes['host'] ?? '');
if ($appHost === '') {
    fwrite(STDERR, "APP_URL no contiene un dominio válido.\n");
    exit(1);
}

// configGenerales.php también se utiliza desde navegador y espera estas
// variables HTTP. En Cron/CLI se construyen a partir de APP_URL.
$appScheme = strtolower((string)($appUrlPartes['scheme'] ?? 'https'));
$appPath = rtrim((string)($appUrlPartes['path'] ?? ''), '/');
$_SERVER['HTTP_HOST'] = $appHost;
$_SERVER['SERVER_NAME'] = $appHost;
$_SERVER['REQUEST_URI'] = ($appPath !== '' ? $appPath : '').'/';
$_SERVER['REQUEST_SCHEME'] = $appScheme;
$_SERVER['HTTPS'] = ($appScheme === 'https') ? 'on' : 'off';
$_SERVER['SERVER_PORT'] = ($appScheme === 'https') ? '443' : '80';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = '/core/facturas/procesarFacturasRecurrentes.php';
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

$raiz = dirname(__DIR__, 2);
chdir($raiz);
$peticionAjax = false;

require_once $raiz.'/core/configGenerales.php';
require_once $raiz.'/controladores/facturasControlador.php';
require_once __DIR__.'/FacturaRecurrenteServicio.php';
require_once __DIR__.'/enviarCorreoFacturaRecurrente.php';

function proximaFechaRecurrente($fechaProgramada, $periodicidad, $diaMesOriginal = null)
{
    $actual = new DateTimeImmutable($fechaProgramada, new DateTimeZone('America/Tegucigalpa'));

    if ($periodicidad === 'once') {
        return null;
    }
    if ($periodicidad === 'daily') {
        return $actual->modify('+1 day')->format('Y-m-d H:i:s');
    }
    if ($periodicidad === 'weekly') {
        return $actual->modify('+7 days')->format('Y-m-d H:i:s');
    }

    // Mensual sin saltar febrero cuando el día original es 29, 30 o 31.
    $dia = (int)($diaMesOriginal ?: $actual->format('d'));
    $primerDiaSiguiente = $actual->modify('first day of next month');
    $ultimoDia = (int)$primerDiaSiguiente->format('t');
    $diaDestino = min($dia, $ultimoDia);

    return $primerDiaSiguiente
        ->setDate(
            (int)$primerDiaSiguiente->format('Y'),
            (int)$primerDiaSiguiente->format('m'),
            $diaDestino
        )
        ->format('Y-m-d H:i:s');
}

$conexion = mainModel::connection();
$servicio = new FacturaRecurrenteServicio();
$resumen = ['generadas' => 0, 'errores' => 0, 'correos' => 0, 'detalle' => []];

$pendientes = $conexion->query(
    "SELECT rec_id
     FROM facturas_recurrentes
     WHERE estado = 1
       AND next_run_at <= NOW()
       AND (until_at IS NULL OR DATE(next_run_at) <= until_at)
     ORDER BY next_run_at ASC
     LIMIT 25"
);

if ($pendientes === false) {
    fwrite(STDERR, 'No se pudieron consultar recurrencias: '.$conexion->error.PHP_EOL);
    exit(1);
}

$ids = [];
while ($fila = $pendientes->fetch_assoc()) {
    $ids[] = (int)$fila['rec_id'];
}

foreach ($ids as $recId) {
    $ejecucionId = 0;
    $facturasId = 0;
    $empresaId = 0;
    $enviarCorreo = false;

    try {
        $conexion->begin_transaction();

        $stmtRec = $conexion->prepare(
            'SELECT * FROM facturas_recurrentes WHERE rec_id = ? AND estado = 1 LIMIT 1 FOR UPDATE'
        );
        $stmtRec->bind_param('i', $recId);
        $stmtRec->execute();
        $resRec = $stmtRec->get_result();
        $recurrente = $resRec ? $resRec->fetch_assoc() : null;
        $stmtRec->close();

        if (!$recurrente || strtotime($recurrente['next_run_at']) > time()) {
            $conexion->rollback();
            continue;
        }

        $empresaId = (int)$recurrente['empresa_id'];
        $programada = $recurrente['next_run_at'];
        $enviarCorreo = ((int)$recurrente['enviar_correo'] === 1);

        $stmtEjecucion = $conexion->prepare(
            "INSERT INTO facturas_recurrentes_ejecuciones
                (rec_id, empresa_id, scheduled_at, estado, correo_estado, fecha_inicio)
             VALUES (?, ?, ?, 0, ?, NOW())"
        );
        $correoInicial = $enviarCorreo ? 0 : 3;
        $stmtEjecucion->bind_param('iisi', $recId, $empresaId, $programada, $correoInicial);
        if (!$stmtEjecucion->execute()) {
            if ((int)$stmtEjecucion->errno === 1062) {
                $stmtEjecucion->close();
                $conexion->rollback();
                continue;
            }
            throw new Exception('No se pudo registrar la ejecución: '.$stmtEjecucion->error);
        }
        $ejecucionId = (int)$stmtEjecucion->insert_id;
        $stmtEjecucion->close();

        $stmtDetalle = $conexion->prepare(
            'SELECT * FROM facturas_recurrentes_detalle WHERE rec_id = ? ORDER BY rec_detalle_id ASC'
        );
        $stmtDetalle->bind_param('i', $recId);
        $stmtDetalle->execute();
        $resDetalle = $stmtDetalle->get_result();
        $detalle = [];
        while ($filaDetalle = $resDetalle->fetch_assoc()) {
            $detalle[] = $filaDetalle;
        }
        $stmtDetalle->close();

        $factura = $servicio->generar($recurrente, $detalle, $conexion);
        $facturasId = (int)$factura['facturas_id'];
        $proxima = proximaFechaRecurrente($programada, $recurrente['periodicidad'], $recurrente['dia_mes'] ?? null);
        $estadoNuevo = 1;

        if ($proxima === null || (!empty($recurrente['until_at']) && substr($proxima, 0, 10) > $recurrente['until_at'])) {
            $estadoNuevo = 3;
            $proxima = $programada;
        }

        $stmtActualizar = $conexion->prepare(
            "UPDATE facturas_recurrentes
             SET next_run_at = ?, estado = ?, ultimo_facturas_id = ?,
                 last_run_at = NOW(), ultimo_error = NULL
             WHERE rec_id = ?"
        );
        $stmtActualizar->bind_param('siii', $proxima, $estadoNuevo, $facturasId, $recId);
        if (!$stmtActualizar->execute()) {
            throw new Exception('No se pudo avanzar la recurrencia: '.$stmtActualizar->error);
        }
        $stmtActualizar->close();

        $mensaje = ($factura['es_proforma'] ? 'Proforma ' : 'Factura ')
            .$factura['prefijo'].str_pad((string)$factura['numero'], max(1, $factura['relleno']), '0', STR_PAD_LEFT)
            .' generada correctamente.';
        $stmtOk = $conexion->prepare(
            'UPDATE facturas_recurrentes_ejecuciones SET facturas_id = ?, estado = 1, mensaje = ?, fecha_fin = NOW() WHERE ejecucion_id = ?'
        );
        $stmtOk->bind_param('isi', $facturasId, $mensaje, $ejecucionId);
        $stmtOk->execute();
        $stmtOk->close();

        $conexion->commit();
        $resumen['generadas']++;

        if ($enviarCorreo) {
            try {
                enviarCorreoFacturaRecurrente($facturasId, $empresaId);
                $conexion->query('UPDATE facturas_recurrentes_ejecuciones SET correo_estado = 1 WHERE ejecucion_id = '.(int)$ejecucionId);
                $resumen['correos']++;
            } catch (Throwable $correoError) {
                $stmtCorreo = $conexion->prepare(
                    'UPDATE facturas_recurrentes_ejecuciones SET correo_estado = 2, mensaje = CONCAT(IFNULL(mensaje,\'\'), ?) WHERE ejecucion_id = ?'
                );
                $textoCorreo = ' Correo: '.$correoError->getMessage();
                $stmtCorreo->bind_param('si', $textoCorreo, $ejecucionId);
                $stmtCorreo->execute();
                $stmtCorreo->close();
            }
        }

        $resumen['detalle'][] = ['rec_id' => $recId, 'facturas_id' => $facturasId, 'ok' => true];
    } catch (Throwable $e) {
        $conexion->rollback();
        $resumen['errores']++;
        $error = mb_substr($e->getMessage(), 0, 2000);

        $stmtErrorRec = $conexion->prepare('UPDATE facturas_recurrentes SET ultimo_error = ? WHERE rec_id = ?');
        if ($stmtErrorRec) {
            $stmtErrorRec->bind_param('si', $error, $recId);
            $stmtErrorRec->execute();
            $stmtErrorRec->close();
        }

        $resumen['detalle'][] = ['rec_id' => $recId, 'ok' => false, 'error' => $error];
        error_log('Factura recurrente '.$recId.': '.$error);
    }
}

echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($resumen['errores'] > 0 ? 2 : 0);
