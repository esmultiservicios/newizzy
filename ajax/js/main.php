<script>
var DB_MAIN = "<?php echo DB_MAIN; ?>";

function init() {
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

    // LLAMAMOS EL MÉTODO QUE IDENTIFICA EL USUARIO QUE HA INICIADO SESIÓN
    getUserSessionStart();

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
    document.querySelectorAll('.selectpicker').forEach(el => $(el).selectpicker());

    // Inicializar tooltips en las opciones del selectpicker después de la creación
    document.querySelectorAll('[data-toggle="tooltip"]').forEach(el => $(el).tooltip());      

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('#form_main_pagar_proveedores #pagar_proveedores_estado').value = 1;
        $('#form_main_pagar_proveedores #pagar_proveedores_estado').selectpicker('refresh');
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('#form_main_cobrar_clientes #cobrar_clientes_estado').value = 1;
        $('#form_main_cobrar_clientes #cobrar_clientes_estado').selectpicker('refresh');
    });    
}

// Ejecutar cuando la página ha cargado completamente
window.addEventListener('DOMContentLoaded', init);

function actualizarPermisos() {
    const privilegio_id = getPrivilegioUsuario();
    getMenu(privilegio_id);
    getSubMenu(privilegio_id);
    getSubMenu1(privilegio_id);
}

// Ejecutar al cargar
actualizarPermisos();

// Actualizar permisos cada 5 minutos
setInterval(actualizarPermisos, 300000); // 300000 ms = 5 minutos

let renovar = false;
let tiempoRestante = 0;

function mostrarNotificacionRenovacion(tiempoRestante) {
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
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera            
        }).then((value) => {
            resolve(value);
        });
    });
}

function mostrarNotificacionExpiracion() {
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
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera        
    }).then(() => {
        // Redirigir al usuario a la página de inicio de sesión
        window.location.href = '<?php echo SERVERURL;?>';
    });
}

async function renovarSesion() {
    const response = await fetch('<?php echo SERVERURL;?>core/renovar_sesion.php');
    const data = await response.json();

    console.log('Estado de sesión renovada:', data);

    if (data.success) {
        // La renovación fue exitosa, actualizar el tiempo restante
        tiempoRestante = data.tiempoSesion;
        // Llamar nuevamente a validarSesion después de renovar
        await validarSesion();
    }
}

async function validarSesion() {
    const response = await fetch('<?php echo SERVERURL;?>core/verificar_sesion.php?renovar=' + renovar.toString());
    const data = await response.json();

    console.log('Estado de sesión:', data);

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

// Ejecutar validarSesion inicialmente
//validarSesion();

// Configurar intervalo para validar la sesión cada minuto
//setInterval(validarSesion, 1000); // 1000 milisegundos = 1 segundo

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
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).show();
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                    } else {
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).hide();
                        $('.table_' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {

            }
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
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).show();
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                    } else {
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).hide();
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {

            }
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
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).show();
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", false);
                        } else {
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).hide();
                            $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                        }
                    } else {
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).hide();
                        $('.' + valores_tipoUsuarioAccesos[i].tipo_permiso).attr("disabled", true);
                    }
                }
            } catch (e) {

            }
        }
    });
}

function getMenu(privilegio_id) {
    var url = '<?php echo SERVERURL;?>core/getMenuPrivilegios.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'privilegio_id=' + privilegio_id,
        success: function(registro) {
            valores_menu = JSON.parse(registro);
            try {
                for (var i = 0; i < valores_menu.length; i++) {
                    if (valores_menu[i].estado == 1) {
                        $('#' + valores_menu[i].menu).show();
                        $('.' + valores_menu[i].menu).show();
                    } else {
                        $('#' + valores_menu[i].menu).hide();
                        $('.' + valores_menu[i].menu).hide();
                    }
                }
            } catch (e) {

            }
        }
    });
}

function getSubMenu(privilegio_id) {
    var url = '<?php echo SERVERURL;?>core/getSubMenuPrivilegios.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'privilegio_id=' + privilegio_id,
        success: function(registro) {
            valores_submenu = JSON.parse(registro);

            try {
                for (var i = 0; i < valores_submenu.length; i++) {
                    if (valores_submenu[i].estado == 1) {
                        $('#' + valores_submenu[i].submenu).show();
                        $('.' + valores_submenu[i].submenu).show();
                    } else {
                        $('#' + valores_submenu[i].submenu).hide();
                        $('.' + valores_submenu[i].submenu).hide();
                    }

                    console.log(valores_submenu[i].submenu);
                }
            } catch (e) {
                console.error('Error:', e);
            }
        }
    });
}

