<script>
(function() {
    "use strict";

    /* =========================================================
        ESPERAR JQUERY SIN DOCUMENT READY
        Registrar Planes ya no depende de DataTables.
    ========================================================= */
    let intentosCargaPlanes = 0;
    const MAX_INTENTOS_CARGA_PLANES = 60;

    function dependenciasPlanesDisponibles() {
        return (
            typeof window.jQuery !== "undefined" &&
            typeof window.jQuery.fn !== "undefined"
        );
    }

    function esperarDependenciasPlanes() {
        if (dependenciasPlanesDisponibles()) {
            inicializarModuloPlanes(window.jQuery);
            return;
        }

        intentosCargaPlanes++;

        if (intentosCargaPlanes >= MAX_INTENTOS_CARGA_PLANES) {
            console.error("jQuery no está disponible para Administrar Planes.");

            if (typeof window.showNotify === "function") {
                window.showNotify(
                    "error",
                    "Error",
                    "No se pudo iniciar el módulo Administrar Planes."
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
        const PLANES_STORAGE_VISTA = "izzy.registrarPlanes.tipo_vista";
        const PLANES_STORAGE_FILTROS = "izzy.registrarPlanes.filtros.visible";
        const PLANES_STORAGE_KPIS = "izzy.registrarPlanes.kpis.visible";

        const PLANES_MOBILE_QUERY = "(max-width: 767.98px)";
        let planesVistaPreferida = "detalle";

        function planesEsMovil() {
            return window.matchMedia
                ? window.matchMedia(PLANES_MOBILE_QUERY).matches
                : $(window).width() <= 767;
        }


        const planesState = {
            registros: [],
            filtrados: [],
            pagina: 1,
            porPagina: 10,
            porPaginaDetalle: 10,
            porPaginaMiniatura: 6,
            vista: "detalle",
            busqueda: "",
            filtroEstado: "todos",
            filtroConfiguracion: "",
            loading: false
        };

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

        function prepararSelect2Planes($select) {
            if (!$select || !$select.length || !$select.is("select")) {
                return;
            }

            if (typeof window.izzyInitSelect2 === "function") {
                window.izzyInitSelect2($select);
                return;
            }

            if (typeof $.fn.select2 === "function" && !$select.hasClass("select2-hidden-accessible")) {
                $select.select2({
                    width: "100%",
                    minimumResultsForSearch: 0,
                    placeholder: $select.attr("data-placeholder") || "Seleccione"
                });
            }
        }

        function refrescarSelect2Planes($select) {
            if (!$select || !$select.length || !$select.is("select")) {
                return;
            }

            if (typeof window.izzyRefreshSelect2 === "function") {
                window.izzyRefreshSelect2($select);
                return;
            }

            prepararSelect2Planes($select);
            $select.trigger("change.select2");
        }

        function inicializarSelect2Planes() {
            prepararSelect2Planes($("#filtroEstadoPlanes"));
            prepararSelect2Planes($("#filtroConfiguracionPlanes"));
            prepararSelect2Planes(
                $("#configuraciones-container select[name='configuracion_clave[]']")
            );
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
            let opcionesHTML = '<option value=""></option>';

            opcionesConfiguracion.forEach(function(opcion) {
                const selected =
                    configuracion && configuracion.clave === opcion.value
                        ? "selected"
                        : "";

                opcionesHTML +=
                    '<option value="' + planesEscape(opcion.value) + '" ' + selected + '>' +
                        planesEscape(opcion.text) +
                    '</option>';
            });

            const valor =
                configuracion && typeof configuracion.valor !== "undefined"
                    ? configuracion.valor
                    : "";

            const newItem = `
                <div class="configuracion-item planes-config-row">
                    <div class="planes-config-select-wrap">
                        <select class="form-control izzy-select2"
                                name="configuracion_clave[]"
                                data-placeholder="Seleccione una opción"
                                title="Seleccione una opción">
                            ${opcionesHTML}
                        </select>
                    </div>

                    <div class="planes-config-value-wrap">
                        <input type="number"
                               class="form-control"
                               name="configuracion_valor[]"
                               placeholder="Cantidad"
                               min="0"
                               value="${planesEscape(valor)}">
                    </div>

                    <div class="planes-config-action-wrap">
                        <button class="btn btn-danger remover-configuracion"
                                type="button">
                            <i class="fas fa-times mr-1"></i> Quitar
                        </button>
                    </div>
                </div>
            `;

            $("#configuraciones-container").append(newItem);

            const $ultimoSelect =
                $("#configuraciones-container .configuracion-item:last-child select[name='configuracion_clave[]']");

            prepararSelect2Planes($ultimoSelect);

            if (conFoco && $ultimoSelect.length) {
                setTimeout(function() {
                    if (
                        typeof $.fn.select2 === "function" &&
                        $ultimoSelect.hasClass("select2-hidden-accessible")
                    ) {
                        $ultimoSelect.select2("open");
                    } else {
                        $ultimoSelect.trigger("focus");
                    }
                }, 50);
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
            LISTADO PRINCIPAL - DIVs
        ========================================================= */
        function planesEscape(valor) {
            return String(valor === null || typeof valor === "undefined" ? "" : valor)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function planesNormalizarTexto(valor) {
            return String(valor || "")
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        }

        function normalizarConfiguracionesFila(row) {
            let configs = row && row.configuraciones_json ? row.configuraciones_json : {};

            if (typeof configs === "string") {
                try {
                    configs = JSON.parse(configs);
                } catch (e) {
                    configs = {};
                }
            }

            return configs && typeof configs === "object" && !Array.isArray(configs)
                ? configs
                : {};
        }

        function obtenerTextoConfiguraciones(row) {
            const configs = normalizarConfiguracionesFila(row);
            const partes = [];

            Object.keys(configs).forEach(function(clave) {
                const opcion = opcionesConfiguracion.find(function(item) {
                    return item.value === clave;
                });

                partes.push(
                    (opcion ? opcion.text : clave) +
                    ": " +
                    configs[clave]
                );
            });

            return partes.length ? partes.join(" • ") : "Sin configuraciones";
        }

        function contarAccesosPlan(row) {
            return (
                parseInt(row.menus_asignados || 0, 10) +
                parseInt(row.submenus_asignados || 0, 10) +
                parseInt(row.submenus2_asignados || 0, 10)
            );
        }

        function configurarToggleSeccionPlanes(buttonSelector, contentSelector, storageKey) {
            const $button = $(buttonSelector);
            const $content = $(contentSelector);

            if (!$button.length || !$content.length) {
                return;
            }

            let visible = true;

            try {
                const stored = localStorage.getItem(storageKey);
                if (stored !== null) {
                    visible = stored === "1";
                }
            } catch (e) {
                visible = true;
            }

            function aplicarEstado(guardar) {
                $content.toggle(visible);
                $button.attr("aria-expanded", visible ? "true" : "false");
                $button.find("span").text(visible ? "Ocultar" : "Mostrar");
                $button.find("i")
                    .toggleClass("fa-chevron-up", visible)
                    .toggleClass("fa-chevron-down", !visible);

                if (guardar) {
                    try {
                        localStorage.setItem(storageKey, visible ? "1" : "0");
                    } catch (e) {
                        console.warn("No se pudo guardar el estado de la sección.", e);
                    }
                }
            }

            aplicarEstado(false);

            $button
                .off("click.planesToggleSection")
                .on("click.planesToggleSection", function() {
                    visible = !visible;
                    $content.stop(true, true)[visible ? "slideDown" : "slideUp"](180);
                    aplicarEstado(true);
                });
        }

        function inicializarUIPlanes() {
            configurarToggleSeccionPlanes(
                "#btnToggleFiltrosPlanes",
                "#planesFiltrosContenido",
                PLANES_STORAGE_FILTROS
            );

            configurarToggleSeccionPlanes(
                "#btnToggleKpisPlanes",
                "#planesKpisContenido",
                PLANES_STORAGE_KPIS
            );

            try {
                planesVistaPreferida =
                    localStorage.getItem(PLANES_STORAGE_VISTA) === "miniatura"
                        ? "miniatura"
                        : "detalle";
            } catch (e) {
                planesVistaPreferida = "detalle";
            }

            planesState.vista = planesEsMovil()
                ? "miniatura"
                : planesVistaPreferida;

            actualizarBotonesVistaPlanes();
            sincronizarPageSizePlanes();
        }

        function actualizarDisponibilidadVistaPlanes() {
            const movil = planesEsMovil();

            $('.planes-view-btn[data-view="detalle"]')
                .prop("disabled", movil)
                .toggleClass("d-none", movil)
                .attr("aria-hidden", movil ? "true" : "false");
        }

        function actualizarBotonesVistaPlanes() {
            actualizarDisponibilidadVistaPlanes();

            $(".planes-view-btn")
                .removeClass("active")
                .attr("aria-pressed", "false");

            $('.planes-view-btn[data-view="' + planesState.vista + '"]')
                .addClass("active")
                .attr("aria-pressed", "true");
        }

        function sincronizarPageSizePlanes() {
            const miniatura = planesState.vista === "miniatura";
            const opciones = miniatura ? [6, 12, 18, 30] : [10, 25, 50, 100];

            let valor = miniatura
                ? planesState.porPaginaMiniatura
                : planesState.porPaginaDetalle;

            if (opciones.indexOf(valor) === -1) {
                valor = opciones[0];
            }

            planesState.porPagina = valor;

            const $select = $("#planesPageSize");
            $select.empty();

            opciones.forEach(function(item) {
                $select.append(
                    $("<option></option>")
                        .attr("value", item)
                        .text(item)
                );
            });

            $select.val(String(valor));
        }

        function cambiarVistaPlanes(vista) {
            planesState.vista = planesEsMovil()
                ? "miniatura"
                : (vista === "miniatura" ? "miniatura" : "detalle");

            if (!planesEsMovil()) {
                planesVistaPreferida = planesState.vista;

                try {
                    localStorage.setItem(PLANES_STORAGE_VISTA, planesVistaPreferida);
                } catch (e) {
                    console.warn("No se pudo guardar la vista.", e);
                }
            }

            actualizarBotonesVistaPlanes();
            sincronizarPageSizePlanes();
            planesState.pagina = 1;
            renderPlanesPrincipal();
        }

        let planesResponsiveTimer = null;
        $(window)
            .off("resize.planesResponsive orientationchange.planesResponsive")
            .on("resize.planesResponsive orientationchange.planesResponsive", function() {
                clearTimeout(planesResponsiveTimer);

                planesResponsiveTimer = setTimeout(function() {
                    const objetivo = planesEsMovil()
                        ? "miniatura"
                        : planesVistaPreferida;

                    if (planesState.vista === objetivo) {
                        return;
                    }

                    planesState.vista = objetivo;
                    planesState.pagina = 1;
                    actualizarBotonesVistaPlanes();
                    sincronizarPageSizePlanes();
                    renderPlanesPrincipal();
                }, 120);
            });

        function recargarPlanesPrincipal(mantenerPagina) {
            if (planesState.loading) {
                return;
            }

            const paginaAnterior = planesState.pagina;
            planesState.loading = true;

            $.ajax({
                url: PLANES_URLS.llenarDataTable,
                type: "POST",
                dataType: "json",
                success: function(response) {
                    planesState.registros =
                        response && Array.isArray(response.data)
                            ? response.data
                            : [];

                    planesState.pagina = mantenerPagina ? paginaAnterior : 1;
                    aplicarFiltrosPlanesPrincipal();
                },
                error: function(xhr) {
                    console.error("Error al cargar planes:", xhr.responseText);

                    planesState.registros = [];
                    planesState.filtrados = [];

                    actualizarKpisPlanes();
                    renderPlanesPrincipal();

                    notificarPlan(
                        "error",
                        "Error",
                        "Error al cargar los datos de planes"
                    );
                },
                complete: function() {
                    planesState.loading = false;
                }
            });
        }

        function aplicarFiltrosPlanesPrincipal() {
            const busqueda = planesNormalizarTexto(planesState.busqueda);
            const estado = String(planesState.filtroEstado);
            const configuracion = String(planesState.filtroConfiguracion || "");

            planesState.filtrados = planesState.registros.filter(function(row) {
                if (
                    estado !== "todos" &&
                    String(parseInt(row.estado || 0, 10)) !== estado
                ) {
                    return false;
                }

                const configs = normalizarConfiguracionesFila(row);

                if (
                    configuracion &&
                    !Object.prototype.hasOwnProperty.call(configs, configuracion)
                ) {
                    return false;
                }

                if (!busqueda) {
                    return true;
                }

                const texto = planesNormalizarTexto([
                    row.planes_id,
                    row.nombre,
                    obtenerTextoConfiguraciones(row),
                    parseInt(row.estado || 0, 10) === 1 ? "activo" : "inactivo",
                    row.menus_asignados || 0,
                    row.submenus_asignados || 0,
                    row.submenus2_asignados || 0
                ].join(" "));

                return texto.indexOf(busqueda) !== -1;
            });

            actualizarKpisPlanes();
            renderPlanesPrincipal();
        }

        function actualizarKpisPlanes() {
            let activos = 0;
            let configurados = 0;
            let accesos = 0;

            planesState.filtrados.forEach(function(row) {
                if (parseInt(row.estado || 0, 10) === 1) {
                    activos++;
                }

                if (Object.keys(normalizarConfiguracionesFila(row)).length > 0) {
                    configurados++;
                }

                accesos += contarAccesosPlan(row);
            });

            $("#planesKpiRegistros").text(planesState.filtrados.length);
            $("#planesKpiActivos").text(activos);
            $("#planesKpiConfigurados").text(configurados);
            $("#planesKpiAccesos").text(accesos);
        }

        function obtenerPlanStatePorId(planId) {
            return planesState.registros.find(function(row) {
                return String(row.planes_id) === String(planId);
            }) || null;
        }

        function obtenerHtmlEstadoPlanDiv(row) {
            const activo = parseInt(row.estado || 0, 10) === 1;

            return '' +
                '<span class="planes-status-badge ' +
                    (activo ? 'is-active' : 'is-inactive') +
                '">' +
                    '<i class="fas ' +
                        (activo ? 'fa-check-circle' : 'fa-times-circle') +
                    '"></i>' +
                    (activo ? 'Activo' : 'Inactivo') +
                '</span>';
        }

        function construirAccionesPlan(row) {
            return '' +
                '<div class="dropdown planes-actions-dropdown">' +
                    '<button type="button" class="btn btn-sm btn-acciones planes-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                        '<i class="fas fa-cog"></i><span>Acciones</span>' +
                    '</button>' +
                    '<div class="dropdown-menu acciones-menu">' +
                        '<button type="button" class="dropdown-item accion-item btn-asignar-menu" data-plan-id="' + planesEscape(row.planes_id) + '" data-plan-nombre="' + planesEscape(row.nombre) + '">' +
                            '<span class="accion-icon accion-icon-primary"><i class="fas fa-link"></i></span>' +
                            '<span class="accion-label">Asignar Menú</span>' +
                        '</button>' +
                        '<button type="button" class="dropdown-item accion-item btn-asignar-submenu" data-plan-id="' + planesEscape(row.planes_id) + '" data-plan-nombre="' + planesEscape(row.nombre) + '">' +
                            '<span class="accion-icon accion-icon-primary"><i class="fas fa-link"></i></span>' +
                            '<span class="accion-label">Asignar Submenú</span>' +
                        '</button>' +
                        '<button type="button" class="dropdown-item accion-item btn-asignar-submenu2" data-plan-id="' + planesEscape(row.planes_id) + '" data-plan-nombre="' + planesEscape(row.nombre) + '">' +
                            '<span class="accion-icon accion-icon-primary"><i class="fas fa-link"></i></span>' +
                            '<span class="accion-label">Asignar Submenú 2</span>' +
                        '</button>' +
                        '<div class="dropdown-divider"></div>' +
                        '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar btn-editar" data-id="' + planesEscape(row.planes_id) + '">' +
                            '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                            '<span class="accion-label">Editar</span>' +
                        '</button>' +
                        '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar btn-eliminar" data-id="' + planesEscape(row.planes_id) + '" data-nombre="' + planesEscape(row.nombre) + '">' +
                            '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                            '<span class="accion-label">Eliminar</span>' +
                        '</button>' +
                    '</div>' +
                '</div>';
        }

        function construirResumenConfiguracionesPlan(row, miniatura) {
            const configs = normalizarConfiguracionesFila(row);
            const keys = Object.keys(configs);

            if (!keys.length) {
                return '<span class="planes-no-config">Sin configuraciones</span>';
            }

            const visibles = keys.slice(0, miniatura ? 4 : 3);
            let html = '<div class="planes-config-tags">';

            visibles.forEach(function(clave) {
                const opcion = opcionesConfiguracion.find(function(item) {
                    return item.value === clave;
                });

                html +=
                    '<span class="planes-config-tag"><strong>' +
                    planesEscape(opcion ? opcion.text : clave) +
                    ':</strong> ' +
                    planesEscape(configs[clave]) +
                    '</span>';
            });

            html += '</div>';

            if (keys.length > visibles.length) {
                html +=
                    '<small class="planes-more-config">+' +
                    (keys.length - visibles.length) +
                    ' más</small>';
            }

            html +=
                '<button type="button" class="btn btn-sm btn-info btn-ver-configs mt-2" data-plan-id="' +
                planesEscape(row.planes_id) +
                '"><i class="fas fa-eye mr-1"></i> Ver todas</button>';

            return html;
        }

        function construirHeaderPlanesDetalle() {
            return '' +
                '<div class="planes-detail-header">' +
                    '<div>Acciones</div>' +
                    '<div>Plan</div>' +
                    '<div>Configuraciones</div>' +
                    '<div>Estado</div>' +
                    '<div>Menús</div>' +
                    '<div>Submenús</div>' +
                    '<div>Submenús 2</div>' +
                '</div>';
        }

        function construirFilaPlanDetalle(row) {
            return '' +
                '<article class="planes-detail-row" data-id="' + planesEscape(row.planes_id) + '">' +
                    '<div class="planes-detail-cell planes-actions-cell">' +
                        construirAccionesPlan(row) +
                    '</div>' +
                    '<div class="planes-detail-cell">' +
                        '<div class="planes-plan-identity">' +
                            '<div class="planes-plan-icon"><i class="fas fa-layer-group"></i></div>' +
                            '<div>' +
                                '<strong class="planes-plan-name">' + planesEscape(row.nombre || "Sin nombre") + '</strong>' +
                                '<span class="planes-plan-code">ID: ' + planesEscape(row.planes_id) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="planes-detail-cell"><div class="planes-config-cell">' +
                        construirResumenConfiguracionesPlan(row, false) +
                    '</div></div>' +
                    '<div class="planes-detail-cell planes-center-cell">' +
                        obtenerHtmlEstadoPlanDiv(row) +
                    '</div>' +
                    '<div class="planes-detail-cell planes-center-cell">' +
                        '<span class="planes-count-badge menus" id="contador-menus-' + planesEscape(row.planes_id) + '">' +
                            planesEscape(row.menus_asignados || 0) + ' asignados</span>' +
                    '</div>' +
                    '<div class="planes-detail-cell planes-center-cell">' +
                        '<span class="planes-count-badge submenus" id="contador-submenus-' + planesEscape(row.planes_id) + '">' +
                            planesEscape(row.submenus_asignados || 0) + ' asignados</span>' +
                    '</div>' +
                    '<div class="planes-detail-cell planes-center-cell">' +
                        '<span class="planes-count-badge submenus2" id="contador-submenus2-' + planesEscape(row.planes_id) + '">' +
                            planesEscape(row.submenus2_asignados || 0) + ' asignados</span>' +
                    '</div>' +
                '</article>';
        }

        function construirMiniaturaPlan(row) {
            return '' +
                '<article class="planes-mini-card" data-id="' + planesEscape(row.planes_id) + '">' +
                    '<div class="planes-mini-topline"></div>' +
                    '<div class="planes-mini-header">' +
                        '<div class="planes-plan-identity">' +
                            '<div class="planes-plan-icon"><i class="fas fa-layer-group"></i></div>' +
                            '<div><h4>' + planesEscape(row.nombre || "Sin nombre") + '</h4>' +
                            '<span>ID: ' + planesEscape(row.planes_id) + '</span></div>' +
                        '</div>' +
                        obtenerHtmlEstadoPlanDiv(row) +
                    '</div>' +
                    '<div class="planes-mini-body">' +
                        '<span class="planes-mini-label">Configuraciones</span>' +
                        construirResumenConfiguracionesPlan(row, true) +
                        '<div class="planes-mini-counts">' +
                            '<div><small>Menús</small><strong id="contador-menus-' + planesEscape(row.planes_id) + '">' + planesEscape(row.menus_asignados || 0) + '</strong></div>' +
                            '<div><small>Submenús</small><strong id="contador-submenus-' + planesEscape(row.planes_id) + '">' + planesEscape(row.submenus_asignados || 0) + '</strong></div>' +
                            '<div><small>Submenús 2</small><strong id="contador-submenus2-' + planesEscape(row.planes_id) + '">' + planesEscape(row.submenus2_asignados || 0) + '</strong></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="planes-mini-footer">' +
                        construirAccionesPlan(row) +
                    '</div>' +
                '</article>';
        }

        function renderPlanesPrincipal() {
            const total = planesState.filtrados.length;
            const totalPaginas = Math.max(1, Math.ceil(total / planesState.porPagina));

            if (planesState.pagina > totalPaginas) {
                planesState.pagina = totalPaginas;
            }

            const inicio = (planesState.pagina - 1) * planesState.porPagina;
            const fin = Math.min(inicio + planesState.porPagina, total);
            const paginaRows = planesState.filtrados.slice(inicio, fin);

            const $listado = $("#planesListado");
            let html = "";

            $listado
                .toggleClass("vista-detalle", planesState.vista === "detalle")
                .toggleClass("vista-miniatura", planesState.vista === "miniatura");

            if (planesState.vista === "detalle" && total > 0) {
                html += construirHeaderPlanesDetalle();
            }

            paginaRows.forEach(function(row) {
                html += planesState.vista === "miniatura"
                    ? construirMiniaturaPlan(row)
                    : construirFilaPlanDetalle(row);
            });

            $listado.html(html);
            $("#planesVacio").toggle(total === 0);

            $("#planesInfo").text(
                total > 0
                    ? "Mostrando " + (inicio + 1) + " a " + fin + " de " + total + " registros"
                    : "Mostrando 0 registros"
            );

            renderPaginacionPlanes(totalPaginas);

            if (
                typeof getPermisosTipoUsuarioAccesosTable === "function" &&
                typeof getPrivilegioTipoUsuario === "function"
            ) {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }
        }

        function renderPaginacionPlanes(totalPaginas) {
            const pagina = planesState.pagina;
            let html = "";

            html += crearBotonPaginaPlanes("Inicio", "fa-angle-double-left", 1, pagina <= 1);
            html += crearBotonPaginaPlanes("Anterior", "fa-angle-left", Math.max(1, pagina - 1), pagina <= 1);

            let desde = Math.max(1, pagina - 2);
            let hasta = Math.min(totalPaginas, desde + 4);

            if (hasta - desde < 4) {
                desde = Math.max(1, hasta - 4);
            }

            for (let i = desde; i <= hasta; i++) {
                html +=
                    '<button type="button" class="planes-page-btn planes-page-number ' +
                    (i === pagina ? "active" : "") +
                    '" data-page="' + i + '">' + i + '</button>';
            }

            html += crearBotonPaginaPlanes("Siguiente", "fa-angle-right", Math.min(totalPaginas, pagina + 1), pagina >= totalPaginas);
            html += crearBotonPaginaPlanes("Final", "fa-angle-double-right", totalPaginas, pagina >= totalPaginas);

            $("#planesPaginacion").html(html);
        }

        function crearBotonPaginaPlanes(texto, icono, pagina, disabled) {
            return '' +
                '<button type="button" class="planes-page-btn" data-page="' + pagina + '" ' +
                    (disabled ? "disabled" : "") + '>' +
                    '<i class="fas ' + icono + '"></i><span>' + texto + '</span>' +
                '</button>';
        }

        /* =========================================================
            EXCEL PREMIUM
        ========================================================= */
        function planesExportRows() {
            return planesState.filtrados.map(function(row) {
                return {
                    id: parseInt(row.planes_id || 0, 10),
                    plan: row.nombre || "Sin nombre",
                    configuraciones: obtenerTextoConfiguraciones(row),
                    estado: parseInt(row.estado || 0, 10) === 1 ? "Activo" : "Inactivo",
                    menus: parseInt(row.menus_asignados || 0, 10),
                    submenus: parseInt(row.submenus_asignados || 0, 10),
                    submenus2: parseInt(row.submenus2_asignados || 0, 10),
                    accesos: contarAccesosPlan(row)
                };
            });
        }

        function planesXmlEscape(value) {
            return String(value === null || typeof value === "undefined" ? "" : value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&apos;");
        }

        function planesExcelCol(index) {
            let name = "";
            let n = index + 1;

            while (n > 0) {
                const mod = (n - 1) % 26;
                name = String.fromCharCode(65 + mod) + name;
                n = Math.floor((n - 1) / 26);
            }

            return name;
        }

        function planesExcelCell(ref, value, styleId, numeric) {
            if (numeric) {
                const numero = Number(value);

                if (!isNaN(numero)) {
                    return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
                }
            }

            return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t>' +
                planesXmlEscape(value) +
                '</t></is></c>';
        }

        function descargarBlobPlanes(blob, nombre) {
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

        function exportarPlanesExcelPremium() {
            const rows = planesExportRows();

            if (!rows.length) {
                notificarPlan("warning", "Sin información", "No hay planes para exportar.");
                return;
            }

            if (typeof JSZip === "undefined") {
                notificarPlan("error", "Excel no disponible", "No se encontró JSZip para generar el archivo XLSX.");
                return;
            }

            const headers = [
                "ID",
                "Plan",
                "Configuraciones",
                "Estado",
                "Menús",
                "Submenús",
                "Submenús 2",
                "Accesos Totales"
            ];

            const activos = rows.filter(function(row) {
                return row.estado === "Activo";
            }).length;

            const configurados = rows.filter(function(row) {
                return row.configuraciones !== "Sin configuraciones";
            }).length;

            const accesos = rows.reduce(function(acc, row) {
                return acc + row.accesos;
            }, 0);

            const headerRow = 7;
            const firstDataRow = 8;
            const lastRow = Math.max(headerRow, headerRow + rows.length);
            const sheetRows = [];

            sheetRows.push(
                '<row r="1" ht="30" customHeight="1">' +
                planesExcelCell("A1", "IZZY • REPORTE DE PLANES", 1, false) +
                '</row>'
            );

            sheetRows.push(
                '<row r="2" ht="20" customHeight="1">' +
                planesExcelCell(
                    "A2",
                    "Administración de planes, configuraciones y accesos • Generado: " +
                    new Date().toLocaleDateString("es-HN"),
                    2,
                    false
                ) +
                '</row>'
            );

            sheetRows.push(
                '<row r="3">' +
                planesExcelCell("A3", "REGISTROS", 6, false) +
                planesExcelCell("C3", "ACTIVOS", 6, false) +
                planesExcelCell("E3", "CONFIGURADOS", 6, false) +
                planesExcelCell("G3", "ACCESOS", 6, false) +
                '</row>'
            );

            sheetRows.push(
                '<row r="4" ht="27" customHeight="1">' +
                planesExcelCell("A4", rows.length, 7, true) +
                planesExcelCell("C4", activos, 7, true) +
                planesExcelCell("E4", configurados, 7, true) +
                planesExcelCell("G4", accesos, 7, true) +
                '</row>'
            );

            sheetRows.push('<row r="5"></row>');

            sheetRows.push(
                '<row r="6">' +
                planesExcelCell("A6", "Detalle de planes filtrados", 8, false) +
                '</row>'
            );

            sheetRows.push(
                '<row r="' + headerRow + '" ht="28" customHeight="1">' +
                headers.map(function(header, index) {
                    return planesExcelCell(
                        planesExcelCol(index) + headerRow,
                        header,
                        3,
                        false
                    );
                }).join("") +
                '</row>'
            );

            rows.forEach(function(row, index) {
                const excelRow = firstDataRow + index;
                const valores = [
                    row.id,
                    row.plan,
                    row.configuraciones,
                    row.estado,
                    row.menus,
                    row.submenus,
                    row.submenus2,
                    row.accesos
                ];

                const cells = valores.map(function(value, colIndex) {
                    const numeric = colIndex === 0 || colIndex >= 4;
                    let style = numeric ? 5 : 4;

                    if (colIndex === 3) {
                        style = value === "Activo" ? 9 : 10;
                    }

                    return planesExcelCell(
                        planesExcelCol(colIndex) + excelRow,
                        value,
                        style,
                        numeric
                    );
                }).join("");

                sheetRows.push(
                    '<row r="' + excelRow + '" ht="34" customHeight="1">' +
                    cells +
                    '</row>'
                );
            });

            const sheetXml =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:H' + lastRow + '"/>' +
                '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                '</sheetView></sheetViews>' +
                '<cols>' +
                '<col min="1" max="1" width="10" customWidth="1"/>' +
                '<col min="2" max="2" width="24" customWidth="1"/>' +
                '<col min="3" max="3" width="55" customWidth="1"/>' +
                '<col min="4" max="4" width="14" customWidth="1"/>' +
                '<col min="5" max="8" width="16" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join("") + '</sheetData>' +
                '<autoFilter ref="A7:H' + lastRow + '"/>' +
                '<mergeCells count="10">' +
                '<mergeCell ref="A1:H1"/><mergeCell ref="A2:H2"/>' +
                '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                '<mergeCell ref="G3:H3"/><mergeCell ref="G4:H4"/>' +
                '</mergeCells>' +
                '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
                '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
                '</worksheet>';

            const stylesXml =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<fonts count="7">' +
                '<font><sz val="10"/><name val="Calibri"/></font>' +
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
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
                '</borders>' +
                '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
                '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
                '</styleSheet>';

            const workbookXml =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<sheets><sheet name="Planes" sheetId="1" r:id="rId1"/></sheets></workbook>';

            const workbookRels =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
                '</Relationships>';

            const rootRels =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
                '</Relationships>';

            const contentTypes =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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

            const promesa =
                typeof zip.generateAsync === "function"
                    ? zip.generateAsync(opciones)
                    : Promise.resolve(zip.generate(opciones));

            promesa
                .then(function(blob) {
                    descargarBlobPlanes(blob, "Reporte_Planes.xlsx");
                })
                .catch(function(error) {
                    console.error(error);
                    notificarPlan("error", "Error", "No se pudo generar el archivo Excel.");
                });
        }

        /* =========================================================
            PDF PREMIUM - ESTILO CAJAS
        ========================================================= */
        function planesPdfDato(label, value, color) {
            return {
                stack: [
                    {
                        text: String(label || "").toUpperCase(),
                        fontSize: 6.2,
                        bold: true,
                        color: "#6B778C",
                        margin: [0, 0, 0, 2]
                    },
                    {
                        text: String(value === null || typeof value === "undefined" || value === "" ? "—" : value),
                        fontSize: 7.8,
                        bold: true,
                        color: color || "#172B4D"
                    }
                ]
            };
        }

        function planesPdfFiltroTexto() {
            const estado =
                $("#filtroEstadoPlanes option:selected").text() || "Todos";

            const configuracion =
                $("#filtroConfiguracionPlanes option:selected").text() || "Todas";

            const busqueda =
                $.trim($("#buscarPlanes").val()) || "Sin búsqueda";

            return (
                "Estado: " + estado +
                "   |   Configuración: " + configuracion +
                "   |   Búsqueda: " + busqueda
            );
        }


        function planesObtenerLogoPdf(callback) {
            function convertirLogo(source) {
                source = String(source || '').trim();

                if (!source) {
                    if (typeof showNotify === 'function') {
                        showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
                    }
                    return;
                }

                if (source.indexOf('data:image/') === 0) {
                    callback(source);
                    return;
                }

                var img = new Image();
                img.crossOrigin = 'Anonymous';

                img.onload = function () {
                    try {
                        var canvas = document.createElement('canvas');
                        var ctx = canvas.getContext('2d');
                        canvas.width = img.naturalWidth || img.width;
                        canvas.height = img.naturalHeight || img.height;
                        ctx.drawImage(img, 0, 0);
                        var dataUrl = canvas.toDataURL('image/png');

                        if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                            throw new Error('No se pudo convertir el logo a Data URL.');
                        }

                        try { imagen = dataUrl; } catch (e) {}
                        callback(dataUrl);
                    } catch (error) {
                        console.error('Error preparando logo PDF:', error);
                        if (typeof showNotify === 'function') {
                            showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
                        }
                    }
                };

                img.onerror = function () {
                    if (typeof showNotify === 'function') {
                        showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
                    }
                };

                img.src = source;
            }

            if (typeof imagen !== 'undefined' && imagen) {
                convertirLogo(imagen);
                return;
            }

            $.ajax({
                type: 'GET',
                url: '<?php echo SERVERURL;?>core/get_image.php',
                dataType: 'text',
                timeout: 15000
            }).done(function (imageUrl) {
                convertirLogo(imageUrl);
            }).fail(function (xhr) {
                console.error('Error obteniendo logo PDF:', xhr.responseText);
                if (typeof showNotify === 'function') {
                    showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
                }
            });
        }

        function planesPdfLogoPlate(logoDataUrl) {
            return {
                table: {
                    widths: ['*'],
                    body: [[{
                        image: logoDataUrl,
                        fit: [62, 36],
                        alignment: 'center',
                        margin: [7, 5, 7, 5],
                        fillColor: '#FFFFFF'
                    }]]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .5; },
                    vLineWidth: function () { return .5; },
                    paddingLeft: function () { return 0; },
                    paddingRight: function () { return 0; },
                    paddingTop: function () { return 0; },
                    paddingBottom: function () { return 0; }
                }
            };
        }
        function planesPdfEncabezadoPremium(rows) {
            const activos = rows.filter(function(row) {
                return row.estado === "Activo";
            }).length;

            const configurados = rows.filter(function(row) {
                return row.configuraciones !== "Sin configuraciones";
            }).length;

            const accesos = rows.reduce(function(acc, row) {
                return acc + row.accesos;
            }, 0);

            const logoCell = planesPdfLogoPlate(imagen);

            return [
                {
                    table: {
                        widths: [100, "*", 155],
                        body: [[
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [12, 10, 0, 10],
                                stack: [logoCell]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [0, 10, 0, 10],
                                stack: [
                                    {
                                        text: "REPORTE DE PLANES",
                                        fontSize: 16,
                                        bold: true,
                                        color: "#FFFFFF"
                                    },
                                    {
                                        text: "Administración de planes, configuraciones y accesos",
                                        fontSize: 7.5,
                                        color: "#D8E5F0",
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [0, 10, 12, 10],
                                stack: [
                                    {
                                        text: "REPORTE EJECUTIVO",
                                        fontSize: 6.5,
                                        bold: true,
                                        color: "#72E2E5",
                                        alignment: "right"
                                    },
                                    {
                                        text: new Date().toLocaleDateString("es-HN"),
                                        fontSize: 9,
                                        bold: true,
                                        color: "#FFFFFF",
                                        alignment: "right",
                                        margin: [0, 3, 0, 0]
                                    },
                                    {
                                        text: rows.length + " registro(s) filtrado(s)",
                                        fontSize: 6.5,
                                        color: "#D8E5F0",
                                        alignment: "right",
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineWidth: function() { return 0; },
                        vLineWidth: function() { return 0; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ["*"],
                        body: [[{
                            text: "Filtros aplicados: " + planesPdfFiltroTexto(),
                            fontSize: 6.8,
                            color: "#52627A",
                            margin: [10, 7, 10, 7],
                            fillColor: "#F7F9FC"
                        }]]
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ["*", "*", "*", "*"],
                        body: [[
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: "REGISTROS", fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(rows.length), fontSize: 13, bold: true, color: "#172B4D"}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: "ACTIVOS", fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(activos), fontSize: 13, bold: true, color: "#14804A"}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: "CONFIGURADOS", fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(configurados), fontSize: 13, bold: true, color: "#172B4D"}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: "ACCESOS", fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(accesos), fontSize: 13, bold: true, color: "#172B4D"}
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 12]
                }
            ];
        }

        function planesPdfContenidoDetalle(rows) {
            const body = [[
                {text: "ID", style: "th", fillColor: "#17324D"},
                {text: "PLAN", style: "th", fillColor: "#17324D"},
                {text: "CONFIGURACIONES", style: "th", fillColor: "#17324D"},
                {text: "ESTADO", style: "th", fillColor: "#17324D"},
                {text: "MENÚS", style: "th", fillColor: "#17324D"},
                {text: "SUBMENÚS", style: "th", fillColor: "#17324D"},
                {text: "SUBMENÚS 2", style: "th", fillColor: "#17324D"},
                {text: "ACCESOS", style: "th", fillColor: "#17324D"}
            ]];

            rows.forEach(function(row, index) {
                const fill = index % 2 === 0 ? "#FFFFFF" : "#F7F9FC";

                body.push([
                    {text: String(row.id), style: "tdCenter", fillColor: fill},
                    {text: row.plan, style: "tdStrong", fillColor: fill},
                    {text: row.configuraciones, style: "td", fillColor: fill},
                    {
                        text: row.estado,
                        style: "tdCenter",
                        color: row.estado === "Activo" ? "#14804A" : "#C9372C",
                        bold: true,
                        fillColor: fill
                    },
                    {text: String(row.menus), style: "tdCenter", fillColor: fill},
                    {text: String(row.submenus), style: "tdCenter", fillColor: fill},
                    {text: String(row.submenus2), style: "tdCenter", fillColor: fill},
                    {text: String(row.accesos), style: "tdCenter", bold: true, fillColor: fill}
                ]);
            });

            return [
                {
                    text: "VISTA DETALLE",
                    fontSize: 7,
                    bold: true,
                    color: "#17324D",
                    margin: [0, 1, 0, 7]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: [34, 92, "*", 52, 48, 54, 58, 50],
                        body: body
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.55; },
                        vLineWidth: function() { return 0.55; },
                        paddingLeft: function() { return 5; },
                        paddingRight: function() { return 5; },
                        paddingTop: function() { return 6; },
                        paddingBottom: function() { return 6; }
                    }
                }
            ];
        }

        function planesPdfMiniCard(row) {
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
                                            {text: row.plan, fontSize: 10, bold: true, color: "#172B4D"},
                                            {text: "Plan ID: " + row.id, fontSize: 7, color: "#6B778C", margin: [0, 2, 0, 0]}
                                        ]
                                    },
                                    {
                                        width: "auto",
                                        text: row.estado,
                                        fontSize: 6.8,
                                        bold: true,
                                        color: row.estado === "Activo" ? "#14804A" : "#C9372C"
                                    }
                                ]
                            },
                            {
                                canvas: [{
                                    type: "line",
                                    x1: 0,
                                    y1: 0,
                                    x2: 250,
                                    y2: 0,
                                    lineWidth: 0.6,
                                    lineColor: "#DDE3EA"
                                }],
                                margin: [0, 7, 0, 7]
                            },
                            planesPdfDato("Configuraciones", row.configuraciones),
                            {
                                margin: [0, 9, 0, 0],
                                columns: [
                                    {width: "33%", stack: [planesPdfDato("Menús", row.menus)]},
                                    {width: "33%", stack: [planesPdfDato("Submenús", row.submenus)]},
                                    {width: "34%", stack: [planesPdfDato("Submenús 2", row.submenus2)]}
                                ]
                            },
                            {
                                margin: [0, 9, 0, 0],
                                stack: [planesPdfDato("Accesos totales", row.accesos)]
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

        function planesPdfContenidoMiniatura(rows) {
            const contenido = [
                {
                    text: "VISTA MINIATURA",
                    fontSize: 7,
                    bold: true,
                    color: "#17324D",
                    margin: [0, 1, 0, 7]
                }
            ];

            for (let i = 0; i < rows.length; i += 2) {
                contenido.push({
                    columns: [
                        {width: "*", stack: [planesPdfMiniCard(rows[i])]},
                        {width: 10, text: ""},
                        rows[i + 1]
                            ? {width: "*", stack: [planesPdfMiniCard(rows[i + 1])]}
                            : {width: "*", text: ""}
                    ],
                    margin: [0, 0, 0, 9]
                });
            }

            return contenido;
        }

        function previsualizarPlanesPdfPremium() {
            if (!(typeof imagen !== "undefined" && typeof imagen === "string" && imagen.indexOf("data:image/") === 0)) {
                planesObtenerLogoPdf(function(logoDataUrl) {
                    try { imagen = logoDataUrl; } catch (e) {}
                    previsualizarPlanesPdfPremium();
                });
                return;
            }

            const rows = planesExportRows();

            if (!rows.length) {
                notificarPlan("warning", "Sin información", "No hay planes para exportar.");
                return;
            }

            if (typeof pdfMake === "undefined") {
                notificarPlan("error", "PDF no disponible", "No se encontró pdfMake.");
                return;
            }

            if (typeof abrirModalPdfPublico !== "function") {
                notificarPlan("error", "Visor PDF no disponible", "No se encontró el modal PDF público.");
                return;
            }

            const esMiniatura = planesState.vista === "miniatura";

            const contenido = planesPdfEncabezadoPremium(rows).concat(
                esMiniatura
                    ? planesPdfContenidoMiniatura(rows)
                    : planesPdfContenidoDetalle(rows)
            );

            const docDefinition = {
                pageSize: "LETTER",
                pageOrientation: "landscape",
                pageMargins: [28, 28, 28, 34],
                header: function() {
                    return {
                        margin: [28, 12, 28, 0],
                        canvas: [{
                            type: "line",
                            x1: 0,
                            y1: 0,
                            x2: 736,
                            y2: 0,
                            lineWidth: 2,
                            lineColor: "#0EA5A8"
                        }]
                    };
                },
                footer: function(currentPage, pageCount) {
                    return {
                        margin: [28, 8, 28, 0],
                        columns: [
                            {text: "IZZY • Administrar Planes", fontSize: 7, color: "#7A869A"},
                            {text: "Página " + currentPage + " de " + pageCount, fontSize: 7, color: "#7A869A", alignment: "right"}
                        ]
                    };
                },
                content: contenido,
                styles: {
                    th: {fontSize: 6.2, bold: true, color: "#FFFFFF", alignment: "center"},
                    td: {fontSize: 6.3, color: "#253858"},
                    tdStrong: {fontSize: 6.5, bold: true, color: "#172B4D"},
                    tdCenter: {fontSize: 6.4, color: "#253858", alignment: "center"}
                },
                defaultStyle: {fontSize: 8, color: "#253858"}
            };

            const pdf = pdfMake.createPdf(docDefinition);
            const nombre = "Reporte_Planes.pdf";

            if (typeof pdf.getDataUrl === "function") {
                pdf.getDataUrl(function(dataUrl) {
                    abrirModalPdfPublico(dataUrl, "Reporte de Planes", nombre);
                });
                return;
            }

            if (typeof pdf.getBase64 === "function") {
                pdf.getBase64(function(base64) {
                    abrirModalPdfPublico(
                        "data:application/pdf;base64," + base64,
                        "Reporte de Planes",
                        nombre
                    );
                });
                return;
            }

            notificarPlan(
                "error",
                "PDF no disponible",
                "La versión actual de pdfMake no permite una vista previa compatible."
            );
        }

        /* =========================================================
            MODALES AUXILIARES | DIVs / DETALLE / MINIATURA
            Configuraciones, Menús, Submenús Nivel 1 y Nivel 2.
        ========================================================= */
        const PLANES_AUX_STORAGE_PREFIX = "izzy.registrarPlanes.modal.";
        const planesAuxStates = {};

        const planesAuxMeta = {
            configuraciones: {
                modal: "#modalConfiguraciones",
                listado: "#planesAuxListado_configuraciones",
                vacio: "#planesAuxVacio_configuraciones",
                info: "#planesAuxInfo_configuraciones",
                paginacion: "#planesAuxPaginacion_configuraciones",
                search: '[data-planes-aux-search="configuraciones"]',
                pageSize: '[data-planes-aux-page-size="configuraciones"]',
                title: "Configuraciones del Plan",
                file: "Configuraciones_Plan",
                headers: ["CONFIGURACIÓN", "CANTIDAD"],
                values: function(row) {
                    return [row.config, row.valor];
                }
            },
            menus: {
                modal: "#modalAsignarMenus",
                listado: "#planesAuxListado_menus",
                vacio: "#planesAuxVacio_menus",
                info: "#planesAuxInfo_menus",
                paginacion: "#planesAuxPaginacion_menus",
                search: '[data-planes-aux-search="menus"]',
                pageSize: '[data-planes-aux-page-size="menus"]',
                title: "Menús Principales",
                file: "Menus_Plan",
                headers: ["MENÚ", "ESTADO"],
                values: function(row) {
                    return [
                        row.name,
                        planesAuxEsAsignado(row.asignado) ? "Asignado" : "No asignado"
                    ];
                }
            },
            submenus: {
                modal: "#modalAsignarSubmenus",
                listado: "#planesAuxListado_submenus",
                vacio: "#planesAuxVacio_submenus",
                info: "#planesAuxInfo_submenus",
                paginacion: "#planesAuxPaginacion_submenus",
                search: '[data-planes-aux-search="submenus"]',
                pageSize: '[data-planes-aux-page-size="submenus"]',
                title: "Submenús Nivel 1",
                file: "Submenus_Nivel_1_Plan",
                headers: ["MENÚ PADRE", "SUBMENÚ", "ESTADO"],
                values: function(row) {
                    return [
                        row.menu_name,
                        row.name,
                        planesAuxEsAsignado(row.asignado) ? "Asignado" : "No asignado"
                    ];
                }
            },
            submenus2: {
                modal: "#modalAsignarSubmenus2",
                listado: "#planesAuxListado_submenus2",
                vacio: "#planesAuxVacio_submenus2",
                info: "#planesAuxInfo_submenus2",
                paginacion: "#planesAuxPaginacion_submenus2",
                search: '[data-planes-aux-search="submenus2"]',
                pageSize: '[data-planes-aux-page-size="submenus2"]',
                title: "Submenús Nivel 2",
                file: "Submenus_Nivel_2_Plan",
                headers: ["NOMBRE DEL SUBMENÚ", "MENÚ PADRE", "SUBMENÚ NIVEL 1", "ESTADO"],
                values: function(row) {
                    return [
                        row.name,
                        row.menu_name,
                        row.submenu_name,
                        planesAuxEsAsignado(row.asignado) ? "Asignado" : "No asignado"
                    ];
                }
            }
        };

        function planesAuxEsAsignado(valor) {
            return (
                valor === true ||
                valor === 1 ||
                valor === "1" ||
                valor === "true"
            );
        }

        function planesAuxEstado(tipo) {
            if (planesAuxStates[tipo]) {
                return planesAuxStates[tipo];
            }

            let preferida = "detalle";

            try {
                preferida =
                    localStorage.getItem(
                        PLANES_AUX_STORAGE_PREFIX + tipo + ".vista"
                    ) === "miniatura"
                        ? "miniatura"
                        : "detalle";
            } catch (e) {
                preferida = "detalle";
            }

            planesAuxStates[tipo] = {
                rows: [],
                filtered: [],
                pagina: 1,
                porPagina: planesEsMovil() ? 6 : 10,
                porPaginaDetalle: 10,
                porPaginaMiniatura: 6,
                vistaPreferida: preferida,
                vista: planesEsMovil() ? "miniatura" : preferida,
                busqueda: "",
                planId: "",
                planNombre: "Plan"
            };

            return planesAuxStates[tipo];
        }

        function planesAuxTextoFila(tipo, row) {
            if (tipo === "configuraciones") {
                return [row.config, row.valor].join(" ");
            }

            if (tipo === "menus") {
                return [
                    row.name,
                    planesAuxEsAsignado(row.asignado)
                        ? "asignado"
                        : "no asignado"
                ].join(" ");
            }

            if (tipo === "submenus") {
                return [
                    row.menu_name,
                    row.name,
                    planesAuxEsAsignado(row.asignado)
                        ? "asignado"
                        : "no asignado"
                ].join(" ");
            }

            return [
                row.name,
                row.menu_name,
                row.submenu_name,
                planesAuxEsAsignado(row.asignado)
                    ? "asignado"
                    : "no asignado"
            ].join(" ");
        }

        function planesAuxBadge(asignado) {
            const activo = planesAuxEsAsignado(asignado);

            return (
                '<span class="planes-aux-status ' +
                    (activo ? "is-assigned" : "is-pending") +
                '">' +
                    '<i class="fas ' +
                        (activo ? "fa-check-circle" : "fa-minus-circle") +
                    '"></i>' +
                    (activo ? "Asignado" : "No asignado") +
                '</span>'
            );
        }

        function planesAuxBotonAccion(tipo, row) {
            if (tipo === "configuraciones") {
                return (
                    '<button type="button" ' +
                        'class="btn btn-danger planes-aux-action-btn btn-eliminar-config" ' +
                        'data-clave="' + planesEscape(row.clave) + '" ' +
                        'data-plan-id="' + planesEscape(row.plan_id) + '">' +
                        '<i class="fas fa-times"></i>' +
                        '<span>Quitar</span>' +
                    '</button>'
                );
            }

            const asignado = planesAuxEsAsignado(row.asignado);
            let clase = "";
            let atributo = "";
            let id = "";

            if (tipo === "menus") {
                clase = "btn-toggle-menu";
                atributo = "data-menu-id";
                id = row.menu_id;
            } else if (tipo === "submenus") {
                clase = "btn-toggle-submenu";
                atributo = "data-submenu-id";
                id = row.submenu_id;
            } else {
                clase = "btn-toggle-submenu2";
                atributo = "data-submenu2-id";
                id = row.submenu1_id;
            }

            return (
                '<button type="button" ' +
                    'class="btn ' +
                    (asignado ? "btn-danger" : "btn-success") +
                    ' planes-aux-action-btn ' + clase + '" ' +
                    atributo + '="' + planesEscape(id) + '" ' +
                    'data-asignado="' + (asignado ? "1" : "0") + '">' +
                    '<i class="fas ' +
                        (asignado ? "fa-times" : "fa-plus") +
                    '"></i>' +
                    '<span>' +
                        (asignado ? "Quitar" : "Asignar") +
                    '</span>' +
                '</button>'
            );
        }

        function planesAuxHeaderDetalle(tipo) {
            const headers = {
                configuraciones: ["Configuración", "Cantidad", "Acciones"],
                menus: ["Menú", "Estado", "Acciones"],
                submenus: ["Menú Padre", "Nombre del Submenú", "Estado", "Acciones"],
                submenus2: ["Nombre del Submenú", "Menú Padre", "Submenú Nivel 1", "Estado", "Acciones"]
            };

            return (
                '<div class="planes-aux-detail-header">' +
                    headers[tipo].map(function(texto) {
                        return "<div>" + planesEscape(texto) + "</div>";
                    }).join("") +
                '</div>'
            );
        }

        function planesAuxFilaDetalle(tipo, row) {
            let cells = [];

            if (tipo === "configuraciones") {
                cells = [
                    '<div class="planes-aux-primary">' +
                        '<span class="planes-aux-row-icon">' +
                            '<i class="fas fa-sliders-h"></i>' +
                        '</span>' +
                        '<strong>' + planesEscape(row.config) + '</strong>' +
                    '</div>',
                    '<span class="planes-aux-value">' +
                        planesEscape(row.valor) +
                    '</span>',
                    planesAuxBotonAccion(tipo, row)
                ];
            } else if (tipo === "menus") {
                cells = [
                    '<div class="planes-aux-primary">' +
                        '<span class="planes-aux-row-icon">' +
                            '<i class="fas fa-bars"></i>' +
                        '</span>' +
                        '<strong>' + planesEscape(row.name) + '</strong>' +
                    '</div>',
                    planesAuxBadge(row.asignado),
                    planesAuxBotonAccion(tipo, row)
                ];
            } else if (tipo === "submenus") {
                cells = [
                    planesEscape(row.menu_name),
                    '<div class="planes-aux-primary">' +
                        '<span class="planes-aux-row-icon">' +
                            '<i class="fas fa-stream"></i>' +
                        '</span>' +
                        '<strong>' + planesEscape(row.name) + '</strong>' +
                    '</div>',
                    planesAuxBadge(row.asignado),
                    planesAuxBotonAccion(tipo, row)
                ];
            } else {
                cells = [
                    '<div class="planes-aux-primary">' +
                        '<span class="planes-aux-row-icon">' +
                            '<i class="fas fa-project-diagram"></i>' +
                        '</span>' +
                        '<strong>' + planesEscape(row.name) + '</strong>' +
                    '</div>',
                    planesEscape(row.menu_name),
                    planesEscape(row.submenu_name),
                    planesAuxBadge(row.asignado),
                    planesAuxBotonAccion(tipo, row)
                ];
            }

            return (
                '<article class="planes-aux-detail-row">' +
                    cells.map(function(cell, index) {
                        return (
                            '<div class="planes-aux-detail-cell ' +
                                (index === cells.length - 1
                                    ? "planes-aux-action-cell"
                                    : "") +
                            '">' +
                                cell +
                            '</div>'
                        );
                    }).join("") +
                '</article>'
            );
        }

        function planesAuxMiniCard(tipo, row) {
            let titulo = "";
            let icono = "fa-layer-group";
            let contenido = "";

            if (tipo === "configuraciones") {
                titulo = row.config;
                icono = "fa-sliders-h";
                contenido =
                    '<div class="planes-aux-mini-data">' +
                        '<span>Cantidad</span>' +
                        '<strong>' + planesEscape(row.valor) + '</strong>' +
                    '</div>';
            } else if (tipo === "menus") {
                titulo = row.name;
                icono = "fa-bars";
                contenido =
                    '<div class="planes-aux-mini-data">' +
                        '<span>Estado</span>' +
                        planesAuxBadge(row.asignado) +
                    '</div>';
            } else if (tipo === "submenus") {
                titulo = row.name;
                icono = "fa-stream";
                contenido =
                    '<div class="planes-aux-mini-data">' +
                        '<span>Menú padre</span>' +
                        '<strong>' + planesEscape(row.menu_name) + '</strong>' +
                    '</div>' +
                    '<div class="planes-aux-mini-data">' +
                        '<span>Estado</span>' +
                        planesAuxBadge(row.asignado) +
                    '</div>';
            } else {
                titulo = row.name;
                icono = "fa-project-diagram";
                contenido =
                    '<div class="planes-aux-mini-data">' +
                        '<span>Menú padre</span>' +
                        '<strong>' + planesEscape(row.menu_name) + '</strong>' +
                    '</div>' +
                    '<div class="planes-aux-mini-data">' +
                        '<span>Submenú nivel 1</span>' +
                        '<strong>' + planesEscape(row.submenu_name) + '</strong>' +
                    '</div>' +
                    '<div class="planes-aux-mini-data">' +
                        '<span>Estado</span>' +
                        planesAuxBadge(row.asignado) +
                    '</div>';
            }

            return (
                '<article class="planes-aux-mini-card">' +
                    '<div class="planes-aux-mini-topline"></div>' +
                    '<div class="planes-aux-mini-header">' +
                        '<span class="planes-aux-mini-icon">' +
                            '<i class="fas ' + icono + '"></i>' +
                        '</span>' +
                        '<div>' +
                            '<h4>' + planesEscape(titulo) + '</h4>' +
                            '<small>' +
                                planesEscape(planesAuxMeta[tipo].title) +
                            '</small>' +
                        '</div>' +
                    '</div>' +
                    '<div class="planes-aux-mini-body">' +
                        contenido +
                    '</div>' +
                    '<div class="planes-aux-mini-footer">' +
                        planesAuxBotonAccion(tipo, row) +
                    '</div>' +
                '</article>'
            );
        }

        function planesAuxSincronizarPageSize(tipo) {
            const state = planesAuxEstado(tipo);
            const miniatura = state.vista === "miniatura";
            const opciones = miniatura
                ? [6, 12, 18, 30]
                : [10, 25, 50, 100];

            let valor = miniatura
                ? state.porPaginaMiniatura
                : state.porPaginaDetalle;

            if (opciones.indexOf(valor) === -1) {
                valor = opciones[0];
            }

            state.porPagina = valor;

            const $select = $(planesAuxMeta[tipo].pageSize);
            $select.empty();

            opciones.forEach(function(item) {
                $select.append(
                    $("<option></option>")
                        .attr("value", item)
                        .text(item)
                );
            });

            $select.val(String(valor));
            refrescarSelect2Planes($select);
        }

        function planesAuxActualizarVistaBotones(tipo) {
            const state = planesAuxEstado(tipo);
            const movil = planesEsMovil();
            const selector =
                '[data-planes-aux-view="' + tipo + '"]';

            $(selector)
                .removeClass("active")
                .attr("aria-pressed", "false");

            $(selector + '[data-view="detalle"]')
                .prop("disabled", movil)
                .toggleClass("d-none", movil)
                .attr("aria-hidden", movil ? "true" : "false");

            $(selector + '[data-view="' + state.vista + '"]')
                .addClass("active")
                .attr("aria-pressed", "true");
        }

        function planesAuxCambiarVista(tipo, vista) {
            const state = planesAuxEstado(tipo);

            state.vista = planesEsMovil()
                ? "miniatura"
                : (vista === "miniatura"
                    ? "miniatura"
                    : "detalle");

            if (!planesEsMovil()) {
                state.vistaPreferida = state.vista;

                try {
                    localStorage.setItem(
                        PLANES_AUX_STORAGE_PREFIX + tipo + ".vista",
                        state.vistaPreferida
                    );
                } catch (e) {
                    console.warn(
                        "No se pudo guardar la vista del modal.",
                        e
                    );
                }
            }

            state.pagina = 1;
            planesAuxSincronizarPageSize(tipo);
            planesAuxActualizarVistaBotones(tipo);
            planesAuxRender(tipo);
        }

        function planesAuxFiltrar(tipo) {
            const state = planesAuxEstado(tipo);
            const q = planesNormalizarTexto(state.busqueda);

            state.filtered = state.rows.filter(function(row) {
                if (!q) {
                    return true;
                }

                return (
                    planesNormalizarTexto(
                        planesAuxTextoFila(tipo, row)
                    ).indexOf(q) !== -1
                );
            });

            const totalPaginas = Math.max(
                1,
                Math.ceil(
                    state.filtered.length / state.porPagina
                )
            );

            if (state.pagina > totalPaginas) {
                state.pagina = totalPaginas;
            }

            planesAuxRender(tipo);
        }

        function planesAuxPaginacionHtml(tipo, totalPaginas) {
            const state = planesAuxEstado(tipo);

            if (totalPaginas <= 1) {
                return "";
            }

            function boton(texto, pagina, disabled, active, icono) {
                return (
                    '<button type="button" ' +
                        'class="planes-aux-page-btn ' +
                            (active ? "active" : "") +
                        '" ' +
                        'data-planes-aux-page="' + tipo + '" ' +
                        'data-page="' + pagina + '" ' +
                        (disabled ? "disabled" : "") +
                    '>' +
                        (icono
                            ? '<i class="fas ' + icono + '"></i>'
                            : "") +
                        '<span>' + texto + '</span>' +
                    '</button>'
                );
            }

            let html = "";
            const pagina = state.pagina;

            html += boton(
                "Inicio",
                1,
                pagina === 1,
                false,
                "fa-angle-double-left"
            );

            html += boton(
                "Anterior",
                Math.max(1, pagina - 1),
                pagina === 1,
                false,
                "fa-angle-left"
            );

            let desde = Math.max(1, pagina - 2);
            let hasta = Math.min(
                totalPaginas,
                desde + 4
            );

            desde = Math.max(1, hasta - 4);

            for (let i = desde; i <= hasta; i++) {
                html += boton(
                    String(i),
                    i,
                    false,
                    i === pagina,
                    ""
                );
            }

            html += boton(
                "Siguiente",
                Math.min(totalPaginas, pagina + 1),
                pagina === totalPaginas,
                false,
                "fa-angle-right"
            );

            html += boton(
                "Final",
                totalPaginas,
                pagina === totalPaginas,
                false,
                "fa-angle-double-right"
            );

            return html;
        }

        function planesAuxRender(tipo) {
            const meta = planesAuxMeta[tipo];
            const state = planesAuxEstado(tipo);
            const $list = $(meta.listado);
            const $empty = $(meta.vacio);

            const total = state.filtered.length;
            const totalPaginas = Math.max(
                1,
                Math.ceil(total / state.porPagina)
            );

            state.pagina = Math.min(
                Math.max(1, state.pagina),
                totalPaginas
            );

            const inicio =
                (state.pagina - 1) * state.porPagina;

            const visibles = state.filtered.slice(
                inicio,
                inicio + state.porPagina
            );

            $list
                .removeClass(
                    "vista-detalle vista-miniatura"
                )
                .addClass(
                    "vista-" + state.vista
                )
                .attr("data-kind", tipo);

            if (!total) {
                $list.empty().hide();
                $empty.show();
                $(meta.info).text("0 registros");
                $(meta.paginacion).empty();
                return;
            }

            $empty.hide();
            $list.show();

            let html = "";

            if (state.vista === "detalle") {
                html += planesAuxHeaderDetalle(tipo);

                visibles.forEach(function(row) {
                    html += planesAuxFilaDetalle(
                        tipo,
                        row
                    );
                });
            } else {
                visibles.forEach(function(row) {
                    html += planesAuxMiniCard(
                        tipo,
                        row
                    );
                });
            }

            $list.html(html);

            const fin = Math.min(
                inicio + visibles.length,
                total
            );

            $(meta.info).text(
                "Mostrando registros del " +
                (inicio + 1) +
                " al " +
                fin +
                " de un total de " +
                total +
                " registros"
            );

            $(meta.paginacion).html(
                planesAuxPaginacionHtml(
                    tipo,
                    totalPaginas
                )
            );
        }

        function planesAuxTituloModal(tipo, planNombre) {
            const nombre = $.trim(String(planNombre || "Plan"));

            if (tipo === "configuraciones") {
                return "Configuraciones del Plan: " + nombre;
            }

            if (tipo === "menus") {
                return "Asignar Menús Principales al Plan: " + nombre;
            }

            if (tipo === "submenus") {
                return "Asignar Submenús Nivel 1 al Plan: " + nombre;
            }

            return "Asignar Submenús Nivel 2 al Plan: " + nombre;
        }

        function planesAuxSetRows(
            tipo,
            rows,
            planId,
            planNombre
        ) {
            const meta = planesAuxMeta[tipo];
            const state = planesAuxEstado(tipo);

            state.rows =
                Array.isArray(rows)
                    ? rows
                    : [];

            state.filtered =
                state.rows.slice();

            state.pagina = 1;
            state.busqueda = "";
            state.planId = planId || "";
            state.planNombre =
                planNombre ||
                state.planNombre ||
                "Plan";

            $(meta.modal + " .modal-title")
                .text(
                    planesAuxTituloModal(
                        tipo,
                        state.planNombre
                    )
                );

            state.vista = planesEsMovil()
                ? "miniatura"
                : state.vistaPreferida;

            $(meta.search).val("");
            $('[data-planes-aux-clear="' + tipo + '"]')
                .hide();

            planesAuxSincronizarPageSize(tipo);
            planesAuxActualizarVistaBotones(tipo);
            planesAuxFiltrar(tipo);
        }

        function planesAuxLoading(tipo) {
            const meta = planesAuxMeta[tipo];

            $(meta.vacio).hide();
            $(meta.info).text("Cargando...");
            $(meta.paginacion).empty();

            $(meta.listado)
                .removeClass(
                    "vista-detalle vista-miniatura"
                )
                .addClass("vista-detalle")
                .show()
                .html(
                    '<div class="planes-aux-loading">' +
                        '<i class="fas fa-spinner fa-spin"></i>' +
                        '<span>Cargando información...</span>' +
                    '</div>'
                );
        }

        function planesAuxActualizarAsignado(
            tipo,
            id,
            nuevoEstado
        ) {
            const state = planesAuxEstado(tipo);

            const key =
                tipo === "menus"
                    ? "menu_id"
                    : (
                        tipo === "submenus"
                            ? "submenu_id"
                            : "submenu1_id"
                    );

            const row = state.rows.find(function(item) {
                return String(item[key]) === String(id);
            });

            if (row) {
                row.asignado =
                    nuevoEstado ? 1 : 0;
            }

            planesAuxFiltrar(tipo);
        }

        function planesAuxActualizarConfiguraciones(
            planId,
            configuraciones
        ) {
            const state =
                planesAuxEstado("configuraciones");

            const rows = [];
            let index = 1;

            if (
                configuraciones &&
                typeof configuraciones === "object"
            ) {
                Object.keys(configuraciones).forEach(
                    function(clave) {
                        const opcion =
                            opcionesConfiguracion.find(
                                function(op) {
                                    return op.value === clave;
                                }
                            );

                        rows.push({
                            id: index++,
                            clave: clave,
                            config: opcion
                                ? opcion.text
                                : clave,
                            valor: configuraciones[clave],
                            plan_id: planId
                        });
                    }
                );
            }

            planesAuxSetRows(
                "configuraciones",
                rows,
                planId,
                state.planNombre
            );
        }

        function listar_configuraciones(
            plan_id,
            configuraciones,
            planNombre
        ) {
            const state =
                planesAuxEstado("configuraciones");

            state.planNombre =
                planNombre ||
                state.planNombre ||
                "Plan";

            $("#modalConfiguraciones .modal-title")
                .text(
                    "Configuraciones del Plan: " +
                    state.planNombre
                );

            planesAuxActualizarConfiguraciones(
                plan_id,
                configuraciones || {}
            );

            $("#modalConfiguraciones")
                .data("plan-id", plan_id)
                .modal({
                    show: true,
                    keyboard: false,
                    backdrop: "static"
                });
        }

        /* =========================================================
            EXPORTACIÓN AUXILIAR | EXCEL
        ========================================================= */
        function planesAuxExportRows(tipo) {
            const state = planesAuxEstado(tipo);
            const meta = planesAuxMeta[tipo];

            return state.filtered.map(function(row) {
                return meta.values(row).map(function(value) {
                    return (
                        value === null ||
                        typeof value === "undefined"
                    )
                        ? ""
                        : String(value);
                });
            });
        }

        function planesAuxResumen(tipo) {
            const state = planesAuxEstado(tipo);
            const rows = state.filtered || [];
            const total = rows.length;

            if (tipo === "configuraciones") {
                const conValor = rows.filter(function(row) {
                    return (
                        row.valor !== null &&
                        typeof row.valor !== "undefined" &&
                        String(row.valor).trim() !== ""
                    );
                }).length;

                const totalConfigurado = rows.reduce(function(acc, row) {
                    const numero = Number(row.valor);
                    return acc + (isNaN(numero) ? 0 : numero);
                }, 0);

                return [
                    {label: "REGISTROS", value: total, color: "#172B4D"},
                    {label: "CON VALOR", value: conValor, color: "#14804A"},
                    {label: "TOTAL CONFIGURADO", value: totalConfigurado, color: "#172B4D"},
                    {label: "PLAN", value: state.planNombre || "Plan", color: "#172B4D"}
                ];
            }

            const asignados = rows.filter(function(row) {
                return planesAuxEsAsignado(row.asignado);
            }).length;

            const noAsignados = Math.max(0, total - asignados);

            return [
                {label: "REGISTROS", value: total, color: "#172B4D"},
                {label: "ASIGNADOS", value: asignados, color: "#14804A"},
                {label: "NO ASIGNADOS", value: noAsignados, color: "#C9372C"},
                {label: "PLAN", value: state.planNombre || "Plan", color: "#172B4D"}
            ];
        }

        function planesAuxFiltroTexto(tipo) {
            const state = planesAuxEstado(tipo);
            const busqueda = $.trim(state.busqueda) || "Sin búsqueda";

            return (
                "Plan: " +
                (state.planNombre || "Plan") +
                "   |   Búsqueda: " +
                busqueda
            );
        }

        function planesAuxSubtitulo(tipo) {
            const state = planesAuxEstado(tipo);
            const nombre = state.planNombre || "Plan";

            if (tipo === "configuraciones") {
                return (
                    "Plan: " +
                    nombre +
                    " • Configuraciones y cantidades permitidas"
                );
            }

            if (tipo === "menus") {
                return (
                    "Plan: " +
                    nombre +
                    " • Administración de menús principales"
                );
            }

            if (tipo === "submenus") {
                return (
                    "Plan: " +
                    nombre +
                    " • Administración de submenús nivel 1"
                );
            }

            return (
                "Plan: " +
                nombre +
                " • Administración de submenús nivel 2"
            );
        }

        function planesAuxExcelStyleXml() {
            return (
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<fonts count="7">' +
                    '<font><sz val="10"/><name val="Calibri"/></font>' +
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
                    '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
                '</borders>' +
                '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
                '<cellXfs count="11">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
                '</styleSheet>'
            );
        }

        function planesAuxExportarExcel(tipo) {
            const state = planesAuxEstado(tipo);
            const meta = planesAuxMeta[tipo];
            const rows = planesAuxExportRows(tipo);
            const resumen = planesAuxResumen(tipo);

            if (!rows.length) {
                notificarPlan(
                    "warning",
                    "Sin información",
                    "No hay registros para exportar."
                );
                return;
            }

            if (typeof JSZip === "undefined") {
                notificarPlan(
                    "error",
                    "Excel no disponible",
                    "No se encontró JSZip para generar el archivo XLSX."
                );
                return;
            }

            const headers = meta.headers;
            const headerRow = 7;
            const firstDataRow = 8;
            const lastRow = Math.max(headerRow, headerRow + rows.length);
            const virtualLastCol = "H";
            const dataLastCol = planesExcelCol(headers.length - 1);
            const sheetRows = [];

            sheetRows.push(
                '<row r="1" ht="30" customHeight="1">' +
                    planesExcelCell(
                        "A1",
                        "IZZY • " + meta.title.toUpperCase(),
                        1,
                        false
                    ) +
                '</row>'
            );

            sheetRows.push(
                '<row r="2" ht="20" customHeight="1">' +
                    planesExcelCell(
                        "A2",
                        planesAuxSubtitulo(tipo) +
                        " • Generado: " +
                        new Date().toLocaleDateString("es-HN"),
                        2,
                        false
                    ) +
                '</row>'
            );

            sheetRows.push(
                '<row r="3">' +
                    planesExcelCell("A3", resumen[0].label, 6, false) +
                    planesExcelCell("C3", resumen[1].label, 6, false) +
                    planesExcelCell("E3", resumen[2].label, 6, false) +
                    planesExcelCell("G3", resumen[3].label, 6, false) +
                '</row>'
            );

            sheetRows.push(
                '<row r="4" ht="27" customHeight="1">' +
                    planesExcelCell("A4", resumen[0].value, 7, false) +
                    planesExcelCell("C4", resumen[1].value, 7, false) +
                    planesExcelCell("E4", resumen[2].value, 7, false) +
                    planesExcelCell("G4", resumen[3].value, 7, false) +
                '</row>'
            );

            sheetRows.push('<row r="5"></row>');

            sheetRows.push(
                '<row r="6">' +
                    planesExcelCell(
                        "A6",
                        "Detalle de " +
                        meta.title.toLowerCase() +
                        " filtrados",
                        8,
                        false
                    ) +
                '</row>'
            );

            sheetRows.push(
                '<row r="' + headerRow + '" ht="28" customHeight="1">' +
                    headers.map(function(header, index) {
                        return planesExcelCell(
                            planesExcelCol(index) + headerRow,
                            header,
                            3,
                            false
                        );
                    }).join("") +
                '</row>'
            );

            rows.forEach(function(row, rowIndex) {
                const excelRow = firstDataRow + rowIndex;

                sheetRows.push(
                    '<row r="' + excelRow + '" ht="34" customHeight="1">' +
                        row.map(function(value, colIndex) {
                            let style = 4;

                            if (
                                String(value) === "Asignado" ||
                                String(value) === "Activo"
                            ) {
                                style = 9;
                            } else if (
                                String(value) === "No asignado" ||
                                String(value) === "Inactivo"
                            ) {
                                style = 10;
                            }

                            return planesExcelCell(
                                planesExcelCol(colIndex) + excelRow,
                                value,
                                style,
                                false
                            );
                        }).join("") +
                    '</row>'
                );
            });

            const sheetXml =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:' + virtualLastCol + lastRow + '"/>' +
                '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                '</sheetView></sheetViews>' +
                '<cols>' +
                    '<col min="1" max="1" width="30" customWidth="1"/>' +
                    '<col min="2" max="4" width="24" customWidth="1"/>' +
                    '<col min="5" max="8" width="16" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join("") + '</sheetData>' +
                '<autoFilter ref="A7:' + dataLastCol + lastRow + '"/>' +
                '<mergeCells count="10">' +
                    '<mergeCell ref="A1:H1"/><mergeCell ref="A2:H2"/>' +
                    '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                    '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                    '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                    '<mergeCell ref="G3:H3"/><mergeCell ref="G4:H4"/>' +
                '</mergeCells>' +
                '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
                '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
                '</worksheet>';

            const workbookXml =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets>' +
                '</workbook>';

            const workbookRels =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
                '</Relationships>';

            const rootRels =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
                '</Relationships>';

            const contentTypes =
                '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
            zip.folder("xl").file("styles.xml", planesAuxExcelStyleXml());
            zip.folder("xl").folder("_rels").file("workbook.xml.rels", workbookRels);
            zip.folder("xl").folder("worksheets").file("sheet1.xml", sheetXml);

            const opciones = {
                type: "blob",
                mimeType:
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                compression: "DEFLATE"
            };

            const promesa =
                typeof zip.generateAsync === "function"
                    ? zip.generateAsync(opciones)
                    : Promise.resolve(zip.generate(opciones));

            promesa
                .then(function(blob) {
                    descargarBlobPlanes(
                        blob,
                        meta.file +
                        "_" +
                        String(state.planNombre || "Plan")
                            .replace(/[^\w\-]+/g, "_") +
                        ".xlsx"
                    );
                })
                .catch(function(error) {
                    console.error(error);

                    notificarPlan(
                        "error",
                        "Error",
                        "No se pudo generar el archivo Excel."
                    );
                });
        }

        function planesAuxPdfEncabezadoPremium(tipo, rows, logoDataUrl) {
            const state = planesAuxEstado(tipo);
            const meta = planesAuxMeta[tipo];
            const resumen = planesAuxResumen(tipo);
            const logoCell = planesPdfLogoPlate(logoDataUrl);

            return [
                {
                    table: {
                        widths: [100, "*", 155],
                        body: [[
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [12, 10, 0, 10],
                                stack: [logoCell]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [0, 10, 0, 10],
                                stack: [
                                    {
                                        text: meta.title.toUpperCase(),
                                        fontSize: 16,
                                        bold: true,
                                        color: "#FFFFFF"
                                    },
                                    {
                                        text: planesAuxSubtitulo(tipo),
                                        fontSize: 7.5,
                                        color: "#D8E5F0",
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: "#17324D",
                                margin: [0, 10, 12, 10],
                                stack: [
                                    {
                                        text: "REPORTE EJECUTIVO",
                                        fontSize: 6.5,
                                        bold: true,
                                        color: "#72E2E5",
                                        alignment: "right"
                                    },
                                    {
                                        text: new Date().toLocaleDateString("es-HN"),
                                        fontSize: 9,
                                        bold: true,
                                        color: "#FFFFFF",
                                        alignment: "right",
                                        margin: [0, 3, 0, 0]
                                    },
                                    {
                                        text: rows.length + " registro(s) filtrado(s)",
                                        fontSize: 6.5,
                                        color: "#D8E5F0",
                                        alignment: "right",
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineWidth: function() { return 0; },
                        vLineWidth: function() { return 0; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ["*"],
                        body: [[{
                            text: "Filtros aplicados: " + planesAuxFiltroTexto(tipo),
                            fontSize: 6.8,
                            color: "#52627A",
                            margin: [10, 7, 10, 7],
                            fillColor: "#F7F9FC"
                        }]]
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ["*", "*", "*", "*"],
                        body: [[
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: resumen[0].label, fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(resumen[0].value), fontSize: 13, bold: true, color: resumen[0].color}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: resumen[1].label, fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(resumen[1].value), fontSize: 13, bold: true, color: resumen[1].color}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: resumen[2].label, fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(resumen[2].value), fontSize: 13, bold: true, color: resumen[2].color}
                                ]
                            },
                            {
                                fillColor: "#F7F9FC",
                                margin: [8, 7, 8, 7],
                                stack: [
                                    {text: resumen[3].label, fontSize: 6.3, bold: true, color: "#6B778C"},
                                    {text: String(resumen[3].value), fontSize: 10.5, bold: true, color: resumen[3].color}
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 12]
                }
            ];
        }

        function planesAuxPdfContenidoDetalle(tipo, rows) {
            const meta = planesAuxMeta[tipo];

            const body = [
                meta.headers.map(function(header) {
                    return {
                        text: header,
                        style: "th",
                        fillColor: "#17324D"
                    };
                })
            ];

            rows.forEach(function(row, index) {
                const fill =
                    index % 2 === 0
                        ? "#FFFFFF"
                        : "#F7F9FC";

                body.push(
                    row.map(function(value) {
                        const texto = String(value || "—");
                        const esEstado =
                            texto === "Asignado" ||
                            texto === "No asignado";

                        return {
                            text: texto,
                            style: esEstado ? "tdCenter" : "td",
                            color:
                                texto === "Asignado"
                                    ? "#14804A"
                                    : (
                                        texto === "No asignado"
                                            ? "#C9372C"
                                            : "#253858"
                                    ),
                            bold: esEstado,
                            fillColor: fill
                        };
                    })
                );
            });

            const widths =
                meta.headers.length === 2
                    ? ["*", "*"]
                    : (
                        meta.headers.length === 3
                            ? ["*", "*", 110]
                            : ["*", "*", "*", 110]
                    );

            return [
                {
                    text: "VISTA DETALLE",
                    fontSize: 7,
                    bold: true,
                    color: "#17324D",
                    margin: [0, 1, 0, 7]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: widths,
                        body: body
                    },
                    layout: {
                        hLineColor: function() { return "#DDE3EA"; },
                        vLineColor: function() { return "#DDE3EA"; },
                        hLineWidth: function() { return 0.55; },
                        vLineWidth: function() { return 0.55; },
                        paddingLeft: function() { return 5; },
                        paddingRight: function() { return 5; },
                        paddingTop: function() { return 6; },
                        paddingBottom: function() { return 6; }
                    }
                }
            ];
        }

        function planesAuxPrevisualizarPdf(tipo) {
            const state = planesAuxEstado(tipo);
            const meta = planesAuxMeta[tipo];
            const rows = planesAuxExportRows(tipo);

            if (!rows.length) {
                notificarPlan(
                    "warning",
                    "Sin información",
                    "No hay registros para exportar."
                );
                return;
            }

            if (
                typeof pdfMake === "undefined" ||
                typeof abrirModalPdfPublico !== "function"
            ) {
                notificarPlan(
                    "error",
                    "PDF no disponible",
                    "No están disponibles los componentes del PDF."
                );
                return;
            }

            const generar = function(logoDataUrl) {
                const contenido =
                    planesAuxPdfEncabezadoPremium(
                        tipo,
                        rows,
                        logoDataUrl
                    ).concat(
                        planesAuxPdfContenidoDetalle(
                            tipo,
                            rows
                        )
                    );

                const doc = {
                    pageSize: "LETTER",
                    pageOrientation: "landscape",
                    pageMargins: [28, 28, 28, 34],

                    header: function() {
                        return {
                            margin: [28, 12, 28, 0],
                            canvas: [{
                                type: "line",
                                x1: 0,
                                y1: 0,
                                x2: 736,
                                y2: 0,
                                lineWidth: 2,
                                lineColor: "#0EA5A8"
                            }]
                        };
                    },

                    footer: function(page, pages) {
                        return {
                            margin: [28, 8, 28, 0],
                            columns: [
                                {
                                    text: "IZZY • Administrar Planes",
                                    fontSize: 7,
                                    color: "#7A869A"
                                },
                                {
                                    text:
                                        "Página " +
                                        page +
                                        " de " +
                                        pages,
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
                            fontSize: 6.2,
                            bold: true,
                            color: "#FFFFFF",
                            alignment: "center"
                        },
                        td: {
                            fontSize: 6.5,
                            color: "#253858"
                        },
                        tdCenter: {
                            fontSize: 6.5,
                            color: "#253858",
                            alignment: "center"
                        }
                    },

                    defaultStyle: {
                        fontSize: 8,
                        color: "#253858"
                    }
                };

                const pdf = pdfMake.createPdf(doc);
                const nombre =
                    meta.file +
                    "_" +
                    String(state.planNombre || "Plan")
                        .replace(/[^\w\-]+/g, "_") +
                    ".pdf";

                if (typeof pdf.getDataUrl === "function") {
                    pdf.getDataUrl(function(dataUrl) {
                        abrirModalPdfPublico(
                            dataUrl,
                            meta.title +
                            " - " +
                            (state.planNombre || "Plan"),
                            nombre
                        );
                    });
                    return;
                }

                if (typeof pdf.getBase64 === "function") {
                    pdf.getBase64(function(base64) {
                        abrirModalPdfPublico(
                            "data:application/pdf;base64," +
                            base64,
                            meta.title +
                            " - " +
                            (state.planNombre || "Plan"),
                            nombre
                        );
                    });
                    return;
                }

                notificarPlan(
                    "error",
                    "PDF no disponible",
                    "La versión actual de pdfMake no permite una vista previa compatible."
                );
            };

            if (
                typeof imagen !== "undefined" &&
                typeof imagen === "string" &&
                imagen.indexOf("data:image/") === 0
            ) {
                generar(imagen);
                return;
            }

            planesObtenerLogoPdf(function(logoDataUrl) {
                try {
                    imagen = logoDataUrl;
                } catch (e) {}

                generar(logoDataUrl);
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
                            recargarPlanesPrincipal(true);

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
                            const configs =
                                response.configuraciones || {};

                            planesAuxActualizarConfiguraciones(
                                planId,
                                configs
                            );

                            recargarPlanesPrincipal(true);

                            notificarPlan(
                                "success",
                                "Éxito",
                                response.message
                            );
                        } else {
                            notificarPlan(
                                "error",
                                "Error",
                                response.message
                            );
                        }
                    },
                    error: function(xhr) {
                        console.error("Error al eliminar:", xhr.responseText);
                        notificarPlan("error", "Error", "Error de conexión al eliminar configuración");
                    },
                    complete: function() {
                        $button.prop("disabled", false).html('<i class="fas fa-times"></i><span>Quitar</span>');
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
                            recargarPlanesPrincipal(true);

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
            CARGAR MENÚS / SUBMENÚS PARA ASIGNACIÓN - DIVs
        ========================================================= */
        function listar_menus_asignacion(
            plan_id,
            planNombre
        ) {
            planesAuxLoading("menus");

            $.ajax({
                method: "POST",
                url: PLANES_URLS.obtenerMenus,
                data: {
                    plan_id: plan_id
                },
                dataType: "json"
            })
            .done(function(json) {
                if (!json || !json.success) {
                    planesAuxSetRows(
                        "menus",
                        [],
                        plan_id,
                        planNombre
                    );

                    notificarPlan(
                        "error",
                        "Error",
                        (json && json.message) ||
                        "Error al cargar los menús"
                    );
                    return;
                }

                const rows =
                    (json.data || []).map(
                        function(menu) {
                            return {
                                menu_id: menu.menu_id,
                                name: menu.name,
                                asignado:
                                    planesAuxEsAsignado(
                                        menu.asignado
                                    )
                                        ? 1
                                        : 0
                            };
                        }
                    );

                const contador =
                    rows.filter(function(row) {
                        return planesAuxEsAsignado(
                            row.asignado
                        );
                    }).length;

                $("#contador-menus-" + plan_id)
                    .text(
                        contador + " asignados"
                    );

                planesAuxSetRows(
                    "menus",
                    rows,
                    plan_id,
                    planNombre
                );
            })
            .fail(function(xhr) {
                console.error(
                    "Error al cargar menús:",
                    xhr.responseText
                );

                planesAuxSetRows(
                    "menus",
                    [],
                    plan_id,
                    planNombre
                );

                notificarPlan(
                    "error",
                    "Error",
                    "Error de conexión al cargar los menús"
                );
            });
        }

        function listar_submenus_asignacion(
            plan_id,
            planNombre
        ) {
            planesAuxLoading("submenus");

            $.ajax({
                method: "POST",
                url: PLANES_URLS.obtenerSubmenus,
                data: {
                    plan_id: plan_id
                },
                dataType: "json"
            })
            .done(function(json) {
                if (!json || !json.success) {
                    planesAuxSetRows(
                        "submenus",
                        [],
                        plan_id,
                        planNombre
                    );

                    notificarPlan(
                        "error",
                        "Error",
                        (json && json.message) ||
                        "Error al cargar los submenús"
                    );
                    return;
                }

                const rows =
                    (json.data || []).map(
                        function(submenu) {
                            return {
                                submenu_id:
                                    submenu.submenu_id,
                                menu_name:
                                    submenu.descripcion_padre,
                                name:
                                    submenu.descripcion,
                                asignado:
                                    planesAuxEsAsignado(
                                        submenu.asignado
                                    )
                                        ? 1
                                        : 0
                            };
                        }
                    );

                const contador =
                    rows.filter(function(row) {
                        return planesAuxEsAsignado(
                            row.asignado
                        );
                    }).length;

                $("#contador-submenus-" + plan_id)
                    .text(
                        contador + " asignados"
                    );

                planesAuxSetRows(
                    "submenus",
                    rows,
                    plan_id,
                    planNombre
                );
            })
            .fail(function(xhr) {
                console.error(
                    "Error al cargar submenús:",
                    xhr.responseText
                );

                planesAuxSetRows(
                    "submenus",
                    [],
                    plan_id,
                    planNombre
                );

                notificarPlan(
                    "error",
                    "Error",
                    "Error de conexión al cargar los submenús"
                );
            });
        }

        function listar_submenus2_asignacion(
            plan_id,
            planNombre
        ) {
            planesAuxLoading("submenus2");

            $.ajax({
                method: "POST",
                url: PLANES_URLS.obtenerSubmenus2,
                data: {
                    plan_id: plan_id
                },
                dataType: "json"
            })
            .done(function(json) {
                if (!json || !json.success) {
                    planesAuxSetRows(
                        "submenus2",
                        [],
                        plan_id,
                        planNombre
                    );

                    notificarPlan(
                        "error",
                        "Error",
                        (json && json.message) ||
                        "Error al cargar los submenús nivel 2"
                    );
                    return;
                }

                const rows =
                    (json.data || []).map(
                        function(s2) {
                            return {
                                submenu1_id:
                                    s2.submenu1_id,
                                menu_name:
                                    s2.descripcion_padre,
                                submenu_name:
                                    s2.descripcion,
                                name:
                                    s2.descripcion_menu,
                                asignado:
                                    planesAuxEsAsignado(
                                        s2.asignado
                                    )
                                        ? 1
                                        : 0
                            };
                        }
                    );

                const contador =
                    rows.filter(function(row) {
                        return planesAuxEsAsignado(
                            row.asignado
                        );
                    }).length;

                $("#contador-submenus2-" + plan_id)
                    .text(
                        contador + " asignados"
                    );

                planesAuxSetRows(
                    "submenus2",
                    rows,
                    plan_id,
                    planNombre
                );
            })
            .fail(function(xhr) {
                console.error(
                    "Error al cargar submenús nivel 2:",
                    xhr.responseText
                );

                planesAuxSetRows(
                    "submenus2",
                    [],
                    plan_id,
                    planNombre
                );

                notificarPlan(
                    "error",
                    "Error",
                    "Error de conexión al cargar los submenús nivel 2"
                );
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

        function actualizarUIBotonAsignacion(
            $button,
            asignadoActual
        ) {
            const nuevoEstado =
                !planesAuxEsAsignado(asignadoActual);

            if ($button.hasClass("btn-toggle-menu")) {
                planesAuxActualizarAsignado(
                    "menus",
                    $button.data("menu-id"),
                    nuevoEstado
                );
                return;
            }

            if ($button.hasClass("btn-toggle-submenu")) {
                planesAuxActualizarAsignado(
                    "submenus",
                    $button.data("submenu-id"),
                    nuevoEstado
                );
                return;
            }

            if ($button.hasClass("btn-toggle-submenu2")) {
                planesAuxActualizarAsignado(
                    "submenus2",
                    $button.data("submenu2-id"),
                    nuevoEstado
                );
            }
        }

        /* =========================================================
            STACKING DEL DROPDOWN DE ACCIONES
            La fila que tiene el menú abierto sube por encima de
            todas las demás filas/cards. Evita depender de :has().
        ========================================================= */
        function limpiarStackDropdownPlanes() {
            $("#planesListado .planes-detail-row, #planesListado .planes-mini-card")
                .removeClass("planes-dropdown-open");
        }

        function activarStackDropdownPlanes($dropdown) {
            limpiarStackDropdownPlanes();

            if (!$dropdown || !$dropdown.length) {
                return;
            }

            $dropdown
                .closest(".planes-detail-row, .planes-mini-card")
                .addClass("planes-dropdown-open");
        }

        /* =========================================================
           PLANES | DROPDOWN DE ACCIONES ADAPTATIVO
           Posicionamiento real contra viewport, sin invadir modales.
           ========================================================= */
        var planesDropdownActivo = null;

        function planesMedirDropdown($menu) {
            var menu = $menu && $menu.length ? $menu[0] : null;
            if (!menu) return { width: 220, height: 160 };
            var wasShow = $menu.hasClass('show');
            var cssText = menu.style.cssText;
            $menu.addClass('show');
            menu.style.setProperty('display','block','important');
            menu.style.setProperty('visibility','hidden','important');
            menu.style.setProperty('position','fixed','important');
            menu.style.setProperty('top','0px','important');
            menu.style.setProperty('left','0px','important');
            menu.style.setProperty('right','auto','important');
            menu.style.setProperty('bottom','auto','important');
            menu.style.setProperty('transform','none','important');
            var r = menu.getBoundingClientRect();
            menu.style.cssText = cssText;
            if (!wasShow) $menu.removeClass('show');
            return { width: Math.max(r.width || 0, 220), height: Math.max(r.height || 0, 1) };
        }

        function planesLimpiarEstilosDropdown($dropdown) {
            if (!$dropdown || !$dropdown.length) return;
            var $menu = $dropdown.children('.dropdown-menu').first();
            var menu = $menu[0];
            $dropdown.removeClass('show dropup dropright dropleft');
            $menu.removeClass('show dropdown-menu-right').removeAttr('x-placement data-popper-placement data-izzy-placement');
            if (menu) {
                ['display','visibility','position','top','left','right','bottom','transform','z-index','max-height','overflow-y'].forEach(function(prop) { menu.style.removeProperty(prop); });
            }
            var $row = $dropdown.closest('.planes-detail-row, .planes-mini-card');
            $row.removeClass('planes-dropdown-open');
            if ($row.length) $row[0].style.removeProperty('transform');
        }

        function planesObtenerBoton($dropdown) {
            return $dropdown.children('.planes-acciones-toggle').first();
        }

        function planesCerrarDropdown($dropdown) {
            if (!$dropdown || !$dropdown.length) return;
            planesObtenerBoton($dropdown).attr('aria-expanded','false');
            planesLimpiarEstilosDropdown($dropdown);
            if (planesDropdownActivo && planesDropdownActivo.length && planesDropdownActivo.is($dropdown)) planesDropdownActivo = null;
        }

        function planesCerrarTodosDropdowns($excepto) {
            $('#planesListado .planes-actions-dropdown').each(function() {
                var $d = $(this);
                if ($excepto && $excepto.length && $d.is($excepto)) return;
                planesCerrarDropdown($d);
            });
        }

        function planesPosicionarDropdown($dropdown) {
            var $button = planesObtenerBoton($dropdown);
            var $menu = $dropdown.children('.dropdown-menu').first();
            if (!$button.length || !$menu.length) return false;

            var button = $button[0], menu = $menu[0];
            var rect = button.getBoundingClientRect();
            var size = planesMedirDropdown($menu);
            var vw = window.innerWidth || document.documentElement.clientWidth || 0;
            var vh = window.innerHeight || document.documentElement.clientHeight || 0;
            var margin = 10, gap = 7;
            var below = vh - rect.bottom - margin;
            var above = rect.top - margin;
            var right = vw - rect.right - margin;
            var leftSpace = rect.left - margin;
            var top, left, placement;

            if (below >= size.height + gap) { top = rect.bottom + gap; placement = 'bottom'; }
            else if (above >= size.height + gap) { top = rect.top - size.height - gap; placement = 'top'; }
            else if (right >= size.width + gap) { left = rect.right + gap; top = rect.top; placement = 'right'; }
            else if (leftSpace >= size.width + gap) { left = rect.left - size.width - gap; top = rect.top; placement = 'left'; }
            else { top = above > below ? rect.top - size.height - gap : rect.bottom + gap; placement = above > below ? 'top-clamped' : 'bottom-clamped'; }

            if (left === undefined) {
                left = rect.left;
                if (left + size.width > vw - margin) left = rect.right - size.width;
            }
            left = Math.max(margin, Math.min(left, Math.max(margin, vw - size.width - margin)));
            top = Math.max(margin, Math.min(top, Math.max(margin, vh - Math.min(size.height, vh - margin * 2) - margin)));

            menu.style.setProperty('display','block','important');
            menu.style.setProperty('visibility','visible','important');
            menu.style.setProperty('position','fixed','important');
            menu.style.setProperty('left',Math.round(left)+'px','important');
            menu.style.setProperty('top',Math.round(top)+'px','important');
            menu.style.setProperty('right','auto','important');
            menu.style.setProperty('bottom','auto','important');
            menu.style.setProperty('transform','none','important');
            menu.style.setProperty('z-index','1985','important');
            menu.style.setProperty('max-height',Math.max(90,vh-margin*2)+'px','important');
            menu.style.setProperty('overflow-y','auto','important');
            menu.setAttribute('data-izzy-placement', placement);
            $menu.addClass('show');
            $button.attr('aria-expanded','true');

            var $row = $dropdown.closest('.planes-detail-row, .planes-mini-card');
            $row.addClass('planes-dropdown-open');
            if ($row.length) $row[0].style.setProperty('transform','none','important');
            planesDropdownActivo = $dropdown;
            return true;
        }

        function inicializarDropdownAccionesPlanes() {
    // El administrador global de main.php usa portal al <body> y evita conflictos con Bootstrap/Popper.
    if (window.IZZYActionDropdown && window.IZZYActionDropdown.isGlobalManager) return;
            var $root = $('#planesListado').first();
            if (!$root.length) $root = $('body');

            $root.off('click.planesDropdownAdaptativo', '.planes-actions-dropdown .planes-acciones-toggle')
                .on('click.planesDropdownAdaptativo', '.planes-actions-dropdown .planes-acciones-toggle', function(e) {
                    e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
                    var $dropdown = $(this).closest('.planes-actions-dropdown');
                    var estaba = $dropdown.children('.dropdown-menu').hasClass('show');
                    planesCerrarTodosDropdowns($dropdown);
                    if (estaba) { planesCerrarDropdown($dropdown); return; }
                    planesLimpiarEstilosDropdown($dropdown);
                    planesPosicionarDropdown($dropdown);
                });

            $root.off('click.planesDropdownItem', '.planes-actions-dropdown .dropdown-item, .planes-actions-dropdown .accion-item')
                .on('click.planesDropdownItem', '.planes-actions-dropdown .dropdown-item, .planes-actions-dropdown .accion-item', function() {
                    var $dropdown = $(this).closest('.planes-actions-dropdown');
                    window.setTimeout(function() { planesLimpiarEstilosDropdown($dropdown); }, 0);
                });

            $(document).off('click.planesDropdownOutside').on('click.planesDropdownOutside', function(e) {
                if (!$(e.target).closest('.planes-actions-dropdown').length) planesCerrarTodosDropdowns();
            });
            $(document).off('show.bs.modal.planesDropdown').on('show.bs.modal.planesDropdown', function() { planesCerrarTodosDropdowns(); });
            $(document).off('keydown.planesDropdown').on('keydown.planesDropdown', function(e) { if (e.key === 'Escape') planesCerrarTodosDropdowns(); });
            $(window).off('resize.planesDropdown scroll.planesDropdown').on('resize.planesDropdown scroll.planesDropdown', function() { planesCerrarTodosDropdowns(); });
        }

        /* =========================================================
            EVENTOS
        ========================================================= */
        function registrarEventosPlanes() {
            inicializarDropdownAccionesPlanes();
            $("#formFiltrosPlanes")
                .off("submit.planesFiltros")
                .on("submit.planesFiltros", function(e) {
                    e.preventDefault();

                    planesState.filtroEstado =
                        $("#filtroEstadoPlanes").val() || "todos";

                    planesState.filtroConfiguracion =
                        $("#filtroConfiguracionPlanes").val() || "";

                    planesState.pagina = 1;
                    aplicarFiltrosPlanesPrincipal();
                });

            $("#formFiltrosPlanes")
                .off("reset.planesFiltros")
                .on("reset.planesFiltros", function() {
                    setTimeout(function() {
                        planesState.filtroEstado = "todos";
                        planesState.filtroConfiguracion = "";
                        planesState.pagina = 1;

                        $("#filtroEstadoPlanes").val("todos");
                        $("#filtroConfiguracionPlanes").val("");

                        refrescarSelect2Planes(
                            $("#filtroEstadoPlanes")
                        );

                        refrescarSelect2Planes(
                            $("#filtroConfiguracionPlanes")
                        );

                        aplicarFiltrosPlanesPrincipal();
                    }, 50);
                });

            $("#filtroEstadoPlanes, #filtroConfiguracionPlanes")
                .off("change.planesFiltroRapido")
                .on("change.planesFiltroRapido", function() {
                    planesState.filtroEstado =
                        $("#filtroEstadoPlanes").val() || "todos";

                    planesState.filtroConfiguracion =
                        $("#filtroConfiguracionPlanes").val() || "";

                    planesState.pagina = 1;
                    aplicarFiltrosPlanesPrincipal();
                });

            $("#buscarPlanes")
                .off("input.planesSearch")
                .on("input.planesSearch", function() {
                    planesState.busqueda =
                        $.trim($(this).val()).toLowerCase();

                    planesState.pagina = 1;
                    aplicarFiltrosPlanesPrincipal();
                });

            $("#planesPageSize")
                .off("change.planesPageSize")
                .on("change.planesPageSize", function() {
                    const valor = parseInt($(this).val(), 10);

                    planesState.porPagina =
                        isNaN(valor) || valor <= 0
                            ? (planesState.vista === "miniatura" ? 6 : 10)
                            : valor;

                    if (planesState.vista === "miniatura") {
                        planesState.porPaginaMiniatura = planesState.porPagina;
                    } else {
                        planesState.porPaginaDetalle = planesState.porPagina;
                    }

                    planesState.pagina = 1;
                    renderPlanesPrincipal();
                });

            $(".planes-view-btn")
                .off("click.planesVista")
                .on("click.planesVista", function() {
                    cambiarVistaPlanes($(this).data("view"));
                });

            $("#planesPaginacion")
                .off("click.planesPage", ".planes-page-btn")
                .on("click.planesPage", ".planes-page-btn", function() {
                    if ($(this).prop("disabled")) {
                        return;
                    }

                    planesState.pagina =
                        parseInt($(this).data("page"), 10) || 1;

                    renderPlanesPrincipal();
                });

            $("#btnActualizarPlanes")
                .off("click.planesRefresh")
                .on("click.planesRefresh", function() {
                    recargarPlanesPrincipal(true);
                });

            $("#btnIngresarPlan")
                .off("click.planesIngresar")
                .on("click.planesIngresar", function() {
                    resetFormulario();

                    const $card = $("#cardFormularioPlan");

                    if ($card.length) {
                        $("html, body").animate(
                            {scrollTop: $card.offset().top - 80},
                            250
                        );
                    }

                    setTimeout(function() {
                        $("#nombre_plan").trigger("focus");
                    }, 280);
                });

            $("#btnExcelPlanes")
                .off("click.planesExcel")
                .on("click.planesExcel", exportarPlanesExcelPremium);

            $("#btnPdfPlanes")
                .off("click.planesPdf")
                .on("click.planesPdf", previsualizarPlanesPdfPremium);

            $(document)
                .off(
                    "input.planesAuxSearch",
                    "[data-planes-aux-search]"
                )
                .on(
                    "input.planesAuxSearch",
                    "[data-planes-aux-search]",
                    function() {
                        const tipo =
                            $(this)
                                .data("planes-aux-search");

                        const state =
                            planesAuxEstado(tipo);

                        state.busqueda =
                            $.trim($(this).val());

                        state.pagina = 1;

                        $(
                            '[data-planes-aux-clear="' +
                            tipo +
                            '"]'
                        ).toggle(
                            !!state.busqueda
                        );

                        planesAuxFiltrar(tipo);
                    }
                );

            $(document)
                .off(
                    "click.planesAuxClear",
                    "[data-planes-aux-clear]"
                )
                .on(
                    "click.planesAuxClear",
                    "[data-planes-aux-clear]",
                    function() {
                        const tipo =
                            $(this)
                                .data("planes-aux-clear");

                        const state =
                            planesAuxEstado(tipo);

                        const $input =
                            $(planesAuxMeta[tipo].search);

                        $input.val("");
                        $(this).hide();

                        state.busqueda = "";
                        state.pagina = 1;

                        planesAuxFiltrar(tipo);
                        $input.trigger("focus");
                    }
                );

            $(document)
                .off(
                    "change.planesAuxPageSize",
                    "[data-planes-aux-page-size]"
                )
                .on(
                    "change.planesAuxPageSize",
                    "[data-planes-aux-page-size]",
                    function() {
                        const tipo =
                            $(this)
                                .data("planes-aux-page-size");

                        const state =
                            planesAuxEstado(tipo);

                        const valor =
                            parseInt(
                                $(this).val(),
                                10
                            );

                        state.porPagina =
                            isNaN(valor) ||
                            valor <= 0
                                ? (
                                    state.vista ===
                                    "miniatura"
                                        ? 6
                                        : 10
                                )
                                : valor;

                        if (
                            state.vista ===
                            "miniatura"
                        ) {
                            state.porPaginaMiniatura =
                                state.porPagina;
                        } else {
                            state.porPaginaDetalle =
                                state.porPagina;
                        }

                        state.pagina = 1;
                        planesAuxRender(tipo);
                    }
                );

            $(document)
                .off(
                    "click.planesAuxView",
                    "[data-planes-aux-view]"
                )
                .on(
                    "click.planesAuxView",
                    "[data-planes-aux-view]",
                    function() {
                        planesAuxCambiarVista(
                            $(this)
                                .data("planes-aux-view"),
                            $(this)
                                .data("view")
                        );
                    }
                );

            $(document)
                .off(
                    "click.planesAuxPage",
                    ".planes-aux-page-btn"
                )
                .on(
                    "click.planesAuxPage",
                    ".planes-aux-page-btn",
                    function() {
                        if ($(this).prop("disabled")) {
                            return;
                        }

                        const tipo =
                            $(this)
                                .data("planes-aux-page");

                        const state =
                            planesAuxEstado(tipo);

                        state.pagina =
                            parseInt(
                                $(this).data("page"),
                                10
                            ) || 1;

                        planesAuxRender(tipo);
                    }
                );

            $(document)
                .off(
                    "click.planesAuxExcel",
                    "[data-planes-aux-excel]"
                )
                .on(
                    "click.planesAuxExcel",
                    "[data-planes-aux-excel]",
                    function() {
                        planesAuxExportarExcel(
                            $(this)
                                .data("planes-aux-excel")
                        );
                    }
                );

            $(document)
                .off(
                    "click.planesAuxPdf",
                    "[data-planes-aux-pdf]"
                )
                .on(
                    "click.planesAuxPdf",
                    "[data-planes-aux-pdf]",
                    function() {
                        planesAuxPrevisualizarPdf(
                            $(this)
                                .data("planes-aux-pdf")
                        );
                    }
                );

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
                const planId = $(this).data("plan-id");
                const rowData = obtenerPlanStatePorId(planId);
                const configs = rowData ? normalizarConfiguracionesFila(rowData) : {};
                const planNombre = rowData ? rowData.nombre : "Sin nombre";

                $("#modalConfiguraciones .modal-title")
                    .text("Configuraciones del Plan: " + planNombre);

                listar_configuraciones(planId, configs, planNombre);
            });

            $(document).off("click.planesEditar", ".btn-editar").on("click.planesEditar", ".btn-editar", function() {
                const planId = $(this).data("id");

                cargarPlanParaEditar(planId);

                const $card = $("#cardFormularioPlan");

                if ($card.length) {
                    $("html, body").animate(
                        {scrollTop: $card.offset().top - 80},
                        250
                    );
                }
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
                const asignado = planesAuxEsAsignado($button.data("asignado"));
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
                            recargarPlanesPrincipal(true);

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
                const asignado = planesAuxEsAsignado($button.data("asignado"));
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
                            recargarPlanesPrincipal(true);

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
                const asignado = planesAuxEsAsignado($button.data("asignado"));
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
                            recargarPlanesPrincipal(true);

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

            let planesAuxResponsiveTimer = null;

            $(window)
                .off(
                    "resize.planesAuxResponsive orientationchange.planesAuxResponsive"
                )
                .on(
                    "resize.planesAuxResponsive orientationchange.planesAuxResponsive",
                    function() {
                        clearTimeout(
                            planesAuxResponsiveTimer
                        );

                        planesAuxResponsiveTimer =
                            setTimeout(function() {
                                Object.keys(
                                    planesAuxStates
                                ).forEach(
                                    function(tipo) {
                                        const state =
                                            planesAuxEstado(tipo);

                                        const objetivo =
                                            planesEsMovil()
                                                ? "miniatura"
                                                : state.vistaPreferida;

                                        if (
                                            state.vista ===
                                            objetivo
                                        ) {
                                            planesAuxActualizarVistaBotones(
                                                tipo
                                            );
                                            return;
                                        }

                                        state.vista =
                                            objetivo;

                                        state.pagina = 1;

                                        planesAuxSincronizarPageSize(
                                            tipo
                                        );

                                        planesAuxActualizarVistaBotones(
                                            tipo
                                        );

                                        planesAuxRender(
                                            tipo
                                        );
                                    }
                                );
                            }, 120);
                    }
                );

            $(document).off("click.planesAbrirMenus", ".btn-asignar-menu").on("click.planesAbrirMenus", ".btn-asignar-menu", function() {
                const planId = $(this).data("plan-id");
                const planNombre = $(this).data("plan-nombre");

                $("#plan_id_menus").val(planId);
                $("#modalAsignarMenus .modal-title").text("Asignar Menús Principales al Plan: " + planNombre);

                listar_menus_asignacion(planId, planNombre);

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

                listar_submenus_asignacion(planId, planNombre);

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

                listar_submenus2_asignacion(planId, planNombre);

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
            inicializarSelect2Planes();
            inicializarUIPlanes();
            registrarEventosPlanes();
            recargarPlanesPrincipal(false);

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