# NEWIZZY

## Sistema IZZY

Documentación técnica del proyecto **IZZY**, desarrollado y administrado por **ES MULTISERVICIOS**.

Este repositorio corresponde al ambiente **NEWIZZY (Producción)**. IZZY también cuenta con el ambiente **DEVIZZY (Demo/Desarrollo)**, administrado de forma independiente. Ambos ambientes deben mantener sus credenciales completamente separadas.

> **IMPORTANTE:** Este README no debe contener contraseñas, tokens, API keys, claves privadas ni otros secretos reales.

---

## Ambientes

- **NEWIZZY** — Producción. **Este repositorio.**
- **DEVIZZY** — Demo / Desarrollo. Ambiente independiente.

Cada ambiente debe utilizar únicamente sus propias credenciales.

---

## Estructura de credenciales en el servidor

```text
/home/esmultiservicios/
│
├── credentials/
│   ├── newizzy/
│   │   └── .env          ← Credenciales reales de PRODUCCIÓN
│   │
│   └── devizzy/
│       └── .env          ← Credenciales reales de DEV / DEMO
│
└── public_html/
    ├── newizzy/
    │   └── core/
    │       └── configAPP.php
    │           ↓
    │       /home/esmultiservicios/credentials/newizzy/.env
    │
    └── devizzy/
        └── core/
            └── configAPP.php
                ↓
            /home/esmultiservicios/credentials/devizzy/.env
```

### NEWIZZY — Producción

```text
/home/esmultiservicios/public_html/newizzy/core/configAPP.php
```

debe cargar exclusivamente:

```text
/home/esmultiservicios/credentials/newizzy/.env
```

### DEVIZZY — Demo / Desarrollo

```text
/home/esmultiservicios/public_html/devizzy/core/configAPP.php
```

debe cargar exclusivamente:

```text
/home/esmultiservicios/credentials/devizzy/.env
```

---

## Entorno local

Para desarrollo local se mantiene la misma filosofía: las credenciales permanecen fuera de la carpeta pública del proyecto.

Ejemplo para NEWIZZY:

```text
C:\
├── credentials\
│   └── newizzy\
│       └── .env
│
└── laragon\
    └── www\
        └── newizzy\
```

Credenciales:

```text
C:\credentials\newizzy\.env
```

Proyecto:

```text
C:\laragon\www\newizzy
```

Si DEVIZZY se utiliza también localmente, debe disponer de su propio `.env` independiente.

---

## configAPP.php

El archivo `core/configAPP.php` forma parte del código versionado de IZZY. No contiene credenciales reales: carga la configuración desde el archivo `.env` externo correspondiente a cada ambiente.

`core/configAPP.php` permanece en Git; los valores secretos deben mantenerse únicamente en el `.env` externo de cada ambiente, nunca dentro de este archivo.

```text
NEWIZZY
core/configAPP.php
        ↓
/home/esmultiservicios/credentials/newizzy/.env

DEVIZZY
core/configAPP.php
        ↓
/home/esmultiservicios/credentials/devizzy/.env
```

---

## Clave de accesos de Cocina

`IZZY_COCINA_TOKEN_CIPHER_KEY` es una variable sensible requerida en el `.env` externo del ambiente correspondiente. Debe contener exactamente **64 caracteres hexadecimales**. Nunca debe escribirse directamente en el código ni versionarse.

Si ya existen accesos de Cocina cifrados, debe conservarse la misma clave: cambiarla impediría descifrar los accesos existentes.

`IZZY_COCINA_TOKEN_BYTES = 32` permanece definido en el código porque indica el tamaño usado para generar tokens y no es información sensible.

---

## Seguridad

