<script>
<?php
/*
 * Fallback seguro para variables usadas en los reportes IIS.
 * Evita warnings cuando configGenerales.php no dejó cargado $GLOBALS['SISTEMA_PRUEBA']
 * o $GLOBALS['db'] antes de imprimir este main.php.
 */
$IZZY_DB_JS = '';
if (isset($GLOBALS['db']) && trim((string)$GLOBALS['db']) !== '') {
    $IZZY_DB_JS = trim((string)$GLOBALS['db']);
} elseif (isset($_SESSION['db_cliente']) && trim((string)$_SESSION['db_cliente']) !== '') {
    $IZZY_DB_JS = trim((string)$_SESSION['db_cliente']);
} elseif (defined('DB_MAIN')) {
    $IZZY_DB_JS = DB_MAIN;
}

$IZZY_DB_MAIN_JS = defined('DB_MAIN') ? DB_MAIN : $IZZY_DB_JS;

$IZZY_SISTEMA_PRUEBA_JS = 'NO';
if (isset($GLOBALS['SISTEMA_PRUEBA']) && trim((string)$GLOBALS['SISTEMA_PRUEBA']) !== '') {
    $IZZY_SISTEMA_PRUEBA_JS = trim((string)$GLOBALS['SISTEMA_PRUEBA']);
} elseif (defined('SISTEMA_PRUEBA')) {
    $IZZY_SISTEMA_PRUEBA_JS = SISTEMA_PRUEBA;
}

$IZZY_DB_JS = htmlspecialchars((string)$IZZY_DB_JS, ENT_QUOTES, 'UTF-8');
$IZZY_DB_MAIN_JS = htmlspecialchars((string)$IZZY_DB_MAIN_JS, ENT_QUOTES, 'UTF-8');
$IZZY_SISTEMA_PRUEBA_JS = htmlspecialchars((string)$IZZY_SISTEMA_PRUEBA_JS, ENT_QUOTES, 'UTF-8');
?>

//main.php
var DB_MAIN = "<?php echo $IZZY_DB_MAIN_JS; ?>";

// LLAMAMOS EL MÉTODO QUE IDENTIFICA EL USUARIO QUE HA INICIADO SESIÓN
getUserSessionStart();
// LLAMAMOS LOS MÉTODOS CORRESPONDIENTES A LOS MENÚS
getGithubVersion();
// getImagenHeader();
getPlanes();
getSistemas();

validarAperturaCajaUsuario();
getCollaboradoresModalPagoFacturas();

// LLAMAMOS LOS MÉTODOS QUE OBTIENEN LOS PERMISOS DE LOS USUARIOS PARA LOS ACCESOS
getPermisosTipoUsuarioAccesosForms(getPrivilegioTipoUsuario());
getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

getAlmacen();
getMedida();
getTipoProducto();
getEmpresaProductos();
getProductos();
getCategoriaProductos();
getEmpresaColaboradores();
getPuestoColaboradores();
getCollaboradoresModalPagoFacturasCompras();
getClientesCXC();
getProveedoresCXP();

function init() {
  // Activa selectpicker y tooltips de inmediato
  if (window.jQuery && $.fn && $.fn.selectpicker) {
    $('.selectpicker').selectpicker();
  }
  if (window.jQuery && $.fn && $.fn.tooltip) {
    $('[data-toggle="tooltip"]').tooltip();
  }

  // ❌ NO usar DOMContentLoaded aquí: ya ocurrió
  // ✅ Fijar valores por defecto AHORA y refrescar selectpicker

  // Pagar Proveedores > Estado = 1
  var $estadoPP = $('#form_main_pagar_proveedores #pagar_proveedores_estado');
  if ($estadoPP.length) {
    $estadoPP.val('1');
    if ($estadoPP.selectpicker) $estadoPP.selectpicker('refresh');
  }

  // Cuentas por Cobrar Clientes > Estado = 1
  var $estadoCXC = $('#form_main_cobrar_clientes #main_cobrar_clientes_estado');
  if ($estadoCXC.length) {
    $estadoCXC.val('1');
    if ($estadoCXC.selectpicker) $estadoCXC.selectpicker('refresh');
  }

  // El resto de tu inicialización global
  aplicar();
}

<!-- Responsive menú: móvil muestra #facturaMovil; escritorio muestra #facturas/#facturaCompras/#cotizacion -->
(function () {
  // ===== CONFIG =====
  const BREAKPOINT_DESKTOP = 992;      // lg en Bootstrap 4.6
  const MAX_TABLET_WIDTH   = 1366;     // iPad Pro 12.9 en landscape
  const MAX_TABLET_HEIGHT  = 900;      // altura típica tablet en landscape

  const menuItems = {
    movil: ['facturaMovil'],
    escritorio: ['facturas', 'facturaCompras', 'cotizacion']
  };

  // ====== UTILS ======
  function getViewportSize(){
    return {
      w: Math.max(document.documentElement.clientWidth,  window.innerWidth  || 0),
      h: Math.max(document.documentElement.clientHeight, window.innerHeight || 0)
    };
  }
  function isCoarsePointer(){
    // true en la mayoría de tablets/teléfonos
    return window.matchMedia && matchMedia('(pointer: coarse)').matches;
  }
  function isLandscape(w, h){ return w > h; }

  // Detecta "tablet en landscape" para forzar vista móvil de factura
  function isTabletLandscape(w, h){
    // Heurística: dispositivo de puntero "grueso", apaisado y dentro de rangos de tablet
    return isCoarsePointer() && isLandscape(w, h) &&
           w <= MAX_TABLET_WIDTH && h <= MAX_TABLET_HEIGHT;
  }

  function setVisible(id, visible) {
    const el = document.getElementById(id);
    if (!el) return;

    if (visible) {
      // Quitar ocultamientos previos
      el.style.display = '';
      el.classList.remove('hidden-by-responsive', 'perm-hidden', 'ocultar', 'd-none');

      // Si algún padre quedó oculto por permisos/estilos, destaparlo también
      const parentHidden = el.closest('.perm-hidden, .d-none, .ocultar');
      if (parentHidden) {
        parentHidden.classList.remove('perm-hidden', 'd-none', 'ocultar');
        if (parentHidden.style && parentHidden.style.display === 'none') {
          parentHidden.style.display = '';
        }
      }
    } else {
      el.style.display = 'none';
      el.classList.add('hidden-by-responsive');
    }
  }

  function applyResponsive() {
    const { w, h } = getViewportSize();

    // Regla base: móviles/tablets (ancho < 992) → facturaMovil
    // Excepción/plus: tablet en landscape → también facturaMovil
    const smallOrTabletLandscape = (w < BREAKPOINT_DESKTOP) || isTabletLandscape(w, h);

    const show = smallOrTabletLandscape ? menuItems.movil      : menuItems.escritorio;
    const hide = smallOrTabletLandscape ? menuItems.escritorio : menuItems.movil;

    show.forEach(id => setVisible(id, true));
    hide.forEach(id => setVisible(id, false));
  }

  function debounce(fn, wait = 200) {
    let t;
    return () => { clearTimeout(t); t = setTimeout(fn, wait); };
  }

  function startResponsive() {
    applyResponsive();
    // Ajusta al rotar o cambiar tamaño/zoom
    window.addEventListener('resize',          debounce(applyResponsive, 200));
    window.addEventListener('orientationchange', debounce(applyResponsive, 200));
    // iPadOS a veces no dispara bien orientationchange; este extra ayuda
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) applyResponsive();
    });
  }

  // Exportar para re-ejecutar cuando cargues permisos/menú dinámico
  window.applyResponsive = applyResponsive;

  // Iniciar cuando el DOM esté listo (sin jQuery/document.ready)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startResponsive);
  } else {
    startResponsive();
  }
})();

// Ejecutar al cargar
actualizarPermisos();

// Actualizar permisos cada 5 minutos
setInterval(actualizarPermisos, 300000); // 300000 ms = 5 minutos

let renovar = false;
let tiempoRestante = 0;

async function mostrarNotificacionRenovacion(tiempoRestante) {
    return new Promise((resolve) => {
        swal({
            title: "Renovar Sesión",
            text: `Tu sesión está a punto de vencer. Tiempo restante: ${tiempoRestante} minutos. ¿Deseas renovarla?`,
            icon: "info",
            buttons: {
                cancel: "Cancelar",
                confirm: {
                    text: "Renovar",
                    closeModal: false,
                },
            },
            closeOnEsc: false,
            closeOnClickOutside: false            
        }).then(async (value) => {
            if (value) {
                // Si el usuario elige renovar
                const renovacionExitosa = await renovarSesion();
                if (renovacionExitosa) {
                    // Solo actualizamos la bitácora si la renovación fue exitosa
                    await actualizarBitacora();
                }
            }
            resolve(value);
        });
    });
}

async function renovarSesion() {
    try {
        const response = await fetch('<?php echo SERVERURL;?>core/renovar_sesion.php');
        const data = await response.json();

        if (data.success) {
            // La renovación fue exitosa, actualizar el tiempo restante
            tiempoRestante = data.tiempoSesion;
            // Mostrar notificación de éxito
            await showNotify('success', 'Sesión renovada', 'Tu sesión ha sido renovada exitosamente');
            return true;
        } else {
            // Mostrar notificación de error
            await showNotify('error', 'Error', 'No se pudo renovar la sesión: ' + (data.message || 'Error desconocido'));
            return false;
        }
    } catch (error) {        
        await showNotify('error', 'Error', 'No se pudo conectar al servidor para renovar la sesión');
        return false;
    }
}

async function mostrarNotificacionExpiracion() {
    swal({
        title: "Sesión Expirada",
        text: "Su sesión ha expirado. Serás redirigido a la página de inicio de sesión.",
        icon: "warning",
        buttons: {
            confirm: {
                text: "Aceptar",
                closeModal: false,
            },
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false        
    }).then(async () => {
        // Actualizar la bitácora antes de redirigir
        await actualizarBitacora();
        // Redirigir al usuario a la página de inicio de sesión
        window.location.href = '<?php echo SERVERURL;?>';
    });
}

async function actualizarBitacora() {
    try {
        const codigo_bitacora = localStorage.getItem('codigo_bitacora_sd');
        const hora_salida = new Date().toLocaleTimeString();
        
        const response = await $.ajax({
            url: '<?php echo SERVERURL; ?>core/actualizarBitacora.php',
            type: 'POST',
            data: {
                codigo_bitacora: codigo_bitacora,
                hora_salida: hora_salida
            },
            dataType: 'json'
        });

        if (!response.success) {            
            await showNotify('warning', 'Advertencia', 'No se pudo actualizar el registro de la bitácora');
        }
    } catch (error) {        
        await showNotify('error', 'Error', 'Error al intentar actualizar la bitácora');
    }
}

async function validarSesion() {
    const response = await fetch('<?php echo SERVERURL;?>core/verificar_sesion.php?renovar=' + renovar.toString());
    const data = await response.json();

    if (data.estado === 'expired') {
        mostrarNotificacionExpiracion();
    } else if (data.estado === 'show_notification') {
        const isRenewed = await mostrarNotificacionRenovacion(data.tiempoRestante);

        if (isRenewed) {
            renovar = true;
            // Llamar a renovarSesion solo si la renovación no está en curso
            if (!data.renovar) {
                renovarSesion();
            }
        }
    }
}

//IICIO MENUS
function getPermisosTipoUsuarioAccesosTable(privilegio_id) {
    var url = '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'permisos_tipo_user_id=' + privilegio_id,
        success: function(registro) {
            valores_tipoUsuarioAccesos = JSON.parse(registro);

            try {
                for (var i = 0; i < valores_tipoUsuarioAccesos.length; i++) {
                    if (valores_tipoUsuarioAccesos[i].estado == 1) {
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', '');
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                    } else {
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', 'display: none;');
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {
               
            }
        },
        error: function(xhr, status, error) {
            
        }
    });
}

function getPermisosTipoUsuarioAccesosForms(privilegio_id) {
    var url = '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'permisos_tipo_user_id=' + privilegio_id,
        success: function(registro) {
            valores_tipoUsuarioAccesos = JSON.parse(registro);

            try {
                for (var i = 0; i < valores_tipoUsuarioAccesos.length; i++) {
                    if (valores_tipoUsuarioAccesos[i].estado == 1) {
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', '');
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                    } else {
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', 'display: none;');
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {
                
            }
        },
        error: function(xhr, status, error) {
          
        }
    });
}

function getPermisosTipoUsuarioAccesosTableAccion(privilegio_id, tipo) {
    var url = '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'permisos_tipo_user_id=' + privilegio_id,
        success: function(registro) {
            valores_tipoUsuarioAccesos = JSON.parse(registro);

            try {
                for (var i = 0; i < valores_tipoUsuarioAccesos.length; i++) {
                    if (valores_tipoUsuarioAccesos[i].estado == 1) {
                        if (valores_tipoUsuarioAccesos[i].tipo_permiso == tipo) {
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', '');
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                        } else {
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', 'display: none;');
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                        }
                    } else {
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr('style', 'display: none;');
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {
                
            }
        },
        error: function(xhr, status, error) {
           
        }
    });
}

// --- 1) Ocultar TODO (sidebar + topbar) y limpiar display inline
function ocultarTodoNavbar() {
  $('#sidenavAccordion .link, .sb-topnav .link')
    .addClass('perm-hidden')
    .removeAttr('style'); // elimina display:none inline inicial
}

// --- 2) Aplica visibilidad por id o clase (en cualquier zona del DOM)
function aplicarVisibilidad(filas, keyNombre) {
  if (!Array.isArray(filas)) return;

  for (let i = 0; i < filas.length; i++) {
    const row = filas[i] || {};
    const nombre = row[keyNombre];
    if (!nombre) continue;

    const okAcceso = Number(row.estado) === 1;
    const estadoPlan = row.estado_menu_plan ?? row.estado_submenu_plan ?? row.estado_submenu1_plan;
    const okPlan = (estadoPlan === undefined) ? true : Number(estadoPlan) === 1;

    const selId  = '#' + nombre;  // coincide con id="reporteVentas"
    const selCls = '.' + nombre;  // coincide con class="reporteVentas"

    if ($(selId).length === 0 && $(selCls).length === 0) {
      
      continue;
    }

    const mostrar = okAcceso && okPlan;
    $(selId).toggleClass('perm-hidden', !mostrar);
    $(selCls).toggleClass('perm-hidden', !mostrar);
  }
}

// --- 3) Endpoints (mismo comportamiento, sólo parseo y aplicar)
function getMenu(privilegio_id) {
  return $.post('<?php echo SERVERURL;?>core/getMenuPrivilegios.php',
    { privilegio_id },
    function (registro) {
      let data = []; try { data = JSON.parse(registro || '[]'); } catch(e) {}
      aplicarVisibilidad(data, 'menu');
    }
  );
}
function getSubMenu(privilegio_id) {
  return $.post('<?php echo SERVERURL;?>core/getSubMenuPrivilegios.php',
    { privilegio_id },
    function (registro) {
      let data = []; try { data = JSON.parse(registro || '[]'); } catch(e) {}
      aplicarVisibilidad(data, 'submenu');
    }
  );
}
function getSubMenu1(privilegio_id) {
  return $.post('<?php echo SERVERURL;?>core/getSubMenuPrivilegios1.php',
    { privilegio_id },
    function (registro) {
      let data = []; try { data = JSON.parse(registro || '[]'); } catch(e) {}
      aplicarVisibilidad(data, 'submenu1');
    }
  );
}

// --- 4) Orquestador (cubre sidebar + topbar)
function actualizarPermisos() {
  const privilegio_id = (typeof getPrivilegioUsuario === 'function')
    ? getPrivilegioUsuario()
    : <?php echo (int)($_SESSION['privilegio_id'] ?? 0); ?>;

  // Evita flash en ambos navs y limpia estados
  $('#sidenavAccordion, .sb-topnav').addClass('nav-loading');
  ocultarTodoNavbar();

  $.when(
    getMenu(privilegio_id),
    getSubMenu(privilegio_id),
    getSubMenu1(privilegio_id)
  ).always(function () {
    $('#sidenavAccordion, .sb-topnav').removeClass('nav-loading');
  });
}

function getPrivilegioUsuario() {
    var url = '<?php echo SERVERURL;?>core/getPrivilegioUsuario.php';
    var privilegio = null;

    $.ajax({
        type: 'POST',
        url: url,
        async: false, // ⚠️ Bloquea la ejecución hasta recibir la respuesta
        success: function(valores) {
            var datos = JSON.parse(valores); // Asegurar que se parsea correctamente

            if (datos.error === "session_expired") {
                swal({
                    title: "⏳ Sesión Expirada",
                    text: "😞 ¡Oh no! Tu sesión ha expirado. Por favor, inicia sesión nuevamente. 🔐",
                    icon: "warning",
                    buttons: {
                        confirm: {
                            text: "🔄 Iniciar Sesión",
                            closeModal: true,
                        },
                    },
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                }).then(() => {
                    window.location.href = "<?php echo SERVERURL;?>login/";
                });

                return;
            }

            privilegio = datos[0]; // Asigna el privilegio
        },
        error: function(xhr, status, error) {     
            swal({
                title: "❌ ¡Error Detectado!",
                text: "😵‍💫 Algo salió mal al procesar la solicitud. Inténtalo de nuevo más tarde. 🛠️",
                icon: "error",
                buttons: {
                    confirm: {
                        text: "😓 Cerrar",
                        closeModal: true,
                    },
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            });
        }
    });

    return privilegio; // Devuelve el privilegio directamente
}

function getSessionUser() {
    var url = '<?php echo SERVERURL;?>core/getSessionUser.php';
    var db_cliente;
    $.ajax({
        type: 'POST',
        url: url,
        dataType: 'json', // jQuery automáticamente parsea el JSON
        async: false,
        success: function(datos) {
            // Ya no necesitas parsear, 'datos' ya es un array
            db_cliente = datos[0];
        },
        error: function(xhr, status, error) {
  
        }
    });
    return db_cliente;
}

function getPrivilegioTipoUsuario() {
    var url = '<?php echo SERVERURL;?>core/getPrivilegioUsuarioTipo.php';
    var privilegio;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(valores) {
            var datos = eval(valores);
            privilegio = datos[0];
        }
    });
    return privilegio;
}
//FIN MENUS

//INICIO OBTETNER EL NOMBRE DE USUARIO QUE INICIO SESIÓN
function getUserSessionStart() {
    var url = '<?php echo SERVERURL;?>core/getUserSession.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#user_session').html(data);
        }
    });
}
//FIN OBTETNER EL NOMBRE DE USUARIO QUE INICIO SESIÓN

//INICIO VALORES PARA DATATABLE
//INICIO IDIOMA
var idioma_español = {
    "processing": "Procesando...",
    "lengthMenu": "Mostrar _MENU_ registros",
    "zeroRecords": "No se encontraron resultados",
    "emptyTable": "Ningún dato disponible en esta tabla",
    "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
    "infoFiltered": "(filtrado de un total de _MAX_ registros)",
    "search": "Buscar:",
    "infoThousands": ",",
    "loadingRecords": "Cargando...",
    "paginate": {
        "first": "Primero",
        "last": "Último",
        "next": "Siguiente",
        "previous": "Anterior"
    },
    "aria": {
        "sortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sortDescending": ": Activar para ordenar la columna de manera descendente"
    },
    "buttons": {
        "copy": "Copiar",
        "colvis": "Visibilidad",
        "collection": "Colección",
        "colvisRestore": "Restaurar visibilidad",
        "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles del sistema. <br \/> <br \/> Para cancelar, haga clic en este mensaje o presione escape.",
        "copySuccess": {
            "1": "Copiada 1 fila al portapapeles",
            "_": "Copiadas %d fila al portapapeles"
        },
        "copyTitle": "Copiar al portapapeles",
        "csv": "CSV",
        "excel": "Excel",
        "pageLength": {
            "-1": "Mostrar todas las filas",
            "1": "Mostrar 1 fila",
            "_": "Mostrar %d filas"
        },
        "pdf": "PDF",
        "print": "Imprimir"
    },
    "autoFill": {
        "cancel": "Cancelar",
        "fill": "Rellene todas las celdas con <i>%d<\/i>",
        "fillHorizontal": "Rellenar celdas horizontalmente",
        "fillVertical": "Rellenar celdas verticalmentemente"
    },
    "decimal": ",",
    "searchBuilder": {
        "add": "Añadir condición",
        "button": {
            "0": "Constructor de búsqueda",
            "_": "Constructor de búsqueda (%d)"
        },
        "clearAll": "Borrar todo",
        "condition": "Condición",
        "conditions": {
            "date": {
                "after": "Despues",
                "before": "Antes",
                "between": "Entre",
                "empty": "Vacío",
                "equals": "Igual a",
                "not": "No",
                "notBetween": "No entre",
                "notEmpty": "No Vacio"
            },
            "moment": {
                "after": "Despues",
                "before": "Antes",
                "between": "Entre",
                "empty": "Vacío",
                "equals": "Igual a",
                "not": "No",
                "notBetween": "No entre",
                "notEmpty": "No vacio"
            },
            "number": {
                "between": "Entre",
                "empty": "Vacio",
                "equals": "Igual a",
                "gt": "Mayor a",
                "gte": "Mayor o igual a",
                "lt": "Menor que",
                "lte": "Menor o igual que",
                "not": "No",
                "notBetween": "No entre",
                "notEmpty": "No vacío"
            },
            "string": {
                "contains": "Contiene",
                "empty": "Vacío",
                "endsWith": "Termina en",
                "equals": "Igual a",
                "not": "No",
                "notEmpty": "No Vacio",
                "startsWith": "Empieza con"
            }
        },
        "data": "Data",
        "deleteTitle": "Eliminar regla de filtrado",
        "leftTitle": "Criterios anulados",
        "logicAnd": "Y",
        "logicOr": "O",
        "rightTitle": "Criterios de sangría",
        "title": {
            "0": "Constructor de búsqueda",
            "_": "Constructor de búsqueda (%d)"
        },
        "value": "Valor"
    },
    "searchPanes": {
        "clearMessage": "Borrar todo",
        "collapse": {
            "0": "Paneles de búsqueda",
            "_": "Paneles de búsqueda (%d)"
        },
        "count": "{total}",
        "countFiltered": "{shown} ({total})",
        "emptyPanes": "Sin paneles de búsqueda",
        "loadMessage": "Cargando paneles de búsqueda",
        "title": "Filtros Activos - %d"
    },
    "select": {
        "1": "%d fila seleccionada",
        "_": "%d filas seleccionadas",
        "cells": {
            "1": "1 celda seleccionada",
            "_": "$d celdas seleccionadas"
        },
        "columns": {
            "1": "1 columna seleccionada",
            "_": "%d columnas seleccionadas"
        }
    },
    "thousands": "."
}
//FIN IDIOMA

//INICIO CONVETIR IMAGEN BASE 64
function toDataURL(src, callback, outputFormat) {
    var img = new Image();
    img.crossOrigin = 'Anonymous';
    img.onload = function() {
        var canvas = document.createElement('CANVAS');
        var ctx = canvas.getContext('2d');
        var dataURL;
        canvas.height = this.naturalHeight;
        canvas.width = this.naturalWidth;
        ctx.drawImage(this, 0, 0);
        dataURL = canvas.toDataURL(outputFormat);
        callback(dataURL);
    };
    img.src = src;
    if (img.complete || img.complete === undefined) {
        img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
        img.src = src;
    }
}
//FIN CONVERTIR IMAGEN BASE 64

var lengthMenu = [
    [5, 10, 20, 30, 50, 100, -1],
    [5, 10, 20, 30, 50, 100, "Todo"]
];
var lengthMenu10 = [
    [10, 20, 30, 50, 100, -1],
    [10, 20, 30, 50, 100, "Todo"]
];
var lengthMenu20 = [
    [20, 30, 50, 100, -1],
    [20, 30, 50, 100, "Todo"]
];

var dom = "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
    "<'row'<'col-sm-12'tr>>" +
    "<'row'<'col-sm-5'i><'col-sm-7'p>>";
//FIN VALORES PARA DATATABLE

//INICIO CONSUMIDOR FINAL PARA COTIZACION Y FACTURACION
function getConsumidorFinal() {
    var url = '<?php echo SERVERURL;?>core/getConsumidorFinal.php';

    $.ajax({
        type: 'POST',
        url: url,
        success: function(valores) {
            var datos = eval(valores);
            $('#invoice-form #cliente_id').val(datos[0]);
            $('#invoice-form #cliente').val(datos[1]);
            $('#invoice-form #client-customers-bill').html("<b>Cliente:</b> " + datos[1]);
            $('#invoice-form #rtn-customers-bill').html("<b>RTN:</b> " + datos[2]);

            $('#quoteForm #cliente_id').val(datos[0]);
            $('#quoteForm #cliente').val(datos[1]);
            $('#quoteForm #client-customers-quote').html("<b>Cliente:</b> " + datos[1]);
            $('#quoteForm #rtn-customers-quote').html("<b>RTN:</b> " + datos[2]);
            return false;
        }
    });
}

function getCajero(setAlways = false) {
  var url = '<?php echo SERVERURL;?>core/getCajero.php';

  $.ajax({
    type: 'POST',
    url: url,
    dataType: 'json'
  })
  .done(function(datos){
    // Soporta array [id, nombre] o objeto {colaboradores_id, colaborador}
    var id  = Array.isArray(datos) ? datos[0] : (datos.colaboradores_id || '');
    var nom = Array.isArray(datos) ? datos[1] : (datos.colaborador || '');
    if (!id || !nom) return;

    /* ===== FACTURAS ===== */
    var emptyInvoiceSeller = !$('#invoice-form #colaborador_id').val() || !$('#invoice-form #colaborador').val();
    if (setAlways || emptyInvoiceSeller) {
      $('#invoice-form #colaborador_id').val(id);
      $('#invoice-form #colaborador').val(nom);
      // Encabezado visible (si existe)
      var $hdrBill = $('#invoice-form #vendedor-customers-bill');
      if ($hdrBill.length) $hdrBill.html('<b>Vendedor: </b>' + nom);
    }

    /* ===== COTIZACIONES ===== */
    var emptyQuoteSeller = !$('#quoteForm #colaborador_id').val() || !$('#quoteForm #colaborador').val();
    if (setAlways || emptyQuoteSeller) {
      $('#quoteForm #colaborador_id').val(id);
      $('#quoteForm #colaborador').val(nom);
      // Encabezado visible en cotización (usa cualquiera que tengas en el HTML)
      var $hdrQuote = $('#quoteForm #vendedor-customers-quote, #quoteForm #vendedor-customers-bill');
      if ($hdrQuote.length) $hdrQuote.html('<b>Vendedor: </b>' + nom);
    }

    // Apertura de caja (como ya lo tenías)
    $('#formAperturaCaja #colaboradores_id_apertura').val(id);
    $('#formAperturaCaja #usuario_apertura').val(nom);
  })
  .fail(function(xhr){
    return false;
  });
}

/* ============================================================
   ÚNICA función para resolver % ISV desde la BD según flags
   - isv2Flag=1  => consulta isv_id=2 (18%)
   - else isv1=1 => consulta isv_id=1 (15%)
   Devuelve: { id: 0|1|2, valor: number }  (valor en %)
============================================================ */
function getPorcentajeISV(isv1Flag, isv2Flag) {
    try {
        var isv1 = parseInt(isv1Flag, 10) === 1;
        var isv2 = parseInt(isv2Flag, 10) === 1;
        var id = 0;

        if (isv2)      id = 2;
        else if (isv1) id = 1;

        if (id === 0) return { id: 0, valor: 0 };

        var resp = { id: id, valor: 0 };
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>core/facturas/getISVValor.php',
            data: { isv_id: id },
            async: false,
            success: function(r){
                try{
                    var j = (typeof r === 'string') ? JSON.parse(r) : r;
                    resp.id    = parseInt(j.id || 0, 10);
                    resp.valor = parseFloat(j.valor || 0);
                }catch(_){}
            }
        });
        return resp;
    } catch(_) {
        return { id: 0, valor: 0 };
    }
}

/* ============================
   Cache y fetch de % ISV 1/2
   ============================ */
const ISV_CACHE = {
  1: { valor: 0, activar: 0, loaded: false }, // ejemplo 15
  2: { valor: 0, activar: 0, loaded: false }  // ejemplo 18
};

// Devuelve Promise<number> con el % (0..100)
function fetchISVPercent(isv_id){
  return new Promise((resolve) => {
    if (ISV_CACHE[isv_id] && ISV_CACHE[isv_id].loaded){
      resolve(ISV_CACHE[isv_id].activar === 1 ? ISV_CACHE[isv_id].valor : 0);
      return;
    }
    $.ajax({
      url: "<?php echo SERVERURL; ?>core/facturas/getISVValor.php",
      type: "POST",
      dataType: "json",
      data: { isv_id },
      success: function(r){
        const val = parseFloat(r && r.valor ? r.valor : 0);
        const act = parseInt(r && r.activar ? r.activar : 0, 10);
        ISV_CACHE[isv_id] = { valor: isNaN(val)?0:val, activar: act===1?1:0, loaded:true };
        resolve(ISV_CACHE[isv_id].activar === 1 ? ISV_CACHE[isv_id].valor : 0);
      },
      error: function(){
        ISV_CACHE[isv_id] = { valor:0, activar:0, loaded:true };
        resolve(0);
      }
    });
  });
}

// (opcional) precarga ambos una vez
(function prefetchISVs(){ Promise.all([fetchISVPercent(1), fetchISVPercent(2)]); })();

function getPorcentajeISV(documento) {
    var url = '<?php echo SERVERURL;?>core/getISV.php';

    var isv;
    $.ajax({
        type: 'POST',
        url: url,
        data: 'documento=' + documento,
        async: false,
        success: function(data) {
            var datos = eval(data);
            isv = datos[0];
        }
    });
    return isv;
}

function validarISV(documento) {
    var url = '<?php echo SERVERURL;?>core/getISV.php';

    var activo;
    $.ajax({
        type: 'POST',
        url: url,
        data: 'documento=' + documento,
        async: false,
        success: function(data) {
            var datos = eval(data);
            activo = datos[1];
        }
    });
    return activo;
}

$(document).ready(function() {
    showDate();
    showTimeForm();
});

function showDate() {
    var fecha = new Date();
    $('#invoice-form #fecha-customers-bill').html("<b>Fecha:</b> " + fecha.getDate() + "/" + (fecha.getMonth() + 1) +
        "/" + fecha.getFullYear());
    $('#quoteForm #fecha-customers-quote').html("<b>Fecha:</b> " + fecha.getDate() + "/" + (fecha.getMonth() + 1) +
        "/" + fecha.getFullYear());
}

function showTimeForm() {
    myDate = new Date();
    hours = myDate.getHours();
    minutes = myDate.getMinutes();
    seconds = myDate.getSeconds();
    if (hours < 10) hours = 0 + hours;
    if (minutes < 10) minutes = "0" + minutes;
    if (seconds < 10) seconds = "0" + seconds;
    $('#invoice-form #hora-customers-bill').html("<b>Hora:</b> " + hours + ":" + minutes + ":" + seconds);
    $('#quoteForm #hora-customers-quote').html("<b>Hora:</b> " + hours + ":" + minutes + ":" + seconds);
}

document.addEventListener("DOMContentLoaded", function() {
    // Invocamos cada 1 segundos ;)
    const milisegundos = 1 * 1000;
    setInterval(function() {
        // No esperamos la respuesta de la petición porque no nos importa
        showDate();
        showTimeForm();
    }, milisegundos);
});

//FIN CONSUMIDOR FINAL PARA COTIZACION Y FACTURACION

//INICIO PRODUCTOS
/*INICIO FORMULARIO PRODUCTOS*/
function modal_registrar_productos() {
    $('#formProductos').attr({
        'data-form': 'save'
    });
    $('#formProductos').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarProductosAjax.php'
    });
    $('#formProductos')[0].reset();
    $('#reg_producto').show();
    $('#edi_producto').hide();
    $('#delete_producto').hide();

    //MOSTRAR OBJETOS
    $('#formProductos #cantidad').show();
    $('#div_cantidad_editar_producto').show();

    //HABILITAR OBJETOS
    $('#formProductos #producto').attr("readonly", false);
    $('#formProductos #categoria').attr("disabled", false);
    $('#formProductos #medida').attr("disabled", false);
    $('#formProductos #almacen').attr("disabled", false);
    $('#formProductos #cantidad').attr("readonly", false);
    $('#formProductos #precio_compra').attr("readonly", false);
    $('#formProductos #precio_venta').attr("readonly", false);
    $('#formProductos #descripcion').attr("readonly", false);
    $('#formProductos #cantidad_minima').attr("readonly", false);
    $('#formProductos #cantidad_maxima').attr("readonly", false);
    $('#formProductos #producto_isv_factura').attr("disabled", false);
    $('#formProductos #producto_isv_compra').attr("disabled", false);
    $('#formProductos #bar_code_product').attr("readonly", false);
    $('#formProductos #producto_empresa_id').attr("disabled", false);
    $('#formProductos #producto_categoria').attr("disabled", false);
    $('#formProductos #tipo_producto').attr("disabled", false);
    $('#formProductos #precio_mayoreo').attr("readonly", false);
    $('#formProductos #porcentaje_venta').attr("readonly", false);
    $('#formProductos #cantidad_mayoreo').attr("readonly", false);
    $('#formProductos #producto_isv_compra').attr('checked', false);
    $('#formProductos #cantidad').attr("disabled", false);
    $('#formProductos #producto_superior').attr("disabled", false);

    $('#formProductos #producto_empresa_id').val(1);
    $('#formProductos #producto_empresa_id').selectpicker('refresh');

    $('#formProductos #producto_categoria').val(1);
    $('#formProductos #producto_categoria').selectpicker('refresh');

    $('#formProductos #almacen').val(1);
    $('#formProductos #almacen').selectpicker('refresh');

    $('#formProductos #tipo_producto').val(1);
    $('#formProductos #tipo_producto').selectpicker('refresh');

    $('#formProductos #buscar_producto_empresa').show();
    $('#formProductos #buscar_producto_categorias').show();

    $('#formProductos #medida').val(1);
    $('#formProductos #medida').selectpicker('refresh');

    $('#formProductos #producto_activo').attr('checked', true);
    $('#formProductos #estado_producto').hide();
    $('#formProductos #grupo_editar_bacode').hide();

    if (validarISV("Facturas") == 1) {
        $('#formProductos #producto_isv_factura').attr('checked', true);
    } else {
        $('#formProductos #producto_isv_factura').attr('checked', false);
    }

    if (validarISV("Compras") == 1) {
        $('#formProductos #producto_isv_compra').attr('checked', true);
    } else {
        $('#formProductos #producto_isv_compra').attr('checked', false);
    }

    $("#formProductos #preview").attr("src", "<?php echo SERVERURLLOGO;?>/image_preview.png");

    $('#formProductos #estado_producto').hide();

    $('#modal_registrar_productos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/*FIN FORMULARIO PRODUCTOS*/
function getEmpresaProductos() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProductos #producto_empresa_id').html("");
            $('#formProductos #producto_empresa_id').html(data);
            $('#formProductos #producto_empresa_id').selectpicker('refresh');

            $('#formProductos #producto_empresa_id').val(1);
            $('#formProductos #producto_empresa_id').selectpicker('refresh');

            // Refrescar Bootstrap Select después de establecer los valores
            $('.selectpicker').selectpicker('refresh');
        }
    });
}

function getMedida(count) {
    var url = '<?php echo SERVERURL;?>core/getMedida.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProductos #medida').html("");
            $('#formProductos #medida').html(data);
            $('#formProductos #medida').selectpicker('refresh');

            $('#formProductos #medida').val(1);
            $('#formProductos #medida').selectpicker('refresh');


            $('#medidaPurchase_' + count).html(data);
            $('#medidaPurchase_' + count).selectpicker('refresh');
            $('#medida_' + count).html(data);
            $('#medida_' + count).selectpicker('refresh');

            $('#formProductos #medida').val(1);
            $('#formProductos #medida').selectpicker('refresh');

            // Refrescar Bootstrap Select después de establecer los valores
            $('.selectpicker').selectpicker('refresh');
        }
    });
}

function getAlmacen() {
    var url = '<?php echo SERVERURL;?>core/getAlmacen.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProductos #almacen').html("");
            $('#formProductos #almacen').html(data);
            $('#formProductos #almacen').selectpicker('refresh');

            $('#form_main_movimientos #almacen').html("");
            $('#form_main_movimientos #almacen').html(data);
            $('#form_main_movimientos #almacen').selectpicker('refresh');

            $('#form_main_movimientos #almacen').val(1);
            $('#form_main_movimientos #almacen').selectpicker('refresh');

            $('#formulario_busqueda_productos_facturacion #almacen_facturas').html("");
            $('#formulario_busqueda_productos_facturacion #almacen_facturas').html(data);
            $('#formulario_busqueda_productos_facturacion #almacen_facturas').selectpicker('refresh');

            $('#formulario_busqueda_productos_facturacion #almacen_facturas').val(1);
            $('#formulario_busqueda_productos_facturacion #almacen_facturas').selectpicker('refresh');+

            $('#formulario_busqueda_productos_cotizacion #almacen').html("");
            $('#formulario_busqueda_productos_cotizacion #almacen').html(data);
            $('#formulario_busqueda_productos_cotizacion #almacen').selectpicker('refresh');

            $('#formulario_busqueda_productos_cotizacion #almacen').val(1);
            $('#formulario_busqueda_productos_cotizacion #almacen').selectpicker('refresh');            

            $('#formTransferencia #id_bodega').html("");
            $('#formTransferencia #id_bodega').html(data);
            $('#formTransferencia #id_bodega').selectpicker('refresh');

            $('#formTransferencia #id_bodega').val(1);
            $('#formTransferencia #id_bodega').selectpicker('refresh');

            $('#almacen_modal').html("");
            $('#almacen_modal').html(data);
            $('#almacen_modal').selectpicker('refresh');

            $('#almacen_modal').val(1);
            $('#almacen_modal').selectpicker('refresh');

            $('#formProductos #almacen').val(1);
            $('#formProductos #almacen').selectpicker('refresh');

            // Refrescar Bootstrap Select después de establecer los valores
            $('.selectpicker').selectpicker('refresh');
        }
    });
}

