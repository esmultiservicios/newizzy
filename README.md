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

## API IZZY v1 — Aplicación móvil e integraciones (fases 1 y 2)

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
| Clientes | `clientes:read` | Pendiente de publicación y pruebas |
| Productos e inventario | `productos:read` | Pendiente de publicación y pruebas |
| Proveedores | `proveedores:read` | Pendiente de publicación y pruebas |
| Facturas | `facturas:read` | Pendiente de publicación y pruebas |

**Importante:** estos alcances se pueden registrar, pero **no existe aún una API publicada de CRUD** de esos recursos en esta entrega. No prometer a terceros endpoints de lectura o escritura que todavía no existen. Para la siguiente fase se necesitan `clientes:write`, `productos:write`, `proveedores:write` y `facturas:create`, con validación del usuario o integración, privilegios, aislamiento entre bases y transacciones. En facturación, la norma SAR impide modificar una factura emitida: las anulaciones y notas de crédito requieren su flujo legal, no un `PUT` genérico. Deben probarse casos positivos y negativos en DEMO antes de permitir mutaciones. Las APIs para APK también deberán revisar permisos por usuario, plan, menú y empresa para cada acción, sin duplicar lógica de negocio.

### Base de datos e instalación

- Respaldar DB_MAIN y los archivos antes de actualizar.
- Ejecutar **una sola vez y en DB_MAIN** `DB_CAMBIOS_API_20261108.sql`, que consolida las tablas y migraciones de autenticación, autorización e integraciones. No ejecutarlo en cada base cliente.
- No ejecutar nuevamente el script a ciegas; revisar los cambios ya aplicados y la estructura real antes de migraciones posteriores.
- Probar en DEMO: login/refresh/me/logout, menú por privilegio, empresa sin permiso, empresa suspendida, tokens vencidos, secreto incorrecto, revocación y acceso cruzado entre dos clientes.
- Esta entrega no realiza migración remota ni certifica respuestas HTTP reales contra una base de datos del usuario.

### Extensiones futuras: facturación móvil y pagos

La APK podrá incluir catálogo, existencias, clientes, proveedores, secuencias y emisión de facturas cuando existan los controladores API que invoquen **las mismas reglas de negocio vigentes de IZZY**, incluyendo apertura de caja, permisos y comprobantes fiscales. Para una pasarela de pago futura se requiere conector separado, firma y verificación de webhooks, operaciones idempotentes y conciliación; las claves del proveedor de pagos no deben mezclarse con los secretos de estas integraciones.

### Dos direcciones de integración (interfaz v2.3)

- **Otro sistema → IZZY:** la empresa autorizada crea una credencial en **Mi cuenta → Integraciones API** o el equipo de IZZY la administra desde **Sistema → API → Accesos externos a IZZY**. `integrations/token.php` obtiene un Bearer y `integrations/me.php` confirma la identidad. La credencial nunca concede acceso a otra base de datos.
- **IZZY → servicios externos:** una pasarela de pago, SMS, WhatsApp o servicio de mensajería necesita un conector **saliente** distinto que administre credenciales y eventos del proveedor. El formulario de accesos externos **no configura ese tipo de conexión**; este conector queda pendiente de implementación.

En Mi cuenta se muestran tres pestañas de tamaño fijo: **Mis accesos**, **Módulos y operaciones**, **Cómo conectarse**. La segunda explica clientes, productos, inventario, proveedores y facturas, indicando expresamente que sus endpoints de datos y escrituras aún no están publicados. No se debe vender una autorización de alcance como funcionalidad implementada. La tercera pestaña contiene las URL y ejemplos copiables, con desplazamiento interno para que no cambie el tamaño del modal.

La integración externa **no inicia sesión como usuario**: envía su Client ID y Client Secret a `integrations/token.php` y usa el Bearer resultante. `integrations/me.php` verifica el vínculo. Las futuras rutas por módulo tendrán lectura (`GET`), creación (`POST`) y actualización (`PUT`) únicamente cuando su implementación haya sido aprobada y probada. En facturas, `POST` será para emisión conforme al SAR, nunca modificación de documentos ya emitidos. Los nombres y direcciones concretas de esas rutas aún no están disponibles; no intentar invocar rutas supuestas.

### Controles de interfaz v2.4

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

### Claridad para el administrador (v2.5)

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