1. Los archivos `.env` **NO deben almacenarse dentro de `public_html`**.
2. Los archivos `.env` **NO deben subirse a GitHub**.
3. Este README **NO debe contener secretos reales**.
4. NEWIZZY nunca debe cargar el `.env` de DEVIZZY.
5. DEVIZZY nunca debe cargar el `.env` de NEWIZZY.
6. `core/configAPP.php` se versiona y no debe contener contraseñas ni secretos reales; obtiene esos valores del `.env` externo correspondiente.
7. Si cambia una contraseña, token u otra credencial, se actualiza únicamente el `.env` del ambiente correspondiente.
8. No copiar credenciales de producción hacia DEV/DEMO.
9. Antes de cada publicación, verificar que ningún secreto haya sido agregado accidentalmente al repositorio.
10. No incluir contraseñas, tokens, API keys o claves privadas en commits, documentación o mensajes de soporte.

---

## Git / GitHub

Los secretos deben permanecer fuera del repositorio.

En este proyecto `core/configAPP.php` se versiona junto con el código. Los secretos permanecen fuera del repositorio, en el `.env` externo propio de cada ambiente.

`.gitignore` excluye los archivos de entorno y los directorios sensibles que puedan aparecer dentro del árbol del proyecto:

```gitignore
.env
.env.*
credentials/
secrets/
```

Si se necesita documentar qué variables requiere IZZY, puede usarse un archivo de ejemplo con nombres de variables y valores vacíos. La regla `.env.*` también ignora `.env.example`; para versionar ese ejemplo habría que añadir una excepción explícita en `.gitignore`.

```text
.env.example
```

con nombres de variables y valores vacíos, por ejemplo:

```text
DB_HOST=
DB_NAME=
DB_USER=
DB_PASSWORD=
```

**Nunca colocar valores reales en `.env.example`.**

---

## VS Code y Codex

El código puede trabajarse desde VS Code y Codex utilizando la carpeta correspondiente al proyecto.

Ejemplo:

```text
C:\laragon\www\newizzy
```

Las credenciales permanecen separadas:

```text
C:\credentials\newizzy\.env
```

La regla general es:

```text
CÓDIGO       → Proyecto / repositorio
CREDENCIALES → Ubicación externa y privada
```

No es necesario incorporar las contraseñas reales al repositorio para modificar el código de IZZY.

---

## Verificación antes de publicar

Antes de publicar cambios:

- Confirmar que este repositorio NEWIZZY carga `/home/esmultiservicios/credentials/newizzy/.env`.
- Si se trabaja con la instalación DEVIZZY, confirmar por separado que carga `/home/esmultiservicios/credentials/devizzy/.env`.
- Confirmar que ningún `.env` fue agregado a Git.
- Revisar que no existan contraseñas o tokens escritos directamente en archivos versionados.
- Comprobar las conexiones en el ambiente correspondiente.
- Verificar que DEVIZZY no utilice accidentalmente credenciales o recursos exclusivos de producción.
- Confirmar que los cambios de configuración no afecten el funcionamiento existente de IZZY.

---

## API IZZY v1 — Aplicación móvil e integraciones (fases 1 a 4.1, en desarrollo)

Esta es la **única documentación de API** del repositorio. No se necesitan archivos de documentación por fase. Todas las direcciones mostradas usan la base del entorno desplegado, por ejemplo `https://demo.izzycloud.app/api/v1/` o `https://sistema.izzycloud.app/api/v1/`; las credenciales de ambos entornos son distintas.

### Dos identidades, dos tipos de `me.php`

| Uso | Ruta | Identidad | Dónde se utiliza |
| --- | --- | --- | --- |
| APK — iniciar sesión | `POST auth/login.php` | Usuario y contraseña de IZZY | Dispositivo móvil |
| APK — renovar sesión | `POST auth/refresh.php` | Refresh token individual | Dispositivo móvil |
| APK — consultar perfil | `GET auth/me.php` | Bearer **del usuario** | Dispositivo móvil |
| APK — cerrar sesión | `POST auth/logout.php` | Bearer o refresh del usuario | Dispositivo móvil |
| APK — navegación | `GET navigation/menus.php` | Bearer del usuario, permisos y plan | Dispositivo móvil |
| Integración — obtener token | `POST integrations/token.php` | Client ID y Client Secret | **Servidor externo**, nunca APK |
| Integración — comprobar acceso | `GET integrations/me.php` | Bearer **de integración** | Servidor externo |

