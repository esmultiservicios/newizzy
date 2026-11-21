# IZZY — cron, altas e importación de bases y logs (v002)

## Evidencia y alcance
Captura cPanel 21/11/2026, usuario `esmultiservicios`:

- **Producción** (`/home/esmultiservicios/public_html/sistema.izzycloud.app`): `* * * * *` para `scripts/process_jobs.php`, con `APP_URL=https://sistema.izzycloud.app/`, intérprete `/opt/cpanel/ea-php82/root/usr/bin/php -q`, salida a `/home/esmultiservicios/process_jobs.log`.
- **Producción**: `*/5 * * * *` para `core/facturas/procesarFacturasRecurrentes.php --execute`, `APP_URL=https://sistema.izzycloud.app/`, salida a `storage/logs/facturas_recurrentes.log`.
- **Demo** (`/home/esmultiservicios/public_html/demo.izzycloud.app`): `*/5 * * * *` para `core/facturas/procesarFacturasRecurrentes.php --execute`, `APP_URL=https://demo.izzycloud.app/`, salida a `storage/logs/facturas_recurrentes.log`.
- **No IZZY**: `service-monitor.php` de ZYNKO. Jamás se altera.

**Novedad v002 — worker del registro en demo:** `scripts/process_jobs.php` ya existe en el proyecto original y procesa la cola de registros: trabajos `db_import` que llaman a `core/registro/DatabaseSetup.php` para importar `plantilla/plantilla_izzy.sql` y trabajos `registration_email`/`access_email` para notificaciones. La captura de cPanel solo acredita el cron de **producción** cada minuto; a petición del usuario se registra ahora un cron equivalente **nuevo para demo**, cada minuto, con log independiente `storage/logs/process_jobs.log`. **Esto es una nueva tarea de demo, no una tarea preexistente demostrada por la captura.** No se crea ni importa ninguna base de datos al desplegar: solo se procesan jobs pendientes por el cron.

El código de facturación además escribe en `storage/logs/facturas_recurrentes_demo.log` para demo. Se mantienen ambos nombres: **no se cambia el PHP existente**.

## Ejecutar `.cpanel.yml` desde cPanel (Git Version Control)

**No se ejecuta el YAML mediante `bash` o `php`.** cPanel lee `.cpanel.yml` al usar la opción **Deploy HEAD Commit** en **cPanel → Git Version Control → Manage → Pull or Deploy** del repositorio de IZZY demo. Primero confirmar que el checkout desplegado es el de demo y que existe `bin/` dentro del Document Root. El YAML ejecuta en orden:

1. `export DEPLOYPATH=/home/esmultiservicios/public_html/demo.izzycloud.app/` (define la ruta de demo; no copia archivos).
2. `test -d "$DEPLOYPATH"` y `test -f "$DEPLOYPATH/index.php"` (verificaciones obligatorias; detienen el despliegue si faltan rutas).
3. `bash "$DEPLOYPATH/bin/install-cron.sh" --deploy` (instala solo cron faltantes de **demo**: proceso de registros/importación, facturación y mantenimiento de logs; conserva todas las entradas existentes).
4. `bash "$DEPLOYPATH/bin/verify-cron.sh"` (comprueba horarios, tareas presentes, duplicaciones y fechas de logs).

**Importante sobre la importación SQL:** no se debe ejecutar `DatabaseSetup::importDatabase()` en cada Deploy. La importación se realiza para un cliente nuevo al procesar su job `db_import`, después de que el flujo de registro lo haya creado en la cola. El script `scripts/process_jobs.php` maneja los trabajos en su ejecución por cron cada minuto; también gestiona las notificaciones. El proceso real de alta y envío debe probarse en una fase funcional separada. Si se ejecuta manualmente `process_jobs.php`, procesará la cola real y puede crear/modificar bases y enviar correos: no hacerlo como prueba no destructiva.

**Cron demo nuevo:** `* * * * * APP_URL=https://demo.izzycloud.app/ /opt/cpanel/ea-php82/root/usr/bin/php -q /home/esmultiservicios/public_html/demo.izzycloud.app/scripts/process_jobs.php >> /home/esmultiservicios/public_html/demo.izzycloud.app/storage/logs/process_jobs.log 2>&1`. No se modifica el cron de producción.

## Instalación / restauración

1. Desplegar los archivos de este paquete en **cada instalación correspondiente** (producción y demo), conservando la estructura del proyecto. No copiar una instancia sobre otra ni sobrescribir configuraciones.
2. Entrar como usuario cPanel `esmultiservicios`, no `root` (SSH o terminal cPanel).
3. Desde la raíz de cada instalación, ejecutar `bash bin/install-cron.sh --dry-run` para previsualizar. Luego `bash bin/install-cron.sh --install`.
4. Ejecutar `bash bin/verify-cron.sh` y examinar los logs. Comprobar las ejecuciones reales y errores luego de su primera hora programada.

**Integración `.cpanel.yml`:** después de comprobar las rutas originales del sitio demo, invoca la instalación con `--deploy` al usar Git Deployment de cPanel. Al no poder leer el crontab existente, el script falla sin reemplazarlo. El archivo `.cpanel.yml` de demo **no despliega producción**. En producción, si se usa un repositorio distinto, aplique el paquete y adapte su propio `.cpanel.yml` con el mismo paso, después de verificar sus tareas actuales.

## Política de protección

- No altera las entradas ya existentes de IZZY: si hay un comando con la ruta PHP de IZZY, lo conserva intacto, aun si difiere de la captura (verificador avisa). No borra ninguna línea de crontab; agrega solamente ausentes.
- Nuevo mantenimiento: `15 3 * * *` por sitio, gestionado por `bin/rotate-logs.sh`.
- El mantenimiento rota **solo** `storage/logs/facturas_recurrentes.log`, `storage/logs/facturas_recurrentes_demo.log` y el log del worker (`storage/logs/process_jobs.log` en demo; `/home/esmultiservicios/process_jobs.log` en producción) cuando superan **10 MiB**; elimina **solo** rotaciones `.gz` de estos ficheros con más de **30 días**. No toca otros `.log`, `.lock`, registros SQL, archivos de clientes, copias de seguridad ni logs de otros proyectos.
- Eliminar cron con el panel cPanel **solo si ha sido decidido manualmente**. El instalador no elimina nada y no convierte entradas activas a otro comando.
- Antes de migrar a AlmaLinux 10 comprobar que sigue existiendo **PHP EA 8.2** en la ruta original y que `crontab`, `flock`, `gzip`, `stat` y `find` están disponibles. **No se garantiza compatibilidad** con versiones futuras sin probarlas. No sustituir el intérprete sin validar la aplicación.

## Comprobaciones y límites

- `verify-cron.sh` valida presencia, duplicados y calendario; aporta fechas de modificación de logs, pero no puede demostrar por sí solo que la tarea completó correctamente.
- `process_jobs.php` interviene en la importación SQL de clientes, el alta y los correos. v002 integra su ejecución programada en demo sin modificar su código. Este cambio no verifica de extremo a extremo la creación del plan gratis, el registro ni el envío: eso requiere una fase funcional independiente.
- El snapshot de VM y backups externos siguen siendo necesarios. Los registros activos **no** se borran automáticamente por antigüedad; se rotan solo por tamaño.
