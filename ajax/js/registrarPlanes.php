<script>
(function() {
    "use strict";

    /* =========================================================
        ESPERAR JQUERY + DATATABLES SIN DOCUMENT READY
    ========================================================= */
    let intentosCargaPlanes = 0;
    const MAX_INTENTOS_CARGA_PLANES = 60;

    function dependenciasPlanesDisponibles() {
        if (typeof window.jQuery === "undefined") {
            return false;
        }

        const $ = window.jQuery;

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

    function esperarDependenciasPlanes() {
        if (dependenciasPlanesDisponibles()) {
            inicializarModuloPlanes(window.jQuery);
            return;
        }

        intentosCargaPlanes++;

        if (intentosCargaPlanes >= MAX_INTENTOS_CARGA_PLANES) {
            console.error("DataTables no está disponible. Revise que jquery.dataTables.min.js se cargue antes de este script.");

            if (typeof window.showNotify === "function") {
                window.showNotify(
                    "error",
                    "Error",
                    "No se pudo cargar DataTables. Revise la carga de librerías JS."
                );
            }

            return;
        }

        setTimeout(esperarDependenciasPlanes, 150);
    }

    function inicializarModuloPlanes($) {

        /* =========================================================
            CONFIGURACIÓN DE RUTAS
        ========================================================= */
        const PLANES_URLS = {
            llenarDataTable: "<?php echo SERVERURL;?>core/planes/llenarDataTablePlanes.php",
            eliminarConfiguracion: "<?php echo SERVERURL;?>core/planes/eliminarConfiguracionPlan.php",
            obtenerPlan: "<?php echo SERVERURL;?>core/planes/obtenerPlan.php",
            registrarPlan: "<?php echo SERVERURL;?>core/planes/registrarPlan.php",
            actualizarPlan: "<?php echo SERVERURL;?>core/planes/actualizarPlan.php",
            eliminarPlan: "<?php echo SERVERURL;?>core/planes/eliminarPlan.php",
            obtenerMenus: "<?php echo SERVERURL;?>core/planes/obtenerMenusPlanDisponibles.php",
            obtenerSubmenus: "<?php echo SERVERURL;?>core/planes/obtenerSubmenusPlanDisponibles.php",
            obtenerSubmenus2: "<?php echo SERVERURL;?>core/planes/obtenerSubmenus2PlanDisponibles.php",
            asignarMenu: "<?php echo SERVERURL;?>core/planes/asignarMenuPlan.php",
            asignarSubmenu: "<?php echo SERVERURL;?>core/planes/asignarSubmenuPlan.php",
            asignarSubmenu2: "<?php echo SERVERURL;?>core/planes/asignarSubmenu2Plan.php"
        };

        /* =========================================================
            OPCIONES DE CONFIGURACIÓN
        ========================================================= */
        const opcionesConfiguracion = [
            {value: "usuarios", text: "Usuarios"},
            {value: "clientes", text: "Clientes"},
            {value: "proveedores", text: "Proveedores"},
            {value: "productos", text: "Productos"},
            {value: "facturas", text: "Facturas"},
            {value: "compras", text: "Compras"},
            {value: "cotizaciones", text: "Cotizaciones"},
            {value: "perfiles", text: "Puntos de Venta"},
            {value: "almacenes", text: "Almacenes"},
            {value: "categorias", text: "Categorías"},
            {value: "colaboradores", text: "Colaboradores"},
            {value: "ubicaciones", text: "Ubicaciones"},
            {value: "contratos", text: "Contratos"},
            {value: "cuentas", text: "Cuentas Contables"},
            {value: "ingresos", text: "Ingresos Contables"},
            {value: "egresos", text: "Egresos Contables"},
            {value: "secuencia", text: "Secuencias de Facturacion"}
        ];

        /* =========================================================
            VARIABLES DEL MÓDULO
        ========================================================= */
        let dataTablePlanes = null;

        /* =========================================================
            HELPERS
        ========================================================= */
        function notificarPlan(type, title, message) {
            if (typeof showNotify === "function") {
                showNotify(type, title, message);
                return;
            }

            console.log(title + ": " + message);
        }

        function cerrarLoadingSiExiste() {
            if (typeof swal !== "undefined") {
                try {
                    swal.close();
                } catch (e) {

                }
            }
        }

        function refrescarSelectPicker() {
            if (typeof $.fn.selectpicker === "function") {
                $(".selectpicker").selectpicker("refresh");
            }
        }

        function inicializarSelectPicker() {
            if (typeof $.fn.selectpicker === "function") {
                $(".selectpicker").selectpicker();
                $(".selectpicker").selectpicker("refresh");
            }
        }

        function bloquearBotonSubmit(texto) {
            $("#btn-submit")
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin"></i> ' + texto);
        }

        function restaurarBotonSubmit(esEdicion) {
            $("#btn-submit")
                .prop("disabled", false)
                .html(
                    esEdicion
                        ? '<i class="fas fa-sync mr-1"></i> Actualizar Plan'
                        : '<i class="fas fa-save mr-1"></i> Registrar Plan'
                );
        }

        function updateEstadoLabel() {
            const estadoActivo = $("#estado_plan").is(":checked");

            $("#estado_label")
                .text(estadoActivo ? "Activo" : "Inactivo")
                .removeClass("text-success text-danger")
                .addClass(estadoActivo ? "font-weight-bold text-success mb-0" : "font-weight-bold text-danger mb-0");
        }

        function obtenerHtmlEstado(data) {
            const iconSize = "1.25em";

            return data == 1
                ? `<span class="status-badge status-active">
                    <i class="fas fa-check-circle" style="font-size: ${iconSize}"></i>ACTIVO</span>`
                : `<span class="status-badge status-inactive">
                    <i class="fas fa-times-circle" style="font-size: ${iconSize}"></i>INACTIVO</span>`;
        }

        /* =========================================================
            CONFIGURACIONES DINÁMICAS
        ========================================================= */
        function agregarConfiguracion(conFoco = false, configuracion = null) {
            let opcionesHTML = "";

            opcionesConfiguracion.forEach(function(opcion) {
                const selected = configuracion && configuracion.clave === opcion.value ? "selected" : "";
                opcionesHTML += '<option value="' + opcion.value + '" ' + selected + '>' + opcion.text + '</option>';
            });

            const valor = configuracion && typeof configuracion.valor !== "undefined" ? configuracion.valor : "";

            const newItem = `
                <div class="input-group mb-3 configuracion-item">
                    <select class="form-control selectpicker mr-2" name="configuracion_clave[]" data-live-search="true" title="Seleccione una opción">
                        ${opcionesHTML}
                    </select>
                    <input type="number" class="form-control mr-2" name="configuracion_valor[]" placeholder="Cantidad" min="0" value="${valor}">
                    <div class="input-group-append">
                        <button class="btn btn-danger remover-configuracion" type="button">
                            <i class="fas fa-times fa-lg"></i> Quitar
                        </button>
                    </div>
                </div>
            `;

            $("#configuraciones-container").append(newItem);

            refrescarSelectPicker();

            if (conFoco) {
                const $ultimoSelect = $("#configuraciones-container .configuracion-item:last-child select[name='configuracion_clave[]']");

                if ($ultimoSelect.length > 0 && typeof $.fn.selectpicker === "function") {
                    $ultimoSelect.selectpicker("focus");
                }
            }
        }

        function obtenerConfiguracionesFormulario() {
            const configs = {};
            let hasEmptyConfigs = false;

            $(".configuracion-item").each(function() {
                const $item = $(this);
                const $select = $item.find("select[name='configuracion_clave[]']");
                const $input = $item.find("input[name='configuracion_valor[]']");

                const clave = $select.val();
                const valor = $.trim($input.val());

                if (!clave) {
                    hasEmptyConfigs = true;
                    $select.addClass("is-invalid");
                } else {
                    $select.removeClass("is-invalid");
                    configs[clave] = valor;
                }
            });

            return {
                configs: configs,
                hasEmptyConfigs: hasEmptyConfigs
            };
        }

        /* =========================================================
            HEADER DINÁMICO - PLANES
        ========================================================= */
        function construirHeaderDataTablePlanes() {
            const $tabla = $("#dataTablePlanes");

            $tabla.empty();

            $tabla.append(
                '<thead>' +
                    '<tr>' +
                        '<th>Acciones</th>' +
                        '<th>Código</th>' +
                        '<th>Plan</th>' +
                        '<th>Configuraciones</th>' +
                        '<th>Estado</th>' +
                        '<th>Menús</th>' +
                        '<th>Submenús</th>' +
                        '<th>Submenús 2</th>' +
                    '</tr>' +
                '</thead>' +
                '<tbody></tbody>'
            );
        }

        /* =========================================================
            DATATABLE PRINCIPAL - PLANES
        ========================================================= */
        function inicializarDataTablePlanes() {
            if ($("#dataTablePlanes").length === 0) {
                console.error("No existe la tabla #dataTablePlanes en esta vista.");
                return;
            }

            if ($.fn.dataTable.isDataTable("#dataTablePlanes")) {
                $("#dataTablePlanes").DataTable().clear().destroy();
            }

            construirHeaderDataTablePlanes();

            dataTablePlanes = $("#dataTablePlanes").DataTable({
                destroy: true,
                ajax: {
                    url: PLANES_URLS.llenarDataTable,
                    type: "POST",
                    dataSrc: "data",
                    error: function(xhr) {
                        console.error("Error al cargar datos:", xhr.responseText);
                        notificarPlan("error", "Error", "Error al cargar los datos de planes");
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

                                        '<button type="button" class="dropdown-item accion-item btn-asignar-menu" data-plan-id="' + row.planes_id + '" data-plan-nombre="' + row.nombre + '">' +
                                            '<span class="accion-icon accion-icon-primary">' +
                                                '<i class="fas fa-link"></i>' +
                                            '</span>' +
                                            '<span class="accion-label">Asignar Menú</span>' +
                                        '</button>' +

                                        '<button type="button" class="dropdown-item accion-item btn-asignar-submenu" data-plan-id="' + row.planes_id + '" data-plan-nombre="' + row.nombre + '">' +
                                            '<span class="accion-icon accion-icon-primary">' +
                                                '<i class="fas fa-link"></i>' +
                                            '</span>' +
                                            '<span class="accion-label">Asignar Submenú</span>' +
                                        '</button>' +

                                        '<button type="button" class="dropdown-item accion-item btn-asignar-submenu2" data-plan-id="' + row.planes_id + '" data-plan-nombre="' + row.nombre + '">' +
                                            '<span class="accion-icon accion-icon-primary">' +
                                                '<i class="fas fa-link"></i>' +
                                            '</span>' +
                                            '<span class="accion-label">Asignar Submenú 2</span>' +
                                        '</button>' +

                                        '<div class="dropdown-divider"></div>' +

                                        '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar btn-editar" data-id="' + row.planes_id + '">' +
                                            '<span class="accion-icon accion-icon-editar">' +
                                                '<i class="fas fa-edit"></i>' +
                                            '</span>' +
                                            '<span class="accion-label">Editar</span>' +
                                        '</button>' +

                                        '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar btn-eliminar" data-id="' + row.planes_id + '" data-nombre="' + row.nombre + '">' +
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

                            if (typeof data === "string" && data.includes("<ul")) {
                                const configs = $(data).find("li");

                                if (configs.length > 0) {
                                    const first = configs.first().text();
                                    const extras = configs.length - 1;

                                    return `
                                        <div>${first}</div>
                                        ${extras > 0 ? `<small class="text-muted">+${extras} más</small>` : ""}
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
                        className: "text-center",
                        render: function(data) {
                            return obtenerHtmlEstado(data);
                        }
                    },
                    {
                        data: null,
                        className: "text-center",
                        render: function(data, type, row) {
                            const count = row.menus_asignados || 0;

                            return `
                                <span class="badge badge-pill badge-primary" id="contador-menus-${row.planes_id}">
                                    ${count} asignados
                                </span>
                            `;
                        }
                    },
                    {
                        data: null,
                        className: "text-center",
                        render: function(data, type, row) {
                            const count = row.submenus_asignados || 0;

                            return `
                                <span class="badge badge-pill badge-info" id="contador-submenus-${row.planes_id}">
                                    ${count} asignados
                                </span>
                            `;
                        }
                    },
                    {
                        data: null,
                        className: "text-center",
                        render: function(data, type, row) {
                            const count = row.submenus2_asignados || 0;

                            return `
                                <span class="badge badge-pill badge-secondary" id="contador-submenus2-${row.planes_id}">
                                    ${count} asignados
                                </span>
                            `;
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
                        className: "text-center text-nowrap align-middle",
                        responsivePriority: 1
                    },
                    {
                        width: "8%",
                        targets: 1,
                        className: "text-center text-nowrap"
                    },
                    {
                        width: "18%",
                        targets: 2
                    },
                    {
                        width: "28%",
                        targets: 3,
                        responsivePriority: 3
                    },
                    {
                        width: "10%",
                        targets: 4,
                        className: "text-center text-nowrap"
                    },
                    {
                        width: "9%",
                        targets: 5,
                        className: "text-center text-nowrap"
                    },
                    {
                        width: "9%",
                        targets: 6,
                        className: "text-center text-nowrap"
                    },
                    {
                        width: "9%",
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
            LISTAR CONFIGURACIONES DEL PLAN
        ========================================================= */
        function listar_configuraciones(plan_id, configuraciones) {
            if ($.fn.dataTable.isDataTable("#tablaConfiguraciones")) {
                $("#tablaConfiguraciones").DataTable().clear().destroy();
            }

            let dataSet = [];

            if (configuraciones && typeof configuraciones === "object" && Object.keys(configuraciones).length > 0) {
                let index = 1;

                for (const [clave, valor] of Object.entries(configuraciones)) {
                    const opcion = opcionesConfiguracion.find(function(op) {
                        return op.value === clave;
                    });

                    const texto = opcion ? opcion.text : clave;

                    dataSet.push({
                        id: index,
                        clave: clave,
                        config: texto,
                        valor: valor,
                        acciones: `
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
                    id: 1,
                    clave: "",
                    config: "Sin configuraciones",
                    valor: "-",
                    acciones: ""
                });
            }

            $("#tablaConfiguraciones").DataTable({
                data: dataSet,
                columns: [
                    {data: "id", title: "#", width: "5%"},
                    {data: "config", title: "Configuración"},
                    {data: "valor", title: "Cantidad"},
                    {data: "acciones", title: "Acciones", width: "15%"}
                ],
                language: typeof idioma_español !== "undefined" ? idioma_español : {},
                paging: false,
                searching: false,
                info: false,
                responsive: true
            });

            $("#modalConfiguraciones").data("plan-id", plan_id);

            $("#modalConfiguraciones").modal({
                show: true,
                keyboard: false,
                backdrop: "static"
            });
        }

        /* =========================================================
            RESET FORMULARIO
        ========================================================= */
        function resetFormulario() {
            $("#form-title").text("Registrar Nuevo Plan");

            if ($("#formulario_plan").length > 0) {
                $("#formulario_plan")[0].reset();
            }

            $("#plan_id").val("");
            $("#configuraciones-container").empty();
            $("#btn-submit").html('<i class="fas fa-save mr-1"></i> Registrar Plan').prop("disabled", false);
            $("#btn-cancelar-edicion").hide();

            $("#estado_plan").prop("checked", true);
            updateEstadoLabel();

            agregarConfiguracion(false);
            refrescarSelectPicker();

            $("#nombre_plan").focus();
        }

        /* =========================================================
            CARGAR DATOS PARA EDITAR PLAN
        ========================================================= */
        function cargarPlanParaEditar(planId) {
            $.ajax({
                url: PLANES_URLS.obtenerPlan,
                type: "POST",
                data: {
                    plan_id: planId
                },
                dataType: "json",
                beforeSend: function() {
                    bloquearBotonSubmit("Cargando...");
                },
                success: function(response) {
                    if (response.success) {
                        $("#form-title").text("Editar Plan");
                        $("#nombre_plan").val(response.data.nombre);
                        $("#estado_plan").prop("checked", response.data.estado == 1);
                        $("#plan_id").val(response.data.planes_id);

                        updateEstadoLabel();

                        const $container = $("#configuraciones-container");
                        $container.empty();

                        try {
                            const configs = response.data.configuraciones_json || {};

                            if (Object.keys(configs).length > 0) {
                                for (const [clave, valor] of Object.entries(configs)) {
                                    agregarConfiguracion(false, {
                                        clave: clave,
                                        valor: valor
                                    });
                                }
                            } else {
                                agregarConfiguracion(false);
                            }
                        } catch (e) {
                            console.error("Error parsing configs:", e);
                            notificarPlan("error", "Error", "Error al cargar las configuraciones");
                        }

                        $("#btn-submit")
                            .html('<i class="fas fa-sync fa-lg mr-1"></i> Actualizar Plan')
                            .prop("disabled", false);

                        $("#btn-cancelar-edicion").show();
                        $("#nombre_plan").focus();

                        refrescarSelectPicker();

                    } else {
                        notificarPlan("error", "Error", response.message || "Error al cargar el plan");
                        $("#btn-submit").prop("disabled", false).html('<i class="fas fa-save mr-1"></i> Registrar Plan');
                    }
                },
                error: function(xhr) {
                    console.error("Error en la solicitud:", xhr.responseText);
                    notificarPlan("error", "Error", "Error de conexión al cargar el plan");
                    $("#btn-submit").prop("disabled", false).html('<i class="fas fa-save mr-1"></i> Registrar Plan');
                }
            });
        }

        /* =========================================================
            GUARDAR PLAN
        ========================================================= */
        function guardarPlan(e) {
            e.preventDefault();

            const form = $("#formulario_plan")[0];
            const formData = new FormData(form);

            const resultadoConfigs = obtenerConfiguracionesFormulario();

            if (resultadoConfigs.hasEmptyConfigs) {
                notificarPlan("warning", "Validación", "Seleccione una configuración válida o elimine la fila vacía.");
                return;
            }

            formData.append("configuraciones_json", JSON.stringify(resultadoConfigs.configs));

            const esEdicion = !!formData.get("plan_id");
            const url = esEdicion ? PLANES_URLS.actualizarPlan : PLANES_URLS.registrarPlan;

            bloquearBotonSubmit("Procesando...");

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

                        if (dataTablePlanes) {
                            dataTablePlanes.ajax.reload(null, false);
                        }

                        notificarPlan(response.type, response.title, response.message);
                    } else {
                        notificarPlan(response.type || "error", response.title || "Error", response.message || "Error desconocido");
                    }

                    restaurarBotonSubmit(esEdicion);
                },
                error: function(xhr) {
                    console.error("Error en la solicitud:", xhr.responseText);
                    notificarPlan("error", "Error", "Error de conexión al procesar el plan");
                    restaurarBotonSubmit(esEdicion);
                }
            });
        }

        /* =========================================================
            ELIMINAR CONFIGURACIÓN DEL PLAN
        ========================================================= */
        function eliminarConfiguracionPlan($button) {
            const clave = $button.data("clave");
            const planId = $button.data("plan-id");

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
                        text: "Sí, eliminar"
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then(function(willConfirm) {
                if (willConfirm !== true) {
                    return;
                }

                $.ajax({
                    url: PLANES_URLS.eliminarConfiguracion,
                    type: "POST",
                    data: {
                        plan_id: planId,
                        clave: clave
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.success) {
                            const configs = response.configuraciones || {};
                            const table = $("#tablaConfiguraciones").DataTable();

                            if (Object.keys(configs).length === 0) {
                                table.clear().draw();

                                table.row.add({
                                    id: 1,
                                    clave: "",
                                    config: "Sin configuraciones",
                                    valor: "-",
                                    acciones: ""
                                }).draw();
                            } else {
                                let newData = [];
                                let index = 1;

                                for (const [key, val] of Object.entries(configs)) {
                                    const opcion = opcionesConfiguracion.find(function(op) {
                                        return op.value === key;
                                    });

                                    const texto = opcion ? opcion.text : key;

                                    newData.push({
                                        id: index,
                                        clave: key,
                                        config: texto,
                                        valor: val,
                                        acciones: `
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

                            if (dataTablePlanes) {
                                dataTablePlanes.ajax.reload(null, false);
                            }

                            notificarPlan("success", "Éxito", response.message);
                        } else {
                            notificarPlan("error", "Error", response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error("Error al eliminar:", xhr.responseText);
                        notificarPlan("error", "Error", "Error de conexión al eliminar configuración");
                    },
                    complete: function() {
                        $button.prop("disabled", false).html('<i class="fas fa-trash"></i> Eliminar');
                    }
                });
            });
        }

        /* =========================================================
            ELIMINAR PLAN
        ========================================================= */
        function eliminarPlan(planId, nombrePlan) {
            const mensajeHTML = `¿Desea eliminar permanentemente el plan?<br><br>
            <strong>Nombre:</strong> ${nombrePlan}`;

            swal({
                title: "¿Confirmar eliminación?",
                content: {
                    element: "div",
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
                        className: "btn-danger"
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then(function(confirmado) {
                if (!confirmado) {
                    return;
                }

                $.ajax({
                    url: PLANES_URLS.eliminarPlan,
                    type: "POST",
                    data: {
                        plan_id: planId
                    },
                    dataType: "json",
                    beforeSend: function() {
                        if (typeof showLoading === "function") {
                            showLoading("Procesando...");
                        }
                    },
                    success: function(response) {
                        cerrarLoadingSiExiste();

                        if (response.type === "success") {
                            if (dataTablePlanes) {
                                dataTablePlanes.ajax.reload(null, false);
                            }

                            notificarPlan(response.type, response.title, response.message);
                        } else {
                            notificarPlan(response.type || "error", response.title || "Error", response.message || "No se pudo eliminar el plan");
                        }
                    },
                    error: function(xhr) {
                        cerrarLoadingSiExiste();
                        console.error("Error en la solicitud:", xhr.responseText);
                        notificarPlan("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            });
        }

        /* =========================================================
            LISTAR MENÚS PRINCIPALES PARA ASIGNACIÓN
        ========================================================= */
        function listar_menus_asignacion(plan_id) {
            $("#tablaMenus").DataTable({
                destroy: true,
                ajax: {
                    method: "POST",
                    url: PLANES_URLS.obtenerMenus,
                    data: {
                        plan_id: plan_id
                    },
                    dataSrc: function(json) {
                        if (!json.success) {
                            console.error(json.message);
                            notificarPlan("error", "Error", "Error al cargar los menús");
                            return [];
                        }

                        const contador = json.data.filter(function(d) {
                            return d.asignado;
                        }).length;

                        $("#contador-menus-" + plan_id).text(contador + " asignados");

                        return json.data.map(function(menu, index) {
                            return {
                                "#": index + 1,
                                name: menu.name,
                                asignado: menu.asignado
                                    ? '<span class="badge badge-success">Asignado</span>'
                                    : '<span class="badge badge-secondary">No asignado</span>',
                                acciones: `
                                    <button class="btn btn-sm ${menu.asignado ? "btn-danger" : "btn-success"} btn-toggle-menu"
                                        data-menu-id="${menu.menu_id}"
                                        data-asignado="${menu.asignado}">
                                        ${menu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                    </button>
                                `
                            };
                        });
                    }
                },
                columns: [
                    {data: "#"},
                    {data: "name"},
                    {data: "asignado"},
                    {data: "acciones"}
                ],
                lengthMenu: typeof lengthMenu10 !== "undefined" ? lengthMenu10 : [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                stateSave: true,
                language: typeof idioma_español !== "undefined" ? idioma_español : {},
                dom: typeof dom !== "undefined" ? dom : "frtip",
                buttons: []
            });
        }

        /* =========================================================
            LISTAR SUBMENÚS NIVEL 1 PARA ASIGNACIÓN
        ========================================================= */
        function listar_submenus_asignacion(plan_id) {
            $("#tablaSubmenus").DataTable({
                destroy: true,
                ajax: {
                    method: "POST",
                    url: PLANES_URLS.obtenerSubmenus,
                    data: {
                        plan_id: plan_id
                    },
                    dataSrc: function(json) {
                        if (!json.success) {
                            console.error(json.message);
                            notificarPlan("error", "Error", "Error al cargar los submenús");
                            return [];
                        }

                        const contador = json.data.filter(function(d) {
                            return d.asignado;
                        }).length;

                        $("#contador-submenus-" + plan_id).text(contador + " asignados");

                        return json.data.map(function(submenu, index) {
                            return {
                                "#": index + 1,
                                menu_name: submenu.descripcion_padre,
                                name: submenu.descripcion,
                                asignado: submenu.asignado
                                    ? '<span class="badge badge-success">Asignado</span>'
                                    : '<span class="badge badge-secondary">No asignado</span>',
                                acciones: `
                                    <button class="btn btn-sm ${submenu.asignado ? "btn-danger" : "btn-success"} btn-toggle-submenu"
                                        data-submenu-id="${submenu.submenu_id}"
                                        data-asignado="${submenu.asignado}">
                                        ${submenu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                    </button>
                                `
                            };
                        });
                    }
                },
                columns: [
                    {data: "#"},
                    {data: "menu_name"},
                    {data: "name"},
                    {data: "asignado"},
                    {data: "acciones"}
                ],
                lengthMenu: typeof lengthMenu10 !== "undefined" ? lengthMenu10 : [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                stateSave: true,
                language: typeof idioma_español !== "undefined" ? idioma_español : {},
                dom: typeof dom !== "undefined" ? dom : "frtip",
                buttons: []
            });
        }

        /* =========================================================
            LISTAR SUBMENÚS NIVEL 2 PARA ASIGNACIÓN
        ========================================================= */
        function listar_submenus2_asignacion(plan_id) {
            $("#tablaSubmenus2").DataTable({
                destroy: true,
                ajax: {
                    method: "POST",
                    url: PLANES_URLS.obtenerSubmenus2,
                    data: {
                        plan_id: plan_id
                    },
                    dataSrc: function(json) {
                        if (!json.success) {
                            console.error(json.message);
                            notificarPlan("error", "Error", "Error al cargar los submenús nivel 2");
                            return [];
                        }

                        const contador = json.data.filter(function(d) {
                            return d.asignado;
                        }).length;

                        $("#contador-submenus2-" + plan_id).text(contador + " asignados");

                        return json.data.map(function(s2, index) {
                            return {
                                "#": index + 1,
                                menu_name: s2.descripcion_padre,
                                submenu_name: s2.descripcion,
                                name: s2.descripcion_menu,
                                asignado: s2.asignado
                                    ? '<span class="badge badge-success">Asignado</span>'
                                    : '<span class="badge badge-secondary">No asignado</span>',
                                acciones: `
                                    <button class="btn btn-sm ${s2.asignado ? "btn-danger" : "btn-success"} btn-toggle-submenu2"
                                        data-submenu2-id="${s2.submenu1_id}"
                                        data-asignado="${s2.asignado}">
                                        ${s2.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
                                    </button>
                                `
                            };
                        });
                    }
                },
                columns: [
                    {data: "#"},
                    {data: "name"},
                    {data: "menu_name"},
                    {data: "submenu_name"},
                    {data: "asignado"},
                    {data: "acciones"}
                ],
                lengthMenu: typeof lengthMenu10 !== "undefined" ? lengthMenu10 : [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                stateSave: true,
                language: typeof idioma_español !== "undefined" ? idioma_español : {},
                dom: typeof dom !== "undefined" ? dom : "frtip",
                buttons: []
            });
        }

        /* =========================================================
            TOGGLE ASIGNACIÓN
        ========================================================= */
        function actualizarContadorAsignacion(selectorContador, asignadoActual) {
            const $counterElement = $(selectorContador);
            const currentCount = parseInt($counterElement.text().split(" ")[0], 10) || 0;
            const newCount = asignadoActual ? currentCount - 1 : currentCount + 1;

            $counterElement.text(newCount + " asignados");
        }

        function actualizarUIBotonAsignacion($button, asignadoActual) {
            $button.data("asignado", !asignadoActual);
            $button.toggleClass("btn-success btn-danger");
            $button.html(asignadoActual ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');

            const $badge = $button.closest("tr").find("span.badge");
            $badge.toggleClass("badge-success badge-secondary");
            $badge.text(asignadoActual ? "No asignado" : "Asignado");
        }

        /* =========================================================
            EVENTOS
        ========================================================= */
        function registrarEventosPlanes() {
            $("#estado_plan").off("change.planes").on("change.planes", updateEstadoLabel);

            $("#agregar-configuracion").off("click.planes").on("click.planes", function() {
                agregarConfiguracion(true);
            });

            $("#configuraciones-container").off("click.planes", ".remover-configuracion").on("click.planes", ".remover-configuracion", function() {
                $(this).closest(".configuracion-item").remove();
            });

            $(document).off("click.planesEliminarConfig", ".btn-eliminar-config").on("click.planesEliminarConfig", ".btn-eliminar-config", function() {
                eliminarConfiguracionPlan($(this));
            });

            $(document).off("click.planesVerConfigs", ".btn-ver-configs").on("click.planesVerConfigs", ".btn-ver-configs", function() {
                const configs = $(this).data("configs");
                const rowData = dataTablePlanes.row($(this).closest("tr")).data();
                const planId = rowData ? rowData.planes_id : null;
                const planNombre = rowData ? rowData.nombre : "Sin nombre";

                $("#modalConfiguraciones .modal-title").text("Configuraciones del Plan: " + planNombre);

                listar_configuraciones(planId, configs);
            });

            $(document).off("click.planesEditar", ".btn-editar").on("click.planesEditar", ".btn-editar", function() {
                const planId = $(this).data("id");
                cargarPlanParaEditar(planId);
            });

            $("#btn-cancelar-edicion").off("click.planes").on("click.planes", function() {
                resetFormulario();
            });

            $("#formulario_plan").off("submit.planes").on("submit.planes", guardarPlan);

            $(document).off("click.planesEliminar", ".btn-eliminar").on("click.planesEliminar", ".btn-eliminar", function() {
                const planId = $(this).data("id");
                const nombrePlan = $(this).data("nombre");

                eliminarPlan(planId, nombrePlan);
            });

            $(document).off("click.planesToggleMenu", ".btn-toggle-menu").on("click.planesToggleMenu", ".btn-toggle-menu", function() {
                const $button = $(this);
                const menuId = $button.data("menu-id");
                const asignado = $button.data("asignado");
                const planId = $("#plan_id_menus").val();
                const nuevoEstado = asignado ? 0 : 1;

                $.ajax({
                    url: PLANES_URLS.asignarMenu,
                    type: "POST",
                    data: {
                        plan_id: planId,
                        menu_id: menuId,
                        estado: nuevoEstado
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.estado) {
                            actualizarUIBotonAsignacion($button, asignado);
                            actualizarContadorAsignacion("#contador-menus-" + planId, asignado);

                            if (dataTablePlanes) {
                                dataTablePlanes.ajax.reload(null, false);
                            }

                            notificarPlan(response.type, response.title, response.message);
                        } else {
                            notificarPlan(response.type || "error", response.title || "Error", response.message || "Error al actualizar menú");
                        }
                    },
                    error: function() {
                        notificarPlan("error", "Error", "Error de conexión al actualizar menú");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                    }
                });
            });

            $(document).off("click.planesToggleSubmenu", ".btn-toggle-submenu").on("click.planesToggleSubmenu", ".btn-toggle-submenu", function() {
                const $button = $(this);
                const submenuId = $button.data("submenu-id");
                const asignado = $button.data("asignado");
                const planId = $("#plan_id_submenus").val();
                const nuevoEstado = asignado ? 0 : 1;

                $.ajax({
                    url: PLANES_URLS.asignarSubmenu,
                    type: "POST",
                    data: {
                        plan_id: planId,
                        submenu_id: submenuId,
                        estado: nuevoEstado
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.estado) {
                            actualizarUIBotonAsignacion($button, asignado);
                            actualizarContadorAsignacion("#contador-submenus-" + planId, asignado);

                            if (dataTablePlanes) {
                                dataTablePlanes.ajax.reload(null, false);
                            }

                            notificarPlan(response.type, response.title, response.message);
                        } else {
                            notificarPlan(response.type || "error", response.title || "Error", response.message || "Error al actualizar submenú");
                        }
                    },
                    error: function() {
                        notificarPlan("error", "Error", "Error de conexión al actualizar submenú");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                    }
                });
            });

            $(document).off("click.planesToggleSubmenu2", ".btn-toggle-submenu2").on("click.planesToggleSubmenu2", ".btn-toggle-submenu2", function() {
                const $button = $(this);
                const submenu2Id = $button.data("submenu2-id");
                const asignado = $button.data("asignado");
                const planId = $("#plan_id_submenus2").val();
                const nuevoEstado = asignado ? 0 : 1;

                $.ajax({
                    url: PLANES_URLS.asignarSubmenu2,
                    type: "POST",
                    data: {
                        plan_id: planId,
                        submenu1_id: submenu2Id,
                        estado: nuevoEstado
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $button.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.estado) {
                            actualizarUIBotonAsignacion($button, asignado);
                            actualizarContadorAsignacion("#contador-submenus2-" + planId, asignado);

                            if (dataTablePlanes) {
                                dataTablePlanes.ajax.reload(null, false);
                            }

                            notificarPlan(response.type, response.title, response.message);
                        } else {
                            notificarPlan(response.type || "error", response.title || "Error", response.message || "Error al actualizar submenú nivel 2");
                        }
                    },
                    error: function() {
                        notificarPlan("error", "Error", "Error de conexión al actualizar submenú nivel 2");
                    },
                    complete: function() {
                        $button.prop("disabled", false);
                    }
                });
            });

            $(document).off("click.planesAbrirMenus", ".btn-asignar-menu").on("click.planesAbrirMenus", ".btn-asignar-menu", function() {
                const planId = $(this).data("plan-id");
                const planNombre = $(this).data("plan-nombre");

                $("#plan_id_menus").val(planId);
                $("#modalAsignarMenus .modal-title").text("Asignar Menús Principales al Plan: " + planNombre);

                listar_menus_asignacion(planId);

                $("#modalAsignarMenus").modal({
                    keyboard: false,
                    backdrop: "static"
                }).modal("show");
            });

            $(document).off("click.planesAbrirSubmenus", ".btn-asignar-submenu").on("click.planesAbrirSubmenus", ".btn-asignar-submenu", function() {
                const planId = $(this).data("plan-id");
                const planNombre = $(this).data("plan-nombre");

                $("#plan_id_submenus").val(planId);
                $("#modalAsignarSubmenus .modal-title").text("Asignar Submenús Nivel 1 al Plan: " + planNombre);

                listar_submenus_asignacion(planId);

                $("#modalAsignarSubmenus").modal({
                    keyboard: false,
                    backdrop: "static"
                }).modal("show");
            });

            $(document).off("click.planesAbrirSubmenus2", ".btn-asignar-submenu2").on("click.planesAbrirSubmenus2", ".btn-asignar-submenu2", function() {
                const planId = $(this).data("plan-id");
                const planNombre = $(this).data("plan-nombre");

                $("#plan_id_submenus2").val(planId);
                $("#modalAsignarSubmenus2 .modal-title").text("Asignar Submenús Nivel 2 al Plan: " + planNombre);

                listar_submenus2_asignacion(planId);

                $("#modalAsignarSubmenus2").modal({
                    keyboard: false,
                    backdrop: "static"
                }).modal("show");
            });
        }

        /* =========================================================
            ARRANQUE DEL MÓDULO
        ========================================================= */
        function arrancarPlanes() {
            updateEstadoLabel();
            inicializarSelectPicker();
            registrarEventosPlanes();
            inicializarDataTablePlanes();

            $("#configuraciones-container").empty();
            agregarConfiguracion(false);

            setTimeout(function() {
                $("#nombre_plan").focus();
            }, 100);
        }

        arrancarPlanes();
    }

    esperarDependenciasPlanes();

})();
</script>