`auth/me.php` **no se trasladó**. El segundo archivo `integrations/me.php` valida otra clase de credencial. No se debe sustituir uno por el otro.

### Activación manual por IZZY

1. El equipo de IZZY autoriza una empresa **activa** desde **Sistema → API → Empresas autorizadas**. El listado muestra solo activas por defecto; el filtro Select2 permite consultar inactivas y todos los registros.
2. El administrador de esa empresa abre **Mi cuenta → Integraciones API**. Una empresa suspendida o un usuario sin privilegio no puede generar credenciales.
3. Asigna un nombre, vencimiento y alcances. El sistema genera un Client ID y un Client Secret de un solo uso visual. Al perder el secreto, se debe revocar y emitir una nueva integración.
4. El sistema externo envía JSON a `POST /api/v1/integrations/token.php`:

```json
{
  "client_id": "SU_CLIENT_ID",
  "client_secret": "SU_CLIENT_SECRET"
}
```

5. Con el `access_token` devuelto, verifica `GET /api/v1/integrations/me.php` con `Authorization: Bearer SU_ACCESS_TOKEN`.

No debe enviarse `db`, `empresa_id` ni `server_customers_id` para decidir qué empresa consultar. La asociación se determina por la credencial almacenada en DB_MAIN y se vuelve a validar en cada recurso. Todos los clientes usan la misma URL del entorno; **el token delimita su empresa**. El secreto no debe incluirse en una APK, JavaScript público, enlaces, capturas ni correos.

**Significado de errores:** “Empresa no autorizada” significa que no cumple con la aprobación y las comprobaciones de acceso. “No fue posible comprobar el permiso” significa que falló una solicitud (por ejemplo sesión vencida, CSRF, rol, DB o servidor); **no es prueba automática de denegación**. Revisar el mensaje específico, los registros de PHP y la conexión a DB_MAIN antes de dar acceso.

### Alcances disponibles y estado real

| Recurso | Alcance de credencial | Operaciones de datos |
| --- | --- | --- |
| Clientes | `clientes:read` y `clientes:write` | `GET`, `POST`, `PATCH data/clientes.php` — consulta, registro y edición |
| Productos e inventario | `productos:read` | `GET data/productos.php` — catálogo; movimientos de inventario pendientes |
| Proveedores | `proveedores:read` y `proveedores:write` | `GET`, `POST`, `PATCH data/proveedores.php` — consulta, registro y edición |
| Facturas | `facturas:read` | `GET data/facturas.php` — consulta y detalle; emisión pendiente |
| Empresa | `empresa:read` | `GET data/empresa.php` — información de la empresa autorizada |
| Secuencias | `secuencias:read` | `GET data/secuencias.php` — CAI, rangos y fechas; **no reserva correlativos** |

**Importante:** existe consulta GET para seis recursos. La fase 4.1 añade `POST` y `PATCH` **solo para clientes y proveedores**, con permisos de escritura independientes. `POST/PUT/PATCH` de productos, inventario, facturas, empresa y secuencias continúan deshabilitados. No existe todavía un CRUD completo. Las anulaciones, notas de crédito y correlativos de facturación requieren sus propios procesos conforme al SAR. No promover escrituras a producción sin pruebas reales.

### Base de datos e instalación

