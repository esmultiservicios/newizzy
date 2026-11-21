#!/usr/bin/env bash
# Verificación no destructiva; nunca ejecuta facturación o jobs.
set -euo pipefail
source "$(dirname "$0")/cron-common.sh"
if [[ "$(id -un)" != esmultiservicios ]]; then echo 'Ejecutar como usuario cPanel esmultiservicios' >&2; exit 2; fi
current="$(crontab -l 2>/dev/null)" || { echo 'ERROR: no se pudo consultar crontab' >&2; exit 2; }
fail=0
check() {
 local name="$1" target="$2" expected="$3" entries count actual
 entries="$(printf '%s\n' "$current" | grep -F "$target" | grep -vE '^[[:space:]]*#' || true)"
 if [[ -z "$entries" ]]; then echo "FALTA: $name"; fail=1; return; fi
 count="$(printf '%s\n' "$entries" | wc -l)"
 if (( count != 1 )); then echo "ATENCIÓN: $name tiene $count entradas"; fail=1; fi
 actual="$(printf '%s\n' "$entries" | head -1 | awk '{print $1" "$2" "$3" "$4" "$5}')"
 if [[ "$actual" != "$expected" ]]; then echo "ATENCIÓN: $name horario $actual (captura: $expected); no se modificó"; fail=1; else echo "OK: $name [$actual]"; fi
}
check 'jobs registro e importación IZZY' "$IZZY_ROOT/scripts/process_jobs.php" '* * * * *'
check 'facturas recurrentes IZZY' "$IZZY_ROOT/core/facturas/procesarFacturasRecurrentes.php" '*/5 * * * *'
check 'mantenimiento logs IZZY' "$IZZY_ROOT/bin/rotate-logs.sh" '15 3 * * *'
echo '--- Última ejecución (evidencia de archivos; no garantiza ejecución exitosa) ---'
for file in /home/esmultiservicios/process_jobs.log "$IZZY_ROOT/storage/logs/process_jobs.log" "$IZZY_ROOT/storage/logs/facturas_recurrentes.log" "$IZZY_ROOT/storage/logs/facturas_recurrentes_demo.log"; do
 if [[ "$file" = /home/esmultiservicios/process_jobs.log && "$IZZY_SITE" != production ]]; then continue; fi
 if [[ "$file" = "$IZZY_ROOT/storage/logs/process_jobs.log" && "$IZZY_SITE" = production ]]; then continue; fi
 if [[ -f "$file" ]]; then stat -c '%y %s bytes %n' "$file"; else echo "SIN ARCHIVO: $file"; fi
done
echo 'Para validar funcionamiento revise contenido/errores y evidencias de jobs/facturas en la aplicación.'
exit "$fail"
