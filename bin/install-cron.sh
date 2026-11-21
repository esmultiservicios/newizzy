#!/usr/bin/env bash
# Instala solamente entradas IZZY faltantes; conserva íntegros los demás cron.
set -euo pipefail
source "$(dirname "$0")/cron-common.sh"
command -v crontab >/dev/null || { echo 'ERROR: crontab no disponible'; exit 2; }
mode="${1:---install}"
[[ "$mode" = --install || "$mode" = --dry-run || "$mode" = --deploy ]] || { echo 'Uso: bash bin/install-cron.sh [--install|--dry-run|--deploy]'; exit 2; }
if [[ "$(id -un)" != esmultiservicios ]]; then
  echo 'ERROR: ejecutar como usuario cPanel esmultiservicios, nunca como root.' >&2; exit 2
fi
umask 077
base="$(mktemp)"; result="$(mktemp)"
trap 'rm -f "$base" "$result"' EXIT
if ! crontab -l >"$base" 2>/dev/null; then
  # No interpretar errores de permisos/lectura como un crontab vacío.
  if crontab -l 2>&1 | grep -Eqi 'no crontab for'; then : >"$base"; else echo 'ERROR: imposible leer crontab actual' >&2; exit 2; fi
fi
cp "$base" "$result"
# Se identifican SOLO por ruta completa y archivo PHP concreto, nunca por nombre genérico.
check_or_add() {
  local name="$1" path="$2" schedule="$3" command="$4"
  local found
  found="$(grep -F "$path" "$base" | grep -vE '^[[:space:]]*#' || true)"
  if [[ -n "$found" ]]; then
    echo "CONSERVADO: $name (ya existe, sin cambiar horario ni comando)"
    echo "$found" | sed 's/^/  /'
    return 0
  fi
  printf '%s %s\n' "$schedule" "$command" >>"$result"
  echo "NUEVO: $name [$schedule]"
}
# El worker también procesa importación de plantilla SQL y notificaciones SOLO de jobs
# pendientes generados por el registro. No ejecutar importaciones durante el deploy.
if [[ "$IZZY_SITE" = production ]]; then
  WORKER_LOG=/home/esmultiservicios/process_jobs.log
else
  WORKER_LOG="$IZZY_ROOT/storage/logs/process_jobs.log"
fi
check_or_add 'jobs registro e importación IZZY' "$IZZY_ROOT/scripts/process_jobs.php" '* * * * *' "APP_URL=$IZZY_URL $IZZY_PHP -q $IZZY_ROOT/scripts/process_jobs.php >> $WORKER_LOG 2>&1"
check_or_add 'facturas recurrentes IZZY' "$IZZY_ROOT/core/facturas/procesarFacturasRecurrentes.php" '*/5 * * * *' "APP_URL=$IZZY_URL $IZZY_PHP -q $IZZY_ROOT/core/facturas/procesarFacturasRecurrentes.php --execute >> $IZZY_ROOT/storage/logs/facturas_recurrentes.log 2>&1"
# Mantenimiento solicitado explícitamente, nuevo cron propio e independiente.
check_or_add 'mantenimiento logs IZZY' "$IZZY_ROOT/bin/rotate-logs.sh" '15 3 * * *' " /bin/bash $IZZY_ROOT/bin/rotate-logs.sh >/dev/null 2>&1"
if cmp -s "$base" "$result"; then echo 'Sin cambios; instalación idempotente.'; exit 0; fi
if [[ "$mode" = --dry-run ]]; then echo 'SIMULACIÓN: no se instaló nada'; exit 0; fi
# Garantizar que el crontab no cambió mientras se calculaba la actualización.
current="$(mktemp)"; trap 'rm -f "$base" "$result" "$current"' EXIT
if ! crontab -l >"$current" 2>/dev/null; then
  if crontab -l 2>&1 | grep -Eqi 'no crontab for'; then : >"$current"; else echo 'ERROR: lectura simultánea fallida' >&2; exit 2; fi
fi
cmp -s "$base" "$current" || { echo 'ERROR: crontab modificado concurrentemente; reintente.' >&2; exit 2; }
crontab "$result"
echo 'Instalado. Revise con: bash bin/verify-cron.sh'