- Respaldar DB_MAIN y los archivos antes de actualizar.
- Ejecutar **una sola vez y en DB_MAIN** `DB_CAMBIOS_API_20261108.sql`, que consolida las tablas y migraciones de autenticación, autorización e integraciones. No ejecutarlo en cada base cliente.
- No ejecutar nuevamente el script a ciegas; revisar los cambios ya aplicados y la estructura real antes de migraciones posteriores.
- Probar en DEMO: login/refresh/me/logout, menú por privilegio, empresa sin permiso, empresa suspendida, tokens vencidos, secreto incorrecto, revocación y acceso cruzado entre dos clientes.
- Esta entrega no realiza migración remota ni certifica respuestas HTTP reales contra una base de datos del usuario.

### Extensiones futuras: facturación móvil y pagos

La APK podrá incluir catálogo, existencias, clientes, proveedores, secuencias y emisión de facturas cuando existan los controladores API que invoquen **las mismas reglas de negocio vigentes de IZZY**, incluyendo apertura de caja, permisos y comprobantes fiscales. Para una pasarela de pago futura se requiere conector separado, firma y verificación de webhooks, operaciones idempotentes y conciliación; las claves del proveedor de pagos no deben mezclarse con los secretos de estas integraciones.

### Dos direcciones de integración

- **Otro sistema → IZZY:** la empresa autorizada crea una credencial en **Mi cuenta → Integraciones API** o el equipo de IZZY la administra desde **Sistema → API → Accesos externos a IZZY**. `integrations/token.php` obtiene un Bearer y `integrations/me.php` confirma la identidad. La credencial nunca concede acceso a otra base de datos.
- **IZZY → servicios externos:** una pasarela de pago, SMS, WhatsApp o servicio de mensajería necesita un conector **saliente** distinto que administre credenciales y eventos del proveedor. El formulario de accesos externos **no configura ese tipo de conexión**; este conector queda pendiente de implementación.

En Mi cuenta se muestran tres pestañas de tamaño fijo: **Mis accesos**, **Módulos y operaciones**, **Cómo conectarse**. La segunda muestra las operaciones realmente disponibles: GET para seis recursos y POST/PATCH para clientes y proveedores. La tercera incluye las rutas y ejemplos con desplazamiento interno para conservar el tamaño del modal.

**Regla de uniformidad UI:** Sistema → API y Mi cuenta → Integraciones usan los mismos seis recursos de datos y los mismos ocho permisos seleccionables para accesos externos (seis `:read` y dos `:write`). Los ocho permisos deben verse explícitamente en el modal del cliente; `POST` y `PATCH` comparten el permiso `:write` por recurso. En el directorio, cada ruta de autenticación externa y cada recurso ofrece **Ver detalles** con encabezados, parámetros y ejemplos. Las rutas móviles (`auth/*`, `navigation/menus.php`) se documentan por separado: no requieren credenciales de integración y no deben exponerse como permisos externos.

Los detalles de las rutas se abren en un **modal de documentación con scroll interno**, sin aumentar la altura de cada tarjeta ni desalinear su cuadrícula. El cliente dispone del mismo patrón en la pestaña de módulos: ocho tarjetas de permisos visibles y botones «Ver detalles» con operaciones, rutas, encabezados y ejemplos. Ambos modales muestran encabezado y botón de cierre, admiten la tecla ESC y no se cierran al pulsar fuera.

Los modales de documentación muestran un resumen, la solicitud, pasos de consumo, seguridad por empresa y comprobaciones de respuesta. En «Nuevo acceso externo», una advertencia destacada explica que el secreto solo se muestra una vez y que conceder escrituras requiere marcar los permisos correspondientes al crear la credencial; las ya existentes no reciben ese permiso automáticamente.

La integración externa **no inicia sesión como usuario**: envía Client ID y Client Secret a `integrations/token.php` y usa el Bearer resultante. `integrations/me.php` verifica el vínculo. Para clientes y proveedores, los nuevos alcances `:write` autorizan POST/PATCH; las credenciales ya emitidas no ganan permisos de escritura automáticamente. En facturación, una futura emisión POST deberá respetar el SAR; nunca permitirá alterar documentos ya emitidos.