function getSubMenu1(privilegio_id) {
    var url = '<?php echo SERVERURL;?>core/getSubMenuPrivilegios1.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'privilegio_id=' + privilegio_id,
        success: function(registro) {
            valores_submenu = JSON.parse(registro);

            try {
                for (var i = 0; i < valores_submenu.length; i++) {
                    if (valores_submenu[i].estado == 1) {
                        $('#' + valores_submenu[i].submenu1).show();
                        $('.' + valores_submenu[i].submenu1).show();
                    } else {
                        $('#' + valores_submenu[i].submenu1).hide();
                        $('.' + valores_submenu[i].submenu1).hide();
                    }
                }
            } catch (e) {

            }
        }
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
            console.error("Error al obtener privilegio:", error);
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
        async: false,
        success: function(valores) {
            var datos = eval(valores);
            db_cliente = datos[0];
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

function getCajero() {
    var url = '<?php echo SERVERURL;?>core/getCajero.php';

    $.ajax({
        type: 'POST',
        url: url,
        success: function(valores) {
            var datos = eval(valores);
            $('#invoice-form #colaborador_id').val(datos[0]);
            $('#invoice-form #colaborador').val(datos[1]);

            $('#quoteForm #colaborador_id').val(datos[0]);
            $('#quoteForm #colaborador').val(datos[1]);

            $('#formAperturaCaja #colaboradores_id_apertura').val(datos[0]);
            $('#formAperturaCaja #usuario_apertura').val(datos[1]);
            return false;
        }
    });
}

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

    $("#formProductos #preview").attr("src", "<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png");

    $('#formProductos #proceso_productos').val("Registro de Productos");
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

            $('#formulario_busqueda_productos_facturacion #almacen').html("");
            $('#formulario_busqueda_productos_facturacion #almacen').html(data);
            $('#formulario_busqueda_productos_facturacion #almacen').selectpicker('refresh');

            $('#formulario_busqueda_productos_facturacion #almacen').val(1);
            $('#formulario_busqueda_productos_facturacion #almacen').selectpicker('refresh');

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
                    swal({
                        title: "Error",
                        text: "La contraseña que ingresó no coincide con la anterior",
                        icon: "error",
                        dangerMode: true,
                        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                    });
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
 * viewReport
 * Función para generar y visualizar reportes en una nueva pestaña mediante un POST dinámico.
 * 
 * @param {Object} params Objeto con los parámetros necesarios para generar el reporte.
 *                        Debe contener las claves y valores esperados por el servidor IIS.
 * 
 * @example
 * // Ejemplo 1: Generar un reporte con parámetros básicos
 * var params = {
 *     "id": 123,              // ID del reporte o recurso
 *     "type": "Reporte",      // Tipo de reporte
 *     "db": "mi_base_datos"   // Nombre de la base de datos
 * };
 * viewReport(params);
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
function viewReport(params) {
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

/*function viewReport(params) {
    var url = "<?php echo defined('SERVERURLWINDOWS') ? SERVERURLWINDOWS : ''; ?>";

    // Verificar si la URL está vacía o no definida
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

	// Verificar si la URL responde antes de enviar el formulario
	fetch(url, { method: "GET" })
	.then(response => {
		if (!response.ok) {
			throw new Error("El servidor de reportes no está disponible.");
		}
		enviarFormulario(url, params);
	})
	.catch(error => {
		swal({
			title: "Error al obtener el reporte",
			content: {
				element: "p",
				attributes: {
					innerHTML: "No fue posible conectarse con el servidor de reportes.<br><br>🔍 <b>Posibles causas:</b><br>✅ El servidor puede estar en mantenimiento.<br>✅ Puede haber un problema de conexión.<br><br>📌 <b>Pasos recomendados:</b><br>1️⃣ Verifique su conexión a internet.<br>2️⃣ Intente nuevamente en unos minutos.<br>3️⃣ Si el problema persiste, comuníquese con soporte e informe el siguiente código de error: <b>SERVIDOR_NO_DISPONIBLE</b>."
				}
			},
			icon: "error",
			button: "Entendido",
			dangerMode: true,
			closeOnEsc: false,
			closeOnClickOutside: false
		});
	});
}

// 📝 Función para crear y enviar el formulario
function enviarFormulario(url, params) {
    var form = document.createElement("form");
    form.method = "POST";
    form.action = url;

    for (var key in params) {
        if (params.hasOwnProperty(key)) {
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = params[key];
            form.appendChild(input);
        }
    }

    var newWindow = window.open("", "_blank");
    newWindow.document.body.appendChild(form);
    form.submit();
}*/

//INICIO IMPRIMIR FACTURACION
function printQuote(cotizacion_id) {
    params = {
        "id": cotizacion_id,
        "type": "Cotizacion_carta_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>"
    };   

    // Llamar a la función para mostrar el reporte
    viewReport(params);
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
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };
                } else if (formato === "Media Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_media_izzy",
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };
                } else if (formato === "Ticket") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_ticket_izzy",
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };                
                } else {
                    // Manejar caso donde el formato no sea válido
                    swal({
                        title: "Error",
                        text: "El formato de impresión no es válido. Verifica la configuración de la impresora.",
                        icon: "error",
                        button: "Cerrar",
                        dangerMode: true,
                        closeOnEsc: false,
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                      
                    });
                    return; // Salir si el formato no es válido
                }

                // Llamar a la función para mostrar el reporte
                viewReport(params);
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
            console.error("Error en la solicitud AJAX:", error);
            swal({
                title: "Error",
                text: "Hubo un problema al procesar la solicitud.",
                icon: "error",
                buttons: {
                    confirm: {
                        text: "Cerrar",
                        closeModal: true,
                    },
                },
                dangerMode: true,
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera              
            });
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
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };
                } else if (formato === "Media Carta") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_media_izzy",
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };
                } else if (formato === "Ticket") {
                    params = {
                        "id": facturas_id,
                        "type": "Factura_ticket_izzy",
                        "db": "<?php echo $GLOBALS['db']; ?>"
                    };                
                } else {
                    // Manejar caso donde el formato no sea válido
                    swal({
                        title: "Error",
                        text: "El formato de impresión no es válido. Verifica la configuración de la impresora.",
                        icon: "error",
                        button: "Cerrar",
                        dangerMode: true,
                        closeOnEsc: false,
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                      
                    });
                    return; // Salir si el formato no es válido
                }

                // Llamar a la función para mostrar el reporte
                viewReport(params);
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
            console.error("Error en la solicitud AJAX:", error);
            swal({
                title: "Error",
                text: "Hubo un problema al procesar la solicitud.",
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
                console.log('Comprobante Tipo:', impresora.tipo);
            } else {
                // Usando SweetAlert en lugar de alert
                swal({
                    title: "Error",
                    text: "No hay impresoras activas o configuradas.",
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
            console.error("Error en la solicitud AJAX:", error);
            swal({
                title: "Error",
                text: "Hubo un problema al procesar la solicitud.",
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
                "db": "<?php echo $GLOBALS['db']; ?>"
            };   

            // Llamar a la función para mostrar el reporte
            viewReport(params);
        }
    });
}

function printPurchase(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/generaCompra.php?compras_id=' + compras_id;
    window.open(url);
}

//INICIO ENVIAR COTIZACION POR CORREO ELECTRONICO
function mailQuote(cotizacion_id) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea enviar la cotización: # " + getNumeroCotizacion(cotizacion_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, enviar la cotización!",
            }
        },
        timer: 3000,
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
    }).then((willConfirm) => {
        if (willConfirm === true) {
            sendQuote(cotizacion_id);
        }
    });
}

function sendQuote(cotizacion_id) {
    var url = '<?php echo SERVERURL; ?>core/sendCotizacion.php';
    var bill = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'cotizacion_id=' + cotizacion_id,
        success: function(data) {
            bill = data;
            if (bill == 1) {
                swal({
                    title: "Success",
                    text: "La cotización ha sido enviada por correo satisfactoriamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                     
                });
            }
        }
    });
    return bill;
}

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
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea enviar este numero de factura: # " + getNumeroFactura(facturas_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, enviar la factura!",
            }
        },
        timer: 3000,
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
    }).then((willConfirm) => {
        if (willConfirm === true) {
            sendMail(facturas_id);
        }
    });
}

function sendMail(facturas_id) {
    var url = '<?php echo SERVERURL; ?>core/sendFactura.php';
    var bill = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'facturas_id=' + facturas_id,
        success: function(data) {
            bill = data;
            if (bill == 1) {
                swal({
                    title: "Success",
                    text: "La factura ha sido enviada por correo satisfactoriamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                     
                });
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
    $('#formColaboradores #apellido_colaborador').attr('readonly', false);
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

    $('#formColaboradores #proceso_colaboradores').val("Registro");
    $('#modal_registrar_colaboradores').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getPuestoColaboradores() {
    var url = '<?php echo SERVERURL;?>core/getPuestoColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formColaboradores #puesto_colaborador').html("");
            $('#formColaboradores #puesto_colaborador').html(data);
            $('#formColaboradores #puesto_colaborador').selectpicker('refresh');
        }
    });
}

function getEmpresaColaboradores() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formColaboradores #colaborador_empresa_id').html("");
            $('#formColaboradores #colaborador_empresa_id').html(data);
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
    $('#formAsistencia').attr({
        'data-form': 'save'
    });
    $('#formAsistencia').attr({
        'action': '<?php echo SERVERURL;?>ajax/addAsistenciaMarcajeAjax.php'
    });
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

$('#formAsistencia #asistencia_empleado').on('change', function() {
    if ($('#formAsistencia #marcarAsistencia_id').val() == 1) {
        $('#formAsistencia #registro_hora').html(getHoraInicio($('#formAsistencia #asistencia_empleado')
            .val()));
    }
});

//FIN MARCAR ASISTENCIA

//INICIO MODIFICAR PERFIL USUARIO SISTEMA
//CONSULTAR CODIGO DE CLIENTE
function getCodigoCliente() {
    var url = '<?php echo SERVERURL;?>core/getCodigoCliente.php';

    $.ajax({
        type: 'POST',
        url: url,
        success: function(valores) {
            var datos = eval(valores);
            $('#formColaboradores #cliente_codigo_colaborador').val(datos[0]);
            return false;
        }
    });
}

//GENERAR PIN
function generatePin(generateNew) {
    var codigoCliente = $('#formColaboradores #cliente_codigo_colaborador').val();

    // Realizar la solicitud Ajax para generar el PIN
    $.ajax({
        url: '<?php echo SERVERURL;?>core/generarPinCliente.php',
        type: 'POST',
        data: {
            codigoCliente: codigoCliente,
            generateNew: generateNew // Envía el parámetro generateNew al servidor
        },
        dataType: 'json',
        success: function(response) {
            var pin = response.pin;

            // Actualizar el valor del input con el nuevo PIN
            $('#pin_colaborador').val(pin);
        },
        error: function(error) {
            console.error('Error al generar el PIN: ', error);
        }
    });
}

