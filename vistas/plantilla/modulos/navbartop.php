<nav class="sb-topnav navbar navbar-expand navbar-dark bg-color-navarlateral">
  <div class="navbar-brand logo-container">
    <a href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
      <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logos/logo.svg"
           alt="IZZY" class="logo img-fluid">
    </a>
  </div>

  <!-- ===== Botón Menú principal (solo tablets/móviles) ===== -->
  <div class="mobile-mainmenu-wrapper d-lg-none d-flex justify-content-center">
    <div class="dropdown">
      <button id="mobile-mainmenu-btn" class="btn btn-light btn-md" type="button"
              title="Menú principal" aria-label="Abrir menú principal"
              aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-th-large" aria-hidden="true"></i>
        <span class="izzy-mainmenu-text ml-2">Menú</span>
      </button>
      <div id="mobile-mainmenu" class="dropdown-menu dropdown-menu-center shadow"
           aria-labelledby="mobile-mainmenu-btn"></div>
    </div>
  </div>

  <!-- Botón de alternar menú lateral (sidebar) -->
  <button class="btn izzy-navbar-menu-toggle order-1 order-lg-0" id="sidebarToggle"
          type="button" title="Mostrar u ocultar menú lateral"
          aria-label="Mostrar u ocultar menú lateral" aria-controls="layoutSidenav_nav">
    <i class="fas fa-bars" aria-hidden="true" style="color:#fff!important;-webkit-text-fill-color:#fff!important;opacity:1!important;"></i>
  </button>

  <!-- Control de pantalla completa del navbar. Se mantiene al navegar la preferencia. -->
  <button id="izzy-persistent-fullscreen" class="btn btn-primary btn-sm order-1 order-lg-0"
          type="button" title="Activar pantalla completa" aria-label="Activar pantalla completa"
          aria-pressed="false">
    <i class="fas fa-expand" aria-hidden="true"></i>
  </button>

  <!-- Menú principal (versión desktop) -->
  <ul class="navbar-nav d-none d-lg-flex">
    <li class="nav-item"><a class="nav-link link menu-item reporteVentas" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteVentas/" style="display:none"><i class="fas fa-file-invoice-dollar fa-lg mr-2"></i>Reporte Ventas</a></li>
    <li class="nav-item"><a class="nav-link link menu-item reporteCotizacion" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCotizacion/" style="display:none"><i class="fas fa-file-signature fa-lg mr-2"></i>Reporte Cotización</a></li>
    <li class="nav-item"><a class="nav-link link menu-item reporteCompras" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>reporteCompras/" style="display:none"><i class="fas fa-shopping-cart fa-lg mr-2"></i>Reporte Compras</a></li>
    <li class="nav-item"><a class="nav-link link menu-item cobrarClientes" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>cobrarClientes/" style="display:none"><i class="fas fa-hand-holding-usd fa-lg mr-2"></i>CXC Clientes</a></li>
    <li class="nav-item"><a class="nav-link link menu-item pagarProveedores" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>pagarProveedores/" style="display:none"><i class="fas fa-file-invoice fa-lg mr-2"></i>CXP Proveedores</a></li>
    <li class="nav-item"><a class="nav-link link menu-item inventario" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>inventario/" style="display:none"><i class="fas fa-exchange-alt fa-lg mr-2"></i>Movimientos</a></li>
    <li class="nav-item"><a class="nav-link link menu-item transferencia" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>transferencia/" style="display:none"><i class="fas fa-boxes fa-lg mr-2"></i>Inventario</a></li>
    <li class="nav-item"><a class="nav-link link menu-item nomina" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>nomina/" style="display:none"><i class="fas fa-money-check-alt fa-lg mr-2"></i>Nomina</a></li>
    <li class="nav-item"><a class="nav-link link menu-item asistencia" href="#" id="marcarAsistencia"><i class="fas fa-user-clock fa-lg mr-2"></i>Asistencia</a></li>
  </ul>

  <!-- Navbar usuario -->
  <ul class="navbar-nav ml-auto mr-0 mr-md-3 my-2 my-md-0 navbar-nav-user">
    <li class="nav-item dropdown mx-1">
      <a class="nav-link dropdown-toggle position-relative izzy-nav-bell" id="notification-bell" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="far fa-bell"></i><span id="notification-count" class="izzy-notification-badge" style="display:none;"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right notification-dropdown izzy-notification-panel" aria-labelledby="notification-bell">
        <div class="izzy-panel-heading">
          <strong>Notificaciones</strong>
          <button type="button" class="izzy-panel-link" id="izzy-mark-notifications-read">Marcar como leídas</button>
        </div>
        <div id="izzy-notification-items" aria-live="polite">
          <div class="izzy-notification-card"><span class="izzy-icon"><i class="fas fa-info-circle"></i></span>
            <div class="izzy-copy"><strong>Sin avisos pendientes</strong><p>Las novedades de su cuenta aparecerán aquí.</p></div>
          </div>
        </div>
        <div id="izzy-aviso-facturacion" style="display:none" aria-live="polite">
          <span id="izzy-aviso-facturacion-texto" class="sr-only"></span>
        </div>
        <a class="dropdown-item d-flex align-items-center" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>DetallesFacturacion/" style="display:none!important">
          <span id="notification-dropdown-count">0</span>
        </a>
        <button type="button" class="izzy-all-notifications" id="izzy-view-all-notifications">Ver todas las notificaciones <i class="fas fa-arrow-right ml-2"></i></button>
      </div>
    </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" data-izzy-user-dropdown="1">
        <span class="izzy-user-avatar"><i class="fas fa-building" aria-hidden="true"></i></span><span id="user_session" class="izzy-user-name mr-1"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right user-dropdown izzy-user-panel" aria-labelledby="userDropdown">
        <div class="izzy-user-panel-heading">
          <span class="izzy-user-panel-avatar"><i class="fas fa-building" aria-hidden="true"></i></span>
          <div class="izzy-user-panel-identity">
            <strong id="izzy-user-menu-display-name">Mi empresa</strong>
            <small>Mi cuenta</small>
          </div>
        </div>
        <a class="dropdown-item izzy-account-action" href="#" id="cambiar_contraseña_usuarios_sistema"><i class="fas fa-key izzy-account-icon izzy-account-icon-key"></i> Modificar Contraseña</a>
        <a class="dropdown-item izzy-account-action" href="#" id="modificar_perfil_usuario_sistema"><i class="fas fa-id-card izzy-account-icon izzy-account-icon-profile"></i> Mi Perfil<span id="badge-codigo-cliente" class="badge bg-primary ml-2"></span></a>
        <a class="dropdown-item izzy-account-action" href="#" id="izzy-mi-cuenta-api" style="display:none"><i class="fas fa-plug izzy-account-icon izzy-account-icon-api"></i> Integraciones API</a>
        <a class="dropdown-item izzy-account-action" href="#" id="ver-pin-usuario" data-toggle="popover"><i class="fas fa-lock izzy-account-icon izzy-account-icon-pin"></i> Ver mi PIN<span id="badge-pin-cliente" class="badge bg-info ml-2"></span></a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item d-flex align-items-center izzy-account-action" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>DetallesFacturacion/"><i class="fas fa-file-invoice izzy-account-icon izzy-account-icon-billing"></i><span class="flex-grow-1 ml-2">Detalles de Facturación</span><span id="izzy-estado-cuenta-facturacion" class="badge badge-warning ml-2" style="display:none;"></span><span id="badge-facturas-pendientes-dropdown" class="badge bg-danger" style="display:none;">0</span></a>
        <a class="dropdown-item izzy-account-action" href="#" id="izzy-open-help-center"><i class="fas fa-question-circle izzy-account-icon izzy-account-icon-help"></i> Centro de Ayuda</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item btn-exit-system izzy-account-action" href="<?php echo $lc->encryption($_SESSION['token_sd']);?>"><i class="fas fa-sign-out-alt izzy-account-icon izzy-account-icon-exit"></i> Salir</a>
      </div>
    </li>
  </ul>