function getTipoProducto() {
    var url = '<?php echo SERVERURL;?>core/getTipoProducto.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProductos #tipo_producto').html("");
            $('#formProductos #tipo_producto').html(data);
            $('#formProductos #tipo_producto').selectpicker('refresh');

            $('#formProductos #tipo_producto').val(1);
            $('#formProductos #tipo_producto').selectpicker('refresh');

            // Refrescar Bootstrap Select después de establecer los valores
            $('.selectpicker').selectpicker('refresh');
        }
    });
}

function getCategoriaProductos() {
    var url = '<?php echo SERVERURL;?>core/getCategoriaProductos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProductos #producto_categoria').html("");
            $('#formProductos #producto_categoria').html(data);
            $('#formProductos #producto_categoria').selectpicker('refresh');

            $('#formProductos #producto_categoria').val(1);
            $('#formProductos #producto_categoria').selectpicker('refresh');
        }
    });
}

function getProductos() {
    var url = '<?php echo SERVERURL;?>core/getProductos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formMovimientos #movimiento_producto').html(data);
            $('#formMovimientos #movimiento_producto').selectpicker('refresh');

            $('#formProductos #producto_superior').html(data);
            $('#formProductos #producto_superior').selectpicker('refresh');

            $('#producto_movimiento_filtro').html(data);
            $('#producto_movimiento_filtro').selectpicker('refresh');
        }
    });
}
//FIN PRODUCTOS

/*INICIO FORMULARIO PUESTO DE COLABORADORES*/
function modal_puestos(){
	  $('#formPuestos').attr({ 'data-form': 'save' });
	  $('#formPuestos').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPuestosAjax.php' });
	  $('#formPuestos')[0].reset();
	  $('#reg_puestos').show();
	  $('#edi_puestos').hide();
	  $('#delete_puestos').hide();

	  //HABILITAR OBJETOS
	  $('#formPuestos #puesto').attr('readonly', false);
	  $('#formPuestos #puestos_activo').attr('disabled', false);
	  $('#formPuestos #estado_puestos').hide();

	  $('#formPuestos #proceso_puestos').val("Registro");
	  $('#modal_registrar_puestos').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	  });

      $('#modal_registrar_puestos').off('shown.bs.modal').on('shown.bs.modal', function(){
            $(this).find('#formPuestos #puesto').focus();
      });

      // Escuchar cuando se cierra el modal (después de un registro exitoso)
    $('#modal_registrar_puestos').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            // Listener para después del cierre
            $('#modal_registrar_puestos').on('hidden.bs.modal', function () {
                if($('#formPuestos').data('success')) {
                    alert("hey haz llegado hasta aqui");
                }
            });
    });
}
/*FIN FORMULARIO PUESTO DE COLABORADORES*/

//INICIO CLIENTES
/*INICIO FORMULARIO CLIENTES*/
function modal_clientes() {
    getDepartamentoClientes();
    $('#formClientes').attr({
        'data-form': 'save'
    });
    $('#formClientes').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarClientesAjax.php'
    });
    $('#formClientes')[0].reset();
    $('#reg_cliente').show();
    $('#edi_cliente').hide();
    $('#delete_cliente').hide();
    $('#formClientes #fecha_clientes').attr('disabled', false);

    //HABILITAR OBJETOS
    $('#formClientes #nombre_clientes').attr("readonly", false);
    $('#formClientes #identidad_clientes').attr("readonly", false);
    $('#formClientes #fecha_clientes').attr("readonly", false);
    $('#formClientes #departamento_cliente').attr("disabled", false);
    $('#formClientes #municipio_cliente').attr("disabled", false);
    $('#formClientes #dirección_clientes').attr("disabled", false);
    $('#formClientes #telefono_clientes').attr("readonly", false);
    $('#formClientes #correo_clientes').attr("readonly", false);
    $('#formClientes #clientes_activo').attr("disabled", false);
    $('#formClientes #estado_clientes').hide();
    $('#formClientes #grupo_editar_rtn').hide();

    $('#formClientes #proceso_clientes').val("Registro");
    getMunicipiosClientes(0);
    $('#modal_registrar_clientes').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getDepartamentoClientes() {
    var url = '<?php echo SERVERURL;?>core/getDepartamentos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formClientes #departamento_cliente').html("");
            $('#formClientes #departamento_cliente').html(data);
            $('#formClientes #departamento_cliente').selectpicker('refresh');
        }
    });
}
/*FIN FORMULARIO CLIENTES*/
//FIN CLIENTES

// Función para cargar la configuración ISV (VERSIÓN CORREGIDA)
function cargarConfiguracionISV() {
    $.ajax({
        url: '<?php echo SERVERURL;?>core/productos/getIsvConfig.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {                
                // Procesar ISV tipo 1
                $('#producto_isv1').closest('.col-md-6').show();
                $('#producto_isv1').prop('checked', response.isv1.activar === 1);
                
                // Actualizar etiqueta con el porcentaje real desde la BD
                $('#producto_isv1').next('.custom-control-label').html(
                    '<i class="fas fa-percentage mr-1"></i>Aplica ISV ' + response.isv1.valor + '%'
                );
                
                // Procesar ISV tipo 2
                $('#producto_isv2').closest('.col-md-6').show();
                $('#producto_isv2').prop('checked', response.isv2.activar === 1);
                
                // Actualizar etiqueta con el porcentaje real desde la BD
                $('#producto_isv2').next('.custom-control-label').html(
                    '<i class="fas fa-percentage mr-1"></i>Aplica ISV ' + response.isv2.valor + '%'
                );
                
                // Guardar los valores en data attributes para uso posterior
                $('#producto_isv1').data('valor', response.isv1.valor / 100);
                $('#producto_isv2').data('valor', response.isv2.valor / 100);
                
                // Aplicar lógica de selección exclusiva automática
                aplicarSeleccionExclusivaISV();
            } else {
                // Mostrar ambos con valores por defecto en caso de error
                $('#producto_isv1').closest('.col-md-6').show();
                $('#producto_isv2').closest('.col-md-6').show();
                aplicarSeleccionExclusivaISV();
            }
        },
        error: function(xhr, status, error) {
            showNotify('warning', 'Aviso', 'No se pudo completar la acción solicitada.');
            // Mostrar ambos con valores por defecto en caso de error
            $('#producto_isv1').closest('.col-md-6').show();
            $('#producto_isv2').closest('.col-md-6').show();
            aplicarSeleccionExclusivaISV();
        }
    });
}

// Función para selección exclusiva automática de ISV
function aplicarSeleccionExclusivaISV() {
    // Cuando se selecciona ISV 1, desmarcar ISV 2 automáticamente
    $('#producto_isv1').change(function() {
        if ($(this).is(':checked')) {
            $('#producto_isv2').prop('checked', false);
        }
    });
    
    // Cuando se selecciona ISV 2, desmarcar ISV 1 automáticamente
    $('#producto_isv2').change(function() {
        if ($(this).is(':checked')) {
            $('#producto_isv1').prop('checked', false);
        }
    });
    
    // Validar que al menos uno esté seleccionado al enviar el formulario, SOLO si producto_isv_factura está activo
    $('#formProductos').submit(function(e) {
        // Verificar si el checkbox de "Calcular ISV en Factura" está activado
        var calcularISVFactura = $('#producto_isv_factura').is(':checked');
        
        // Solo validar los ISV si el checkbox de factura está activado
        if (calcularISVFactura && !$('#producto_isv1').is(':checked') && !$('#producto_isv2').is(':checked')) {
            e.preventDefault();
            
            // Obtener los porcentajes actuales de las etiquetas
            var porcentaje1 = $('#producto_isv1').next('.custom-control-label').text().match(/\d+/);
            var porcentaje2 = $('#producto_isv2').next('.custom-control-label').text().match(/\d+/);
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar al menos un tipo de ISV (' + (porcentaje1 ? porcentaje1[0] : '15') + '% o ' + (porcentaje2 ? porcentaje2[0] : '16') + '%)',
            });
            return false;
        }
        return true;
    });
}

function getPorcentajeTextoISVProducto(isv_id) {
  var porcentaje = fetchISVProductoSync(isv_id);
  var texto = formatearPorcentajeLabelISVProductos(porcentaje);

  if (texto !== '') {
    return texto + '%';
  }

  return '';
}

function getTextoISVProducto(isv_id) {
  var porcentaje = fetchISVProductoSync(isv_id);
  var texto = 'ISV';

  if (porcentaje > 0) {
    texto = 'ISV ' + formatearPorcentajeLabelISVProductos(porcentaje) + '%';
  }

  return texto;
}

function actualizarLabelsISVProductos() {
  var textoISV1 = getTextoISVProducto(1);
  var textoISV2 = getTextoISVProducto(2);

  $('#label_producto_isv1').html('Aplica ' + textoISV1);
  $('#label_producto_isv2').html('Aplica ' + textoISV2);

  $('#small_producto_isv1').html(textoISV1);
  $('#small_producto_isv2').html(textoISV2);
}

function recargarLabelsISVProductos() {
  cacheISVProductos = {};
  actualizarLabelsISVProductos();
}

/*INICIO FORMULARIO PRODUCTOS*/
function modal_productos() {
    $('#formProductos').attr({
        'data-form': 'save'
    });
    $('#formProductos').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarProductosAjax.php'
    });
    $('#formProductos')[0].reset();
    $('#reg_producto').show();
    $('#edi_producto').hide();
    $('#delete_producto').hide();

    //MOSTRAR OBJETOS
    $('#formProductos #cantidad').show();
    $('#div_cantidad_editar_producto').show();

    cargarConfiguracionISV();

    //HABILITAR OBJETOS
    $('#formProductos #producto').attr("readonly", false);
    $('#formProductos #categoria').attr("disabled", false);
    $('#formProductos #medida').attr("disabled", false);
    $('#formProductos #almacen').attr("disabled", false);
    $('#formProductos #cantidad').attr("readonly", false);
    $('#formProductos #precio_compra').attr("readonly", false);
    $('#formProductos #precio_venta').attr("readonly", false);
    $('#formProductos #descripcion').attr("readonly", false);
    $('#formProductos #cantidad_minima').attr("readonly", false);
    $('#formProductos #cantidad_maxima').attr("readonly", false);
    $('#formProductos #producto_isv_factura').attr("disabled", false);
    $('#formProductos #producto_isv_compra').attr("disabled", false);
    $('#formProductos #bar_code_product').attr("readonly", false);
    $('#formProductos #producto_empresa_id').attr("disabled", false);
    $('#formProductos #producto_categoria').attr("disabled", false);
    $('#formProductos #tipo_producto').attr("disabled", false);
    $('#formProductos #precio_mayoreo').attr("readonly", false);
    $('#formProductos #porcentaje_venta').attr("readonly", false);
    $('#formProductos #cantidad_mayoreo').attr("readonly", false);
    $('#formProductos #producto_isv_compra').attr('checked', false);
    $('#formProductos #cantidad').attr("disabled", false);
    $('#formProductos #producto_superior').attr("disabled", false);

    $('#formProductos #producto_empresa_id').val(1);
    $('#formProductos #producto_empresa_id').selectpicker('refresh');

    $('#formProductos #producto_categoria').val(1);
    $('#formProductos #producto_categoria').selectpicker('refresh');

    $('#formProductos #almacen').val(1);
    $('#formProductos #almacen').selectpicker('refresh');

    $('#formProductos #tipo_producto').val(1);
    $('#formProductos #tipo_producto').selectpicker('refresh');

    $('#formProductos #buscar_producto_empresa').show();
    $('#formProductos #buscar_producto_categorias').show();

    $('#formProductos #medida').val(1);
    $('#formProductos #medida').selectpicker('refresh');

    $('#formProductos #producto_activo').attr('checked', true);
    $('#formProductos #estado_producto').hide();
    $('#formProductos #grupo_editar_bacode').hide();

    if (validarISV("Facturas") == 1) {
        $('#formProductos #producto_isv_factura').attr('checked', true);
    } else {
        $('#formProductos #producto_isv_factura').attr('checked', false);
    }

    if (validarISV("Compras") == 1) {
        $('#formProductos #producto_isv_compra').attr('checked', true);
    } else {
        $('#formProductos #producto_isv_compra').attr('checked', false);
    }

    ClenProductImage();

    // ---- Actualiza labels ISV dinámicos antes de abrir ----
    recargarLabelsISVProductos();

    $('#formProductos #proceso_productos').val("Registro de Productos");
    $('#modal_registrar_productos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function ClenProductImage(){
    // Limpiar vistas previas de imágenes - Mismo enfoque simple que en empresas
    $('#productoPreview').html('').hide();
    $('#productoInfo').text('Ningún archivo seleccionado');
    $("#formProductos #preview").attr("src", "").hide();
}
/*FIN FORMULARIO PRODUCTOS*/

function CleanEnterpriseImage(){
    // Limpiar vistas previas de imágenes
    $('#logoPreview').html('').hide();
    $('#firmaPreview').html('').hide();
    $('#logoInfo').text('Ningún archivo seleccionado');
    $('#firmaInfo').text('Ningún archivo seleccionado');
}

//INICIO PROVEEDORES
/*INICIO FORMULARIO PROVEEDORES*/
function modal_proveedores() {
    getDepartamentoProveedores();
    $('#formProveedores').attr({
        'data-form': 'save'
    });
    $('#formProveedores').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarProveedoresAjax.php'
    });
    $('#formProveedores')[0].reset();
    $('#reg_proveedor').show();
    $('#edi_proveedor').hide();
    $('#delete_proveedor').hide();
    $('#formProveedores #fecha_proveedores').attr('disabled', false);

    //HABILITAR OBJETOS
    $('#formProveedores #nombre_proveedores').attr("readonly", false);
    $('#formProveedores #apellido_proveedores').attr("readonly", false);
    $('#formProveedores #rtn_proveedores').attr("readonly", false);
    $('#formProveedores #fecha_proveedores').attr("readonly", false);
    $('#formProveedores #departamento_proveedores').attr("disabled", false);
    $('#formProveedores #municipio_proveedores').attr("disabled", false);
    $('#formProveedores #dirección_proveedores').attr("disabled", false);
    $('#formProveedores #telefono_proveedores').attr("readonly", false);
    $('#formProveedores #correo_proveedores').attr("readonly", false);
    $('#formProveedores #proveedores_activo').attr("disabled", false);
    $('#formProveedores #estado_proveedores').hide();
    $('#formProveedores #grupo_editar_rtn').hide();

    $('#formProveedores #proceso_proveedores').val("Registro");
    $('#modal_registrar_proveedores').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getDepartamentoProveedores() {
    var url = '<?php echo SERVERURL;?>core/getDepartamentos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formProveedores #departamento_proveedores').html("");
            $('#formProveedores #departamento_proveedores').html(data);
            $('#formProveedores #departamento_proveedores').selectpicker('refresh');
        }
    });
}

function getMunicipiosProveedores(municipios_id) {
    var url = '<?php echo SERVERURL;?>core/getMunicipios.php';

    var departamentos_id = $('#formProveedores #departamento_proveedores').val();

    $.ajax({
        type: 'POST',
        url: url,
        data: 'departamentos_id=' + departamentos_id,
        success: function(data) {
            $('#formProveedores #municipio_proveedores').html("");
            $('#formProveedores #municipio_proveedores').html(data);
            $('#formProveedores #municipio_proveedores').selectpicker('refresh');
            $('#formProveedores #municipio_proveedores').val(municipios_id);
            $('#formProveedores #municipio_proveedores').selectpicker('refresh');
        }
    });
    return false;
}

$('#formProveedores #departamento_proveedores').on('change', function() {
    var url = '<?php echo SERVERURL;?>core/getMunicipios.php';

    var departamentos_id = $('#formProveedores #departamento_proveedores').val();

    $.ajax({
        type: 'POST',
        url: url,
        data: 'departamentos_id=' + departamentos_id,
        success: function(data) {
            $('#formProveedores #municipio_proveedores').html("");
            $('#formProveedores #municipio_proveedores').html(data);
            $('#formProveedores #municipio_proveedores').selectpicker('refresh');
        }
    });
    return false;
});
//FIN PROVEEDORES

//INICIO FORMULARIO CAMBIAR CONTRAEÑA
// Validación de contraseña anterior
$('#form-cambiarcontra #contranaterior').on('blur', function() {
    if ($('#form-cambiarcontra #contranaterior').val() !== "") {
        var url = '<?php echo SERVERURL; ?>core/consultar_pass.php';
        var contranaterior = $('#form-cambiarcontra #contranaterior').val();

        $.ajax({
            type: 'POST',
            url: url,
            data: {
                contranaterior: contranaterior
            },
            success: function(datos) {
                if (datos == 0) {
                    showNotify('error', 'Error', 'La contraseña que ingresó no coincide con la anterior');
                    $("#form-cambiarcontra #contranaterior").css("border-color", "red");
                    $("#form-cambiarcontra #ModalContraseñacontra_Edit").prop('disabled', true);
                } else {
                    $("#form-cambiarcontra #contranaterior").css("border-color", "green");
                    $("#form-cambiarcontra #Modalcambiarcontra_Edit").prop('disabled', false);
                }
            }
        });
    }
});

function mostrarRequisitos() {
    // Mostrar requisitos de contraseña
    $('#form-cambiarcontra #mayus').show();
    $('#form-cambiarcontra #special').show();
    $('#form-cambiarcontra #numbers').show();
    $('#form-cambiarcontra #lower').show();
    $('#form-cambiarcontra #len').show();
}

// Validación de seguridad de contraseña nueva
var nuevacontraIsEmpty = true; // Variable para controlar si nuevacontra está vacío

$('#form-cambiarcontra #nuevacontra').on('keyup', function() {
    nuevacontraIsEmpty = $(this).val().trim() === ''; // Actualiza el estado de nuevacontraIsEmpty
    verificarSeguridad();
});

// Validación de coincidencia de contraseñas
$('#form-cambiarcontra #repcontra').on('keyup', function() {
    validarCoincidencia();
});

function verificarSeguridad() {
    var pass = $('#form-cambiarcontra #nuevacontra').val();
    var check = 0;

    var regExpr = [
        /^(?=.*[A-Z])/,
        /^(?=.*[!@#$%&*¡?¿|°/\+-.:,;()~<>])/,
        /^(?=.*[0-9])/,
        /^(?=.*[a-z])/,
        /^(?=.{8,})/
    ];

    var elementos = [
        $('#form-cambiarcontra #mayus'),
        $('#form-cambiarcontra #special'),
        $('#form-cambiarcontra #numbers'),
        $('#form-cambiarcontra #lower'),
        $('#form-cambiarcontra #len')
    ];

    if (!nuevacontraIsEmpty) { // Solo valida si nuevacontra no está vacío
        for (var i = 0; i < regExpr.length; i++) {
            if (regExpr[i].test(pass)) {
                elementos[i].hide();
                check++;
            } else {
                elementos[i].show();
            }
        }

        if (check >= 0 && check <= 2) {
            $('#form-cambiarcontra #mensaje_cambiar_contra').html("<strong>Contraseña Insegura</strong>").css("color",
                "red");
            $('#form-cambiarcontra #Modalcambiarcontra_Edit').prop('disabled', true);
        } else if (check >= 3 && check <= 4) {
            $('#form-cambiarcontra #mensaje_cambiar_contra').html("<strong>Contraseña poco segura</strong>").css(
                "color", "orange");
            $('#form-cambiarcontra #Modalcambiarcontra_Edit').prop('disabled', true);
        } else if (check === 5) {
            $('#form-cambiarcontra #mensaje_cambiar_contra').html("<strong>Contraseña muy segura</strong>").css("color",
                "green");
            $('#form-cambiarcontra #Modalcambiarcontra_Edit').prop('disabled', false);
        } else {
            $('#form-cambiarcontra #mensaje_cambiar_contra').html("").css("color", "none");
            $('#form-cambiarcontra #Modalcambiarcontra_Edit').prop('disabled', true);
        }
    }
}

function validarCoincidencia() {
    var nuevacontra = $('#form-cambiarcontra #nuevacontra').val();
    var repcontra = $('#form-cambiarcontra #repcontra').val();

    if (nuevacontra !== repcontra) {
        $("#form-cambiarcontra #repcontra").css("border-color", "red");
        $("#form-cambiarcontra #Modalcambiarcontra_Edit").prop('disabled', true);
    } else if (nuevacontra === '' || repcontra === '') {
        $("#form-cambiarcontra #repcontra").css("border-color", "none");
        $("#form-cambiarcontra #Modalcambiarcontra_Edit").prop('disabled', true);
    } else {
        $("#form-cambiarcontra #repcontra").css("border-color", "green");
        $("#form-cambiarcontra #Modalcambiarcontra_Edit").prop('disabled', false);
    }
}

function limpiarForm() {
    $('#form-cambiarcontra #contranaterior').val("");
    $('#form-cambiarcontra #nuevacontra').val("");
    $('#form-cambiarcontra #repcontra').val("");
    $('#form-cambiarcontra #mensaje').html("");
    $('#form-cambiarcontra #mayus').show();
    $('#form-cambiarcontra #special').show();
    $('#form-cambiarcontra #numbers').show();
    $('#form-cambiarcontra #lower').show();
    $('#form-cambiarcontra #len').show();
    $('#form-cambiarcontra #contranaterior').focus();
    $("#form-cambiarcontra #Modalcambiarcontra_Edit").attr('disabled', true);
    $('#form-cambiarcontra #mensaje_cambiar_contra').html("");
    $("#form-cambiarcontra #contranaterior").css("border-color", "none");
    $("#form-cambiarcontra #repcontra").css("border-color", "none");
    $("#form-cambiarcontra #nuevacontra").css("border-color", "none");
}

//MOSTRAR CONTRASEÑA
$(document).ready(function() {
    //CAMPO CONTRASEÑA ANTERIOR
    $('#form-cambiarcontra #show_password1').on('mousedown', function() {
        var cambio = $("#form-cambiarcontra #contranaterior")[0];
        if (cambio.type == "password") {
            cambio.type = "text";
            $('#icon1').removeClass('fa fa-eye-slash').addClass('fa fa-eye');
        } else {
            cambio.type = "password";
            $('#icon1').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        }
        return false;
    });

    $('#form-cambiarcontra #show_password1').on('mousedown', function() {
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
        return false;
    });

    //CAMPO NUEVA CONTRASEÑA
    $('#form-cambiarcontra #show_password2').on('mousedown', function() {
        var cambio = $("#form-cambiarcontra #nuevacontra")[0];
        if (cambio.type == "password") {
            cambio.type = "text";
            $('#icon2').removeClass('fa fa-eye-slash').addClass('fa fa-eye');
        } else {
            cambio.type = "password";
            $('#icon2').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        }
        return false;
    });

    $('#form-cambiarcontra #show_password2').on('click', function() {
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
        return false;
    });

    //CAMPO REPETIR CONTRASEÑA
    $('#form-cambiarcontra #show_password3').on('click', function() {
        var cambio = $("#form-cambiarcontra #repcontra")[0];
        if (cambio.type == "password") {
            cambio.type = "text";
            $('#icon3').removeClass('fa fa-eye-slash').addClass('fa fa-eye');
        } else {
            cambio.type = "password";
            $('#icon3').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        }
        return false;
    });

    $('#form-cambiarcontra #show_password3').on('click', function() {
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
        return false;
    });

    //OCULTAR CONTRASEÑA
    $('#form-cambiarcontra #show_password1').on('mouseout', function() {
        $('#icon1').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        var cambio = $("#form-cambiarcontra #contranaterior")[0];
        cambio.type = "password";
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
        return false;
    });

    $('#form-cambiarcontra #show_password2').on('mouseout', function() {
        $('#icon2').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        var cambio = $("#form-cambiarcontra #nuevacontra")[0];
        cambio.type = "password";
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
    });

    $('#form-cambiarcontra #show_password3').on('mouseout', function() {
        $('#icon3').removeClass('fa fa-eye').addClass('fa fa-eye-slash');
        var cambio = $("#form-cambiarcontra #repcontra")[0];
        cambio.type = "password";
        $('#Password').attr('type', $(this).is(':checked') ? 'text' : 'password');
    });
});
//FIN FORMULARIO CAMBIAR CONTRASEÑA

//INICIO FUNCIONES ADICIONALES
function convertDateFormat(string) {
    if (string == null || string == "") {
        var hoy = new Date();
        string = convertDate(hoy);
    }

    var info = string.split('-');
    return info[2] + '/' + info[1] + '/' + info[0];
}

function convertDate(inputFormat) {
    function pad(s) {
        return (s < 10) ? '0' + s : s;
    }
    var d = new Date(inputFormat);
    return [d.getFullYear(), pad(d.getMonth() + 1), pad(d.getDate())].join('-');
}

function today() {
    var hoy = new Date();
    return convertDate(hoy);
}

function getMonth() {
    const hoy = new Date()
    return hoy.toLocaleString('default', {
        month: 'long'
    });
}

function getDay() {
    const hoy = new Date().getDate();
    return hoy;
}
//FIN FUNCIONES ADICIONALES

function abrirReporte(document_id, type, db) {
    // Construir la URL directamente con los parámetros
    var url = "https://wi.fastsolutionhn.com/Rpt/esmultiservicios.aspx?id=" + document_id + "&type=" + type + "&db=" + db;

    // Abrir la URL en una nueva ventana
    window.open(url, "_blank");
}

//INICIO FUNCION PARA OBTENER REPORTES DESDE IIS
/**
 * 
 * @example
 * // Ejemplo 2: Generar un reporte para usuarios específicos
 * var params = {
 *     "user_id": 456,         // ID del usuario
 *     "type": "Usuario",      // Tipo de reporte
 *     "year": 2024            // Año del reporte
 * };
 * viewReport(params);
 * 
 * @throws {Error} Si la URL del servidor no está definida o es inválida.
 * @throws {Error} Si los parámetros enviados no son un objeto válido.
 */
function viewReport_old(params) {
    var url = "<?php echo defined('SERVERURLWINDOWS') ? SERVERURLWINDOWS : ''; ?>";

    if (!url || url.trim() === "") {
        swal({
            title: "Error de conexión",
            content: {
                element: "p",
                attributes: {
                    innerHTML: "No se pudo acceder al servidor de reportes. Esto puede deberse a un problema de conexión o a que el servicio no está disponible.<br><br>📌 <b>Pasos recomendados:</b><br>1️⃣ Verifique su conexión a internet.<br>2️⃣ Intente nuevamente en unos minutos.<br>3️⃣ Si el problema persiste, comuníquese con soporte e informe el siguiente código de error: <b>SERVIDOR_NO_RESPONDE</b>."
                }
            },
            icon: "error",
            button: "Entendido",
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        });
        return;
    }

    // 📌 Intentar abrir la ventana emergente antes de la redirección para evitar bloqueos
    var reporteWindow = window.open("", "_blank");

    if (!reporteWindow || reporteWindow.closed || typeof reporteWindow.closed === "undefined") {
        swal({
            title: "⚠️ Ventana emergente bloqueada",
            content: {
                element: "p",
                attributes: {
                    innerHTML: "Tu navegador ha bloqueado la ventana emergente del reporte.<br><br>📌 <b>Cómo permitir ventanas emergentes:</b><br>🔹 <b>Google Chrome (Windows/Mac):</b> Haz clic en el ícono de la barra de direcciones (🔕 con una X), selecciona <b>Permitir siempre</b> y recarga la página.<br>🔹 <b>Microsoft Edge:</b> Ve a <b>Configuración > Cookies y permisos del sitio > Ventanas emergentes y redirecciones</b> y permite este sitio.<br>🔹 <b>Mozilla Firefox:</b> Ve a <b>Configuración > Privacidad y seguridad</b>, busca <b>Permitir ventanas emergentes</b> y agrégalo.<br>🔹 <b>Safari en iPhone:</b> Ve a <b>Ajustes > Safari</b> y desactiva <b>Bloquear emergentes, o bloquear ventanas emergentes</b>. Luego, selecciona <b>Permitir</b> cuando Safari pregunte <b>El sitio Web esta intentando abrir una vewntana emergente o algo parecido</b>.<br>🔹 <b>Safari en Mac:</b> Ve a <b>Safari > Configuración > Sitios web > Ventanas emergentes</b> y permite las ventanas para este sitio.<br>🔹 <b>Android (Chrome/Edge):</b> Ve a <b>Configuración > Configuración del sitio > Ventanas emergentes y redirecciones</b> y permite este sitio."
                }
            },
            icon: "warning",
            button: "OK",
            closeOnEsc: false,
            closeOnClickOutside: false
        });
        return;
    }

    // 📌 Redirigir a la URL del reporte
    reporteWindow.location.href = url + "?" + new URLSearchParams(params).toString();
}

function enviarFormulario(url, params, ventana) {
    let form = document.createElement("form");
    form.method = "POST";
    form.action = url;
    form.target = ventana ? ventana.name : "_blank";

    for (let key in params) {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

//FIN FUNCION PARA OBTENER REPORTES DESDE IIS

//INICIO IMPRIMIR FACTURACION
function printQuote(cotizacion_id) {
    params = {
        "id": cotizacion_id,
        "type": "Cotizacion_carta_izzy",
        "db": "<?php echo $IZZY_DB_JS; ?>",
        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
    };   

    // Llamar a la función para mostrar el reporte
    viewReport(params, "Cotización");
}

function printBill(facturas_id, $print_comprobante) {
    var url = "<?php echo SERVERURL;?>core/getImpresoraComprobante.php";

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            formato: "Factura",
        },
        success: function(data) {
            // Parsear el JSON
            const impresora = JSON.parse(data)[0]; // Acceder a la primera impresora

            // Comprobar si la impresora está activa
            if (impresora && impresora.estado === "1") {
                // Generar la URL con los parámetros de facturas_id y formato
                var params;

                // Eliminar espacios adicionales del formato
                var formato = impresora.formato.trim();                

                if (formato === "Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_carta_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Media Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_media_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Ticket") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_ticket_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };                
                } else {
                    // Manejar caso donde el formato no sea válido
                    showNotify('error', 'Error', 'El formato de impresión no es válido. Verifica la configuración de la impresora.');
                    return; // Salir si el formato no es válido
                }

                // Llamar a la función para mostrar el reporte
                viewReport(params, "Factura");
            } else {
                // Usando SweetAlert en lugar de alert
                 showNotify('error', 'Error', 'La impresora no está activa. Diríjase al menú de "Configuración" > "Impresoras" para activar la impresora. Después de activarla, podrás reimprimir la factura desde el reporte de facturación.');
            }
        },
        error: function(xhr, status, error) {            
            showNotify('error', 'Error', 'Hubo un problema al procesar la solicitud.');
        }
    });

    return false;
}

function printBillMovil(facturas_id, $print_comprobante) {
    var url = "<?php echo SERVERURL;?>core/getImpresoraComprobante.php";

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            formato: "Factura",
        },
        success: function(data) {
            // Parsear el JSON
            const impresora = JSON.parse(data)[0]; // Acceder a la primera impresora

            // Comprobar si la impresora está activa
            if (impresora && impresora.estado === "1") {
                // Generar la URL con los parámetros de facturas_id y formato
                var params;

                // Eliminar espacios adicionales del formato
                var formato = impresora.formato.trim();                

                if (formato === "Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_carta_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Media Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_media_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Ticket") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_ticket_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };                
                } else {
                    // Manejar caso donde el formato no sea válido
                    showNotify('error', 'Error', 'El formato de impresión no es válido. Verifica la configuración de la impresora.');
                    return; // Salir si el formato no es válido
                }

                // Llamar a la función para mostrar el reporte
                viewReport_old(params);
            } else {
                // Usando SweetAlert en lugar de alert
                 showNotify('error', 'Error', 'La impresora no está activa. Diríjase al menú de "Configuración" > "Impresoras" para activar la impresora. Después de activarla, podrás reimprimir la factura desde el reporte de facturación.');
            }
        },
        error: function(xhr, status, error) {            
            showNotify('error', 'Error', 'Hubo un problema al procesar la solicitud.');
        }
    });

    return false;
}

function printBillReporteVentas(facturas_id, print_comprobante) {
    var url = "<?php echo SERVERURL;?>core/getImpresoraComprobante.php";

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            formato: "Factura",
        },
        success: function(data) {
            // Parsear el JSON
            const impresora = JSON.parse(data)[0]; // Acceder a la primera impresora

            // Comprobar si la impresora está activa
            if (impresora && impresora.estado === "1") {
                // Generar la URL con los parámetros de facturas_id y formato
                var params;

                // Eliminar espacios adicionales del formato
                var formato = impresora.formato.trim();                

                if (formato === "Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_carta_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Media Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_media_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };
                } else if (formato === "Ticket") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_ticket_izzy",
                        "db": "<?php echo $IZZY_DB_JS; ?>",
                        "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
                    };                
                } else {
                    // Manejar caso donde el formato no sea válido
                    showNotify('error', 'Error', 'El formato de impresión no es válido. Verifica la configuración de la impresora.');
                    return; // Salir si el formato no es válido
                }

                // Llamar a la función para mostrar el reporte
                viewReport(params, "Factura");
            } else {
                // Usando SweetAlert en lugar de alert
                swal({
                    title: "Error",
                    text: "La impresora no está activa. Diríjase al menú de 'Configuración' > 'Impresoras' para activar la impresora. Después de activarla, podrás reimprimir la factura desde el reporte de facturación.",
                    icon: "error",
                    buttons: {
                        confirm: {
                            text: "Cerrar",
                            closeModal: true,
                        },
                    },
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
            }
        },
        error: function(xhr, status, error) {            
            showNotify('error', 'Error', 'Hubo un problema al procesar la solicitud.');
        }
    });
}

function printBillComprobanteReporteVentas(facturas_id, print_comprobante) {
    var url = "<?php echo SERVERURL;?>core/getImpresoraComprobante.php";

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            formato: "Comprobante",
        },
        success: function(data) {
            // Parsear el JSON
            const impresora = JSON.parse(data)[0]; // Acceder a la primera impresora

            // Comprobar si la impresora está activa
            if (impresora && impresora.estado == 1) {
                var baseUrl = '<?php echo SERVERURL;?>core/';
                var endpoint = 'generaComprobanteEntrega.php';

                // Generar la URL con los parámetros de facturas_id y formato
                var params = `?facturas_id=${facturas_id}&formato=${impresora.formato}`;

                // Abrir la URL generada
                window.open(baseUrl + endpoint + params);
            } else {
                // Usando SweetAlert en lugar de alert
                showNotify('error', 'Error', 'No hay impresoras activas o configuradas.');
            }
        },
        error: function(xhr, status, error) {
            showNotify('error', 'Error', 'Hubo un problema al procesar la solicitud.');
        }
    });
}

function printComprobanteCajas(apertura_id) {
    var url = "<?php echo SERVERURL;?>core/llenarDataTableImpresora.php";

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            id: 1,
        },
        success: function(data) {
            params = {
                "id": apertura_id,
                "type": "Comprobante_caja_izzy",
                "db": "<?php echo $IZZY_DB_JS; ?>",
                "demo_sistema": "<?php echo $IZZY_SISTEMA_PRUEBA_JS; ?>"
            };   

            // Llamar a la función para mostrar el reporte
            viewReport(params, "Comprobante de Caja");
        }
    });
}

function printPurchase(compras_id) {
  var url = '<?php echo SERVERURL; ?>core/generaCompra.php?compras_id=' + compras_id;

  abrirDocumentoEnModal(url, "Documento de compra");
}

//INICIO ENVIAR COTIZACION POR CORREO ELECTRONICO
//INICIO ENVIAR COTIZACION POR CORREO ELECTRONICO
function mailQuote(cotizacion_id) {
    cotizacion_id = parseInt(cotizacion_id || 0, 10);

    if (cotizacion_id <= 0) {
        showNotify('error', 'Error', 'No se recibió una cotización válida para enviar por correo.');
        return false;
    }

    // Evita doble envío accidental si la función se dispara dos veces seguidas
    if (!window.__mailQuoteEnProceso) {
        window.__mailQuoteEnProceso = {};
    }

    if (window.__mailQuoteEnProceso[cotizacion_id]) {
        showNotify('warning', 'En proceso', 'Esta cotización ya se está enviando. Espere un momento.');
        return false;
    }

    window.__mailQuoteEnProceso[cotizacion_id] = true;

    showNotify('info', 'Enviando', 'Enviando cotización por correo, por favor espere...');

    sendQuote(cotizacion_id);
}