function updateDatePin(newPin) {
    $.ajax({
        url: '<?php echo SERVERURL;?>core/updatePin.php.php', // Reemplaza con la URL correcta para actualizar el PIN
        type: 'POST',
        data: {
            pin: newPin
        }, // Envía el nuevo PIN al servidor
        success: function(response) {
            // Verificar si la actualización fue exitosa
            if (response.success) {
                console.log('PIN actualizado correctamente en la base de datos.');
            } else {
                console.error('Error al actualizar el PIN en la base de datos.');
            }
        },
        error: function(error) {
            console.error('Error al actualizar el PIN: ', error);
        }
    });
}

// Asignar la función al evento click del botón "Generar"
$('#generarPin').on('click', function(event) {
    event.preventDefault();
    generatePin(1);
});

$('#modificar_perfil_usuario_sistema').on('click', function(e) {
    e.preventDefault();

    $('#formColaboradores')[0].reset();

    $('#estado_colaboradores').hide();
    $("#datosClientes").show();
    getCodigoCliente();

    var url = '<?php echo SERVERURL;?>core/editarColaboradoresUsuario.php';

    $.ajax({
        type: 'POST',
        url: url,
        success: function(registro) {
            var valores = eval(registro);
            $('#formColaboradores').attr({
                'data-form': 'update'
            });
            $('#formColaboradores').attr({
                'action': '<?php echo SERVERURL;?>ajax/modificarColaboradorAjaxMain.php'
            });
            $('#reg_colaborador').hide();
            $('#edi_colaborador').show();
            $('#delete_colaborador').hide();
            $('#formColaboradores #nombre_colaborador').val(valores[0]);
            $('#formColaboradores #apellido_colaborador').val(valores[1]);
            $('#formColaboradores #identidad_colaborador').val(valores[2]);
            $('#formColaboradores #telefono_colaborador').val(valores[3]);
            $('#formColaboradores #puesto_colaborador').val(valores[4]);
            $('#formColaboradores #colaborador_empresa_id').val(valores[5]);
            $('#formColaboradores #colaborador_id').val(valores[7]);
            $('#formColaboradores #fecha_ingreso_colaborador').val(valores[8]);
            $('#formColaboradores #fecha_egreso_colaborador').val(valores[9]);

            if (valores[6] == 1) {
                $('#formColaboradores #colaboradores_activo').attr('checked', true);
            } else {
                $('#formColaboradores #colaboradores_activo').attr('checked', false);
            }

            //HABILITAR OBJETOS
            $('#formColaboradores #nombre_colaborador').attr('readonly', false);
            $('#formColaboradores #apellido_colaborador').attr('readonly', false);
            $('#formColaboradores #identidad_colaborador').attr('readonly', false);
            $('#formColaboradores #telefono_colaborador').attr('readonly', false);
            $('#formColaboradores #estado_colaborador').attr('disabled', false);

            //DESHABILITAR OBJETOS
            $('#formColaboradores #puesto_colaborador').attr('disabled', true);
            $('#formColaboradores #colaborador_empresa_id').attr('disabled', true);

            $('#formColaboradores #fecha_ingreso_colaborador').attr('disabled', true);
            $('#formColaboradores #fecha_egreso_colaborador').attr('disabled', true);
            $('#formColaboradores #buscar_colaborador_empresa').hide();

            $('#formColaboradores #proceso_colaboradores').val("Editar");
            generatePin(0);
            $('#modal_registrar_colaboradores').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        }
    });
});
//FIN MODIFICAR PERFIL USUARIO SISTEMA


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
            console.error("Error al obtener la URL de la imagen");
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

//INICIO CUENTAS POR COBRAR CLIENTES
$('#form_main_cobrar_clientes #cobrar_clientes_estado').on("change", function(e) {
    listar_cuentas_por_cobrar_clientes();
});

$('#form_main_cobrar_clientes #cobrar_clientes').on("change", function(e) {
    listar_cuentas_por_cobrar_clientes();
});

$('#form_main_cobrar_clientes #fechai').on("change", function(e) {
    listar_cuentas_por_cobrar_clientes();
});

$('#form_main_cobrar_clientes #fechaf').on("change", function(e) {
    listar_cuentas_por_cobrar_clientes();
});

var listar_cuentas_por_cobrar_clientes = function() {
    var estado = "";

    if ($("#form_main_cobrar_clientes #cobrar_clientes_estado").val() == "" || $(
            "#form_main_cobrar_clientes #cobrar_clientes_estado").val() == null) {
        estado = 1;
    } else {
        estado = $("#form_main_cobrar_clientes #cobrar_clientes_estado").val();
    }

    var clientes_id = $("#form_main_cobrar_clientes #cobrar_clientes").val();
    var fechai = $("#form_main_cobrar_clientes #fechai").val();
    var fechaf = $("#form_main_cobrar_clientes #fechaf").val();

    var table_cuentas_por_cobrar_clientes = $("#dataTableCuentasPorCobrarClientes").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableCobrarClientes.php",
            "data": {
                "estado": estado,
                "clientes_id": clientes_id,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "cliente"
            },
            {
                "data": "numero"
            },
            {
                data: 'credito',
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
                },
            },
            {
                data: "abono",
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
                },
            },
            {
                data: "saldo",
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
                },
            },
            {
                "data": "vendedor"
            },
            {
                "defaultContent": "<button class='table_abono btn btn-dark table_secondary'><span class='fas fa-cash-register fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes abono_factura btn btn-dark table_success ocultar'><span class='fa fa-money-bill-wave fa-solid'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark table_info ocultar'><span class='fas fa-file-download fa-lg'></span></button>"
            }
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
                width: "16%",
                targets: 1
            },
            {
                width: "16%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3,
                className: "text-center"
            },
            {
                width: "12%",
                targets: 4,
                className: "text-center"
            },
            {
                width: "12%",
                targets: 5,
                className: "text-center"
            },
            {
                width: "16%",
                targets: 6
            },
            {
                width: "2%",
                targets: 7
            },
            {
                width: "2%",
                targets: 8
            },
            {
                width: "2%",
                targets: 9
            }
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            // Agregar clases de color a las celdas de cada fila según el valor de 'color'
            $('td', nRow).addClass(aData['color']);

            // Personalizar el color de la celda en la posición 2 (índice 2)
            $('td:eq(2)', nRow).css('color', 'red');
        },
        "footerCallback": function(row, data, start, end, display) {
            // Aquí puedes calcular los totales y actualizar el footer
            var totalCredito = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.credito) || 0);
            }, 0);

            var totalAbono = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.abono) || 0);
            }, 0);

            var totalPendiente = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.saldo) || 0);
            }, 0);

            // Formatear los totales con separadores de miles y coma para decimales
            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            var totalCreditoFormatted = formatter.format(totalCredito);
            var totalAbonoFormatted = formatter.format(totalAbono);
            var totalPendienteFormatted = formatter.format(totalPendiente);

            // Asignar los totales a los elementos HTML
            $('#credito-cxc').html(totalCreditoFormatted);
            $('#abono-cxc').html(totalAbonoFormatted);
            $('#total-footer-cxc').html(totalPendienteFormatted);
        },
        "buttons": [{
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
                    columns: [2, 3, 4, 5, 6]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Cuentas por Cobrar Clientes',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [2, 3, 4, 5, 6]
                },
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

    registrar_abono_cxc_clientes_dataTable("#dataTableCuentasPorCobrarClientes tbody",
        table_cuentas_por_cobrar_clientes);
    ver_abono_cxc_clientes_dataTable("#dataTableCuentasPorCobrarClientes tbody", table_cuentas_por_cobrar_clientes);
    view_reporte_facturas_dataTable("#dataTableCuentasPorCobrarClientes tbody", table_cuentas_por_cobrar_clientes);
}

