#!/usr/bin/env bash
# Solo rota logs expresamente pertenecientes a IZZY; no elimina datos de DB.
set -euo pipefail
source "$(dirname "$0")/cron-common.sh"
umask 077
LOG_DIR="$IZZY_ROOT/storage/logs"
[[ -d "$LOG_DIR" ]] || exit 0
# Bloqueo no invasivo, evita mantenimientos simultáneos.
exec 9>"$LOG_DIR/.izzy-log-maintenance.lock"
flock -n 9 || exit 0
# Rota únicamente logs propios si sobrepasan 10 MiB; conserva ~30 días de históricos.
rotate() {
 local file="$1" sz today dest
 [[ -f "$file" && ! -L "$file" ]] || return 0
 sz="$(stat -c %s "$file")"
 (( sz >= 10485760 )) || return 0
 today="$(date +%Y%m%d-%H%M%S)-$$"
 dest="$file.$today"
 mv -- "$file" "$dest"
 : >"$file"
 chmod 600 "$file"
 gzip -n -- "$dest"
}
# Ficheros actualmente utilizados, con nombres separados por instancia.
rotate "$LOG_DIR/facturas_recurrentes.log"
rotate "$LOG_DIR/facturas_recurrentes_demo.log"
if [[ "$IZZY_SITE" = demo ]]; then rotate "$LOG_DIR/process_jobs.log"; fi
# Rotación externa del procesador de registros solo en producción.
if [[ "$IZZY_SITE" = production ]]; then rotate '/home/esmultiservicios/process_jobs.log'; fi
# Eliminar exclusivamente archivos de rotación creados por este mecanismo
# con fecha en el nombre, mayores de 30 días; nunca archivos .log activos.
for name in facturas_recurrentes.log facturas_recurrentes_demo.log; do
 find "$LOG_DIR" -maxdepth 1 -type f -name "$name.????????-??????-*.gz" -mtime +30 -delete
done
if [[ "$IZZY_SITE" = demo ]]; then
 find "$LOG_DIR" -maxdepth 1 -type f -name 'process_jobs.log.????????-??????-*.gz' -mtime +30 -delete
fi
if [[ "$IZZY_SITE" = production ]]; then
 find /home/esmultiservicios -maxdepth 1 -type f -name 'process_jobs.log.????????-??????-*.gz' -mtime +30 -delete
fi