function sendQuote(cotizacion_id) {
    cotizacion_id = parseInt(cotizacion_id || 0, 10);

    if (cotizacion_id <= 0) {
        showNotify('error', 'Error', 'No se pudo identificar la cotización a enviar.');
        return false;
    }

    var url = '<?php echo SERVERURL; ?>core/correo/sendCotizacion.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        cache: false,
        data: {
            cotizacion_id: cotizacion_id
        },
        success: function(data) {
            var respuesta = $.trim(data || '');

            var $respuestaAjax = $('<div class="RespuestaAjax d-none"></div>');
            $('body').append($respuestaAjax);
            $respuestaAjax.html(respuesta);

            $respuestaAjax.find('script').each(function() {
              var codigo = this.text || this.textContent || this.innerHTML || '';

              if ($.trim(codigo) !== '') {
                  try {
                      $.globalEval(codigo);
                  } catch (e) {
                      showNotify(
                          'error',
                          'Error',
                          'No se pudo completar el envío de la cotización.'
                      );
                  }
              }
          });

            if (
                respuesta.indexOf("showNotify('success'") === -1 &&
                respuesta.indexOf('showNotify("success"') === -1 &&
                respuesta.indexOf("showNotify('error'") === -1 &&
                respuesta.indexOf('showNotify("error"') === -1 &&
                respuesta.indexOf("showNotify('warning'") === -1 &&
                respuesta.indexOf('showNotify("warning"') === -1
            ) {
                showNotify('success', 'Cotización enviada', 'La cotización fue enviada correctamente por correo.');
            }

            setTimeout(function() {
                $respuestaAjax.remove();

                if (window.__mailQuoteEnProceso && window.__mailQuoteEnProceso[cotizacion_id]) {
                    delete window.__mailQuoteEnProceso[cotizacion_id];
                }
            }, 2000);
        },
        error: function(xhr) {
            showNotify(
                'error',
                'Error',
                xhr.responseText || 'No se pudo enviar la cotización por correo.'
            );

            if (window.__mailQuoteEnProceso && window.__mailQuoteEnProceso[cotizacion_id]) {
                delete window.__mailQuoteEnProceso[cotizacion_id];
            }
        }
    });

    return true;
}
//FIN ENVIAR COTIZACION POR CORREO ELECTRONICO

function getNumeroCotizacion(cotizacion_id) {
    var url = '<?php echo SERVERURL; ?>core/getNoCotizacion.php';
    var noFactura = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'cotizacion_id=' + cotizacion_id,
        success: function(data) {
            var datos = eval(data);
            noFactura = datos[0];
        }
    });
    return noFactura;
}
//FIN ENVIAR COTIZACION POR CORREO ELECTRONICO

//INICIO ENVIAR FACTURA POR CORREO ELECTRONICO
function mailBill(facturas_id) {
    facturas_id = parseInt(facturas_id || 0);

    if (facturas_id <= 0) {
      showNotify(
          'warning',
          'Factura no válida',
          'No se encontró una factura válida para enviar por correo.'
      );
      return;
  }

    // Evita doble envío accidental si la función se dispara dos veces seguidas
    if (!window.__mailBillEnProceso) {
        window.__mailBillEnProceso = {};
    }

    if (window.__mailBillEnProceso[facturas_id]) {
        return;
    }

    window.__mailBillEnProceso[facturas_id] = true;

    setTimeout(function () {
        if (typeof sendMail === 'function') {
            sendMail(facturas_id);
        } else {
            showNotify(
                'error',
                'Error',
                'No se pudo iniciar el envío del correo.'
            );
        }

        setTimeout(function () {
            delete window.__mailBillEnProceso[facturas_id];
        }, 3000);
    }, 300);
}

function sendMail(facturas_id) {
    var url = '<?php echo SERVERURL; ?>core/correo/sendFactura.php';
    var bill = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'facturas_id=' + facturas_id,
        success: function(data) {
            bill = data;
            if (bill == 1) {
                showNotify('success', 'Success', 'La factura ha sido enviada por correo satisfactoriamente');
            }
        }
    });
    return bill;
}

function getNumeroFactura(facturas_id) {
    var url = '<?php echo SERVERURL; ?>core/getNoFactura.php';
    var noFactura = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'facturas_id=' + facturas_id,
        success: function(data) {
            var datos = eval(data);
            noFactura = datos[0];
        }
    });
    return noFactura;
}

function getNumeroCompra(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/getNoCompra.php';
    var noCompra = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'compras_id=' + compras_id,
        success: function(data) {
            var datos = eval(data);
            noCompra = datos[0];
        }
    });
    return noCompra;
}
//FIN ENVIAR FACTURA POR CORREO ELECTRONICO

/*INICIO FORMULARIO COLABORADORES*/
function modal_colaboradores() {
    getPuestoColaboradores();
    $('#formColaboradores').attr({
        'data-form': 'save'
    });
    $('#formColaboradores').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarColaboradorAjax.php'
    });
    $('#formColaboradores')[0].reset();
    $('#reg_colaborador').show();
    $('#edi_colaborador').hide();
    $('#delete_colaborador').hide();

    //HABILITAR OBJETOS
    $('#formColaboradores #nombre_colaborador').attr('readonly', false);
    $('#formColaboradores #identidad_colaborador').attr('readonly', false);
    $('#formColaboradores #telefono_colaborador').attr('readonly', false);
    $('#formColaboradores #puesto_colaborador').attr('disabled', false);
    $('#formColaboradores #estado_colaborador').attr('disabled', false);
    $('#formColaboradores #colaboradores_activo').attr('disabled', false);
    $('#formColaboradores #colaborador_empresa_id').attr('disabled', false);
    $('#formColaboradores #fecha_ingreso_colaborador').attr('disabled', false);
    $('#formColaboradores #fecha_egreso_colaborador').attr('disabled', false);
    $('#formColaboradores #buscar_colaborador_empresa').show();
    $('#formColaboradores #estado_colaboradores').hide();

    $('#formColaboradores #datosClientes').hide();
    $('#formColaboradores #estado_colaborador').hide();

    $('#formColaboradores #proceso_colaboradores').val("Registro");
    $('#modal_registrar_colaboradores').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getPuestoColaboradores() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getPuestoColaboradores.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formColaboradores #puesto_colaborador');
            select.empty();
            
            if(response.success) {
                response.data.forEach(puesto => {
                    select.append(`
                        <option value="${puesto.puestos_id}">
                            ${puesto.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay colaboradores disponibles</option>');
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            $('#formColaboradores #puesto_colaborador').html('<option value="">Error al cargar</option>');
            $('#formColaboradores #puesto_colaborador').selectpicker('refresh');
        }
    });
}

function getEmpresaColaboradores() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formColaboradores #colaborador_empresa_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`
                        <option value="${empresa.empresa_id}">
                            ${empresa.nombre}
                        </option>
                    `);
                });
                
                // Establecer valor por defecto si existe
                if(response.data.length > 0) {
                    select.val(1); // O el valor que necesites por defecto
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formColaboradores #colaborador_empresa_id').html('<option value="">Error al cargar</option>');
            $('#formColaboradores #colaborador_empresa_id').selectpicker('refresh');
        }
    });
}
/*FIN FORMULARIO COLABORADORES*/

//INICIO CAMBIAR CONTRASEÑA
$('#cambiar_contraseña_usuarios_sistema').on('click', function(e) {
    e.preventDefault();

    $('#form-cambiarcontra').attr({
        'data-form': 'update'
    });
    $('#form-cambiarcontra').attr({
        'action': '<?php echo SERVERURL;?>ajax/modificarContrasenaAjax.php'
    });
    $('#form-cambiarcontra')[0].reset();

    // Restaurar estilos y mensajes de error
    $('#form-cambiarcontra #mensaje_cambiar_contra').html("");
    $('#form-cambiarcontra input').css("border-color", "");
    $('#form-cambiarcontra #repcontra').css("border-color", "");
    $('#form-cambiarcontra #mensaje_cambiar_contra').html("").css("color", "none");

    // Resto del código para abrir el modal
    $('#ModalContraseña').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });

    // Mostrar condiciones de seguridad después de abrir el modal
    mostrarRequisitos();
});
//FIN CAMBIAR CONTRASEÑA


//FIN MARCAR ASISTENCIA
$('#marcarAsistencia').on('click', function(e) {
    e.preventDefault();

    $('#formAsistencia')[0].reset();
    $('#reg_asistencia').show();
    $('#edi_asistencia').hide();
    $('#formAsistencia #proceso_asistencia').val("Registro");
    $('#formAsistencia #asistencia_empleado').val(getColaboradorAsistencia());
    $('#formAsistencia #fechaAsistencia').hide();
    $('#formAsistencia #marcarAsistencia_id').val(1);

    $('#formAsistencia #asistencia_empleado').selectpicker('refresh');
    $('#formAsistencia #grupoHora').show();
    $('#formAsistencia #grupoHorai').hide();
    $('#formAsistencia #grupoHoraf').hide();
    $('#formAsistencia #grupoHoraComentario').hide();
    $('#formAsistencia #registro_hora').html(getHoraInicio($('#formAsistencia #asistencia_empleado').val()));

    $('#modal_registrar_asistencia').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});


$('#formAsistencia').on('submit', function(e) {
    e.preventDefault();

    // 1. Refrescar selectpickers si existen
    if ($('.selectpicker').length) {
        $('.selectpicker').selectpicker('refresh');
    }

    // 2. Construir objeto de datos manualmente
    const getValue = (selector) => $(selector).val();
    const formData = {
        asistencia_id: getValue('#asistencia_id'),
        asistencia_empleado: getValue('#asistencia_empleado'),
        fecha: getValue('#fecha'),
        hora: getValue('#hora'),
        horaf: getValue('#horagf') || null,
        comentario: getValue('#comentario') || '',
        marcarAsistencia_id: getValue('#marcarAsistencia_id') || 0
    };

    // 3. Validación básica en cliente
    const requiredFields = ['asistencia_empleado', 'fecha'];
    const missingFields = requiredFields.filter(field => !formData[field]);
    
    if (missingFields.length > 0) {
        showNotify('error', 'Error', `Faltan campos requeridos: ${missingFields.join(', ')}`);
        return;
    }

    // 4. Determinar si es creación o edición
    const isEdit = !!(formData.asistencia_id && String(formData.asistencia_id).trim() !== '' && formData.asistencia_id !== '0');

    const url = isEdit ? '<?php echo SERVERURL;?>core/asistencia/modificarAsistenciaAjax.php' 
                      : '<?php echo SERVERURL;?>core/asistencia/addAsistenciaMarcajeAjax.php';

    // 5. Configuración de SweetAlert dinámica
    swal({
        title: isEdit ? "¿Actualizar asistencia?" : "¿Registrar asistencia?",
        text: isEdit ? "Confirma los cambios en el registro" : "Confirma que deseas registrar esta asistencia",
        icon: "info",
        buttons: {
            cancel: { 
                text: "Cancelar", 
                visible: true, 
                className: "btn-light" 
            },
            confirm: { 
                text: isEdit ? "Sí, actualizar" : "Sí, registrar",
                className: "btn-primary"
            }
        },
        dangerMode: false,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm) {
            // 6. Deshabilitar botón durante el envío
            const submitBtn = isEdit ? $('#edi_asistencia') : $('#reg_asistencia');
            const originalBtnHtml = submitBtn.html();
            submitBtn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            // 7. Enviar datos
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: "json",
                success: function(response) {
                    // Restaurar botón
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                    
                    // Mostrar notificación
                    if (response.Alerta) {
                        showNotify(response.Tipo, response.Titulo, response.Texto);
                    } else if (response.status) {
                        showNotify(response.status, response.title || "Respuesta", response.message);
                    }
                    
                    // Manejar respuesta exitosa
                    if ((response.Alerta && response.Alerta === "recargar") || response.status === "success") {
                        // Actualizar la tabla si la función existe
                        if (typeof listar_asistencia === 'function') {
                            listar_asistencia();
                        }
                        
                        // Cerrar modal después de 1.5 segundos
                        setTimeout(() => {
                            $('#modal_registrar_asistencia').modal('hide');
                            
                            // Resetear formulario y estado
                            $('#formAsistencia')[0].reset();
                            $('#reg_asistencia').hide();
                            $('#edi_asistencia').hide();
                            $('#formAsistencia').attr('data-form', '');
                            
                            // Mostrar campos apropiados
                            $('#grupoHora').show();
                            $('#grupoHorai').hide();
                            $('#grupoHoraf').hide();
                        }, 1500);
                    }
                    
                    // Resaltar campos con error si existen
                    if (response.missing_fields) {
                        $('.is-invalid').removeClass('is-invalid');
                        response.missing_fields.forEach(field => {
                            $(`[name="${field}"], #${field}`).addClass('is-invalid');
                        });
                    }
                },
                error: function(xhr) {
                    // Restaurar botón
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                    
                    // Manejo de errores mejorado
                    let errorMsg = "Error al procesar la solicitud";
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMsg = errorResponse.message || errorResponse.Texto || errorMsg;
                    } catch (e) {
              
                    }
                    
                    showNotify("error", "Error de conexión", errorMsg);
                   
                }
            });
        }
    });
});

$('#formAsistencia #asistencia_empleado').on('change', function() {
    if ($('#formAsistencia #marcarAsistencia_id').val() == 1) {
        $('#formAsistencia #registro_hora').html(getHoraInicio($('#formAsistencia #asistencia_empleado')
            .val()));
    }
});

//FIN MARCAR ASISTENCIA

// Función para cargar código de cliente y PIN - Versión Final
async function cargarDatosCliente() {
    try {
        const response = await $.ajax({
            url: '<?php echo SERVERURL; ?>core/getCodigoCliente.php',
            type: 'POST',
            dataType: 'json'
        });

        // Ocultar/mostrar elementos según si es DB_MAIN
        if (response.is_main_db) {
            // Si es la base de datos principal, ocultamos todo relacionado con PIN
            $('#badge-codigo-cliente').addClass('d-none');
            $('#ver-pin-usuario').addClass('d-none');
            return null;
        }

        // Validación del código de cliente para bases de datos no principales
        if (response.success && response.codigo_cliente && !isNaN(response.codigo_cliente)) {
            const codigo = String(response.codigo_cliente).trim();
            
            // Actualizar UI del código de cliente
            $('#badge-codigo-cliente')
                .text('CLIENTE: ' + codigo)
                .removeClass('d-none bg-secondary bg-danger')
                .addClass('bg-primary');
            
            // Mostrar opción de PIN y cargarlo
            $('#ver-pin-usuario').removeClass('d-none');
            await cargarPinCliente(codigo, false);
            
            return codigo;
        } else {
            // Manejar caso cuando no hay código de cliente válido
            $('#badge-codigo-cliente')
                .text('Sin código')
                .removeClass('bg-primary d-none')
                .addClass('bg-warning');
                
            $('#ver-pin-usuario').addClass('d-none');
            
            throw new Error(response.error || 'Código de cliente no disponible para esta base de datos');
        }
    } catch (error) {        
        $('#ver-pin-usuario').addClass('d-none');
        $('#badge-codigo-cliente')
            .text('Error')
            .removeClass('d-none bg-primary')
            .addClass('bg-danger');
            
        mostrarErrorCliente(error.message || 'Error al cargar datos del cliente');
        return null;
    }
}

// Función para cargar/actualizar el PIN - Versión Final
async function cargarPinCliente(codigoCliente, generateNew = false) {
    // Validación robusta del código de cliente
    if (!codigoCliente || isNaN(codigoCliente)) {
        mostrarErrorBadgePin('Código inválido');
        return null;
    }

    try {
        const response = await $.ajax({
            url: '<?php echo SERVERURL;?>core/generarPinCliente.php',
            type: 'POST',
            data: {
                codigoCliente: codigoCliente,
                generateNew: generateNew ? 1 : 0
            },
            dataType: 'json'
        });

        if (response.success && response.pin) {
            const pin = String(response.pin);
            actualizarUIPin(pin);
            return pin;
        } else {
            throw new Error(response.error || 'PIN no generado');
        }
    } catch (error) {        
        mostrarErrorBadgePin(error.message);
        return null;
    }
}

// Helper para actualizar la UI del PIN
function actualizarUIPin(pin) {
    $('#badge-pin-cliente')
        .text(pin.slice(-4) + '...')
        .removeClass('bg-danger d-none')
        .addClass('bg-info');
        
    $('#ver-pin-usuario').attr('data-content', `
        <div class="pin-popover-content">
            <div class="pin-header">
                <i class="fas fa-lock mr-2"></i>
                <span>Tu PIN de acceso</span>
            </div>
            <div class="pin-value">${pin}</div>
            <div class="pin-footer">
                <small class="text-muted">Válido por 5 minutos</small>
                <button class="btn btn-sm btn-outline-primary btn-regenerate-pin mt-2">
                    <i class="fas fa-sync-alt mr-1"></i> Regenerar
                </button>
            </div>
        </div>
    `);
}

// Helper para mostrar errores en el badge del PIN
function mostrarErrorBadgePin(mensaje) {
    $('#badge-pin-cliente')
        .text('Error')
        .removeClass('bg-info')
        .addClass('bg-danger');
        
    if (mensaje) {        
        mostrarErrorCliente(mensaje);
    }
}

// Mostrar error con notificación
function mostrarErrorCliente(mensaje) {
    if (typeof showNotify !== 'undefined') {
        showNotify("error", "Error", mensaje);
    } else {
        alert(mensaje);
    }
}

// Inicializar popover
function inicializarPopoverPIN() {
    $('#ver-pin-usuario').popover({
        html: true,
        placement: 'right',
        trigger: 'click',
        container: 'body',
        template: `
            <div class="popover pin-popover" role="tooltip">
                <div class="popover-arrow"></div>
                <div class="popover-body"></div>
            </div>
        `
    });
    
    // Cerrar popover al hacer clic fuera
    $(document).on('click', function(e) {
        if ($(e.target).data('toggle') !== 'popover'
            && $(e.target).parents('[data-toggle="popover"]').length === 0
            && $(e.target).parents('.popover.in').length === 0) { 
            $('#ver-pin-usuario').popover('hide');
        }
    });
    
    // Manejar regeneración de PIN desde el popover
    $(document).on('click', '.btn-regenerate-pin', async function() {
        const codigoCliente = $('#badge-codigo-cliente').text().replace('CLIENTE: ', '');
        if (codigoCliente) {
            await cargarPinCliente(codigoCliente, true);
            $('#ver-pin-usuario').popover('hide');
            showNotify("success", "PIN actualizado", "Se ha generado un nuevo PIN");
        }
    });
}

// GENERAR PIN - Versión Final
async function generatePin(generateNew) {
    const codigoCliente = $('#formColaboradores #cliente_codigo_colaborador').val();
    const main_db = $('#formColaboradores #main_db').val();

    // No generar PIN si es la base de datos principal
    if (main_db === "true") {
        return;
    }

    // Validación estricta para bases de datos no principales
    if (!codigoCliente || isNaN(codigoCliente)) {
        showNotify("error", "Error", "Código de cliente no válido");
        return;
    }

    try {
        const response = await $.ajax({
            url: '<?php echo SERVERURL; ?>core/generarPinCliente.php',
            type: 'POST',
            data: {
                codigoCliente: codigoCliente,
                generateNew: generateNew
            },
            dataType: 'json'
        });

        // Verificación robusta de la respuesta
        if (response && response.pin !== undefined && response.pin !== null) {
            const pinDisplay = String(response.pin);
            
            // Actualizar UI
            $('#formColaboradores #pin_colaborador').val(pinDisplay);
            actualizarUIPin(pinDisplay);
            
            showNotify("success", "PIN generado", "Se ha creado un nuevo PIN");
        } else {
            throw new Error(response.error || 'No se recibió un PIN válido del servidor');
        }
    } catch (error) {        
        mostrarErrorBadgePin(error.message);
        showNotify("error", "Error de conexión", "No se pudo generar el PIN");
    }
}

// CONSULTAR CÓDIGO DE CLIENTE - Versión Final
async function getCodigoCliente() {
    try {
        const response = await $.ajax({
            url: '<?php echo SERVERURL; ?>core/getCodigoCliente.php',
            type: 'POST',
            dataType: 'json'
        });

        // Asignar valores con validación
        const codigoCliente = response.codigo_cliente || '';
        $('#formColaboradores #cliente_codigo_colaborador').val(codigoCliente); 
        $('#formColaboradores #main_db').val(response.is_main_db);

        // Mostrar/ocultar sección de PIN según el tipo de DB
        if (response.is_main_db) {
            $('#formColaboradores #datosClientes').hide();
            $('#badge-codigo-cliente').addClass('d-none');
        } else {
            $('#formColaboradores #datosClientes').show();
            
            // Generar PIN solo si hay código de cliente válido
            if (response.success && codigoCliente && !isNaN(codigoCliente)) {
                await generatePin(0);
            }
        }
    } catch (error) {
        
    }
}

// MODIFICAR PERFIL USUARIO SISTEMA - Versión Final
$('#modificar_perfil_usuario_sistema').on('click', async function(e) {
    e.preventDefault();
    $('#formColaboradores')[0].reset();
    $('#estado_colaboradores').hide();

    try {
        // 1. Cargar código de cliente (espera a que termine)
        await getCodigoCliente();
        
        // 2. Cargar datos del colaborador
        const registro = await $.ajax({
            url: '<?php echo SERVERURL;?>core/editarColaboradoresUsuario.php',
            type: 'POST'
        });

        const valores = JSON.parse(registro);
        
        // Configurar formulario
        $('#formColaboradores').attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarColaboradorAjaxMain.php'
        });
        
        // Mostrar/ocultar elementos
        $('#reg_colaborador').hide();
        $('#edi_colaborador').show();
        $('#delete_colaborador').hide();
        
        // Llenar valores del formulario
        $('#formColaboradores #nombre_colaborador').val(valores[0]).attr('readonly', false);
        $('#formColaboradores #identidad_colaborador').val(valores[1]).attr('readonly', false);
        $('#formColaboradores #telefono_colaborador').val(valores[2]).attr('readonly', false);
        $('#formColaboradores #puesto_colaborador').val(valores[3]).attr('disabled', true);
        $('#formColaboradores #colaborador_empresa_id').val(valores[4]).attr('disabled', true);
        $('#formColaboradores #colaborador_id').val(valores[9]);
        $('#formColaboradores #fecha_ingreso_colaborador').val(valores[6]).attr('disabled', true);
        $('#formColaboradores #fecha_egreso_colaborador').val(valores[7]).attr('disabled', true);
        
        // Configurar checkbox
        $('#formColaboradores #colaboradores_activo').prop('checked', valores[5] == 1);

        $('#formColaboradores #estado_colaborador').hide();
        
        // Mostrar modal
        $('#modal_registrar_colaboradores').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });

    } catch (error) {
        
        showNotify("error", "Error", "No se pudo cargar el perfil");
    }
});

// Evento para regenerar PIN desde botón
$(document).on('click', '#regenerar-pin', async function() {
    const codigoCliente = $('#badge-codigo-cliente').text().replace('CLIENTE: ', '');
    if (codigoCliente) {
        await cargarPinCliente(codigoCliente, true);
        showNotify("success", "PIN actualizado", "Se ha generado un nuevo PIN");
    }
});

// Evento para mostrar modal del PIN
$(document).on('click', '#ver-pin-usuario', function(e) {
    e.preventDefault();
    $('#pinModal').modal('show');
});

// Inicialización al cargar la página
$(document).ready(function() {
    inicializarPopoverPIN();
    cargarDatosCliente();
    
    // Actualizar PIN periódicamente (cada minuto) solo si no es DB_MAIN
    setInterval(async function() {
        const main_db = $('#formColaboradores #main_db').val();
        if (main_db === "true") return;
        
        const codigoCliente = $('#badge-codigo-cliente').text().replace('CLIENTE: ', '');
        if (codigoCliente) {
            await cargarPinCliente(codigoCliente, false);
        }
    }, 60000);
    
    // Evento para botón Generar PIN
    $('#generarPin').on('click', function(event) {
        event.preventDefault();
        generatePin(1);
    });
});

function getImagenHeaderConsulta(callback) {
    var url = '<?php echo SERVERURL;?>core/get_image.php';

    // Obtener la URL de la imagen usando Ajax
    $.ajax({
        type: "GET",
        url: url, // Ruta al archivo PHP
        success: function(imageUrl) {
            // Llamar a la función de devolución de llamada con la URL de la imagen
            callback(imageUrl);
        },
        error: function() {
            // Puedes manejar errores aquí también, si es necesario.
        }
    });
}

var imagen;
getImagenHeaderConsulta(function(imageUrl) {
    toDataURL(imageUrl, function(dataUrl) {
        imagen = dataUrl;
        // Ahora, 'imagen' contiene los datos de la imagen en formato Data URL
    });
});

function validarAperturaCajaUsuario() {
    if (getConsultarAperturaCaja() == 2) {
        $("#invoice-form #btn_apertura").show();
        $("#invoice-form #reg_factura").attr("disabled", true);
        $("#invoice-form #add_cliente").attr("disabled", true);
        $("#invoice-form #add_vendedor").attr("disabled", true);
        $("#invoice-form #btn_retiro_caja").attr("disabled", true);
        $("#invoice-form #btn_exoneracion").attr("disabled", true);
        $("#invoice-form #btn_ver_caja_factura").attr("disabled", true);
        $("#invoice-form #addRows").attr("disabled", true);
        $("#invoice-form #removeRows").attr("disabled", true);
        $("#invoice-form #notasFactura").attr("disabled", true);
        $("#invoice-form #btn_apertura").show();
        $("#invoice-form #btn_cierre").hide();
    } else {
        $("#invoice-form #btn_apertura").hide();
        $("#invoice-form #reg_factura").attr("disabled", false);
        $("#invoice-form #add_cliente").attr("disabled", false);
        $("#invoice-form #add_vendedor").attr("disabled", false);
        $("#invoice-form #btn_retiro_caja").attr("disabled", false);
        $("#invoice-form #btn_exoneracion").attr("disabled", false);
        $("#invoice-form #btn_ver_caja_factura").attr("disabled", false);
        $("#invoice-form #addRows").attr("disabled", false);
        $("#invoice-form #removeRows").attr("disabled", false);
        $("#invoice-form #notasFactura").attr("disabled", false);
        $("#invoice-form #btn_cierre").show();
        $("#invoice-form #btn_apertura").hide();
    }
}

function getConsultarAperturaCaja() {
    var url = '<?php echo SERVERURL;?>core/getAperturaCajaUsuario.php';

    var estado_apertura;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(registro) {
            var valores = eval(registro);
            estado_apertura = valores[0];
        }
    });
    return estado_apertura;
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - CUENTAS POR COBRAR CLIENTES
   ========================================================= */

   function construirHeaderFooterDataTableCuentasPorCobrarClientes() {
    var $tabla = $("#dataTableCuentasPorCobrarClientes");
    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Cliente</th>' +
                '<th>Tipo</th>' +
                '<th>Número</th>' +
                '<th>Crédito</th>' +
                '<th>Abono</th>' +
                '<th>Saldo</th>' +
                '<th>Vendedor</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="5" class="text-right">Totales:</td>' +
                '<td id="credito-cxc"></td>' +
                '<td id="abono-cxc"></td>' +
                '<td id="total-footer-cxc"></td>' +
                '<td></td>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTADO - CUENTAS POR COBRAR CLIENTES
   ========================================================= */
   var listar_cuentas_por_cobrar_clientes = function() {
    var cobrar_estado = "";

    if (
        $("#form_main_cobrar_clientes #main_cobrar_clientes_estado").val() == "" ||
        $("#form_main_cobrar_clientes #cobrar_clientes_estado").val() == null
    ) {
        cobrar_estado = 1;
    } else {
        cobrar_estado = $("#form_main_cobrar_clientes #cobrar_clientes_estado").val();
    }

    var cobrar_clientes_id = $("#form_main_cobrar_clientes #main_cobrar_clientes").val();
    var cobrar_fechai = $("#form_main_cobrar_clientes #main_cobrarclientes_fechai").val();
    var cobrar_fechaf = $("#form_main_cobrar_clientes #main_cobrarclientes_fechaf").val();

    if ($.fn.DataTable.isDataTable("#dataTableCuentasPorCobrarClientes")) {
        $("#dataTableCuentasPorCobrarClientes").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableCuentasPorCobrarClientes();

    var table_cuentas_por_cobrar_clientes = $("#dataTableCuentasPorCobrarClientes").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableCobrarClientes.php",
            "data": {
                "estado": cobrar_estado,
                "clientes_id": cobrar_clientes_id,
                "fechai": cobrar_fechai,
                "fechaf": cobrar_fechaf
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item table_abono">' +
                                  '<span class="accion-icon accion-icon-success">' +
                                      '<i class="fas fa-cash-register"></i>' +
                                  '</span>' +
                                    '<span class="accion-label">Registrar abono</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes abono_factura ocultar">' +
                                  '<span class="accion-icon accion-icon-warning">' +
                                      '<i class="fas fa-money-bill-wave"></i>' +
                                  '</span>' +
                                  '<span class="accion-label">Ver abonos</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes print_factura ocultar">' +
                                  '<span class="accion-icon accion-icon-danger">' +
                                      '<i class="fas fa-file-download"></i>' +
                                  '</span>' +
                                  '<span class="accion-label">Ver factura</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                "data": "fecha"
            },
            {
                "data": "cliente"
            },
            {
                "data": "tipo_factura",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var text = data == 1 ? 'Contado' : 'Crédito';

                        var icon = data == 1
                            ? '<i class="fas fa-clock mr-1"></i>'
                            : '<i class="fas fa-check-circle mr-1"></i>';

                        var badgeClass = data == 1
                            ? 'badge badge-pill badge-success'
                            : 'badge badge-pill badge-warning';

                        return '<span class="' + badgeClass + '" style="font-size:0.85rem; padding:0.45em 0.7em; font-weight:500;">' +
                            icon +
                            text +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                "data": "numero",
                "render": function(data, type, row) {
                    if (type === 'sort') {
                        return parseInt(row.numero_ordenamiento);
                    }

                    return data;
                }
            },
            {
                "data": "credito",
                "render": function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "abono",
                "render": function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "saldo",
                "render": function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "vendedor"
            }
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "order": [[4, "desc"]],
        "orderFixed": {
            "pre": [[4, "desc"]]
        },
        "columnDefs": [
            {
                width: "10%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "18%",
                targets: 2
            },
            {
                width: "9%",
                targets: 3,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "12%",
                targets: 4,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "12%",
                targets: 5,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "12%",
                targets: 6,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "12%",
                targets: 7,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "15%",
                targets: 8
            }
        ],
        "footerCallback": function(row, data, start, end, display) {
            var totalCredito = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.credito) || 0);
            }, 0);

            var totalAbono = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.abono) || 0);
            }, 0);

            var totalPendiente = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.saldo) || 0);
            }, 0);

            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2
            });

            $('#credito-cxc').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatter.format(totalCredito) +
                '</span>'
            );

            $('#abono-cxc').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatter.format(totalAbono) +
                '</span>'
            );

            $('#total-footer-cxc').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatter.format(totalPendiente) +
                '</span>'
            );
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Cuentas por Cobrar Clientes',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_cuentas_por_cobrar_clientes();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Cuents por Cobrar Clientes',
                exportOptions: {
                    columns: [3, 4, 5, 6, 7]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Cuentas por Cobrar Clientes',
                messageTop: 'Fecha desde: ' + convertDateFormat(cobrar_fechai) + ' Fecha hasta: ' +
                    convertDateFormat(cobrar_fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [3, 4, 5, 6, 7]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_cuentas_por_cobrar_clientes.search('').draw();
    $('#buscar').focus();

    registrar_abono_cxc_clientes_dataTable(
        "#dataTableCuentasPorCobrarClientes tbody",
        table_cuentas_por_cobrar_clientes
    );

    ver_abono_cxc_clientes_dataTable(
        "#dataTableCuentasPorCobrarClientes tbody",
        table_cuentas_por_cobrar_clientes
    );

    view_reporte_facturas_dataTable(
        "#dataTableCuentasPorCobrarClientes tbody",
        table_cuentas_por_cobrar_clientes
    );
};

var view_reporte_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillReporteVentas(data.facturas_id);
    });
}

var REFRESCAR_CXC_AL_CERRAR_PAGO = false;

var registrar_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_abono");

    $(tbody).on("click", "button.table_abono", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (data.estado == 2 || data.saldo <= 0) {
            // no tiene acceso a la accion si la factura ya fue cancelada
            showNotify('error', 'Error', 'No puede realizar esta accion a las facturas canceladas!');
        } else {
            $("#GrupoPagosMultiplesFacturas").hide();

            REFRESCAR_CXC_AL_CERRAR_PAGO = true;

            pago(data.facturas_id, 2, 'cxc');

            // Para facturas
            // openPaymentModal('factura', 1250.00, 'Cliente Ejemplo', 12345);
        }
    });
};

var ver_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_factura");
    $(tbody).on("click", "button.abono_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        
        // Configuración del modal para evitar cierre no deseado
        $('#ver_abono_cxc').modal({
            backdrop: 'static', // Evita que se cierre al hacer clic fuera
            keyboard: false    // Evita que se cierre al presionar ESC
        }).modal('show');
        
        $("#formulario_ver_abono_cxc #abono_facturas_id").val(data.facturas_id);
        listar_AbonosCXC();
    });
}

var ver_abono_cxp_proveedor_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_proveedor");
    $(tbody).on("click", "button.abono_proveedor", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxp').modal('show');
        $("#formulario_ver_abono_cxp #abono_compras_id").val(data.compras_id);
        listar_AbonosCXP();
    });
}

$(document).off('hidden.bs.modal.refrescarCXC', '#modal_pagos_unificado')
.on('hidden.bs.modal.refrescarCXC', '#modal_pagos_unificado', function() {

    if (window.REFRESCAR_CXC_AL_CERRAR_PAGO === true) {
        window.REFRESCAR_CXC_AL_CERRAR_PAGO = false;

        if (typeof listar_busqueda_cuentas_por_cobrar_clientes === "function") {
            listar_busqueda_cuentas_por_cobrar_clientes();
        }

        if (typeof listar_cuentas_por_cobrar_clientes === "function") {
            listar_cuentas_por_cobrar_clientes();
        }
    }

});

function getClientesCXC() {
  var url = '<?php echo SERVERURL;?>core/getClientesCXC.php';
  
  return $.ajax({
    type: "POST",
    url: url,
    dataType: "html",
    cache: false
  }).then(function (html) {
    const $sel = $('#form_main_cobrar_clientes #main_cobrar_clientes');

    // placeholder fijo
    $sel.empty().append('<option value="">Clientes</option>');

    // elimina cualquier selected que venga del backend
    const $opts = $(html);
    $opts.filter('option').prop('selected', false).removeAttr('selected');
    $sel.append($opts);

    // **FORZAR vacío real** antes de refrescar el UI
    $sel.val('');
    if ($sel.selectpicker) $sel.selectpicker('refresh');
  });
}

function getProveedoresCXP() {
    var url = '<?php echo SERVERURL;?>core/getProveedoresCXP.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_pagar_proveedores #pagar_proveedores').html("");
            $('#form_main_pagar_proveedores #pagar_proveedores').html(data);
            $('#form_main_pagar_proveedores #pagar_proveedores').selectpicker('refresh');
        }
    });
}