var view_reporte_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillReporteVentas(data.facturas_id);
    });
}

var registrar_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_abono");
    $(tbody).on("click", "button.table_abono", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 2 || data.saldo <=
            0) { //no tiene acceso a la accion si la factura ya fue cancelada
            swal({
                title: 'Error',
                text: 'No puede realizar esta accion a las facturas canceladas!',
                icon: 'error',
                dangerMode: true,
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
            });
        } else {
            $("#GrupoPagosMultiplesFacturas").hide();
            pago(data.facturas_id, 2);
        }
    });
}

var ver_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_factura");
    $(tbody).on("click", "button.abono_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxc').modal('show');
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

function getClientesCXC() {
    var url = '<?php echo SERVERURL;?>core/getClientesCXC.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_cobrar_clientes #cobrar_clientes').html("");
            $('#form_main_cobrar_clientes #cobrar_clientes').html(data);
            $('#form_main_cobrar_clientes #cobrar_clientes').selectpicker('refresh');
        }
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

//INICIO CUENTAS POR PAGAR PROVEEDORES
$('#form_main_pagar_proveedores #pagar_proveedores_estado').on("change", function(e) {
    listar_cuentas_por_pagar_proveedores();
});

$('#form_main_pagar_proveedores #pagar_proveedores').on("change", function(e) {
    listar_cuentas_por_pagar_proveedores();
});

$('#form_main_pagar_proveedores #fechai').on("change", function(e) {
    listar_cuentas_por_pagar_proveedores();
});

$('#form_main_pagar_proveedores #fechaf').on("change", function(e) {
    listar_cuentas_por_pagar_proveedores();
});

