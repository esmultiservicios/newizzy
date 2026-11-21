#!/usr/bin/env bash
# Utilidades de cron IZZY. No ejecutar directamente.
set -euo pipefail
IZZY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
case "$IZZY_ROOT" in
  /home/esmultiservicios/public_html/sistema.izzycloud.app) IZZY_SITE=production; IZZY_URL=https://sistema.izzycloud.app/ ;;
  /home/esmultiservicios/public_html/demo.izzycloud.app) IZZY_SITE=demo; IZZY_URL=https://demo.izzycloud.app/ ;;
  *) echo "ERROR: ruta no autorizada para instalar cron: $IZZY_ROOT" >&2; exit 2 ;;
esac
IZZY_PHP=/opt/cpanel/ea-php82/root/usr/bin/php
[[ -x "$IZZY_PHP" ]] || { echo "ERROR: no existe el PHP CLI original $IZZY_PHP" >&2; exit 2; }
[[ -f "$IZZY_ROOT/core/facturas/procesarFacturasRecurrentes.php" ]] || { echo 'ERROR: falta tarea de facturación original' >&2; exit 2; }
[[ -f "$IZZY_ROOT/scripts/process_jobs.php" ]] || { echo 'ERROR: falta process_jobs.php' >&2; exit 2; }
[[ -f "$IZZY_ROOT/plantilla/plantilla_izzy.sql" ]] || { echo 'ERROR: falta plantilla SQL requerida por el registro' >&2; exit 2; }
