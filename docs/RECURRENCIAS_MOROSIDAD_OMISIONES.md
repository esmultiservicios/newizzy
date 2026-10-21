# Reglas de facturación recurrente y pago pendiente de IZZY

## 1. Clientes IZZY con deuda

El cron consulta **DB_MAIN**, usando la misma condición de vencimiento que `modelos/loginModel.php`: deuda en `cobrar_clientes` con `estado = 1`, de meses anteriores o del mes actual desde el día 16. Respeta la excepción `server_customers.validar = 2`. **No confundir esa deuda de suscripción con las cuentas por cobrar de cada empresa.**

Cuando se cumple la condición, el cron **retiene el ciclo sin avanzar `next_run_at`**. No genera factura, no envía correo ni consume numeración fiscal. Deja una marca `[MOROSIDAD_PENDIENTE]` en `ultimo_error` para la recuperación posterior.

Cuando DB_MAIN confirme que el cliente está al día, el cron genera los ciclos retenidos utilizando la fecha actual como fecha de emisión y conserva la fecha programada en el historial. Después avanza progresivamente hasta ponerse al día. Si hay varios ciclos atrasados, puede necesitarlos procesar en varias ejecuciones del cron. El registro histórico permite evitar duplicados.

**Compatibilidad:** las versiones anteriores que ya avanzaron los ciclos por morosidad dejaron registros `motivo=morosidad` en `facturas_recurrentes_omisiones`. El proceso nuevo recupera hasta 24 por base y ejecución, únicamente si no existe historial de emisión/reserva para el período; no mueve hacia atrás el calendario actual. No se recuperan automáticamente los ciclos con reservas ambiguas, que requieren conciliación.

## 2. Períodos excluidos manualmente

En el modal de Facturas recurrentes, botón **Excluir períodos** de una recurrencia activa. En frecuencia mensual se elige mes; diaria/semanal/única se elige fecha. El servidor comprueba que exista un ciclo futuro real y almacena su fecha programada. Puede restaurarse antes del vencimiento. Al llegar, el cron lo consume sin emitir ni enviar. No se altera una factura ya emitida.

La tabla `facturas_recurrentes_omisiones` se crea bajo demanda en cada base de cliente mediante `CREATE TABLE IF NOT EXISTS`. Si el usuario SQL no tiene `CREATE TABLE`, usar la migración SQL documentada abajo antes de activar el cron; no se emitirán facturas cuando falle esa verificación.

## 3. Cierre de sesión

`ajax/js/main.php` libera los botones del cuadro de confirmación **exclusivamente mientras está abierto el cierre de sesión**, conservando las restricciones para mutaciones de negocio y la excepción de DetallesFacturacion.

## 4. Pruebas previas a producción

1. Verificar en DB_MAIN una empresa morosa y otra al día. Probar la simulación del cron con `--db=...` (sin `--execute`).
2. Programar una omisión mensual futura y comprobar que aparece en el listado, y que Restaurar la retira.
3. Probar en entorno controlado cron con cuenta morosa, sin generar documento/correo y con próximo ciclo avanzado.
4. Probar pago regularizado: se emiten los ciclos retenidos (uno por período) con fecha de emisión actual; confirmar que al ejecutar el cron otra vez no se duplican. Verificar también que la recuperación de omisiones antiguas respete las reservas y emisiones existentes.
5. Comprobar cuenta en modo consulta: navegación disponible, acciones bloqueadas, DetallesFacturacion habilitado y Salir -> Confirmar/Cancelar funcional.
6. Revisar que nuevos intentos no dupliquen facturas ya emitidas.

**Importante:** no activar cambios de cron en producción sin respaldo y revisión de estas pruebas. No ejecutar recuperación histórica a ciegas.

## 5. Diferencia imprescindible: retención frente a exclusión

- **Morosidad:** retener, conservar la fecha y emitir después de que se pague. Nunca perder el período.
- **Exclusión manual:** período ya cubierto o adelantado por decisión del administrador; no emitir ese período cuando llegue su fecha ni automáticamente al pagar.
- **Antiguas omisiones por morosidad:** recuperar solo las que no tengan ejecución previa. Cualquier reserva o factura dudosa debe conciliarse manualmente antes de liberar emisión.
- **Protección fiscal:** todos los períodos recuperados conservan el período original como `scheduled_at` y toman la fecha real actual como fecha de emisión; deben cumplir las reglas de numeración vigentes.

**Limitación operacional:** el cron consulta el estado de pago al iniciar cada ejecución. Una cancelación registrada después de iniciarlo no se observa hasta el siguiente ciclo de cron. Para casos con discrepancias de pagos o facturas manuales, hacer conciliación antes de activar el cron en producción.
