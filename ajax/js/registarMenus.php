<script>
$(function() {
    // Variables para controlar el estado de edición
    let isEditing = false;
    let currentEditId = null;
    let currentEditType = null;

    // Vista previa del ícono
    $("#icono_menu").on('input', function() {
        const iconClass = $(this).val().trim();
        if(iconClass) {
            $("#icono_preview").attr('class', iconClass);
        } else {
            $("#icono_preview").attr('class', 'fas fa-question');
        }
    });

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
            dataType: "json",
            success: function(response) {
                const $select = $(`#${selectId}`);
                $select.html("");
                let options = '<option value="">Seleccionar...</option>';
                response.data.forEach(item => {
                    options += `<option value="${item.id}">${item.descripcion || item.nombre}</option>`;
                });
                $select.html(options);
                $select.val(null);
                if ($select.hasClass('selectpicker')) {
                    $select.selectpicker('destroy');
                }
                $select.addClass('selectpicker').selectpicker({
                    liveSearch: true,
                    size: 10,
                    noneSelectedText: "Seleccione una opción"
                });

                if(dependencyId != null){
                    $select.val(dependencyId);
                }

                $select.selectpicker('refresh');
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
            // Actualizar label según el tipo
            $("#label_dependencia").text(tipo === "submenu" ? "Menú Principal" : "Submenú Nivel 1");
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
            { data: "descripcion" },
            { 
                data: "icon",
                render: function(data, type, row) {
                    return data ? `<i class="${data}"></i>` : '';
                }
            },
            { data: "orden" },
            { data: "dependency" },
            {
                data: "visible",
                render: function(data, type, row) {
                    if (type === 'display') {
                        var icon = data == 1
                            ? '<i class="fas fa-circle-check mr-1"></i>'
                            : '<i class="fas fa-circle-xmark mr-1"></i>';
                        var badgeClass = data == 1
                            ? 'badge badge-pill badge-success'
                            : 'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass + 
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + (data == 1 ? 'Visible' : 'Oculto') + '</span>';
                    }
                    return data;
                }
            },
            {
                defaultContent: "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>",
                orderable: false,
                className: "text-center"
            },
            {
                defaultContent: "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>",
                orderable: false,
                className: "text-center"
            }
        ],
        language: idioma_español,
        responsive: true,
        autoWidth: false
    });

    // Función para resetear el formulario
    function resetForm() {
        isEditing = false;
        currentEditId = null;
        currentEditType = null;
        
        $("#formulario_menu")[0].reset();
        $("#menu_id").val("");
        $("#tipo_menu").val("").selectpicker("refresh");
        $("#dependencia_menu").val("").selectpicker("refresh");
        $("#dependencia_menu_group").hide();
        $("#icono_preview").attr('class', 'fas fa-question');
        $("#visible_menu").prop("checked", true);
        
        $("#form_title").html("Registrar Nuevo Elemento de Menú");
        $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Registrar');
        $("#btnCancelarEdicion").hide();
    }

    // Manejar envío del formulario (registrar/actualizar)
    $("#formulario_menu").on("submit", function(e) {
        e.preventDefault();
        
        const tipo = $("#tipo_menu").val();
        const nombre = $("#nombre_menu").val();
        const descripcion = $("#descripcion_menu").val();
        const dependencia = $("#dependencia_menu").val();
        const icono = $("#icono_menu").val();
        const orden = $("#orden_menu").val();
        const visible = $("#visible_menu").is(":checked") ? 1 : 0;
        const id = $("#menu_id").val();
        
        const url = isEditing ? "<?php echo SERVERURL;?>core/editarMenu.php" : "<?php echo SERVERURL;?>core/agregarMenu.php";
        const method = "POST";
        
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
            method: method,
            data: data,
            dataType: "json",
            beforeSend: function() {
                $("#btnAccionMenu").prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...');
            },
            success: function(response) {
                $("#btnAccionMenu").prop("disabled", false);
                
                if (response && response.type) {
                    showNotify(response.type, response.title, response.message);
                    if (response.type === "success") {
                        resetForm();
                        dataTableMenus.ajax.reload(null, false);
                    }
                } else {
                    showNotify('error', 'Error', 'Respuesta inválida del servidor');
                }
                
                if (isEditing) {
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Actualizar');
                } else {
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Registrar');
                }
            },
            error: function(xhr) {
                $("#btnAccionMenu").prop("disabled", false);
                
                if (isEditing) {
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Actualizar');
                } else {
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Registrar');
                }
                
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error al procesar la solicitud';
                showNotify('error', 'Error', errorMsg);
            }
        });
    });

    // Cancelar edición
    $("#btnCancelarEdicion").on("click", function() {
        resetForm();
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
                    // Activar modo edición
                    isEditing = true;
                    currentEditId = id;
                    currentEditType = type;
                    
                    // Llenar el formulario
                    $("#menu_id").val(data.id);
                    $("#tipo_menu").val(type).selectpicker("refresh");
                    $("#nombre_menu").val(data.nombre);
                    $("#descripcion_menu").val(data.descripcion);
                    $("#icono_menu").val(data.icon);
                    $("#icono_preview").attr('class', data.icon || 'fas fa-question');
                    $("#orden_menu").val(data.orden);
                    $("#visible_menu").prop("checked", data.visible == 1);

                    // Manejar dependencias
                    if (type === "submenu" || type === "submenu1") {
                        $("#dependencia_menu_group").show();
                        $("#label_dependencia").text(type === "submenu" ? "Menú Principal" : "Submenú Nivel 1");
                        loadDependencies(type, "dependencia_menu", data.dependency);                    
                    } else {
                        $("#dependencia_menu_group").hide();
                    }
                    
                    // Cambiar interfaz a modo edición
                    $("#form_title").html("Editar Elemento de Menú");
                    $("#btnAccionMenu").html('<i class="fas fa-save mr-1"></i> Actualizar');
                    $("#btnCancelarEdicion").show();
                    
                    // Scroll al formulario
                    $('html, body').animate({
                        scrollTop: $("#div_top").offset().top - 20
                    }, 500, function() {
                        // Después de hacer scroll, enfocar el primer campo vacío
                        const emptyFields = $('input[type="text"], input[type="number"], select').filter(function() {
                            return $(this).val() === '';
                        });
                        
                        if (emptyFields.length > 0) {
                            emptyFields.first().focus();
                        } else {
                            // Si todos los campos están llenos, enfocar el primer campo
                            $("#nombre_menu").focus();
                        }
                    });
                } else {
                    showNotify('error', 'Error', 'No se pudieron cargar los datos');
                }
            },
            error: function() {
                showNotify('error', 'Error', 'Error al cargar los datos del menú');
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
        const nombre = rowData.descripcion || rowData.name;

        swal({
            title: "¿Está seguro?",
            text: "¿Desea eliminar el elemento: " + nombre + "?",
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
        }).then((confirm) => {
            if (confirm) {
                $.ajax({
                    url: "<?php echo SERVERURL;?>core/eliminarMenu.php",
                    method: "POST",
                    data: { 
                        id: id,
                        tipo: type 
                    },
                    dataType: "json",
                    beforeSend: function() {
                        showLoading("Eliminando el elemento...");
                    },
                    success: function(response) {
                        if (response && response.type) {
                            showNotify(response.type, response.title, response.message);
                            if (response.type === "success") {
                                // Si estamos editando el elemento que se eliminó, resetear el formulario
                                if (isEditing && currentEditId === id) {
                                    resetForm();
                                }
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
                            : 'Error al eliminar el elemento';
                        showNotify('error', 'Error', errorMsg);
                    }
                });
            }
        });
    });

    // Inicializar el formulario
    resetForm();
});
</script>