$(() => {
    // Evento para el botón de Generar Reporte
    $('#form_main_cobrar_clientes').on('submit', function(e) {
        e.preventDefault();
        listar_cuentas_por_cobrar_clientes();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_cobrar_clientes').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_cuentas_por_cobrar_clientes();
    });	    

    // Evento para el botón de Generar Reporte
    $('#form_main_pagar_proveedores').on('submit', function(e) {
        e.preventDefault();
        listar_cuentas_por_pagar_proveedores();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_pagar_proveedores').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_cuentas_por_pagar_proveedores();
    });	       
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - CUENTAS POR PAGAR PROVEEDORES
   ========================================================= */
   function construirHeaderFooterDataTableCuentasPorPagarProveedores() {
    var $tabla = $("#dataTableCuentasPorPagarProveedores");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Proveedor</th>' +
                '<th>Tipo</th>' +
                '<th>Factura</th>' +
                '<th>Crédito</th>' +
                '<th>Abono</th>' +
                '<th>Saldo</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="5" class="text-right">Totales:</td>' +
                '<td id="credito-cxp"></td>' +
                '<td id="abono-cxp"></td>' +
                '<td id="total-footer-cxp"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTADO - CUENTAS POR PAGAR PROVEEDORES
   ========================================================= */
   var listar_cuentas_por_pagar_proveedores = function() {
    var estado = $('#form_main_pagar_proveedores #pagar_proveedores_estado').val();

    var proveedores_id = $("#form_main_pagar_proveedores #pagar_proveedores").val();
    var fechai = $("#form_main_pagar_proveedores #fechai").val();
    var fechaf = $("#form_main_pagar_proveedores #fechaf").val();

    if ($.fn.DataTable.isDataTable("#dataTableCuentasPorPagarProveedores")) {
        $("#dataTableCuentasPorPagarProveedores").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableCuentasPorPagarProveedores();

    var table_cuentas_por_pagar_proveedores = $("#dataTableCuentasPorPagarProveedores").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTablePagarProveedores.php",
            "data": {
                "estado": estado,
                "proveedores_id": proveedores_id,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item table_pay ocultar">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-hand-holding-usd"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Abonar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item abono_proveedor">' +
                                    '<span class="accion-icon accion-icon-secondary">' +
                                        '<i class="fas fa-money-bill-wave"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Abonos</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes print_factura ocultar">' +
                                    '<span class="accion-icon accion-icon-success">' +
                                        '<i class="fas fa-file-download"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Factura</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                "data": "fecha"
            },
            {
                "data": "proveedores"
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var text = data == 1 ? 'Crédito' : 'Contado';

                        var icon = data == 1
                            ? '<i class="fas fa-clock mr-1"></i>'
                            : '<i class="fas fa-check-circle mr-1"></i>';

                        var badgeClass = data == 1
                            ? 'badge badge-pill badge-warning'
                            : 'badge badge-pill badge-success';

                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon +
                            text +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                "data": "factura",
                "render": function(data, type, row) {
                    if (type === 'sort') {
                        return parseInt(row.numero_ordenamiento);
                    }

                    return data;
                }
            },
            {
                "data": "credito",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';

                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "abono",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';

                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            },
            {
                "data": "saldo",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';

                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                }
            }
        ],
        "order": [[4, "desc"]],
        "orderFixed": {
            "pre": [[4, "desc"]]
        },
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "10%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "12%",
                targets: 1
            },
            {
                width: "18%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "18%",
                targets: 4,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "13%",
                targets: 5,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "13%",
                targets: 6,
                className: "text-right text-nowrap align-middle"
            },
            {
                width: "14%",
                targets: 7,
                className: "text-right text-nowrap align-middle"
            }
        ],
        "footerCallback": function(row, data, start, end, display) {
            var totalCredito = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.credito) || 0);
            }, 0);

            var totalAbono = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.abono) || 0);
            }, 0);

            var totalPendiente = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.saldo) || 0);
            }, 0);

            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2
            });

            $('#credito-cxp').html(formatter.format(totalCredito));
            $('#abono-cxp').html(formatter.format(totalAbono));
            $('#total-footer-cxp').html(formatter.format(totalPendiente));
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Cuentas Pagar Proveedores',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_cuentas_por_pagar_proveedores();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Cuentas por Pagar Proveedores',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Cuentas por Pagar Proveedores',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [2, 3, 4, 5, 6, 7]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_cuentas_por_pagar_proveedores.search('').draw();
    $('#buscar').focus();

    registrar_pago_proveedores_dataTable(
        "#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores
    );

    ver_abono_cxp_proveedor_dataTable(
        "#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores
    );

    ver_reporte_facturas_cxp_proveedor_dataTable(
        "#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores
    );
}

var ver_reporte_facturas_cxp_proveedor_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printPurchase(data.compras_id);
    });
}

var registrar_pago_proveedores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_pay");
    $(tbody).on("click", "button.table_pay", function() {
        var data = table.row($(this).parents("tr")).data();
        if (data.saldo <= 0) {
            showNotify('info', 'Alerta', 'Esta Factura ya fue Cancelada');
        } else {
            $("#GrupoPagosMultiples").hide();
            pagoCompras(data.compras_id, data.saldo, 2);
        }
    });
}
//FIN LLENAR TABLAS

/*INICIO FUNCION OBTENER MUNICIPIOS*/
function getMunicipiosClientes(municipios_id) {
    var url = '<?php echo SERVERURL;?>core/getMunicipios.php';

    var departamentos_id = $('#formClientes #departamento_cliente').val();

    $.ajax({
        type: 'POST',
        url: url,
        data: 'departamentos_id=' + departamentos_id,
        success: function(data) {
            $('#formClientes #municipio_cliente').html("");
            $('#formClientes #municipio_cliente').html(data);
            $('#formClientes #municipio_cliente').selectpicker('refresh');

            $('#formClientes #municipio_cliente').val(municipios_id);
            $('#formClientes #municipio_cliente').selectpicker('refresh');
        }
    });
    return false;
}

$('#formClientes #departamento_cliente').on('change', function() {
    var url = '<?php echo SERVERURL;?>core/getMunicipios.php';

    var departamentos_id = $('#formClientes #departamento_cliente').val();

    $.ajax({
        type: 'POST',
        url: url,
        data: 'departamentos_id=' + departamentos_id,
        success: function(data) {
            $('#formClientes #municipio_cliente').html("");
            $('#formClientes #municipio_cliente').html(data);
            $('#formClientes #municipio_cliente').selectpicker('refresh');
        }
    });
    return false;
});

$(() => {
    $("#modal_registrar_clientes").on('shown.bs.modal', function() {
        $(this).find('#formClientes #nombre_clientes').focus();
    });
});

// Evento para el botón de Buscar (submit)
$('#form_main_clientes').on('submit', function(e) {
    e.preventDefault();
    listar_clientes(); 
});

// Evento para el botón de Limpiar (reset)
$('#form_main_clientes').on('reset', function() {
    // Limpia y refresca los selects
    $('#form_main_clientes .selectpicker')
        .val('')
        .selectpicker('refresh');
    listar_clientes();
});

/* =========================================================
   HEADER DINÁMICO - CLIENTES
   ========================================================= */
   function construirHeaderDataTableClientes() {
    var $tabla = $("#dataTableClientes");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Cliente</th>' +
                '<th>RTN</th>' +
                '<th>Teléfono</th>' +
                '<th>Correo</th>' +
                '<th>Departamento</th>' +
                '<th>Municipio</th>' +
                '<th class="sistema">Sistema</th>' +
                '<th>Estado</th>' +
                '<th>Puntos</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO ACCIONES FORMULARIO CLIENTES
var listar_clientes = function(estado) {
    var estado = $('#form_main_clientes #estado_clientes').val();

    if ($.fn.DataTable.isDataTable("#dataTableClientes")) {
        $("#dataTableClientes").DataTable().clear().destroy();
    }

    construirHeaderDataTableClientes();

    var table_clientes = $("#dataTableClientes").DataTable({
        destroy: true,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableClientes.php",
            data: {
                estado: estado
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    var privilegio = getPrivilegioUsuario();
                    var db_consulta = getSessionUser() === "" ? DB_MAIN : getSessionUser();

                    /*
                       Generar solo debe mostrarse si:
                       - Está en la base principal DB_MAIN
                       - El usuario tiene privilegio permitido
                       Antes se manejaba con columna .generar.
                       Ahora se maneja dentro del dropdown.
                    */
                    var privilegiosPermitidosGenerar = [1, 2, 3];
                    var puedeGenerar = privilegiosPermitidosGenerar.includes(privilegio) && db_consulta === DB_MAIN;

                    var botonGenerar = '';

                    if (puedeGenerar) {
                        botonGenerar =
                            '<button type="button" class="dropdown-item accion-item accion-confirmar table_crear generar accion-generar-cliente">' +
                                '<span class="accion-icon accion-icon-primary">' +
                                    '<i class="fab fa-centos"></i>' +
                                '</span>' +
                                '<span class="accion-label">Generar</span>' +
                            '</button>';
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                botonGenerar +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                data: "cliente"
            },
            {
                data: "rtn"
            },
            {
                data: "telefono"
            },
            {
                data: "correo"
            },
            {
                data: "departamento"
            },
            {
                data: "municipio"
            },
            {
                data: "sistema",
                render: function(data, type, row) {
                    if (type === "display") {
                        let badgeClass = "badge badge-pill ";
                        let label = "";
                        let icon = '<i class="fas fa-cogs mr-1"></i>';

                        if (!data) {
                            badgeClass += "badge-secondary";
                            label = "Sin sistema";
                            icon = '<i class="fas fa-ban mr-1"></i>';
                        } else {
                            switch (data) {
                                case "IZZY":
                                    badgeClass += "badge-primary";
                                    label = data;
                                    break;

                                case "CAMI":
                                    badgeClass += "badge-success";
                                    label = data;
                                    break;

                                case "MONISYS":
                                    badgeClass += "badge-warning";
                                    label = data;
                                    break;

                                default:
                                    badgeClass += "badge-info";
                                    label = data;
                                    break;
                            }
                        }

                        return '<span class="' + badgeClass + '" style="font-size: 0.9rem; padding: 0.45em 0.75em; font-weight: 600;">' +
                            icon +
                            label +
                        '</span>';
                    }

                    return data || "Sin sistema";
                }
            },
            {
                data: "estado",
                render: function(data, type, row) {
                    if (type === "display") {
                        var estadoText = data == 1 ? "Activo" : "Inactivo";

                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';

                        var badgeClass = data == 1 ?
                            "badge badge-pill badge-success" :
                            "badge badge-pill badge-danger";

                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon +
                            estadoText +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                data: "puntos",
                render: function(data, type, row) {
                    var clienteId = row.id || row.clientes_id || 0;

                    return '<span class="badge badge-primary">' + (data || 0) + '</span> ' +
                        '<button type="button" class="btn btn-sm btn-info ver-historial" title="Ver historial" data-id="' + clienteId + '">' +
                            '<i class="fas fa-history" style="color: white;"></i>' +
                        '</button>';
                }
            }
        ],

        order: [[1, "asc"]],

        lengthMenu: lengthMenu10,
        stateSave: true,
        bDestroy: true,
        language: idioma_español,
        dom: dom,

        columnDefs: [
            {
                width: "8%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "18%",
                targets: 1
            },
            {
                width: "12%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3
            },
            {
                width: "16%",
                targets: 4
            },
            {
                width: "11%",
                targets: 5
            },
            {
                width: "11%",
                targets: 6
            },
            {
                width: "8%",
                targets: 7,
                className: "text-center text-nowrap"
            },
            {
                width: "8%",
                targets: 8,
                className: "text-center text-nowrap"
            },
            {
                width: "8%",
                targets: 9,
                className: "text-center text-nowrap"
            }
        ],

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: "Actualizar Clientes",
                className: "table_actualizar btn btn-secondary ocultar",
                action: function() {
                    listar_clientes();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: "Agregar Clientes",
                className: "btn btn-primary ocultar",
                action: function() {
                    modal_clientes();
                }
            },
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: "Excel",
                title: "Reporte de Clientes",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                className: "table_reportes btn btn-success ocultar"
            },
            {
                extend: "pdf",
                orientation: "landscape",
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: "PDF",
                pageSize: "LEGAL",
                title: "Reporte de Clientes",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-danger ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],

        drawCallback: function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            var privilegio = getPrivilegioUsuario();
            var db_consulta = getSessionUser() === "" ? DB_MAIN : getSessionUser();
            var table = this.api();

            /*
               Sistema antes era columna 6.
               Ahora con Acciones al inicio, Sistema es columna 7.
               Se oculta si no está en DB_MAIN.
            */
            if (db_consulta === DB_MAIN) {
                table.column(7).visible(true);
            } else {
                table.column(7).visible(false);
            }

            /*
               Programa de puntos.
               Puntos es columna 9.
            */
            $.ajax({
                url: "<?php echo SERVERURL;?>core/programaPuntos/verificarProgramaPuntos.php",
                type: "POST",
                dataType: "json",
                async: false,
                success: function(response) {
                    if (response.mostrar_puntos) {
                        table.column(9).visible(true);
                    } else {
                        table.column(9).visible(false);
                    }
                },
                error: function() {
                    table.column(9).visible(false);
                }
            });
        }
    });

    $("#dataTableClientes").off("click", ".ver-historial");
    $("#dataTableClientes").on("click", ".ver-historial", function() {
        var cliente_id = $(this).data("id");

        $("#modal_historial_puntos").modal("show");
        cargarHistorialPuntos(cliente_id);
    });

    table_clientes.search("").draw();

    $("#buscar").focus();

    generar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
    editar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
    eliminar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
};
//FIN ACCIONES FORMULARIO CLIENTES

function cargarHistorialPuntos(cliente_id) {
    
    // Mostrar loader
    $('#tabla_historial_puntos tbody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</td></tr>');
    
    $.ajax({
        url: '<?php echo SERVERURL;?>core/programaPuntos/llenarDataTableHistoricoPuntos.php', // <-- URL CORRECTA
        method: 'POST',
        data: { 
            cliente_id: cliente_id,
            programa_puntos_id: 1
        },
        dataType: 'json',
        success: function(response) {
            
            if(response.success) {
                $('#nombre_cliente_puntos').text(response.nombre_cliente || 'Cliente no identificado');
                
                // Mostrar total con 2 decimales
                var totalPuntos = parseFloat(response.total_puntos || 0).toFixed(2);
                $('#total_puntos_historial').text(totalPuntos);
                
                var tbody = $('#tabla_historial_puntos tbody');
                tbody.empty();
                
                if(response.historial && response.historial.length > 0) {
                    $.each(response.historial, function(index, item) {
                        var puntos = parseFloat(item.puntos) || 0;
                        var signo = item.tipo === 'Acumulación' ? '+' : '-';
                        var clase = item.tipo === 'Acumulación' ? 'text-success' : 'text-danger';
                        
                        var row = '<tr>' +
                            '<td>' + (item.fecha || '--') + '</td>' +
                            '<td>' + (item.tipo || '--') + '</td>' +
                            '<td class="'+clase+'">' + signo + puntos.toFixed(2) + '</td>' +
                            '<td>' + (item.descripcion || '--') + '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                } else {
                    tbody.append('<tr><td colspan="4" class="text-center">No hay registros de puntos</td></tr>');
                }
            } else {
                toastr.error(response.message || 'Error al cargar el historial de puntos');
                $('#tabla_historial_puntos tbody').html(
                    '<tr><td colspan="4" class="text-center text-danger">Error al cargar el historial</td></tr>'
                );
                $('#total_puntos_historial').text('0.00'); // <-- Mostrar 0.00 en caso de error
            }
        },
        error: function(xhr, status, error) {
            toastr.error('Error al conectar con el servidor');
            $('#tabla_historial_puntos tbody').html(
                '<tr><td colspan="4" class="text-center text-danger">Error de conexión</td></tr>'
            );
            $('#total_puntos_historial').text('0.00'); // <-- Mostrar 0.00 en caso de error
        }
    });
}

var listar_generar_clientes = function() {
    var clientes_id = $("#formGenerarSistema #clientes_id").val();

    if ($.fn.DataTable.isDataTable("#DatatableGenerarSistema")) {
        $("#DatatableGenerarSistema").DataTable().clear().destroy();
    }

    var table_generar_clientes = $("#DatatableGenerarSistema").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableGenerarSistema.php",
            "data": {
                "clientes_id": clientes_id
            }
        },
        "columns": [
            {"data": "nombre"},
            {"data": "db"},
            {"data": "sistema"},
            {"data": "plan"},
            {"data": "validar"}
        ],
        "lengthMenu": lengthMenu20,
        "stateSave": true,
        "language": idioma_español,
        "dom": dom,
        "autoWidth": false,
        "columnDefs": [
            {width: "40%", targets: 0},
            {width: "20%", targets: 1},
            {width: "15%", targets: 2},
            {width: "15%", targets: 3},
            {width: "10%", targets: 4}
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Clientes',
                className: 'btn btn-secondary',
                action: function() {
                    listar_generar_clientes();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Clientes',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-success',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Clientes',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-danger',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "initComplete": function() {
            this.api().columns.adjust();
        },
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    $("#modal_generar_sistema")
        .off("shown.bs.modal.ajustarTablaGenerar")
        .on("shown.bs.modal.ajustarTablaGenerar", function() {
            if ($.fn.DataTable.isDataTable("#DatatableGenerarSistema")) {
                $("#DatatableGenerarSistema").DataTable().columns.adjust();
            }
        });

    table_generar_clientes.search('').draw();
    $('#buscar').focus();
};  

/* =========================================================
   REFRESCAR TABLA GENERAR SISTEMA AL CERRAR MODAL CLIENTES
   ========================================================= */
$(document)
.off('hidden.bs.modal.refrescarGenerarClientes', '#modal_registrar_clientes')
.on('hidden.bs.modal.refrescarGenerarClientes', '#modal_registrar_clientes', function() {

    if ($("#modal_generar_sistema").hasClass("show")) {

        if ($.fn.DataTable.isDataTable("#DatatableGenerarSistema")) {
            $("#DatatableGenerarSistema").DataTable().ajax.reload(null, false);
            $("#DatatableGenerarSistema").DataTable().columns.adjust();
        }

    }

});

$("#modal_generar_sistema").on('shown.bs.modal', function () {
    $(this).find('#formGenerarSistema #empresa').focus();
});

// Validar input texto
$('#formGenerarSistema #empresa, #formGenerarSistema #clientes_correo').on('input', function () {
    if ($(this).val().trim() !== '') {
        $(this).removeClass('is-invalid');
    }
});

// Validar selects (con selectpicker o normales)
$('#formGenerarSistema #sistema, #formGenerarSistema #plan').on('change', function () {
    if ($(this).val()) {
        $(this).removeClass('is-invalid');
    }
});

$("#reg_generarSitema").click(function(e) {
    e.preventDefault();

    var clientes_id = $("#formGenerarSistema #clientes_id").val();
    var validar = $("#formGenerarSistema #validar").val();
    var sistema_id = $("#formGenerarSistema #sistema").val();
    var planes_id = $("#formGenerarSistema #plan").val();

    var cliente = $("#formGenerarSistema #cliente").val();
    var rtn = $("#formGenerarSistema #rtn").val();
    var empresa = $("#formGenerarSistema #empresa").val();
    var correo = $("#formGenerarSistema #clientes_correo").val();
    var telefono = $("#formGenerarSistema #clientes_telefono").val();
    var eslogan = $("#formGenerarSistema #eslogan").val();
    var otra_informacion = $("#formGenerarSistema #otra_informacion").val();
    var celular = $("#formGenerarSistema #whatsApp").val();
    var ubicacion = $("#formGenerarSistema #clientes_ubicacion").val();
    var pass = "";

    // Resetear clases de error
    $('.form-control, .selectpicker').removeClass('is-invalid');

    if (!empresa) {
        $('#formGenerarSistema #empresa').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'La empresa es obligatoria, por favor ingrese el nombre de la empresa');
        return;
    }

    if (!sistema_id) {
        $('#formGenerarSistema #sistema').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'El sistema es obligatorio, por favor seleccione un sistema');
        return;
    }

    if (!planes_id) {
        $('#formGenerarSistema #plan').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'El plan es obligatorio, por favor seleccione un plan');
        return;
    }

    if (!correo) {
        $('#formGenerarSistema #clientes_correo').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'El correo es obligatorio');
        return;
    }

    $.ajax({
        url: '<?php echo SERVERURL; ?>ajax/registrarClienteAutonomoAjax.php',
        type: "POST",
        data: {
            clientes_id: clientes_id,
            user_empresa: empresa,
            user_name: cliente,
            user_telefono: telefono,
            email: correo,
            user_pass:pass,
            sistema_id: sistema_id,
            planes_id: planes_id,
            eslogan: eslogan,
            otra_informacion: otra_informacion,
            celular: celular,
            ubicacion: ubicacion,
            validar: validar,
            rtn: rtn,
        },
        beforeSend: function() {
            showLoading("Registrando usuario...");
        },
        success: function(resp) {
            if (resp.estado) {
                showNotify(resp.type, resp.title, resp.mensaje);
                listar_generar_clientes();
                listar_clientes();
            } else {
                showNotify(resp.type, resp.title, resp.mensaje);
            }
        },
        error: function(xhr, status, error) {
            try {
                const errResponse = JSON.parse(xhr.responseText);
                showNotify('error', 'Error', errResponse.mensaje || 'Error en el servidor');
            } catch (e) {
                showNotify('error', 'Error', 'Error de conexión: ' + error);
            }
        },
        complete: function() {
            
        }
    });
});

var generar_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_crear");
    $(tbody).on("click", "button.table_crear", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formGenerarSistema')[0].reset();
        $('#formGenerarSistema #clientes_id').val(data.clientes_id);

        listar_generar_clientes();

        $('#formGenerarSistema #cliente').val(data.cliente);
        $('#formGenerarSistema #rtn').val(data.rtn);
        $('#formGenerarSistema #clientes_telefono').val(data.telefono);
        $('#formGenerarSistema #clientes_correo').val(data.correo);
        $('#formGenerarSistema #clientes_ubicacion').val(data.ubicacion);
        $('#formGenerarSistema #empresa').val(data.empresa);
        $('#formGenerarSistema #eslogan').val(data.eslogan);
        $('#formGenerarSistema #otra_informacion').val(data.otra_informacion);
        $('#formGenerarSistema #whatsApp').val(data.whatsapp);

        $('#formGenerarSistema #sistema').val(data.sistema_id);
        $('#formGenerarSistema #sistema').selectpicker('refresh');
        $('#formGenerarSistema #plan').val(data.planes_id);
        $('#formGenerarSistema #plan').selectpicker('refresh');

        $('#formGenerarSistema #cliente').attr('disabled', true);
        $('#formGenerarSistema #rtn').attr('disabled', true);

        $('#formGenerarSistema #proceso_GenerarSistema').val("Generar Sistema");

        getValidarFacturacion();

        if (data.correo === "") {
            showNotify('error', 'Correo requerido', 'El cliente no tiene correo registrado. Agregue uno en su perfil antes de continuar');

            $('#reg_generarSitema').attr('disabled', true);
        } else {
            $('#reg_generarSitema').attr('disabled', false);
        }

        $('#modal_generar_sistema').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
}

function getPlanes() {
    var url = '<?php echo SERVERURL;?>core/getPlanes.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formGenerarSistema #plan').html("");
            $('#formGenerarSistema #plan').html(data);
            $('#formGenerarSistema #plan').selectpicker('refresh');
        }
    });
}

function getValidarFacturacion() {
    var url = '<?php echo SERVERURL;?>core/getValidarFacturacion.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formGenerarSistema #validar').html("");
            $('#formGenerarSistema #validar').html(data);
            $('#formGenerarSistema #validar').selectpicker('refresh');

            $('#formGenerarSistema #validar').val(1);
            $('#formGenerarSistema #validar').selectpicker('refresh');
        }
    });
}


function getSistemas() {
    var url = '<?php echo SERVERURL;?>core/getSistemas.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formGenerarSistema #sistema').html("");
            $('#formGenerarSistema #sistema').html(data);
            $('#formGenerarSistema #sistema').selectpicker('refresh');
        }
    });
}

var editar_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarClientes.php';
        $('#formClientes #clientes_id').val(data.clientes_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formClientes').serialize(),
            dataType: 'json',
            success: function(respuesta) {
                // Configuración básica del formulario
                $('#formClientes').attr({
                    'data-form': 'update',
                    'action': '<?php echo SERVERURL;?>ajax/modificarClientesAjax.php'
                }).trigger('reset');
                
                $('#reg_cliente').hide();
                $('#edi_cliente').show();
                $('#delete_cliente').hide();

                // Llenar datos del cliente
                $('#formClientes #nombre_clientes').val(respuesta.nombre || '');
                $('#formClientes #identidad_clientes').val(respuesta.rtn || '');
                $('#formClientes #fecha_clientes').attr('disabled', true).val(respuesta.fecha || '');
                $('#formClientes #departamento_cliente').val(respuesta.departamentos_id || '').selectpicker('refresh');
                getMunicipiosClientes(respuesta.municipios_id);
                $('#formClientes #municipio_cliente').val(respuesta.municipios_id || '').selectpicker('refresh');
                $('#formClientes #dirección_clientes').val(respuesta.localidad || '');
                $('#formClientes #telefono_clientes').val(respuesta.telefono || '');
                $('#formClientes #correo_clientes').val(respuesta.correo || '');
                $('#formClientes #clientes_activo').prop('checked', respuesta.estado == 1);

                /* SECCIÓN DE PUNTOS - CAMBIOS CLAVE */
                $('#card_puntos_cliente').show();
                
                // Manejo de puntos (siempre mostrar valor, 0 por defecto)
                var puntos = respuesta.puntos || 0;
                $('#puntos_acumulados').val(puntos);
                
                // Manejo de fecha (formato correcto o "No existe")
                var fechaActualizacion = 'No existe';
                if (respuesta.ultima_actualizacion && respuesta.ultima_actualizacion !== 'No existe') {
                    var fecha = new Date(respuesta.ultima_actualizacion);
                    if (!isNaN(fecha.getTime())) {
                        fechaActualizacion = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                }
                $('#puntos_ultima_actualizacion').val(fechaActualizacion);
                
                // Estilos condicionales
                if (puntos == 0) {
                    $('#puntos_acumulados').addClass('text-muted');
                } else {
                    $('#puntos_acumulados').removeClass('text-muted').addClass('text-success');
                }
                
                if (fechaActualizacion === 'No existe') {
                    $('#puntos_ultima_actualizacion').addClass('text-muted');
                } else {
                    $('#puntos_ultima_actualizacion').removeClass('text-muted');
                }

                // Configurar botón de historial
                $('#btn_ver_historial_puntos').off('click').on('click', function() {
                    $('#modal_historial_puntos').modal('show');
                    cargarHistorialPuntos(data.clientes_id);
                });

                // Manejo de estados de los campos
                $('#formClientes #nombre_clientes').attr("readonly", false);
                $('#formClientes #departamento_cliente, #formClientes #municipio_cliente, #formClientes #dirección_clientes').attr("disabled", false);
                $('#formClientes #telefono_clientes, #formClientes #correo_clientes').attr("readonly", false);
                $('#formClientes #clientes_activo').attr("disabled", false);
                $('#formClientes #grupo_editar_rtn').show();
                $('#formClientes #identidad_clientes').attr("readonly", true);
                $('#formClientes #fecha_clientes').attr("readonly", true);

                // Mostrar modal
                $('#formClientes #proceso_clientes').val("Editar");
                $('#modal_registrar_clientes').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            },
            error: function(xhr, status, error) {                
                showNotify('error', 'Error', 'No se pudieron cargar los datos del cliente');
                $('#modal_registrar_clientes').modal('hide');
            }
        });
    });
};

