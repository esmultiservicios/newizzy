<script>
(function() {
    "use strict";

    /* =========================================================
        CONFIGURACIÓN DE RUTAS
    ========================================================= */
    const MENU_URLS = {
        llenarDataTable: "<?php echo SERVERURL;?>core/menus/llenarDataTableMenus.php",
        getDependencies: "<?php echo SERVERURL;?>core/menus/getDependenciesMenu.php",
        getMenuById: "<?php echo SERVERURL;?>core/menus/getMenuById.php",
        agregarMenu: "<?php echo SERVERURL;?>core/menus/agregarMenu.php",
        editarMenu: "<?php echo SERVERURL;?>core/menus/editarMenu.php",
        eliminarMenu: "<?php echo SERVERURL;?>core/menus/eliminarMenu.php"
    };

    /* =========================================================
        VARIABLES DE CONTROL
    ========================================================= */
    let isEditing = false;
    let currentEditId = null;
    let currentEditType = null;
    let dataTableMenus = null;
    let intentosCargaMenus = 0;
    const MAX_INTENTOS_CARGA_MENUS = 40;

    /* =========================================================
        VALIDACIÓN DE DEPENDENCIAS JS
    ========================================================= */
    function dependenciasMenusDisponibles() {
        if (typeof window.jQuery === "undefined") {
            return false;
        }

        if (typeof $.fn === "undefined") {
            return false;
        }

        if (typeof $.fn.DataTable !== "function") {
            return false;
        }

        if (typeof $.fn.dataTable === "undefined") {
            return false;
        }

        if (typeof $.fn.dataTable.isDataTable !== "function") {
            return false;
        }

        return true;
    }

    function esperarDependenciasMenus() {
        if (dependenciasMenusDisponibles()) {
            inicializarModuloMenus();
            return;
        }

        intentosCargaMenus++;

        if (intentosCargaMenus >= MAX_INTENTOS_CARGA_MENUS) {
            console.error("DataTables no está disponible. Revise que jquery.dataTables.min.js se cargue antes de este script.");

            if (typeof showNotify === "function") {
                showNotify(
                    "error",
                    "Error",
                    "No se pudo cargar DataTables. Revise la carga de librerías JS."
                );
            }

            return;
        }

        setTimeout(esperarDependenciasMenus, 150);
    }

    /* =========================================================
        HELPERS GENERALES
    ========================================================= */
    function notificarMenu(type, title, message) {
        if (typeof showNotify === "function") {
            showNotify(type, title, message);
        } else {
            console.log(title + ": " + message);
        }
    }

    function inicializarSelectPicker($select) {
        if (!$select || $select.length === 0) {
            return;
        }

        if (typeof $.fn.selectpicker !== "function") {
            return;
        }

        if ($select.hasClass("selectpicker")) {
            try {
                $select.selectpicker("destroy");
            } catch (e) {

            }
        }

        $select.addClass("selectpicker").selectpicker({
            liveSearch: true,
            size: 10,
            noneSelectedText: "Seleccione una opción"
        });

        $select.selectpicker("refresh");
    }

    function refrescarSelectPicker($select) {
        if (!$select || $select.length === 0) {
            return;
        }

        if (typeof $.fn.selectpicker === "function" && $select.hasClass("selectpicker")) {
            $select.selectpicker("refresh");
        }
    }

    function obtenerTipoInternoDesdeTexto(tipoTexto) {
        if (!tipoTexto) {
            return "menu";
        }

        if (tipoTexto.includes("Nivel 1")) {
            return "submenu";
        }

        if (tipoTexto.includes("Nivel 2")) {
            return "submenu1";
        }

        return "menu";
    }

    function bloquearBotonAccion(texto) {
        $("#btnAccionMenu")
            .prop("disabled", true)
            .html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + texto);
    }

    function restaurarBotonAccion() {
        $("#btnAccionMenu").prop("disabled", false);

        if (isEditing) {
            $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Actualizar');
        } else {
            $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Registrar');
        }
    }

    function mostrarAdvertenciasSincronizacion(response) {
        if (!response || !response.warnings || !Array.isArray(response.warnings) || response.warnings.length === 0) {
            return;
        }

        console.warn("Advertencias de sincronización de menús:", response.warnings);

        notificarMenu(
            "warning",
            "Sincronización parcial",
            "El cambio se aplicó en la base principal, pero algunas bases de clientes no pudieron sincronizarse. Revise consola/logs."
        );
    }

    function cerrarLoadingSiExiste() {
        if (typeof swal !== "undefined") {
            try {
                swal.close();
            } catch (e) {

            }
        }
    }

    /* =========================================================
        VISTA PREVIA DEL ÍCONO
    ========================================================= */
    function eventoVistaPreviaIcono() {
        $("#icono_menu").off("input.menusAdmin").on("input.menusAdmin", function() {
            const iconClass = $(this).val().trim();

            if (iconClass) {
                $("#icono_preview").attr("class", iconClass);
            } else {
                $("#icono_preview").attr("class", "fas fa-question");
            }
        });
    }

    /* =========================================================
        CARGAR DEPENDENCIAS
    ========================================================= */
    function loadDependencies(type, selectId, dependencyId = null) {
        let data = {
            tipo: type === "submenu"
                ? "getMenus"
                : type === "submenu1"
                    ? "getAllSubmenus"
                    : "getSubmenusByMenu"
        };

        if (type === "submenu1" && dependencyId) {
            data.menu_id = dependencyId;
        }

        $.ajax({
            url: MENU_URLS.getDependencies,
            method: "POST",
            data: data,
            dataType: "json",
            success: function(response) {
                const $select = $("#" + selectId);

                if (!response || !Array.isArray(response.data)) {
                    notificarMenu("error", "Error", "Respuesta inválida al cargar dependencias");
                    return;
                }

                let options = '<option value="">Seleccionar...</option>';

                response.data.forEach(function(item) {
                    const texto = item.descripcion || item.nombre || "";
                    options += '<option value="' + item.id + '">' + texto + '</option>';
                });

                $select.html(options);

                inicializarSelectPicker($select);

                if (dependencyId !== null && dependencyId !== "") {
                    $select.val(dependencyId);
                } else {
                    $select.val("");
                }

                refrescarSelectPicker($select);
            },
            error: function(xhr) {
                console.error("Error cargando dependencias:", xhr.responseText);
                notificarMenu("error", "Error", "No se pudieron cargar las dependencias");
            }
        });
    }

    /* =========================================================
        MOSTRAR / OCULTAR DEPENDENCIA
    ========================================================= */
    function eventoTipoMenu() {
        $("#tipo_menu").off("change.menusAdmin").on("change.menusAdmin", function() {
            const tipo = $(this).val();
            const $dependenciaGroup = $("#dependencia_menu_group");

            if (tipo === "submenu" || tipo === "submenu1") {
                $dependenciaGroup.show();
                $("#label_dependencia").text(tipo === "submenu" ? "Menú Principal" : "Submenú Nivel 1");
                loadDependencies(tipo, "dependencia_menu");
            } else {
                $dependenciaGroup.hide();
                $("#dependencia_menu").val("");
                refrescarSelectPicker($("#dependencia_menu"));
            }
        });
    }

    /* =========================================================
        HEADER DINÁMICO - MENÚS
    ========================================================= */
    function construirHeaderDataTableMenus() {
        const $tabla = $("#dataTableMenus");

        $tabla.empty();

        $tabla.append(
            '<thead>' +
                '<tr>' +
                    '<th>Acciones</th>' +
                    '<th>Tipo</th>' +
                    '<th>Nombre</th>' +
                    '<th>Descripción</th>' +
                    '<th>Ícono</th>' +
                    '<th>Orden</th>' +
                    '<th>Dependencia</th>' +
                    '<th>Visible</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody></tbody>'
        );
    }

    /* =========================================================
        DATATABLE - REGISTRAR MENÚS
    ========================================================= */
    function inicializarDataTableMenus() {
        if ($("#dataTableMenus").length === 0) {
            console.error("No existe la tabla #dataTableMenus en esta vista.");
            return;
        }

        if ($.fn.dataTable.isDataTable("#dataTableMenus")) {
            $("#dataTableMenus").DataTable().clear().destroy();
        }

        construirHeaderDataTableMenus();

        dataTableMenus = $("#dataTableMenus").DataTable({
            destroy: true,
            ajax: {
                url: MENU_URLS.llenarDataTable,
                type: "POST",
                dataSrc: "data",
                error: function(xhr) {
                    console.error("Error cargando DataTable de menús:", xhr.responseText);
                    notificarMenu("error", "Error", "No se pudo cargar el listado de menús.");
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

                        return '' +
                            '<div class="dropdown acciones-dropdown">' +

                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +

                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

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
                    data: "type"
                },
                {
                    data: "name"
                },
                {
                    data: "descripcion"
                },
                {
                    data: "icon",
                    className: "text-center",
                    render: function(data, type, row) {
                        if (type !== "display") {
                            return data || "";
                        }

                        return data ? '<i class="' + data + '"></i>' : "";
                    }
                },
                {
                    data: "orden",
                    className: "text-center"
                },
                {
                    data: "dependency"
                },
                {
                    data: "visible",
                    className: "text-center",
                    render: function(data, type, row) {
                        if (type === "display") {
                            const icon = data == 1
                                ? '<i class="fas fa-circle-check mr-1"></i>'
                                : '<i class="fas fa-circle-xmark mr-1"></i>';

                            const badgeClass = data == 1
                                ? "badge badge-pill badge-success"
                                : "badge badge-pill badge-danger";

                            return '<span class="' + badgeClass +
                                '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                                icon + (data == 1 ? "Visible" : "Oculto") + '</span>';
                        }

                        return data;
                    }
                }
            ],
            language: typeof idioma_español !== "undefined" ? idioma_español : {},
            responsive: true,
            autoWidth: false,
            columnDefs: [
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
                    width: "24%",
                    targets: 3
                },
                {
                    width: "8%",
                    targets: 4,
                    className: "text-center"
                },
                {
                    width: "8%",
                    targets: 5,
                    className: "text-center"
                },
                {
                    width: "14%",
                    targets: 6
                },
                {
                    width: "8%",
                    targets: 7,
                    className: "text-center text-nowrap"
                }
            ],
            drawCallback: function(settings) {
                if (typeof getPermisosTipoUsuarioAccesosTable === "function" && typeof getPrivilegioTipoUsuario === "function") {
                    getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
                }

                if (typeof cerrarDropdownAcciones === "function") {
                    cerrarDropdownAcciones();
                }
            }
        });
    }

    /* =========================================================
        RESET FORMULARIO
    ========================================================= */
    function resetForm() {
        isEditing = false;
        currentEditId = null;
        currentEditType = null;

        if ($("#formulario_menu").length > 0) {
            $("#formulario_menu")[0].reset();
        }

        $("#menu_id").val("");

        $("#tipo_menu").val("");
        refrescarSelectPicker($("#tipo_menu"));

        $("#dependencia_menu").val("");
        refrescarSelectPicker($("#dependencia_menu"));

        $("#dependencia_menu_group").hide();
        $("#icono_preview").attr("class", "fas fa-question");
        $("#visible_menu").prop("checked", true);

        $("#form_title").html("Registrar Nuevo Elemento de Menú");
        $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Registrar');
        $("#btnCancelarEdicion").hide();
    }

    /* =========================================================
        VALIDAR FORMULARIO
    ========================================================= */
    function validarFormularioMenu() {
        const tipo = $("#tipo_menu").val();
        const nombre = $("#nombre_menu").val().trim();
        const descripcion = $("#descripcion_menu").val().trim();
        const dependencia = $("#dependencia_menu").val();

        if (tipo === "") {
            notificarMenu("warning", "Validación", "Seleccione el tipo de elemento.");
            return false;
        }

        if (nombre === "") {
            notificarMenu("warning", "Validación", "Ingrese el nombre interno del menú.");
            $("#nombre_menu").focus();
            return false;
        }

        if (descripcion === "") {
            notificarMenu("warning", "Validación", "Ingrese la descripción del menú.");
            $("#descripcion_menu").focus();
            return false;
        }

        if ((tipo === "submenu" || tipo === "submenu1") && (dependencia === "" || dependencia === null)) {
            notificarMenu("warning", "Validación", "Seleccione la dependencia del elemento.");
            return false;
        }

        return true;
    }

    /* =========================================================
        REGISTRAR / EDITAR MENÚ
    ========================================================= */
    function eventoSubmitFormularioMenu() {
        $("#formulario_menu").off("submit.menusAdmin").on("submit.menusAdmin", function(e) {
            e.preventDefault();

            if (!validarFormularioMenu()) {
                return;
            }

            const tipo = $("#tipo_menu").val();
            const nombre = $("#nombre_menu").val().trim();
            const descripcion = $("#descripcion_menu").val().trim();
            const dependencia = $("#dependencia_menu").val();
            const icono = $("#icono_menu").val().trim();
            const orden = $("#orden_menu").val();
            const visible = $("#visible_menu").is(":checked") ? 1 : 0;
            const id = $("#menu_id").val();

            const url = isEditing ? MENU_URLS.editarMenu : MENU_URLS.agregarMenu;

            const data = {
                tipo: tipo,
                nombre: nombre,
                descripcion: descripcion,
                dependencia: dependencia,
                icono: icono,
                orden: orden,
                visible: visible
            };

            if (isEditing) {
                data.id = id;
                data.edit_tipo = currentEditType;
            }

            $.ajax({
                url: url,
                method: "POST",
                data: data,
                dataType: "json",
                beforeSend: function() {
                    bloquearBotonAccion(isEditing ? "Actualizando..." : "Registrando...");
                },
                success: function(response) {
                    restaurarBotonAccion();

                    if (response && response.type) {
                        notificarMenu(response.type, response.title, response.message);

                        if (response.type === "success") {
                            mostrarAdvertenciasSincronizacion(response);
                            resetForm();

                            if (dataTableMenus) {
                                dataTableMenus.ajax.reload(null, false);
                            }
                        }

                        return;
                    }

                    notificarMenu("error", "Error", "Respuesta inválida del servidor");
                },
                error: function(xhr) {
                    restaurarBotonAccion();

                    const errorMsg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : "Error al procesar la solicitud";

                    console.error("Error guardando menú:", xhr.responseText);
                    notificarMenu("error", "Error", errorMsg);
                }
            });
        });
    }

    /* =========================================================
        CANCELAR EDICIÓN
    ========================================================= */
    function eventoCancelarEdicion() {
        $("#btnCancelarEdicion").off("click.menusAdmin").on("click.menusAdmin", function() {
            resetForm();
        });
    }

    /* =========================================================
        EDITAR MENÚ / SUBMENÚ / SUBMENÚ NIVEL 2
    ========================================================= */
    function eventoEditarMenu() {
        $(document).off("click.menusAdminEditar", ".table_editar").on("click.menusAdminEditar", ".table_editar", function() {
            const row = $(this).closest("tr");
            const rowData = dataTableMenus.row(row).data();

            if (!rowData) {
                notificarMenu("error", "Error", "No se pudo obtener la fila seleccionada.");
                return;
            }

            const id = rowData.id;
            const type = obtenerTipoInternoDesdeTexto(rowData.type);

            $.ajax({
                url: MENU_URLS.getMenuById,
                method: "POST",
                data: {
                    id: id,
                    tipo: type
                },
                dataType: "json",
                success: function(data) {
                    if (!data || !data.id) {
                        notificarMenu("error", "Error", data && data.message ? data.message : "No se pudieron cargar los datos");
                        return;
                    }

                    isEditing = true;
                    currentEditId = id;
                    currentEditType = type;

                    $("#menu_id").val(data.id);
                    $("#tipo_menu").val(type);
                    refrescarSelectPicker($("#tipo_menu"));

                    $("#nombre_menu").val(data.nombre);
                    $("#descripcion_menu").val(data.descripcion);
                    $("#icono_menu").val(data.icon);
                    $("#icono_preview").attr("class", data.icon || "fas fa-question");
                    $("#orden_menu").val(data.orden);
                    $("#visible_menu").prop("checked", data.visible == 1);

                    if (type === "submenu" || type === "submenu1") {
                        $("#dependencia_menu_group").show();
                        $("#label_dependencia").text(type === "submenu" ? "Menú Principal" : "Submenú Nivel 1");
                        loadDependencies(type, "dependencia_menu", data.dependency);
                    } else {
                        $("#dependencia_menu_group").hide();
                        $("#dependencia_menu").val("");
                        refrescarSelectPicker($("#dependencia_menu"));
                    }

                    $("#form_title").html("Editar Elemento de Menú");
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Actualizar');
                    $("#btnCancelarEdicion").show();

                    $("html, body").animate({
                        scrollTop: $("#div_top").offset().top - 20
                    }, 500, function() {
                        $("#nombre_menu").focus();
                    });
                },
                error: function(xhr) {
                    console.error("Error cargando menú:", xhr.responseText);
                    notificarMenu("error", "Error", "Error al cargar los datos del menú");
                }
            });
        });
    }

    /* =========================================================
        ELIMINAR MENÚ / SUBMENÚ / SUBMENÚ NIVEL 2
    ========================================================= */
    function eventoEliminarMenu() {
        $(document).off("click.menusAdminEliminar", ".table_eliminar").on("click.menusAdminEliminar", ".table_eliminar", function() {
            const row = $(this).closest("tr");
            const rowData = dataTableMenus.row(row).data();

            if (!rowData) {
                notificarMenu("error", "Error", "No se pudo obtener la fila seleccionada.");
                return;
            }

            const id = rowData.id;
            const type = obtenerTipoInternoDesdeTexto(rowData.type);
            const nombre = rowData.descripcion || rowData.name;

            let tipoTexto = "menú principal";

            if (type === "submenu") {
                tipoTexto = "submenú nivel 1";
            }

            if (type === "submenu1") {
                tipoTexto = "submenú nivel 2";
            }

            swal({
                title: "¿Está seguro?",
                text: "¿Desea eliminar este " + tipoTexto + ": " + nombre + "?",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        value: null,
                        visible: true
                    },
                    confirm: {
                        text: "Eliminar",
                        className: "btn-danger"
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then(function(confirm) {
                if (!confirm) {
                    return;
                }

                $.ajax({
                    url: MENU_URLS.eliminarMenu,
                    method: "POST",
                    data: {
                        id: id,
                        tipo: type
                    },
                    dataType: "json",
                    beforeSend: function() {
                        if (typeof showLoading === "function") {
                            showLoading("Eliminando el elemento...");
                        }
                    },
                    success: function(response) {
                        cerrarLoadingSiExiste();

                        if (response && response.type) {
                            notificarMenu(response.type, response.title, response.message);

                            if (response.type === "success") {
                                mostrarAdvertenciasSincronizacion(response);

                                if (isEditing && currentEditId == id && currentEditType === type) {
                                    resetForm();
                                }

                                if (dataTableMenus) {
                                    dataTableMenus.ajax.reload(null, false);
                                }
                            }

                            return;
                        }

                        notificarMenu("error", "Error", "Respuesta inválida del servidor");
                    },
                    error: function(xhr) {
                        cerrarLoadingSiExiste();

                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : "Error al eliminar el elemento";

                        console.error("Error eliminando menú:", xhr.responseText);
                        notificarMenu("error", "Error", errorMsg);
                    }
                });
            });
        });
    }

    /* =========================================================
        INICIALIZAR MÓDULO
    ========================================================= */
    function inicializarModuloMenus() {
        eventoVistaPreviaIcono();
        eventoTipoMenu();
        eventoSubmitFormularioMenu();
        eventoCancelarEdicion();
        eventoEditarMenu();
        eventoEliminarMenu();

        inicializarDataTableMenus();
        resetForm();
    }

    esperarDependenciasMenus();

})();
</script>