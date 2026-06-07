<script>
// asignacionPlanes.php
$(window).on("load", function() {
    "use strict";

    /* =========================================================
        RUTAS - ASIGNACIÓN DE PLANES
    ========================================================= */
    const ASIGNAR_PLANES_URLS = {
        obtenerAsignaciones: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerAsignacionesRecientes.php",
        obtenerClientes: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerClientesParaAsignacion.php",
        obtenerPlanes: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerPlanesActivos.php",
        obtenerSistemas: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerSistemas.php",
        verificarPlanCliente: "<?php echo SERVERURL; ?>core/AsignarPlanes/verificarPlanCliente.php",
        actualizarPlanCliente: "<?php echo SERVERURL; ?>core/AsignarPlanes/actualizarPlanCliente.php"
    };

    let tablaAsignaciones = null;

    /* =========================================================
        FORMATO FECHA
    ========================================================= */
    function formatFechaHora(fecha) {
        if (!fecha) {
            return "";
        }

        const date = new Date(fecha);

        if (isNaN(date.getTime())) {
            return fecha;
        }

        return date.toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    /* =========================================================
        LIMPIAR HTML
    ========================================================= */
    function limpiarHtml(texto) {
        if (texto === null || typeof texto === "undefined") {
            return "";
        }

        return String(texto)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function obtenerBadgePlan(nombrePlan, planesId) {
        const nombre = String(nombrePlan || "").toLowerCase().trim();

        if (nombre.includes("emprendedor")) {
            return { clase: "badge-primary", icono: "fas fa-rocket" };
        }

        if (nombre.includes("básico+") || nombre.includes("basico+")) {
            return { clase: "badge-info", icono: "fas fa-layer-group" };
        }

        if (nombre.includes("básico") || nombre.includes("basico")) {
            return { clase: "badge-info", icono: "fas fa-leaf" };
        }

        if (nombre.includes("regular")) {
            return { clase: "badge-success", icono: "fas fa-check-circle" };
        }

        if (nombre.includes("estandar") || nombre.includes("estándar")) {
            return { clase: "badge-warning", icono: "fas fa-star" };
        }

        if (nombre.includes("premium")) {
            return { clase: "badge-danger", icono: "fas fa-gem" };
        }

        const estilos = [
            { clase: "badge-primary", icono: "fas fa-rocket" },
            { clase: "badge-info", icono: "fas fa-layer-group" },
            { clase: "badge-success", icono: "fas fa-check-circle" },
            { clase: "badge-warning", icono: "fas fa-star" },
            { clase: "badge-danger", icono: "fas fa-gem" },
            { clase: "badge-secondary", icono: "fas fa-crown" }
        ];

        const index = Math.abs(parseInt(planesId, 10) || 0) % estilos.length;

        return estilos[index];
    }    

    /* =========================================================
        BOTÓN ACTUALIZAR PLAN
    ========================================================= */
    function obtenerBotonActualizarPlan() {
        let boton = $("#btnActualizarPlan");

        if (boton.length <= 0) {
            boton = $("#formAsignacionPlan").find("button[type='submit']").first();
        }

        return boton;
    }

    function bloquearBotonActualizarPlan() {
        const boton = obtenerBotonActualizarPlan();

        if (boton.length > 0) {
            boton
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin mr-1"></i> Actualizando...');
        }
    }

    function restaurarBotonActualizarPlan() {
        const boton = obtenerBotonActualizarPlan();

        if (boton.length > 0) {
            boton
                .prop("disabled", false)
                .html('<i class="fas fa-save mr-1"></i> Actualizar Plan');
        }
    }

    /* =========================================================
        RESET FORMULARIO
    ========================================================= */
    function resetFormularioAsignacion() {
        if ($("#formAsignacionPlan").length > 0) {
            $("#formAsignacionPlan")[0].reset();
        }

        $("#server_customers_id").val("");
        $("#user_extra").val(0);

        if (typeof $.fn.selectpicker === "function") {
            $(".selectpicker").selectpicker("refresh");
        }
    }

    /* =========================================================
        HEADER DINÁMICO - ASIGNACIÓN DE PLANES
    ========================================================= */
    function construirHeaderTablaAsignaciones() {
        var $tabla = $("#tablaAsignaciones");

        $tabla.empty();

        $tabla.append(
            '<thead>' +
                '<tr>' +
                    '<th>Acciones</th>' +
                    '<th>#</th>' +
                    '<th>Cliente</th>' +
                    '<th>Plan</th>' +
                    '<th>Sistema</th>' +
                    '<th>Usuarios Extra</th>' +
                    '<th>Validar</th>' +
                    '<th>Estado</th>' +
                    '<th>Fecha Registro</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody></tbody>'
        );
    }

    /* =========================================================
        DATATABLE - ASIGNACIÓN DE PLANES
    ========================================================= */
    function inicializarDataTableAsignaciones() {
        construirHeaderTablaAsignaciones();

        tablaAsignaciones = $("#tablaAsignaciones").DataTable({
            destroy: true,
            ajax: {
                url: ASIGNAR_PLANES_URLS.obtenerAsignaciones,
                type: "POST",
                dataSrc: function(json) {
                    if (json && json.success === false) {
                        showNotify("error", "Error", json.message || "Error al cargar las asignaciones");
                        return [];
                    }

                    return json.data || [];
                },
                error: function(xhr) {
                    console.error("Error al cargar asignaciones:", xhr.responseText);
                    showNotify("error", "Error", "Error al cargar las asignaciones");
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

                                    '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar btn-editar-asignacion" ' +
                                        'data-id="' + row.server_customers_id + '" ' +
                                        'data-cliente-id="' + row.cliente_id + '" ' +
                                        'data-plan-id="' + row.planes_id + '" ' +
                                        'data-sistema-id="' + row.sistema_id + '" ' +
                                        'data-user-extra="' + row.user_extra + '" ' +
                                        'data-validar="' + row.validar + '" ' +
                                        'data-estado="' + row.estado + '">' +
                                        '<span class="accion-icon accion-icon-editar">' +
                                            '<i class="fas fa-edit"></i>' +
                                        '</span>' +
                                        '<span class="accion-label">Editar</span>' +
                                    '</button>' +

                                '</div>' +

                            '</div>';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    },
                    className: "text-center"
                },
                {
                    data: "cliente",
                    render: function(data) {
                        const nombre = data && data.nombre ? limpiarHtml(data.nombre) : "";
                        const identificacion = data && data.identificacion ? limpiarHtml(data.identificacion) : "Sin identificación";
                        const codigoCliente = data && data.codigo_cliente ? limpiarHtml(data.codigo_cliente) : "Sin código";

                        return '' +
                            '<strong>' + nombre + '</strong><br>' +
                            '<small class="text-muted">RTN: ' + identificacion + '</small><br>' +
                            '<small class="text-muted">Código Cliente: ' + codigoCliente + '</small><br>';
                    }
                },
                {
                    data: "plan",
                    render: function(data, type, row) {
                        const nombrePlan = data && data.nombre ? limpiarHtml(data.nombre) : "Sin plan";
                        const info = obtenerBadgePlan(nombrePlan, row.planes_id);

                        return '<span class="badge ' + info.clase + ' badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.85em; font-weight: 600; letter-spacing: 0.2px;">' +
                                    '<i class="' + info.icono + '" style="margin-right: 6px;"></i>' + nombrePlan +
                                '</span>';
                    }
                },
                {
                    data: "sistema",
                    render: function(data) {
                        const nombreSistema = data && data.nombre ? data.nombre : "";

                        let badgeClass;
                        let iconClass;

                        switch (nombreSistema) {
                            case "CAMI":
                                badgeClass = "badge-info";
                                iconClass = "fas fa-stethoscope";
                                break;

                            case "IZZY":
                                badgeClass = "badge-success";
                                iconClass = "fas fa-store";
                                break;

                            case "MONISYS":
                                badgeClass = "badge-warning";
                                iconClass = "fas fa-chart-line";
                                break;

                            default:
                                badgeClass = "badge-secondary";
                                iconClass = "fas fa-question-circle";
                                break;
                        }

                        return '<span class="badge ' + badgeClass + ' badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                                    '<i class="' + iconClass + '" style="margin-right: 5px;"></i>' + limpiarHtml(nombreSistema) +
                                '</span>';
                    }
                },
                {
                    data: "user_extra",
                    className: "text-center",
                    render: function(data) {
                        const userExtra = parseInt(data, 10) || 0;

                        return userExtra > 0
                            ? '<span class="badge badge-secondary badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">+' + userExtra + '</span>'
                            : '<span class="text-muted">Ninguno</span>';
                    }
                },
                {
                    data: "validar",
                    className: "text-center",
                    render: function(data) {
                        const isValid = data == 1;
                        const badgeClass = isValid ? "badge-success" : "badge-secondary";
                        const iconClass = isValid ? "fas fa-check-circle" : "fas fa-times-circle";
                        const text = isValid ? "Sí" : "No";

                        return '<span class="badge ' + badgeClass + ' badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                                    '<i class="' + iconClass + '" style="margin-right: 5px;"></i>' + text +
                                '</span>';
                    }
                },
                {
                    data: "estado",
                    className: "text-center",
                    render: function(data, type) {
                        if (type !== "display") {
                            return data;
                        }

                        const estadoText = data == 1 ? "Activo" : "Inactivo";

                        const icon = data == 1
                            ? '<i class="fas fa-check-circle mr-1"></i>'
                            : '<i class="fas fa-times-circle mr-1"></i>';

                        const badgeClass = data == 1
                            ? "badge badge-pill badge-success"
                            : "badge badge-pill badge-danger";

                        return '<span class="' + badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }
                },
                {
                    data: "fecha_registro",
                    render: function(data) {
                        return formatFechaHora(data);
                    }
                }
            ],
            language: idioma_español,
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
                    width: "5%",
                    targets: 1,
                    className: "text-center text-nowrap"
                },
                {
                    width: "24%",
                    targets: 2
                },
                {
                    width: "12%",
                    targets: 3,
                    className: "text-center text-nowrap"
                },
                {
                    width: "12%",
                    targets: 4,
                    className: "text-center text-nowrap"
                },
                {
                    width: "10%",
                    targets: 5,
                    className: "text-center text-nowrap"
                },
                {
                    width: "8%",
                    targets: 6,
                    className: "text-center text-nowrap"
                },
                {
                    width: "9%",
                    targets: 7,
                    className: "text-center text-nowrap"
                },
                {
                    width: "10%",
                    targets: 8,
                    className: "text-center text-nowrap"
                }
            ],
            drawCallback: function(settings) {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

                if (typeof cerrarDropdownAcciones === "function") {
                    cerrarDropdownAcciones();
                }
            }
        });
    }

    /* =========================================================
        CARGAR CLIENTES
    ========================================================= */
    function cargarClientes() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerClientes,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#cliente_id");
                    select.empty();

                    select.append('<option value="">Seleccione un cliente...</option>');

                    response.data.forEach(function(cliente) {
                        select.append(
                            '<option value="' + cliente.clientes_id + '" ' +
                                'data-subtext="' + limpiarHtml(cliente.identificacion || "Sin identificación") + '">' +
                                limpiarHtml(cliente.nombre) +
                            '</option>'
                        );
                    });

                    select.selectpicker("refresh");
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar clientes");
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error de conexión al cargar clientes");
            }
        });
    }

    /* =========================================================
        CARGAR PLANES
    ========================================================= */
    function cargarPlanes() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerPlanes,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#planes_id");
                    select.empty();

                    select.append('<option value="">Seleccione un plan...</option>');

                    response.data.forEach(function(plan) {
                        select.append(
                            '<option value="' + plan.planes_id + '">' +
                                limpiarHtml(plan.nombre) +
                            '</option>'
                        );
                    });

                    select.selectpicker("refresh");
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar planes");
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error de conexión al cargar planes");
            }
        });
    }

    /* =========================================================
        CARGAR SISTEMAS
    ========================================================= */
    function cargarSistemas() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerSistemas,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#sistema_id");
                    select.empty();

                    response.data.forEach(function(sistema) {
                        select.append(
                            '<option value="' + sistema.sistema_id + '">' +
                                limpiarHtml(sistema.nombre) +
                            '</option>'
                        );
                    });

                    select.selectpicker("refresh");
                    select.prop("disabled", true);
                }
            },
            error: function(xhr) {
                console.error("Error al cargar sistemas:", xhr.responseText);
            }
        });
    }

    /* =========================================================
        VERIFICAR PLAN CLIENTE
    ========================================================= */
    function verificarPlanCliente(clienteId, callback) {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.verificarPlanCliente,
            type: "POST",
            data: {
                cliente_id: clienteId
            },
            dataType: "json",
            success: function(response) {
                if (typeof callback === "function") {
                    callback(response);
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error al verificar plan del cliente");
            }
        });
    }

    /* =========================================================
        ACTUALIZAR PLAN CLIENTE
    ========================================================= */
    function actualizarPlanCliente(formData) {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.actualizarPlanCliente,
            type: "POST",
            data: formData,
            dataType: "json",
            beforeSend: function() {
                bloquearBotonActualizarPlan();
            },
            success: function(response) {
                restaurarBotonActualizarPlan();

                showNotify(
                    response.type || "info",
                    response.title || "Resultado",
                    response.message || "Proceso finalizado"
                );

                if (response.success) {
                    if (tablaAsignaciones) {
                        tablaAsignaciones.ajax.reload(null, false);
                    }

                    resetFormularioAsignacion();
                }
            },
            error: function(xhr) {
                restaurarBotonActualizarPlan();

                console.error("Error al actualizar plan:", xhr.responseText);
                showNotify("error", "Error", "Error al actualizar plan");
            }
        });
    }

    /* =========================================================
        EDITAR ASIGNACIÓN
    ========================================================= */
    $(document).off("click", ".btn-editar-asignacion").on("click", ".btn-editar-asignacion", function() {
        $("#server_customers_id").val($(this).data("id"));
        $("#cliente_id").val($(this).data("cliente-id")).selectpicker("refresh");
        $("#planes_id").val($(this).data("plan-id")).selectpicker("refresh");
        $("#sistema_id").val($(this).data("sistema-id")).selectpicker("refresh");
        $("#user_extra").val($(this).data("user-extra"));
        $("#validar").val($(this).data("validar")).selectpicker("refresh");
        $("#estado").val($(this).data("estado")).selectpicker("refresh");

        if ($("#div_top").length > 0) {
            $("html, body").animate({
                scrollTop: $("#div_top").offset().top - 20
            }, 500);
        }
    });

    /* =========================================================
        CAMBIO DE CLIENTE
    ========================================================= */
    $("#cliente_id").off("change").on("change", function() {
        const clienteId = $(this).val();

        if (clienteId) {
            verificarPlanCliente(clienteId, function(response) {
                if (response.exists) {
                    $("#server_customers_id").val(response.data.server_customers_id);
                    $("#planes_id").val(response.data.planes_id).selectpicker("refresh");
                    $("#sistema_id").val(response.data.sistema_id).selectpicker("refresh");
                    $("#user_extra").val(response.data.user_extra);
                    $("#validar").val(response.data.validar).selectpicker("refresh");
                    $("#estado").val(response.data.estado).selectpicker("refresh");
                }
            });
        } else {
            $("#server_customers_id").val("");
            $("#user_extra").val(0);
        }
    });

    /* =========================================================
        SUBMIT FORMULARIO CON SWAL NORMAL
    ========================================================= */
    $("#formAsignacionPlan").off("submit").on("submit", function(e) {
        e.preventDefault();

        const clienteId = $("#cliente_id").val();
        const serverCustomersId = $("#server_customers_id").val();
        const planesId = $("#planes_id").val();
        const userExtra = parseInt($("#user_extra").val(), 10);

        if (!clienteId) {
            showNotify("warning", "Advertencia", "Debe seleccionar un cliente");
            return;
        }

        if (!serverCustomersId) {
            showNotify("warning", "Advertencia", "El cliente seleccionado no tiene registro server_customers");
            return;
        }

        if (!planesId) {
            showNotify("warning", "Advertencia", "Debe seleccionar un plan");
            return;
        }

        if (isNaN(userExtra) || userExtra < 0) {
            showNotify("warning", "Advertencia", "Los usuarios extra no pueden ser negativos");
            return;
        }

        const clienteTexto = $("#cliente_id option:selected").text().trim();
        const planTexto = $("#planes_id option:selected").text().trim();
        const sistemaTexto = $("#sistema_id option:selected").text().trim();
        const validarTexto = $("#validar option:selected").text().trim();
        const estadoTexto = $("#estado option:selected").text().trim();

        swal({
            title: "¿Confirmar cambio de plan?",
            text:
                "Cliente: " + clienteTexto + "\n" +
                "Plan: " + planTexto + "\n" +
                "Sistema: " + sistemaTexto + "\n" +
                "Usuarios extra: " + userExtra + "\n" +
                "Validar: " + validarTexto + "\n" +
                "Estado: " + estadoTexto + "\n\n" +
                "Se actualizará el plan y los usuarios permitidos para este cliente.",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn btn-secondary",
                    closeModal: true
                },
                confirm: {
                    text: "Sí, actualizar",
                    value: true,
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: true
                }
            },
            dangerMode: false
        }).then(function(confirmado) {
            if (confirmado) {
                const formData = $("#formAsignacionPlan").serialize();
                actualizarPlanCliente(formData);
            }
        });
    });

    /* =========================================================
        INICIALIZAR
    ========================================================= */
    inicializarDataTableAsignaciones();
    cargarClientes();
    cargarPlanes();
    cargarSistemas();
});
</script>