var listar_cuentas_por_pagar_proveedores = function() {
    var estado = "";

    if ($("#form_main_pagar_proveedores #pagar_proveedores_estado").val() == "" || $(
            "#form_main_pagar_proveedores #pagar_proveedores_estado").val() == null) {
        estado = 1;
    } else {
        estado = $("#form_main_pagar_proveedores #pagar_proveedores_estado").val();
    }

    var proveedores_id = $("#form_main_pagar_proveedores #pagar_proveedores").val();
    var fechai = $("#form_main_pagar_proveedores #fechai").val();
    var fechaf = $("#form_main_pagar_proveedores #fechaf").val();

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
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "proveedores"
            },
            {
                "data": "factura"
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
                },
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
                },
            },
            {
                data: "saldo",
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
                },
            },
            {
                "defaultContent": "<button class='table_pay btn btn-dark table_info ocultar'><span class='fas fa-hand-holding-usd fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='abono_proveedor btn btn-dark table_success'><span class='fa fa-money-bill-wave fa-solid'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark table_info ocultar'><span class='fas fa-file-download fa-lg'></span></button>"
            }
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "12.5%",
                targets: 0
            },
            {
                width: "10.5%",
                targets: 1
            },
            {
                width: "12.5%",
                targets: 2
            },
            {
                width: "20.5%",
                targets: 3,
                className: "text-center"
            },
            {
                width: "24.5%",
                targets: 4,
                className: "text-center"
            },
            {
                width: "12.5%",
                targets: 5,
                className: "text-center"
            },
            {
                width: "2.5%",
                targets: 6
            },
            {
                width: "2.5%",
                targets: 7
            }
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            // Agregar clases de color a las celdas de cada fila según el valor de 'color'
            $('td', nRow).addClass(aData['color']);

            // Personalizar el color de la celda en la posición 2 (índice 2)
            $('td:eq(2)', nRow).css('color', 'red');
        },
        "footerCallback": function(row, data, start, end, display) {
            // Calcular los totales
            var totalCredito = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.credito) || 0); // Asegurar que sea numérico
            }, 0).toFixed(2);

            var totalAbono = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.abono) || 0);
            }, 0).toFixed(2);

            var totalPendiente = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.saldo) || 0);
            }, 0).toFixed(2);

            // Formatear los totales con separadores de miles y coma para decimales
            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            var totalCreditoFormatted = formatter.format(totalCredito);
            var totalAbonoFormatted = formatter.format(totalAbono);
            var totalPendienteFormatted = formatter.format(totalPendiente);

            // Asignar los totales a los elementos HTML
            $('#credito-cxp').html(totalCreditoFormatted);
            $('#abono-cxp').html(totalAbonoFormatted);
            $('#total-footer-cxp').html(totalPendienteFormatted);
        },
        "buttons": [{
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
                    columns: [1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Cuentas por Pagar Proveedores',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                },
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
    table_cuentas_por_pagar_proveedores.search('').draw();
    $('#buscar').focus();

    registrar_pago_proveedores_dataTable("#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores);
    ver_abono_cxp_proveedor_dataTable("#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores);
    ver_reporte_facturas_cxp_proveedor_dataTable("#dataTableCuentasPorPagarProveedores tbody",
        table_cuentas_por_pagar_proveedores);
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
        console.log('saldo', data.saldo)
        if (data.saldo <= 0) {
            swal({
                title: "Alerta",
                text: "Esta Factura ya fue Cancelada",
                icon: "info",
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
            });
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

$(document).ready(function() {
    $("#modal_registrar_clientes").on('shown.bs.modal', function() {
        $(this).find('#formClientes #nombre_clientes').focus();
    });
});

//INICIO ACCIONES FROMULARIO CLIENTES
var listar_clientes = function(estado) {
    var table_clientes = $("#dataTableClientes").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableClientes.php",
            "data": {
                "estado": estado // nuevo parámetro
            }
        },
        "columns": [{
                "data": "cliente"
            },
            {
                "data": "rtn"
            },
            {
                "data": "telefono"
            },
            {
                "data": "correo"
            },
            {
                "data": "departamento"
            },
            {
                "data": "municipio"
            },
            {
                "data": "sistema"
            },
            {
                "defaultContent": "<button class='table_crear btn btn-dark ocultar generar'><span class='fab fa-centos fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "30%",
                targets: 0
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "14%",
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
                width: "10%",
                targets: 5
            },
            {
                width: "8%",
                targets: 6
            },
            {
                width: "2%",
                targets: 7
            },
            {
                width: "2%",
                targets: 8
            },
            {
                width: "2%",
                targets: 9
            }
        ],
        "createdRow": function(row, data, dataIndex) {
            var cells = $(row).find("td"); // Obtén todas las celdas en la fila
            $(cells[7]).addClass("generar");
            $(cells[6]).addClass("sistema");
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Clientes',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_clientes();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Clientes',
                className: 'btn btn-primary ocultar',
                action: function() {
                    modal_clientes();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Clientes',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 6]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                pageSize: 'LEGAL',
                title: 'Reporte de Clientes',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
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

            //Ocultamos el boton generar si el permiso no es super administrator, administrador o reseller
            if (getPrivilegioUsuario() !== 1 || getPrivilegioUsuario() !== 2 ||
                getPrivilegioUsuario() !== 3) {
                var db_consulta = getSessionUser() === "" ? DB_MAIN : getSessionUser();
                if (db_consulta === DB_MAIN) {
                    $('.generar').show();
                } else {
                    $('.generar').hide();
                    $('.sistema').hide();
                }
            } else {
                $('.generar').hide();
            }
        }
    });
    table_clientes.search('').draw();
    $('#buscar').focus();

    generar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
    editar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
    eliminar_clientes_dataTable("#dataTableClientes tbody", table_clientes);
}

var listar_generar_clientes = function() {
    var clientes_id = $("#formGenerarSistema #clientes_id").val();

    // Destruir la tabla si ya existe
    if (table_generar_clientes) {
        table_generar_clientes.destroy();
    }

    var table_generar_clientes = $("#DatatableGenerarSistema").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableGenerarSistema.php",
            "data": {
                "clientes_id": clientes_id,
            }
        },
        "columns": [{
                "data": "nombre"
            },
            {
                "data": "db"
            },
            {
                "data": "sistema"
            },
            {
                "data": "plan"
            },
            {
                "data": "validar"
            },
        ],
        "lengthMenu": lengthMenu20,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "60%",
                targets: 0
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "5%",
                targets: 2
            },
            {
                width: "5%",
                targets: 3
            },
            {
                width: "20%",
                targets: 4
            }
        ],
        "buttons": [{
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
                },
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

    table_generar_clientes.search('').draw();

    $('#buscar').focus();
}

$(document).ready(function() {
    $("#modal_generar_sistema").on('shown.bs.modal', function() {
        $(this).find('#formGenerarSistema #empresa').focus();
    });
});

// Definir un mapa que asocie los valores de sistema_id con las URLs
const sistemaUrls = {
    1: '<?php echo SERVERURL;?>core/scriptDataBaseIZZY.php',
    2: '<?php echo SERVERURL;?>core/scriptDataBaseCAMI.php',
};

$("#reg_generarSitema").click(function(e) {
    e.preventDefault();

    var clientes_id = $("#formGenerarSistema #clientes_id").val();
    var db = $("#formGenerarSistema #db").val();
    var validar = $("#formGenerarSistema #validar").val();
    var sistema_id = $("#formGenerarSistema #sistema").val();
    var planes_id = $("#formGenerarSistema #plan").val();
    var url = "";

    if (sistemaUrls.hasOwnProperty(sistema_id)) {
        url = sistemaUrls[sistema_id];
    }

    var razon_social = $("#formGenerarSistema #cliente").val();
    var rtn = $("#formGenerarSistema #rtn").val();
    var empresa = $("#formGenerarSistema #empresa").val();
    var correo = $("#formGenerarSistema #clientes_correo").val();
    var eslogan = $("#formGenerarSistema #eslogan").val();
    var otra_informacion = $("#formGenerarSistema #otra_informacion").val();
    var celular = $("#formGenerarSistema #whatsApp").val();
    var ubicacion = $("#formGenerarSistema #clientes_ubicacion").val();

    var telefono = $("#formGenerarSistema #clientes_telefono").val();

    var estado = 1;

    $.ajax({
        url: url,
        type: "POST",
        data: {
            clientes_id: clientes_id,
            db: db,
            validar: validar,
            sistema_id: sistema_id,
            planes_id: planes_id,
            estado: estado,
            razon_social: razon_social,
            rtn: rtn,
            empresa: empresa,
            correo: correo,
            telefono: telefono,
            eslogan: eslogan,
            otra_informacion: otra_informacion,
            celular: celular,
            ubicacion: ubicacion
        },
        beforeSend: function() {
            // Mostrar modal de carga antes de la solicitud AJAX
            $('#loadingMessage').text('Por favor espere, Generando Sistema...');
            $('#loadingModal').modal('show');
        },
        success: function(response) {
            // Verificar la respuesta del servidor
            if (response.startsWith("Éxito: ")) {
                var Message = response.substring(7);

                swal({
                    title: "Success",
                    text: Message,
                    icon: "success",
                    confirmButtonClass: "btn-primary",
                    timer: 3000,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                     
                });

                // Cerrar el modal de carga después de un breve retraso
                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);

                listar_generar_clientes();
            } else if (response.startsWith("Error DB: ")) {
                var Message = response.substring(10);

                swal({
                    title: "Error",
                    text: Message,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            } else if (response.startsWith("Error Sistema Existe: ")) {
                var Message = response.substring(22);

                swal({
                    title: "Error",
                    text: Message,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            } else if (response.startsWith("Error de conexión: ")) {
                var Message = response.substring(19);

                swal({
                    title: "Error",
                    text: Message,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            } else if (response.startsWith("Error al seleccionar la base de datos: ")) {
                var Message = response.substring(19);

                swal({
                    title: "Error",
                    text: Message,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            } else if (response.startsWith("Error Correo Existe: ")) {
                var Message = response.substring(21);

                swal({
                    title: "Error",
                    text: Message,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            } else {
                swal({
                    title: "Error",
                    text: "Error al generar el sistema",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });

                setTimeout(function() {
                    $('#loadingModal').modal('hide');
                }, 1000);
            }

            // Cerrar el modal de carga en caso de error
            $('#loadingModal').modal('hide');
        },
        error: function(xhr, status, error) {
            // Ocultar modal de carga en caso de error
            $('#loadingModal').modal('hide');
            console.error(xhr.responseText);

            swal({
                title: "Error",
                text: "Error al ejecutar el sistema",
                icon: "error",
                dangerMode: true,
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
            });
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

        if ($('#formGenerarSistema #db').val() === "") {
            $('#formGenerarSistema #db').val(data.db);
        }

        $('#formGenerarSistema #cliente').attr('disabled', true);
        $('#formGenerarSistema #rtn').attr('disabled', true);
        $('#formGenerarSistema #db').attr('disabled', true);

        $('#formGenerarSistema #proceso_GenerarSistema').val("Generar Sistema");

        getValidarFacturacion();

        if (data.correo === "") {
            swal({
                title: "Error",
                text: "Lo sentimos el cliente no tiene registrado un correo, es recomendable registrar uno, por favor diríjase al perfil del cliente y agregue el correo antes de generarle una cuenta",
                icon: "error",
                dangerMode: true,
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
            });

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

function empresaDB() {
    var primeros10Digitos = $('#formGenerarSistema #empresa').val().substring(0, 10);
    var resultado = primeros10Digitos.trim();

    $('#formGenerarSistema #db').val(resultado);
}

$('#formGenerarSistema #empresa').on('input', function(e) {
    if ($('#formGenerarSistema #empresa').val() !== "") {
        empresaDB();
    }
});

var sistemaSeleccionadoAnterior = ""; // Variable para almacenar el sistema seleccionado anteriormente

$('#formGenerarSistema #sistema').on('change', function(e) {
    if ($('#formGenerarSistema #empresa').val() !== "") {
        empresaDB();
        var nombreSistemaSeleccionado = $('#formGenerarSistema #sistema option:selected').text().toLowerCase()
            .substring(0, 10);
        var valorDb = $('#formGenerarSistema #db').val();

        // Verificar si hay un sistema anterior y eliminarlo
        if (sistemaSeleccionadoAnterior !== "") {
            valorDb = valorDb.replace("esmultiservicios_" + sistemaSeleccionadoAnterior, "");
        }

        // Verificar si ya hay "esmultiservicios_" al principio, de lo contrario, agregarlo
        if (valorDb.indexOf("esmultiservicios_") !== 0) {
            valorDb = "esmultiservicios_" + valorDb;
        }

        // Concatenar el nuevo nombre de sistema seleccionado
        valorDb = valorDb.replace("esmultiservicios_" + nombreSistemaSeleccionado + "_", "");
        valorDb = valorDb.replace(/_+/g, "_"); // Eliminar duplicaciones de guiones bajos
        valorDb = valorDb.replace("esmultiservicios_", ""); // Eliminar cualquier repetición

        // Establecer el valor del campo "db" con el resultado
        valorDb = "esmultiservicios_" + valorDb + "_" + nombreSistemaSeleccionado;

        // Obtener los primeros 10 dígitos de valorDb
        var primeros10Digitos = valorDb.substring(0, 10);

        // Establecer el valor del campo "db" con el resultado final
        $('#formGenerarSistema #db').val(primeros10Digitos + valorDb.substring(10));

        // Actualizar el sistema seleccionado anteriormente
        sistemaSeleccionadoAnterior = nombreSistemaSeleccionado;
    }
});

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
        $('#formClientes #clientes_id').val(data.clientes_id)

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formClientes').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formClientes').attr({
                    'data-form': 'update'
                });
                $('#formClientes').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarClientesAjax.php'
                });
                $('#formClientes')[0].reset();
                $('#reg_cliente').hide();
                $('#edi_cliente').show();
                $('#delete_cliente').hide();

                $('#formClientes #nombre_clientes').val(valores[0]);
                $('#formClientes #identidad_clientes').val(valores[1]);
                $('#formClientes #fecha_clientes').attr('disabled', true);
                $('#formClientes #fecha_clientes').val(valores[2]);
                $('#formClientes #departamento_cliente').val(valores[3]);
                $('#formClientes #departamento_cliente').selectpicker('refresh');
                getMunicipiosClientes(valores[4]);
                $('#formClientes #municipio_cliente').val(valores[4]);
                $('#formClientes #municipio_cliente').selectpicker('refresh');
                $('#formClientes #dirección_clientes').val(valores[5]);
                $('#formClientes #telefono_clientes').val(valores[6]);
                $('#formClientes #correo_clientes').val(valores[7]);

                if (valores[8] == 1) {
                    $('#formClientes #clientes_activo').attr('checked', true);
                } else {
                    $('#formClientes #clientes_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS
                $('#formClientes #nombre_clientes').attr("readonly", false);
                $('#formClientes #departamento_cliente').attr("disabled", false);
                $('#formClientes #municipio_cliente').attr("disabled", false);
                $('#formClientes #dirección_clientes').attr("disabled", false);
                $('#formClientes #telefono_clientes').attr("readonly", false);
                $('#formClientes #correo_clientes').attr("readonly", false);
                $('#formClientes #clientes_activo').attr("disabled", false);
                $('#formClientes #grupo_editar_rtn').show();

                //DESHABILITAR
                $('#formClientes #identidad_clientes').attr("readonly", true);
                $('#formClientes #fecha_clientes').attr("readonly", true);
                $('#formClientes #estado_clientes').show();

                $('#formClientes #proceso_clientes').val("Editar");
                $('#modal_registrar_clientes').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarClientes.php';
        $('#formClientes #clientes_id').val(data.clientes_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formClientes').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formClientes').attr({
                    'data-form': 'delete'
                });
                $('#formClientes').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarClientesAjax.php'
                });
                $('#formClientes')[0].reset();
                $('#reg_cliente').hide();
                $('#edi_cliente').hide();
                $('#delete_cliente').show();

                $('#formClientes #nombre_clientes').val(valores[0]);
                $('#formClientes #identidad_clientes').val(valores[1]);
                $('#formClientes #fecha_clientes').attr('disabled', true);
                $('#formClientes #fecha_clientes').val(valores[2]);
                $('#formClientes #departamento_cliente').val(valores[3]);
                $('#formClientes #departamento_cliente').selectpicker('refresh');
                getMunicipiosClientes(valores[4]);
                $('#formClientes #municipio_cliente').val(valores[4]);
                $('#formClientes #municipio_cliente').selectpicker('refresh');
                $('#formClientes #dirección_clientes').val(valores[5]);
                $('#formClientes #telefono_clientes').val(valores[6]);
                $('#formClientes #correo_clientes').val(valores[7]);

                if (valores[8] == 1) {
                    $('#formClientes #clientes_activo').attr('checked', true);
                } else {
                    $('#formClientes #clientes_activo').attr('checked', false);
                }

                //DESHABILITAR OBJETOS
                $('#formClientes #nombre_clientes').attr("readonly", true);
                $('#formClientes #identidad_clientes').attr("readonly", true);
                $('#formClientes #fecha_clientes').attr("readonly", true);
                $('#formClientes #departamento_cliente').attr("disabled", true);
                $('#formClientes #municipio_cliente').attr("disabled", true);
                $('#formClientes #dirección_clientes').attr("disabled", true);
                $('#formClientes #telefono_clientes').attr("readonly", true);
                $('#formClientes #correo_clientes').attr("readonly", true);
                $('#formClientes #clientes_activo').attr("disabled", true);
                $('#formClientes #estado_clientes').hide();
                $('#formClientes #grupo_editar_rtn').hide();

                $('#formClientes #proceso_clientes').val("Eliminar");
                $('#modal_registrar_clientes').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
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
$('#formClientes #grupo_editar_rtn').on('click', function(e) {
    e.preventDefault();

    $('#formEditarRTNClientes')[0].reset();
    $('#formEditarRTNClientes #pro_clientes').val("Editar");
    $('#formEditarRTNClientes #clientes_id').val($('#formClientes #clientes_id').val());
    $('#formEditarRTNClientes #cliente').val($('#formClientes #nombre_clientes').val());
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
                className: "btn-primary",
                closeModal: false
            }
        },
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
    }).then((isConfirm) => {
        if (isConfirm) {
            editRTNCliente(clientes_id, rtn);
        }
    });
}

function editRTNCliente(clientes_id, rtn) {
    var url = '<?php echo SERVERURL; ?>core/editRTNCliente.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'clientes_id=' + clientes_id + '&rtn=' + rtn,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "El RTN ha sido actualizado satisfactoriamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
                listar_clientes();
                $('#formClientes #identidad_clientes').val(rtn);
            } else if (data == 2) {
                swal({
                    title: "Error",
                    text: "Error el RTN no se puede actualizar",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            } else if (data == 3) {
                swal({
                    title: "Error",
                    text: "El RTN ya existe",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            }
        }
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
            $('#formEfectivoBill #monto_efectivo').val(saldoFactura);
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
            //$('#formEfectivoBill #monto_efectivo').val(saldoFactura);
            $('#Purchase-pay').html(saldoFactura);
        }
    });
}
//FIN ACCIONES FROMULARIO CLIENTES

//INICIO MODAL REGSITRAR PAGO FACTURACIÓN CLIENTES
function customRound(number) {
    var truncated = Math.floor(number * 100) / 100; // Trunca a dos decimales
    var secondDecimal = Math.floor((number * 100) % 10); // Obtiene el segundo decimal

    if (secondDecimal >= 5) { // Si el segundo decimal es mayor o igual a 5, redondea hacia arriba
        return parseFloat((truncated + 0.01).toFixed(2)); // Redondea hacia arriba
    } else { // Si el segundo decimal es menor que 5, no redondea
        return parseFloat(truncated.toFixed(2)); // No redondea
    }
}

function pago(facturas_id, tipoPago) {
    var url = '<?php echo SERVERURL;?>core/editarPagoFacturas.php';

    $('#pagos_multiples_switch').attr('checked', false);
    getCollaboradoresModalPagoFacturas();

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            facturas_id: facturas_id
        },
        dataType: 'json', // Asegúrate de que el servidor devuelve JSON
        success: function(datos) {
            // Verifica que datos sea un array o un objeto
            if (!Array.isArray(datos)) {
                console.error('Formato de datos inesperado:', datos);
                return;
            }

            $('#formEfectivoBill .border-right a:eq(0) a').tab('show');
            $("#customer-name-bill").html("<b>Cliente:</b> " + datos[0]);
            $("#customer_bill_pay").val(datos[6]);
            $('#bill-pay').html("L. " + parseFloat(datos[6]).toFixed(2));

            //EFECTIVO
            $('#formEfectivoBill')[0].reset();
            $('#formEfectivoBill #monto_efectivo').val(parseFloat(datos[6]));

            $('#formEfectivoBill #factura_id_efectivo').val(facturas_id);
            $('#formEfectivoBill #tipo_factura').val(tipoPago);
            $('#formEfectivoBill #pago_efectivo').attr('disabled', true);

            if (tipoPago == 2) {
                $('#bill-pay').html("L. " + parseFloat(datos[6]));
                $('#tab5').hide();
                $("#formEfectivoBill #tipo_factura_efectivo").val(tipoPago);

                $('#formTarjetaBill #monto_efectivo_tarjeta').show();
                $('#formTransferenciaBill #importe_transferencia').show();
                $('#formChequeBill #importe_cheque').show();
                $("#formEfectivoBill #grupo_cambio_efectivo").hide();
            }

            //TARJETA
            $('#formTarjetaBill')[0].reset();
            $('#formTarjetaBill #monto_efectivo').val(parseFloat(datos[6]));
            $('#formTarjetaBill #importe_tarjeta').val(parseFloat(datos[6]));
            $('#formTarjetaBill #factura_id_tarjeta').val(facturas_id);
            $('#formTarjetaBill #tipo_factura').val(tipoPago);
            $('#formTarjetaBill #pago_efectivo').attr('disabled', true);

            //TRANSFERENCIA
            $('#formTransferenciaBill')[0].reset();
            $('#formTransferenciaBill #monto_efectivo').val(parseFloat(datos[6]));
            $('#formTransferenciaBill #factura_id_transferencia').val(facturas_id);
            $('#formTransferenciaBill #tipo_factura_transferencia').val(tipoPago);
            $('#formTransferenciaBill #pago_efectivo').attr('disabled', true);

            //CHEQUES
            $('#formChequeBill')[0].reset();
            $('#formChequeBill #monto_efectivo').val(parseFloat(datos[6]));
            $('#formChequeBill #factura_id_cheque').val(facturas_id);
            $('#formChequeBill #pago_efectivo').attr('disabled', true);
            $('#formChequeBill #tipo_factura_cheque').val(tipoPago);

            $('#modal_pagos').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr, status, error) {
            console.error('Error al realizar la solicitud:', status, error);
        }
    });
}

$(document).ready(function() {
    $("#tab1").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formTarjetaBill #efectivo_bill').focus();
        });
    });

    $("#tab2").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formTarjetaBill #cr_bill').focus();
        });
    });

    $("#tab3").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formTarjetaBill #bk_nm').focus();
        });
    });

    $("#tab4").on("click", function() {
        $("#modal_pagos").on('shown.bs.modal', function() {
            $(this).find('#formChequeBill #bk_nm_chk').focus();
        });
    });
});