var eliminar_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var clientes_id = data.clientes_id;
        var nombreCliente = data.cliente; 
        var rtnCliente = data.rtn || 'No registrado'; // Manejo de RTN vacío
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente al cliente?<br><br>
                        <strong>Nombre:</strong> ${nombreCliente}<br>
                        <strong>RTN:</strong> ${rtnCliente}`;
        
        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-light"
                },
                confirm: {
                    text: "Sí, eliminar",
                    value: true,
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
               
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarClientesAjax.php',
                    data: {
                        clientes_id: clientes_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    before: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false); // Recargar tabla sin resetear paginación
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });

    });
}

$('#formClientes #label_clientes_activo').html("Activo");

$('#formClientes .switch').change(function() {
    if ($('input[name=clientes_activo]').is(':checked')) {
        $('#formClientes #label_clientes_activo').html("Activo");
        return true;
    } else {
        $('#formClientes #label_clientes_activo').html("Inactivo");
        return false;
    }
});

//INICIO EDITAR RTN CLIENTE
//SE LLAMA AL MODAL CUANDO PRESIONAMOS EN EDITAR RTN EN CLIENTES
$(document).on('click', '#formClientes #grupo_editar_rtn_clientes .editar_rtn', function(e) {
    e.preventDefault();

    var clientes_id = $('#formClientes #clientes_id').val();
    var cliente = $('#formClientes #nombre_clientes').val();
    var rtn = $('#formClientes #identidad_clientes').val();

    if ($('#formEditarRTNClientes').length === 0) {
        showNotify('error', 'Error', 'No se encontró el formulario para editar RTN.');
        return;
    }

    if ($('#modalEditarRTNClientes').length === 0) {
        showNotify('error', 'Error', 'No se encontró el modal para editar RTN.');
        return;
    }

    $('#formEditarRTNClientes')[0].reset();

    $('#formEditarRTNClientes #pro_clientes').val('Editar');
    $('#formEditarRTNClientes #clientes_id').val(clientes_id);
    $('#formEditarRTNClientes #cliente').val(cliente);
    $('#formEditarRTNClientes #rtn_cliente').val(rtn);

    $('#modalEditarRTNClientes').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$(document).ready(function() {
    $("#modalEditarRTNClientes").on('shown.bs.modal', function() {
        $(this).find('#formEditarRTNClientes #rtn_cliente').focus();
    });
});

$('#editar_rtn_clientes').on('click', function(e) {
    e.preventDefault();

    editRTNClient($('#formEditarRTNClientes #clientes_id').val(), $('#formEditarRTNClientes #rtn_cliente')
        .val());
});

function editRTNClient(clientes_id, rtn) {
    swal({
        title: "¿Estás seguro?",
        text: "¿Desea editar el RTN para el cliente: " + getNombreCliente(clientes_id) + "?",
        icon: "info",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true,
                closeModal: true
            },
            confirm: {
                text: "¡Sí, deseo editarlo!",
                className: "btn-primary"
            }
        },
        closeOnClickOutside: false,
        closeOnEsc: false
    }).then((willEdit) => {
        if (willEdit) {
            return editRTNCliente(clientes_id, rtn); // Retorna la promesa del AJAX
        }
    });
}

function editRTNCliente(clientes_id, rtn) {
    var url = '<?php echo SERVERURL; ?>core/editRTNCliente.php';
    
    // Convertir a AJAX asíncrono (recomendado)
    return $.ajax({
        type: 'POST',
        url: url,
        async: true, // Cambiado a true (elimina el bloqueo)
        data: { 
            clientes_id: clientes_id, 
            rtn: rtn 
        }
    }).then(function(data) {
        if (data == 1) {
            swal.close(); // Cierra manualmente el SweetAlert
            showNotify('success', 'Success', 'El RTN ha sido actualizado satisfactoriamente');
            listar_clientes();
            $('#formClientes #identidad_clientes').val(rtn);
        } else if (data == 2) {
            swal.close();
            showNotify('error', 'Error', 'Error el RTN no se puede actualizar');
        } else if (data == 3) {
            swal.close();
            showNotify('error', 'Error', 'El RTN ya existe');
        }
    }).fail(function() {
        swal.close();
        showNotify('error', 'Error', 'Error en la solicitud');
    });
}

function getNombreCliente(clientes_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreCliente.php';
    var nombreCliente = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'clientes_id=' + clientes_id,
        success: function(data) {
            var datos = eval(data);
            nombreCliente = datos[0];
        }
    });

    return nombreCliente;
}
//FIN EDITAR RTN CLIENTE

//funcion aplicar nuevo saldo
function saldoFactura(facturas_id) {
    //IMPORTE NUEVO EFECTIVO
    var url = '<?php echo SERVERURL;?>core/getSaldoFactura.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'facturas_id=' + facturas_id,
        success: function(saldoFactura) {
            $('#formEfectivoBill #monto_efectivo, #monto_efectivo_efectivo').val(saldoFactura);
            $('#bill-pay').html(saldoFactura);
        }
    });
}

//funcion aplicar nuevo saldo compras CXP
function saldoCompras(compras_id) {
    //IMPORTE NUEVO EFECTIVO
    var url = '<?php echo SERVERURL;?>core/getSaldoCompras.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'compras_id=' + compras_id,
        success: function(saldoFactura) {
            //$('#formEfectivoBill #monto_efectivo, #monto_efectivo_efectivo').val(saldoFactura);
            $('#Purchase-pay').html(saldoFactura);
        }
    });
}
//FIN ACCIONES FROMULARIO CLIENTES

// INICIO PAGO UNIFICADO
/* ============================================================
   MODAL PAGOS UNIFICADO – JS COMPLETO
   Facturación + CxC
   - pago() queda global como window.pago
   - Step 1 -> 2 NO valida importes
   - Step 2 -> 3 valida según reglas
   - Tarjeta permite importe visible SOLO en CxC
   - Transferencia y Cheque: banco opcional
   - Empaqueta todos los campos antes de enviar
   ============================================================ */

/* ===============================
   ESTADO GLOBAL DEL MODAL
   =============================== */
   var VALOR_POR_PUNTO = 1;
  var CURRENT_FACTURA_ID = null;
  var CURRENT_TIPO_PAGO = 1;     // 1: factura, 2: CxC
  var CURRENT_ORIGEN = '';       // 'cxc' para CxC

  var CURRENT_STEP = 1;
  var SELECTED_METHODS = new Set(['cash']);
  var LAST_SELECTED = 'cash';

  var CASH_TYPED = 0;
  var CASH_CAMBIO = 0;

  var LAST_APPLIED_AMOUNTS = {
    cash: 0,
    card: 0,
    transfer: 0,
    check: 0,
    points: 0
  };

  var PAYMENT_MODAL_CACHE = window.PAYMENT_MODAL_CACHE || {
    cliente_pago: '',
    cliente_nombre: '',
    total_pago: '',
    efectivo: {},
    tarjeta: {},
    transferencia: {},
    cheque: {},
    puntos: {}
  };

  window.PAYMENT_MODAL_CACHE = PAYMENT_MODAL_CACHE;

  /* ===============================
    MAPAS DEL MODAL
    =============================== */
  var methodIdMap = {
    cash: '#payment_cash',
    card: '#payment_card',
    transfer: '#payment_transfer',
    check: '#payment_check',
    points: '#payment_points'
};

var formMap = {
  cash: function () { return $('#formEfectivoBill'); },
  card: function () { return $('#formTarjetaBill'); },
  transfer: function () { return $('#formTransferenciaBill'); },
  check: function () { return $('#formChequeBill'); },
  points: function () { return $('#formPuntosBill'); }
};

var firstFieldByMethod = {
  cash: '#efectivo_bill',
  card: '#importe_tarjeta',  // Cambiado para que apunte al campo importe
  transfer: '#importe_transferencia',
  check: '#importe_cheque',
  points: '#puntos_uso'
};

var amountFieldByMethod = {
  cash: '#efectivo_bill',
  card: '#importe_tarjeta',
  transfer: '#importe_transferencia',
  check: '#importe_cheque',
  points: '#importe_puntos'
};

var facturaIdFieldByMethod = {
  cash: 'factura_id_efectivo',
  card: 'factura_id_tarjeta',
  transfer: 'factura_id_transferencia',
  check: 'factura_id_cheque',
  points: 'factura_id_puntos'
};

/* ===============================
   HELPERS NUMÉRICOS
   =============================== */
function normalizarMontoEntrada(str) {
  var v = (str || '').toString().replace(/[^0-9.]/g, '');
  var p = v.split('.');

  if (p.length > 2) {
    v = p[0] + '.' + p.slice(1).join('');
  }

  p = v.split('.');

  if (p.length > 1) {
    v = p[0] + '.' + p[1].slice(0, 2);
  }

  return v;
}

function parseMonto(str) {
  if (str === null || typeof str === 'undefined') return 0;

  var limpio = String(str)
    .replace(/L\./gi, '')
    .replace(/,/g, '')
    .replace(/[^\d.]/g, '')
    .trim();

  var n = parseFloat(normalizarMontoEntrada(limpio));
  return isNaN(n) ? 0 : n;
}

function parseMontoSeguro(valor) {
  return parseMonto(valor);
}

function fixed2(v) {
  return (parseFloat(v || 0) || 0).toFixed(2);
}

function fixed2Seguro(v) {
  return fixed2(v);
}

function fmtMiles(v) {
  var n = parseMonto(v);

  return n.toLocaleString('es-HN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function fmtMilesSeguro(v) {
  return fmtMiles(v);
}

function todaySafe() {
  try {
    return new Date().toISOString().split('T')[0];
  } catch (_) {
    return '';
  }
}

function isMultiOn() {
  return $('#modal_pagos_unificado').find('#pagos_multiples_switch').is(':checked');
}

function isCxC() {
  return String(CURRENT_TIPO_PAGO) === '2' || String(CURRENT_ORIGEN).toLowerCase() === 'cxc';
}

function isFactura() {
  return !isCxC();
}

function notifyPago(type, title, message) {
  if (typeof showNotify === 'function') {
    showNotify(type, title, message);
  } else {
    alert((title || 'Aviso') + '\n' + (message || ''));
  }
}

/* ===============================
   FOCUS ROBUSTO
   =============================== */
function waitForVisible($root, selector, maxTries, delay) {
  maxTries = maxTries || 20;
  delay = delay || 40;

  return new Promise(function (resolve) {
    var tries = 0;

    function tick() {
      var $el = $root.find(selector).first();

      if ($el.length && $el.is(':visible') && !$el.is(':disabled') && $el[0].offsetParent !== null) {
        resolve($el);
        return;
      }

      tries++;

      if (tries >= maxTries) {
        resolve($());
        return;
      }

      requestAnimationFrame(function () {
        setTimeout(tick, delay);
      });
    }

    tick();
  });
}

async function forceFocus(selector) {
  var $m = $('#modal_pagos_unificado');
  var $candidate = await waitForVisible($m, selector, 25, 30);
  var $el = $candidate;

  if (!$el.length) {
    var $activeForms = $m.find('.payment-details.payment-step.active:visible');
    var $fallback = $activeForms.find('input:visible:enabled, select:visible:enabled, textarea:visible:enabled').first();

    if ($fallback.length) {
      $el = $fallback;
    }
  }

  if (!$el.length) return false;

  try {
    if (document.activeElement !== $el[0]) {
      $el[0].focus({ preventScroll: true });
    }

    $el.trigger('focus');

    setTimeout(function () {
      try {
        var v = ($el.val() || '').toString();

        if ($el[0].setSelectionRange && $el[0].type !== 'date') {
          $el[0].setSelectionRange(v.length, v.length);
        }
      } catch (_) {}
    }, 20);

    $el.addClass('focus-field');

    setTimeout(function () {
      $el.removeClass('focus-field');
    }, 800);

    return true;
  } catch (e) {
    showNotify('warning', 'Aviso', 'No se pudo enfocar el campo automáticamente.');
    return false;
  }
}

function resetDetailsScroll() {
  var $m = $('#modal_pagos_unificado');

  $m.find('.payment-details-container').scrollTop(0);
  $m.find('.modal-body').scrollTop(0);
  $m.find('.payment-body').scrollTop(0);
}

async function focusFirstFieldFor(method) {
  var $m = $('#modal_pagos_unificado');
  var selField = firstFieldByMethod[method] || null;
  var formSelector = methodIdMap[method];

  $m.find('#section_details').show().css('display', 'block');

  if (formSelector) {
    var $formSection = $m.find(formSelector);

    if ($formSection.length) {
      if (!isMultiOn()) {
        $m.find('.payment-details.payment-step').removeClass('active').hide();
      }

      $formSection.addClass('active').show().css('display', 'block');
    }
  }

  resetDetailsScroll();

  if (selField) {
    await forceFocus(selField);
  }
}

// ============================================================
// FUNCIONES CORREGIDAS PARA TARJETA (SOLO JS)
// ============================================================

function ensureCardAmountCxCInput() {
  var $f = $('#formTarjetaBill');
  if (!$f.length) return;
  
  var $campoImporte = $f.find('#importe_tarjeta');
  var $grupo = $campoImporte.closest('.payment-form-group');
  
  // Asegurar que el campo tenga los atributos correctos
  if ($campoImporte.length) {
    $campoImporte.attr('inputmode', 'decimal');
    $campoImporte.attr('placeholder', ' ');
    $campoImporte.addClass('payment-form-control');
    
    // Asegurar que el label esté correcto
    var $label = $grupo.find('label');
    if ($label.length && $label.attr('for') !== 'importe_tarjeta') {
      $label.attr('for', 'importe_tarjeta');
      $label.html('Importe <span class="payment-required">*</span>');
    }
  }
}

function updateCardAmountVisibility() {
  var $f = $('#formTarjetaBill');
  if (!$f.length) return;

  ensureCardAmountCxCInput();

  var $campoImporte = $f.find('#importe_tarjeta');
  var $grupo = $campoImporte.closest('.payment-form-group');
  var total = parseMonto($('#modal_pagos_unificado #customer_bill_pay').val());

  if (isCxC()) {
    // CxC: campo VISIBLE, requerido, y con focus
    if ($grupo.length) $grupo.show();
    $campoImporte.show();
    $campoImporte.prop('required', true);
    $campoImporte.val(''); // Limpiar valor anterior
    
    firstFieldByMethod.card = '#importe_tarjeta';
  } else {
    // Factura: campo OCULTO, no requerido, valor automático
    if ($grupo.length) $grupo.hide();
    $campoImporte.hide();
    $campoImporte.prop('required', false);
    $campoImporte.val(fixed2(total)); // Asignar el total automáticamente
    
    firstFieldByMethod.card = '#cr_bill';
  }
}



/* ===============================
   UI DE PASOS
   =============================== */
function setStepActive(stepIdx) {
  var $m = $('#modal_pagos_unificado');

  $m.find('.payment-header .step').removeClass('active');
  $m.find('.payment-header .step[data-step="' + stepIdx + '"]').addClass('active');

  $m.find('#paymentSteps').css(
    '--progress-width',
    stepIdx === 1 ? '33%' : (stepIdx === 2 ? '66%' : '100%')
  );
}

function goToStep(step) {
  var $m = $('#modal_pagos_unificado');

  cacheAllPaymentForms();

  CURRENT_STEP = step;

  if (step === 1) {
    $('#pills_info').show();
    $('#global_options_bar').show();
    $('#opt_multi_wrap').show();

    $m.find('#section_methods').show();
    $m.find('#section_details').hide();
    $m.find('#section_confirm').hide();

    setStepActive(1);

    $m.find('#btnPrev').prop('disabled', true);
    $m.find('#btnNext').show().prop('disabled', SELECTED_METHODS.size === 0);

  } else if (step === 2) {
    $('#pills_info').show();
    $('#global_options_bar').show();
    $('#opt_multi_wrap').hide();

    $m.find('#section_methods').hide();
    $m.find('#section_details').show();
    $m.find('#section_confirm').hide();

    setStepActive(2);

    $m.find('#btnPrev').prop('disabled', false);
    $m.find('#btnNext').show().prop('disabled', false);

    showDetailsForSelected();
    updateCardAmountVisibility();

    setTimeout(async function () {
      var method = SELECTED_METHODS.size > 1
        ? (LAST_SELECTED && SELECTED_METHODS.has(LAST_SELECTED) ? LAST_SELECTED : Array.from(SELECTED_METHODS)[0])
        : Array.from(SELECTED_METHODS)[0];

      await focusFirstFieldFor(method);
    }, 180);

  } else {
    cacheAllPaymentForms();
    hydrateSelectedPaymentForms();

    $('#pills_info').hide();
    $('#global_options_bar').hide();

    $m.find('#section_methods').hide();
    $m.find('#section_details').hide();
    $m.find('#section_confirm').show();

    setStepActive(3);

    $m.find('#btnPrev').prop('disabled', false);
    $m.find('#btnNext').hide();

    buildConfirmSummary();
  }

  cacheAllPaymentForms();
}

/* ===============================
   SELECCIÓN DE MÉTODOS
   =============================== */
function isValidCombo(newSet) {
  if (newSet.size > 2) {
    return {
      ok: false,
      msg: 'Máximo 2 métodos en pagos múltiples.'
    };
  }

  var hasCash = newSet.has('cash');
  var hasPoints = newSet.has('points');
  var nonCash = Array.from(newSet).filter(function (m) {
    return m !== 'cash';
  });

  if (newSet.size === 2 && !hasCash) {
    return {
      ok: false,
      msg: 'La combinación múltiple debe incluir Efectivo.'
    };
  }

  if (hasPoints && nonCash.some(function (m) {
    return m !== 'points' && m !== 'cash';
  })) {
    return {
      ok: false,
      msg: 'Puntos solo puede combinarse con Efectivo.'
    };
  }

  return { ok: true };
}

function updateMethodsUI() {
  var $m = $('#modal_pagos_unificado');
  var $cards = $m.find('#paymentMethodsGrid .method-card');

  $cards.removeClass('selected').attr('aria-pressed', 'false');

  $cards.each(function () {
    var k = $(this).data('method');

    if (SELECTED_METHODS.has(k)) {
      $(this).addClass('selected').attr('aria-pressed', 'true');
    }
  });

  $m.find('#paymentMethodsGrid .method-card').removeClass('default-focus');

  if (SELECTED_METHODS.size === 0) {
    $m.find('#paymentMethodsGrid .method-card[data-method="cash"]').addClass('default-focus');
  }

  if (CURRENT_STEP === 1) {
    $m.find('#btnNext').prop('disabled', SELECTED_METHODS.size === 0);
  }

  if (CURRENT_STEP === 2) {
    showDetailsForSelected();

    setTimeout(async function () {
      var method = LAST_SELECTED && SELECTED_METHODS.has(LAST_SELECTED)
        ? LAST_SELECTED
        : Array.from(SELECTED_METHODS)[0];

      if (method) {
        await focusFirstFieldFor(method);
      }
    }, 150);
  }

  syncPaymentConfirm();
}

function toggleMethod(method) {
  cacheAllPaymentForms();

  LAST_SELECTED = method;

  if (!isMultiOn()) {
    SELECTED_METHODS = new Set([method]);

    if (method === 'points' && CURRENT_FACTURA_ID) {
      cargarPuntosClienteAjax(CURRENT_FACTURA_ID);
    }

    updateMethodsUI();
    return;
  }

  var candidate = new Set(SELECTED_METHODS);

  if (candidate.has(method)) {
    candidate.delete(method);
  } else {
    candidate.add(method);
  }

  var v = isValidCombo(candidate);

  if (!v.ok) {
    notifyPago('warning', 'Atención', v.msg);
    return;
  }

  SELECTED_METHODS = candidate;

  if (method === 'points' && SELECTED_METHODS.has('points') && CURRENT_FACTURA_ID) {
    cargarPuntosClienteAjax(CURRENT_FACTURA_ID);
  }

  updateMethodsUI();
}

function showDetailsForSelected() {
  var $m = $('#modal_pagos_unificado');

  $m.find('.payment-details.payment-step').removeClass('active').hide();

  if (SELECTED_METHODS.size === 0) return;

  $m.find('#section_details').show().css('display', 'block');

  SELECTED_METHODS.forEach(function (m) {
    var sel = methodIdMap[m];

    if (sel) {
      $m.find(sel).addClass('active').show().css('display', 'block');
    }
  });

  var $f = $('#formEfectivoBill');

  if ($f.length) {
    if (isMultiOn() || isCxC()) {
      $f.find('#grupo_cambio_efectivo').hide();
    } else {
      $f.find('#grupo_cambio_efectivo').show();
    }
  }

  updateCardAmountVisibility();

  $m.find('#pago_efectivo, #pago_tarjeta, #pago_transferencia, #pago_cheque, #pago_puntos')
    .hide()
    .prop('disabled', true)
    .attr('type', 'button');

  resetDetailsScroll();
}


function getEfectivoRecibidoActual() {
  var $m = $('#modal_pagos_unificado');
  var $field = $m.find('#formEfectivoBill #efectivo_bill:visible:enabled').first();

  if (!$field.length) {
    $field = $m.find('#formEfectivoBill input[name="efectivo_bill"]:visible:enabled').first();
  }

  if (!$field.length) {
    $field = $m.find('#formEfectivoBill #efectivo_bill').first();
  }

  if (!$field.length) {
    $field = $m.find('#formEfectivoBill input[name="efectivo_bill"]').first();
  }

  return parseMontoSeguro($field.val());
}

/* ===============================
   LECTURA Y CÁLCULO DE IMPORTES
   =============================== */
function getCardAmountValue() {
  var $f = $('#formTarjetaBill');

  if (isCxC()) {
    var visible = parseMonto($f.find('#importe_tarjeta_cxc_visible').val());

    if (visible > 0) {
      return visible;
    }
  }

  return parseMonto($f.find('#importe_tarjeta').val() || $f.find('#monto_efectivo, #monto_efectivo_efectivo_tarjeta').val());
}

function readAmounts() {
  var $m = $('#modal_pagos_unificado');
  var totalFactura = parseMonto($m.find('#customer_bill_pay').val());

  var amounts = {
    cash: getEfectivoRecibidoActual(),
    card: getCardAmountValue(),
    transfer: parseMonto($m.find('#formTransferenciaBill #importe_transferencia').val()),
    check: parseMonto($m.find('#formChequeBill #importe_cheque').val()),
    points: parseMonto($m.find('#formPuntosBill #importe_puntos').val())
  };

  var sumSel = Array.from(SELECTED_METHODS).reduce(function (acc, m) {
    return acc + (amounts[m] || 0);
  }, 0);

  return {
    totalFactura: totalFactura,
    amounts: amounts,
    sumSel: sumSel
  };
}

function computeAppliedAmounts() {
  var data = readAmounts();
  var totalFactura = data.totalFactura;
  var amounts = data.amounts;

  var apply = {
    cash: 0,
    card: 0,
    transfer: 0,
    check: 0,
    points: 0
  };

  if (isFactura()) {
    if (isMultiOn() && SELECTED_METHODS.size === 2) {
      apply.card = SELECTED_METHODS.has('card') ? amounts.card : 0;
      apply.transfer = SELECTED_METHODS.has('transfer') ? amounts.transfer : 0;
      apply.check = SELECTED_METHODS.has('check') ? amounts.check : 0;
      apply.points = SELECTED_METHODS.has('points') ? amounts.points : 0;

      var noCash = apply.card + apply.transfer + apply.check + apply.points;
      apply.cash = SELECTED_METHODS.has('cash') ? Math.max(totalFactura - noCash, 0) : 0;
    } else {
      var only = Array.from(SELECTED_METHODS)[0];

      if (only === 'cash') {
        apply.cash = Math.min(amounts.cash, totalFactura);
      } else if (only) {
        apply[only] = totalFactura;
      }
    }
  } else {
    apply.cash = SELECTED_METHODS.has('cash') ? amounts.cash : 0;
    apply.card = SELECTED_METHODS.has('card') ? amounts.card : 0;
    apply.transfer = SELECTED_METHODS.has('transfer') ? amounts.transfer : 0;
    apply.check = SELECTED_METHODS.has('check') ? amounts.check : 0;
    apply.points = SELECTED_METHODS.has('points') ? amounts.points : 0;
  }

  var sumApplied = Array.from(SELECTED_METHODS).reduce(function (acc, m) {
    return acc + (apply[m] || 0);
  }, 0);

  return {
    totalFactura: totalFactura,
    amounts: amounts,
    apply: apply,
    sumApplied: sumApplied
  };
}

/* ===============================
   VALIDACIONES STEP 2 -> STEP 3
   =============================== */
function validateBeforeConfirm() {
  cacheAllPaymentForms();
  updateCardAmountVisibility();

  var $m = $('#modal_pagos_unificado');
  var totalFactura = parseMonto($m.find('#customer_bill_pay').val());
  var multi = isMultiOn();

  if (totalFactura <= 0) {
    warnPago('El total a pagar debe ser mayor que 0.', '#customer_bill_pay');
    return false;
  }

  if (!multi && isFactura()) {
    if (SELECTED_METHODS.has('card')) {
      $('#formTarjetaBill #importe_tarjeta').val(fixed2(totalFactura));
      $('#formTarjetaBill #monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(fixed2(totalFactura));
    }

    if (SELECTED_METHODS.has('transfer') && parseMonto($('#formTransferenciaBill #importe_transferencia').val()) <= 0) {
      $('#formTransferenciaBill #importe_transferencia').val(fixed2(totalFactura));
    }

    if (SELECTED_METHODS.has('check') && parseMonto($('#formChequeBill #importe_cheque').val()) <= 0) {
      $('#formChequeBill #importe_cheque').val(fixed2(totalFactura));
    }
  }

  if (SELECTED_METHODS.has('cash')) {
    var efectivo = getEfectivoRecibidoActual();

    if (efectivo <= 0) {
      warnPago('El efectivo recibido debe ser mayor que 0.', '#efectivo_bill');
      return false;
    }

    if (isFactura() && !multi && efectivo + 0.0001 < totalFactura) {
      warnPago('En facturación, el efectivo debe ser igual o mayor al valor a cobrar.', '#efectivo_bill');
      return false;
    }

    if (isCxC() && efectivo > totalFactura + 0.0001) {
      warnPago('En CxC, el efectivo recibido no puede ser mayor al saldo.', '#efectivo_bill');
      return false;
    }
  }

  if (SELECTED_METHODS.has('card')) {
    var tarjeta = getCardAmountValue();
    var totalFactura = parseMonto($('#modal_pagos_unificado #customer_bill_pay').val());
    var multi = isMultiOn();

    if (isCxC()) {
      // Regla para CxC: mayor que cero y menor o igual al saldo
      if (tarjeta <= 0) {
        warnPago('En CxC, el importe de tarjeta debe ser mayor que 0.', '#importe_tarjeta');
        return false;
      }

      if (tarjeta > totalFactura + 0.0001) {
        warnPago('En CxC, el importe de tarjeta no puede ser mayor al saldo pendiente.', '#importe_tarjeta');
        return false;
      }
    } else {
      // Regla para Factura: igual al total (y ocultar el campo)
      $('#formTarjetaBill #importe_tarjeta').val(fixed2(totalFactura));
      $('#formTarjetaBill #monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(fixed2(totalFactura));
      
      // Si no es múltiple, validar que el monto sea exactamente el total
      if (!multi && Math.abs(tarjeta - totalFactura) > 0.005) {
        warnPago('En facturación, el importe de tarjeta debe ser igual al total a pagar.', '#cr_bill');
        return false;
      }
    }
  }

  if (SELECTED_METHODS.has('transfer')) {
    var transferencia = parseMonto($('#formTransferenciaBill #importe_transferencia').val());

    if (transferencia <= 0) {
      warnPago('El importe de transferencia debe ser mayor que 0.', '#importe_transferencia');
      return false;
    }

    if (isFactura()) {
      if (Math.abs(transferencia - totalFactura) > 0.005 && !multi) {
        warnPago('En facturación, la transferencia debe ser igual al valor a cobrar.', '#importe_transferencia');
        return false;
      }
    }

    if (isCxC() && transferencia > totalFactura + 0.0001) {
      warnPago('En CxC, la transferencia no puede ser mayor al saldo.', '#importe_transferencia');
      return false;
    }
  }

  if (SELECTED_METHODS.has('check')) {
    var cheque = parseMonto($('#formChequeBill #importe_cheque').val());

    if (cheque <= 0) {
      warnPago('El importe de cheque debe ser mayor que 0.', '#importe_cheque');
      return false;
    }

    if (isFactura()) {
      if (Math.abs(cheque - totalFactura) > 0.005 && !multi) {
        warnPago('En facturación, el cheque debe ser igual al valor a cobrar.', '#importe_cheque');
        return false;
      }
    }

    if (isCxC() && cheque > totalFactura + 0.0001) {
      warnPago('En CxC, el cheque no puede ser mayor al saldo.', '#importe_cheque');
      return false;
    }
  }

  if (SELECTED_METHODS.has('points')) {
    var puntosDisponibles = parseMonto($('#formPuntosBill #puntos_disponibles').val());
    var puntosUsar = parseMonto($('#formPuntosBill #puntos_uso').val());
    var importePuntos = parseMonto($('#formPuntosBill #importe_puntos').val());

    if (puntosDisponibles <= 0) {
      warnPago('Los puntos disponibles deben ser mayores que 0.', '#puntos_disponibles');
      return false;
    }

    if (puntosUsar <= 0) {
      warnPago('Los puntos a usar deben ser mayores que 0.', '#puntos_uso');
      return false;
    }

    if (importePuntos <= 0) {
      warnPago('El equivalente en lempiras de los puntos debe ser mayor que 0.', '#puntos_uso');
      return false;
    }

    if (importePuntos > totalFactura + 0.0001) {
      warnPago('El importe de puntos no puede ser mayor al valor a cobrar.', '#puntos_uso');
      return false;
    }
  }

  var data = readAmounts();
  var amounts = data.amounts;
  var sumSel = data.sumSel;

  if (multi) {
    if (isFactura()) {
      if (!SELECTED_METHODS.has('cash')) {
        warnPago('En facturación, el pago múltiple debe incluir efectivo.', '#efectivo_bill');
        return false;
      }

      var noCashSum = 0;

      if (SELECTED_METHODS.has('card')) noCashSum += amounts.card;
      if (SELECTED_METHODS.has('transfer')) noCashSum += amounts.transfer;
      if (SELECTED_METHODS.has('check')) noCashSum += amounts.check;
      if (SELECTED_METHODS.has('points')) noCashSum += amounts.points;

      if (noCashSum > totalFactura + 0.0001) {
        warnPago('La parte no efectiva no puede exceder el total.', null);
        return false;
      }
    } else {
      if (sumSel > totalFactura + 0.0001) {
        var focusM = LAST_SELECTED && SELECTED_METHODS.has(LAST_SELECTED)
          ? LAST_SELECTED
          : Array.from(SELECTED_METHODS)[0];

        warnPago('En CxC, la suma de métodos no puede exceder el saldo.', firstFieldByMethod[focusM]);
        return false;
      }
    }
  }

  hydrateSelectedPaymentForms();
  syncPaymentConfirm();

  return true;
}

function warnPago(msg, selectorToFocus) {
  notifyPago('warning', 'Atención', msg);

  if (selectorToFocus) {
    setTimeout(async function () {
      await forceFocus(selectorToFocus);
    }, 180);
  }
}

/* ===============================
   CACHE Y EMPAQUETADO
   =============================== */
function escapeSelectorName(name) {
  return String(name || '').replace(/([ #;?%&,.+*~\':"!^$[\]()=>|/@])/g, '\\$1');
}

function cleanPickerText(text) {
  text = (text || '').toString().replace(/\s+/g, ' ').trim();

  var invalid = [
    '',
    'Banco',
    'Usuario que Recibe',
    'Seleccione',
    'Seleccionar',
    'Nothing selected',
    'No hay selección'
  ];

  return invalid.indexOf(text) >= 0 ? '' : text;
}

function getBootstrapSelectText($select) {
  if (!$select || !$select.length) return '';

  var text = '';
  var $wrap = $select.closest('.bootstrap-select');

  if ($wrap.length) {
    text = cleanPickerText($wrap.find('.filter-option-inner-inner').first().text());
  }

  if (!text) {
    text = cleanPickerText($select.find('option:selected').text());
  }

  return text;
}

function getRealFieldValue($field) {
  if (!$field || !$field.length) return '';

  var tag = ($field.prop('tagName') || '').toLowerCase();
  var type = ($field.attr('type') || '').toLowerCase();

  if (type === 'checkbox') {
    return $field.is(':checked') ? '1' : '0';
  }

  var value = ($field.val() || '').toString().trim();

  if (tag === 'select' && value === '') {
    value = getBootstrapSelectText($field);
  }

  return value;
}

function setRealFieldValue($field, value) {
  if (!$field || !$field.length) return;

  value = value === null || typeof value === 'undefined' ? '' : String(value);

  var tag = ($field.prop('tagName') || '').toLowerCase();
  var type = ($field.attr('type') || '').toLowerCase();

  if (type === 'checkbox') {
    $field.prop('checked', value === '1' || value === 'true');
    $field.val(value === '1' || value === 'true' ? 1 : 0);
    return;
  }

  if (tag === 'select') {
    if (value !== '') {
      var exists = false;

      $field.find('option').each(function () {
        if (String($(this).val()) === value) {
          exists = true;
          return false;
        }
      });

      if (!exists) {
        $field.append($('<option>', {
          value: value,
          text: value,
          selected: true
        }));
      }
    }

    $field.val(value);

    if ($.fn.selectpicker && $field.hasClass('selectpicker')) {
      try {
        $field.selectpicker('val', value);
        $field.selectpicker('refresh');
      } catch (_) {}
    }

    return;
  }

  $field.val(value);
}

function ensureNamedHidden($form, name, value) {
  if (!$form || !$form.length || !name) return;

  value = value === null || typeof value === 'undefined' ? '' : String(value);

  var selector = '[name="' + escapeSelectorName(name) + '"]';
  var $field = $form.find(selector).first();

  if ($field.length) {
    setRealFieldValue($field, value);
    $field.prop('disabled', false);
  } else {
    $('<input>', {
      type: 'hidden',
      name: name,
      value: value
    }).appendTo($form);
  }
}

function formKeyFromId(formId) {
  switch (formId) {
    case 'formEfectivoBill': return 'efectivo';
    case 'formTarjetaBill': return 'tarjeta';
    case 'formTransferenciaBill': return 'transferencia';
    case 'formChequeBill': return 'cheque';
    case 'formPuntosBill': return 'puntos';
    default: return '';
  }
}

function formByKey(key) {
  switch (key) {
    case 'efectivo': return $('#formEfectivoBill');
    case 'tarjeta': return $('#formTarjetaBill');
    case 'transferencia': return $('#formTransferenciaBill');
    case 'cheque': return $('#formChequeBill');
    case 'puntos': return $('#formPuntosBill');
    default: return $();
  }
}

function cachePaymentForm($form) {
  if (!$form || !$form.length) return;

  var key = formKeyFromId($form.attr('id'));

  if (!key) return;

  if (!PAYMENT_MODAL_CACHE[key]) {
    PAYMENT_MODAL_CACHE[key] = {};
  }

  $form.find('input, select, textarea').each(function () {
    var $field = $(this);
    var name = $field.attr('name');
    var id = $field.attr('id');
    var value = getRealFieldValue($field);

    if (name) {
      if (value !== '' || typeof PAYMENT_MODAL_CACHE[key][name] === 'undefined') {
        PAYMENT_MODAL_CACHE[key][name] = value;
      }
    }

    if (id) {
      if (value !== '' || typeof PAYMENT_MODAL_CACHE[key][id] === 'undefined') {
        PAYMENT_MODAL_CACHE[key][id] = value;
      }
    }
  });

  var cliente = $('#modal_pagos_unificado #customer-name-bill').text().trim();
  var total = $('#modal_pagos_unificado #customer_bill_pay').val();

  if (cliente && cliente !== '—') {
    PAYMENT_MODAL_CACHE.cliente_pago = cliente;
    PAYMENT_MODAL_CACHE.cliente_nombre = cliente;
  }

  if (total !== '') {
    PAYMENT_MODAL_CACHE.total_pago = total;
  }
}

function cacheAllPaymentForms() {
  cachePaymentForm($('#formEfectivoBill'));
  cachePaymentForm($('#formTarjetaBill'));
  cachePaymentForm($('#formTransferenciaBill'));
  cachePaymentForm($('#formChequeBill'));
  cachePaymentForm($('#formPuntosBill'));
}

function getCacheValue(key, name, fallback) {
  fallback = fallback === null || typeof fallback === 'undefined' ? '' : fallback;

  if (
    PAYMENT_MODAL_CACHE[key] &&
    typeof PAYMENT_MODAL_CACHE[key][name] !== 'undefined' &&
    PAYMENT_MODAL_CACHE[key][name] !== ''
  ) {
    return PAYMENT_MODAL_CACHE[key][name];
  }

  return fallback;
}

function hydrateCommonFields($form, key) {
  if (!$form || !$form.length) return;

  var total = $('#modal_pagos_unificado #customer_bill_pay').val() || PAYMENT_MODAL_CACHE.total_pago || '0.00';
  var cliente = $('#modal_pagos_unificado #customer-name-bill').text().trim() || PAYMENT_MODAL_CACHE.cliente_pago || '';
  var tipoFinal = isCxC() ? 2 : (isMultiOn() ? 2 : 1);
  var printVal = $('#modal_pagos_unificado #comprobante_print_switch').is(':checked') ? 1 : 0;
  var multipleVal = $('#modal_pagos_unificado #pagos_multiples_switch').is(':checked') ? 1 : 0;

  ensureNamedHidden($form, 'cliente_pago', cliente);
  ensureNamedHidden($form, 'cliente_nombre', cliente);
  ensureNamedHidden($form, 'total_pago', fixed2(total));
  ensureNamedHidden($form, 'customer_bill_pay', fixed2(total));
  ensureNamedHidden($form, 'origen_pago', CURRENT_ORIGEN || '');
  ensureNamedHidden($form, 'comprobante_print', printVal);
  ensureNamedHidden($form, 'multiple_pago', multipleVal);

  if (key === 'efectivo' || key === 'tarjeta') {
    ensureNamedHidden($form, 'tipo_factura', tipoFinal);
  }

  if (key === 'transferencia') {
    ensureNamedHidden($form, 'tipo_factura_transferencia', tipoFinal);
  }

  if (key === 'cheque') {
    ensureNamedHidden($form, 'tipo_factura_cheque', tipoFinal);
  }

  if (key === 'puntos') {
    ensureNamedHidden($form, 'tipo_factura_puntos', tipoFinal);
  }
}

function hydrateCashForm() {
  var $form = $('#formEfectivoBill');

  if (!$form.length) return;

  var calc = computeAppliedAmounts();
  var entregado = getEfectivoRecibidoActual();

  if (entregado <= 0) {
    entregado = parseMonto(getCacheValue('efectivo', 'efectivo_bill', 0));
  }

  var aplicado = calc.apply.cash || parseMonto($form.find('#monto_efectivo, #monto_efectivo_efectivo').val()) || entregado;
  var cambio = parseMonto($form.find('#cambio_efectivo').val());

  if (isFactura() && !isMultiOn()) {
    var total = parseMonto($('#modal_pagos_unificado #customer_bill_pay').val());
    cambio = Math.max(entregado - total, 0);
    aplicado = Math.min(total, entregado);
  }

  if (isCxC() || isMultiOn()) {
    cambio = 0;
  }

  hydrateCommonFields($form, 'efectivo');

  ensureNamedHidden($form, 'fecha_efectivo', getCacheValue('efectivo', 'fecha_efectivo', $form.find('#fecha_efectivo').val() || todaySafe()));
  ensureNamedHidden($form, 'factura_id_efectivo', CURRENT_FACTURA_ID || getCacheValue('efectivo', 'factura_id_efectivo', ''));
  ensureNamedHidden($form, 'monto_efectivo', fixed2(aplicado));
  ensureNamedHidden($form, 'efectivo_bill', fixed2(entregado));
  ensureNamedHidden($form, 'efectivo_bill_bk', fixed2(entregado));
  ensureNamedHidden($form, 'cambio_efectivo', fixed2(cambio));
  ensureNamedHidden($form, 'cambio_efectivo_bk', fixed2(cambio));
  ensureNamedHidden($form, 'usuario_efectivo', getCacheValue('efectivo', 'usuario_efectivo', $form.find('#usuario_efectivo').val() || ''));
}

function hydrateCardForm() {
  var $form = $('#formTarjetaBill');

  if (!$form.length) return;

  updateCardAmountVisibility();

  var $m = $('#modal_pagos_unificado');
  var totalFactura = parseMonto($m.find('#customer_bill_pay').val());
  var importe = 0;

  if (isCxC()) {
    // CxC: usa el importe que escribió el usuario
    importe = getCardAmountValue();

    if (importe <= 0) {
      importe = 0;
    }
  } else {
    // Facturación: tarjeta siempre debe aplicar el total exacto
    importe = totalFactura;
  }

  var numero = $form.find('#cr_bill').val() || getCacheValue('tarjeta', 'cr_bill', '') || '';
  var exp = $form.find('#exp').val() || getCacheValue('tarjeta', 'exp', '') || '';
  var aprob = $form.find('#cvcpwd').val() || getCacheValue('tarjeta', 'cvcpwd', '') || '';
  var usuario = getRealFieldValue($form.find('#usuario_tarjeta')) || getCacheValue('tarjeta', 'usuario_tarjeta', '');
  var banco = getRealFieldValue($form.find('#bk_nm_tarjeta')) || getCacheValue('tarjeta', 'banco_id_tarjeta', '');

  hydrateCommonFields($form, 'tarjeta');

  ensureNamedHidden($form, 'fecha_tarjeta', getCacheValue('tarjeta', 'fecha_tarjeta', $form.find('#fecha_tarjeta').val() || todaySafe()));
  ensureNamedHidden($form, 'factura_id_tarjeta', CURRENT_FACTURA_ID || getCacheValue('tarjeta', 'factura_id_tarjeta', ''));
  ensureNamedHidden($form, 'monto_efectivo', fixed2(importe));
  ensureNamedHidden($form, 'importe_tarjeta', fixed2(importe));
  ensureNamedHidden($form, 'cr_bill', numero);
  ensureNamedHidden($form, 'exp', exp);
  ensureNamedHidden($form, 'cvcpwd', aprob);
  ensureNamedHidden($form, 'usuario_tarjeta', usuario);
  ensureNamedHidden($form, 'numero_tarjeta_bk', numero);
  ensureNamedHidden($form, 'expiracion_bk', String(exp).replace('/', ''));
  ensureNamedHidden($form, 'numero_aprobacion_bk', aprob);
  ensureNamedHidden($form, 'banco_id_tarjeta', banco || '0');

  $form.find('#monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(fixed2(importe));
  $form.find('#importe_tarjeta').val(fixed2(importe));

  if (isCxC()) {
    $form.find('#importe_tarjeta_cxc_visible').val(fixed2(importe));
  }
}

function hydrateTransferForm() {
  var $form = $('#formTransferenciaBill');

  if (!$form.length) return;

  var calc = computeAppliedAmounts();
  var importe = calc.apply.transfer || parseMonto($form.find('#importe_transferencia').val()) || parseMonto(getCacheValue('transferencia', 'importe_transferencia', 0));

  var banco = getRealFieldValue($form.find('#bk_nm')) || getCacheValue('transferencia', 'bk_nm', '');
  var autorizacion = $form.find('#ben_nm').val() || getCacheValue('transferencia', 'ben_nm', '') || getCacheValue('transferencia', 'ref_transferencia', '');
  var usuario = getRealFieldValue($form.find('#usuario_transferencia')) || getCacheValue('transferencia', 'usuario_transferencia', '');

  hydrateCommonFields($form, 'transferencia');

  ensureNamedHidden($form, 'fecha_transferencia', getCacheValue('transferencia', 'fecha_transferencia', $form.find('#fecha_transferencia').val() || todaySafe()));
  ensureNamedHidden($form, 'factura_id_transferencia', CURRENT_FACTURA_ID || getCacheValue('transferencia', 'factura_id_transferencia', ''));
  ensureNamedHidden($form, 'monto_efectivo', fixed2(importe));
  ensureNamedHidden($form, 'importe_transferencia', fixed2(importe));
  ensureNamedHidden($form, 'bk_nm', banco);
  ensureNamedHidden($form, 'ben_nm', autorizacion);
  ensureNamedHidden($form, 'ref_transferencia', autorizacion);
  ensureNamedHidden($form, 'banco_id_transferencia', banco || '0');
  ensureNamedHidden($form, 'cta_transferencia', getCacheValue('transferencia', 'cta_transferencia', ''));
  ensureNamedHidden($form, 'usuario_transferencia', usuario);
}

function hydrateChequeForm() {
  var $form = $('#formChequeBill');

  if (!$form.length) return;

  var calc = computeAppliedAmounts();
  var importe = calc.apply.check || parseMonto($form.find('#importe_cheque').val()) || parseMonto(getCacheValue('cheque', 'importe_cheque', 0));

  var banco = getRealFieldValue($form.find('#bk_nm_chk')) || getCacheValue('cheque', 'bk_nm_chk', '');
  var cheque = $form.find('#check_num').val() || getCacheValue('cheque', 'check_num', '') || '';
  var bancoTexto = getBootstrapSelectText($form.find('#bk_nm_chk')) || getCacheValue('cheque', 'banco_cheque', '');
  var usuario = getRealFieldValue($form.find('#usuario_cheque')) || getCacheValue('cheque', 'usuario_cheque', '');

  hydrateCommonFields($form, 'cheque');

  ensureNamedHidden($form, 'fecha_cheque', getCacheValue('cheque', 'fecha_cheque', $form.find('#fecha_cheque').val() || todaySafe()));
  ensureNamedHidden($form, 'factura_id_cheque', CURRENT_FACTURA_ID || getCacheValue('cheque', 'factura_id_cheque', ''));
  ensureNamedHidden($form, 'monto_efectivo', fixed2(importe));
  ensureNamedHidden($form, 'importe_cheque', fixed2(importe));
  ensureNamedHidden($form, 'bk_nm_chk', banco);
  ensureNamedHidden($form, 'check_num', cheque);
  ensureNamedHidden($form, 'numero_cheque', cheque);
  ensureNamedHidden($form, 'banco_id_cheque', banco || '0');
  ensureNamedHidden($form, 'banco_cheque', bancoTexto);
  ensureNamedHidden($form, 'usuario_cheque', usuario);
}

function hydratePuntosForm() {
  var $form = $('#formPuntosBill');

  if (!$form.length) return;

  var calc = computeAppliedAmounts();
  var importe = calc.apply.points || parseMonto($form.find('#importe_puntos').val()) || parseMonto(getCacheValue('puntos', 'importe_puntos', 0));
  var puntos = $form.find('#puntos_uso').val() || getCacheValue('puntos', 'puntos_uso', '') || getCacheValue('puntos', 'puntos_usar', '');
  var equivalente = $form.find('#equivalente_puntos').val() || getCacheValue('puntos', 'equivalente_puntos', '') || getCacheValue('puntos', 'equivalente_lempiras', '');
  var usuario = getRealFieldValue($form.find('#usuario_puntos')) || getCacheValue('puntos', 'usuario_puntos', '');

  hydrateCommonFields($form, 'puntos');

  ensureNamedHidden($form, 'fecha_puntos', getCacheValue('puntos', 'fecha_puntos', $form.find('#fecha_puntos').val() || todaySafe()));
  ensureNamedHidden($form, 'factura_id_puntos', CURRENT_FACTURA_ID || getCacheValue('puntos', 'factura_id_puntos', ''));
  ensureNamedHidden($form, 'puntos_disponibles', getCacheValue('puntos', 'puntos_disponibles', $form.find('#puntos_disponibles').val() || ''));
  ensureNamedHidden($form, 'puntos_uso', puntos);
  ensureNamedHidden($form, 'puntos_usar', puntos);
  ensureNamedHidden($form, 'equivalente_lempiras', equivalente);
  ensureNamedHidden($form, 'equivalente_puntos', equivalente);
  ensureNamedHidden($form, 'importe_puntos', fixed2(importe));
  ensureNamedHidden($form, 'usuario_puntos', usuario);
}

function hydratePaymentFormByKey(key) {
  cacheAllPaymentForms();

  switch (key) {
    case 'efectivo':
      hydrateCashForm();
      break;
    case 'tarjeta':
      hydrateCardForm();
      break;
    case 'transferencia':
      hydrateTransferForm();
      break;
    case 'cheque':
      hydrateChequeForm();
      break;
    case 'puntos':
      hydratePuntosForm();
      break;
  }

  var $form = formByKey(key);
  $form.find(':input').prop('disabled', false);

  cachePaymentForm($form);
}

function hydrateSelectedPaymentForms() {
  ensureAllFacturaIds();
  cacheAllPaymentForms();

  if (SELECTED_METHODS.has('cash')) hydratePaymentFormByKey('efectivo');
  if (SELECTED_METHODS.has('card')) hydratePaymentFormByKey('tarjeta');
  if (SELECTED_METHODS.has('transfer')) hydratePaymentFormByKey('transferencia');
  if (SELECTED_METHODS.has('check')) hydratePaymentFormByKey('cheque');
  if (SELECTED_METHODS.has('points')) hydratePaymentFormByKey('puntos');
}

/* ===============================
   PACKERS
   =============================== */
function packCashForSubmit() {
  hydrateCashForm();
}

function packCardForSubmit() {
  hydrateCardForm();
}

function packTransferForSubmit() {
  hydrateTransferForm();
}

function packChequeForSubmit() {
  hydrateChequeForm();
}

function packPuntosForSubmit() {
  hydratePuntosForm();
}

function ensureFacturaId(method) {
  var $f = formMap[method] && formMap[method]();
  var field = facturaIdFieldByMethod[method];

  if ($f && field) {
    var $inp = $f.find('[name="' + field + '"]');

    if ($inp.length) {
      $inp.val(CURRENT_FACTURA_ID || '');
    }
  }
}

function ensureAllFacturaIds() {
  var fid = CURRENT_FACTURA_ID || '';

  $('#formEfectivoBill [name="factura_id_efectivo"]').val(fid);
  $('#formTarjetaBill [name="factura_id_tarjeta"]').val(fid);
  $('#formTransferenciaBill [name="factura_id_transferencia"]').val(fid);
  $('#formChequeBill [name="factura_id_cheque"]').val(fid);
  $('#formPuntosBill [name="factura_id_puntos"]').val(fid);
}

function syncAmountToForm(method, override) {
  var $f = formMap[method] && formMap[method]();

  if (!$f || !$f.length) return 0;

  var srcVal = typeof override === 'number' ? override : 0;

  if (typeof override !== 'number') {
    if (method === 'card') {
      srcVal = getCardAmountValue();
    } else {
      srcVal = parseMonto($(amountFieldByMethod[method]).val());
    }
  }

  var uiVal = fixed2(srcVal);

  if (method === 'cash') {
    ensureNamedHidden($f, 'monto_efectivo', uiVal);
    return parseMonto(uiVal);
  }

  if (method === 'card') {
    ensureNamedHidden($f, 'importe_tarjeta', uiVal);
    ensureNamedHidden($f, 'monto_efectivo', uiVal);
    $f.find('#importe_tarjeta').val(uiVal);
    $f.find('#monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(uiVal);
    return parseMonto(uiVal);
  }

  if (method === 'transfer') {
    ensureNamedHidden($f, 'importe_transferencia', uiVal);
    ensureNamedHidden($f, 'monto_efectivo', uiVal);
    return parseMonto(uiVal);
  }

  if (method === 'check') {
    ensureNamedHidden($f, 'importe_cheque', uiVal);
    ensureNamedHidden($f, 'monto_efectivo', uiVal);
    return parseMonto(uiVal);
  }

  if (method === 'points') {
    ensureNamedHidden($f, 'importe_puntos', uiVal);
    return parseMonto(uiVal);
  }

  return parseMonto(uiVal);
}

/* ===============================
   AJAX
   =============================== */
function handleServerResponse(resp) {
  try {
    if (resp && typeof resp.funcion === 'string' && resp.funcion.trim()) {
      try {
        eval(resp.funcion);
      } catch (e) {
         showNotify('warning', 'Aviso', 'No se pudo completar la acción solicitada.');
      }
    }
  } catch (_) {}

  if (typeof showNotify === 'function') {
    showNotify(
      resp && resp.status ? 'success' : 'error',
      resp && resp.title ? resp.title : (resp && resp.status ? 'Éxito' : 'Error'),
      resp && resp.message ? resp.message : ''
    );
  } else {
    alert(
      (resp && resp.status ? 'OK: ' : 'ERROR: ')
      + (resp && resp.title ? resp.title : '')
      + '\n'
      + (resp && resp.message ? resp.message : '')
    );
  }

  if (resp && resp.closeAllModals) {
    $('#modal_pagos_unificado').modal('hide');
  }
}

function submitFormAjax($f) {
  if (!$f || !$f.length) {
    return $.Deferred().reject({
      status: false,
      title: 'Error',
      message: 'Formulario de pago no encontrado.'
    }).promise();
  }

  cacheAllPaymentForms();

  var key = formKeyFromId($f.attr('id'));

  if (key) {
    hydratePaymentFormByKey(key);
  }

  $f.find(':input').prop('disabled', false);

  var payload = $f.serialize();

  return $.ajax({
    type: $f.attr('method') || 'POST',
    url: $f.attr('action'),
    data: payload,
    dataType: 'json'
  }).then(function (resp) {
    return resp;
  }, function (xhr) {
    try {
      return $.Deferred().reject(JSON.parse(xhr.responseText)).promise();
    } catch (_) {
      return $.Deferred().reject({
        status: false,
        title: 'Error',
        message: xhr.statusText || 'Ajax error'
      }).promise();
    }
  });
}

function doMultiplePayment() {
  var order = ['points', 'card', 'transfer', 'check', 'cash'];
  var forms = order
    .filter(function (m) {
      return SELECTED_METHODS.has(m);
    })
    .map(function (m) {
      return formMap[m]();
    })
    .filter(function ($f) {
      return $f && $f.length;
    });

  var chain = Promise.resolve();
  var lastResp = null;

  forms.forEach(function ($f) {
    chain = chain.then(function () {
      return submitFormAjax($f).then(function (r) {
        lastResp = r;
        return r;
      });
    });
  });

  return chain.then(function () {
    return lastResp;
  });
}

/* ===============================
   RESUMEN Y CONFIRMACIÓN
   =============================== */
function calcularCambioEfectivo() {
  var $f = $('#modal_pagos_unificado #formEfectivoBill');

  if (!$f.length) return;

  var efectivo = getEfectivoRecibidoActual();
  var monto = parseMonto($('#modal_pagos_unificado #customer_bill_pay').val());

  CASH_TYPED = efectivo;

  if (isMultiOn() || isCxC()) {
    CASH_CAMBIO = 0;
    $f.find('#cambio_efectivo').val('0.00');
    return;
  }

  var cambio = Math.max(efectivo - monto, 0);

  CASH_CAMBIO = cambio;

  $f.find('#cambio_efectivo').val(cambio > 0 ? fixed2(cambio) : '');
}

function calcularResumenPagosUnificado() {
  var $m = $('#modal_pagos_unificado');
  var calc = computeAppliedAmounts();
  var totalFactura = calc.totalFactura;
  var amounts = calc.amounts;
  var apply = calc.apply;

  var efectivoRecibido = amounts.cash || 0;
  var cambio = 0;

  if (isFactura() && !isMultiOn() && SELECTED_METHODS.has('cash')) {
    cambio = Math.max(efectivoRecibido - totalFactura, 0);
  }

  var totalAplicado = 0;

  Array.from(SELECTED_METHODS).forEach(function (m) {
    totalAplicado += apply[m] || 0;
  });

  var diferencia = totalFactura - totalAplicado;

  if (SELECTED_METHODS.has('cash')) {
    $m.find('#formEfectivoBill #cambio_efectivo').val(cambio > 0 ? fixed2(cambio) : '');
  }

  return {
    totalFactura: totalFactura,
    efectivoRecibido: efectivoRecibido,
    efectivoAplicado: apply.cash || 0,
    cambio: cambio,
    transferencia: apply.transfer || 0,
    cheque: apply.check || 0,
    tarjeta: apply.card || 0,
    puntos: apply.points || 0,
    totalAplicado: totalAplicado,
    diferencia: diferencia
  };
}

function syncPaymentConfirm() {
  var $m = $('#modal_pagos_unificado');

  if (!$m.length) return;

  var cliente = $m.find('#customer-name-bill').text().trim() || '—';
  var resumen = calcularResumenPagosUnificado();

  $m.find('#confirm-customer-name').text(cliente);
  $m.find('#confirm-total-amount').text('L. ' + fmtMiles(resumen.totalFactura));
  $m.find('#confirm-print-option').text($m.find('#comprobante_print_switch').is(':checked') ? 'Sí' : 'No');
  $m.find('#confirm-multi-option').text($m.find('#pagos_multiples_switch').is(':checked') ? 'Activado' : 'Desactivado');

  var html = '';

  if (SELECTED_METHODS.has('cash')) {
    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-primary mr-1"></i> Efectivo recibido</span>'
      + '  <strong>L. ' + fmtMiles(resumen.efectivoRecibido) + '</strong>'
      + '</div>';

    if (resumen.cambio > 0) {
      html += ''
        + '<div class="confirm-method-row text-success">'
        + '  <span><i class="fas fa-circle text-success mr-1"></i> Cambio</span>'
        + '  <strong>L. ' + fmtMiles(resumen.cambio) + '</strong>'
        + '</div>';
    }

    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-info mr-1"></i> Efectivo aplicado</span>'
      + '  <strong>L. ' + fmtMiles(resumen.efectivoAplicado) + '</strong>'
      + '</div>';
  }

  if (SELECTED_METHODS.has('card')) {
    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-primary mr-1"></i> Tarjeta</span>'
      + '  <strong>L. ' + fmtMiles(resumen.tarjeta) + '</strong>'
      + '</div>';
  }

  if (SELECTED_METHODS.has('transfer')) {
    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-primary mr-1"></i> Transferencia</span>'
      + '  <strong>L. ' + fmtMiles(resumen.transferencia) + '</strong>'
      + '</div>';
  }

  if (SELECTED_METHODS.has('check')) {
    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-primary mr-1"></i> Cheque</span>'
      + '  <strong>L. ' + fmtMiles(resumen.cheque) + '</strong>'
      + '</div>';
  }

  if (SELECTED_METHODS.has('points')) {
    html += ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-warning mr-1"></i> Puntos</span>'
      + '  <strong>L. ' + fmtMiles(resumen.puntos) + '</strong>'
      + '</div>';
  }

  if (html.trim() === '') {
    html = ''
      + '<div class="confirm-method-row">'
      + '  <span><i class="fas fa-circle text-muted mr-1"></i> Sin método aplicado</span>'
      + '  <strong>L. 0.00</strong>'
      + '</div>';
  }

  $m.find('#confirm-methods-list').html(html);
  $m.find('#confirm-total-apply').text('L. ' + fmtMiles(resumen.totalAplicado));

  var diferenciaAbs = Math.abs(resumen.diferencia);

  $m.find('#confirm-difference').text('L. ' + fmtMiles(diferenciaAbs));

  if (diferenciaAbs <= 0.01) {
    $m.find('#difference-line').removeClass('text-danger').addClass('text-success ok');
  } else {
    $m.find('#difference-line').removeClass('text-success ok').addClass('text-danger');
  }
}

function buildConfirmSummary() {
  cacheAllPaymentForms();
  hydrateSelectedPaymentForms();

  var $m = $('#modal_pagos_unificado');
  var calc = computeAppliedAmounts();

  LAST_APPLIED_AMOUNTS = calc.apply;

  syncPaymentConfirm();

  var canConfirm = false;

  if (isCxC()) {
    canConfirm = calc.sumApplied > 0 && calc.sumApplied <= calc.totalFactura + 0.0001;
  } else {
    canConfirm = calc.sumApplied >= calc.totalFactura - 0.005;
  }

  $m.find('#btnConfirmPay')
    .prop('disabled', !canConfirm)
    .off('click.pagoConfirm')
    .on('click.pagoConfirm', function () {
      var $btn = $(this);

      $btn.prop('disabled', true);

      hydrateSelectedPaymentForms();

      var tipoFinal = isCxC() ? 2 : (isMultiOn() ? 2 : 1);

      $('#formEfectivoBill #tipo_factura, #tipo_factura_efectivo').val(tipoFinal);
      $('#formTarjetaBill #tipo_factura, #tipo_factura_efectivo').val(tipoFinal);
      $('#formTransferenciaBill #tipo_factura, #tipo_factura_efectivo_transferencia').val(tipoFinal);
      $('#formChequeBill #tipo_factura, #tipo_factura_efectivo_cheque').val(tipoFinal);
      $('#formPuntosBill #tipo_factura, #tipo_factura_efectivo_puntos').val(tipoFinal);

      var printVal = $('#comprobante_print_switch').is(':checked') ? 1 : 0;

      $('.comprobante_print_value').val(printVal);

      ensureAllFacturaIds();

      var applyVal = function (m) {
        return parseFloat(fixed2(LAST_APPLIED_AMOUNTS[m] || 0));
      };

      if (SELECTED_METHODS.has('cash')) {
        syncAmountToForm('cash', applyVal('cash'));
        packCashForSubmit();
      }

      if (SELECTED_METHODS.has('card')) {
        syncAmountToForm('card', applyVal('card'));
        packCardForSubmit();
      }

      if (SELECTED_METHODS.has('transfer')) {
        syncAmountToForm('transfer', applyVal('transfer'));
        packTransferForSubmit();
      }

      if (SELECTED_METHODS.has('check')) {
        syncAmountToForm('check', applyVal('check'));
        packChequeForSubmit();
      }

      if (SELECTED_METHODS.has('points')) {
        syncAmountToForm('points', applyVal('points'));
        packPuntosForSubmit();
      }

      var finishOk = function (resp) {
        handleServerResponse(resp || {
          status: true,
          title: 'Éxito',
          message: 'Pago registrado.'
        });
      };

      var finishErr = function (resp) {
        $btn.prop('disabled', false);

        handleServerResponse(resp || {
          status: false,
          title: 'Error',
          message: 'No se pudo registrar el pago.'
        });
      };

      if (isMultiOn() && SELECTED_METHODS.size === 2) {
        $('.multiple_pago').val(1);
        hydrateSelectedPaymentForms();

        doMultiplePayment().then(finishOk).catch(finishErr);
      } else {
        $('.multiple_pago').val(0);

        var only = Array.from(SELECTED_METHODS)[0];
        var $f = formMap[only]();

        $f.find('.comprobante_print_value').val(printVal);
        $f.find('.multiple_pago').val(0);

        submitFormAjax($f).then(finishOk).catch(finishErr);
      }
    });
}

/* ===============================
   CONFIGURAR FORMULARIOS
   =============================== */
function configurarFormularioEfectivo(facturas_id, tipoPago, monto, origen) {
  var $f = $('#modal_pagos_unificado #formEfectivoBill');

  if ($f[0]) $f[0].reset();

  var m = parseMonto(monto);

  $f.find('#monto_efectivo, #monto_efectivo_efectivo').val(fixed2(m));
  $f.find('#factura_id_efectivo').val(facturas_id);
  $f.find('#tipo_factura, #tipo_factura_efectivo').val(tipoPago);
  $f.find('#origen_pago, #origen_pago_efectivo').val(origen);
  $f.find('#pago_efectivo').prop('disabled', true);
  $f.find('#efectivo_bill').prop('required', true);

  if (isMultiOn() || isCxC()) {
    $f.find('#grupo_cambio_efectivo').hide();
    $f.find('#cambio_efectivo').val('0.00');
  } else {
    $f.find('#grupo_cambio_efectivo').show();
  }

  CASH_TYPED = 0;
  CASH_CAMBIO = 0;

  calcularCambioEfectivo();
}

function configurarFormularioTarjeta(facturas_id, tipoPago, monto, origen) {
  var $f = $('#modal_pagos_unificado #formTarjetaBill');

  if ($f[0]) $f[0].reset();

  ensureCardAmountCxCInput();

  var m = parseMonto(monto);

  $f.find('#monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(fixed2(m));
  $f.find('#importe_tarjeta').val(fixed2(m));
  $f.find('#factura_id_tarjeta').val(facturas_id);
  $f.find('#tipo_factura, #tipo_factura_efectivo').val(tipoPago);
  $f.find('#origen_pago, #origen_pago_efectivo').val(origen);
  $f.find('#pago_tarjeta').prop('disabled', false);
  $f.find('#cr_bill, #exp, #cvcpwd').prop('required', false);

  updateCardAmountVisibility();
}

function configurarFormularioTransferencia(facturas_id, tipoPago, monto, origen) {
  var $f = $('#modal_pagos_unificado #formTransferenciaBill');

  if ($f[0]) $f[0].reset();

  var m = parseMonto(monto);

  $f.find('#monto_efectivo, #monto_efectivo_efectivo').val(fixed2(m));
  $f.find('#factura_id_transferencia').val(facturas_id);
  $f.find('#tipo_factura, #tipo_factura_efectivo_transferencia').val(tipoPago);
  $f.find('#origen_pago, #origen_pago_efectivo').val(origen);
  $f.find('#pago_transferencia').prop('disabled', false);
  $f.find('#importe_transferencia').prop('required', true);

  // Banco opcional
  $f.find('#bk_nm').prop('required', false).removeAttr('required');
}

function configurarFormularioCheque(facturas_id, tipoPago, monto, origen) {
  var $f = $('#modal_pagos_unificado #formChequeBill');

  if ($f[0]) $f[0].reset();

  var m = parseMonto(monto);

  $f.find('#monto_efectivo, #monto_efectivo_efectivo').val(fixed2(m));
  $f.find('#factura_id_cheque').val(facturas_id);
  $f.find('#tipo_factura, #tipo_factura_efectivo_cheque').val(tipoPago);
  $f.find('#origen_pago, #origen_pago_efectivo').val(origen);
  $f.find('#pago_cheque').prop('disabled', false);
  $f.find('#importe_cheque').prop('required', true);

  // Banco opcional
  $f.find('#bk_nm_chk').prop('required', false).removeAttr('required');
}

function configurarFormularioPuntos(facturas_id, tipoPago, monto, origen) {
  var $f = $('#modal_pagos_unificado #formPuntosBill');

  if ($f[0]) $f[0].reset();

  $f.find('#factura_id_puntos').val(facturas_id);
  $f.find('#tipo_factura, #tipo_factura_efectivo_puntos').val(tipoPago);
  $f.find('#origen_pago, #origen_pago_efectivo').val(origen);
  $f.find('#importe_puntos').val('0');
  $f.find('#puntos_disponibles').val('');
  $f.find('#puntos_uso, #puntos_usar').val('');
  $f.find('#equivalente_puntos').val('');
  $f.find('#pago_puntos').prop('disabled', true);
  $f.find('#puntos_uso').prop('required', true);
}

/* ===============================
   DATOS DEL PAGO
   =============================== */
function extractPagoData(datos) {
  return extractPagoDataRobusto(datos);
}

function extractPagoDataRobusto(datos, totalManual, clienteManual) {
  var cliente = '';
  var total = 0;

  if (typeof clienteManual !== 'undefined' && clienteManual !== null && String(clienteManual).trim() !== '') {
    cliente = String(clienteManual).trim();
  }

  if (typeof totalManual !== 'undefined' && totalManual !== null && parseMonto(totalManual) > 0) {
    total = parseMonto(totalManual);
  }

  if (Array.isArray(datos) && datos.length > 0 && (typeof datos[0] !== 'object' || datos[0] === null)) {
    if (!cliente && typeof datos[0] !== 'undefined') {
      cliente = String(datos[0] || '').trim();
    }

    if (total <= 0) {
      var posicionesPreferidas = [6, 7, 5, 4, 3, 2, 1];

      for (var i = 0; i < posicionesPreferidas.length; i++) {
        var idx = posicionesPreferidas[i];

        if (typeof datos[idx] !== 'undefined' && parseMonto(datos[idx]) > 0) {
          total = parseMonto(datos[idx]);
          break;
        }
      }

      if (total <= 0) {
        var nums = datos
          .map(function (x) {
            return parseMonto(x);
          })
          .filter(function (x) {
            return x > 0;
          });

        if (nums.length) {
          total = nums[nums.length - 1];
        }
      }
    }

    return {
      cliente: cliente || '—',
      total: parseMonto(total)
    };
  }

  var row = datos;

  if (Array.isArray(datos)) {
    row = datos.length > 0 ? datos[0] : {};
  } else if (datos && Array.isArray(datos.data)) {
    row = datos.data.length > 0 ? datos.data[0] : {};
  } else if (datos && Array.isArray(datos.datos)) {
    row = datos.datos.length > 0 ? datos.datos[0] : {};
  } else if (datos && datos.data && typeof datos.data === 'object') {
    row = datos.data;
  } else if (datos && datos.datos && typeof datos.datos === 'object') {
    row = datos.datos;
  } else if (datos && datos.factura && typeof datos.factura === 'object') {
    row = datos.factura;
  } else if (datos && datos.result && typeof datos.result === 'object') {
    row = datos.result;
  }

  row = row || {};

  if (!cliente) {
    cliente =
      row.cliente ||
      row.nombre_cliente ||
      row.clientes_nombre ||
      row.nombre ||
      row.customer ||
      row.customer_name ||
      row.razon_social ||
      row.cliente_nombre ||
      '';
  }

  if (total <= 0) {
    total =
      row.saldo ||
      row.total_pagar ||
      row.total ||
      row.importe ||
      row.total_factura ||
      row.pagar ||
      row.monto ||
      row.valor ||
      0;
  }

  return {
    cliente: cliente || '—',
    total: parseMonto(total)
  };
}

function cargarPuntosClienteAjax(facturas_id) {
  var $m = $('#modal_pagos_unificado');

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/programaPuntos/getPuntosCliente.php',
    data: { facturas_id: facturas_id },
    dataType: 'json'
  }).done(function (resp) {
    var $f = $m.find('#formPuntosBill');

    if (!resp || resp.error || resp.success === false) {
      VALOR_POR_PUNTO = 1;
      $f.find('#puntos_disponibles').val('0');
      $f.find('#puntos_uso, #puntos_usar').val('');
      $f.find('#equivalente_puntos').val('');
      $f.find('#importe_puntos').val('0');
      $m.find('#pago_puntos').prop('disabled', true);
      return;
    }

    VALOR_POR_PUNTO = parseFloat(resp.valor_por_punto || 1) || 1;

    var disponibles = parseFloat(resp.puntos_disponibles || 0);
    var montoFactura = parseMonto($m.find('#customer_bill_pay').val());
    var maxPorMonto = Math.floor(montoFactura / VALOR_POR_PUNTO);
    var sugeridos = Math.min(maxPorMonto, Math.floor(disponibles));

    $f.find('#puntos_disponibles').val(fixed2(disponibles));
    $f.find('#puntos_uso, #puntos_usar').attr('placeholder', sugeridos > 0 ? ('Hasta ' + sugeridos + ' pts') : '0');
    $f.find('#equivalente_puntos').val('');
    $f.find('#importe_puntos').val('0');
    $m.find('#pago_puntos').prop('disabled', true);
  }).fail(function () {
    var $f = $m.find('#formPuntosBill');

    VALOR_POR_PUNTO = 1;

    $f.find('#puntos_disponibles').val('0');
    $f.find('#puntos_uso, #puntos_usar').val('');
    $f.find('#equivalente_puntos').val('');
    $f.find('#importe_puntos').val('0');
    $m.find('#pago_puntos').prop('disabled', true);
  });
}

/* ===============================
   RESET
   =============================== */
function limpiarMontosPagoUnificado() {
  var $m = $('#modal_pagos_unificado');

  $m.find('#formEfectivoBill #efectivo_bill').val('');
  $m.find('#formEfectivoBill #cambio_efectivo').val('');

  $m.find('#formTarjetaBill #importe_tarjeta_cxc_visible').val('');

  if (isCxC()) {
    $m.find('#formTarjetaBill #importe_tarjeta').val('');
    $m.find('#formTarjetaBill #monto_efectivo, #monto_efectivo_efectivo_tarjeta').val('');
  }

  $m.find('#formTransferenciaBill #importe_transferencia').val('');
  $m.find('#formChequeBill #importe_cheque').val('');

  $m.find('#formPuntosBill #puntos_uso').val('');
  $m.find('#formPuntosBill #puntos_usar').val('');
  $m.find('#formPuntosBill #equivalente_puntos').val('');
  $m.find('#formPuntosBill #importe_puntos').val('0');

  syncPaymentConfirm();
}

function resetPaymentCache() {
  PAYMENT_MODAL_CACHE = {
    cliente_pago: '',
    cliente_nombre: '',
    total_pago: '',
    efectivo: {},
    tarjeta: {},
    transferencia: {},
    cheque: {},
    puntos: {}
  };

  window.PAYMENT_MODAL_CACHE = PAYMENT_MODAL_CACHE;
}

function hardResetModalState() {
  var $m = $('#modal_pagos_unificado');

  CURRENT_STEP = 1;
  SELECTED_METHODS = new Set(['cash']);
  LAST_SELECTED = 'cash';

  LAST_APPLIED_AMOUNTS = {
    cash: 0,
    card: 0,
    transfer: 0,
    check: 0,
    points: 0
  };

  CASH_TYPED = 0;
  CASH_CAMBIO = 0;

  CURRENT_FACTURA_ID = null;
  CURRENT_TIPO_PAGO = 1;
  CURRENT_ORIGEN = '';

  resetPaymentCache();

  $m.find('#comprobante_print_switch').prop('checked', false).val(0);
  $m.find('#label_print_comprobant').text('No');

  $m.find('#pagos_multiples_switch').prop('checked', false).val(0);
  $m.find('#label_pagos_multiples').text('Desactivado');

  $m.find('.comprobante_print_value').val(0);
  $m.find('.multiple_pago').val(0);

  $m.find('form').each(function () {
    this.reset();
  });

  $m.find('input[type="text"], input[type="number"], input[type="tel"]').val('');
  $m.find('#cambio_efectivo').val('0.00');
  $m.find('.RespuestaAjax').empty();

  $m.find('#section_methods').show();
  $m.find('#section_details').hide();
  $m.find('#section_confirm').hide();

  $m.find('.payment-details.payment-step').removeClass('active').hide();

  updateMethodsUI();
  setStepActive(1);
}

/* ===============================
   FUNCIÓN GLOBAL PARA ABRIR MODAL
   =============================== */
function pago(facturas_id, tipoPago, origen, totalManual, clienteManual) {
  var $m = $('#modal_pagos_unificado');
  var url = '<?php echo SERVERURL;?>core/editarPagoFacturas.php';

  hardResetModalState();

  CURRENT_FACTURA_ID = facturas_id;
  CURRENT_TIPO_PAGO = typeof tipoPago === 'undefined' || tipoPago === null ? 1 : tipoPago;
  CURRENT_ORIGEN = origen || '';

  if (typeof getCollaboradoresModalPagoFacturas === 'function') {
    getCollaboradoresModalPagoFacturas();
  }

  if (typeof getBanco === 'function') {
    getBanco();
  }

  $.ajax({
    type: 'POST',
    url: url,
    data: { facturas_id: facturas_id },
    dataType: 'json'
  }).done(function (datos) {
    var info = extractPagoDataRobusto(datos, totalManual, clienteManual);
    var cliente = info.cliente || '—';
    var total = parseMonto(info.total);

    $m.find('#customer-name-bill').text(cliente);
    $m.find('#customer_bill_pay').val(fixed2(total));
    $m.find('#bill-pay').text('L. ' + fmtMiles(total));

    $m.data('clientePago', cliente);
    $m.data('totalPago', total);

    configurarFormularioEfectivo(facturas_id, CURRENT_TIPO_PAGO, total, CURRENT_ORIGEN);
    configurarFormularioTarjeta(facturas_id, CURRENT_TIPO_PAGO, total, CURRENT_ORIGEN);
    configurarFormularioTransferencia(facturas_id, CURRENT_TIPO_PAGO, total, CURRENT_ORIGEN);
    configurarFormularioCheque(facturas_id, CURRENT_TIPO_PAGO, total, CURRENT_ORIGEN);
    configurarFormularioPuntos(facturas_id, CURRENT_TIPO_PAGO, total, CURRENT_ORIGEN);

    limpiarMontosPagoUnificado();

    goToStep(1);

    $m.modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });

    $m.off('keydown.blockEsc').on('keydown.blockEsc', function (e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        e.stopPropagation();
      }
    });

    $m.off('click.stopBackdrop').on('click.stopBackdrop', function (e) {
      if ($(e.target).is('.modal')) {
        e.preventDefault();
        e.stopPropagation();
      }
    });

    try {
      var modalData = $m.data('bs.modal');

      if (modalData && modalData._config) {
        modalData._config.backdrop = 'static';
        modalData._config.keyboard = false;
      }
    } catch (_) {}

    if ($.fn.selectpicker) {
      setTimeout(function () {
        $m.find('.selectpicker').selectpicker('refresh');
      }, 250);
    }

    setTimeout(function () {
      updateCardAmountVisibility();
      cacheAllPaymentForms();
      syncPaymentConfirm();
    }, 300);

  }).fail(function (xhr) {
    showNotify('error', 'Error', 'No se pudo cargar la información del pago.');

    handleServerResponse({
      status: false,
      title: 'Error',
      message: 'No se pudieron cargar los datos del pago.'
    });
  });
}

window.pago = pago;

/* ===============================
   EVENTOS
   =============================== */
function initPagoUnificado() {
  var $m = $('#modal_pagos_unificado');

  if (!$m.length) return;

  if ($m.data('pago-unificado-init') === 1) return;

  $m.data('pago-unificado-init', 1);

  ensureCardAmountCxCInput();

  $m.find('[data-toggle="tooltip"]').tooltip();

  if (!$('#payment-button-styles').length) {
    var buttonStyles = ''
      + '<style id="payment-button-styles">'
      + '.payment-btn{position:relative;overflow:hidden;transition:all .3s ease;transform:translateY(0px);}'
      + '.payment-btn:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,0,0,.2);}'
      + '.payment-btn:active{transform:translateY(0px);transition:all .1s ease;}'
      + '.payment-btn::before{content:"";position:absolute;top:50%;left:50%;width:0;height:0;background:rgba(255,255,255,.3);border-radius:50%;transform:translate(-50%,-50%);transition:width .6s,height .6s;}'
      + '.payment-btn:active::before{width:300px;height:300px;}'
      + '.payment-btn-prev{background:linear-gradient(135deg,#ffc107 0%,#ff8f00 100%);border:none;color:white;}'
      + '.payment-btn-next{background:linear-gradient(135deg,#007bff 0%,#0056b3 100%);border:none;color:white;}'
      + '.payment-btn-close{background:linear-gradient(135deg,#6c757d 0%,#495057 100%);border:none;color:white;}'
      + '.confirm-submit-btn{background:linear-gradient(135deg,#28a745 0%,#20c997 100%);border:none;}'
      + '.focus-field{border-color:#007bff!important;box-shadow:0 0 0 .2rem rgba(0,123,255,.25)!important;}'
      + '.error-field{border-color:#dc3545!important;box-shadow:0 0 0 .2rem rgba(220,53,69,.25)!important;}'
      + '.confirm-method-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px dashed #dbe5ef;gap:12px;}'
      + '.confirm-method-row:last-child{border-bottom:0;}'
      + '.confirm-method-row strong{white-space:nowrap;}'
      + '</style>';

    $('head').append(buttonStyles);
  }

  // Focus automático en el campo importe_tarjeta cuando es CxC y se entra al step 2
  $('#modal_pagos_unificado').off('focus.tarjetaCxC').on('focus.tarjetaCxC', function() {
    if (CURRENT_STEP === 2 && SELECTED_METHODS.has('card') && isCxC()) {
      setTimeout(function() {
        var $campo = $('#importe_tarjeta');
        if ($campo.length && $campo.is(':visible')) {
          $campo.focus();
        }
      }, 200);
    }
  });

  $m.off('click.pagoMethods keypress.pagoMethods', '#paymentMethodsGrid .method-card')
    .on('click.pagoMethods keypress.pagoMethods', '#paymentMethodsGrid .method-card', function (e) {
      if (e.type === 'click' || (e.type === 'keypress' && (e.which === 13 || e.which === 32))) {
        var method = $(this).data('method');

        toggleMethod(method);

        if (CURRENT_STEP === 2) {
          setTimeout(async function () {
            await focusFirstFieldFor(method);
          }, 120);
        }
      }
    });

  $m.find('#btnPrev').off('click.pagoPrev').on('click.pagoPrev', function () {
    cacheAllPaymentForms();

    if (CURRENT_STEP === 2) {
      goToStep(1);
    } else if (CURRENT_STEP === 3) {
      goToStep(2);
    }
  });

  $m.find('#btnNext').off('click.pagoNext').on('click.pagoNext', function () {
    cacheAllPaymentForms();

    if (CURRENT_STEP === 1) {
      if (SELECTED_METHODS.size === 0) {
        notifyPago('warning', 'Atención', 'Selecciona al menos un método.');
        return;
      }

      // IMPORTANTE: del step 1 al 2 NO se validan importes.
      goToStep(2);
      return;
    }

    if (CURRENT_STEP === 2) {
      if (!validateBeforeConfirm()) {
        return;
      }

      goToStep(3);
      setTimeout(syncPaymentConfirm, 100);
    }
  });

  if ($.fn.inputmask) {
    $m.find('#formTarjetaBill #cr_bill').inputmask('9999', {
      placeholder: '',
      clearIncomplete: false,
      greedy: false,
      showMaskOnHover: false,
      showMaskOnFocus: false
    });

    $m.find('#formTarjetaBill #exp').inputmask('99/99', {
      placeholder: '',
      clearIncomplete: false,
      greedy: false,
      showMaskOnHover: false,
      showMaskOnFocus: false
    });

    $m.find('#formTarjetaBill #cvcpwd').inputmask('999999', {
      placeholder: '',
      clearIncomplete: false,
      greedy: false,
      showMaskOnHover: false,
      showMaskOnFocus: false
    });
  }

  $m.off('change.pagoPrint', '#comprobante_print_switch')
    .on('change.pagoPrint', '#comprobante_print_switch', function () {
      var on = $(this).is(':checked');

      $(this).val(on ? 1 : 0);

      $m.find('.comprobante_print_value').val(on ? 1 : 0);
      $m.find('#label_print_comprobant').text(on ? 'Sí' : 'No');

      cacheAllPaymentForms();
      syncPaymentConfirm();
    });

  $m.off('change.pagoMulti', '#pagos_multiples_switch')
    .on('change.pagoMulti', '#pagos_multiples_switch', function () {
      var on = $(this).is(':checked');

      $(this).val(on ? 1 : 0);

      $m.find('.multiple_pago').val(on ? 1 : 0);
      $m.find('#label_pagos_multiples').text(on ? 'Activado' : 'Desactivado');

      var tipo = isCxC() ? 2 : (on ? 2 : 1);

      $('#formEfectivoBill #tipo_factura, #tipo_factura_efectivo').val(tipo);
      $('#formTarjetaBill #tipo_factura, #tipo_factura_efectivo').val(tipo);
      $('#formTransferenciaBill #tipo_factura, #tipo_factura_efectivo_transferencia').val(tipo);
      $('#formChequeBill #tipo_factura, #tipo_factura_efectivo_cheque').val(tipo);
      $('#formPuntosBill #tipo_factura, #tipo_factura_efectivo_puntos').val(tipo);

      if (!on && SELECTED_METHODS.size > 1) {
        var keep = LAST_SELECTED && SELECTED_METHODS.has(LAST_SELECTED)
          ? LAST_SELECTED
          : Array.from(SELECTED_METHODS)[0];

        SELECTED_METHODS = new Set([keep]);
      }

      updateMethodsUI();

      if (CURRENT_STEP === 2) {
        showDetailsForSelected();
      }

      cacheAllPaymentForms();
      syncPaymentConfirm();
    });

  $m.off('input.pagoMoney', '#formEfectivoBill #efectivo_bill, #formTransferenciaBill #importe_transferencia, #formChequeBill #importe_cheque, #formTarjetaBill #importe_tarjeta_cxc_visible')
    .on('input.pagoMoney', '#formEfectivoBill #efectivo_bill, #formTransferenciaBill #importe_transferencia, #formChequeBill #importe_cheque, #formTarjetaBill #importe_tarjeta_cxc_visible', function (e) {
      var cur = e.target.selectionStart;
      var orig = $(this).val();
      var clean = normalizarMontoEntrada(orig);

      $(this).val(clean);

      try {
        e.target.setSelectionRange(cur + (clean.length - orig.length), cur + (clean.length - orig.length));
      } catch (_) {}

      if ($(this).attr('id') === 'efectivo_bill') {
        calcularCambioEfectivo();
      }

      if ($(this).attr('id') === 'importe_tarjeta_cxc_visible') {
        $('#formTarjetaBill #importe_tarjeta').val(clean);
        $('#formTarjetaBill #monto_efectivo, #monto_efectivo_efectivo_tarjeta').val(clean);
      }

      cachePaymentForm($(this).closest('form'));
      syncPaymentConfirm();
    });

  // Eventos para formatear el campo importe_tarjeta
  $('#modal_pagos_unificado').off('input.pagoTarjetaFormat blur.pagoTarjetaFormat', '#importe_tarjeta')
    .on('input.pagoTarjetaFormat', '#importe_tarjeta', function(e) {
      var cur = e.target.selectionStart;
      var orig = $(this).val();
      var clean = normalizarMontoEntrada(orig);
      $(this).val(clean);
      try {
        e.target.setSelectionRange(cur + (clean.length - orig.length), cur + (clean.length - orig.length));
      } catch(_) {}
      calcularCambioEfectivo();
      syncPaymentConfirm();
    })
    .on('blur.pagoTarjetaFormat', '#importe_tarjeta', function() {
      var v = $(this).val();
      if (v) {
        $(this).val(fixed2Seguro(v));
      }
      calcularCambioEfectivo();
      syncPaymentConfirm();
    });

  $m.off('blur.pagoMoney', '#formEfectivoBill #efectivo_bill, #formTransferenciaBill #importe_transferencia, #formChequeBill #importe_cheque, #formTarjetaBill #importe_tarjeta_cxc_visible')
    .on('blur.pagoMoney', '#formEfectivoBill #efectivo_bill, #formTransferenciaBill #importe_transferencia, #formChequeBill #importe_cheque, #formTarjetaBill #importe_tarjeta_cxc_visible', function () {
      var v = $(this).val();

      if (v) {
        $(this).val(fixed2(v));
      }

      if ($(this).attr('id') === 'efectivo_bill') {
        calcularCambioEfectivo();
      }

      if ($(this).attr('id') === 'importe_tarjeta_cxc_visible') {
        $('#formTarjetaBill #importe_tarjeta').val($(this).val());
        $('#formTarjetaBill #monto_efectivo, #monto_efectivo_efectivo_tarjeta').val($(this).val());
      }

      cachePaymentForm($(this).closest('form'));
      syncPaymentConfirm();
    });

  $m.off('input.pagoPuntos', '#formPuntosBill #puntos_uso, #formPuntosBill #puntos_usar')
    .on('input.pagoPuntos', '#formPuntosBill #puntos_uso, #formPuntosBill #puntos_usar', function () {
      var val = (this.value || '').replace(/[^\d]/g, '');

      this.value = val;

      var pts = parseInt(val || '0', 10);
      var disp = parseFloat(($m.find('#formPuntosBill #puntos_disponibles').val() || '0').replace(',', '.')) || 0;
      var monto = parseMonto($m.find('#customer_bill_pay').val());

      var ptsCap = Math.max(0, Math.min(pts, Math.floor(disp)));

      if (ptsCap !== pts) {
        this.value = String(ptsCap);
      }

      var eq = Math.min(ptsCap * VALOR_POR_PUNTO, monto);

      $m.find('#formPuntosBill #puntos_uso').val(ptsCap ? String(ptsCap) : '');
      $m.find('#formPuntosBill #puntos_usar').val(ptsCap ? String(ptsCap) : '');
      $m.find('#formPuntosBill #equivalente_puntos').val(eq > 0 ? fixed2(eq) : '');
      $m.find('#formPuntosBill #importe_puntos').val(fixed2(eq));
      $m.find('#pago_puntos').prop('disabled', !(eq > 0));

      cachePaymentForm($(this).closest('form'));
      syncPaymentConfirm();
    });

  $m.off('submit.pagoAjax', '.FormularioAjax')
    .on('submit.pagoAjax', '.FormularioAjax', function (e) {
      e.preventDefault();
      return false;
    });

  $m.off('shown.bs.modal.pago').on('shown.bs.modal.pago', function () {
    setTimeout(function () {
      $('#paymentMethodsGrid .method-card.default-focus').focus();
      updateCardAmountVisibility();
      syncPaymentConfirm();
    }, 220);
  });

  $m.off('hidden.bs.modal.pago').on('hidden.bs.modal.pago', function () {
    hardResetModalState();
  });

  $m.off('focus.pagoInputs', 'input[type="text"], input[type="number"], input[type="tel"], input[type="password"]')
    .on('focus.pagoInputs', 'input[type="text"], input[type="number"], input[type="tel"], input[type="password"]', function () {
      if (CURRENT_STEP === 2) {
        $(this).addClass('focus-field');
      }
    });

  $m.off('blur.pagoInputs', 'input[type="text"], input[type="number"], input[type="tel"], input[type="password"]')
    .on('blur.pagoInputs', 'input[type="text"], input[type="number"], input[type="tel"], input[type="password"]', function () {
      $(this).removeClass('focus-field');
    });

  $(document)
    .off('input.paymentCache change.paymentCache', '#modal_pagos_unificado input, #modal_pagos_unificado select, #modal_pagos_unificado textarea')
    .on('input.paymentCache change.paymentCache', '#modal_pagos_unificado input, #modal_pagos_unificado select, #modal_pagos_unificado textarea', function () {
      cachePaymentForm($(this).closest('form'));
    });

  $(document)
    .off('click.paymentCache', '#modal_pagos_unificado .dropdown-menu .dropdown-item, #modal_pagos_unificado .dropdown-menu li, #modal_pagos_unificado .bootstrap-select .dropdown-menu a')
    .on('click.paymentCache', '#modal_pagos_unificado .dropdown-menu .dropdown-item, #modal_pagos_unificado .dropdown-menu li, #modal_pagos_unificado .bootstrap-select .dropdown-menu a', function () {
      var $form = $(this).closest('form');

      setTimeout(function () {
        cachePaymentForm($form);
      }, 80);
    });

  updateMethodsUI();
}

/* Inicializar sin usar $(document).ready directo */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPagoUnificado);
} else {
  initPagoUnificado();
}

/* FIN NUEVO PAGO */

// Función para obtener los colaboradores
function getCollaboradoresModalPagoFacturas() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getCollaboradores.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            const $selects = $('#modal_pagos_unificado #usuario_efectivo, #modal_pagos_unificado #usuario_tarjeta, #modal_pagos_unificado #usuario_transferencia, #modal_pagos_unificado #usuario_cheque, #modal_pagos_unificado #usuario_puntos');

            $selects.empty();

            if (response.success && response.data && response.data.length) {
                response.data.forEach(function(user) {
                    const userId = user.colaboradores_id || user.users_id || user.usuario_id || user.id || '';
                    const userNombre = user.nombre || user.colaborador || user.usuario || user.name || '';

                    if (userId !== '' && userNombre !== '') {
                        $selects.append(
                            '<option value="' + userId + '">' + userNombre + '</option>'
                        );
                    }
                });
            }

            if ($selects.find('option').length === 0) {
                $selects.append('<option value="">No hay usuarios disponibles</option>');
            }

            if ($.fn.selectpicker) {
                $selects.selectpicker('refresh');
            }
        },
        error: function() {
            showNotify("error", "Error", "No se pudieron cargar los usuarios");
        }
    });
}

// Función para obtener los bancos
function getBanco() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getBanco.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            const $selects = $('#formTransferenciaBill #bk_nm, #formChequeBill #bk_nm_chk');
            $selects.empty();

            if (response.success && response.data && response.data.length) {
                response.data.forEach(function(banco) {
                    const bancoId = banco.bancos_id || banco.banco_id || banco.id || banco.cuentas_id || '';
                    const bancoNombre = banco.nombre || banco.banco || banco.descripcion || '';

                    if (bancoId !== '' && bancoNombre !== '') {
                        $selects.append(
                            '<option value="' + bancoId + '">' + bancoNombre + '</option>'
                        );
                    }
                });
            }

            if ($selects.find('option').length === 0) {
                $selects.append('<option value="">No hay bancos disponibles</option>');
            }

            $selects.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "No se pudieron cargar los bancos");
        }
    });
}
// FIN MODAL REGISTRAR PAGO FACTURACIÓN CLIENTES
  
//INICIO ABONO CXC
$(document).ready(function() {
    $("#ver_abono_cxc").on('shown.bs.modal', function() {
        $(this).find('#formulario_ver_abono_cxc #buscar').focus();
    });
});

var listar_AbonosCXC = function() {
    var factura_id = $("#formulario_ver_abono_cxc #abono_facturas_id").val();

    var table_cuentas_por_cobrar_clientes = $("#table-modal-abonos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/getAbonosCXC.php",
            "data": {
                "factura_id": factura_id
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "tipo_pago"
            },
            {
                "data": "descripcion"
            },
            {
                "data": "abono"
            },
            {
                "data": "usuario"
            },
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "20%",
                targets: 0
            },
            {
                width: "15%",
                targets: 1
            },
            {
                width: "25%",
                targets: 2
            },
            {
                width: "15%",
                targets: 3
            },
            {
                width: "25%",
                targets: 4
            }
        ],
        "fnRowCallback": function(nRow, res, iDisplayIndex, iDisplayIndexFull) {
            $('#ver_abono_cxcTitle').html('Factura: ' + res['no_factura'] + ' Cliente: ' + res[
                'cliente'] + ' Total Factura: L. ' + res['importe'])
            $('#total-footer-modal-cxc').html('L. ' + res['total'])
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Abonos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_AbonosCXC();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Abonos Cuentas por Cobrar Clientes',
                messageTop: 'Factura: ' + getNumeroFactura(factura_id) + ' ' + getNombreClienteFactura(
                    factura_id) + ' Total Factura: L. ' + getImporteFacturas(factura_id),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Abonos Cuentas por Cobrar Clientes',
                messageTop: 'Factura: ' + getNumeroFactura(factura_id) + ' ' + getNombreClienteFactura(
                    factura_id) + ' Total Factura: L. ' + getImporteFacturas(factura_id),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                customize: function(doc) {
                    if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
                        doc.content.splice(0, 0, {
                            image: imagen,  
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_cuentas_por_cobrar_clientes.search('').draw();
    $('#buscar').focus();
}

function getNombreClienteFactura(factura_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreClienteFactura.php';
    var cliente = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'factura_id=' + factura_id,
        success: function(data) {
            var datos = eval(data);
            cliente = datos[0];
        }
    });

    return cliente;
}

function getImporteFacturas(factura_id) {
    var url = '<?php echo SERVERURL; ?>core/getImporteFacturas.php';
    var importe = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'factura_id=' + factura_id,
        success: function(data) {
            var datos = eval(data);
            importe = datos[0];
        }
    });

    return importe;
}
//FIN ABONO CXC

//INICIO CXP PROVEEDOR
$(document).ready(function() {
    $("#ver_abono_cxc").on('shown.bs.modal', function() {
        $(this).find('#formulario_ver_abono_cxc #buscar').focus();
    });
});

var listar_AbonosCXP = function() {
    var compras_id = $("#formulario_ver_abono_cxp #abono_compras_id").val();

    var table_cuentas_por_cobrar_clientes = $("#table-modal-abonosCXP").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/getAbonosCXP.php",
            "data": {
                "compras_id": compras_id
            },
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "tipo_pago"
            },
            {
                "data": "descripcion"
            },
            {
                "data": "abono"
            },
            {
                "data": "usuario"
            },
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "10%",
                targets: 0
            },
            {
                width: "15%",
                targets: 1
            },
            {
                width: "35%",
                targets: 2
            },
            {
                width: "15%",
                targets: 3
            },
            {
                width: "50%",
                targets: 4
            }
        ],
        "fnRowCallback": function(nRow, res, iDisplayIndex, iDisplayIndexFull) {
            $('#ver_abono_cxPTitle').html('Factura: ' + res['factura'] + ' Proveedor: ' + res[
                'nombre'] + ' Total Factura: L. ' + res['importe'])
            $('#total-footer-modal-cxp').html('L. ' + res['total'])
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Abonos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_AbonosCXP();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Abonos Cuentas por Pagar Proveedores',
                messageTop: 'Factura: ' + getNumeroCompra(compras_id) + ' ' +
                    getNombreClienteFacturaCompras(compras_id) + ' Total Factura: L. ' +
                    getImporteCompras(compras_id),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Abonos Cuentas por por Pagar Proveedores',
                messageTop: 'Factura: ' + getNumeroCompra(compras_id) + ' ' +
                    getNombreClienteFacturaCompras(compras_id) + ' Total Factura: L. ' +
                    getImporteCompras(compras_id),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                customize: function(doc) {
                    if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
                        doc.content.splice(0, 0, {
                            image: imagen,  
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_cuentas_por_cobrar_clientes.search('').draw();
    $('#buscar').focus();
}

function getNombreClienteFacturaCompras(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreClienteFacturaCompras.php';
    var cliente = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'compras_id=' + compras_id,
        success: function(data) {
            var datos = eval(data);
            cliente = datos[0];
        }
    });

    return cliente;
}

function getImporteCompras(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/getImporteCompras.php';
    var importe = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'compras_id=' + compras_id,
        success: function(data) {
            var datos = eval(data);
            importe = datos[0];
        }
    });

    return importe;
}
//FIN ABONO CXP PROVEEDOR

//INICIO MODAL REGSITRAR PAGO COMPRAS PROVEEDORES
$(document).ready(function() {
    //INICIO PAGOS MULTIPLES COMPRAS
    $('#modal_pagosPurchase .label_pagos_multiples').html("No");

    $('#modal_pagosPurchase .switch').change(function() {
        if ($('input[name=pagos_multiples_switch]').is(':checked')) {
            $('#modal_pagosPurchase .label_pagos_multiples').html("Si");
            $('#pagos_multiples_switch').val(1);
            $('#modal_pagosPurchase .multiple_pago').val(1);
            //HABILITAR TEXTFIELD COMPRAS
            $('#formEfectivoPurchase #pago_efectivo').prop('disabled', false);
            ///TARJETA
            $('#formTarjetaPurchase #pago_tarjeta').prop('disabled', false);
            $('#formTarjetaPurchase #monto_efectivo, #monto_efectivo_efectivo_tarjeta').prop("type", "text")
            ///TRANSFERENCIA
            $('#formTransferenciaPurchase #importe_transferencia').prop("type", "text")
            //INPUTS CAMBIO
            $('#grupo_cambio_compras').hide()

            return true;
        } else {
            $('#modal_pagosPurchase .label_pagos_multiples').html("No");
            $('#pagos_multiples_switch').val(0);
            $('#modal_pagosPurchase .multiple_pago').val(0);
            //HABILITAR TEXTFIELD COMPRAS
            $('#formEfectivoPurchase #pago_efectivo').prop('disabled', true)
            ///TARJETA
            //--$('#formTarjetaPurchase #pago_tarjeta').prop('disabled', true);
            $('#formTarjetaPurchase #monto_efectivo, #monto_efectivo_efectivo_tarjeta').prop("type", "hidden")
            ///TRANSFERENCIA
            $('#formTransferenciaPurchase #importe_transferencia').prop("type", "hidden")
            //INPUTS CAMBIO
            $('#grupo_cambio_compras').show()
            return false;
        }
    });
    //FIN PAGOS MULTIPLES COMPRAS

    //INCIO PAGOS MULTIPLES FACTURAS
    $('#modal_pagos .label_pagos_multiples').html("No");

    $('#modal_pagos .switch').change(function() {
        if ($('input[name=pagos_multiples_switch]').is(':checked')) {
            $('#modal_pagos .label_pagos_multiples').html("Si");
            $('#pagos_multiples_switch').val(1);
            $('#modal_pagos .multiple_pago').val(1);
            //HABILITAR TEXTFIELD COMPRAS
            $('#formTarjetaBill #pago_efectivo').prop('disabled', false);
            ///TARJETA
            $('#formTarjetaPurchase #pago_tarjeta').prop('disabled', false);
            $('#formTarjetaPurchase #monto_efectivo, #monto_efectivo_efectivo_tarjeta').prop("type", "text")
            ///TRANSFERENCIA
            $('#formTransferenciaBill #importe_transferencia').prop("type", "text")
            //INPUTS CAMBIO
            $('#grupo_cambio_efectivo').hide()

            return true;
        } else {
            $('#modal_pagos .label_pagos_multiples').html("No");
            $('#pagos_multiples_switch').val(0);
            $('#modal_pagos .multiple_pago').val(0);
            //HABILITAR TEXTFIELD COMPRAS
            $('#formTarjetaBill #pago_efectivo').prop('disabled', true)
            ///TARJETA
            //--$('#formTarjetaPurchase #pago_tarjeta').prop('disabled', true);
            $('#formTarjetaPurchase #monto_efectivo, #monto_efectivo_efectivo_tarjeta').prop("type", "hidden")
            ///TRANSFERENCIA
            $('#formTransferenciaBill #importe_transferencia').prop("type", "hidden")
            //INPUTS CAMBIO
            $('#grupo_cambio_efectivo').show()
            return false;
        }
    });
    //FIN PAGOS MULTIPLES FACTURAS
});

//INICIO MODAL REGSITRAR PAGO COMPRAS PROVEEDORES
function pagoCompras(compras_id, saldo, tipo) {
    var url = '<?php echo SERVERURL;?>core/editarPagoCompras.php';

    $('#pagos_multiples_switch').attr('checked', false);
    getCollaboradoresModalPagoFacturasCompras();

    $.ajax({
        type: 'POST',
        url: url,
        data: 'compras_id=' + compras_id,
        success: function(valores) {
            var datos = eval(valores);
            $('#formEfectivoPurchase .border-right a:eq(0) a').tab('show');
            $("#customer-name-Purchase").html("<b>Proveedor:</b> " + datos[0]);
            $("#customer_Purchase_pay").val(datos[3]);
            $('#Purchase-pay').html("L. " + parseFloat(datos[6]).toFixed(2));

            //EFECTIVO
            $('#formEfectivoPurchase')[0].reset();
            $('#formEfectivoPurchase #monto_efectivo, #monto_efectivo_efectivoPurchase').val(datos[3]);
            $('#formEfectivoPurchase #compras_id_efectivo').val(compras_id);
            $('#formEfectivoPurchase #pago_efectivo').attr('disabled', true);
            $('#formEfectivoPurchase #tipo_purchase_efectivo').val(tipo);

            if (tipo == '2') {
                $('#monto_efectivo, #monto_efectivo_efectivo_tarjeta').attr('type', 'number');
                $('#tab5Purchase').hide();
                $('#importe_transferencia').attr('type', 'number');
                $('#importe_cheque').attr('type', 'number');
                //
                $("#formEfectivoBill #cambio_efectivo").val(0)
                $("#grupo_cambio_compras").hide();
            }

            //TARJETA
            $('#formTarjetaPurchase')[0].reset();
            $('#formTarjetaPurchase #monto_efectivo, #monto_efectivo_efectivoPurchase').val(datos[3]);
            $('#formTarjetaPurchase #compras_id_tarjeta').val(compras_id);
            $('#formTarjetaPurchase #pago_efectivo').attr('disabled', true);
            $('#formTarjetaPurchase #tipo_purchase_efectivo').val(tipo);

            //TRANSFERENCIA
            $('#formTransferenciaPurchase')[0].reset();
            $('#formTransferenciaPurchase #monto_efectivo, #monto_efectivo_efectivoPurchase').val(datos[3]);
            $('#formTransferenciaPurchase #compras_id_transferencia').val(compras_id);
            $('#formTransferenciaPurchase #pago_efectivo').attr('disabled', true);
            $('#formTransferenciaPurchase #tipo_purchase_efectivo').val(tipo);

            //CHEQUE
            $('#formChequePurchase #compras_id_cheque').val(compras_id);
            $('#formChequePurchase #tipo_purchase_efectivo').val(tipo);
            $('#formChequePurchase #monto_efectivo, #monto_efectivo_efectivoPurchase').val(datos[3]);

            $('#modal_pagosPurchase').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });

            return false;
        }
    });
}

$(document).ready(function() {
    $("#tab1Purchase").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formEfectivoPurchase #efectivo_Purchase').focus();
        });
    });

    $("#tab2Purchase").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formEfectivoPurchase #cr_Purchase').focus();
        });
    });

    $("#tab2Purchase").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formEfectivoPurchase #bk_nm').focus();
        });
    });
});

$(document).ready(function() {
    $('#formTarjetaPurchase #cr_Purchase').inputmask("9999");
});

$(document).ready(function() {
    $('#formTarjetaPurchase #exp').inputmask("99/99");
});

$(document).ready(function() {
    $('#formTarjetaPurchase #cvcpwd').inputmask("999999");
});

$(document).ready(function() {
    $("#formEfectivoPurchase #efectivo_Purchase").on("keyup", function() {
        var efectivo = parseFloat($("#formEfectivoPurchase #efectivo_Purchase").val()).toFixed(2);
        var monto = parseFloat($("#formEfectivoPurchase #monto_efectivo, #monto_efectivo_efectivoPurchase").val()).toFixed(2);
        var credito = $("#formEfectivoPurchase #tipo_purchase_efectivo").val();
        var pagos_multiples = $('#pagos_multiples_switch').val();

        if (credito == 2) {
            $("#formEfectivoPurchase #cambio_efectivoPurchase").val(0)
            $("#formEfectivoPurchase #cambio_efectivoPurchase").hide();
        }

        var total = efectivo - monto;

        //Math.floor NOS PERMITE COMPARAR UN FLOAT CONVIRTIENDOLO A ENTERO CUANDO SE MULTIPLICA POR 100

        if (Math.floor(efectivo * 100) >= Math.floor(monto * 100) || credito == 2 || pagos_multiples ==
            1) {
            $('#formEfectivoPurchase #cambio_efectivoPurchase').val(parseFloat(total).toFixed(2));
            $('#formEfectivoPurchase #pago_efectivo').attr('disabled', false);
        } else {
            $('#formEfectivoPurchase #cambio_efectivoPurchase').val(parseFloat(0).toFixed(2));
            $('#formEfectivoPurchase #pago_efectivo').attr('disabled', true);
        }
    });
});

function getBancoPurchase() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getBanco.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const selectTransferencia = $('#formTransferenciaPurchase #bk_nm');
            const selectCheque = $('#formChequePurchase #bk_nm_chk');
            
            selectTransferencia.empty();
            selectCheque.empty();
            
            if(response.success) {
                response.data.forEach(banco => {
                    const option = `
                        <option value="${banco.bancos_id}" 
                                data-subtext="${banco.cuenta || 'Sin cuenta'}">
                            ${banco.nombre}
                        </option>
                    `;
                    selectTransferencia.append(option);
                    selectCheque.append(option);
                });
            } else {
                const errorOption = '<option value="">No hay bancos disponibles</option>';
                selectTransferencia.append(errorOption);
                selectCheque.append(errorOption);
            }
            
            selectTransferencia.selectpicker('refresh');
            selectCheque.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar bancos");
            const errorOption = '<option value="">Error al cargar</option>';
            
            $('#formTransferenciaPurchase #bk_nm').html(errorOption);
            $('#formChequePurchase #bk_nm_chk').html(errorOption);
            
            $('#formTransferenciaPurchase #bk_nm').selectpicker('refresh');
            $('#formChequePurchase #bk_nm_chk').selectpicker('refresh');
        }
    });
}

// Versión adaptada para colaboradores en facturas
function getCollaboradoresModalPagoFacturas() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getColaboradores.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const selects = [
                '#formEfectivoBill #usuario_efectivo',
                '#formTarjetaBill #usuario_tarjeta',
                '#formTransferenciaBill #usuario_transferencia',
                '#formChequeBill #usuario_cheque'
            ];
            
            // Limpiar todos los selects
            selects.forEach(selector => {
                $(selector).empty();
            });
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    const option = `
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${colaborador.identidad || 'Sin identidad'}">
                            ${colaborador.nombre}
                        </option>
                    `;
                    
                    // Agregar a todos los selects
                    selects.forEach(selector => {
                        $(selector).append(option);
                    });
                });
            } else {
                const errorOption = '<option value="">No hay colaboradores disponibles</option>';
                selects.forEach(selector => {
                    $(selector).append(errorOption);
                });
            }
            
            // Refrescar todos los selects
            selects.forEach(selector => {
                $(selector).selectpicker('refresh');
            });
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            const errorOption = '<option value="">Error al cargar</option>';
            
            const selects = [
                '#formEfectivoBill #usuario_efectivo',
                '#formTarjetaBill #usuario_tarjeta',
                '#formTransferenciaBill #usuario_transferencia',
                '#formChequeBill #usuario_cheque'
            ];
            
            selects.forEach(selector => {
                $(selector).html(errorOption).selectpicker('refresh');
            });
        }
    });
}

// Versión adaptada para colaboradores en compras
function getCollaboradoresModalPagoFacturasCompras() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getColaboradores.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const selects = [
                '#formEfectivoPurchase #usuario_efectivo_compras',
                '#formTarjetaPurchase #usuario_tarjeta_compras',
                '#formTransferenciaPurchase #usuario_transferencia_compras',
                '#formChequePurchase #usuario_cheque_compras'
            ];
            
            // Limpiar todos los selects
            selects.forEach(selector => {
                $(selector).empty();
            });
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    const option = `
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${colaborador.identidad || 'Sin identidad'}">
                            ${colaborador.nombre}
                        </option>
                    `;
                    
                    // Agregar a todos los selects
                    selects.forEach(selector => {
                        $(selector).append(option);
                    });
                });
            } else {
                const errorOption = '<option value="">No hay colaboradores disponibles</option>';
                selects.forEach(selector => {
                    $(selector).append(errorOption);
                });
            }
            
            // Refrescar todos los selects
            selects.forEach(selector => {
                $(selector).selectpicker('refresh');
            });
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            const errorOption = '<option value="">Error al cargar</option>';
            
            const selects = [
                '#formEfectivoPurchase #usuario_efectivo_compras',
                '#formTarjetaPurchase #usuario_tarjeta_compras',
                '#formTransferenciaPurchase #usuario_transferencia_compras',
                '#formChequePurchase #usuario_cheque_compras'
            ];
            
            selects.forEach(selector => {
                $(selector).html(errorOption).selectpicker('refresh');
            });
        }
    });
}

//INICIO ASISTENCIA
$(document).ready(function() {
    listar_asistencia();
    getColaboradores();
    $('#form_main_asistencia #estado').val(0);
    $('#form_main_asistencia #estado').selectpicker('refresh');
});

/* =========================================================
   HEADER DINÁMICO - ASISTENCIA
   ========================================================= */

   function construirHeaderDataTableAsistencia() {
    var $tabla = $("#dataTableAsistencia");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Colaborador</th>' +
                '<th>Fecha</th>' +
                '<th>Hora Inicio</th>' +
                '<th>Hora Fin</th>' +
                '<th>Horas Trabajadas</th>' +
                '<th>Comentario</th>' +
            '</tr>' +
        '</thead>'
    );
}


//INICIO ACCIONES FROMULARIO ASISTENCIA
var listar_asistencia = function() {
    var estado = $('#form_main_asistencia #estado').val();
    var colaboradores_id = $('#form_main_asistencia #colaborador').val();
    var fechai = $('#form_main_asistencia #fechai').val();
    var fechaf = $('#form_main_asistencia #fechaf').val();

    if ($.fn.DataTable.isDataTable("#dataTableAsistencia")) {
        $("#dataTableAsistencia").DataTable().clear().destroy();
    }

    construirHeaderDataTableAsistencia();

    var table_asistencia = $("#dataTableAsistencia").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/asistencia/llenarDataTableAsistencia.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf,
                "colaborador": colaboradores_id,
                "estado": estado
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar editar_asistencia ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar eliminar_salida ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar Marcaje</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar eliminar_marcaje ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar Asistencia</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                "data": "colaborador"
            },
            {
                "data": "fecha"
            },
            {
                "data": "horai"
            },
            {
                "data": "horaf"
            },
            {
                "data": "horas_trabajadas"
            },
            {
                "data": "comentario"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "12%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "20%",
                targets: 1
            },
            {
                width: "10%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3
            },
            {
                width: "10%",
                targets: 4
            },
            {
                width: "13%",
                targets: 5
            },
            {
                width: "25%",
                targets: 6
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Asistencia',
                className: 'btn btn-secondary',
                action: function() {
                    listar_asistencia();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar Asistencia',
                titleAttr: 'Agregar Asistencia',
                className: 'btn btn-primary',
                action: function() {
                    modal_asistencia();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Asistencia',
                messageTop: 'Semana del: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-success',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte Asistencia',
                messageTop: 'Semana del: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-danger',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                },
                customize: function(doc) {
                    if (imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_asistencia.search('').draw();
    $('#buscar').focus();

    edit_asistencia_colaboradores_dataTable("#dataTableAsistencia tbody", table_asistencia);
    delete_marcaje_asistencia_colaboradores_dataTable("#dataTableAsistencia tbody", table_asistencia);
    delete_salida_asistencia_colaboradores_dataTable("#dataTableAsistencia tbody", table_asistencia);
}

var delete_salida_asistencia_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.eliminar_marcaje");
    $(tbody).on("click", "button.eliminar_marcaje", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        var nombre = data.colaborador;
        var fecha = data.fecha;
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la asistencia?<br><br>
                <strong>Nombre:</strong> ${nombre}<br>
                <strong>Fecha:</strong> ${fecha}`;

        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-light"
                },
                confirm: {
                    text: "Sí, eliminar",
                    value: true,
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {               
                deleteAsistenciaMarcajeSalidaColaborador(data.asistencia_id);
            }
        });
    });
}