</nav>

<?php
if (session_status()!==PHP_SESSION_ACTIVE) session_start(['name'=>'SD']);
if (empty($_SESSION['csrf_api_cuenta'])) $_SESSION['csrf_api_cuenta']=bin2hex(random_bytes(32));
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL,ENT_QUOTES,'UTF-8'); ?>vistas/plantilla/css/miCuentaApi.css?v=1">
<div class="izzy-account-api-overlay" id="izzy-account-api-overlay" aria-hidden="true" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_api_cuenta'],ENT_QUOTES,'UTF-8'); ?>" data-url="<?php echo htmlspecialchars(SERVERURL,ENT_QUOTES,'UTF-8'); ?>ajax/miCuentaApi.php" data-base-url="<?php echo htmlspecialchars(rtrim(SERVERURL,'/').'/api/v1/',ENT_QUOTES,'UTF-8'); ?>">
  <section class="izzy-account-api-dialog" role="dialog" aria-modal="true" aria-labelledby="izzy-account-api-title">
    <header><div><small>MI CUENTA · INTEGRACIONES</small><h2 id="izzy-account-api-title">Integraciones API</h2></div><button type="button" class="izzy-account-api-close" aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
    <div class="izzy-account-api-body">
      <p>Administre el acceso de otros sistemas a su empresa IZZY. Los datos permanecen aislados en su base y la autorización siempre depende de IZZY.</p>
      <div id="izzy-account-api-status" role="status"></div>
      <nav class="izzy-account-api-tabs" aria-label="Opciones de integración"><button type="button" class="is-active" data-izzy-api-tab="access"><i class="fas fa-plug"></i> Mis accesos</button><button type="button" data-izzy-api-tab="modules"><i class="fas fa-layer-group"></i> Módulos y operaciones</button><button type="button" data-izzy-api-tab="guide"><i class="fas fa-book-open"></i> Cómo conectarse</button></nav>
      <div class="izzy-account-api-tab-panel is-active" data-izzy-api-content="access">
      <div id="izzy-account-api-list"></div>
      <div class="izzy-account-api-promo"><span><i class="fas fa-cloud"></i></span><div><strong>Conecte su operación con IZZY</strong><small>Un solo acceso por integración, autorización de su empresa y credenciales revocables. Sus datos permanecen protegidos.</small></div></div>
      <form id="izzy-account-api-form" autocomplete="off">
        <h3>Nuevo acceso para un sistema externo</h3>
        <div class="izzy-account-api-fields"><div><label for="izzy-account-api-name">Nombre</label><input id="izzy-account-api-name" maxlength="100" required placeholder="Ej. Conexión con mi tienda"></div>
        <div><label for="izzy-account-api-expiry">Vencimiento</label><select id="izzy-account-api-expiry" required><option value="90">90 días</option><option value="30">30 días</option><option value="365">1 año</option><option value="0">Sin vencimiento</option></select></div></div>
        <fieldset class="izzy-account-api-scopes"><legend>Datos que desea compartir (alcances reservados)</legend><label><input type="checkbox" value="clientes:read" checked><span class="izzy-account-api-scope-icon"><i class="fas fa-users"></i></span><span><strong>Clientes</strong><small>Consulta · Próxima fase</small></span></label><label><input type="checkbox" value="productos:read" checked><span class="izzy-account-api-scope-icon"><i class="fas fa-box"></i></span><span><strong>Productos</strong><small>Consulta · Próxima fase</small></span></label><label><input type="checkbox" value="proveedores:read"><span class="izzy-account-api-scope-icon"><i class="fas fa-truck"></i></span><span><strong>Proveedores</strong><small>Consulta · Próxima fase</small></span></label><label><input type="checkbox" value="facturas:read"><span class="izzy-account-api-scope-icon"><i class="fas fa-file-invoice"></i></span><span><strong>Facturas</strong><small>Consulta · Próxima fase</small></span></label></fieldset>
        <div class="izzy-account-api-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-key"></i> Crear credenciales</button></div>
      </form>
      </div>
      <section class="izzy-account-api-tab-panel" data-izzy-api-content="modules" aria-label="Módulos de integración">
        <h3><i class="fas fa-database"></i> ¿Qué podrá conectar con IZZY?</h3>
        <p>Estas son las operaciones previstas para su empresa. Las rutas de datos se publicarán por etapas después de validar los permisos y las reglas de facturación.</p>
        <div class="izzy-account-api-modules"><article><i class="fas fa-users"></i><strong>Clientes</strong><span>Consultar, registrar y actualizar clientes.</span><small>Lectura y escritura pendientes</small></article><article><i class="fas fa-boxes"></i><strong>Productos e inventario</strong><span>Catálogo, existencias y movimientos autorizados.</span><small>Lectura y escritura pendientes</small></article><article><i class="fas fa-truck"></i><strong>Proveedores</strong><span>Consultar, registrar y actualizar proveedores.</span><small>Lectura y escritura pendientes</small></article><article><i class="fas fa-file-invoice-dollar"></i><strong>Facturas</strong><span>Consultar y crear documentos siguiendo las reglas SAR de IZZY.</span><small>Emisión pendiente de validación</small></article></div>
        <div class="izzy-account-api-warning"><i class="fas fa-shield-alt"></i> La API no acepta un nombre de base de datos proporcionado por el cliente. Cada credencial identifica exclusivamente su empresa.</div>
      </section>
      <div class="izzy-account-api-tab-panel" data-izzy-api-content="guide">
      <details class="izzy-account-api-guide" id="izzy-account-api-guide" aria-labelledby="izzy-account-api-guide-title">
        <summary><i class="fas fa-book-open"></i> Guía de conexión, URL y ejemplos <span>Ver instrucciones <i class="fas fa-chevron-down"></i></span></summary>
        <div class="izzy-account-api-guide-head"><div><small>GUÍA DE CONEXIÓN</small><h3 id="izzy-account-api-guide-title">Cómo usar su API en 3 pasos</h3></div><span class="izzy-account-api-guide-badge"><i class="fas fa-lock"></i> HTTPS seguro</span></div>
        <ol><li><strong>1. Cree una integración</strong><span>Debe estar autorizada por IZZY. Elija la empresa y los datos que autoriza a compartir.</span></li><li><strong>2. Guarde sus credenciales</strong><span>El Client Secret se muestra una sola vez. Guárdelo en su servidor, nunca en una aplicación pública.</span></li><li><strong>3. Solicite un Bearer y consulte su acceso</strong><span>Su servidor se identifica con Client ID + Secret. No necesita iniciar sesión con correo y contraseña como lo hace la APK.</span></li></ol>
        <div class="izzy-account-api-endpoint"><span><b>POST</b> Obtener token</span><code id="izzy-account-api-token-url"></code><button type="button" data-izzy-copy="izzy-account-api-token-url" aria-label="Copiar URL de token"><i class="far fa-copy"></i> Copiar</button></div>
        <div class="izzy-account-api-endpoint"><span><b>GET</b> Verificar integración</span><code id="izzy-account-api-me-url"></code><button type="button" data-izzy-copy="izzy-account-api-me-url" aria-label="Copiar URL de verificación"><i class="far fa-copy"></i> Copiar</button></div>
        <div class="izzy-account-api-example"><div><strong>Ejemplo para obtener token</strong><button type="button" data-izzy-copy="izzy-account-api-json"><i class="far fa-copy"></i> Copiar JSON</button></div><pre id="izzy-account-api-json">{
  "client_id": "SU_CLIENT_ID",
  "client_secret": "SU_CLIENT_SECRET"
}</pre><small>Encabezado: Content-Type: application/json. Para verificar la integración, envíe Authorization: Bearer SU_ACCESS_TOKEN.</small></div>
        <div class="izzy-account-api-connection-map"><h4><i class="fas fa-project-diagram"></i> Cómo se comunicarán los módulos</h4><p>Una vez publicadas las rutas correspondientes, cada petición llevará el Bearer de la integración y el servidor identificará automáticamente la empresa. El sistema externo no podrá seleccionar otra base de datos.</p><div><span><i class="fas fa-users"></i> Clientes</span><strong>GET consultar · POST crear · PUT actualizar</strong><small>Rutas pendientes</small></div><div><span><i class="fas fa-box"></i> Productos</span><strong>GET consultar · POST crear · PUT actualizar</strong><small>Rutas pendientes</small></div><div><span><i class="fas fa-truck"></i> Proveedores</span><strong>GET consultar · POST crear · PUT actualizar</strong><small>Rutas pendientes</small></div><div><span><i class="fas fa-file-invoice"></i> Facturas</span><strong>GET consultar · POST emitir</strong><small>Rutas pendientes; no se modifican facturas emitidas</small></div></div>
        <div class="izzy-account-api-warning"><i class="fas fa-info-circle"></i> Las rutas para consultar clientes, productos, proveedores y facturas están previstas para próximas fases. Esta pantalla no las presenta como disponibles todavía.</div>
      </details>
      </div>
      <div id="izzy-account-api-credentials" hidden><strong>Guarde estas credenciales ahora; el secreto se mostrará una sola vez.</strong><label>Client ID</label><code id="izzy-account-client-id"></code><label>Client Secret</label><code id="izzy-account-client-secret"></code><button type="button" class="btn btn-primary" id="izzy-account-api-credentials-close"><i class="fas fa-check"></i> Entendido</button></div>
    </div>
    <footer><span class="izzy-account-api-footer-caption"><i class="fas fa-shield-alt"></i> IZZY · Integraciones seguras por empresa</span><button type="button" class="btn btn-secondary izzy-account-api-close"><i class="fas fa-times"></i> Cerrar</button></footer>
  </section>
