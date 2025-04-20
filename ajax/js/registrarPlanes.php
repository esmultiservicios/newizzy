<script>
document.addEventListener("DOMContentLoaded", function() {
    // Definir las opciones para los selects
    const opcionesConfiguracion = [
        {value: 'usuarios', text: 'Usuarios'},
        {value: 'clientes', text: 'Clientes'},
        {value: 'proveedores', text: 'Proveedores'},
        {value: 'productos', text: 'Productos'},
        {value: 'facturas', text: 'Facturas'},
        {value: 'perfiles', text: 'Puntos de Venta'},
        {value: 'almacenes', text: 'Almacenes'},
        {value: 'categorias', text: 'Categorías'}
    ];

    // 1. Manejo del estado del plan
    const estadoCheckbox = document.getElementById("estado_plan");
    const estadoLabel = document.getElementById("estado_label");

    function updateEstadoLabel() {
        estadoLabel.textContent = estadoCheckbox.checked ? "Activo" : "Inactivo";
        estadoLabel.className = estadoCheckbox.checked 
            ? "font-weight-bold text-success mb-0" 
            : "font-weight-bold text-danger mb-0";
    }

    estadoCheckbox.addEventListener("change", updateEstadoLabel);
    updateEstadoLabel();

    // 2. Configuraciones dinámicas
    const agregarBtn = document.getElementById("agregar-configuracion");
    const container = document.getElementById("configuraciones-container");

    function agregarConfiguracion(conFoco = false, configuracion = null) {
        // Generar las opciones del select
        let opcionesHTML = '';
        opcionesConfiguracion.forEach(opcion => {
            const selected = configuracion && configuracion.clave === opcion.value ? 'selected' : '';
            opcionesHTML += `<option value="${opcion.value}" ${selected}>${opcion.text}</option>`;
        });

        const newItem = `
            <div class="input-group mb-3 configuracion-item">
                <select class="form-control selectpicker mr-2" name="configuracion_clave[]" data-live-search="true" title="Seleccione una opción">
                    ${opcionesHTML}
                </select>
                <input type="number" class="form-control mr-2" name="configuracion_valor[]" placeholder="Cantidad" min="0" 
                    ${configuracion ? `value="${configuracion.valor}"` : ''}>
                <div class="input-group-append">
                    <button class="btn btn-danger remover-configuracion" type="button">
                        <i class="fas fa-times fa-lg"></i> Quitar
                    </button>
                </div>
            </div>`;
        
        container.insertAdjacentHTML("beforeend", newItem);
        
        // Inicializar el selectpicker de Bootstrap
        $('.selectpicker').selectpicker('refresh');
        
        // Solo enfocar si se solicita explícitamente
        if (conFoco) {
            const selects = container.querySelectorAll('.configuracion-item:last-child select[name="configuracion_clave[]"]');
            if (selects.length > 0) {
                $(selects[0]).selectpicker('focus');
            }
        }
    }

    agregarBtn.addEventListener("click", function() {
        agregarConfiguracion(true);
    });

    container.addEventListener("click", function(e) {
        if (e.target.closest(".remover-configuracion")) {
            e.target.closest(".configuracion-item").remove();
        }
    });

    // 3. DataTable y funciones AJAX
    const dataTablePlanes = $('#dataTablePlanes').DataTable({
        ajax: {
            url: "<?php echo SERVERURL;?>core/llenarDataTablePlanes.php",
            type: "POST",
            dataSrc: "data",
            error: function(xhr) {
                console.error("Error al cargar datos:", xhr.responseText);
                showNotify("error", "Error", "Error al cargar los datos de planes");
            }
        },
        columns: [
            { 
                data: "planes_id" 
            },            
            { 
                data: "nombre" 
            },
            { 
                data: "configuraciones",
                render: function(data, type, row) {
                    if (!data || data === "Sin configuraciones") {
                        return data;
                    }
                    
                    if (data.includes("<ul")) {
                        const configs = $(data).find("li");
                        if (configs.length > 0) {
                            const first = configs.first().text();
                            const extras = configs.length - 1;
                            
                            return `
                                <div>${first}</div>
                                ${extras > 0 ? `<small class="text-muted">+${extras} más</small>` : ''}
                                <button class="btn btn-sm btn-info btn-ver-configs mt-1" 
                                    data-configs='${JSON.stringify(row.configuraciones_json)}'>
                                    <i class="fas fa-eye"></i> Ver todas
                                </button>
                            `;
                        }
                    }
                    return data;
                }
            },
            {
                data: "estado",
                render: function(data) {
                    const iconSize = "1.25em";
                    return data == 1 
                        ? `<span class="status-badge status-active">
                            <i class="fas fa-check-circle" style="font-size: ${iconSize}"></i>ACTIVO</span>`
                        : `<span class="status-badge status-inactive">
                            <i class="fas fa-times-circle" style="font-size: ${iconSize}"></i>INACTIVO</span>`;
                }
            },
            {
                data: null,
                render: (data, type, row) => {
                    const count = row.menus_asignados || 0;
                    return `
                        <button class="btn btn-sm btn-primary btn-asignar-menu" 
                                data-plan-id="${row.planes_id}" 
                                data-plan-nombre="${row.nombre}">
                            <i class="fas fa-link"></i> Asignar
                        </button>
                        <div class="mt-1 small" id="contador-menus-${row.planes_id}">${count} asignados</div>
                    `;
                }
            },
            {
                data: null,
                render: (data, type, row) => {
                    const count = row.submenus_asignados || 0;
                    return `
                        <button class="btn btn-sm btn-primary btn-asignar-submenu" 
                                data-plan-id="${row.planes_id}" 
                                data-plan-nombre="${row.nombre}">
                            <i class="fas fa-link"></i> Asignar
                        </button>
                        <div class="mt-1 small" id="contador-submenus-${row.planes_id}">${count} asignados</div>
                    `;
                }
            },
            {
                data: null,
                render: (data, type, row) => {
                    const count = row.submenus2_asignados || 0;
                    return `
                        <button class="btn btn-sm btn-primary btn-asignar-submenu2" 
                                data-plan-id="${row.planes_id}" 
                                data-plan-nombre="${row.nombre}">
                            <i class="fas fa-link"></i> Asignar
                        </button>
                        <div class="mt-1 small" id="contador-submenus2-${row.planes_id}">${count} asignados</div>
                    `;
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="table_editar btn btn-dark ocultar btn-editar" data-id="${data.planes_id}">
                                <i class="fas fa-edit"></i>Editar
                            </button>
                            <button class="table_eliminar btn btn-dark ocultar btn-eliminar" data-id="${data.planes_id}">
                                <i class="fas fa-trash"></i>Eliminar
                            </button>
                        </div>
                    `;
                }
            }
        ],
        "language": idioma_español,
        responsive: true,
        columnDefs: [
            { 
                responsivePriority: 1, targets: 0 
            },
            { 
                responsivePriority: 2, targets: -1 
            },
            { 
                responsivePriority: 3, targets: 2 
            }
        ]
    });

    // Función mejorada para listar configuraciones con botón de eliminar
    var listar_configuraciones = function(plan_id, configuraciones){
        // Verifica si ya hay un DataTable activo y lo destruye
        if ($.fn.DataTable.isDataTable('#tablaConfiguraciones')) {
            $('#tablaConfiguraciones').DataTable().clear().destroy();
        }

        // Preparamos los datos para el DataTable
        let dataSet = [];

        if (configuraciones && typeof configuraciones === "object" && Object.keys(configuraciones).length > 0) {
            let index = 1;
            for (const [clave, valor] of Object.entries(configuraciones)) {
                // Encontrar el texto correspondiente al valor
                const opcion = opcionesConfiguracion.find(op => op.value === clave);
                const texto = opcion ? opcion.text : clave;
                
                dataSet.push({ 
                    "id": index,
                    "clave": clave,
                    "config": texto, 
                    "valor": valor,
                    "acciones": `
                        <button class="btn btn-sm btn-danger btn-eliminar-config" 
                                data-clave="${clave}" 
                                data-plan-id="${plan_id}">
                            <i class="fas fa-times fa-lg"></i> Quitar
                        </button>
                    `
                });
                index++;
            }
        } else {
            dataSet.push({ 
                "id": 1,
                "clave": "",
                "config": "Sin configuraciones", 
                "valor": "-",
                "acciones": ""
            });
        }

        // Inicializar como DataTable
        const table = $('#tablaConfiguraciones').DataTable({
            data: dataSet,
            columns: [
                { data: "id", title: "#", width: "5%" },
                { data: "config", title: "Configuración" },
                { data: "valor", title: "Cantidad" },
                { data: "acciones", title: "Acciones", width: "15%" }
            ],
            language: idioma_español,
            paging: false,
            searching: false,
            info: false,
            responsive: true
        });

        // Guardar el ID del plan en el modal para usarlo luego
        $('#modalConfiguraciones').data('plan-id', plan_id);

        // Mostrar modal con configuración para evitar cierre accidental
        $("#modalConfiguraciones").modal({
            show: true,
            keyboard: false,    // Desactiva el cierre con la tecla ESC
            backdrop: 'static'  // Desactiva el cierre al hacer clic fuera
        });
    };

    // Evento para eliminar configuración
    $(document).on("click", ".btn-eliminar-config", function() {
        const clave = $(this).data('clave');
        const planId = $(this).data('plan-id');
        const button = $(this);
        
        swal({
            title: "¿Estás seguro?",
            text: `¡Se eliminará la configuración "${clave}" del plan!`,
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "Sí, eliminar",
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((willConfirm) => {
            if (willConfirm === true) {
                $.ajax({
                    url: "<?php echo SERVERURL;?>core/eliminarConfiguracionPlan.php",
                    type: "POST",
                    data: { 
                        plan_id: planId,
                        clave: clave
                    },
                    dataType: "json",
                    beforeSend: function() {
                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar la tabla de configuraciones
                            const configs = response.configuraciones || {};
                            const table = $('#tablaConfiguraciones').DataTable();
                            
                            if (Object.keys(configs).length === 0) {
                                table.clear().draw();
                                table.row.add({
                                    "id": 1,
                                    "clave": "",
                                    "config": "Sin configuraciones", 
                                    "valor": "-",
                                    "acciones": ""
                                }).draw();
                            } else {
                                // Reconstruir los datos
                                let newData = [];
                                let index = 1;
                                for (const [key, val] of Object.entries(configs)) {
                                    const opcion = opcionesConfiguracion.find(op => op.value === key);
                                    const texto = opcion ? opcion.text : key;
                                    
                                    newData.push({ 
                                        "id": index,
                                        "clave": key,
                                        "config": texto, 
                                        "valor": val,
                                        "acciones": `
                                            <button class="btn btn-sm btn-danger btn-eliminar-config" 
                                                    data-clave="${key}" 
                                                    data-plan-id="${planId}">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        `
                                    });
                                    index++;
                                }
                                
                                table.clear().rows.add(newData).draw();
                            }
                            
                            // Actualizar el DataTable principal
                            dataTablePlanes.ajax.reload(null, false);
                            showNotify("success", "Éxito", response.message);
                        } else {
                            showNotify("error", "Error", response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error("Error al eliminar:", xhr.responseText);
                        showNotify("error", "Error", "Error de conexión al eliminar configuración");
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<i class="fas fa-trash"></i> Eliminar');
                    }
                });
            }
        });
    });

    // 4. Mostrar configuraciones en modal
    $(document).on("click", ".btn-ver-configs", function() {
        const configs = $(this).data("configs");
        const planId = $(this).closest('tr').find('.btn-editar').data('id') || null;
        
        // Obtener nombre del plan desde DataTable
        const planNombre = dataTablePlanes.row($(this).closest('tr')).data().nombre || "Sin nombre";

        // Establecer el título del modal dinámicamente
        $('#modalConfiguraciones .modal-title').text(`Configuraciones del Plan: ${planNombre}`);

        listar_configuraciones(planId, configs);
    });


    // 5. Cargar datos al editar
    $(document).on("click", ".btn-editar", function() {
        const planId = $(this).data("id");
        
        $.ajax({
            url: "<?php echo SERVERURL;?>core/obtenerPlan.php",
            type: "POST",
            data: { 
                plan_id: planId 
            },
            dataType: "json",
            beforeSend: function() {
                $("#btn-submit").prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...');
            },
            success: function(response) {
                if (response.success) {
                    $("#form-title").text("Editar Plan");
                    $("#nombre_plan").val(response.data.nombre);
                    $("#usuarios_plan").val(response.data.usuarios);
                    $("#estado_plan").prop("checked", response.data.estado == 1);
                    $("#plan_id").val(response.data.planes_id);
                    
                    updateEstadoLabel();

                    // Cargar configuraciones
                    const container = $("#configuraciones-container");
                    container.empty();
                    
                    try {
                        const configs = response.data.configuraciones_json || {};
                        
                        if (Object.keys(configs).length > 0) {
                            for (const [clave, valor] of Object.entries(configs)) {
                                agregarConfiguracion(false, {clave: clave, valor: valor});
                            }
                        }
                    } catch (e) {
                        console.error("Error parsing configs:", e);
                        showNotify("error", "Error", "Error al cargar las configuraciones");
                    }

                    $("#btn-submit").html('<i class="fas fa-sync fa-lg mr-1"></i> Actualizar Plan').prop("disabled", false);
                    $("#btn-cancelar-edicion").show();
                    $("#nombre_plan").focus();
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar el plan");
                    $("#btn-submit").prop("disabled", false).html('<i class="fas fa-save mr-1"></i> Registrar Plan');
                }
            },
            error: function(xhr) {
                console.error("Error en la solicitud:", xhr.responseText);
                showNotify("error", "Error", "Error de conexión al cargar el plan");
                $("#btn-submit").prop("disabled", false).html('<i class="fas fa-save mr-1"></i> Registrar Plan');
            }
        });
    });

    // 6. Cancelar edición
    $("#btn-cancelar-edicion").click(function() {
        resetFormulario();
    });

    // 7. Enviar formulario (crear/actualizar)
    $("#formulario_plan").submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const configs = {};
        let hasEmptyConfigs = false;
        
        $(".configuracion-item").each(function() {
            const clave = $(this).find("select[name='configuracion_clave[]']").val();
            const valor = $(this).find("input[name='configuracion_valor[]']").val().trim();
            
            if (!clave) {
                hasEmptyConfigs = true;
                $(this).find("select[name='configuracion_clave[]']").addClass("is-invalid");
            } else {
                $(this).find("select[name='configuracion_clave[]']").removeClass("is-invalid");
                configs[clave] = valor;
            }
        });    
        
        formData.append("configuraciones_json", JSON.stringify(configs));
        
        const url = formData.get("plan_id") 
            ? "<?php echo SERVERURL;?>core/actualizarPlan.php" 
            : "<?php echo SERVERURL;?>core/registrarPlan.php";
        
        $("#btn-submit").prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.type === "success") {
                    resetFormulario();
                    
                    dataTablePlanes.ajax.reload(null, false);
                    showNotify(response.type, response.title, response.message);
                } else {
                    showNotify(response.type, response.title, response.message || "Error desconocido");
                }
                $("#btn-submit").prop("disabled", false).html(
                    formData.get("plan_id") 
                        ? '<i class="fas fa-sync mr-1"></i> Actualizar Plan' 
                        : '<i class="fas fa-save mr-1"></i> Registrar Plan'
                );
            },
            error: function(xhr) {
                console.error("Error en la solicitud:", xhr.responseText);
                showNotify("error", "Error", "Error de conexión al procesar el plan");
                $("#btn-submit").prop("disabled", false).html(
                    formData.get("plan_id") 
                        ? '<i class="fas fa-sync mr-1"></i> Actualizar Plan' 
                        : '<i class="fas fa-save mr-1"></i> Registrar Plan'
                );
            }
        });
    });

    // 8. Eliminar plan
    $(document).on("click", ".btn-eliminar", function() {
        const planId = $(this).data("id");
        
        swal({
            title: "¿Estás seguro?",
            text: "¡No podrás revertir esto!",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "Sí, eliminar",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        }).then((willConfirm) => {
            if (willConfirm === true) {
                $.ajax({
                    url: "<?php echo SERVERURL;?>ajax/eliminarPlan.php",
                    type: "POST",
                    data: { plan_id: planId },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            dataTablePlanes.ajax.reload();
                            showNotify(response.type, "Éxito", response.message);
                        } else {
                            showNotify("error", "Error", response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error("Error al eliminar:", xhr.responseText);
                        showNotify("error", "Error", "Error al eliminar el plan");
                    }
                });
            }
        });
    });

    // 9. Función para resetear formulario
    function resetFormulario() {
        $("#form-title").text("Registrar Nuevo Plan");
        $("#formulario_plan")[0].reset();
        $("#plan_id").val("");
        $("#configuraciones-container").empty();
        $("#btn-submit").html('<i class="fas fa-save mr-1"></i> Registrar Plan');
        $("#btn-cancelar-edicion").hide();
        $("#nombre_plan").focus();
        
        // Agregar una configuración vacía y refrescar los selects
        agregarConfiguracion(false);
        $('.selectpicker').selectpicker('refresh');
    }

    // Inicializar con una configuración vacía al cargar la página pero sin enfocar
    agregarConfiguracion(false);
    // Enfocar el campo nombre_plan después de un pequeño retraso para asegurar que el DOM esté listo
    setTimeout(() => {
        document.getElementById("nombre_plan").focus();
    }, 100);

    // Función mejorada para cargar menús principales
    var listar_menus_asignacion = function(plan_id) {
        var table = $("#tablaMenus").DataTable({
            "destroy": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL; ?>core/obtenerMenusPlanDisponibles.php",
                "data": {
                    "plan_id": plan_id
                },
                "dataSrc": function(json) {
                    if (!json.success) {
                        console.error(json.message);
                        showNotify('error', 'Error', 'Error al cargar los menús');
                        return [];
                    }
                    
                    let contador = json.data.filter(d => d.asignado).length;
                    $(`#contador-menus-${plan_id}`).text(`${contador} asignados`);
                    
                    return json.data.map((menu, index) => {
                        return {
                            "#": index + 1,
                            "name": menu.name,
                            "asignado": menu.asignado ? 
                                '<span class="badge badge-success">Asignado</span>' : 
                                '<span class="badge badge-secondary">No asignado</span>',
                            "acciones": `
                                <button class="btn btn-sm ${menu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-menu" 
                                    data-menu-id="${menu.menu_id}" 
                                    data-asignado="${menu.asignado}">
                                    ${menu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                </button>
                            `
                        };
                    });
                }
            },
            "columns": [
                { "data": "#" },
                { "data": "name" },
                { "data": "asignado" },
                { "data": "acciones" }
            ],
            "lengthMenu": lengthMenu10,
            "stateSave": true,
            "language": idioma_español,
            "dom": dom,
            "buttons": []
        });
    };

    // Función mejorada para cargar submenús nivel 1
    var listar_submenus_asignacion = function(plan_id) {
        var table = $("#tablaSubmenus").DataTable({
            "destroy": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL; ?>core/obtenerSubmenusPlanDisponibles.php",
                "data": {
                    "plan_id": plan_id
                },
                "dataSrc": function(json) {
                    if (!json.success) {
                        console.error(json.message);
                        showNotify('error', 'Error', 'Error al cargar los submenús');
                        return [];
                    }
                    
                    let contador = json.data.filter(d => d.asignado).length;
                    $(`#contador-submenus-${plan_id}`).text(`${contador} asignados`);
                    
                    return json.data.map((submenu, index) => {
                        return {
                            "#": index + 1,
                            "menu_name": submenu.descripcion_padre,
                            "name": submenu.descripcion,
                            "asignado": submenu.asignado ? 
                                '<span class="badge badge-success">Asignado</span>' : 
                                '<span class="badge badge-secondary">No asignado</span>',
                            "acciones": `
                                <button class="btn btn-sm ${submenu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenu" 
                                    data-submenu-id="${submenu.submenu_id}" 
                                    data-asignado="${submenu.asignado}">
                                    ${submenu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                </button>
                            `
                        };
                    });
                }
            },
            "columns": [
                { "data": "#" },
                { "data": "menu_name" },
                { "data": "name" },
                { "data": "asignado" },
                { "data": "acciones" }
            ],
            "lengthMenu": lengthMenu10,
            "stateSave": true,
            "language": idioma_español,
            "dom": dom,
            "buttons": []
        });
    };

    // Función mejorada para cargar submenús nivel 2
    var listar_submenus2_asignacion = function(plan_id) {
        var table = $("#tablaSubmenus2").DataTable({
            "destroy": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL; ?>core/obtenerSubmenus2PlanDisponibles.php",
                "data": {
                    "plan_id": plan_id
                },
                "dataSrc": function(json) {
                    if (!json.success) {
                        console.error(json.message);
                        showNotify('error', 'Error', 'Error al cargar los submenús nivel 2');
                        return [];
                    }
                    
                    let contador = json.data.filter(d => d.asignado).length;
                    $(`#contador-submenus2-${plan_id}`).text(`${contador} asignados`);
                    
                    return json.data.map((s2, index) => {
                        return {
                            "#": index + 1,
                            "menu_name": s2.descripcion_padre,
                            "submenu_name": s2.descripcion,
                            "name": s2.descripcion_menu,
                            "asignado": s2.asignado ? 
                                '<span class="badge badge-success">Asignado</span>' : 
                                '<span class="badge badge-secondary">No asignado</span>',
                            "acciones": `
                                <button class="btn btn-sm ${s2.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenu2" 
                                    data-submenu2-id="${s2.submenu1_id}" 
                                    data-asignado="${s2.asignado}">
                                    ${s2.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                </button>
                            `
                        };
                    });
                }
            },
            "columns": [
                { "data": "#" },
                { "data": "menu_name" },
                { "data": "submenu_name" },
                { "data": "name" },
                { "data": "asignado" },
                { "data": "acciones" }
            ],
            "lengthMenu": lengthMenu10,
            "stateSave": true,
            "language": idioma_español,
            "dom": dom,
            "buttons": []
        });
    };

    // Evento para asignar/quitar menús principales
    $(document).on('click', '.btn-toggle-menu', function() {
        const button = $(this);
        const menuId = button.data('menu-id');
        const asignado = button.data('asignado');
        const planId = $('#plan_id_menus').val();
        const nuevoEstado = asignado ? 0 : 1;
        
        $.ajax({
            url: '<?php echo SERVERURL;?>core/asignarMenuPlan.php',
            type: 'POST',
            data: { 
                plan_id: planId, 
                menu_id: menuId,
                estado: nuevoEstado
            },
            dataType: 'json',
            beforeSend: function() {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            },
            success: function(response) {
                if (response.estado) {
                    // Actualización suave sin recargar toda la tabla
                    button.data('asignado', !asignado);
                    button.toggleClass('btn-success btn-danger');
                    button.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
                    
                    // Actualizar el badge
                    const badge = button.closest('tr').find('span.badge');
                    badge.toggleClass('badge-success badge-secondary');
                    badge.text(asignado ? 'No asignado' : 'Asignado');
                    
                    // Actualizar contador
                    const currentCount = parseInt($(`#contador-menus-${planId}`).text());
                    $(`#contador-menus-${planId}`).text(asignado ? currentCount - 1 : currentCount + 1);
                    
                    // Actualizar DataTable principal sin recarga completa
                    const row = dataTablePlanes.row($(`.btn-asignar-menu[data-plan-id="${planId}"]`).closest('tr'));
                    const rowData = row.data();
                    
                    if (rowData) {
                        rowData.menus_asignados = asignado ? rowData.menus_asignados - 1 : rowData.menus_asignados + 1;
                        row.data(rowData).draw(false); // false para evitar resetear paginación
                    }
                    
                    showNotify(response.type, response.title, response.message);
                } else {
                    showNotify(response.type, response.title, response.message || 'Error al actualizar menú');
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", 'Error de conexión al actualizar menú');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Evento para asignar/quitar submenús nivel 1
    $(document).on('click', '.btn-toggle-submenu', function() {
        const button = $(this);
        const submenuId = button.data('submenu-id');
        const asignado = button.data('asignado');
        const planId = $('#plan_id_submenus').val();
        const nuevoEstado = asignado ? 0 : 1;
        
        $.ajax({
            url: '<?php echo SERVERURL;?>core/asignarSubmenuPlan.php',
            type: 'POST',
            data: { 
                plan_id: planId, 
                submenu_id: submenuId,
                estado: nuevoEstado 
            },
            dataType: 'json',
            beforeSend: function() {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            },
            success: function(response) {
                if (response.estado) {
                    // Actualización suave
                    button.data('asignado', !asignado);
                    button.toggleClass('btn-success btn-danger');
                    button.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
                    
                    const badge = button.closest('tr').find('span.badge');
                    badge.toggleClass('badge-success badge-secondary');
                    badge.text(asignado ? 'No asignado' : 'Asignado');
                    
                    // Actualizar contador
                    const currentCount = parseInt($(`#contador-submenus-${planId}`).text());
                    $(`#contador-submenus-${planId}`).text(asignado ? currentCount - 1 : currentCount + 1);
                    
                    // Actualizar DataTable principal sin recarga completa
                    const row = dataTablePlanes.row($(`.btn-asignar-submenu[data-plan-id="${planId}"]`).closest('tr'));
                    const rowData = row.data();
                    
                    if (rowData) {
                        rowData.submenus_asignados = asignado ? rowData.submenus_asignados - 1 : rowData.submenus_asignados + 1;
                        row.data(rowData).draw(false);
                    }
                    
                    showNotify(response.type, response.title, response.message);
                } else {
                    showNotify(response.type, response.title, response.message || 'Error al actualizar submenú');
                }
            },
            error: function(xhr) {
                showNotify('error', 'Error', 'Error de conexión al actualizar submenú');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Evento para asignar/quitar submenús nivel 2
    $(document).on('click', '.btn-toggle-submenu2', function() {
        const button = $(this);
        const submenu2Id = button.data('submenu2-id');
        const asignado = button.data('asignado');
        const planId = $('#plan_id_submenus2').val();
        const nuevoEstado = asignado ? 0 : 1;
        
        $.ajax({
            url: '<?php echo SERVERURL;?>core/asignarSubmenu2Plan.php',
            type: 'POST',
            data: { 
                plan_id: planId, 
                submenu1_id: submenu2Id,
                estado: nuevoEstado
            },
            dataType: 'json',
            beforeSend: function() {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            },
            success: function(response) {
                if (response.estado) {
                    // Actualización suave
                    button.data('asignado', !asignado);
                    button.toggleClass('btn-success btn-danger');
                    button.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
                    
                    const badge = button.closest('tr').find('span.badge');
                    badge.toggleClass('badge-success badge-secondary');
                    badge.text(asignado ? 'No asignado' : 'Asignado');
                    
                    // Actualizar contador
                    const currentCount = parseInt($(`#contador-submenus2-${planId}`).text());
                    $(`#contador-submenus2-${planId}`).text(asignado ? currentCount - 1 : currentCount + 1);
                    
                    // Actualizar DataTable principal sin recarga completa
                    const row = dataTablePlanes.row($(`.btn-asignar-submenu2[data-plan-id="${planId}"]`).closest('tr'));
                    const rowData = row.data();
                    
                    if (rowData) {
                        rowData.submenus2_asignados = asignado ? rowData.submenus2_asignados - 1 : rowData.submenus2_asignados + 1;
                        row.data(rowData).draw(false);
                    }
                    
                    showNotify(response.type, response.title, response.message);
                } else {
                    showNotify(response.type, response.title, response.message || 'Error al actualizar submenú nivel 2');
                }
            },
            error: function(xhr) {
                showNotify('error', 'Error', 'Error de conexión al actualizar submenú nivel 2');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Eventos para abrir los modales con configuración persistente
    $(document).on('click', '.btn-asignar-menu', function() {
        const planId = $(this).data('plan-id');
        const planNombre = $(this).data('plan-nombre');
        
        $('#plan_id_menus').val(planId);
        $('#modalAsignarMenus .modal-title').text(`Asignar Menús Principales al Plan: ${planNombre}`);
        listar_menus_asignacion(planId);
        
        // Mostrar modal con opciones de no cierre
        $('#modalAsignarMenus').modal({
            keyboard: false,    // Desactiva el cierre con ESC
            backdrop: 'static'  // Desactiva el cierre al hacer clic fuera
        }).modal('show');
    });

    $(document).on('click', '.btn-asignar-submenu', function() {
        const planId = $(this).data('plan-id');
        const planNombre = $(this).data('plan-nombre');
        
        $('#plan_id_submenus').val(planId);
        $('#modalAsignarSubmenus .modal-title').text(`Asignar Submenús Nivel 1 al Plan: ${planNombre}`);
        listar_submenus_asignacion(planId);
        
        // Mostrar modal con opciones de no cierre
        $('#modalAsignarSubmenus').modal({
            keyboard: false,    // Desactiva el cierre con ESC
            backdrop: 'static'  // Desactiva el cierre al hacer clic fuera
        }).modal('show');
    });

    // Evento para abrir modal de submenús nivel 2
    $(document).on('click', '.btn-asignar-submenu2', function() {
        const planId = $(this).data('plan-id');
        const planNombre = $(this).data('plan-nombre');
        
        $('#plan_id_submenus2').val(planId);
        $('#modalAsignarSubmenus2 .modal-title').text(`Asignar Submenús Nivel 2 al Plan: ${planNombre}`);
        listar_submenus2_asignacion(planId);
        
        // Mostrar modal con opciones de no cierre
        $('#modalAsignarSubmenus2').modal({
            keyboard: false,    // Desactiva el cierre con ESC
            backdrop: 'static'  // Desactiva el cierre al hacer clic fuera
        }).modal('show');
    });  
});
</script>