$(document).ready(function() {
    $('#formTarjetaBill #cr_bill').inputmask("9999");
});

$(document).ready(function() {
    $('#formTarjetaBill #exp').inputmask("99/99");
});

$(document).ready(function() {
    $('#formTarjetaBill #cvcpwd').inputmask("999999");
});

$(document).ready(function() {
    $("#formEfectivoBill #efectivo_bill").on("keyup", function() {
        var efectivo = parseFloat($("#formEfectivoBill #efectivo_bill").val()).toFixed(2);
        var monto = parseFloat($("#formEfectivoBill #monto_efectivo").val()).toFixed(2);
        var credito = $("#formEfectivoBill #tipo_factura").val();
        var pagos_multiples = $('#pagos_multiples_switch').val();

        if (credito == 2) {
            $("#formEfectivoBill #cambio_efectivo").val(0)
            $("#formEfectivoBill #grupo_cambio_efectivo").hide();
        }

        var total = efectivo - monto;

        if (Math.floor(efectivo * 100) >= Math.floor(monto * 100) || credito == 2 || pagos_multiples ==
            1) {
            $('#formEfectivoBill #cambio_efectivo').val(parseFloat(total).toFixed(2));
            $('#formEfectivoBill #pago_efectivo').attr('disabled', false);

            //aqi
        } else {
            $('#formEfectivoBill #cambio_efectivo').val(parseFloat(0).toFixed(2));
            $('#formEfectivoBill #pago_efectivo').attr('disabled', true);
        }

        if (parseFloat(efectivo) > parseFloat(monto)) {
            $('#formEfectivoBill #pago_efectivo').attr('disabled', true);
        }
    });
});

