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

// Centralizar los logs del proceso dentro del proyecto y crear la ruta si no existe.
$logDir = $raiz.'/storage/logs';
if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
    fwrite(STDERR, 'No se pudo crear el directorio de logs: '.$logDir.PHP_EOL);
    exit(1);
}

$esDemoCron = (
    $appHost === 'demo.izzycloud.app' ||
    str_starts_with(strtolower($appHost), 'demo.')
);
$logFile = $logDir.'/'.($esDemoCron ? 'facturas_recurrentes_demo.log' : 'facturas_recurrentes.log');

ini_set('log_errors', '1');
ini_set('error_log', $logFile);

function izzyFacturaRecurrenteLog(string $mensaje): void
{
    error_log('[Factura recurrente] '.$mensaje);
}


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

// Protección: el cron normal usa --execute. Sin esa bandera, solo consulta.
$ejecutar = in_array('--execute', $argv ?? [], true);
$soloDb = null;
$recuperarMes = null;
$aceptarCiclosAnteriores = in_array('--confirmar-ciclos-previos', $argv ?? [], true);
$maxPorBase = 0; // Sin límite artificial de facturas.


foreach (($argv ?? []) as $argumento) {
    if (str_starts_with($argumento, '--recuperar-mes=')) {
        $recuperarMes = substr($argumento, strlen('--recuperar-mes='));
        if (!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/', $recuperarMes)) {
            fwrite(STDERR, "Mes de recuperación inválido. Use AAAA-MM.\n");
            exit(1);
        }
    }

    if (str_starts_with($argumento, '--db=')) {
        $soloDb = substr($argumento, 5);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $soloDb)) {
            fwrite(STDERR, "Nombre de base de datos inválido.\n");
            exit(1);
        }
    }
}

if ($recuperarMes !== null && (!$aceptarCiclosAnteriores || $soloDb === null)) {
    fwrite(STDERR, "La recuperación histórica requiere --db=BASE y --confirmar-ciclos-previos. Revise antes los documentos manuales y períodos anteriores.\n");
    exit(1);
}
if ($recuperarMes !== null && !$ejecutar) {
    // Se permite simular la selección histórica sin confirmar la emisión.
}
$lock = fopen($logDir.'/facturas_recurrentes.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Otro procesamiento de facturas recurrentes está activo.\n");
    exit(1);
}

$mainModelCron = new mainModel();
$GLOBALS['db'] = DB_MAIN;
$conexionPrincipal = $mainModelCron->connection();
// Solo IZZY (sistema_id=1) y clientes activos (estado=1).
// CAMI, registros inactivos y bases no existentes nunca se procesan.
$baseDatos = [DB_MAIN];
$clientes = $conexionPrincipal->query(
    "SELECT DISTINCT sc.db
     FROM server_customers AS sc
     INNER JOIN information_schema.SCHEMATA AS sch ON sch.SCHEMA_NAME = sc.db
     WHERE sc.estado = 1 AND sc.sistema_id = 1
       AND sc.db IS NOT NULL AND sc.db <> ''"
);
if ($clientes === false) {
    fwrite(STDERR, 'No se pudieron enumerar las bases IZZY activas: '.$conexionPrincipal->error.PHP_EOL);
    exit(1);
}
while ($cliente = $clientes->fetch_assoc()) {
    $nombre = (string)$cliente['db'];
    if (preg_match('/^[a-zA-Z0-9_]+$/', $nombre)) {
        $baseDatos[] = $nombre;
    }
}
$clientes->free();
$baseDatos = array_values(array_unique($baseDatos));
if ($soloDb !== null) {
    if (!in_array($soloDb, $baseDatos, true)) {
        fwrite(STDERR, "La base solicitada no pertenece a las bases activas registradas.\n");
        exit(1);
    }
    $baseDatos = [$soloDb];
}
$conexionPrincipal->close();
$servicio = new FacturaRecurrenteServicio();
$resumen = ['modo' => $ejecutar ? 'EJECUCION' : 'SIMULACION', 'generadas' => 0, 'errores' => 0, 'revision' => 0, 'correos' => 0, 'bases' => []];
$hoy = new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa'));

/**
 * El generador heredado usa otras conexiones. Se confirma una reserva durable
 * ANTES de invocarlo. Un fallo ambiguo no se reintenta automáticamente.
 */
