<script>
$(function() {
    // Función para cargar dependencias (menús o submenús)
    function loadDependencies(type, selectId, dependencyId = null) {
        let data = {
            tipo: type === "submenu" ? "getMenus" : 
                type === "submenu1" ? "getAllSubmenus" : "getSubmenusByMenu"
        };
        if (type === "submenu1" && dependencyId) {
            data.menu_id = dependencyId;
        }
        $.ajax({
            url: "<?php echo SERVERURL;?>core/getDependenciesMenu.php",
            method: "POST",
            data: data,
            dataType: "json", // Indica que esperamos un JSON como respuesta
            success: function(response) {
                console.log("Datos recibidos:", response);
                const $select = $(`#${selectId}`);
                // Limpia el contenido actual
                $select.html("");
                // Genera las opciones a partir del JSON
                let options = '';
                response.data.forEach(item => {
                    options += `<option value="${item.id}">${item.nombre}</option>`;
                });
                $select.html(options);
                // Deselecciona todas las opciones
                $select.val(null);
                // Refresca el plugin selectpicker
                if ($select.hasClass('selectpicker')) {
                    $select.selectpicker('destroy'); // Destruye el plugin existente
                }
                $select.addClass('selectpicker').selectpicker({
                    liveSearch: true,
                    size: 10,
                    noneSelectedText: "Seleccione una opción" // Texto personalizado
                });
                // Fuerza redibujado (por si acaso)
                setTimeout(() => {
                    $select.selectpicker('refresh');
                }, 100);
            },
            error: function() {
                showNotify('error', 'Error', 'No se pudieron cargar las dependencias');
            }
        });
    }

    // Mostrar/Ocultar campo de dependencia según el tipo de elemento
    $("#tipo_menu").on("change", function() {
        const tipo = $(this).val();
        const dependenciaGroup = $("#dependencia_menu_group");
        if (tipo === "submenu" || tipo === "submenu1") {
            dependenciaGroup.show();
            loadDependencies(tipo, "dependencia_menu");
        } else {
            dependenciaGroup.hide();
        }
    });

    // Inicializar DataTable
    const dataTableMenus = $('#dataTableMenus').DataTable({
        ajax: {
            url: "<?php echo SERVERURL;?>core/llenarDataTableMenus.php",
            type: "POST",
            dataSrc: "data"
        },
        columns: [
            { data: "type" },
            { data: "name" },
            { data: "dependency" },
            {
                defaultContent: "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>",
                orderable: false,
                className: "text-center"
            },
            {
                defaultContent: "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>",
                orderable: false,
                className: "text-center"
            }
        ],
        language: idioma_español,
        responsive: true,
        autoWidth: false
    });

    // Registrar un nuevo elemento
    $("#formulario_menu").on("submit", function(e) {
        e.preventDefault();
        const tipo = $("#tipo_menu").val();
        const nombre = $("#nombre_menu").val();
        const dependencia = $("#dependencia_menu").val();

        $.ajax({
            url: "<?php echo SERVERURL;?>ajax/agregarMenuAjax.php",
            method: "POST",
            data: { 
                tipo: tipo,
                nombre: nombre,
                dependencia: dependencia 
            },
            dataType: "json",
            beforeSend: function() {
                // Opcional: Mostrar loader
            },
            success: function(response) {
                if (response && response.type) {
                    showNotify(response.type, response.title, response.message);
                    if (response.type === "success") {
                        // Restablecer el formulario
                        $("#formulario_menu")[0].reset();

                        // Reinicializar los selectores con selectpicker
                        $("#tipo_menu").val(null).selectpicker("refresh");
                        $("#dependencia_menu").val(null).selectpicker("refresh");

                        // Ocultar el grupo de dependencias
                        $("#dependencia_menu_group").hide();

                        // Recargar la tabla
                        dataTableMenus.ajax.reload(null, false);
                    }
                } else {
                    showNotify('error', 'Error', 'Respuesta inválida del servidor');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error al procesar la solicitud';
                showNotify('error', 'Error', errorMsg);
            }
        });
    });

    // Editar un elemento existente
    $(document).on("click", ".table_editar", function() {
        const row = $(this).closest('tr');
        const rowData = dataTableMenus.row(row).data();
        const id = rowData.id;
        const type = rowData.type.includes("Nivel 1") ? "submenu" : 
                   rowData.type.includes("Nivel 2") ? "submenu1" : "menu";

        $.ajax({
            url: "<?php echo SERVERURL;?>core/getMenuById.php",
            method: "POST",
            data: { 
                id: id,
                tipo: type 
            },
            dataType: "json",
            success: function(data) {
                if (data) {
                    $("#edit_id").val(data.id);
                    $("#edit_tipo").val(type);
                    $("#edit_nombre").val(data.nombre);

                    if (type === "submenu" || type === "submenu1") {
                        $("#edit_dependencia_group").show();
                        loadDependencies(type, "edit_dependencia", data.dependency);
                    } else {
                        $("#edit_dependencia_group").hide();
                    }

                    $("#modalEditarMenu").modal("show");
                } else {
                    showNotify('error', 'Error', 'No se pudieron cargar los datos');
                }
            },
            error: function() {
                showNotify('error', 'Error', 'Error al cargar los datos del menú');
            }
        });
    });

    // Guardar cambios en edición
    $("#btnGuardarCambios").on("click", function() {
        const id = $("#edit_id").val();
        const type = $("#edit_tipo").val();
        const nombre = $("#edit_nombre").val();
        const dependencia = $("#edit_dependencia").val();

        $.ajax({
            url: "<?php echo SERVERURL;?>ajax/editarMenuAjax.php",
            method: "POST",
            data: { 
                id: id,
                tipo: type,
                nombre: nombre,
                dependencia: dependencia 
            },
            dataType: "json",
            beforeSend: function() {
                // Opcional: Mostrar loader
            },
            success: function(response) {
                if (response && response.type) {
                    showNotify(response.type, response.title, response.message);
                    if (response.type === "success") {
                        // Ocultar el modal
                        $("#modalEditarMenu").modal("hide");

                        // Restablecer los campos del formulario de edición
                        $("#edit_id").val("");
                        $("#edit_tipo").val(null).selectpicker("refresh");
                        $("#edit_nombre").val("");
                        $("#edit_dependencia").val(null).selectpicker("refresh");

                        // Ocultar el grupo de dependencias
                        $("#edit_dependencia_group").hide();

                        // Recargar la tabla
                        dataTableMenus.ajax.reload(null, false);
                    }
                } else {
                    showNotify('error', 'Error', 'Respuesta inválida del servidor');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error al guardar los cambios';
                showNotify('error', 'Error', errorMsg);
            }
        });
    });

    // Eliminar un elemento con SweetAlert
    $(document).on("click", ".table_eliminar", function() {
        const row = $(this).closest('tr');
        const rowData = dataTableMenus.row(row).data();
        const id = rowData.id;
        const type = rowData.type.includes("Nivel 1") ? "submenu" : 
                     rowData.type.includes("Nivel 2") ? "submenu1" : "menu";
        const nombre = rowData.name;

        swal({
            title: "¿Está seguro?",
            text: "¿Desea eliminar el elemento: " + nombre + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-secondary"
                },
                confirm: {
                    text: "Eliminar",
                    className: "btn-danger"
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirm) => {
            if (confirm) {
                $.ajax({
                    url: "<?php echo SERVERURL;?>ajax/eliminarMenuAjax.php",
                    method: "POST",
                    data: { 
                        id: id,
                        tipo: type 
                    },
                    dataType: "json",
                    beforeSend: function() {
                        swal({
                            title: "Procesando",
                            text: "Eliminando el elemento...",
                            icon: "info",
                            buttons: false,
                            closeOnClickOutside: false,
                            closeOnEsc: false
                        });
                    },
                    success: function(response) {
                        swal.close();
                        if (response && response.type) {
                            showNotify(response.type, response.title, response.message);
                            if (response.type === "success") {
                                // Restablecer el formulario principal
                                $("#formulario_menu")[0].reset();

                                // Reinicializar los selectores con selectpicker
                                $("#tipo_menu").val(null).selectpicker("refresh");
                                $("#dependencia_menu").val(null).selectpicker("refresh");

                                // Ocultar el grupo de dependencias
                                $("#dependencia_menu_group").hide();

                                // Recargar la tabla
                                dataTableMenus.ajax.reload(null, false);
                            }
                        } else {
                            showNotify('error', 'Error', 'Respuesta inválida del servidor');
                        }
                    },
                    error: function(xhr) {
                        swal.close();
                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : 'Error al eliminar el elemento';
                        showNotify('error', 'Error', errorMsg);
                    }
                });
            }
        });
    });
});

$("#modalEditarMenu").on('shown.bs.modal', function() {
    $(this).find('#formulario_editar_menu #edit_nombre').focus();
});
</script>