function getBanco() {
    var url = '<?php echo SERVERURL;?>core/getBanco.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formTransferenciaBill #bk_nm').html("");
            $('#formTransferenciaBill #bk_nm').html(data);
            $('#formTransferenciaBill #bk_nm').selectpicker('refresh');

            $('#formChequeBill #bk_nm_chk').html("");
            $('#formChequeBill #bk_nm_chk').html(data);
            $('#formChequeBill #bk_nm_chk').selectpicker('refresh');
        }

    });
}
//FIN MODAL REGSITRAR PAGO FACTURACIÓN CLIENTES

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
            $('#formTarjetaPurchase #monto_efectivo_tarjeta').prop("type", "text")
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
            $('#formTarjetaPurchase #monto_efectivo_tarjeta').prop("type", "hidden")
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
            $('#formTarjetaPurchase #monto_efectivo_tarjeta').prop("type", "text")
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
            $('#formTarjetaPurchase #monto_efectivo_tarjeta').prop("type", "hidden")
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
            $('#formEfectivoPurchase #monto_efectivoPurchase').val(datos[3]);
            $('#formEfectivoPurchase #compras_id_efectivo').val(compras_id);
            $('#formEfectivoPurchase #pago_efectivo').attr('disabled', true);
            $('#formEfectivoPurchase #tipo_purchase_efectivo').val(tipo);

            if (tipo == '2') {
                $('#monto_efectivo_tarjeta').attr('type', 'number');
                $('#tab5Purchase').hide();
                $('#importe_transferencia').attr('type', 'number');
                $('#importe_cheque').attr('type', 'number');
                //
                $("#formEfectivoBill #cambio_efectivo").val(0)
                $("#grupo_cambio_compras").hide();
            }

            //TARJETA
            $('#formTarjetaPurchase')[0].reset();
            $('#formTarjetaPurchase #monto_efectivoPurchase').val(datos[3]);
            $('#formTarjetaPurchase #compras_id_tarjeta').val(compras_id);
            $('#formTarjetaPurchase #pago_efectivo').attr('disabled', true);
            $('#formTarjetaPurchase #tipo_purchase_efectivo').val(tipo);

            //TRANSFERENCIA
            $('#formTransferenciaPurchase')[0].reset();
            $('#formTransferenciaPurchase #monto_efectivoPurchase').val(datos[3]);
            $('#formTransferenciaPurchase #compras_id_transferencia').val(compras_id);
            $('#formTransferenciaPurchase #pago_efectivo').attr('disabled', true);
            $('#formTransferenciaPurchase #tipo_purchase_efectivo').val(tipo);

            //CHEQUE
            $('#formChequePurchase #compras_id_cheque').val(compras_id);
            $('#formChequePurchase #tipo_purchase_efectivo').val(tipo);
            $('#formChequePurchase #monto_efectivoPurchase').val(datos[3]);

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
        var monto = parseFloat($("#formEfectivoPurchase #monto_efectivoPurchase").val()).toFixed(2);
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
    var url = '<?php echo SERVERURL;?>core/getBanco.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formTransferenciaPurchase #bk_nm').html("");
            $('#formTransferenciaPurchase #bk_nm').html(data);
            $('#formTransferenciaPurchase #bk_nm').selectpicker('refresh');

            $('#formChequePurchase #bk_nm_chk').html("");
            $('#formChequePurchase #bk_nm_chk').html(data);
            $('#formChequePurchase #bk_nm_chk').selectpicker('refresh');
        }
    });
}
//FIN MODAL REGSITRAR PAGO COMPRAS PROVEEDORES

function getCollaboradoresModalPagoFacturas() {
    var url = '<?php echo SERVERURL;?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEfectivoBill #usuario_efectivo').html("");
            $('#formEfectivoBill #usuario_efectivo').html(data);
            $('#formEfectivoBill #usuario_efectivo').selectpicker('refresh');

            $('#formTarjetaBill #usuario_tarjeta').html("");
            $('#formTarjetaBill #usuario_tarjeta').html(data);
            $('#formTarjetaBill #usuario_tarjeta').selectpicker('refresh');

            $('#formTransferenciaBill #usuario_transferencia').html("");
            $('#formTransferenciaBill #usuario_transferencia').html(data);
            $('#formTransferenciaBill #usuario_transferencia').selectpicker('refresh');

            $('#formChequeBill #usuario_cheque').html("");
            $('#formChequeBill #usuario_cheque').html(data);
            $('#formChequeBill #usuario_cheque').selectpicker('refresh');
        }
    });
}