### Fase 3 — Entrega de consulta y validación de permisos

| Ruta GET | Parámetros | Autorización |
| --- | --- | --- |
| `/api/v1/data/clientes.php` | `pagina`, `limite`, `id` | `clientes:read` o menú `clientes` + `consultar` |
| `/api/v1/data/productos.php` | `pagina`, `limite`, `id` | `productos:read` o menú `productos` + `consultar` |
| `/api/v1/data/proveedores.php` | `pagina`, `limite`, `id` | `proveedores:read` o menú `proveedores` + `consultar` |
| `/api/v1/data/facturas.php` | `pagina`, `limite`, `id` | `facturas:read` o menú `facturas` + `consultar` |
| `/api/v1/data/empresa.php` | `pagina`, `limite`, `id` | `empresa:read` o menú `empresa` + `consultar` |
| `/api/v1/data/secuencias.php` | `pagina`, `limite`, `id` | `secuencias:read` o menú `secuencia` + `consultar` |

`pagina` empieza en 1, `limite` usa 25 por defecto y no excede 100, `id` consulta un registro. `facturas.php?id=123` incluye líneas del detalle. Todas las consultas exigen Bearer y HTTPS, no aceptan nombre de DB y devuelven `ok`, `mensaje` y `data`. El menú móvil ahora devuelve `acciones`, `privilegio_id`, `privilegio_nombre` y `tipo_user_id`; el perfil `auth/me.php` también incluye `acciones`. Los permisos son calculados nuevamente desde las tablas del tenant en cada petición.

**Estado:** las consultas permanecen disponibles. Las escrituras de clientes y proveedores de la fase 4.1 requieren validación HTTP contra MySQL real y prueba de aislamiento entre dos tenants antes de usarse en producción. Inventario, facturación y secuencias necesitan todavía sus transacciones, auditoría e idempotencia.

### Fase 4.1 — Escritura de clientes y proveedores (APK e integraciones)

Base DEMO: `https://demo.izzycloud.app/api/v1/`. Las rutas son exactamente las mismas para ambas identidades; la **base de datos se resuelve desde el token**. Nunca enviar `db`, `empresa_id` o `server_customers_id` en los datos de registro.

| Recurso | GET (consulta) | POST (crear) | PATCH (editar) | Alcance externo de escritura |
| --- | --- | --- | --- | --- |
| Clientes | `data/clientes.php` | `data/clientes.php` | `data/clientes.php?id=N` | `clientes:write` |
| Proveedores | `data/proveedores.php` | `data/proveedores.php` | `data/proveedores.php?id=N` | `proveedores:write` |

Todos usan `Authorization: Bearer ACCESS_TOKEN`. Para POST y PATCH enviar `Content-Type: application/json`. En la APK, el token se obtiene de `auth/login.php`; en sistemas externos autorizados, se obtiene de `integrations/token.php` con Client ID y Secret **únicamente desde el servidor**.

Ejemplo de creación (POST):

```http
POST /api/v1/data/clientes.php
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json

{"nombre":"Cliente de prueba","rtn":"","telefono":"","correo":"","departamentos_id":0,"municipios_id":0,"localidad":""}
```

Ejemplo de edición (PATCH):

```http
PATCH /api/v1/data/proveedores.php?id=25
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json

{"telefono":"99998888","correo":"proveedor@ejemplo.com"}
```

Campos admitidos: `nombre`, `rtn`, `fecha` (`YYYY-MM-DD`), `departamentos_id`, `municipios_id`, `localidad`, `telefono`, `correo`, `estado`. En creación, `nombre` es obligatorio. IZZY asigna el ID y controla RTN duplicado, validaciones, límite del plan y restricciones por pagos vencidos. No se admite eliminar registros por API en esta entrega.