</div>
<script src="<?php echo htmlspecialchars(SERVERURL,ENT_QUOTES,'UTF-8'); ?>ajax/js/miCuentaApi.js?v=1" defer></script>

<!-- Centro de Ayuda: informativo, no requiere cambios en tablas ni permisos. -->
<div class="modal fade izzy-help-dialog" id="izzy-help-center-modal" tabindex="-1" role="dialog" aria-labelledby="izzy-help-title" aria-hidden="true" data-izzy-payment-readonly-allowed="1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="izzy-help-title"><i class="fas fa-life-ring mr-2"></i>Centro de Ayuda de IZZY</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
      <div class="modal-body">
        <p class="text-muted">Busque respuestas sobre el uso de IZZY. Esta guía inicial es informativa; las opciones disponibles dependen de su plan y permisos.</p>
        <input type="search" id="izzy-help-search" class="izzy-help-search" placeholder="Buscar ayuda: facturas, caja, inventario..." aria-label="Buscar artículos de ayuda">
        <div id="izzy-help-articles">
          <article class="izzy-help-topic"><strong><i class="fas fa-file-invoice mr-2"></i>Ventas y facturación</strong><p>Acceda a Ventas para gestionar documentos. Consulte sus reportes según los permisos disponibles. Las fechas de emisión de facturas no deben alterarse después de emitirlas.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-cash-register mr-2"></i>Caja y cobros</strong><p>Antes de facturar, revise la apertura de caja y los métodos de pago configurados. Las funciones disponibles dependen del perfil asignado.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-boxes mr-2"></i>Productos e inventario</strong><p>Desde los módulos autorizados puede consultar productos, existencias y movimientos de almacén.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-users mr-2"></i>Clientes y proveedores</strong><p>Registre los datos de contacto e identificación. Use la edición para actualizar información existente de forma controlada.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-file-invoice-dollar mr-2"></i>Facturación de su suscripción</strong><p>Abra Detalles de Facturación desde el menú del usuario para consultar documentos y pagos. El plazo habitual de pago es hasta el día 15.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-shield-alt mr-2"></i>Usuarios y seguridad</strong><p>Utilice Mi Perfil para revisar sus datos y Modificar Contraseña para actualizar su acceso. No comparta sus credenciales.</p></article>
          <article class="izzy-help-topic"><strong><i class="fas fa-headset mr-2"></i>Soporte técnico</strong><p>¿Necesita ayuda? Contacte a ES MULTISERVICIOS por WhatsApp. Describa el módulo y el mensaje mostrado, sin enviar contraseñas.</p></article>
        </div>
      </div>
      <div class="modal-footer"><a href="https://wa.me/50489136844" target="_blank" rel="noopener noreferrer" class="btn btn-success" data-izzy-payment-readonly-allowed="1"><i class="fab fa-whatsapp mr-1"></i> Solicitar soporte</a><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-2" aria-hidden="true"></i>Cerrar</button></div>
    </div>
  </div>
</div>
<div class="modal fade izzy-history-dialog" id="izzy-notifications-modal" tabindex="-1" role="dialog" aria-labelledby="izzy-notifications-title" aria-hidden="true" data-izzy-payment-readonly-allowed="1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="izzy-notifications-title"><i class="fas fa-bell mr-2"></i>Todas las notificaciones</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body"><p class="text-muted small">Avisos de facturación disponibles en esta sesión. El historial persistente se incorporará cuando se conecte la tabla de notificaciones.</p><div id="izzy-all-notification-items"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-2" aria-hidden="true"></i>Cerrar</button></div>
  </div></div>
</div>