function getCollaboradoresModalPagoFacturasCompras() {
    var url = '<?php echo SERVERURL;?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEfectivoPurchase #usuario_efectivo_compras').html("");
            $('#formEfectivoPurchase #usuario_efectivo_compras').html(data);
            $('#formEfectivoPurchase #usuario_efectivo_compras').selectpicker('refresh');

            $('#formTarjetaPurchase #usuario_tarjeta_compras').html("");
            $('#formTarjetaPurchase #usuario_tarjeta_compras').html(data);
            $('#formTarjetaPurchase #usuario_tarjeta_compras').selectpicker('refresh');

            $('#formTransferenciaPurchase #usuario_transferencia_compras').html("");
            $('#formTransferenciaPurchase #usuario_transferencia_compras').html(data);
            $('#formTransferenciaPurchase #usuario_transferencia_compras').selectpicker('refresh');

            $('#formChequePurchase #usuario_cheque_compras').html("");
            $('#formChequePurchase #usuario_cheque_compras').html(data);
            $('#formChequePurchase #usuario_cheque_compras').selectpicker('refresh');
        }
    });
}

//IMAGE FILE TYPE VALIDATION
$(document).on("click", ".browse", function() {
    var file = $(this)
        .parent()
        .parent()
        .parent()
        .find(".file");
    file.trigger("click");
});
$('input[type="file"]').change(function(e) {
    var file = this.files[0];
    var imagefile = file.type;
    var match = ["image/jpeg", "image/png", "image/jpg"];
    if (!((imagefile == match[0]) || (imagefile == match[1]) || (imagefile == match[2]))) {
        swal({
            title: "Error",
            text: "Por favor seleccione una archivo valido con el formato (JPEG/JPG/PNG)",
            icon: "error",
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
        });
        $("#file").val('');
        return false;
    } else {
        var fileName = e.target.files[0].name;
        $("#formProductos #file_product").val(fileName);

        var reader = new FileReader();
        reader.onload = function(e) {
            // get loaded data and render thumbnail.
            document.getElementById("preview").src = e.target.result;
        };
        // read the image file as a data URL.
        reader.readAsDataURL(this.files[0]);
    }
});


//INICIO ASISTENCIA
$(document).ready(function() {
    listar_asistencia();
    getColaboradores();
    $('#form_main_asistencia #estado').val(0);
    $('#form_main_asistencia #estado').selectpicker('refresh');
});

//INICIO ACCIONES FROMULARIO PRIVILEGIOS
var listar_asistencia = function() {
    var estado = $('#form_main_asistencia #estado').val();
    var colaboradores_id = $('#form_main_asistencia #colaborador').val();
    var fechai = $('#form_main_asistencia #fechai').val();
    var fechaf = $('#form_main_asistencia #fechaf').val();

    var table_asistencia = $("#dataTableAsistencia").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableAsistencia.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf,
                "colaborador": colaboradores_id,
                "estado": estado
            }
        },
        "columns": [{
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
                "data": "horat"
            },
            {
                "data": "comentario"
            },
            {
                "defaultContent": "<button class='table_editar editar_asistencia btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar eliminar_salida btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar eliminar_marcaje btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "15.11%",
                targets: 0
            },
            {
                width: "8.11%",
                targets: 1
            },
            {
                width: "9.11%",
                targets: 2
            },
            {
                width: "9.11%",
                targets: 3
            },
            {
                width: "11.11%",
                targets: 4
            },
            {
                width: "19.11%",
                targets: 5
            },
            {
                width: "6.11%",
                targets: 6
            },
            {
                width: "10.11%",
                targets: 7
            },
            {
                width: "11.11%",
                targets: 8
            }
        ],
        "buttons": [{
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
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

        swal({
            title: "¿Estas seguro?",
            text: "¿Desea eliminar la asistencia para el colaborador: # " + data.colaborador +
                    ", para la fecha " + data.fecha + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, eliminar la asistencia!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
        }).then((willConfirm) => {
            if (willConfirm === true) {
                deleteAsistenciaMarcajeSalidaColaborador(data.asistencia_id);
            }
        });
    });
}

function deleteAsistenciaMarcajeSalidaColaborador(asistencia_id) {
    var url = '<?php echo SERVERURL;?>core/deleteAsistenciaColaborador.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'asistencia_id=' + asistencia_id,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "La asitencia ha sido eliminada correctamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                     
                });
                listar_asistencia();
            } else {
                swal({
                    title: 'Error',
                    text: 'Lo sentimos no se puede eliminar la asistencia',
                    icon: 'error',
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            }
        }
    });
}

var delete_marcaje_asistencia_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.eliminar_salida");
    $(tbody).on("click", "button.eliminar_salida", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Estas seguro?",
            text: "¿Desea eliminar el marcaje de salida para el colaborador: # " + data
                    .colaborador + ", para la fecha " + data.fecha + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, eliminar el marcaje de salida!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
        }).then((willConfirm) => {
            if (willConfirm === true) {
                deleteMarcajeSalida(data.asistencia_id);
            }
        });
    });
}

function deleteMarcajeSalida(asistencia_id) {
    var url = '<?php echo SERVERURL;?>core/deleteMarcajeSalidaColaborador.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'asistencia_id=' + asistencia_id,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "El marcaje de salida ha sido eliminado correctamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                     
                });
                listar_asistencia();
            } else if (data == 3) {
                swal({
                    title: 'Error',
                    text: 'No hay marcaje de salida',
                    icon: 'error',
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            } else {
                swal({
                    title: 'Error',
                    text: 'Lo sentimos no se puede eliminar el marcaje de salida',
                    icon: 'error',
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            }
        }
    });
}

var edit_asistencia_colaboradores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.editar_asistencia");
    $(tbody).on("click", "button.editar_asistencia", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarAsistencia.php';
        $('#formAsistencia')[0].reset();
        $('#formAsistencia #asistencia_id').val(data.asistencia_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formAsistencia').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formAsistencia').attr({
                    'data-form': 'update'
                });
                $('#formAsistencia').attr({
                    'action': '<?php echo SERVERURL;?>ajax/updateAsistenciaAjax.php'
                });
                $('#reg_asistencia').hide();
                $('#edi_asistencia').show();

                $('#formAsistencia #asistencia_empleado').val(valores[0]);
                $('#formAsistencia #asistencia_empleado').selectpicker('refresh');
                $('#formAsistencia #fecha').val(valores[1]);
                $('#formAsistencia #horagi').val(valores[2]);
                $('#formAsistencia #horagf').val(valores[3]);
                $('#formAsistencia #comentario').val(valores[5]);

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
            }
        });
    });
}

function getColaboradores() {
    var url = '<?php echo SERVERURL;?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_asistencia #colaborador').html("");
            $('#form_main_asistencia #colaborador').html(data);
            $('#form_main_asistencia #colaborador').selectpicker('refresh');

            $('#formAsistencia #asistencia_empleado').html("");
            $('#formAsistencia #asistencia_empleado').html(data);
            $('#formAsistencia #asistencia_empleado').selectpicker('refresh');
        }
    });
}

function modal_asistencia() {
    $('#formAsistencia').attr({
        'data-form': 'save'
    });
    $('#formAsistencia').attr({
        'action': '<?php echo SERVERURL;?>ajax/addAsistenciaAjax.php'
    });
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
            console.error("Error al obtener la URL de la imagen");
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
</script>