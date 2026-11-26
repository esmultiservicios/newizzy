# Auditoría de configuración de IZZY

La incidencia reportada fue compatible con una validación global que exigía variables administrativas durante el inicio de sesión. En la revisión local de la base 96dc9ef, los tres nombres originales ya no eran usados por el código de aplicación. El defecto persistía para cPanel y WHM porque sus tokens, usuarios y hosts seguían siendo obligatorios en configAPP.php.

- El inicio ahora valida juntas las variables de conexión y cifrado base. Los clientes cPanel y WHM validan sus propias dependencias antes de emitir solicitudes HTTPS. El registro devuelve HTTP 503 controlado si el servicio de alta carece de configuración.
- PHP CLI usa DB_MAIN sin abrir sesiones web. La selección de archivo conserva producción, demo y Laragon. La ruta explícita se toma del entorno del proceso. Se rechazan archivos bajo la raíz pública o del repositorio, incluyendo rutas resueltas mediante enlaces.
- Los servicios cPanel y WHM verifican el certificado TLS. Si el proveedor usa un nombre alternativo, configure el nombre que aparece en el certificado y la CA correspondiente en PHP.
- ZYNKO usa su token cifrado de DB_MAIN. La llave de protección continúa fuera de la base. Se conserva el archivo protegido o la misma llave de 64 caracteres hexadecimales en IZZY_EXTERNAL_API_ENCRYPTION_KEY.
- Se versiona exclusivamente la plantilla .env.example. Las reglas de Git ignoran archivos de entorno reales, credenciales y llaves de API. La revisión de nombres rastreados no encontró archivos .env o .key en la copia examinada.

## Matriz de variables

### Base y entorno

- **SERVER** · Base obligatoria · configAPP.php, mainModel.php y DatabaseSetup.php · host de la conexión de aplicación e importación.
- **USER** · Base obligatoria y sensible · mismos consumidores · usuario MySQL de la aplicación y del importador. También se asigna como CPANEL_DB_USERNAME interno.
- **PASS** · Base obligatoria y sensible · mismos consumidores · contraseña MySQL. También se asigna como CPANEL_DB_PASSWORD interno.
- **DB_MAIN** · Base obligatoria · configAPP.php, mainModel.php, registro, APIs, cron · base principal del ambiente. Debe corresponder al archivo privado seleccionado.
- **SERVER_MAIN** · Opcional · configAPP.php y mainModel.php · host de consultas a la base principal. Hereda SERVER.
- **DB_PRUEBA** · Opcional por entorno · configAPP.php y getValidUserSesion.php · comparación del indicador is_test en el endpoint de sesión. Su ausencia deja ese indicador en falso.
- **IZZY_ENV_FILE** · Opcional por entorno · izzyEnvironment.php y herramienta CLI · ruta privada explícita del proceso. Tiene prioridad sobre las rutas predeterminadas.
- **APP_URL** · Configuración de cron · process_jobs.php y procesarFacturasRecurrentes.php · identifica el host y ambiente del proceso. Indíquelo explícitamente en cada cron de demo y producción.
- **IZZY_APP_VERSION** · Opcional · configGenerales.php · etiqueta de versión. El arranque usa la versión del repositorio o la versión integrada si falta.

### Administración y MySQL

- **CPANEL_HOST**, **CPANEL_USERNAME** y **CPANEL_TOKEN** · Obligatorios para cPanel · configAPP.php, clientesControlador.php y cPanelAPI.php · servidor UAPI, cuenta y token sensible. Si faltan, el alta de clientes y las operaciones UAPI quedan indisponibles.
- **CPANEL_PORT** · Opcional · cPanelAPI.php · HTTPS 2083 por defecto.
- **CPANEL_DOMINIO** · Obligatorio al operar subdominios · cPanelAPI.php · dominio raíz de esos subdominios.
- **API_TIMEOUT** · Opcional · configAPP.php y cPanelAPI.php · espera para UAPI. Predeterminado 60 segundos.
- **WHM_HOST**, **WHM_USERNAME** y **WHM_TOKEN** · Obligatorios para WHM · configAPP.php y cPanelAPI.php · servidor, operador y token sensible. Solo afectan las operaciones WHM.
- **WHM_PORT** y **WHM_TIMEOUT** · Opcionales · configAPP.php y cPanelAPI.php · puerto HTTPS 2087 y espera de 30 segundos.
- **DB_PREFIX** · Opcional · configAPP.php y vistas de soporte · prefijo mostrado para bases existentes. Hereda CPANEL_USERNAME cuando está presente.
- **DB_MAX_LENGTH** · Opcional · configAPP.php y mainModel.php · longitud del nombre corto para crear bases. Predeterminado 10.
- **MYSQL_USER**, **MYSQL_PASS**, **CPANEL_PASSWORD** · Obsoletas en esta versión · sin consumo en los flujos auditados. cPanel UAPI y WHM autentican por token. USER y PASS siguen siendo necesarios.
- **SSL_VERIFICATION** · Heredada · la clase de administración usa verificación HTTPS obligatoria.
- **SISTEMA_PRUEBA** · Heredada como variable de archivo · la aplicación calcula su constante con el host.

### Integraciones y cifrado