foreach ($baseDatos as $nombreDb) {
    $GLOBALS['db'] = $nombreDb;
    $contadores = ['pendientes' => 0, 'generadas' => 0, 'errores' => 0, 'revision' => 0, 'recuperadas' => 0, 'bloqueadas_secuencia' => 0];
    $resumen['bases'][$nombreDb] = $contadores;
    $cn = null;
    try {
        $cn = $mainModelCron->connection();
        if (!$cn) {
            throw new RuntimeException('Conexión de empresa no disponible.');
        }
        $tablas = $cn->query("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('facturas_recurrentes','facturas_recurrentes_detalle','facturas_recurrentes_ejecuciones')");
        if (!$tablas || (int)$tablas->fetch_assoc()['total'] !== 3) {
            izzyFacturaRecurrenteLog('Base '.$nombreDb.': tablas de recurrencias incompletas.');
            $contadores['revision']++;
            $resumen['revision']++;
            continue;
        }
        $consulta = $cn->query("SELECT rec_id, next_run_at FROM facturas_recurrentes WHERE estado = 1 AND next_run_at <= NOW() AND (until_at IS NULL OR DATE(next_run_at) <= until_at) ORDER BY next_run_at ASC, rec_id ASC");
        if (!$consulta) {
            throw new RuntimeException('No se pudieron consultar recurrencias: '.$cn->error);
        }
        $pendientes = [];
        while ($fila = $consulta->fetch_assoc()) {
            $pendientes[] = $fila;
        }
        $consulta->free();
        $contadores['pendientes'] = count($pendientes);
        if (!$ejecutar) {
            continue;
        }
        foreach ($pendientes as $pendiente) {
            $recId = (int)$pendiente['rec_id'];
            $programadaOriginal = (string)$pendiente['next_run_at'];
            $programada = $programadaOriginal;
            if ($recuperarMes !== null) {
                // Proyectar el ciclo solicitado sin facturar automáticamente meses previos.
                $info = $cn->prepare('SELECT periodicidad, dia_mes, until_at FROM facturas_recurrentes WHERE rec_id = ?');
                $info->bind_param('i', $recId);
                $info->execute();
                $regla = $info->get_result()->fetch_assoc();
                $info->close();
                if (!$regla) { continue; }
                $iteraciones = 0;
                while (substr($programada, 0, 7) < $recuperarMes && $iteraciones++ < 1200) {
                    $siguiente = proximaFechaRecurrente($programada, $regla['periodicidad'], $regla['dia_mes']);
                    if ($siguiente === null || $siguiente <= $programada) { break; }
                    $programada = $siguiente;
                }
                if (substr($programada, 0, 7) !== $recuperarMes ||
                    $programada > date('Y-m-d H:i:s') ||
                    (!empty($regla['until_at']) && substr($programada, 0, 10) > $regla['until_at'])) {
                    continue;
                }
            }
            $empresaId = 0;
            $reservaConfirmada = false;
            $ejecucionId = 0;
            try {
                $fechaProgramada = new DateTimeImmutable($programada, new DateTimeZone('America/Tegucigalpa'));
                // Ventana definida: fecha programada y día siguiente. Las más antiguas
                // requieren autorización y conciliación manual, sin saltar ciclos.
                // Las recurrencias retenidas por falta de autorización fiscal
                // siguen pendientes y se reintentan al día siguiente tras renovar.
                $consultaRetenida = $cn->prepare('SELECT ultimo_error FROM facturas_recurrentes WHERE rec_id = ?');
                $consultaRetenida->bind_param('i', $recId);
                $consultaRetenida->execute();
                $datoRetenido = $consultaRetenida->get_result()->fetch_assoc();
                $consultaRetenida->close();
                $esRetenida = str_starts_with((string)($datoRetenido['ultimo_error'] ?? ''), '[SECUENCIA_PENDIENTE]');
                if ($recuperarMes === null && !$esRetenida && $fechaProgramada->format('Y-m-d') < $hoy->modify('-1 day')->format('Y-m-d')) {
                    $contadores['revision']++;
                    $resumen['revision']++;
                    continue;
                }
                // No crear historial de ejecución ni reservar correlativo
                // mientras falte numeración fiscal vigente.
                $datosPrevia = $cn->prepare('SELECT empresa_id, tipo_documento, ultimo_error FROM facturas_recurrentes WHERE rec_id = ? AND estado = 1');
                $datosPrevia->bind_param('i', $recId);
                $datosPrevia->execute();
                $pre = $datosPrevia->get_result()->fetch_assoc();
                $datosPrevia->close();
                if (!$pre) { continue; }
                $fechaHoyFiscal = (new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa')))->format('Y-m-d');
                $disponible = $servicio->verificarSecuenciaRecurrente($cn, (int)$pre['empresa_id'], (int)$pre['tipo_documento'] === 1 ? 4 : 1, $fechaHoyFiscal);
                if (!$disponible['disponible']) {
                    $marcaAnterior = (string)($pre['ultimo_error'] ?? '');
                    $marcaNueva = '[SECUENCIA_PENDIENTE] '.$fechaHoyFiscal.' | '.$disponible['motivo'];
                    // Un único aviso por día y recurrencia, no uno cada cinco minutos.
                    if ($marcaAnterior !== $marcaNueva) {
                        $marcaStmt = $cn->prepare('UPDATE facturas_recurrentes SET ultimo_error = ? WHERE rec_id = ? AND next_run_at = ?');
                        $marcaStmt->bind_param('sis', $marcaNueva, $recId, $programadaOriginal);
                        $marcaStmt->execute();
                        $marcaStmt->close();
                        $contadores['bloqueadas_secuencia']++;
                    }
                    continue;
                }
                $cn->begin_transaction();
                $stmt = $cn->prepare('SELECT * FROM facturas_recurrentes WHERE rec_id = ? AND estado = 1 FOR UPDATE');
                $stmt->bind_param('i', $recId);
                $stmt->execute();
                $rec = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$rec || (string)$rec['next_run_at'] !== $programadaOriginal) {
                    $cn->rollback();
                    continue;
                }
                $empresaId = (int)$rec['empresa_id'];
                // El ciclo proyectado se usa solo para identificar la emisión y su auditoría.
                $rec['next_run_at'] = $programada;
                if ($recuperarMes !== null) {
                    izzyFacturaRecurrenteLog('Recuperación autorizada: base '.$nombreDb.', rec '.$recId.', ciclo '.$programada.', desde '.$programadaOriginal);
                }
                $stmt = $cn->prepare('SELECT ejecucion_id, estado, facturas_id FROM facturas_recurrentes_ejecuciones WHERE rec_id = ? AND scheduled_at = ? FOR UPDATE');
                $stmt->bind_param('is', $recId, $programada);
                $stmt->execute();
                $anterior = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($anterior) {
                    // Una ejecución anterior (incluso fallida) puede haber creado
                    // un documento en otra conexión. Nunca repetir a ciegas.
                    $cn->rollback();
                    $contadores['revision']++;
                    $resumen['revision']++;
                    continue;
                }
                // La idempotencia se controla mediante la reserva persistente y la
                // clave única (rec_id, scheduled_at) de facturas_recurrentes_ejecuciones.
                // No insertar marcas técnicas en las notas visibles de las facturas.
                // Si se pierde el historial, conciliar manualmente antes de reintentar.
                $stmt = $cn->prepare('INSERT INTO facturas_recurrentes_ejecuciones (rec_id, empresa_id, scheduled_at, estado, correo_estado, mensaje, fecha_inicio) VALUES (?, ?, ?, 0, 0, ?, NOW())');
                $mensajeReserva = 'Reservada; emisión en curso. No reintentar automáticamente sin conciliación.';
                $stmt->bind_param('iiss', $recId, $empresaId, $programada, $mensajeReserva);
                if (!$stmt->execute()) {
                    throw new RuntimeException('No se pudo registrar la reserva: '.$stmt->error);
                }
                $ejecucionId = (int)$stmt->insert_id;
                $stmt->close();
                if (!$cn->commit()) {
                    throw new RuntimeException('No se pudo confirmar la reserva.');
                }
                $reservaConfirmada = true;

                $stmt = $cn->prepare('SELECT * FROM facturas_recurrentes_detalle WHERE rec_id = ? ORDER BY rec_detalle_id ASC');
                $stmt->bind_param('i', $recId);
                $stmt->execute();
                $rs = $stmt->get_result();
                $detalle = [];
                while ($fila = $rs->fetch_assoc()) {
                    $detalle[] = $fila;
                }
                $stmt->close();
                // Fecha única de emisión: la del momento real de generar el documento.
                // La fecha programada permanece en scheduled_at para auditoría.
                $instanteEmision = new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa'));
                $rec['fecha_emision'] = $instanteEmision->format('Y-m-d');
                $rec['fecha_registro_emision'] = $instanteEmision->format('Y-m-d H:i:s');
                $rec['fecha_dolar'] = $rec['fecha_emision'];
                $factura = $servicio->generar($rec, $detalle, $cn);
                $facturasId = (int)$factura['facturas_id'];
                if ($facturasId <= 0) {
                    throw new RuntimeException('Generador sin identificador de documento.');
                }
                $proxima = proximaFechaRecurrente($programada, $rec['periodicidad'], $rec['dia_mes'] ?? null);
                $estado = 1;
                if ($proxima === null || (!empty($rec['until_at']) && substr($proxima, 0, 10) > $rec['until_at'])) {
                    $estado = 3;
                    $proxima = $programada;
                }
                $cn->begin_transaction();
                $stmt = $cn->prepare('UPDATE facturas_recurrentes SET next_run_at = ?, estado = ?, ultimo_facturas_id = ?, fecha_dolar = ?, last_run_at = NOW(), ultimo_error = NULL WHERE rec_id = ? AND next_run_at = ?');
                $stmt->bind_param('siisis', $proxima, $estado, $facturasId, $rec['fecha_dolar'], $recId, $programadaOriginal);
                if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                    throw new RuntimeException('No se pudo avanzar la recurrencia.');
                }
                $stmt->close();
                $mensaje = 'Documento generado correctamente: ID '.$facturasId;
                $stmt = $cn->prepare('UPDATE facturas_recurrentes_ejecuciones SET estado = 1, facturas_id = ?, mensaje = ?, fecha_fin = NOW() WHERE ejecucion_id = ?');
                $stmt->bind_param('isi', $facturasId, $mensaje, $ejecucionId);
                if (!$stmt->execute()) {
                    throw new RuntimeException('No se pudo cerrar el historial de emisión.');
                }
                $stmt->close();
                if (!$cn->commit()) {
                    throw new RuntimeException('No se pudo confirmar el historial.');
                }
                $contadores['generadas']++;
                $resumen['generadas']++;
                if ($recuperarMes !== null) {
                    $contadores['recuperadas']++;
                }
                if ((int)$rec['enviar_correo'] === 1) {
                    try {
                        $correo = enviarCorreoFacturaRecurrente($facturasId, $empresaId, false);
                        $estadoCorreo = !empty($correo['cliente_enviado']) ? 1 : 2;
                        $stmt = $cn->prepare('UPDATE facturas_recurrentes_ejecuciones SET correo_estado = ? WHERE ejecucion_id = ?');
                        $stmt->bind_param('ii', $estadoCorreo, $ejecucionId);
                        $stmt->execute();
                        $stmt->close();
                        if ($estadoCorreo === 1) {
                            $resumen['correos']++;
                        }
                    } catch (Throwable $correoError) {
                        izzyFacturaRecurrenteLog('Correo base '.$nombreDb.', rec '.$recId.': '.$correoError->getMessage());
                        $stmt = $cn->prepare('UPDATE facturas_recurrentes_ejecuciones SET correo_estado = 2 WHERE ejecucion_id = ?');
                        $stmt->bind_param('i', $ejecucionId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            } catch (Throwable $error) {
                try { $cn->rollback(); } catch (Throwable $ignorado) {}
                $contadores['errores']++;
                $resumen['errores']++;
                $detalleError = mb_substr($error->getMessage(), 0, 1200);
                // Si la secuencia cambió entre prevalidación y emisión, conservar
                // pendiente; una reserva ambigua exige revisión antes de reintentar.
                if ($error instanceof SecuenciaFiscalNoDisponibleException && !$reservaConfirmada) {
                    $contadores['errores']--;
                    $resumen['errores']--;
                    $contadores['bloqueadas_secuencia']++;
                    continue;
                }
                izzyFacturaRecurrenteLog('Base '.$nombreDb.', rec '.$recId.': '.$detalleError);
                if ($reservaConfirmada && $ejecucionId > 0) {
                    try {
                        $stmt = $cn->prepare('UPDATE facturas_recurrentes_ejecuciones SET estado = 2, mensaje = ?, fecha_fin = NOW() WHERE ejecucion_id = ?');
                        $stmt->bind_param('si', $detalleError, $ejecucionId);
                        $stmt->execute();
                        $stmt->close();
                        $stmt = $cn->prepare('UPDATE facturas_recurrentes SET ultimo_error = ? WHERE rec_id = ?');
                        $stmt->bind_param('si', $detalleError, $recId);
                        $stmt->execute();
                        $stmt->close();
                    } catch (Throwable $registroError) {
                        izzyFacturaRecurrenteLog('No se pudo persistir el error: '.$registroError->getMessage());
                    }
                }
            }
        }
        // Resumen consolidado por empresa, sin correo por cada documento.
        if (($contadores['generadas'] + $contadores['errores'] + $contadores['bloqueadas_secuencia']) > 0) {
            $empresas = $cn->query('SELECT empresa_id FROM empresa');
            if ($empresas) {
                while ($empresa = $empresas->fetch_assoc()) {
                    try {
                        enviarResumenRecurrente((int)$empresa['empresa_id'], $contadores);
                    } catch (Throwable $errorAviso) {
                        izzyFacturaRecurrenteLog('Aviso base '.$nombreDb.': '.$errorAviso->getMessage());
                    }
                }
                $empresas->free();
            }
        }
    } catch (Throwable $errorBase) {
        $contadores['errores']++;
        $resumen['errores']++;
        izzyFacturaRecurrenteLog('Base '.$nombreDb.': '.$errorBase->getMessage());
    } finally {
        if ($cn) { $cn->close(); }
        unset($resumen['bases'][$nombreDb]);
        $resumen['bases'][$nombreDb] = $contadores;
        unset($contadores);
    }
}
$GLOBALS['db'] = DB_MAIN;
flock($lock, LOCK_UN);
fclose($lock);
echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($resumen['errores'] > 0 ? 2 : 0);