**Pruebas obligatorias en DEMO:** crear y editar clientes/proveedores; comprobar los registros en el sistema web; intentar un RTN duplicado, token vencido, usuario sin permisos, integración sin `:write`, plan sin cupo, cuenta con pago vencido y acceso cruzado de dos empresas. Respuestas esperadas: `201` creado, `200` editado, `400` JSON inválido, `403` permiso insuficiente, `404` ID inexistente, `405` método no permitido y `422` campos inválidos. **No se han completado todavía pruebas autenticadas reales contra MySQL**, por lo que estas escrituras no deben publicarse en producción sin validación.

**Próximos módulos:** productos y existencias, movimientos de inventario, cotizaciones, compras, CxC/CxP, facturas, notas de crédito, secuencias fiscales, apertura/cierre de caja y reportes. No habilitar POST de productos o facturas hasta reutilizar y probar sus reglas de negocio, inventario, caja y SAR.

### Guía de pruebas para el desarrollador de la APK

Usar exclusivamente el dominio del ambiente DEMO y usuarios de prueba. Nunca compartir los tokens reales en mensajes o capturas.

1. Ejecutar `POST https://demo.izzycloud.app/api/v1/auth/login.php` con el mismo cuerpo de usuario/correo y contraseña de la API móvil. Guardar el `access_token` devuelto.
2. Ejecutar `GET https://demo.izzycloud.app/api/v1/auth/me.php` con la cabecera `Authorization: Bearer ACCESS_TOKEN`. Revisar `usuario`, `cliente`, `permisos` y `acciones`.
3. Ejecutar `GET https://demo.izzycloud.app/api/v1/navigation/menus.php` con **ese mismo Bearer de usuario**. Verificar que muestra solo menús/submenús del plan y privilegio, además de los permisos de acción.
4. Con el mismo Bearer, consultar `GET /api/v1/data/clientes.php?pagina=1&limite=10`, `productos.php`, `proveedores.php`, `facturas.php`, `empresa.php` y `secuencias.php` (siempre bajo `/api/v1/data/`). Probar un usuario con y sin permiso `consultar` y comparar las respuestas.
5. Probar `GET /api/v1/data/facturas.php?id=ID_DE_PRUEBA` para ver el encabezado y detalle de una factura existente. **No se altera ni emite ninguna factura**.
6. Confirmar que los tokens vencidos, usuarios desactivados, permisos retirados y empresas ajenas no pueden leer datos. El mismo token no debe acceder a otra empresa cambiando parámetros.

Para un integrador externo, **no usar `auth/login.php`**: obtener token en `POST /api/v1/integrations/token.php` con su Client ID/Secret y consultar únicamente las rutas permitidas por sus scopes. `integrations/me.php` comprueba la integración; `auth/me.php` comprueba usuarios de la APK.

Los resultados de estas pruebas deben registrarse antes de promover cambios a producción. La lectura de secuencias es informativa: el campo `siguiente` no constituye una reserva, garantía ni autorización para emitir ese número desde un cliente móvil o externo.

### Controles de interfaz

**Ubicación de modales:** el navbar (`vistas/plantilla/modulos/navbartop.php`) ahora contiene únicamente la navegación y el enlace a Integraciones API. Los diálogos de Integraciones API, Centro de Ayuda y Notificaciones se renderizan desde `vistas/contenido/modals/vistasModals.php`, incluido por la plantilla. No duplicar IDs ni reinstalar un modal en `navbartop.php`.

**Directorio Sistema → API → Rutas API:** muestra las 13 rutas existentes de esta entrega: cuatro de autenticación móvil, una de navegación, dos de autenticación de integraciones y seis recursos de datos. Los módulos y scopes se muestran desde `core/api/ApiModuleCatalog.php`.

**Vista de empresas:** la lista se numera del 1 en adelante según el resultado filtrado; el número visible es ordinal y no sustituye `server_customers_id`.