function deleteAsistenciaMarcajeSalidaColaborador(asistencia_id) {
    var url = '<?php echo SERVERURL;?>core/asistencia/deleteAsistenciaAjax.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'asistencia_id=' + asistencia_id,
        success: function(response) {
            // Parsear la respuesta JSON
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.Alerta === "recargar") {
                    showNotify('success', data.Titulo, data.Texto);
                    listar_asistencia();
                } else {
                    showNotify(data.Tipo, data.Titulo, data.Texto);
                }
                swal.close();
            } catch (e) {
                // Manejo de respuestas no JSON (backward compatibility)
                if (response == 1) {
                    showNotify('success', 'Éxito', 'La asistencia ha sido eliminada correctamente');
                    listar_asistencia();
                } else {
                    showNotify('error', 'Error', 'No se pudo eliminar la asistencia');
                }
                swal.close();
            }
        },
        error: function() {
            showNotify('error', 'Error', 'Error al conectar con el servidor');
            swal.close();
        }
    });
}

var delete_marcaje_asistencia_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.eliminar_salida");
    $(tbody).on("click", "button.eliminar_salida", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        var Colaborador = data.colaborador;
        var Fecha = data.fecha;
        var horaSalida = data.horaf;

        // Validar si existe hora de salida
        if (!horaSalida || horaSalida === '--:--' || horaSalida === 'N/A') {
            showNotify('warning', 'Advertencia', 'No hay marcaje de salida para eliminar');
            return false;
        }

        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el marcaje?<br><br>
                <strong>Nombre:</strong> ${Colaborador}<br>
                <strong>Fecha:</strong> ${Fecha}<br>
                <strong>Hora Salida:</strong> ${horaSalida}`;

        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-light"
                },
                confirm: {
                    text: "Sí, eliminar",
                    value: true,
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {               
                deleteMarcajeSalida(data.asistencia_id);
            }
        });
    });
}

function deleteMarcajeSalida(asistencia_id) {
    var url = '<?php echo SERVERURL;?>core/asistencia/deleteMarcajeSalidaColaborador.php';

    $.ajax({
        type: "POST",
        url: url,
        data: {asistencia_id: asistencia_id},
        dataType: "json",
        success: function(response) {
            showNotify(response.Tipo, response.Titulo, response.Texto);
            
            if(response.Alerta === "recargar") {
                listar_asistencia();
            }

            swal.close();
        },
        error: function(xhr, status, error) {
            showNotify("error", "Error", "Ocurrió un problema al conectar con el servidor");
            swal.close();
        }
    });
}

var edit_asistencia_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.editar_asistencia");
    $(tbody).on("click", "button.editar_asistencia", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/asistencia/editarAsistencia.php';
        $('#formAsistencia')[0].reset();
        $('#formAsistencia #asistencia_id').val(data.asistencia_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formAsistencia').serialize(),
            dataType: 'json', // Asegurar que esperamos JSON
            success: function(response) {
                if(response && response.length > 0) {
                    $('#formAsistencia').attr({
                        'data-form': 'update'
                    });
                    $('#formAsistencia').attr({
                        'action': '<?php echo SERVERURL;?>core/asistencia/updateAsistenciaAjax.php'
                    });
                    $('#reg_asistencia').hide();
                    $('#edi_asistencia').show();

                    $('#formAsistencia #asistencia_empleado').val(response[0]);
                    $('#formAsistencia #asistencia_empleado').selectpicker('refresh');
                    $('#formAsistencia #fecha').val(response[1]);
                    $('#formAsistencia #horagi').val(response[2]);
                    $('#formAsistencia #horagf').val(response[3]);
                    $('#formAsistencia #comentario').val(response[4]); // Corregido el índice

                    $('#formAsistencia #grupoHora').hide();
                    $('#formAsistencia #grupoHorai').show();
                    $('#formAsistencia #grupoHoraf').show();
                    $('#formAsistencia #grupoHoraComentario').show();

                    $('#formAsistencia #proceso_asistencia').val("Editar");
                    $('#modal_registrar_asistencia').modal({
                        show: true,
                        keyboard: false,
                        backdrop: 'static'
                    });
                } else {                    
                    showNotify('error', 'Error', 'No se pudieron cargar los datos para editar');
                }
            },
            error: function(xhr, status, error) {                
                showNotify('error', 'Error', 'Ocurrió un problema al cargar los datos');
            }
        });
    });
}

function getColaboradores() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/asistencia/getColaboradoresAsistencia.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const selects = [
                '#form_main_asistencia #colaborador',
                '#formAsistencia #asistencia_empleado'
            ];
            
            selects.forEach(selector => {
                $(selector).empty();
            });
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    const option = `
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${colaborador.identidad || 'Sin identidad'}">
                            ${colaborador.nombre}
                        </option>
                    `;
                    
                    selects.forEach(selector => {
                        $(selector).append(option);
                    });
                });
            } else {
                const errorOption = '<option value="">No hay colaboradores disponibles</option>';
                selects.forEach(selector => {
                    $(selector).append(errorOption);
                });
            }
            
            selects.forEach(selector => {
                $(selector).selectpicker('refresh');
            });
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            const errorOption = '<option value="">Error al cargar</option>';
            
            $('#form_main_asistencia #colaborador').html(errorOption).selectpicker('refresh');
            $('#formAsistencia #asistencia_empleado').html(errorOption).selectpicker('refresh');
        }
    });
}

function modal_asistencia() {
    $('#formAsistencia')[0].reset();
    $('#reg_asistencia').show();
    $('#edi_asistencia').hide();
    $('#formAsistencia #proceso_asistencia').val("Registro");
    $('#formAsistencia #fechaAsistencia').show();
    getColaboradores();

    $('#formAsistencia #grupoHora').hide();
    $('#formAsistencia #grupoHorai').show();
    $('#formAsistencia #grupoHoraf').hide();
    $('#formAsistencia #grupoHoraComentario').show();

    $('#formAsistencia #marcarAsistencia_id').val(0);

    $('#modal_registrar_asistencia').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Invocamos cada 1 segundos ;)
    const milisegundos = 1 * 500;
    setInterval(function() {
        // No esperamos la respuesta de la petición porque no nos importa
        showTime();
    }, milisegundos);
});

$(document).ready(function() {
    showTime();
});

function showTime() {
    const current = new Date();

    const time = current.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false
    });

    $('#formAsistencia #hora').val(time);
}

function getColaboradorAsistencia() {
    var url = '<?php echo SERVERURL;?>core/editarUsarioSistema.php';

    var colaboradores_id;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(valores) {
            var datos = eval(valores);
            colaboradores_id = datos[0];
        }
    });

    return colaboradores_id;
}

function getHoraInicio(colaborador_id) {
    var url = '<?php echo SERVERURL;?>core/getHoraInicio.php';

    var tipo;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'colaborador_id=' + colaborador_id,
        success: function(valores) {
            var datos = eval(valores);
            tipo = datos[0];
        }
    });

    return tipo;
}
//FIN ASISTENCIA

function getImagenHeader() {
    var url = '<?php echo SERVERURL;?>core/get_image.php';

    // Obtener la URL de la imagen usando Ajax
    $.ajax({
        type: "GET",
        url: url, // Ruta al archivo PHP
        success: function(imageUrl) {
            // Actualizar la imagen en la barra de navegación
            var logoElement = $(".logo"); // Cambiar por el selector correcto
            logoElement.attr("src", imageUrl);
        },
        error: function() {

        }
    });
}

function getGithubVersion() {
    var url = '<?php echo SERVERURL;?>core/getGithubVersion.php';

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#version').text(response);
        },
        error: function() {
            $('#version').text('Error al obtener la versión.');
        }
    });
}

function getEstadoClientes() {
    var url = '<?php echo SERVERURL;?>core/getEstado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_clientes #estado_clientes').html("");
            $('#form_main_clientes #estado_clientes').html(data);
            $('#form_main_clientes #estado_clientes').selectpicker('refresh');
        }
    });
}

function getCuentasProveedores() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#modal_pagosPurchase #metodopago_efectivo_compras').html("");
            $('#modal_pagosPurchase #metodopago_efectivo_compras').html(data);
            $('#modal_pagosPurchase #metodopago_efectivo_compras').selectpicker('refresh');
        }
    });
}

$(function() {
    // Función general para contar caracteres
    const countChars = () => {
        $('textarea[charmax]').each(function() {
            const maxLength = $(this).attr('charmax');  // Obtener el valor del atributo 'charmax'
            const currentLength = $(this).val().length;  // Contar los caracteres actuales
            const remainingChars = maxLength - currentLength;  // Calcular los caracteres restantes

            // Mostrar el contador de caracteres dentro del mismo contenedor
            const countDisplay = $(this).siblings('div.char-count');  // Buscar el div .char-count dentro del mismo contenedor
            countDisplay.text(`${remainingChars} caracteres restantes`);  // Actualizar el texto
        });
    }

    // Llamar la función al cargar la página para cada textarea
    countChars();

    // Llamar la función cada vez que se escriba en el textarea
    $('textarea[charmax]').on('input', () => countChars());
});

function formatNumber(number) {
    return $.fn.dataTable.render.number(',', '.', 2, '').display(number);
}

function cargarContadorFacturasPendientes() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/contarFacturasPendientesClientes.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.type === 'success') {
                const $campana = $('#notification-bell').closest('li');
                const $contadorCampana = $('#notification-count');
                const $contadorDropdown = $('#notification-dropdown-count');
                const $badgeUsuario = $('#badge-facturas-pendientes-dropdown');

                if (response.total_pendientes > 0) {
                    // Mostrar campana
                    $campana.show();

                    // Mostrar y actualizar contadores
                    $contadorCampana.text(response.total_pendientes).show();
                    $contadorDropdown.text(response.total_pendientes);
                    $badgeUsuario.text(response.total_pendientes).show();

                    // Efecto visual
                    $campana.addClass('new-notification');
                    setTimeout(() => {
                        $campana.removeClass('new-notification');
                    }, 2000);

                    // Cambiar icono a campana llena
                    $('#notification-bell i')
                        .removeClass('far fa-bell')
                        .addClass('fas fa-bell text-warning');

                } else {
                    // Ocultar campana y contadores
                    $campana.hide();
                    $contadorCampana.hide();
                    $badgeUsuario.hide();

                    // Cambiar icono a campana vacía
                    $('#notification-bell i')
                        .removeClass('fas fa-bell text-warning')
                        .addClass('far fa-bell');
                }
            }
        },
        error: function() {
            // Manejo de errores opcional
        }
    });
}

$(() => {
    cargarContadorFacturasPendientes();
    setInterval(cargarContadorFacturasPendientes, 300000); // cada 5 minutos
});

// Función para obtener clientes
function getClientesIngresos() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getClientes.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formIngresosContables #recibide_ingresos');
            select.empty();
            
            if(response.success) {
                response.data.forEach(cliente => {
                    select.append(`
                        <option value="${cliente.clientes_id}" 
                                data-subtext="${cliente.rtn || 'Sin RTN o Identidad'}">
                            ${cliente.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay colaboradores disponibles</option>');
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            $('#formIngresosContables #recibide_ingresos').html('<option value="">Error al cargar</option>');
            $('#formIngresosContables #recibide_ingresos').selectpicker('refresh');
        }
    });
}

