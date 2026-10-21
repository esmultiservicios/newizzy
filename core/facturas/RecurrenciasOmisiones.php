<?php
/**
 * Control de omisiones por ciclo de facturación recurrente.
 * Se almacena por fecha PROGRAMADA, no por fecha de emisión.
 * Esto permite excluir meses/días sin cancelar el resto de la recurrencia.
 */

function izzyRecurrenciasAsegurarOmisiones(mysqli $cn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS facturas_recurrentes_omisiones (
        omision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        rec_id INT NOT NULL,
        scheduled_at DATETIME NOT NULL,
        motivo VARCHAR(24) NOT NULL DEFAULT 'manual',
        observacion VARCHAR(255) NULL,
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (omision_id),
        UNIQUE KEY uq_recurrencia_ciclo (rec_id, scheduled_at),
        KEY ix_fecha (scheduled_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$cn->query($sql)) {
        throw new RuntimeException('No se pudo preparar el registro de omisiones: '.$cn->error);
    }
}

function izzyRecurrenciasProximaFecha(string $fecha, string $periodicidad, ?int $diaMes = null): ?string
{
    $actual = new DateTimeImmutable($fecha, new DateTimeZone('America/Tegucigalpa'));
    if ($periodicidad === 'once') return null;
    if ($periodicidad === 'daily') return $actual->modify('+1 day')->format('Y-m-d H:i:s');
    if ($periodicidad === 'weekly') return $actual->modify('+7 days')->format('Y-m-d H:i:s');
    if ($periodicidad !== 'monthly') throw new RuntimeException('Periodicidad desconocida.');
    $siguiente = $actual->modify('first day of next month');
    $dia = min($diaMes ?: (int)$actual->format('d'), (int)$siguiente->format('t'));
    return $siguiente->setDate((int)$siguiente->format('Y'), (int)$siguiente->format('m'), $dia)->format('Y-m-d H:i:s');
}

/** Verifica que una fecha sea un ciclo real, sin admitir fechas arbitrarias. */
function izzyRecurrenciasResolverCiclo(array $rec, string $periodo): ?string
{
    $frecuencia = (string)$rec['periodicidad'];
    $fecha = (string)$rec['start_at'];
    $inicio = new DateTimeImmutable($fecha);
    if ($frecuencia === 'monthly' && preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', $periodo)) {
        $mesDestino = $periodo;
        $objetivo = null;
    } elseif (preg_match('/^20\d{2}-(0[1-9]|1[0-2])-[0-3]\d$/', $periodo)) {
        $mesDestino = null;
        $objetivo = $periodo;
        if (!checkdate((int)substr($periodo,5,2), (int)substr($periodo,8,2), (int)substr($periodo,0,4))) return null;
    } else {
        return null;
    }
    // Permite saltar la primera ejecución solo si no está dentro de la
    // recurrencia vigente. El usuario no puede crear omisiones históricas.
    $actual = (string)$rec['next_run_at'];
    $limite = (new DateTimeImmutable('now'))->modify('+5 years')->format('Y-m-d H:i:s');
    for ($i = 0; $i < 1900 && $actual !== null && $actual <= $limite; $i++) {
        if ($mesDestino !== null ? substr($actual, 0, 7) === $mesDestino : substr($actual, 0, 10) === $objetivo) {
            if (!empty($rec['until_at']) && substr($actual,0,10) > $rec['until_at']) return null;
            return $actual;
        }
        if (($mesDestino !== null && substr($actual,0,7) > $mesDestino) || ($objetivo !== null && substr($actual,0,10) > $objetivo)) return null;
        $actual = izzyRecurrenciasProximaFecha($actual, $frecuencia, isset($rec['dia_mes']) ? (int)$rec['dia_mes'] : (int)$inicio->format('d'));
    }
    return null;
}