**Sistema → API / Empresas autorizadas:** cada empresa muestra el sistema al que pertenece y puede filtrarse por sistema, estado y nombre. El filtro inicial es **IZZY**; CAMI y Monitoring pueden visualizarse al seleccionar su sistema, pero esta versión de la API solo permite emitir integraciones de IZZY (`sistema_id=1`). No se habilita acceso cruzado a otras plataformas.

**Mi cuenta → Integraciones API:** la pestaña de módulos y ambos formularios de alcances se construyen desde `core/api/ApiModuleCatalog.php`. Para agregar un recurso futuro, además de implementar su endpoint y permisos, debe registrarse en este catálogo; aparecerá en los formularios y la guía de módulos sin repetir tarjetas HTML manualmente. Un elemento en el catálogo no implica que su API de escritura ya esté disponible.

**Diseño del formulario de acceso externo:** tarjetas de alcance con indicador circular a la derecha, foco visible, modales responsivos y acciones fijas en el footer; no se incorporan dependencias de `Swal.fire`.

1. Confirmación explícita antes de autorizar una empresa.
2. Confirmación explícita antes de suspenderla.
3. Nombre y código de empresa incluidos en la confirmación.
4. Confirmación con el `swal(...)` tradicional utilizado por IZZY y respaldo local si el script no está disponible.
5. Botón de acción bloqueado mientras se envía la solicitud.
6. Estados vacíos claros en Mi cuenta sin credenciales.
7. Modales divididos en header, body desplazable y footer fijo.
8. Guía por módulo con métodos previstos y estado real de publicación.
9. Diferenciación entre autenticación de usuario móvil y credencial de servidor.
10. Diseño responsivo sin botones blancos ni hover que borre las etiquetas.

### Claridad para el administrador

**Autorizar una empresa** habilita exclusivamente la **emisión de credenciales de integración**. Después se escogen los alcances reservados por credencial (clientes, productos, proveedores y facturas). **Todavía no activa los endpoints de lectura o escritura**: requieren implementación y pruebas por separado. Una empresa inactiva no aparece en el selector de emisión.

Si no existe ninguna empresa activa y autorizada, el formulario de acceso externo explica el motivo, deshabilita “Crear credenciales” y ofrece abrir la pestaña de autorización. No es un error silencioso del Select2.

El formulario **Otro sistema → IZZY** permite conceder acceso a datos de una empresa IZZY desde un servidor ajeno. Las APIs de pagos, SMS o mensajería que **IZZY → proveedor externo** consuma necesitarán otro módulo de configuración para conectores salientes; no se registran aquí.

### Explicación para desarrolladores: renovación y cierre móvil

La ruta `POST auth/refresh.php` recibe el **refresh token actual** en JSON, devuelve un par nuevo y deja inutilizable el anterior. Si se presenta de nuevo un refresh token ya reemplazado, el servidor trata la reutilización como posible robo y revoca la familia de esa sesión; el usuario tendrá que iniciar sesión de nuevo. La APK debe guardar de forma segura **el par más reciente** después de cada renovación.

La ruta `POST auth/logout.php` puede recibir `Authorization: Bearer TOKEN_DE_ACCESO` si el access token sigue vigente. Si ya venció, admite JSON `{"refresh_token":"TOKEN_REFRESH_ACTUAL"}`. Después del cierre, la APK debe eliminar ambos tokens almacenados. Los valores escritos en este README son ilustrativos, nunca credenciales reales.

## Soporte

Este proyecto corresponde a **IZZY de ES MULTISERVICIOS**.

Para consultas, soporte técnico o incidencias relacionadas con el proyecto, contactar a **ES MULTISERVICIOS mediante mensaje de WhatsApp**:

**WhatsApp:** +504 8913-6844

> Por seguridad, no enviar contraseñas, tokens, API keys, claves privadas ni otras credenciales sensibles por WhatsApp.

---

### ES MULTISERVICIOS

**Más que servicio, construimos soluciones.**
