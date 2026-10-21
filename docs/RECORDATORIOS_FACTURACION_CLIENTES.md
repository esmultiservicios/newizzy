# IZZY | Recordatorios de facturación para clientes

## Alcance

Se conserva el proyecto íntegro, incluida la recuperación de ciclos retenidos por morosidad, exclusiones manuales y salida habilitada en modo consulta. Esta entrega incorpora avisos de solo lectura para la facturación **de la propia cuenta IZZY**, no las cuentas por cobrar de sus clientes comerciales.

## Secuencia de avisos

- **Día 1:** si existe un registro pendiente del mes actual, campana y aviso «Factura del mes disponible». No se afirma que el cron emitió una factura si no existe deuda registrada.
- **Día 5:** aviso de pago pendiente mientras continúe la deuda.
- **Día 10:** segundo recordatorio.
- **Día 15:** último día del plazo.
- **Día 16 en adelante:** aviso de pago vencido; el bloqueo de escritura existente no se modifica.
- **Saldo pagado:** al refrescar la consulta, desaparece el aviso; la campana puede continuar si el contador de facturas interno existente señala documentos pendientes.

Los avisos se consultan al cargar la página y cada 5 minutos, usando el mecanismo ya existente. La notificación emergente se presenta como máximo una vez por etapa y período en la sesión de cada pestaña; el aviso en la campana persiste mientras exista deuda. El menú del usuario permite entrar a **Detalles de Facturación**.

## Archivos cambiados

- `core/facturas/contarFacturasPendientesClientes.php`: estado de pago y etapa por fecha desde DB_MAIN.
- `ajax/js/main.php`: integración de avisos en la campana, contador y notificación, sin alterar permisos.
- `vistas/plantilla/modulos/navbartop.php`: texto de aviso y acceso directo.

## Importante antes de producción

Probar con las cuentas de desarrollo: factura pendiente del mes, factura del mes anterior, pago registrado, cuentas exentas, campana sin deuda, modo consulta y salida. La emisión y envío por correo los sigue gestionando el cron existente. Los avisos son **dentro del sistema**, no correos programados. Como usan la tabla `cobrar_clientes`, la fecha mostrada depende de los registros contables de DB_MAIN; el servidor debe tener sincronizada su zona horaria. No se realizaron pruebas con una base MySQL real.
