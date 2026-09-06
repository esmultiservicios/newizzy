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
        let dataTablePlanes = null; // Compatibilidad con modales auxiliares.

        const PLANES_STORAGE_VISTA = "izzy.registrarPlanes.tipo_vista";
        const PLANES_STORAGE_FILTROS = "izzy.registrarPlanes.filtros.visible";
        const PLANES_STORAGE_KPIS = "izzy.registrarPlanes.kpis.visible";

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
                planesState.vista =
                    localStorage.getItem(PLANES_STORAGE_VISTA) === "miniatura"
                        ? "miniatura"
                        : "detalle";
            } catch (e) {
                planesState.vista = "detalle";
            }

            actualizarBotonesVistaPlanes();
            sincronizarPageSizePlanes();
        }

        function actualizarBotonesVistaPlanes() {
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
            planesState.vista = vista === "miniatura" ? "miniatura" : "detalle";

            try {
                localStorage.setItem(PLANES_STORAGE_VISTA, planesState.vista);
            } catch (e) {
                console.warn("No se pudo guardar la vista.", e);
            }

            actualizarBotonesVistaPlanes();
            sincronizarPageSizePlanes();
            planesState.pagina = 1;
            renderPlanesPrincipal();
        }

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
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<sheets><sheet name="Planes" sheetId="1" r:id="rId1"/></sheets></workbook>';

            const workbookRels =
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
                '</Relationships>';

            const rootRels =
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
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

            const logoCell =
                typeof imagen !== "undefined" && imagen
                    ? {
                        image: imagen,
                        width: 52,
                        height: 24,
                        alignment: "center",
                        margin: [0, 2, 0, 0]
                    }
                    : {
                        text: "IZZY",
                        fontSize: 16,
                        bold: true,
                        color: "#FFFFFF",
                        alignment: "center",
                        margin: [0, 5, 0, 0]
                    };

            return [
                {
                    table: {
                        widths: [72, "*", 155],
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
                            recargarPlanesPrincipal(true);

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
            DIRECCIÓN ADAPTATIVA DEL DROPDOWN
            Prioridad: abajo -> arriba -> derecha -> izquierda.
            Bootstrap/Popper conserva el ajuste final contra viewport.
        ========================================================= */
        function limpiarDireccionDropdownPlanes($dropdown) {
            if (!$dropdown || !$dropdown.length) {
                return;
            }

            $dropdown.removeClass("dropup dropright dropleft");
            $dropdown.children(".dropdown-menu")
                .removeClass("dropdown-menu-right")
                .removeAttr("x-placement data-popper-placement")
                .css({ top: "", left: "", right: "", bottom: "", transform: "" });
        }

        function medirMenuDropdownPlanes($menu) {
            const menu = $menu && $menu.length ? $menu[0] : null;
            if (!menu) {
                return { width: 220, height: 220 };
            }

            const estilos = {
                display: menu.style.display,
                visibility: menu.style.visibility,
                position: menu.style.position,
                top: menu.style.top,
                left: menu.style.left,
                right: menu.style.right,
                bottom: menu.style.bottom,
                transform: menu.style.transform
            };
            const teniaShow = $menu.hasClass("show");

            $menu.addClass("show").css({
                display: "block",
                visibility: "hidden",
                position: "fixed",
                top: "0",
                left: "0",
                right: "auto",
                bottom: "auto",
                transform: "none"
            });

            const rect = menu.getBoundingClientRect();

            if (!teniaShow) {
                $menu.removeClass("show");
            }
            menu.style.display = estilos.display;
            menu.style.visibility = estilos.visibility;
            menu.style.position = estilos.position;
            menu.style.top = estilos.top;
            menu.style.left = estilos.left;
            menu.style.right = estilos.right;
            menu.style.bottom = estilos.bottom;
            menu.style.transform = estilos.transform;

            return {
                width: Math.max(rect.width || 0, 220),
                height: Math.max(rect.height || 0, 1)
            };
        }

        function prepararDireccionDropdownPlanes($dropdown) {
            if (!$dropdown || !$dropdown.length) {
                return;
            }

            const $button = $dropdown.children(".planes-acciones-toggle");
            const $menu = $dropdown.children(".dropdown-menu");
            const button = $button.length ? $button[0] : null;

            if (!button || !$menu.length) {
                return;
            }

            limpiarDireccionDropdownPlanes($dropdown);

            const rect = button.getBoundingClientRect();
            const menuSize = medirMenuDropdownPlanes($menu);
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const margin = 12;
            const gap = 8;

            const espacioAbajo = viewportHeight - rect.bottom - margin;
            const espacioArriba = rect.top - margin;
            const espacioDerecha = viewportWidth - rect.right - margin;
            const espacioIzquierda = rect.left - margin;

            const cabeAbajo = espacioAbajo >= menuSize.height + gap;
            const cabeArriba = espacioArriba >= menuSize.height + gap;
            const cabeDerecha = espacioDerecha >= menuSize.width + gap;
            const cabeIzquierda = espacioIzquierda >= menuSize.width + gap;

            if (cabeAbajo) {
                /* Dropdown normal. */
            } else if (cabeArriba) {
                $dropdown.addClass("dropup");
            } else if (cabeDerecha) {
                $dropdown.addClass("dropright");
            } else if (cabeIzquierda) {
                $dropdown.addClass("dropleft");
            } else if (espacioArriba > espacioAbajo) {
                $dropdown.addClass("dropup");
            }

            /* Alineación horizontal preventiva para dropdown/dropup. */
            if (!$dropdown.hasClass("dropright") && !$dropdown.hasClass("dropleft")) {
                const desbordaDerecha = rect.left + menuSize.width > viewportWidth - margin;
                const puedeAlinearDerecha = rect.right - menuSize.width >= margin;

                if (desbordaDerecha && puedeAlinearDerecha) {
                    $menu.addClass("dropdown-menu-right");
                }
            }
        }

        /* =========================================================
            EVENTOS
        ========================================================= */
        function registrarEventosPlanes() {
            /* =====================================================
               DROPDOWN DE ACCIONES DEL LISTADO PRINCIPAL
               Comportamiento normal Bootstrap/Popper:
               - abre debajo del botón;
               - cambia de posición únicamente si no hay espacio;
               - usa el viewport como límite para no quedar oculto.
               ===================================================== */
            function cerrarDropdownsPlanesExcepto($actual) {
                $("#planesListado .planes-actions-dropdown").each(function() {
                    const $dropdown = $(this);
                    const $btn = $dropdown.children(".planes-acciones-toggle");
                    const $menu = $dropdown.children(".dropdown-menu");

                    if ($actual && $actual.length && $dropdown.is($actual)) {
                        return;
                    }

                    try {
                        if (typeof $.fn.dropdown === "function" && $menu.hasClass("show")) {
                            $btn.dropdown("hide");
                        }
                    } catch (error) {
                        /* Limpieza manual abajo como respaldo. */
                    }

                    $btn.attr("aria-expanded", "false");
                    $dropdown.removeClass("show");
                    $menu.removeClass("show");
                    limpiarDireccionDropdownPlanes($dropdown);

                    /* IMPORTANTE: solo baja la fila que se está cerrando. */
                    $dropdown
                        .closest(".planes-detail-row, .planes-mini-card")
                        .removeClass("planes-dropdown-open");
                });
            }

            $(document)
                .off("click.planesAcciones", "#planesListado .planes-acciones-toggle")
                .on("click.planesAcciones", "#planesListado .planes-acciones-toggle", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const $button = $(this);
                    const $dropdown = $button.closest(".planes-actions-dropdown");
                    const $menu = $dropdown.children(".dropdown-menu");
                    const estabaAbierto = $menu.hasClass("show");

                    if (typeof $.fn.dropdown !== "function") {
                        notificarPlan(
                            "error",
                            "Acciones no disponibles",
                            "No se encontró el componente Dropdown de Bootstrap."
                        );
                        return;
                    }

                    cerrarDropdownsPlanesExcepto($dropdown);

                    if (estabaAbierto) {
                        try { $button.dropdown("hide"); } catch (error) {}
                        $button.attr("aria-expanded", "false");
                        $dropdown.removeClass("show");
                        $menu.removeClass("show");
                        limpiarDireccionDropdownPlanes($dropdown);
                        limpiarStackDropdownPlanes();
                        return;
                    }

                    try {
                        prepararDireccionDropdownPlanes($dropdown);

                        $button.dropdown({
                            boundary: "viewport",
                            flip: true,
                            offset: "0,6"
                        });
                        $button.dropdown("show");
                        activarStackDropdownPlanes($dropdown);
                    } catch (error) {
                        console.error("No se pudo abrir el dropdown de acciones:", error);
                        notificarPlan(
                            "error",
                            "Acciones no disponibles",
                            "No se pudo abrir el menú de acciones."
                        );
                    }
                });

            $(document)
                .off("shown.bs.dropdown.planesStack", "#planesListado .planes-actions-dropdown")
                .on("shown.bs.dropdown.planesStack", "#planesListado .planes-actions-dropdown", function() {
                    activarStackDropdownPlanes($(this));
                })
                .off("hidden.bs.dropdown.planesStack", "#planesListado .planes-actions-dropdown")
                .on("hidden.bs.dropdown.planesStack", "#planesListado .planes-actions-dropdown", function() {
                    /* No limpiar todas las filas: un hidden anterior puede dispararse
                       después de abrir otro dropdown y quitarle su stacking. */
                    const $dropdown = $(this);
                    limpiarDireccionDropdownPlanes($dropdown);
                    $dropdown
                        .closest(".planes-detail-row, .planes-mini-card")
                        .removeClass("planes-dropdown-open");
                });

            $(document)
                .off("click.planesCerrarAlElegir", "#planesListado .planes-actions-dropdown .dropdown-item")
                .on("click.planesCerrarAlElegir", "#planesListado .planes-actions-dropdown .dropdown-item", function() {
                    const $button = $(this)
                        .closest(".planes-actions-dropdown")
                        .children(".planes-acciones-toggle");

                    if ($button.length && typeof $.fn.dropdown === "function") {
                        try {
                            $button.dropdown("hide");
                        } catch (error) {
                            /* Bootstrap puede no exponer hide en versiones antiguas. */
                        }
                    }
                });

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

                listar_configuraciones(planId, configs);
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