function getConsultarAperturaCaja() {
    var url = '<?php echo SERVERURL; ?>core/getAperturaCajaUsuario.php';

    var estado_apertura;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(registro) {
            var valores = eval(registro);
            estado_apertura = valores[0];
        }
    });
    return estado_apertura;
}

validarAperturaCajaUsuario();
    getTotalFacturasDisponibles();

/// ==============================
// COUNTER - CONTEO FACTURAS
// ==============================
$(function() {
    // Inicializar variables
    lastState = null;
    lastFacturasCount = null;
    
    // Ejecutar inmediatamente al cargar
    validarAperturaCajaUsuario();
    getTotalFacturasDisponibles();
    
    // Actualizar cada 5 segundos (5000 ms = 5 segundos)
    setInterval(() => {
        validarAperturaCajaUsuario();
        getTotalFacturasDisponibles();
    }, 5000);
});

let lastState = null;
let lastFacturasCount = null; // Nueva variable para trackear el count

function getTotalFacturasDisponibles() {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/getTotalFacturasDisponibles.php?_=' + new Date().getTime(),
        dataType: 'json'
    }).done(function(datos) {    
        updateCounterUI(datos);
    }).fail(function(jqXHR, textStatus, errorThrown) {
        showErrorState();
    });
}

function updateCounterUI(datos) {
    const { facturasPendientes, contador, fechaLimite } = datos;
    const counter = $("#mensajeFacturas");
    const daysLeft = parseInt(contador);
    
    // Determinar el estado actual
    const currentState = getCurrentState(facturasPendientes, daysLeft, fechaLimite);
    
    // Solo actualizar si cambió el estado O el número de facturas
    if (currentState !== lastState || facturasPendientes !== lastFacturasCount) {
        lastState = currentState;
        lastFacturasCount = facturasPendientes; // Guardar el nuevo count        
        
        // Aplicar efecto de cambio
        counter.addClass('state-change');
        setTimeout(() => counter.removeClass('state-change'), 300);
        
        // Configurar según estado
        const config = getStateConfig(currentState, facturasPendientes, daysLeft, fechaLimite);
        
        // Actualizar DOM
        counter.html(`<i class="${config.icon}"></i> <div class="counter-content">${config.text}</div>`)
                .removeClass('alert-normal alert-warning alert-danger')
                .addClass(config.class);
    }
    
    // Controlar botones
    updateButtonsState(facturasPendientes, fechaLimite, daysLeft);
}