- **METHOD** · Opcional · configAPP.php y mainModel.php · cifrado heredado. Predeterminado AES-256-CBC. Conserve el método vigente para descifrar registros anteriores.
- **SECRET_KEY** y **SECRET_IV** · Base obligatoria y sensible · configAPP.php y mainModel.php · cifrado heredado usado en inicio de sesión y otros módulos. Conserve ambos valores existentes.
- **IZZY_COCINA_TOKEN_CIPHER_KEY** · Obligatoria y sensible para tokens de cocina · cocinaTokenConfig.php y cocinaTokenService.php · llave hexadecimal de 64 caracteres.
- **IZZY_EXTERNAL_API_ENCRYPTION_KEY** · Opcional y sensible · ExternalProviderService.php · llave AES-GCM. Alternativa al archivo protegido que ya usa IZZY. Si existe una conexión cifrada, conserve la llave original.
- **IZZY_ZYNKO_TEST_NUMBERS** · Obsoleta en la integración ajustada · los destinatarios de prueba se ingresan en el formulario y se valida su consentimiento.
- **IZZY_DOCUMENT_AUTO_EMAIL** · Opcional · configAPP.php, ajax/js/main.php y sendFactura.php · activa el inicio del correo desde la pantalla, predeterminado verdadero.
- **WEB_SCRAPING_DOLARES** · Opcional · configAPP.php y consulta del cambio · URL del Banco Promerica por defecto.

Las configuraciones de proveedor, las autorizaciones de APIs y las credenciales de correo almacenadas en tablas se administran en IZZY. El diagnóstico de variables no prueba su disponibilidad.

## Actualización por ambiente

1. Respalde los archivos de la publicación anterior y la base donde aplicará el ajuste. Mantenga los secretos en la ruta privada que ya usa cada entorno.
2. Rutas predeterminadas. Producción usa `/home/esmultiservicios/credentials/newizzy/.env`, demo usa `/home/esmultiservicios/credentials/devizzy/.env` y Laragon usa `C:\credentials\newizzy\.env`. Compare por separado los nombres de las bases en su servidor.
3. Copie el ZIP a un entorno de prueba. Ejecute `php scripts/validar_configuracion.php --entorno=local` en Laragon o use `--entorno=demo` o `--entorno=produccion` en el servidor correspondiente. Agregue `--exigir=cpanel`, `--exigir=whm`, `--exigir=cocina`, `--exigir=api-externa` o `--exigir=cron` para que la dependencia elegida determine el código de salida.
4. En bases existentes aplique el bloque **Cola de altas IZZY** de DB_Cambios.sql. La plantilla actualizada se usa para las altas nuevas.
5. Use el mismo binario PHP del cron y el mismo APP_URL para ejecutar `scripts/process_jobs.php --check`. Luego haga el alta controlada y confirme el registro central, la importación y los correos.
6. Valide en demo y local inicio de sesión, dashboard, facturación, inventario, clientes, menús y privilegios. Desde el servidor, confirme MySQL, cPanel, WHM y el cron. Compruebe APIs internas, servicios externos y ZYNKO con un canal y un destinatario autorizado antes de promover producción.
7. Tras publicar y validar, retire las variables obsoletas del archivo privado. Conserve respaldo de la versión anterior por si necesita reversión.

## Pruebas y alcance

- Doce escenarios con configuración ficticia para los hosts de producción, demo y local, con ambos módulos presentes, con cPanel ausente, con WHM ausente y con ambos ausentes. Se verificaron la inicialización de las variables, la validación por módulo y los códigos de salida del diagnóstico.
- cPanel y WHM se probaron con transporte simulado. Se comprobó el encabezado de token, la verificación TLS y el formato de respuesta.
- También se verificaron rutas predeterminadas, errores base agrupados y que el diagnóstico no imprima los secretos ficticios.
- Del cambio acumulado anterior se conservan las pruebas de PDF con 80 filas por reporte, compatibilidad de ciphertext con la llave original, importación de plantilla e índice de cola en MySQL 8.0.
- La comprobación operativa con datos reales de inicio de sesión, dashboard, facturación, inventarios, clientes, privilegios y menús, conexiones MySQL, alta, cPanel, WHM, APIs, ZYNKO y cron sigue pendiente. Los escenarios con datos ficticios verifican el arranque de configuración, no esas pantallas ni los proveedores reales.

## Reversión

Restaure juntos los archivos PHP y JavaScript de la publicación anterior. Si esa versión exigía MYSQL_USER, MYSQL_PASS o CPANEL_PASSWORD y ya los retiró, restaure el archivo privado respaldado para ese mismo ambiente. La llave de cifrado y las credenciales existentes deben conservarse. El índice es compatible con el cron anterior. Restaure la plantilla anterior solo si vuelve también al worker anterior. La base creada para un cliente merece revisarse antes de reintentar o cambiar su estado.

## Commit sugerido

🔐 Aísla credenciales administrativas y valida la configuración de IZZY

- 🧩 Valida cPanel, WHM y módulos opcionales al utilizarlos.
- 🩺 Agrega diagnóstico CLI y una plantilla de entorno sin secretos.
- 📄 Integra los cambios del cron, la plantilla y el visor de PDF contable.

## Archivos del paquete

- .env.example
- .gitignore
- DB_Cambios.sql
- ajax/js/gastosContabilidad.php
- ajax/js/ingresosContabilidad.php
- ajax/js/main.js
- ajax/js/reportesContablesPdf.worker.js
- controladores/clientesControlador.php
- core/api/ExternalProviderService.php
- core/configAPP.php
- core/izzyEnvironment.php
- core/registro/DatabaseSetup.php
- core/registro/cPanelAPI.php
- docs/AUDITORIA_CONFIGURACION.md
- plantilla/plantilla_izzy.sql
- scripts/process_jobs.php
- scripts/validar_configuracion.php
- vistas/plantilla/modulos/DynamicNavbar.php
- vistas/plantilla/plantilla.php
