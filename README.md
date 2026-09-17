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

## Soporte

Este proyecto corresponde a **IZZY de ES MULTISERVICIOS**.

Para consultas, soporte técnico o incidencias relacionadas con el proyecto, contactar a **ES MULTISERVICIOS mediante mensaje de WhatsApp**:

**WhatsApp:** +504 8913-6844

> Por seguridad, no enviar contraseñas, tokens, API keys, claves privadas ni otras credenciales sensibles por WhatsApp.

---

### ES MULTISERVICIOS

**Más que servicio, construimos soluciones.**