function getCurrentState(facturasPendientes, daysLeft, fechaLimite) {
    if (!fechaLimite || fechaLimite.trim() === "Sin definir") return 'no-config';
    if (facturasPendientes < 0) return 'blocked';
    
    if (daysLeft < 0) return 'expired';
    if (daysLeft <= 5) return 'danger';
    if (facturasPendientes <= 9) return 'danger';
    if (facturasPendientes <= 30) return 'warning';
    
    return 'normal';
}

function getStateConfig(state, facturasPendientes, daysLeft, fechaLimite) {
    // Formatear número con separadores de mil
    const facturasFormateadas = facturasPendientes.toLocaleString('es-HN');

    // Mensaje de vencimiento solo cuando daysLeft <= 5 o ya venció
    const vencimientoMsg = (daysLeft <= 5) ? 
        `<div class="counter-line days-left">
            ${daysLeft < 0 
                ? 'Las autorizaciones del SAR han vencido.' 
                : (daysLeft === 0 
                    ? '<strong>Las autorizaciones del SAR vencen hoy.</strong>' 
                    : `Las autorizaciones del SAR vencen en <strong>${daysLeft}</strong> día(s).`)}
        </div>` 
        : '';

    const facturasMsg = `<div class="counter-line facturas-count">
                            Quedan <strong>${facturasFormateadas}</strong> factura(s) autorizada(s) por el SAR.
                         </div>`;

    const configs = {
        'normal': {
            icon: 'fas fa-file-invoice',
            class: 'alert-normal',
            text: facturasMsg
        },
        'warning': {
            icon: 'fas fa-hourglass-half',
            class: 'alert-warning',
            text: facturasMsg + vencimientoMsg
        },
        'danger': {
            icon: 'fas fa-exclamation-triangle',
            class: 'alert-danger',
            text: facturasMsg + vencimientoMsg
        },
        'expired': {
            icon: 'fas fa-calendar-times',
            class: 'alert-danger',
            text: `<div class="counter-line">Las autorizaciones del SAR han vencido.</div>
                   <div class="counter-line">
                       <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="counter-link">Actualizar ahora</a>
                   </div>`
        },
        'blocked': {
            icon: 'fas fa-ban',
            class: 'alert-danger',
            text: `<div class="counter-line">Ha alcanzado el límite de facturas autorizado por el SAR.</div>
                   <div class="counter-line">
                       <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="counter-link">Configurar secuencia</a>
                   </div>`
        },
        'no-config': {
            icon: 'fas fa-calendar-times',
            class: 'alert-warning',
            text: `<div class="counter-line">No se ha definido una fecha límite para las autorizaciones del SAR.</div>
                   <div class="counter-line">
                       <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="counter-link">Establecer fecha</a>
                   </div>`
        }
    };

    return configs[state] || configs['normal'];
}

function updateButtonsState(facturasPendientes, fechaLimite, daysLeft) {
    const facturarBtn = $("#invoice-form #reg_factura");
    // Los botones de caja NO se deben deshabilitar aquí
    
    // Validación de facturas SAR
    const vencimientoPasado = daysLeft < 0;
    const sarDisabled = facturasPendientes <= 0 || !fechaLimite || fechaLimite.trim() === "Sin definir" || vencimientoPasado;
    
    // Validación de caja (2 = cerrada)
    const cajaCerrada = getConsultarAperturaCaja() == 2;
    
    // Solo deshabilitar botones de facturación
    facturarBtn.prop("disabled", sarDisabled || cajaCerrada);

    // Estilos solo para SAR (la caja tiene sus propios estilos)
    if (sarDisabled && !cajaCerrada) {
        facturarBtn.addClass("btn-outline-danger").removeClass("btn-secondary");
    } else {
        facturarBtn.removeClass("btn-outline-danger").addClass("btn-secondary");
    }
}

function showErrorState() {
    $("#mensajeFacturas").html(
        `<i class="fas fa-exclamation-circle"></i> <div class="counter-content">Error al cargar disponibilidad (SAR)</div>`
    ).addClass('alert-danger');
}

function getConsultarAperturaCaja() {
    var url = '<?php echo SERVERURL; ?>core/getAperturaCajaUsuario.php';
    var estado_apertura;

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(registro) {
            var valores = eval(registro);
            estado_apertura = valores[0];
        }
    });
    return estado_apertura;
}

function validarAperturaCajaUsuario() {
    const cajaCerrada = getConsultarAperturaCaja() == 2;
    const elementos = [
        "#reg_factura", "#guardar_factura", "#add_cliente", 
        "#add_vendedor", "#addCambio", "#addQuotetoBill",
        "#addPayCustomers", "#addRows", "#removeRows",
        "#notasFactura", "#addDraft", "#btn_retiro_caja", "#btn_ver_caja_factura", "#btn_exoneracion"
    ];

    // Aplicar estado a todos los elementos (excepto botones de caja)
    elementos.forEach(selector => {
        $(`#invoice-form ${selector}`).prop("disabled", cajaCerrada);
    });

    // Manejar visibilidad de botones de caja (siempre habilitados)
    $("#invoice-form #btn_apertura")
        .toggle(cajaCerrada)
        .prop("disabled", false);
    
    $("#invoice-form #btn_cierre")
        .toggle(!cajaCerrada)
        .prop("disabled", false);
    
    // Forzar actualización del estado SAR
    getTotalFacturasDisponibles();
}
/*FIN CONTEO FACTURAS*/

/*INICIO RETIRO DE CAJA*/
function formatoMonedaRetiro(valor) {
    valor = parseFloat(valor || 0);
    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function notificarRetiroCaja(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    } else if (typeof swal === 'function') {
        swal({
            title: titulo,
            text: mensaje,
            icon: tipo === 'success' ? 'success' : 'error',
            button: 'Aceptar'
        });
    } else {
        alert(titulo + ': ' + mensaje);
    }
}

function cargarCategoriasRetiroCaja() {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/caja/getCategoriasGastosRetiroCaja.php',
        dataType: 'json',
        success: function (response) {
            var html = '';

            if (response.success && response.data) {
                response.data.forEach(function (item) {
                    html += '<option value="' + item.categoria_gastos_id + '">' + item.nombre + '</option>';
                });
            }

            $('#retiro_categoria_gastos_id').html(html);

            if ($.fn.selectpicker) {
                $('#retiro_categoria_gastos_id').selectpicker('refresh');
            }
        }
    });
}

function calcularRetiroCaja() {
    var saldoActual = parseFloat($('#retiro_saldo_actual').val()) || 0;
    var montoRetiro = parseFloat($('#retiro_monto').val()) || 0;
    var saldoFinal = saldoActual - montoRetiro;

    $('#retiro_saldo_final').val(saldoFinal.toFixed(2));
    $('#retiro_saldo_final_text').text(formatoMonedaRetiro(saldoFinal));

    if (saldoActual <= 0) {
        $('#retiro_mensaje_validacion').show().html('No hay dinero disponible en caja para retirar.');
        $('#btn_guardar_retiro_caja').prop('disabled', true);
        return;
    }

    if (montoRetiro <= 0) {
        $('#retiro_mensaje_validacion').hide().html('');
        $('#btn_guardar_retiro_caja').prop('disabled', true);
        return;
    }

    if (montoRetiro > saldoActual) {
        $('#retiro_mensaje_validacion').show().html('No puede retirar más dinero del disponible en caja.');
        $('#btn_guardar_retiro_caja').prop('disabled', true);
        return;
    }

    $('#retiro_mensaje_validacion').hide().html('');
    $('#btn_guardar_retiro_caja').prop('disabled', false);
}

$(document).on('shown.bs.modal', '#modalRetiroCaja', function () {
    $('#formRetiroCaja')[0].reset();
    $('#retiro_mensaje_validacion').hide().html('');
    $('#btn_guardar_retiro_caja').prop('disabled', true);

    cargarCategoriasRetiroCaja();

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/caja/getSaldoRetiroCaja.php',
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                $('#retiro_saldo_actual').val('0.00');
                $('#retiro_saldo_actual_text').text('L. 0.00');
                $('#retiro_saldo_final').val('0.00');
                $('#retiro_saldo_final_text').text('L. 0.00');
                $('#retiro_mensaje_validacion').show().html(response.message);
                return;
            }

            $('#retiro_apertura_id').val(response.apertura_id);
            $('#retiro_saldo_actual').val(response.saldo_disponible);
            $('#retiro_saldo_actual_text').text(formatoMonedaRetiro(response.saldo_disponible));
            $('#retiro_saldo_final').val(response.saldo_disponible);
            $('#retiro_saldo_final_text').text(formatoMonedaRetiro(response.saldo_disponible));

            setTimeout(function () {
                $('#retiro_monto').focus();
            }, 400);
        }
    });
});

$(document).on('input', '#retiro_monto', function () {
    calcularRetiroCaja();
});

$(document).on('submit', '#formRetiroCaja', function (e) {
    e.preventDefault();

    calcularRetiroCaja();

    if ($('#btn_guardar_retiro_caja').prop('disabled')) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/caja/addRetiroCaja.php',
        data: $('#formRetiroCaja').serialize(),
        dataType: 'json',
        beforeSend: function () {
            $('#btn_guardar_retiro_caja').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        },
        success: function (response) {
            $('#btn_guardar_retiro_caja').html('<i class="far fa-save fa-lg mr-1"></i> Registrar retiro');

            if (!response.success) {
                $('#retiro_mensaje_validacion').show().html(response.message);
                calcularRetiroCaja();
                return;
            }

            $('#modalRetiroCaja').modal('hide');

            if (typeof listar_registro_cajas === 'function') {
                listar_registro_cajas();
            }

            notificarRetiroCaja('success', 'Retiro registrado', response.message);
        },
        error: function () {
            $('#btn_guardar_retiro_caja').html('<i class="far fa-save fa-lg mr-1"></i> Registrar retiro');
            $('#btn_guardar_retiro_caja').prop('disabled', false);
            $('#retiro_mensaje_validacion').show().html('Error al procesar el retiro de caja.');
        }
    });
});
/*FIN RETIRO DE CAJA*/

/* =========================================================
   DROPDOWN GLOBAL DE ACCIONES PARA DATATABLES
   ========================================================= */

/* Limpia eventos anteriores para evitar duplicados */
$(document).off("click", ".js-acciones-toggle");
$(document).off("click", ".acciones-menu");
$(document).off("click", ".acciones-menu .accion-item");
$(document).off("click.accionesGlobal");
$(document).off("show.bs.modal.accionesGlobal");
$(document).off("hidden.bs.modal.accionesGlobal");
$(window).off("scroll.accionesGlobal resize.accionesGlobal");

/* Función global para cerrar cualquier menú abierto */
function cerrarDropdownAcciones() {
    $(".acciones-menu.show").each(function () {
        $(this)
            .removeClass("show")
            .removeAttr("style");
    });

    $(".js-acciones-toggle").attr("aria-expanded", "false");
}

/* Abrir/cerrar menú al presionar el botón Acciones */
$(document).on("click", ".js-acciones-toggle", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $button = $(this);
    var $dropdown = $button.closest(".acciones-dropdown");
    var $menu = $dropdown.find(".acciones-menu").first();

    if ($menu.hasClass("show")) {
        cerrarDropdownAcciones();
        return;
    }

    cerrarDropdownAcciones();

    $menu.addClass("show");

    var rect = this.getBoundingClientRect();
    var menuWidth = $menu.outerWidth();
    var menuHeight = $menu.outerHeight();

    var top = rect.bottom + 8;
    var left = rect.right - menuWidth;

    if (left < 10) {
        left = rect.left;
    }

    if (left + menuWidth > window.innerWidth) {
        left = window.innerWidth - menuWidth - 10;
    }

    if (top + menuHeight > window.innerHeight) {
        top = rect.top - menuHeight - 8;
    }

    if (top < 10) {
        top = 10;
    }

    $menu.css({
        display: "block",
        position: "fixed",
        top: top + "px",
        left: left + "px",
        right: "auto",
        bottom: "auto",
        transform: "none",
        zIndex: 999999
    });

    $button.attr("aria-expanded", "true");
});

/* No cerrar si solo se hace clic dentro del menú vacío */
$(document).on("click", ".acciones-menu", function (e) {
    e.stopPropagation();
});

/* Cerrar al presionar una opción del menú */
$(document).on("click", ".acciones-menu .accion-item", function () {
    cerrarDropdownAcciones();
});

/* Cerrar al hacer clic fuera */
$(document).on("click.accionesGlobal", function () {
    cerrarDropdownAcciones();
});

/* Cerrar al hacer scroll o cambiar tamaño */
$(window).on("scroll.accionesGlobal resize.accionesGlobal", function () {
    cerrarDropdownAcciones();
});

/* Cerrar siempre que se abra un modal */
$(document).on("show.bs.modal.accionesGlobal", ".modal", function () {
    cerrarDropdownAcciones();
});

/* Cerrar también cuando se cierre un modal */
$(document).on("hidden.bs.modal.accionesGlobal", ".modal", function () {
    cerrarDropdownAcciones();
});

/* =========================================================
   INICIO MODAL GLOBAL: VISTA PREVIA DE DOCUMENTOS Y REPORTES
========================================================= */

var timeoutLoadingDocumentoPreview = null;
var tipoPreviewDocumentoActual = "documento";

/* =========================================================
   Loading del modal de documentos / reportes
========================================================= */

function mostrarLoadingDocumentoPreview() {
  $("#loadingPreviewDocumento").addClass("is-active");
}

function ocultarLoadingDocumentoPreview() {
  $("#loadingPreviewDocumento").removeClass("is-active");

  if (timeoutLoadingDocumentoPreview) {
    clearTimeout(timeoutLoadingDocumentoPreview);
    timeoutLoadingDocumentoPreview = null;
  }
}

/* =========================================================
   Botón imprimir del modal
========================================================= */

function mostrarBotonImprimirDocumentoPreview() {
  $("#modalPreviewDocumento .modal-documento-preview-btn-print").show();
}

function ocultarBotonImprimirDocumentoPreview() {
  $("#modalPreviewDocumento .modal-documento-preview-btn-print").hide();
}

/* =========================================================
   Preparar URL para documentos PDF normales del sistema
========================================================= */

function prepararUrlDocumentoPreview(urlDocumento) {
  if (!urlDocumento) {
    return "";
  }

  var url = String(urlDocumento);
  var separadorCache = url.indexOf("?") === -1 ? "?" : "&";

  url += separadorCache + "_preview=" + new Date().getTime();

  /*
    Opciones del visor PDF del navegador:
    toolbar=1   muestra barra del PDF
    navpanes=0  oculta panel lateral
    scrollbar=1 permite scroll
    zoom=115    tamaño inicial del documento
  */
  url += "#toolbar=1&navpanes=0&scrollbar=1&zoom=115";

  return url;
}

/* =========================================================
   Abrir documentos PDF normales del sistema en el modal
   Esta función la usan printGastos, printIngresos, facturas, etc.
========================================================= */

function abrirDocumentoEnModal(urlDocumento, tituloDocumento = "Vista previa del documento") {
  if (!urlDocumento) {
    if (typeof showNotify === "function") {
      showNotify("error", "Error", "No se recibió la URL del documento.");
    }

    return;
  }

  var urlPreview = prepararUrlDocumentoPreview(urlDocumento);
  var iframe = $("#iframePreviewDocumento");

  tipoPreviewDocumentoActual = "documento";

  $("#modalPreviewDocumentoLabel").text(tituloDocumento);
  $("#btnAbrirDocumentoNuevaVentana").attr("href", urlDocumento);

  /*
    En documentos internos sí mostramos nuestro botón imprimir.
  */
  mostrarBotonImprimirDocumentoPreview();

  mostrarLoadingDocumentoPreview();

  if (timeoutLoadingDocumentoPreview) {
    clearTimeout(timeoutLoadingDocumentoPreview);
    timeoutLoadingDocumentoPreview = null;
  }

  iframe
    .off("load.previewDocumento")
    .off("load.previewReporteIIS")
    .attr("name", "iframePreviewDocumento")
    .attr("src", "about:blank");

  $("#modalPreviewDocumento").modal({
    backdrop: "static",
    keyboard: false
  });

  $("#modalPreviewDocumento").modal("show");

  iframe.on("load.previewDocumento", function () {
    var srcIframe = $(this).attr("src");

    if (srcIframe && srcIframe !== "about:blank") {
      ocultarLoadingDocumentoPreview();
    }
  });

  setTimeout(function () {
    iframe.attr("src", urlPreview);
  }, 150);

  /*
    Respaldo:
    Algunos navegadores no disparan bien el evento load con visor PDF.
    Esto evita que el loading quede pegado encima del documento.
  */
  timeoutLoadingDocumentoPreview = setTimeout(function () {
    ocultarLoadingDocumentoPreview();
  }, 2500);
}

/* =========================================================
   Abrir reportes Windows / IIS dentro del modal global
========================================================= */

function abrirReporteIISDentroDelModal(urlReporte, tituloReporte = "Vista previa del reporte") {
  if (!urlReporte) {
    if (typeof showNotify === "function") {
      showNotify("error", "Error", "No se recibió la URL del reporte.");
    }

    return;
  }

  var iframe = $("#iframePreviewDocumento");

  tipoPreviewDocumentoActual = "iis";

  $("#modalPreviewDocumentoLabel").text(tituloReporte);
  $("#btnAbrirDocumentoNuevaVentana").attr("href", urlReporte);

  /*
    En reportes Windows/IIS ocultamos nuestro botón imprimir.
    El usuario debe usar el botón imprimir del visor PDF interno.
  */
  ocultarBotonImprimirDocumentoPreview();

  mostrarLoadingDocumentoPreview();

  if (timeoutLoadingDocumentoPreview) {
    clearTimeout(timeoutLoadingDocumentoPreview);
    timeoutLoadingDocumentoPreview = null;
  }

  iframe
    .off("load.previewDocumento")
    .off("load.previewReporteIIS")
    .attr("name", "iframePreviewDocumento")
    .attr("src", "about:blank");

  $("#modalPreviewDocumento").modal({
    backdrop: "static",
    keyboard: false
  });

  $("#modalPreviewDocumento").modal("show");

  iframe.on("load.previewReporteIIS", function () {
    var srcIframe = $(this).attr("src");

    if (srcIframe && srcIframe !== "about:blank") {
      ocultarLoadingDocumentoPreview();
    }
  });

  setTimeout(function () {
    iframe.attr("src", urlReporte);
  }, 150);

  /*
    Respaldo:
    Algunos reportes dentro del iframe no siempre disparan bien el evento load.
    Esto evita que el loading se quede pegado.
  */
  timeoutLoadingDocumentoPreview = setTimeout(function () {
    ocultarLoadingDocumentoPreview();
  }, 2500);
}

/* =========================================================
   viewReport
   Reportes Windows / IIS por GET dentro del modal global
   tituloReporte es opcional.
========================================================= */

function viewReport(params, tituloReporte = "Vista previa del reporte") {
  var url = "<?php echo defined('SERVERURLWINDOWS') ? SERVERURLWINDOWS : ''; ?>";

  if (!url || url.trim() === "") {
    swal({
      title: "Error de conexión",
      content: {
        element: "p",
        attributes: {
          innerHTML: "No se pudo acceder al servidor de reportes. Esto puede deberse a un problema de conexión o a que el servicio no está disponible.<br><br>📌 <b>Pasos recomendados:</b><br>1️⃣ Verifique su conexión a internet.<br>2️⃣ Intente nuevamente en unos minutos.<br>3️⃣ Si el problema persiste, comuníquese con soporte e informe el siguiente código de error: <b>SERVIDOR_NO_RESPONDE</b>."
        }
      },
      icon: "error",
      button: "Entendido",
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    });

    return;
  }

  if (!params || typeof params !== "object") {
    swal({
      title: "Error",
      text: "No se recibieron los parámetros necesarios para generar el reporte.",
      icon: "error",
      button: "Entendido",
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    });

    return;
  }

  var separador = url.indexOf("?") === -1 ? "?" : "&";
  var urlReporte = url + separador + new URLSearchParams(params).toString();

  abrirReporteIISDentroDelModal(urlReporte, tituloReporte);
}

/* =========================================================
   enviarFormulario
   Reportes Windows / IIS por POST dentro del iframe del modal
   tituloReporte es opcional.
========================================================= */

function enviarFormulario(url, params, ventana, tituloReporte = "Vista previa del reporte") {
  if (!url || url.trim() === "") {
    swal({
      title: "Error de conexión",
      content: {
        element: "p",
        attributes: {
          innerHTML: "No se pudo acceder al servidor de reportes. Esto puede deberse a un problema de conexión o a que el servicio no está disponible.<br><br>📌 <b>Pasos recomendados:</b><br>1️⃣ Verifique su conexión a internet.<br>2️⃣ Intente nuevamente en unos minutos.<br>3️⃣ Si el problema persiste, comuníquese con soporte."
        }
      },
      icon: "error",
      button: "Entendido",
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    });

    return;
  }

  if (!params || typeof params !== "object") {
    swal({
      title: "Error",
      text: "No se recibieron los parámetros necesarios para generar el reporte.",
      icon: "error",
      button: "Entendido",
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    });

    return;
  }

  var iframe = $("#iframePreviewDocumento");

  tipoPreviewDocumentoActual = "iis";

  $("#modalPreviewDocumentoLabel").text(tituloReporte);
  $("#btnAbrirDocumentoNuevaVentana").attr("href", url);

  /*
    En reportes Windows/IIS por POST también ocultamos nuestro botón imprimir.
  */
  ocultarBotonImprimirDocumentoPreview();

  mostrarLoadingDocumentoPreview();

  if (timeoutLoadingDocumentoPreview) {
    clearTimeout(timeoutLoadingDocumentoPreview);
    timeoutLoadingDocumentoPreview = null;
  }

  iframe
    .off("load.previewDocumento")
    .off("load.previewReporteIIS")
    .attr("name", "iframePreviewDocumento")
    .attr("src", "about:blank");

  $("#modalPreviewDocumento").modal({
    backdrop: "static",
    keyboard: false
  });

  $("#modalPreviewDocumento").modal("show");

  iframe.on("load.previewReporteIIS", function () {
    ocultarLoadingDocumentoPreview();
  });

  setTimeout(function () {
    var form = document.createElement("form");

    form.method = "POST";
    form.action = url;
    form.target = "iframePreviewDocumento";
    form.style.display = "none";

    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        var input = document.createElement("input");

        input.type = "hidden";
        input.name = key;
        input.value = params[key];

        form.appendChild(input);
      }
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }, 150);

  /*
    Respaldo:
    Algunos reportes dentro del iframe no siempre disparan bien el evento load.
    Esto evita que el loading se quede pegado.
  */
  timeoutLoadingDocumentoPreview = setTimeout(function () {
    ocultarLoadingDocumentoPreview();
  }, 2500);
}

/* =========================================================
   Imprimir documento cargado en el modal
   Solo aplica para documentos internos del sistema
========================================================= */

function imprimirDocumentoPreview() {
  var iframe = document.getElementById("iframePreviewDocumento");

  if (!iframe) {
    return;
  }

  /*
    Si es reporte Windows/IIS no hacemos nada.
    El botón estará oculto, pero esto evita errores si alguien lo llama manualmente.
  */
  if (tipoPreviewDocumentoActual === "iis") {
    return;
  }

  try {
    if (iframe.contentWindow) {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    }
  } catch (error) {
    showNotify('error', 'Error', 'No se pudo imprimir el documento desde el modal.');
  }
}

/* =========================================================
   Limpiar modal al cerrar
========================================================= */

$(document).on("hidden.bs.modal", "#modalPreviewDocumento", function () {
  $("#iframePreviewDocumento")
    .off("load.previewDocumento")
    .off("load.previewReporteIIS")
    .attr("src", "about:blank");

  $("#btnAbrirDocumentoNuevaVentana").attr("href", "#");

  tipoPreviewDocumentoActual = "documento";

  /*
    Dejamos el botón imprimir visible por defecto para los documentos normales.
  */
  mostrarBotonImprimirDocumentoPreview();

  ocultarLoadingDocumentoPreview();
});

/* =========================================================
   FIN MODAL GLOBAL: VISTA PREVIA DE DOCUMENTOS Y REPORTES
========================================================= */

/* =========================================================
   AUTENTICACIÓN ADMINISTRATIVA GLOBAL
   ---------------------------------------------------------
   validarAdminSistema(callback, opciones)

   Sirve para proteger acciones críticas del sistema.
   El PHP valida usuario administrador y guarda auditoría.

   Variables:
   AUTH_ADMIN_SISTEMA_TOKEN
   - Token temporal devuelto por el PHP.

   AUTH_ADMIN_SISTEMA_AUDITORIA_ID
   - ID del registro creado en auditoría.

   AUTH_ADMIN_SISTEMA_CALLBACK
   - Función que se ejecuta después de validar.

   AUTH_ADMIN_SISTEMA_OPCIONES
   - Datos de auditoría enviados al PHP.

   AUTH_ADMIN_SISTEMA_ESPERANDO
   - Controla si el usuario cerró el modal sin validar.
========================================================= */

var AUTH_ADMIN_SISTEMA_TOKEN = '';
var AUTH_ADMIN_SISTEMA_AUDITORIA_ID = 0;
var AUTH_ADMIN_SISTEMA_CALLBACK = null;
var AUTH_ADMIN_SISTEMA_OPCIONES = {};
var AUTH_ADMIN_SISTEMA_ESPERANDO = false;

function enfocarUsuarioAuthAdminSistema() {
    var $usuario = $('#auth_admin_usuario');

    if ($usuario.length > 0) {
        $usuario.trigger('focus');
        $usuario.select();
    }
}

function validarAdminSistema(callback, opciones) {
    opciones = opciones || {};

    AUTH_ADMIN_SISTEMA_CALLBACK = callback;
    AUTH_ADMIN_SISTEMA_OPCIONES = opciones;
    AUTH_ADMIN_SISTEMA_ESPERANDO = true;

    if ($('#modalAutenticacionAdminSistema').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No existe el modal de validación administrativa.');

        if (typeof AUTH_ADMIN_SISTEMA_CALLBACK === 'function') {
            AUTH_ADMIN_SISTEMA_CALLBACK(false, {});
        }

        return;
    }

    if ($('#formAutenticacionAdminSistema').length > 0) {
        $('#formAutenticacionAdminSistema')[0].reset();
    }

    $('#auth_admin_mensaje').html(
        opciones.mensaje || 'Ingrese credenciales de un usuario administrador.'
    );

    $('#btn_validar_auth_admin')
        .prop('disabled', false)
        .html('<i class="fas fa-unlock-alt"></i> Validar');

    $('#modalAutenticacionAdminSistema')
        .off('shown.bs.modal.authAdminSistemaFocus')
        .on('shown.bs.modal.authAdminSistemaFocus', function () {
            enfocarUsuarioAuthAdminSistema();

            setTimeout(function () {
                enfocarUsuarioAuthAdminSistema();
            }, 120);

            setTimeout(function () {
                enfocarUsuarioAuthAdminSistema();
            }, 300);
        });

    $('#modalAutenticacionAdminSistema').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function ejecutarValidacionAdminSistema() {
    var usuario = $.trim($('#auth_admin_usuario').val() || '');
    var password = $('#auth_admin_password').val() || '';

    if (usuario === '' || password === '') {
        showNotify('error', 'Datos requeridos', 'Ingrese usuario y contraseña.');
        enfocarUsuarioAuthAdminSistema();
        return;
    }

    $('#btn_validar_auth_admin')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Validando...');

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/auth/validarAdministradorConfig.php',
        dataType: 'json',
        data: {
            usuario: usuario,
            password: password,
            modulo: AUTH_ADMIN_SISTEMA_OPCIONES.modulo || 'Sistema',
            accion: AUTH_ADMIN_SISTEMA_OPCIONES.accion || 'Validación administrativa',
            referencia_id: AUTH_ADMIN_SISTEMA_OPCIONES.referencia_id || '',
            referencia_texto: AUTH_ADMIN_SISTEMA_OPCIONES.referencia_texto || '',
            motivo: AUTH_ADMIN_SISTEMA_OPCIONES.motivo || ''
        },
        success: function (response) {
            $('#btn_validar_auth_admin')
                .prop('disabled', false)
                .html('<i class="fas fa-unlock-alt"></i> Validar');

            if (!response || response.success !== true || response.permitido !== true) {
                showNotify(
                    'error',
                    'Validación rechazada',
                    response && response.message ? response.message : 'Usuario, contraseña o permisos no válidos.'
                );

                $('#auth_admin_password').val('');
                enfocarUsuarioAuthAdminSistema();

                return;
            }

            AUTH_ADMIN_SISTEMA_TOKEN = response.token || '';
            AUTH_ADMIN_SISTEMA_AUDITORIA_ID = parseInt(response.auditoria_admin_id || 0, 10);
            AUTH_ADMIN_SISTEMA_ESPERANDO = false;

            $('#modalAutenticacionAdminSistema').modal('hide');

            if (typeof AUTH_ADMIN_SISTEMA_CALLBACK === 'function') {
                AUTH_ADMIN_SISTEMA_CALLBACK(true, response);
            }
        },
        error: function (xhr) {
            showNotify('warning', 'Aviso', 'No se pudo completar la acción solicitada.');

            $('#btn_validar_auth_admin')
                .prop('disabled', false)
                .html('<i class="fas fa-unlock-alt"></i> Validar');

            showNotify('error', 'Error', 'Error de comunicación al validar administrador.');
            enfocarUsuarioAuthAdminSistema();
        }
    });
}

$(document)
    .off('submit.authAdminSistema', '#formAutenticacionAdminSistema')
    .on('submit.authAdminSistema', '#formAutenticacionAdminSistema', function (e) {
        e.preventDefault();
        ejecutarValidacionAdminSistema();
    });

$(document)
    .off('hidden.bs.modal.authAdminSistema', '#modalAutenticacionAdminSistema')
    .on('hidden.bs.modal.authAdminSistema', '#modalAutenticacionAdminSistema', function () {
        if (AUTH_ADMIN_SISTEMA_ESPERANDO === true) {
            AUTH_ADMIN_SISTEMA_ESPERANDO = false;

            if (typeof AUTH_ADMIN_SISTEMA_CALLBACK === 'function') {
                AUTH_ADMIN_SISTEMA_CALLBACK(false, {});
            }
        }
    });


  //INICIO METODO ANULAR FACTURAS
  function anularFacturas(facturas_id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
        content: {
        element: "input",
        attributes: {
            placeholder: "Comentario",
            type: "text"
        }
        },
        icon: "warning",
        buttons: {
        cancel: "Cancelar",
        confirm: {
            text: "¡Sí, anular la factura!",
            closeModal: false
        }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((value) => {
        if (value === null) {
        swal.close();
        return false;
        }

        if ($.trim(value) === "") {
        showNotify('error', 'Error', '¡Necesita escribir algo!');
        swal.close();
        return false;
        }

        anular(facturas_id, value);
    });
}

function anular(facturas_id, comentario) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL; ?>core/anularFactura.php',
    async: true,
    timeout: 45000,
    dataType: 'json',
    data: {
      facturas_id: facturas_id,
      comentario: comentario
    },
    success: function (response) {
      swal.close();

      if (response && response.success === true) {
        showNotify(
          'success',
          'Success',
          response.message || 'La factura ha sido anulada con éxito'
        );

        listar_reporte_ventas();
      } else {
        showNotify(
          'error',
          'Error',
          response && response.message ? response.message : 'La factura no se puede anular'
        );
      }
    },
    error: function (xhr, status, error) {
      swal.close();

      if (xhr && xhr.status === 401) {
          showNotify('error', 'Sesión expirada', 'Debe iniciar sesión nuevamente.');
          return;
      }

      if (xhr && xhr.status === 403) {
          showNotify('error', 'Acceso denegado', 'No tiene permisos para anular esta factura.');
          return;
      }

      if (status === 'timeout') {
          showNotify(
              'error',
              'Tiempo agotado',
              'La anulación tardó demasiado. Revise si la factura fue anulada antes de intentarlo otra vez.'
          );
          return;
      }

      showNotify(
          'error',
          'Error',
          'No se pudo anular la factura. Intente nuevamente.'
      );
  }
  });
}
  //FIN METODO ANULAR FACTURAS
</script>
