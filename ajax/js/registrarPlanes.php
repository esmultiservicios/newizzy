<script>
$(function() {
    // Inicializar DataTable
    const dataTablePlanes = $('#dataTablePlanes').DataTable({
        ajax: {
            url: "<?php echo SERVERURL;?>core/llenarDataTablePlanes.php",
            type: "POST",
            dataSrc: "data"
        },
        columns: [
            { data: "nombre" },
            { data: "usuarios" },
			{
				"data": "estado",
				"render": function(data, type, row) {
					const iconSize = "1.25em"; // Tamaño consistente
					if (data == 1) {
					return '<span class="status-badge status-active">' +
								`<i class="fas fa-check-circle" style="font-size: ${iconSize}"></i>` +
								'ACTIVO' +
							'</span>';
					} else {
					return '<span class="status-badge status-inactive">' +
								`<i class="fas fa-times-circle" style="font-size: ${iconSize}"></i>` +
								'INACTIVO' +
							'</span>';
					}
				}
			},			
            {
                defaultContent: "<button class='btn-btn_menu btn btn-primary table_primaryocultar'><span class='fas fa-link'></span></button>",
                orderable: false,
                className: "text-center"
            },
            {
                defaultContent: "<button class='btn_submenu btn btn-primary ocultar'><span class='fas fa-link'></span></button>",
                orderable: false,
                className: "text-center"
            },
            {
                defaultContent: "<button class='btn_submenu1 btn btn-primary ocultar'><span class='fas fa-link'></span></button>",
                orderable: false,
                className: "text-center"
            },						
            {
                defaultContent: "<button class='table_editar btn ocultar'><span class='fas fa-edit fa-lg'></span></button>",
                orderable: false,
                className: "text-center"
            },
            {
                defaultContent: "<button class='table_eliminar btn ocultar'><span class='fa fa-trash fa-lg'></span></button>",
                orderable: false,
                className: "text-center"
            }			
        ],
        language: idioma_español,
        responsive: true,
        autoWidth: false
    });

    // Registrar un nuevo plan
    $("#formulario_plan").on("submit", function(e) {
        e.preventDefault();
        const nombre = $("#nombre_plan").val();
        const usuarios = $("#usuarios_plan").val();
        const estado = $("#estado_plan").is(":checked") ? 1 : 0;

        $.ajax({
            url: "<?php echo SERVERURL;?>ajax/registrarPlanAjax.php",
            method: "POST",
            data: { 
                nombre_plan: nombre,
                usuarios_plan: usuarios,
                estado_plan: estado 
            },
            dataType: "json",
            success: function(response) {
                if (response && response.type) {
                    showNotify(response.type, response.title, response.message);
                    if (response.type === "success") {
                        $("#formulario_plan")[0].reset();
                        dataTablePlanes.ajax.reload(null, false);
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

    // Abrir modal para asignar menús y submenús
    $(document).on("click", ".btn-asignar", function() {
        const row = $(this).closest('tr');
        const rowData = dataTablePlanes.row(row).data();
        const planId = rowData.id;

        $("#plan_id").val(planId);

        // Cargar menús disponibles
        loadDependencies("menu", "menu_asignado");

        $("#modalAsignarMenus").modal("show");
    });

    // Cargar submenús de nivel 1 cuando se selecciona un menú
    $("#menu_asignado").on("change", function() {
        const menuId = $(this).val();
        if (menuId) {
            loadDependencies("submenu", "submenu_asignado", menuId);
            $("#submenu_group").show();
        } else {
            $("#submenu_group").hide();
        }
    });

    // Cargar submenús de nivel 2 cuando se selecciona un submenú de nivel 1
    $("#submenu_asignado").on("change", function() {
        const submenuId = $(this).val();
        if (submenuId) {
            loadDependencies("submenu1", "submenu1_asignado", submenuId);
            $("#submenu1_group").show();
        } else {
            $("#submenu1_group").hide();
        }
    });

    // Guardar asignaciones de menús y submenús
    $("#btnGuardarAsignacion").on("click", function() {
        const planId = $("#plan_id").val();
        const menuId = $("#menu_asignado").val();
        const submenuId = $("#submenu_asignado").val();
        const submenu1Id = $("#submenu1_asignado").val();

        let asignaciones = [];
        if (menuId) asignaciones.push({ tipo: "menu", id: menuId });
        if (submenuId) asignaciones.push({ tipo: "submenu", id: submenuId });
        if (submenu1Id) asignaciones.push({ tipo: "submenu1", id: submenu1Id });

        if (asignaciones.length === 0) {
            showNotify('warning', 'Advertencia', 'Debe seleccionar al menos un menú o submenú');
            return;
        }

        $.ajax({
            url: "<?php echo SERVERURL;?>ajax/asignarMenuAjax.php",
            method: "POST",
            data: { 
                plan_id: planId,
                asignaciones: JSON.stringify(asignaciones)
            },
            dataType: "json",
            success: function(response) {
                if (response && response.type) {
                    showNotify(response.type, response.title, response.message);
                    if (response.type === "success") {
                        $("#modalAsignarMenus").modal("hide");
                        dataTablePlanes.ajax.reload(null, false);
                    }
                } else {
                    showNotify('error', 'Error', 'Respuesta inválida del servidor');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error al guardar las asignaciones';
                showNotify('error', 'Error', errorMsg);
            }
        });
    });

    // Función para cargar dependencias (menús, submenús)
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
                let options = '';
                response.data.forEach(item => {
                    options += `<option value="${item.id}">${item.nombre}</option>`;
                });
                $select.html(options);
                $select.val(null).selectpicker("refresh");
            },
            error: function() {
                showNotify('error', 'Error', 'No se pudieron cargar las dependencias');
            }
        });
    }
});

$("#modalRegistarPlanes").on('shown.bs.modal', function() {
    $(this).find('#formularioRegstrarPlanes #menu_asignado').focus();
});

document.addEventListener("DOMContentLoaded", function () {
    // Obtener referencias al checkbox y al label
    const estadoCheckbox = document.getElementById("estado_plan");
    const estadoLabel = document.getElementById("estado_label");

    // Agregar un evento change al checkbox
    estadoCheckbox.addEventListener("change", function () {
        if (estadoCheckbox.checked) {
            estadoLabel.textContent = "Activo"; // Si está marcado, mostrar "Activo"
        } else {
            estadoLabel.textContent = "Inactivo"; // Si no está marcado, mostrar "Inactivo"
        }
    });

    // Inicializar el texto del label según el estado inicial del checkbox
    if (estadoCheckbox.checked) {
        estadoLabel.textContent = "Activo";
    } else {
        estadoLabel.textContent = "Inactivo";
    }
});
</script>