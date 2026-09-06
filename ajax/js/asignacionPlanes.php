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

    const ASIGNAR_PLANES_STORAGE = {
        filtros: "izzy_asignacion_planes_filtros_visible",
        kpis: "izzy_asignacion_planes_kpis_visible",
        vista: "izzy_asignacion_planes_tipo_vista"
    };

    const asignacionState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        view: "detalle",
        loading: false
    };

    let asignacionDebounceTimer = null;

    /* =========================================================
       UTILIDADES
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

    function formatFechaHora(fecha) {
        if (!fecha) {
            return "No registrada";
        }

        const date = new Date(fecha);

        if (isNaN(date.getTime())) {
            return limpiarHtml(fecha);
        }

        return date.toLocaleDateString("es-HN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }) + " " + date.toLocaleTimeString("es-HN", {
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function normalizarTexto(valor) {
        return String(valor || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function obtenerNombrePlan(row) {
        return row && row.plan && row.plan.nombre ? String(row.plan.nombre) : "Sin plan";
    }

    function obtenerNombreSistema(row) {
        return row && row.sistema && row.sistema.nombre ? String(row.sistema.nombre) : "Sin sistema";
    }

    function obtenerNombreCliente(row) {
        return row && row.cliente && row.cliente.nombre ? String(row.cliente.nombre) : "Sin cliente";
    }

    function planMeta(planesId) {
        const metas = {
            1: { icon: "fa-rocket", cls: "asignacion-plan-primary" },
            2: { icon: "fa-leaf", cls: "asignacion-plan-info" },
            3: { icon: "fa-check-circle", cls: "asignacion-plan-success" },
            4: { icon: "fa-star-half-alt", cls: "asignacion-plan-warning" },
            5: { icon: "fa-gem", cls: "asignacion-plan-danger" },
            6: { icon: "fa-gift", cls: "asignacion-plan-neutral" }
        };

        return metas[parseInt(planesId, 10)] || {
            icon: "fa-layer-group",
            cls: "asignacion-plan-neutral"
        };
    }

    function sistemaMeta(nombre) {
        switch (String(nombre || "").toUpperCase()) {
            case "CAMI":
                return { icon: "fa-stethoscope", cls: "asignacion-system-cami" };
            case "IZZY":
                return { icon: "fa-store", cls: "asignacion-system-izzy" };
            case "MONISYS":
                return { icon: "fa-chart-line", cls: "asignacion-system-monisys" };
            default:
                return { icon: "fa-cubes", cls: "asignacion-system-default" };
        }
    }

    function actualizarPermisosListado() {
        if (typeof getPermisosTipoUsuarioAccesosTable === "function" &&
            typeof getPrivilegioTipoUsuario === "function") {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    }

    /* =========================================================
       PERSISTENCIA MOSTRAR / OCULTAR
       ========================================================= */
    function leerEstadoVisible(clave, valorDefault) {
        try {
            const valor = localStorage.getItem(clave);

            if (valor === null) {
                return valorDefault;
            }

            return valor === "1";
        } catch (e) {
            return valorDefault;
        }
    }

    function guardarEstadoVisible(clave, visible) {
        try {
            localStorage.setItem(clave, visible ? "1" : "0");
        } catch (e) {
            // Si localStorage no está disponible, la vista sigue funcionando.
        }
    }

    function actualizarBotonToggle($boton, visible) {
        $boton.html(
            visible
                ? '<i class="fas fa-chevron-up mr-1"></i> Ocultar'
                : '<i class="fas fa-chevron-down mr-1"></i> Mostrar'
        );

        $boton.attr("aria-expanded", visible ? "true" : "false");
    }

    function configurarSeccionPersistente(config) {
        const $boton = $(config.button);
        const $body = $(config.body);

        if (!$boton.length || !$body.length) {
            return;
        }

        let visible = leerEstadoVisible(config.storageKey, true);

        $body.toggle(visible);
        actualizarBotonToggle($boton, visible);

        $boton.off("click.asignacionToggle").on("click.asignacionToggle", function() {
            visible = !visible;

            $body.stop(true, true).slideToggle(180, function() {
                $(this).toggle(visible);
            });

            guardarEstadoVisible(config.storageKey, visible);
            actualizarBotonToggle($boton, visible);
        });
    }

    function inicializarSeccionesPersistentes() {
        configurarSeccionPersistente({
            button: "#btn_toggle_asignacion_filtros",
            body: "#asignacion_filtros_body",
            storageKey: ASIGNAR_PLANES_STORAGE.filtros
        });

        configurarSeccionPersistente({
            button: "#btn_toggle_asignacion_kpis",
            body: "#asignacion_kpis_body",
            storageKey: ASIGNAR_PLANES_STORAGE.kpis
        });
    }

    /* =========================================================
       BOTÓN ACTUALIZAR PLAN
       ========================================================= */
    function obtenerBotonActualizarPlan() {
        let boton = $("#btn-asignar-plan");

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
                .html('<i class="fas fa-sync-alt mr-1"></i> Actualizar Plan');
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

        $("#formAsignacionPlan")
            .removeData("plan-actual-id")
            .removeData("plan-actual-nombre");

        if (typeof $.fn.selectpicker === "function") {
            $("#formAsignacionPlan .selectpicker").selectpicker("refresh");
        }
    }

    /* =========================================================
       CARGA DEL LISTADO
       ========================================================= */
    function cargarAsignaciones(mantenerPagina) {
        if (asignacionState.loading) {
            return;
        }

        asignacionState.loading = true;

        $("#asignacion_loading").removeClass("d-none");
        $("#asignacion_empty").addClass("d-none");

        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerAsignaciones,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (!response || response.success === false) {
                    asignacionState.rows = [];
                    asignacionState.filtered = [];

                    showNotify(
                        "error",
                        "Error",
                        response && response.message
                            ? response.message
                            : "No se pudieron cargar las asignaciones"
                    );

                    aplicarFiltrosYRender();
                    return;
                }

                asignacionState.rows = Array.isArray(response.data)
                    ? response.data
                    : [];

                if (!mantenerPagina) {
                    asignacionState.page = 1;
                }

                sincronizarCatalogosFiltros();
                aplicarFiltrosYRender();
            },
            error: function(xhr) {
                console.error("Error al cargar asignaciones:", xhr.responseText);

                asignacionState.rows = [];
                asignacionState.filtered = [];
                renderAsignaciones();

                showNotify(
                    "error",
                    "Error",
                    "No se pudieron cargar las asignaciones"
                );
            },
            complete: function() {
                asignacionState.loading = false;
                $("#asignacion_loading").addClass("d-none");
            }
        });
    }

    function sincronizarCatalogosFiltros() {
        const planSeleccionado = $("#filtro_plan").val() || "";
        const sistemaSeleccionado = $("#filtro_sistema").val() || "";

        const planes = {};
        const sistemas = {};

        asignacionState.rows.forEach(function(row) {
            const nombrePlan = obtenerNombrePlan(row);
            const nombreSistema = obtenerNombreSistema(row);

            planes[String(row.planes_id)] = nombrePlan;
            sistemas[String(row.sistema_id)] = nombreSistema;
        });

        const $plan = $("#filtro_plan");
        const $sistema = $("#filtro_sistema");

        $plan.empty().append('<option value="">Todos</option>');
        Object.keys(planes)
            .sort(function(a, b) {
                return planes[a].localeCompare(planes[b], "es");
            })
            .forEach(function(id) {
                $plan.append(
                    '<option value="' + limpiarHtml(id) + '">' +
                        limpiarHtml(planes[id]) +
                    '</option>'
                );
            });

        $sistema.empty().append('<option value="">Todos</option>');
        Object.keys(sistemas)
            .sort(function(a, b) {
                return sistemas[a].localeCompare(sistemas[b], "es");
            })
            .forEach(function(id) {
                $sistema.append(
                    '<option value="' + limpiarHtml(id) + '">' +
                        limpiarHtml(sistemas[id]) +
                    '</option>'
                );
            });

        if (planes[planSeleccionado]) {
            $plan.val(planSeleccionado);
        }

        if (sistemas[sistemaSeleccionado]) {
            $sistema.val(sistemaSeleccionado);
        }

        if (typeof $.fn.selectpicker === "function") {
            $plan.selectpicker("refresh");
            $sistema.selectpicker("refresh");
        }
    }

    function aplicarFiltrosYRender() {
        const filtroPlan = String($("#filtro_plan").val() || "");
        const filtroSistema = String($("#filtro_sistema").val() || "");
        const filtroValidar = String($("#filtro_validar").val() || "");
        const filtroDb = String($("#filtro_db").val() || "");
        const busqueda = normalizarTexto($("#buscar_asignacion").val());

        asignacionState.filtered = asignacionState.rows.filter(function(row) {
            if (filtroPlan && String(row.planes_id) !== filtroPlan) {
                return false;
            }

            if (filtroSistema && String(row.sistema_id) !== filtroSistema) {
                return false;
            }

            if (filtroValidar && String(row.validar) !== filtroValidar) {
                return false;
            }

            if (filtroDb && String(parseInt(row.db_disponible, 10) || 0) !== filtroDb) {
                return false;
            }

            if (busqueda) {
                const texto = normalizarTexto([
                    row.server_customers_id,
                    row.cliente_id,
                    obtenerNombreCliente(row),
                    row.cliente && row.cliente.identificacion,
                    row.cliente && row.cliente.codigo_cliente,
                    obtenerNombrePlan(row),
                    obtenerNombreSistema(row),
                    row.user_extra,
                    row.db_cliente,
                    row.db_mensaje
                ].join(" "));

                if (texto.indexOf(busqueda) === -1) {
                    return false;
                }
            }

            return true;
        });

        const totalPaginas = Math.max(
            1,
            Math.ceil(asignacionState.filtered.length / asignacionState.pageSize)
        );

        if (asignacionState.page > totalPaginas) {
            asignacionState.page = totalPaginas;
        }

        actualizarKpis();
        renderAsignaciones();
    }

    /* =========================================================
       KPIs
       ========================================================= */
    function actualizarKpis() {
        const rows = asignacionState.filtered;
        const planes = {};
        let usuariosExtra = 0;
        let basesDisponibles = 0;

        rows.forEach(function(row) {
            planes[String(row.planes_id)] = true;
            usuariosExtra += parseInt(row.user_extra, 10) || 0;

            if (parseInt(row.db_disponible, 10) === 1) {
                basesDisponibles++;
            }
        });

        $("#kpi_asignaciones_total").text(rows.length);
        $("#kpi_planes_uso").text(Object.keys(planes).length);
        $("#kpi_usuarios_extra").text(usuariosExtra);
        $("#kpi_bases_disponibles").text(basesDisponibles);
    }

    /* =========================================================
       RENDER DEL LISTADO POR DIVs
       ========================================================= */
    function renderAsignaciones() {
        const $listado = $("#asignacion_listado");
        const $empty = $("#asignacion_empty");

        $listado.empty();

        if (!asignacionState.filtered.length) {
            $empty.removeClass("d-none");
            $("#asignacion_resultado_info").text("0 registros");
            renderPaginacion(0);
            return;
        }

        $empty.addClass("d-none");

        const inicio = (asignacionState.page - 1) * asignacionState.pageSize;
        const fin = Math.min(
            inicio + asignacionState.pageSize,
            asignacionState.filtered.length
        );

        $listado
            .toggleClass("vista-miniatura", asignacionState.view === "miniatura")
            .toggleClass("vista-detalle", asignacionState.view === "detalle");

        /*
         * La vista Detalle conserva encabezados visibles aunque el listado
         * esté construido con DIVs. Esto mantiene la lectura tipo tabla
         * sin volver a depender de DataTable.
         */
        if (asignacionState.view === "detalle") {
            $listado.append(renderEncabezadoDetalle());
        }

        asignacionState.filtered
            .slice(inicio, fin)
            .forEach(function(row) {
                $listado.append(
                    asignacionState.view === "miniatura"
                        ? renderAsignacionMiniatura(row)
                        : renderAsignacionCard(row)
                );
            });

        $("#asignacion_resultado_info").text(
            "Mostrando " + (inicio + 1) + " a " + fin +
            " de " + asignacionState.filtered.length + " registros"
        );

        renderPaginacion(asignacionState.filtered.length);
        actualizarPermisosListado();
    }

    function renderEncabezadoDetalle() {
        return '' +
            '<div class="asignacion-detail-header" role="row">' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-building"></i>' +
                    '<span>Cliente</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-layer-group"></i>' +
                    '<span>Plan y Sistema</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-user-shield"></i>' +
                    '<span>Acceso</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-database"></i>' +
                    '<span>Sincronización</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-cog"></i>' +
                    '<span>Acciones</span>' +
                '</div>' +
            '</div>';
    }

    function renderAsignacionCard(row) {
        const clienteNombre = limpiarHtml(obtenerNombreCliente(row));
        const identificacion = limpiarHtml(
            row.cliente && row.cliente.identificacion
                ? row.cliente.identificacion
                : "Sin identificación"
        );
        const codigoCliente = limpiarHtml(
            row.cliente && row.cliente.codigo_cliente
                ? row.cliente.codigo_cliente
                : "Sin código"
        );

        const planNombre = limpiarHtml(obtenerNombrePlan(row));
        const sistemaNombre = limpiarHtml(obtenerNombreSistema(row));

        const plan = planMeta(row.planes_id);
        const sistema = sistemaMeta(obtenerNombreSistema(row));

        const userExtra = parseInt(row.user_extra, 10) || 0;
        const validar = parseInt(row.validar, 10) === 1;
        const activo = parseInt(row.estado, 10) === 1;
        const dbDisponible = parseInt(row.db_disponible, 10) === 1;

        const dbNombre = limpiarHtml(row.db_cliente || "No registrada");
        const dbMensaje = limpiarHtml(
            row.db_mensaje || (dbDisponible ? "Disponible" : "No disponible")
        );

        return '' +
            '<article class="asignacion-record-card">' +
                '<div class="asignacion-record-topline"></div>' +

                '<div class="asignacion-record-grid">' +

                    '<section class="asignacion-record-section asignacion-record-client">' +
                        '<div class="asignacion-main-box">' +
                            '<div class="asignacion-main-icon">' +
                                '<i class="fas fa-building"></i>' +
                            '</div>' +
                            '<div class="asignacion-main-content">' +
                                '<div class="asignacion-client-title">' +
                                    '<strong>' + clienteNombre + '</strong>' +
                                    '<span class="asignacion-status-badge ' +
                                        (activo ? 'is-active' : 'is-inactive') + '">' +
                                        '<i class="fas ' +
                                            (activo ? 'fa-check-circle' : 'fa-times-circle') +
                                        '"></i> ' +
                                        (activo ? 'Activo' : 'Inactivo') +
                                    '</span>' +
                                '</div>' +
                                '<small><i class="fas fa-id-card mr-1"></i> RTN: ' + identificacion + '</small>' +
                                '<small><i class="fas fa-barcode mr-1"></i> Código: ' + codigoCliente + '</small>' +
                            '</div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-layer-group"></i> Plan y Sistema' +
                        '</h4>' +
                        '<div class="asignacion-badge-row">' +
                            '<span class="asignacion-plan-badge ' + plan.cls + '">' +
                                '<i class="fas ' + plan.icon + '"></i> ' + planNombre +
                            '</span>' +
                            '<span class="asignacion-system-badge ' + sistema.cls + '">' +
                                '<i class="fas ' + sistema.icon + '"></i> ' + sistemaNombre +
                            '</span>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-user-shield"></i> Acceso' +
                        '</h4>' +
                        '<div class="asignacion-detail-line">' +
                            '<span class="asignacion-detail-icon"><i class="fas fa-user-plus"></i></span>' +
                            '<div><strong>Usuarios extra</strong><span>' +
                                (userExtra > 0 ? '+' + userExtra : 'Ninguno') +
                            '</span></div>' +
                        '</div>' +
                        '<div class="asignacion-detail-line">' +
                            '<span class="asignacion-detail-icon"><i class="fas fa-shield-alt"></i></span>' +
                            '<div><strong>Validar</strong><span>' +
                                (validar ? 'Sí' : 'No') +
                            '</span></div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-database"></i> Sincronización' +
                        '</h4>' +
                        '<div class="asignacion-db-name">' +
                            '<i class="fas fa-database mr-1"></i> ' + dbNombre +
                        '</div>' +
                        '<span class="asignacion-db-badge ' +
                            (dbDisponible ? 'is-ok' : 'is-error') + '">' +
                            '<i class="fas ' +
                                (dbDisponible ? 'fa-check-circle' : 'fa-exclamation-triangle') +
                            '"></i> ' +
                            (dbDisponible ? 'Disponible' : 'No disponible') +
                        '</span>' +
                        '<small class="asignacion-db-message">' + dbMensaje + '</small>' +
                        '<small class="asignacion-date">' +
                            '<i class="far fa-clock mr-1"></i> ' +
                            formatFechaHora(row.fecha_registro) +
                        '</small>' +
                    '</section>' +

                    '<section class="asignacion-record-section asignacion-record-actions">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-cog"></i> Acciones' +
                        '</h4>' +
                        '<button type="button" ' +
                            'class="btn btn-primary btn-editar-asignacion table_editar ocultar" ' +
                            'data-id="' + limpiarHtml(row.server_customers_id) + '" ' +
                            'data-cliente-id="' + limpiarHtml(row.cliente_id) + '" ' +
                            'data-plan-id="' + limpiarHtml(row.planes_id) + '" ' +
                            'data-plan-nombre="' + planNombre + '" ' +
                            'data-sistema-id="' + limpiarHtml(row.sistema_id) + '" ' +
                            'data-user-extra="' + userExtra + '" ' +
                            'data-validar="' + limpiarHtml(row.validar) + '" ' +
                            'data-estado="' + limpiarHtml(row.estado) + '">' +
                            '<i class="fas fa-edit mr-1"></i> Editar' +
                        '</button>' +
                    '</section>' +

                '</div>' +
            '</article>';
    }

    function renderAsignacionMiniatura(row) {
        const clienteNombre = limpiarHtml(obtenerNombreCliente(row));
        const identificacion = limpiarHtml(
            row.cliente && row.cliente.identificacion
                ? row.cliente.identificacion
                : "Sin identificación"
        );
        const planNombre = limpiarHtml(obtenerNombrePlan(row));
        const sistemaNombre = limpiarHtml(obtenerNombreSistema(row));
        const plan = planMeta(row.planes_id);
        const sistema = sistemaMeta(obtenerNombreSistema(row));
        const userExtra = parseInt(row.user_extra, 10) || 0;
        const validar = parseInt(row.validar, 10) === 1;
        const activo = parseInt(row.estado, 10) === 1;
        const dbDisponible = parseInt(row.db_disponible, 10) === 1;

        return '' +
            '<article class="asignacion-mini-card">' +
                '<div class="asignacion-mini-topline"></div>' +
                '<div class="asignacion-mini-head">' +
                    '<div class="asignacion-mini-identidad">' +
                        '<div class="asignacion-main-icon"><i class="fas fa-building"></i></div>' +
                        '<div>' +
                            '<strong>' + clienteNombre + '</strong>' +
                            '<small><i class="fas fa-id-card mr-1"></i> RTN: ' + identificacion + '</small>' +
                        '</div>' +
                    '</div>' +
                    '<span class="asignacion-status-badge ' +
                        (activo ? 'is-active' : 'is-inactive') + '">' +
                        '<i class="fas ' +
                            (activo ? 'fa-check-circle' : 'fa-times-circle') +
                        '"></i> ' +
                        (activo ? 'Activo' : 'Inactivo') +
                    '</span>' +
                '</div>' +

                '<div class="asignacion-mini-badges">' +
                    '<span class="asignacion-plan-badge ' + plan.cls + '">' +
                        '<i class="fas ' + plan.icon + '"></i> ' + planNombre +
                    '</span>' +
                    '<span class="asignacion-system-badge ' + sistema.cls + '">' +
                        '<i class="fas ' + sistema.icon + '"></i> ' + sistemaNombre +
                    '</span>' +
                '</div>' +

                '<div class="asignacion-mini-stats">' +
                    '<div><small>Usuarios extra</small><strong>' +
                        (userExtra > 0 ? '+' + userExtra : '0') +
                    '</strong></div>' +
                    '<div><small>Validar</small><strong>' +
                        (validar ? 'Sí' : 'No') +
                    '</strong></div>' +
                    '<div><small>Base cliente</small><strong class="' +
                        (dbDisponible ? 'text-success' : 'text-danger') + '">' +
                        (dbDisponible ? 'Disponible' : 'No disponible') +
                    '</strong></div>' +
                '</div>' +

                '<div class="asignacion-mini-footer">' +
                    '<small><i class="far fa-clock mr-1"></i>' +
                        formatFechaHora(row.fecha_registro) +
                    '</small>' +
                    '<button type="button" ' +
                        'class="btn btn-primary btn-editar-asignacion table_editar ocultar" ' +
                        'data-id="' + limpiarHtml(row.server_customers_id) + '" ' +
                        'data-cliente-id="' + limpiarHtml(row.cliente_id) + '" ' +
                        'data-plan-id="' + limpiarHtml(row.planes_id) + '" ' +
                        'data-plan-nombre="' + planNombre + '" ' +
                        'data-sistema-id="' + limpiarHtml(row.sistema_id) + '" ' +
                        'data-user-extra="' + userExtra + '" ' +
                        'data-validar="' + limpiarHtml(row.validar) + '" ' +
                        'data-estado="' + limpiarHtml(row.estado) + '">' +
                        '<i class="fas fa-edit mr-1"></i> Editar' +
                    '</button>' +
                '</div>' +
            '</article>';
    }

    function guardarTipoVista(vista) {
        try {
            localStorage.setItem(ASIGNAR_PLANES_STORAGE.vista, vista);
        } catch (e) {
            // La vista sigue funcionando aunque localStorage no esté disponible.
        }
    }

    function leerTipoVista() {
        try {
            const vista = localStorage.getItem(ASIGNAR_PLANES_STORAGE.vista);
            return vista === "miniatura" ? "miniatura" : "detalle";
        } catch (e) {
            return "detalle";
        }
    }

    function actualizarBotonesVista() {
        $(".asignacion-view-btn").removeClass("active");
        $(".asignacion-view-btn[data-view='" + asignacionState.view + "']")
            .addClass("active");
    }

    function sincronizarTamanoPaginaAsignacion() {
        const $select = $("#asignacion_page_size");
        const esMiniatura = asignacionState.view === "miniatura";
        const opciones = esMiniatura ? [6, 12, 18, 30] : [5, 10, 20, 50];
        const seleccionado = esMiniatura
            ? asignacionState.pageSizeMiniatura
            : asignacionState.pageSizeDetalle;

        $select.empty();

        opciones.forEach(function(valor) {
            $select.append(
                '<option value="' + valor + '">' + valor + '</option>'
            );
        });

        asignacionState.pageSize = opciones.indexOf(seleccionado) !== -1
            ? seleccionado
            : opciones[0];

        $select.val(String(asignacionState.pageSize));
    }

    /* =========================================================
       PAGINACIÓN
       ========================================================= */
    function renderPaginacion(totalRegistros) {
        const $nav = $("#asignacion_paginacion");
        $nav.empty();

        const totalPaginas = Math.max(
            1,
            Math.ceil(totalRegistros / asignacionState.pageSize)
        );

        if (totalRegistros <= 0) {
            return;
        }

        function boton(label, page, disabled, active, icon) {
            return '' +
                '<button type="button" ' +
                    'class="asignacion-page-btn' +
                        (disabled ? ' disabled' : '') +
                        (active ? ' active' : '') + '" ' +
                    'data-page="' + page + '" ' +
                    (disabled ? 'disabled' : '') + '>' +
                    (icon ? '<i class="fas ' + icon + '"></i>' : '') +
                    '<span>' + label + '</span>' +
                '</button>';
        }

        $nav.append(
            boton(
                "Inicio",
                1,
                asignacionState.page === 1,
                false,
                "fa-angle-double-left"
            )
        );

        $nav.append(
            boton(
                "Anterior",
                Math.max(1, asignacionState.page - 1),
                asignacionState.page === 1,
                false,
                "fa-angle-left"
            )
        );

        let desde = Math.max(1, asignacionState.page - 2);
        let hasta = Math.min(totalPaginas, desde + 4);

        desde = Math.max(1, hasta - 4);

        for (let pagina = desde; pagina <= hasta; pagina++) {
            $nav.append(
                boton(
                    String(pagina),
                    pagina,
                    false,
                    pagina === asignacionState.page,
                    ""
                )
            );
        }

        $nav.append(
            boton(
                "Siguiente",
                Math.min(totalPaginas, asignacionState.page + 1),
                asignacionState.page === totalPaginas,
                false,
                "fa-angle-right"
            )
        );

        $nav.append(
            boton(
                "Final",
                totalPaginas,
                asignacionState.page === totalPaginas,
                false,
                "fa-angle-double-right"
            )
        );
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
                                'data-subtext="' +
                                limpiarHtml(cliente.identificacion || "Sin identificación") +
                                '">' +
                                limpiarHtml(cliente.nombre) +
                            '</option>'
                        );
                    });

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }
                } else {
                    showNotify(
                        "error",
                        "Error",
                        response.message || "Error al cargar clientes"
                    );
                }
            },
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error de conexión al cargar clientes"
                );
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

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }
                } else {
                    showNotify(
                        "error",
                        "Error",
                        response.message || "Error al cargar planes"
                    );
                }
            },
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error de conexión al cargar planes"
                );
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

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }

                    select.prop("disabled", true);
                }
            },
            error: function(xhr) {
                console.error("Error al cargar sistemas:", xhr.responseText);
            }
        });
    }

    /* =========================================================
       VERIFICAR PLAN DEL CLIENTE
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
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error al verificar plan del cliente"
                );
            }
        });
    }

    function aplicarDatosPlanAlFormulario(data) {
        $("#server_customers_id").val(data.server_customers_id || "");
        $("#planes_id").val(data.planes_id || "").selectpicker("refresh");
        $("#sistema_id").val(data.sistema_id || "").selectpicker("refresh");
        $("#user_extra").val(parseInt(data.user_extra, 10) || 0);
        $("#validar").val(data.validar).selectpicker("refresh");
        $("#estado").val(data.estado).selectpicker("refresh");

        const nombrePlan = $("#planes_id option:selected").text().trim();

        $("#formAsignacionPlan")
            .data("plan-actual-id", String(data.planes_id || ""))
            .data("plan-actual-nombre", nombrePlan || "Sin plan");
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
                    cargarAsignaciones(true);
                    resetFormularioAsignacion();
                }
            },
            error: function(xhr) {
                restaurarBotonActualizarPlan();

                console.error("Error al actualizar plan:", xhr.responseText);

                let mensaje = "Error al actualizar plan";

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }

                showNotify("error", "Error", mensaje);
            }
        });
    }

    /* =========================================================
       CONFIRMACIÓN PREMIUM DE CAMBIO DE PLAN
       ========================================================= */
    function construirContenidoConfirmacion(datos) {
        const contenedor = document.createElement("div");
        contenedor.className = "asignacion-swal-content";

        contenedor.innerHTML =
            '<div class="asignacion-swal-client">' +
                '<div class="asignacion-swal-client-icon">' +
                    '<i class="fas fa-building"></i>' +
                '</div>' +
                '<div class="asignacion-swal-client-info">' +
                    '<small>CLIENTE</small>' +
                    '<strong>' + limpiarHtml(datos.cliente) + '</strong>' +
                    '<span>Revise la información antes de confirmar el cambio.</span>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-change">' +
                '<div class="asignacion-swal-plan-box">' +
                    '<small>PLAN ACTUAL</small>' +
                    '<strong>' + limpiarHtml(datos.planActual) + '</strong>' +
                '</div>' +
                '<div class="asignacion-swal-arrow" aria-hidden="true">' +
                    '<i class="fas fa-arrow-right"></i>' +
                '</div>' +
                '<div class="asignacion-swal-plan-box is-new">' +
                    '<small>NUEVO PLAN</small>' +
                    '<strong>' + limpiarHtml(datos.planNuevo) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-grid">' +
                '<div>' +
                    '<small><i class="fas fa-cubes"></i> Sistema</small>' +
                    '<strong>' + limpiarHtml(datos.sistema) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-user-plus"></i> Usuarios extra</small>' +
                    '<strong>' + limpiarHtml(datos.usuariosExtra) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-shield-alt"></i> Validación</small>' +
                    '<strong>' + limpiarHtml(datos.validar) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-toggle-on"></i> Estado</small>' +
                    '<strong>' + limpiarHtml(datos.estado) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-notice">' +
                '<i class="fas fa-sync-alt"></i>' +
                '<div>' +
                    '<strong>¿Qué ocurrirá al confirmar?</strong>' +
                    '<span>IZZY actualizará la asignación y sincronizará el cambio en la base principal y en la base del cliente, conservando la configuración correspondiente al plan seleccionado.</span>' +
                '</div>' +
            '</div>';

        return contenedor;
    }

    function confirmarCambioPlan(datos, callback) {
        if (typeof swal !== "function") {
            callback(true);
            return;
        }

        const confirmacion = swal({
            title: "Confirmar cambio de plan",
            content: construirContenidoConfirmacion(datos),
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
                    text: "Sí, actualizar plan",
                    value: true,
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: true
                }
            },
            dangerMode: false,
            closeOnEsc: true,
            closeOnClickOutside: false
        });

        setTimeout(function() {
            $(".asignacion-swal-content")
                .closest(".swal-modal")
                .addClass("asignacion-swal-modal");
        }, 0);

        confirmacion.then(function(confirmado) {
            callback(confirmado === true);
        });
    }

    /* =========================================================
       REPORTES - EXCEL / PDF
       ========================================================= */
    function asignacionFechaReporte() {
        return new Date().toLocaleDateString("es-HN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        });
    }

    function asignacionExportRows() {
        return asignacionState.filtered.map(function(row) {
            return {
                cliente: obtenerNombreCliente(row),
                rtn: row.cliente && row.cliente.identificacion
                    ? row.cliente.identificacion
                    : "Sin identificación",
                plan: obtenerNombrePlan(row),
                sistema: obtenerNombreSistema(row),
                usuariosExtra: parseInt(row.user_extra, 10) || 0,
                validar: parseInt(row.validar, 10) === 1 ? "Sí" : "No",
                baseCliente: parseInt(row.db_disponible, 10) === 1
                    ? "Disponible"
                    : "No disponible",
                estado: parseInt(row.estado, 10) === 1 ? "Activo" : "Inactivo",
                fecha: formatFechaHora(row.fecha_registro)
            };
        });
    }

    function asignacionDescargarBlob(contenido, nombre, tipo, yaEsBlob) {
        const blob = yaEsBlob
            ? contenido
            : new Blob([contenido], {type: tipo});

        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.download = nombre;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }

    function asignacionXmlEscape(value) {
        return String(value === null || typeof value === "undefined" ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&apos;");
    }

    function asignacionExcelColName(index) {
        let name = "";
        let n = index + 1;

        while (n > 0) {
            const mod = (n - 1) % 26;
            name = String.fromCharCode(65 + mod) + name;
            n = Math.floor((n - 1) / 26);
        }

        return name;
    }

    function asignacionExcelCell(ref, value, styleId, numeric) {
        if (numeric) {
            const numero = Number(value);

            if (!isNaN(numero)) {
                return '<c r="' + ref + '" s="' + styleId + '"><v>' +
                    numero +
                '</v></c>';
            }
        }

        const raw = String(value === null || typeof value === "undefined" ? "" : value);
        const preserve = /^\s|\s$/.test(raw)
            ? ' xml:space="preserve"'
            : "";

        return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
            '<is><t' + preserve + '>' +
                asignacionXmlEscape(raw) +
            '</t></is></c>';
    }

    function asignacionGenerarXlsx(rows) {
        if (typeof JSZip === "undefined") {
            return null;
        }

        const headers = [
            "Cliente",
            "RTN",
            "Plan",
            "Sistema",
            "Usuarios Extra",
            "Validar",
            "Base Cliente",
            "Estado",
            "Fecha"
        ];

        const totalActivos = rows.filter(function(row) {
            return row.estado === "Activo";
        }).length;

        const totalBases = rows.filter(function(row) {
            return row.baseCliente === "Disponible";
        }).length;

        const totalUsuariosExtra = rows.reduce(function(acc, row) {
            return acc + Number(row.usuariosExtra || 0);
        }, 0);

        const planes = {};
        rows.forEach(function(row) {
            planes[row.plan] = true;
        });

        const dataRows = rows.map(function(row) {
            return [
                row.cliente,
                row.rtn,
                row.plan,
                row.sistema,
                row.usuariosExtra,
                row.validar,
                row.baseCliente,
                row.estado,
                row.fecha
            ];
        });

        const lastCol = asignacionExcelColName(headers.length - 1);
        const headerRow = 7;
        const firstDataRow = 8;
        const lastRow = Math.max(headerRow, headerRow + dataRows.length);
        const sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">' +
                asignacionExcelCell(
                    "A1",
                    "IZZY • REPORTE DE ASIGNACIÓN DE PLANES",
                    1,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="2" ht="20" customHeight="1">' +
                asignacionExcelCell(
                    "A2",
                    "Control de planes, sistemas y sincronización • Generado: " +
                        asignacionFechaReporte(),
                    2,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="3" ht="18" customHeight="1">' +
                asignacionExcelCell("A3", "REGISTROS", 6, false) +
                asignacionExcelCell("C3", "ACTIVOS", 6, false) +
                asignacionExcelCell("E3", "PLANES EN USO", 6, false) +
                asignacionExcelCell("G3", "BASES DISPONIBLES", 6, false) +
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">' +
                asignacionExcelCell("A4", rows.length, 7, true) +
                asignacionExcelCell("C4", totalActivos, 7, true) +
                asignacionExcelCell("E4", Object.keys(planes).length, 7, true) +
                asignacionExcelCell("G4", totalBases, 7, true) +
                asignacionExcelCell("I4", totalUsuariosExtra, 7, true) +
            '</row>'
        );

        sheetRows.push('<row r="5"></row>');
        sheetRows.push(
            '<row r="6" ht="18" customHeight="1">' +
                asignacionExcelCell(
                    "A6",
                    "Detalle de asignaciones filtradas",
                    8,
                    false
                ) +
            '</row>'
        );

        const headerCells = headers.map(function(header, index) {
            return asignacionExcelCell(
                asignacionExcelColName(index) + headerRow,
                header,
                3,
                false
            );
        }).join("");

        sheetRows.push(
            '<row r="' + headerRow + '" ht="26" customHeight="1">' +
                headerCells +
            '</row>'
        );

        dataRows.forEach(function(row, rowIndex) {
            const excelRow = firstDataRow + rowIndex;

            const cells = row.map(function(value, colIndex) {
                const numeric = colIndex === 4;
                let style = 4;

                if (colIndex === 6) {
                    style = value === "Disponible" ? 9 : 10;
                } else if (colIndex === 7) {
                    style = value === "Activo" ? 9 : 10;
                } else if (colIndex === 4) {
                    style = 5;
                }

                return asignacionExcelCell(
                    asignacionExcelColName(colIndex) + excelRow,
                    value,
                    style,
                    numeric
                );
            }).join("");

            sheetRows.push(
                '<row r="' + excelRow + '" ht="22" customHeight="1">' +
                    cells +
                '</row>'
            );
        });

        const sheetXml =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
                '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView></sheetViews>' +
                '<sheetFormatPr defaultRowHeight="15"/>' +
                '<cols>' +
                    '<col min="1" max="1" width="34" customWidth="1"/>' +
                    '<col min="2" max="2" width="20" customWidth="1"/>' +
                    '<col min="3" max="4" width="19" customWidth="1"/>' +
                    '<col min="5" max="5" width="15" customWidth="1"/>' +
                    '<col min="6" max="6" width="13" customWidth="1"/>' +
                    '<col min="7" max="8" width="19" customWidth="1"/>' +
                    '<col min="9" max="9" width="22" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join("") + '</sheetData>' +
                '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
                '<mergeCells count="10">' +
                    '<mergeCell ref="A1:I1"/>' +
                    '<mergeCell ref="A2:I2"/>' +
                    '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                    '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                    '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                    '<mergeCell ref="G3:I3"/><mergeCell ref="G4:H4"/>' +
                '</mergeCells>' +
                '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
                '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
            '</worksheet>';

        const stylesXml =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<fonts count="7">' +
                    '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                    '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="9"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '</fonts>' +
                '<fills count="7">' +
                    '<fill><patternFill patternType="none"/></fill>' +
                    '<fill><patternFill patternType="gray125"/></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill>' +
                '</fills>' +
                '<borders count="2">' +
                    '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                    '<border>' +
                        '<left style="thin"><color rgb="FFDDE3EA"/></left>' +
                        '<right style="thin"><color rgb="FFDDE3EA"/></right>' +
                        '<top style="thin"><color rgb="FFDDE3EA"/></top>' +
                        '<bottom style="thin"><color rgb="FFDDE3EA"/></bottom>' +
                        '<diagonal/>' +
                    '</border>' +
                '</borders>' +
                '<cellStyleXfs count="1">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' +
                '</cellStyleXfs>' +
                '<cellXfs count="11">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>';

        const workbookXml =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<bookViews><workbookView activeTab="0"/></bookViews>' +
                '<sheets><sheet name="Asignaciones" sheetId="1" r:id="rId1"/></sheets>' +
            '</workbook>';

        const workbookRels =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" ' +
                    'Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" ' +
                    'Target="styles.xml"/>' +
            '</Relationships>';

        const rootRels =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" ' +
                    'Target="xl/workbook.xml"/>' +
            '</Relationships>';

        const contentTypes =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
                '<Default Extension="xml" ContentType="application/xml"/>' +
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
            '</Types>';

        const zip = new JSZip();

        zip.file("[Content_Types].xml", contentTypes);
        zip.folder("_rels").file(".rels", rootRels);
        zip.folder("xl").file("workbook.xml", workbookXml);
        zip.folder("xl").file("styles.xml", stylesXml);
        zip.folder("xl").folder("_rels").file("workbook.xml.rels", workbookRels);
        zip.folder("xl").folder("worksheets").file("sheet1.xml", sheetXml);

        const opciones = {
            type: "blob",
            mimeType: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            compression: "DEFLATE"
        };

        if (typeof zip.generateAsync === "function") {
            return zip.generateAsync(opciones);
        }

        if (typeof zip.generate === "function") {
            try {
                return Promise.resolve(zip.generate(opciones));
            } catch (errorGenerate) {
                return Promise.reject(errorGenerate);
            }
        }

        return Promise.reject(
            new Error("La versión de JSZip no soporta la generación de XLSX.")
        );
    }

    function exportarAsignacionesExcel() {
        if (!asignacionState.filtered.length) {
            showNotify(
                "warning",
                "Sin información",
                "No hay asignaciones para exportar."
            );
            return;
        }

        const rows = asignacionExportRows();
        const promesaXlsx = asignacionGenerarXlsx(rows);

        if (!promesaXlsx) {
            const headers = [
                "Cliente",
                "RTN",
                "Plan",
                "Sistema",
                "Usuarios Extra",
                "Validar",
                "Base Cliente",
                "Estado",
                "Fecha"
            ];

            const csv = [headers].concat(
                rows.map(function(row) {
                    return [
                        row.cliente,
                        row.rtn,
                        row.plan,
                        row.sistema,
                        row.usuariosExtra,
                        row.validar,
                        row.baseCliente,
                        row.estado,
                        row.fecha
                    ];
                })
            ).map(function(line) {
                return line.map(function(value) {
                    const texto = String(
                        value === null || typeof value === "undefined"
                            ? ""
                            : value
                    ).replace(/"/g, '""');

                    return '"' + texto + '"';
                }).join(",");
            }).join("\r\n");

            asignacionDescargarBlob(
                "\ufeff" + csv,
                "Reporte_Asignacion_Planes.csv",
                "text/csv;charset=utf-8;"
            );

            showNotify(
                "warning",
                "Excel compatible",
                "JSZip no está disponible; se generó un CSV compatible con Excel."
            );
            return;
        }

        promesaXlsx
            .then(function(blob) {
                asignacionDescargarBlob(
                    blob,
                    "Reporte_Asignacion_Planes.xlsx",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    true
                );
            })
            .catch(function(error) {
                console.error("Error al generar XLSX:", error);

                showNotify(
                    "error",
                    "Error",
                    "No se pudo generar el archivo Excel."
                );
            });
    }

    function asignacionPdfDato(label, value, color) {
        return {
            stack: [
                {
                    text: String(label || "").toUpperCase(),
                    fontSize: 6.4,
                    bold: true,
                    color: "#6B778C",
                    margin: [0, 0, 0, 2]
                },
                {
                    text: String(
                        value === null || typeof value === "undefined" || value === ""
                            ? "—"
                            : value
                    ),
                    fontSize: 8.1,
                    bold: true,
                    color: color || "#172B4D"
                }
            ]
        };
    }

    function asignacionPdfEncabezado(rows, totalActivos, totalBases, totalUsuariosExtra, totalPlanes) {
        const logo = (typeof imagen !== "undefined" && imagen)
            ? {image: imagen, width: 50, height: 24, alignment: "center", margin: [0, 1, 0, 0]}
            : {text: "IZZY", fontSize: 16, bold: true, color: "#FFFFFF", alignment: "center", margin: [0, 4, 0, 0]};

        const filtroPlan = $("#filtro_plan option:selected").text() || "Todos";
        const filtroSistema = $("#filtro_sistema option:selected").text() || "Todos";
        const filtroEstado = $("#filtro_estado option:selected").text() || "Todos";

        return [
            {
                table: {
                    widths: [70, "*", 150],
                    body: [[
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [12, 10, 0, 10],
                            stack: [logo]
                        },
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [0, 10, 0, 10],
                            stack: [
                                {text: "REPORTE DE ASIGNACIÓN DE PLANES", fontSize: 16, bold: true, color: "#FFFFFF"},
                                {text: "Control de planes, sistemas, accesos y sincronización de clientes", fontSize: 7.5, color: "#D8E5F0", margin: [0, 2, 0, 0]}
                            ]
                        },
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [0, 10, 12, 10],
                            stack: [
                                {text: "REPORTE EJECUTIVO", fontSize: 6.5, bold: true, color: "#72E2E5", alignment: "right"},
                                {text: asignacionFechaReporte(), fontSize: 9, bold: true, color: "#FFFFFF", alignment: "right", margin: [0, 3, 0, 0]},
                                {text: rows.length + " registro(s) filtrado(s)", fontSize: 6.5, color: "#D8E5F0", alignment: "right", margin: [0, 2, 0, 0]}
                            ]
                        }
                    ]]
                },
                layout: {hLineWidth: function(){return 0;}, vLineWidth: function(){return 0;}},
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*"],
                    body: [[{
                        text: "Filtros aplicados: Plan: " + filtroPlan + "   |   Sistema: " + filtroSistema + "   |   Estado: " + filtroEstado,
                        fontSize: 6.8,
                        color: "#52627A",
                        margin: [10, 7, 10, 7],
                        fillColor: "#F7F9FC"
                    }]]
                },
                layout: {
                    hLineColor: function(){return "#DDE3EA";},
                    vLineColor: function(){return "#DDE3EA";},
                    hLineWidth: function(){return 0.6;},
                    vLineWidth: function(){return 0.6;}
                },
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*", "*", "*", "*", "*"],
                    body: [[
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"REGISTROS",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(rows.length),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"ACTIVOS",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalActivos),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"PLANES EN USO",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalPlanes),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"USUARIOS EXTRA",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalUsuariosExtra),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"BASES DISPONIBLES",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalBases),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]}
                    ]]
                },
                layout: {
                    hLineColor: function(){return "#DDE3EA";},
                    vLineColor: function(){return "#DDE3EA";},
                    hLineWidth: function(){return 0.6;},
                    vLineWidth: function(){return 0.6;}
                },
                margin: [0, 0, 0, 12]
            }
        ];
    }

    function asignacionPdfMiniaturaCard(row) {
        return {
            table: {
                widths: ["*"],
                body: [[{
                    margin: [10, 9, 10, 9],
                    stack: [
                        {
                            columns: [
                                {
                                    width: "*",
                                    stack: [
                                        {
                                            text: row.cliente,
                                            fontSize: 10,
                                            bold: true,
                                            color: "#172B4D"
                                        },
                                        {
                                            text: "RTN: " + row.rtn,
                                            fontSize: 7,
                                            color: "#6B778C",
                                            margin: [0, 2, 0, 0]
                                        }
                                    ]
                                },
                                {
                                    width: "auto",
                                    text: row.estado,
                                    fontSize: 7.2,
                                    bold: true,
                                    color: row.estado === "Activo"
                                        ? "#14804A"
                                        : "#C9372C",
                                    alignment: "right"
                                }
                            ]
                        },
                        {
                            canvas: [{
                                type: "line",
                                x1: 0,
                                y1: 0,
                                x2: 235,
                                y2: 0,
                                lineWidth: 0.6,
                                lineColor: "#DDE3EA"
                            }],
                            margin: [0, 7, 0, 7]
                        },
                        {
                            columns: [
                                {
                                    width: "50%",
                                    stack: [
                                        asignacionPdfDato("Plan", row.plan),
                                        {
                                            margin: [0, 7, 0, 0],
                                            stack: [
                                                asignacionPdfDato("Sistema", row.sistema)
                                            ]
                                        }
                                    ]
                                },
                                {
                                    width: "50%",
                                    stack: [
                                        asignacionPdfDato("Usuarios extra", row.usuariosExtra),
                                        {
                                            margin: [0, 7, 0, 0],
                                            columns: [
                                                {
                                                    width: "45%",
                                                    stack: [
                                                        asignacionPdfDato("Validar", row.validar)
                                                    ]
                                                },
                                                {
                                                    width: "55%",
                                                    stack: [
                                                        asignacionPdfDato(
                                                            "Base cliente",
                                                            row.baseCliente,
                                                            row.baseCliente === "Disponible"
                                                                ? "#14804A"
                                                                : "#C9372C"
                                                        )
                                                    ]
                                                }
                                            ]
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            text: "Actualización: " + row.fecha,
                            fontSize: 6.6,
                            color: "#7A869A",
                            margin: [0, 8, 0, 0]
                        }
                    ]
                }]]
            },
            layout: {
                hLineColor: function() { return "#DDE3EA"; },
                vLineColor: function() { return "#DDE3EA"; },
                hLineWidth: function() { return 0.7; },
                vLineWidth: function() { return 0.7; }
            }
        };
    }

    function asignacionPdfContenidoMiniatura(rows) {
        const contenido = [
            {
                text: "VISTA MINIATURA",
                fontSize: 6.8,
                bold: true,
                color: "#17324D",
                margin: [0, 1, 0, 7]
            }
        ];

        for (let i = 0; i < rows.length; i += 2) {
            const columnas = [
                {
                    width: "*",
                    stack: [asignacionPdfMiniaturaCard(rows[i])]
                }
            ];

            if (rows[i + 1]) {
                columnas.push({width: 10, text: ""});
                columnas.push({
                    width: "*",
                    stack: [asignacionPdfMiniaturaCard(rows[i + 1])]
                });
            } else {
                columnas.push({width: 10, text: ""});
                columnas.push({width: "*", text: ""});
            }

            contenido.push({
                columns: columnas,
                columnGap: 0,
                margin: [0, 0, 0, 9]
            });
        }

        return contenido;
    }

    function asignacionPdfContenidoDetalle(rows) {
        const body = [[
            {text: "CLIENTE", style: "th", fillColor: "#17324D"},
            {text: "RTN", style: "th", fillColor: "#17324D"},
            {text: "PLAN", style: "th", fillColor: "#17324D"},
            {text: "SISTEMA", style: "th", fillColor: "#17324D"},
            {text: "USUARIOS EXTRA", style: "th", fillColor: "#17324D"},
            {text: "VALIDAR", style: "th", fillColor: "#17324D"},
            {text: "BASE CLIENTE", style: "th", fillColor: "#17324D"},
            {text: "ESTADO", style: "th", fillColor: "#17324D"},
            {text: "ACTUALIZACIÓN", style: "th", fillColor: "#17324D"}
        ]];

        rows.forEach(function(row) {
            body.push([
                {text: row.cliente, style: "tdStrong"},
                {text: row.rtn, style: "td"},
                {text: row.plan, style: "td"},
                {text: row.sistema, style: "td"},
                {text: String(row.usuariosExtra), style: "tdCenter"},
                {text: row.validar, style: "tdCenter"},
                {
                    text: row.baseCliente,
                    style: "tdCenter",
                    color: row.baseCliente === "Disponible"
                        ? "#14804A"
                        : "#C9372C",
                    bold: true
                },
                {
                    text: row.estado,
                    style: "tdCenter",
                    color: row.estado === "Activo"
                        ? "#14804A"
                        : "#C9372C",
                    bold: true
                },
                {text: row.fecha, style: "td"}
            ]);
        });

        return [
            {
                text: "VISTA DETALLE",
                fontSize: 6.8,
                bold: true,
                color: "#17324D",
                margin: [0, 1, 0, 7]
            },
            {
                table: {
                    headerRows: 1,
                    widths: [92, 66, 66, 54, 48, 42, 66, 46, "*"],
                    body: body
                },
                layout: {
                    fillColor: function(rowIndex) {
                        if (rowIndex === 0) {
                            return "#17324D";
                        }

                        return rowIndex % 2 === 0
                            ? "#F7F9FC"
                            : "#FFFFFF";
                    },
                    hLineColor: function() { return "#DDE3EA"; },
                    vLineColor: function() { return "#DDE3EA"; },
                    hLineWidth: function() { return 0.6; },
                    vLineWidth: function() { return 0.6; },
                    paddingLeft: function() { return 5; },
                    paddingRight: function() { return 5; },
                    paddingTop: function() { return 5; },
                    paddingBottom: function() { return 5; }
                }
            }
        ];
    }

    function exportarAsignacionesPDF() {
        if (!asignacionState.filtered.length) {
            showNotify(
                "warning",
                "Sin información",
                "No hay asignaciones para exportar."
            );
            return;
        }

        if (typeof pdfMake === "undefined") {
            showNotify(
                "warning",
                "PDF no disponible",
                "La librería PDF no está cargada en esta pantalla."
            );
            return;
        }

        const rows = asignacionExportRows();

        const totalActivos = rows.filter(function(row) {
            return row.estado === "Activo";
        }).length;

        const totalBases = rows.filter(function(row) {
            return row.baseCliente === "Disponible";
        }).length;

        const totalUsuariosExtra = rows.reduce(function(acc, row) {
            return acc + Number(row.usuariosExtra || 0);
        }, 0);

        const planes = {};

        rows.forEach(function(row) {
            planes[row.plan] = true;
        });

        const esMiniatura = asignacionState.view === "miniatura";

        const contenido = asignacionPdfEncabezado(
            rows,
            totalActivos,
            totalBases,
            totalUsuariosExtra,
            Object.keys(planes).length
        ).concat(
            esMiniatura
                ? asignacionPdfContenidoMiniatura(rows)
                : asignacionPdfContenidoDetalle(rows)
        );

        const docDefinition = {
            pageSize: "LETTER",
            pageOrientation: "landscape",
            pageMargins: [32, 34, 32, 34],
            header: function() {
                return {
                    margin: [32, 14, 32, 0],
                    canvas: [{
                        type: "line",
                        x1: 0,
                        y1: 0,
                        x2: 724,
                        y2: 0,
                        lineWidth: 2,
                        lineColor: "#0EA5A8"
                    }]
                };
            },
            footer: function(currentPage, pageCount) {
                return {
                    margin: [32, 8, 32, 0],
                    columns: [
                        {
                            text: "IZZY • Asignación de Planes",
                            fontSize: 7,
                            color: "#7A869A"
                        },
                        {
                            text: "Página " + currentPage + " de " + pageCount,
                            fontSize: 7,
                            color: "#7A869A",
                            alignment: "right"
                        }
                    ]
                };
            },
            content: contenido,
            styles: {
                th: {
                    fontSize: 6.4,
                    bold: true,
                    color: "#FFFFFF",
                    alignment: "center"
                },
                td: {
                    fontSize: 6.5,
                    color: "#253858",
                    margin: [0, 1, 0, 1]
                },
                tdStrong: {
                    fontSize: 6.5,
                    bold: true,
                    color: "#172B4D",
                    margin: [0, 1, 0, 1]
                },
                tdCenter: {
                    fontSize: 6.5,
                    color: "#253858",
                    alignment: "center",
                    margin: [0, 1, 0, 1]
                }
            },
            defaultStyle: {
                fontSize: 8,
                color: "#253858"
            }
        };

        const pdf = pdfMake.createPdf(docDefinition);
        const nombrePdf = "Reporte_Asignacion_Planes.pdf";

        if (typeof abrirModalPdfPublico !== "function") {
            showNotify(
                "error",
                "Visor PDF no disponible",
                "No se encontró el modal PDF público."
            );
            return;
        }

        if (typeof pdf.getDataUrl === "function") {
            pdf.getDataUrl(function(dataUrl) {
                abrirModalPdfPublico(
                    dataUrl,
                    "Asignación de Planes",
                    nombrePdf
                );
            });
            return;
        }

        if (typeof pdf.getBase64 === "function") {
            pdf.getBase64(function(base64) {
                abrirModalPdfPublico(
                    "data:application/pdf;base64," + base64,
                    "Asignación de Planes",
                    nombrePdf
                );
            });
            return;
        }

        showNotify(
            "error",
            "PDF no disponible",
            "La versión actual de pdfMake no permite una vista previa compatible."
        );
    }

    /* =========================================================
       EVENTOS - LISTADO
       ========================================================= */
    $("#btn_actualizar_asignaciones")
        .off("click.asignacion")
        .on("click.asignacion", function() {
            cargarAsignaciones(true);
        });

    $("#btn_exportar_asignaciones_excel")
        .off("click.asignacionReporte")
        .on("click.asignacionReporte", exportarAsignacionesExcel);

    $("#btn_exportar_asignaciones_pdf")
        .off("click.asignacionReporte")
        .on("click.asignacionReporte", exportarAsignacionesPDF);

    $("#formFiltrosAsignacion")
        .off("submit.asignacion")
        .on("submit.asignacion", function(e) {
            e.preventDefault();
            asignacionState.page = 1;
            aplicarFiltrosYRender();
        });

    $("#formFiltrosAsignacion")
        .off("reset.asignacion")
        .on("reset.asignacion", function() {
            const form = this;

            setTimeout(function() {
                $(form).find(".selectpicker").val("").selectpicker("refresh");
                asignacionState.page = 1;
                aplicarFiltrosYRender();
            }, 50);
        });

    $("#filtro_plan, #filtro_sistema, #filtro_validar, #filtro_db")
        .off("changed.bs.select.asignacion change.asignacion")
        .on("changed.bs.select.asignacion change.asignacion", function() {
            asignacionState.page = 1;
            aplicarFiltrosYRender();
        });

    $("#buscar_asignacion")
        .off("input.asignacion")
        .on("input.asignacion", function() {
            clearTimeout(asignacionDebounceTimer);

            asignacionDebounceTimer = setTimeout(function() {
                asignacionState.page = 1;
                aplicarFiltrosYRender();
            }, 180);
        });

    $("#asignacion_page_size")
        .off("change.asignacion")
        .on("change.asignacion", function() {
            const value = parseInt($(this).val(), 10);

            asignacionState.pageSize = isNaN(value) || value <= 0
                ? (asignacionState.view === "miniatura" ? 12 : 10)
                : value;

            if (asignacionState.view === "miniatura") {
                asignacionState.pageSizeMiniatura = asignacionState.pageSize;
            } else {
                asignacionState.pageSizeDetalle = asignacionState.pageSize;
            }

            asignacionState.page = 1;
            renderAsignaciones();
        });

    $(".asignacion-view-btn")
        .off("click.asignacionVista")
        .on("click.asignacionVista", function() {
            const vista = String($(this).data("view") || "detalle");

            asignacionState.view = vista === "miniatura"
                ? "miniatura"
                : "detalle";

            guardarTipoVista(asignacionState.view);
            actualizarBotonesVista();
            sincronizarTamanoPaginaAsignacion();
            asignacionState.page = 1;
            renderAsignaciones();
        });

    $("#asignacion_paginacion")
        .off("click.asignacion")
        .on("click.asignacion", "button[data-page]", function() {
            if ($(this).prop("disabled")) {
                return;
            }

            const page = parseInt($(this).data("page"), 10);

            if (!isNaN(page)) {
                asignacionState.page = page;
                renderAsignaciones();
            }
        });

    $(document)
        .off("click.asignacionEditar", ".btn-editar-asignacion")
        .on("click.asignacionEditar", ".btn-editar-asignacion", function() {
            $("#server_customers_id").val($(this).data("id"));
            $("#cliente_id").val($(this).data("cliente-id")).selectpicker("refresh");
            $("#planes_id").val($(this).data("plan-id")).selectpicker("refresh");
            $("#sistema_id").val($(this).data("sistema-id")).selectpicker("refresh");
            $("#user_extra").val($(this).data("user-extra"));
            $("#validar").val($(this).data("validar")).selectpicker("refresh");
            $("#estado").val($(this).data("estado")).selectpicker("refresh");

            $("#formAsignacionPlan")
                .data("plan-actual-id", String($(this).data("plan-id")))
                .data(
                    "plan-actual-nombre",
                    String($(this).data("plan-nombre") || "Sin plan")
                );

            if ($("#div_top").length > 0) {
                $("html, body").animate({
                    scrollTop: $("#div_top").offset().top - 20
                }, 350);
            }
        });

    /* =========================================================
       CAMBIO DE CLIENTE
       ========================================================= */
    $("#cliente_id")
        .off("changed.bs.select.asignacion change.asignacion")
        .on("changed.bs.select.asignacion change.asignacion", function() {
            const clienteId = $(this).val();

            if (!clienteId) {
                $("#server_customers_id").val("");
                $("#user_extra").val(0);

                $("#formAsignacionPlan")
                    .removeData("plan-actual-id")
                    .removeData("plan-actual-nombre");

                return;
            }

            verificarPlanCliente(clienteId, function(response) {
                if (!response || response.success === false) {
                    showNotify(
                        "error",
                        "Error",
                        response && response.message
                            ? response.message
                            : "No se pudo verificar el plan del cliente"
                    );
                    return;
                }

                if (response.exists && response.data) {
                    aplicarDatosPlanAlFormulario(response.data);
                } else {
                    showNotify(
                        "warning",
                        "Sin asignación",
                        "El cliente seleccionado no tiene una asignación de plan registrada."
                    );
                }
            });
        });

    /* =========================================================
       SUBMIT FORMULARIO
       ========================================================= */
    $("#formAsignacionPlan")
        .off("submit.asignacion")
        .on("submit.asignacion", function(e) {
            e.preventDefault();

            const clienteId = $("#cliente_id").val();
            const serverCustomersId = $("#server_customers_id").val();
            const planesId = $("#planes_id").val();
            const userExtra = parseInt($("#user_extra").val(), 10);

            if (!clienteId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Debe seleccionar un cliente"
                );
                return;
            }

            if (!serverCustomersId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "El cliente seleccionado no tiene registro server_customers"
                );
                return;
            }

            if (!planesId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Debe seleccionar un plan"
                );
                return;
            }

            if (isNaN(userExtra) || userExtra < 0) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Los usuarios extra no pueden ser negativos"
                );
                return;
            }

            const datos = {
                cliente: $("#cliente_id option:selected").text().trim(),
                planActual:
                    $("#formAsignacionPlan").data("plan-actual-nombre") ||
                    "No determinado",
                planNuevo: $("#planes_id option:selected").text().trim(),
                sistema: $("#sistema_id option:selected").text().trim(),
                usuariosExtra: userExtra === 0 ? "Ninguno" : "+" + userExtra,
                validar: $("#validar option:selected").text().trim(),
                estado: $("#estado option:selected").text().trim()
            };

            confirmarCambioPlan(datos, function(confirmado) {
                if (!confirmado) {
                    return;
                }

                const formData = $("#formAsignacionPlan").serialize();
                actualizarPlanCliente(formData);
            });
        });

    /* =========================================================
       INICIALIZAR
       ========================================================= */
    asignacionState.view = leerTipoVista();
    actualizarBotonesVista();
    sincronizarTamanoPaginaAsignacion();
    inicializarSeccionesPersistentes();
    cargarClientes();
    cargarPlanes();
    cargarSistemas();
    cargarAsignaciones(false);
});
</script>
