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
    let dataTableMenus = null; // Se conserva solo como referencia histórica; el listado principal ya no usa DataTable.

    const MENUS_STORAGE_FILTROS = "izzy.menus.filtros.visible";
    const MENUS_STORAGE_KPIS = "izzy.menus.kpis.visible";
    const MENUS_STORAGE_VISTA = "izzy.menus.tipo_vista";
    const MENUS_MOBILE_QUERY = "(max-width: 767.98px)";

    const menusState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        view: "detalle",
        preferredView: "detalle",
        search: "",
        tipo: "",
        visible: "",
        loading: false
    };
    let intentosCargaMenus = 0;
    const MAX_INTENTOS_CARGA_MENUS = 40;

    /* =========================================================
        VALIDACIÓN DE DEPENDENCIAS JS
    ========================================================= */
    function dependenciasMenusDisponibles() {
        return typeof window.jQuery !== "undefined" && typeof $.fn !== "undefined";
    }

    function esperarDependenciasMenus() {
        if (dependenciasMenusDisponibles()) {
            inicializarModuloMenus();
            return;
        }

        intentosCargaMenus++;

        if (intentosCargaMenus >= MAX_INTENTOS_CARGA_MENUS) {
            console.error("jQuery no está disponible para Administrar Menús.");

            if (typeof showNotify === "function") {
                showNotify(
                    "error",
                    "Error",
                    "No se pudieron cargar las dependencias del módulo Administrar Menús."
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
        LISTADO PRINCIPAL - DIV / GRID / FLEX
    ========================================================= */
    function menusEscape(value) {
        return String(value === null || typeof value === "undefined" ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function menusEsPantallaPequena() {
        return window.matchMedia
            ? window.matchMedia(MENUS_MOBILE_QUERY).matches
            : $(window).width() <= 767;
    }

    function menusDebounce(fn, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                fn.apply(context, args);
            }, wait);
        };
    }

    function menusTipoInterno(row) {
        return row && row.type ? obtenerTipoInternoDesdeTexto(String(row.type)) : "menu";
    }

    function menusTipoTexto(row) {
        return row && row.type ? String(row.type) : "Menú Principal";
    }

    function menusVisible(row) {
        return parseInt(row && row.visible, 10) === 1;
    }

    function menusConfigurarPanel(buttonSelector, bodySelector, storageKey, defaultVisible) {
        const $button = $(buttonSelector);
        const $body = $(bodySelector);
        let visible = defaultVisible;

        try {
            const stored = localStorage.getItem(storageKey);
            if (stored !== null) {
                visible = stored === "1";
            }
        } catch (e) {}

        function paint() {
            $body.toggle(visible);
            $button.attr("aria-expanded", visible ? "true" : "false");
            $button.html(
                visible
                    ? '<i class="fas fa-chevron-up mr-1"></i> Ocultar'
                    : '<i class="fas fa-chevron-down mr-1"></i> Mostrar'
            );
        }

        paint();

        $button.off("click.menusPanel").on("click.menusPanel", function() {
            visible = !visible;
            $body.stop(true, true)[visible ? "slideDown" : "slideUp"](180);
            paint();

            try {
                localStorage.setItem(storageKey, visible ? "1" : "0");
            } catch (e) {}
        });
    }

    function inicializarVistaMenus() {
        let saved = "detalle";

        try {
            saved = localStorage.getItem(MENUS_STORAGE_VISTA) || "detalle";
        } catch (e) {}

        if (saved !== "miniatura") {
            saved = "detalle";
        }

        menusState.preferredView = saved;
        menusState.view = menusEsPantallaPequena() ? "miniatura" : saved;

        actualizarDisponibilidadVistaMenus();
        actualizarBotonesVistaMenus();
        sincronizarPageSizeMenus();
    }

    function actualizarDisponibilidadVistaMenus() {
        const small = menusEsPantallaPequena();

        $('.menus-view-btn[data-view="detalle"]')
            .prop("disabled", small)
            .toggleClass("d-none", small)
            .attr("aria-hidden", small ? "true" : "false");
    }

    function actualizarBotonesVistaMenus() {
        $(".menus-view-btn")
            .removeClass("active")
            .attr("aria-pressed", "false");

        $('.menus-view-btn[data-view="' + menusState.view + '"]')
            .addClass("active")
            .attr("aria-pressed", "true");
    }

    function sincronizarPageSizeMenus() {
        const mini = menusState.view === "miniatura";
        const options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
        let selected = mini ? menusState.pageSizeMiniatura : menusState.pageSizeDetalle;

        if (options.indexOf(selected) === -1) {
            selected = options[0];
        }

        menusState.pageSize = selected;

        const $select = $("#menus_page_size");
        $select.empty();

        options.forEach(function(value) {
            $select.append('<option value="' + value + '">' + value + '</option>');
        });

        $select.val(String(selected));
    }

    function cambiarVistaMenus(view) {
        let next = view === "miniatura" ? "miniatura" : "detalle";

        if (menusEsPantallaPequena()) {
            next = "miniatura";
        } else {
            menusState.preferredView = next;
            try {
                localStorage.setItem(MENUS_STORAGE_VISTA, next);
            } catch (e) {}
        }

        menusState.view = next;
        menusState.page = 1;
        actualizarDisponibilidadVistaMenus();
        actualizarBotonesVistaMenus();
        sincronizarPageSizeMenus();
        renderMenus();
    }

    function aplicarVistaResponsiveMenus() {
        const target = menusEsPantallaPequena() ? "miniatura" : menusState.preferredView;
        actualizarDisponibilidadVistaMenus();

        if (menusState.view !== target) {
            menusState.view = target;
            menusState.page = 1;
            actualizarBotonesVistaMenus();
            sincronizarPageSizeMenus();
            renderMenus();
        }
    }

    function cargarListadoMenus(mantenerPagina) {
        if (menusState.loading) {
            return;
        }

        const previousPage = menusState.page;
        menusState.loading = true;

        $.ajax({
            url: MENU_URLS.llenarDataTable,
            type: "POST",
            dataType: "json"
        }).done(function(response) {
            menusState.rows = response && Array.isArray(response.data) ? response.data : [];
            menusState.page = mantenerPagina ? previousPage : 1;
            aplicarFiltrosMenus();
        }).fail(function(xhr) {
            console.error("Error cargando listado de menús:", xhr.responseText);
            menusState.rows = [];
            menusState.filtered = [];
            actualizarKpisMenus();
            renderMenus();
            notificarMenu("error", "Error", "No se pudo cargar el listado de menús.");
        }).always(function() {
            menusState.loading = false;
        });
    }

    function aplicarFiltrosMenus() {
        const search = String(menusState.search || "").trim().toLowerCase();
        const tipo = String(menusState.tipo || "");
        const visible = String(menusState.visible || "");

        menusState.filtered = menusState.rows.filter(function(row) {
            const interno = menusTipoInterno(row);
            const rowVisible = menusVisible(row) ? "1" : "0";

            if (tipo && interno !== tipo) {
                return false;
            }

            if (visible !== "" && rowVisible !== visible) {
                return false;
            }

            if (!search) {
                return true;
            }

            const base = [
                row.id,
                row.type,
                row.name,
                row.descripcion,
                row.icon,
                row.orden,
                row.dependency,
                menusVisible(row) ? "visible" : "oculto"
            ].join(" ").toLowerCase();

            return base.indexOf(search) !== -1;
        });

        actualizarKpisMenus();
        renderMenus();
    }

    function actualizarKpisMenus() {
        let visibles = 0;
        let ocultos = 0;
        let dependientes = 0;

        menusState.filtered.forEach(function(row) {
            if (menusVisible(row)) {
                visibles++;
            } else {
                ocultos++;
            }

            if (menusTipoInterno(row) !== "menu") {
                dependientes++;
            }
        });

        $("#menus_kpi_total").text(menusState.filtered.length);
        $("#menus_kpi_visibles").text(visibles);
        $("#menus_kpi_ocultos").text(ocultos);
        $("#menus_kpi_dependientes").text(dependientes);
    }

    function menuStatusBadge(row) {
        return menusVisible(row)
            ? '<span class="menus-status-badge is-visible"><i class="fas fa-check-circle"></i> Visible</span>'
            : '<span class="menus-status-badge is-hidden"><i class="fas fa-times-circle"></i> Oculto</span>';
    }

    function menuIconHtml(row) {
        const icon = String(row && row.icon || "").trim();
        return icon
            ? '<span class="menus-icon-box"><i class="' + menusEscape(icon) + '"></i></span>'
            : '<span class="menus-icon-box"><i class="fas fa-question"></i></span>';
    }

    function construirAccionesMenu(row) {
        return '' +
            '<div class="dropdown menus-actions-dropdown">' +
                '<button type="button" class="btn btn-sm btn-acciones menus-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                    '<i class="fas fa-cog"></i><span>Acciones</span>' +
                '</button>' +
                '<div class="dropdown-menu acciones-menu">' +
                    '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar menus-edit-action" ' +
                        'data-id="' + menusEscape(row.id) + '" data-tipo="' + menusEscape(menusTipoInterno(row)) + '">' +
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                        '<span class="accion-label">Editar</span>' +
                    '</button>' +
                    '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar menus-delete-action" ' +
                        'data-id="' + menusEscape(row.id) + '" data-tipo="' + menusEscape(menusTipoInterno(row)) + '" ' +
                        'data-nombre="' + menusEscape(row.descripcion || row.name || "") + '">' +
                        '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                        '<span class="accion-label">Eliminar</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
    }

    function renderMenuDetalle(row) {
        return '' +
            '<article class="menus-detail-row">' +
                '<div class="menus-detail-cell menus-actions-cell">' + construirAccionesMenu(row) + '</div>' +
                '<div class="menus-detail-cell"><span class="menus-type-badge">' + menusEscape(menusTipoTexto(row)) + '</span></div>' +
                '<div class="menus-detail-cell menus-name-cell">' + menuIconHtml(row) +
                    '<div><strong>' + menusEscape(row.descripcion || row.name || "Sin nombre") + '</strong>' +
                    '<small>' + menusEscape(row.name || "") + '</small></div></div>' +
                '<div class="menus-detail-cell">' + menusEscape(row.dependency || "Sin dependencia") + '</div>' +
                '<div class="menus-detail-cell menus-center-cell"><strong>' + menusEscape(row.orden || 0) + '</strong></div>' +
                '<div class="menus-detail-cell menus-center-cell">' + menuStatusBadge(row) + '</div>' +
            '</article>';
    }

    function renderMenuMiniatura(row) {
        return '' +
            '<article class="menus-mini-card">' +
                '<div class="menus-mini-topline"></div>' +
                '<div class="menus-mini-header">' +
                    '<div class="menus-mini-title-wrap">' + menuIconHtml(row) +
                        '<div><h4>' + menusEscape(row.descripcion || row.name || "Sin nombre") + '</h4>' +
                        '<span>' + menusEscape(row.name || "") + '</span></div>' +
                    '</div>' +
                    menuStatusBadge(row) +
                '</div>' +
                '<div class="menus-mini-body">' +
                    '<div class="menus-mini-field"><span>Tipo</span><strong>' + menusEscape(menusTipoTexto(row)) + '</strong></div>' +
                    '<div class="menus-mini-field"><span>Dependencia</span><strong>' + menusEscape(row.dependency || "Sin dependencia") + '</strong></div>' +
                    '<div class="menus-mini-field"><span>Orden</span><strong>' + menusEscape(row.orden || 0) + '</strong></div>' +
                    '<div class="menus-mini-field"><span>Ícono</span><strong>' + menusEscape(row.icon || "Sin ícono") + '</strong></div>' +
                '</div>' +
                '<div class="menus-mini-footer">' + construirAccionesMenu(row) + '</div>' +
            '</article>';
    }

    function renderMenus() {
        if (menusEsPantallaPequena() && menusState.view !== "miniatura") {
            menusState.view = "miniatura";
            actualizarDisponibilidadVistaMenus();
            actualizarBotonesVistaMenus();
            sincronizarPageSizeMenus();
        }

        const total = menusState.filtered.length;
        const pages = Math.max(1, Math.ceil(total / menusState.pageSize));

        if (menusState.page > pages) {
            menusState.page = pages;
        }

        const start = (menusState.page - 1) * menusState.pageSize;
        const end = Math.min(start + menusState.pageSize, total);
        const rows = menusState.filtered.slice(start, end);
        const $list = $("#menus_listado");

        let html = "";

        $list
            .toggleClass("vista-detalle", menusState.view === "detalle")
            .toggleClass("vista-miniatura", menusState.view === "miniatura");

        if (menusState.view === "detalle" && total) {
            html += '' +
                '<div class="menus-detail-header">' +
                    '<div>Acciones</div>' +
                    '<div>Tipo</div>' +
                    '<div>Elemento</div>' +
                    '<div>Dependencia</div>' +
                    '<div>Orden</div>' +
                    '<div>Estado</div>' +
                '</div>';
        }

        rows.forEach(function(row) {
            html += menusState.view === "miniatura"
                ? renderMenuMiniatura(row)
                : renderMenuDetalle(row);
        });

        $list.html(html);
        $("#menus_empty").toggleClass("d-none", total !== 0);

        $("#menus_resultado_info").text(
            total
                ? "Mostrando " + (start + 1) + " a " + end + " de " + total + " registros"
                : "Mostrando 0 registros"
        );

        renderPaginacionMenus(pages);

        if (
            typeof getPermisosTipoUsuarioAccesosTable === "function" &&
            typeof getPrivilegioTipoUsuario === "function"
        ) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    }

    function renderPaginacionMenus(totalPages) {
        const current = menusState.page;
        let html = "";

        function button(label, icon, page, disabled) {
            return '<button type="button" class="menus-page-btn" data-page="' + page + '" ' +
                (disabled ? "disabled" : "") + '>' +
                '<i class="fas ' + icon + '"></i><span>' + label + '</span></button>';
        }

        html += button("Inicio", "fa-angle-double-left", 1, current <= 1);
        html += button("Anterior", "fa-angle-left", Math.max(1, current - 1), current <= 1);

        let from = Math.max(1, current - 2);
        let to = Math.min(totalPages, from + 4);

        if (to - from < 4) {
            from = Math.max(1, to - 4);
        }

        for (let i = from; i <= to; i++) {
            html += '<button type="button" class="menus-page-btn menus-page-number ' +
                (i === current ? "active" : "") + '" data-page="' + i + '">' + i + '</button>';
        }

        html += button("Siguiente", "fa-angle-right", Math.min(totalPages, current + 1), current >= totalPages);
        html += button("Final", "fa-angle-double-right", totalPages, current >= totalPages);

        $("#menus_paginacion").html(html);
    }

    function inicializarEventosListadoMenus() {
        menusConfigurarPanel("#btn_toggle_menus_filtros", "#menus_filtros_body", MENUS_STORAGE_FILTROS, true);
        menusConfigurarPanel("#btn_toggle_menus_kpis", "#menus_kpis_body", MENUS_STORAGE_KPIS, true);

        $("#form_filtros_menus")
            .off("submit.menusFiltros")
            .on("submit.menusFiltros", function(e) {
                e.preventDefault();
                menusState.tipo = $("#menus_filtro_tipo").val() || "";
                menusState.visible = $("#menus_filtro_visible").val() || "";
                menusState.page = 1;
                aplicarFiltrosMenus();
            })
            .off("reset.menusFiltros")
            .on("reset.menusFiltros", function() {
                setTimeout(function() {
                    $("#menus_filtro_tipo").val("");
                    $("#menus_filtro_visible").val("");
                    menusState.tipo = "";
                    menusState.visible = "";
                    menusState.page = 1;
                    aplicarFiltrosMenus();
                }, 30);
            });

        $("#menus_filtro_tipo, #menus_filtro_visible")
            .off("change.menusFiltros")
            .on("change.menusFiltros", function() {
                menusState.tipo = $("#menus_filtro_tipo").val() || "";
                menusState.visible = $("#menus_filtro_visible").val() || "";
                menusState.page = 1;
                aplicarFiltrosMenus();
            });

        $("#menus_buscar")
            .off("input.menusSearch")
            .on("input.menusSearch", menusDebounce(function() {
                menusState.search = $(this).val() || "";
                menusState.page = 1;
                aplicarFiltrosMenus();
            }, 160));

        $("#menus_page_size")
            .off("change.menusPageSize")
            .on("change.menusPageSize", function() {
                const value = parseInt($(this).val(), 10);
                menusState.pageSize = isNaN(value) ? (menusState.view === "miniatura" ? 6 : 10) : value;

                if (menusState.view === "miniatura") {
                    menusState.pageSizeMiniatura = menusState.pageSize;
                } else {
                    menusState.pageSizeDetalle = menusState.pageSize;
                }

                menusState.page = 1;
                renderMenus();
            });

        $(".menus-view-btn")
            .off("click.menusView")
            .on("click.menusView", function() {
                cambiarVistaMenus($(this).data("view"));
            });

        $("#menus_paginacion")
            .off("click.menusPage", "button[data-page]")
            .on("click.menusPage", "button[data-page]", function() {
                if ($(this).prop("disabled")) {
                    return;
                }

                menusState.page = parseInt($(this).data("page"), 10) || 1;
                renderMenus();
            });

        $("#btn_actualizar_menus")
            .off("click.menusRefresh")
            .on("click.menusRefresh", function() {
                cargarListadoMenus(true);
            });

        $("#btn_nuevo_menu")
            .off("click.menusNuevo")
            .on("click.menusNuevo", function() {
                resetForm();
                $("html, body").animate({scrollTop: $("#div_top").offset().top - 20}, 250);
                setTimeout(function() {
                    $("#tipo_menu").focus();
                }, 260);
            });

        $(window)
            .off("resize.menusResponsive")
            .on("resize.menusResponsive", menusDebounce(aplicarVistaResponsiveMenus, 120));

        /* Dropdown normal + inteligente. */
        $(document)
            .off("click.menusDropdown", "#menus_listado .menus-acciones-toggle")
            .on("click.menusDropdown", "#menus_listado .menus-acciones-toggle", function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $button = $(this);

                if (typeof $.fn.dropdown !== "function") {
                    notificarMenu("error", "Acciones no disponibles", "No se encontró el componente Dropdown de Bootstrap.");
                    return;
                }

                $("#menus_listado .menus-acciones-toggle").not($button).each(function() {
                    try {
                        $(this).dropdown("hide");
                    } catch (error) {}
                });

                try {
                    $button.dropdown({
                        boundary: "viewport",
                        flip: true,
                        offset: "0,6"
                    });
                    $button.dropdown("toggle");
                } catch (error) {
                    console.error("Error abriendo dropdown de menús:", error);
                    notificarMenu("error", "Acciones no disponibles", "No se pudo abrir el menú de acciones.");
                }
            });

        $("#btn_exportar_menus_excel")
            .off("click.menusExcel")
            .on("click.menusExcel", exportarMenusExcel);

        $("#btn_exportar_menus_pdf")
            .off("click.menusPdf")
            .on("click.menusPdf", exportarMenusPDF);
    }


    function menusObtenerLogoPdf(callback) {
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

    function menusPdfLogoPlate(logoDataUrl) {
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
    function menusExcelXmlEscape(value) {
        return String(value === null || typeof value === "undefined" ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;");
    }

    function menusExcelColName(index) {
        let name = "";
        while (index >= 0) {
            name = String.fromCharCode((index % 26) + 65) + name;
            index = Math.floor(index / 26) - 1;
        }
        return name;
    }

    function menusExcelCell(ref, value, styleId, numeric) {
        if (numeric) {
            let numberValue = Number(value);
            if (!isFinite(numberValue)) numberValue = 0;
            return '<c r="' + ref + '" s="' + styleId + '" t="n"><v>' + numberValue + '</v></c>';
        }

        const raw = String(value === null || typeof value === "undefined" ? "" : value);
        const preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';
        return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' +
            menusExcelXmlEscape(raw) + '</t></is></c>';
    }

    function menusGenerarXlsx(rows) {
        if (typeof JSZip === "undefined") return null;

        const headers = ["ID", "Tipo", "Nombre", "Descripción", "Ícono", "Orden", "Dependencia", "Estado"];
        const visibles = rows.filter(menusVisible).length;
        const ocultos = rows.length - visibles;
        const dependientes = rows.filter(function(row) { return menusTipoInterno(row) !== "menu"; }).length;
        const headerRow = 7;
        const firstDataRow = 8;
        const lastCol = "H";
        const lastRow = Math.max(headerRow, headerRow + rows.length);
        const sheetRows = [];

        sheetRows.push('<row r="1" ht="30" customHeight="1">' + menusExcelCell("A1", "IZZY • REPORTE DE MENÚS", 1, false) + '</row>');
        sheetRows.push('<row r="2" ht="20" customHeight="1">' + menusExcelCell("A2", "Administración de menús, submenús y visibilidad • Generado: " + new Date().toLocaleDateString("es-HN"), 2, false) + '</row>');
        sheetRows.push('<row r="3" ht="18" customHeight="1">' +
            menusExcelCell("A3", "REGISTROS", 6, false) +
            menusExcelCell("C3", "VISIBLES", 6, false) +
            menusExcelCell("E3", "OCULTOS", 6, false) +
            menusExcelCell("G3", "DEPENDIENTES", 6, false) + '</row>');
        sheetRows.push('<row r="4" ht="26" customHeight="1">' +
            menusExcelCell("A4", rows.length, 7, true) +
            menusExcelCell("C4", visibles, 7, true) +
            menusExcelCell("E4", ocultos, 7, true) +
            menusExcelCell("G4", dependientes, 7, true) + '</row>');
        sheetRows.push('<row r="5" ht="18" customHeight="1">' + menusExcelCell(
            "A5",
            "Filtros: Tipo " + ($("#menus_filtro_tipo option:selected").text() || "Todos") +
            " | Estado " + ($("#menus_filtro_visible option:selected").text() || "Todos") +
            " | Búsqueda: " + ($.trim($("#menus_buscar").val()) || "Sin búsqueda"),
            8,
            false
        ) + '</row>');
        sheetRows.push('<row r="6" ht="18" customHeight="1">' + menusExcelCell("A6", "Detalle de menús filtrados", 8, false) + '</row>');

        const headerCells = headers.map(function(header, index) {
            return menusExcelCell(menusExcelColName(index) + headerRow, header, 3, false);
        }).join("");
        sheetRows.push('<row r="' + headerRow + '" ht="26" customHeight="1">' + headerCells + '</row>');

        rows.forEach(function(row, rowIndex) {
            const excelRow = firstDataRow + rowIndex;
            const values = [
                row.id || "",
                menusTipoTexto(row),
                row.name || "",
                row.descripcion || "",
                row.icon || "",
                row.orden || 0,
                row.dependency || "Sin dependencia",
                menusVisible(row) ? "Visible" : "Oculto"
            ];

            const cells = values.map(function(value, colIndex) {
                let style = 4;
                if (colIndex === 7) style = menusVisible(row) ? 9 : 10;
                return menusExcelCell(menusExcelColName(colIndex) + excelRow, value, style, false);
            }).join("");

            sheetRows.push('<row r="' + excelRow + '" ht="22" customHeight="1">' + cells + '</row>');
        });

        const sheetXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
            '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
            '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
            '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' +
            '<col min="1" max="1" width="10" customWidth="1"/><col min="2" max="2" width="20" customWidth="1"/>' +
            '<col min="3" max="3" width="24" customWidth="1"/><col min="4" max="4" width="34" customWidth="1"/>' +
            '<col min="5" max="5" width="24" customWidth="1"/><col min="6" max="6" width="10" customWidth="1"/>' +
            '<col min="7" max="7" width="30" customWidth="1"/><col min="8" max="8" width="14" customWidth="1"/>' +
            '</cols><sheetData>' + sheetRows.join("") + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
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

        const stylesXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="7">' +
            '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
            '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
            '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
            '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
            '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
            '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="7"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/><bgColor indexed="64"/></patternFill></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/><bgColor indexed="64"/></patternFill></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/><bgColor indexed="64"/></patternFill></fill></fills>' +
            '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border>' +
            '<left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right>' +
            '<top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
            '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
            '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        const workbookXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews><sheets><sheet name="Menus" sheetId="1" r:id="rId1"/></sheets></workbook>';
        const workbookRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        const rootRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        const contentTypes = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';

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

        if (typeof zip.generateAsync === "function") return zip.generateAsync(opciones);
        if (typeof zip.generate === "function") {
            try { return Promise.resolve(zip.generate(opciones)); }
            catch (errorGenerate) { return Promise.reject(errorGenerate); }
        }
        return Promise.reject(new Error("La versión de JSZip no soporta generateAsync() ni generate()."));
    }

    function exportarMenusExcel() {
        const rows = menusState.filtered;

        if (!rows.length) {
            notificarMenu("warning", "Sin información", "No hay registros para exportar.");
            return;
        }

        const promesa = menusGenerarXlsx(rows);
        if (!promesa) {
            notificarMenu("error", "Excel no disponible", "No se encontró JSZip para generar el XLSX.");
            return;
        }

        promesa.then(function(blob) {
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement("a");
            anchor.href = url;
            anchor.download = "Reporte_Menus.xlsx";
            document.body.appendChild(anchor);
            anchor.click();
            document.body.removeChild(anchor);
            setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
        }).catch(function(error) {
            console.error("Error generando XLSX de Menús:", error);
            notificarMenu("error", "Error", "No se pudo generar el archivo Excel.");
        });
    }


    function exportarMenusPDF() {
        if (!(typeof imagen !== "undefined" && typeof imagen === "string" && imagen.indexOf("data:image/") === 0)) {
            menusObtenerLogoPdf(function(logoDataUrl) {
                try { imagen = logoDataUrl; } catch (e) {}
                exportarMenusPDF();
            });
            return;
        }

        if (!menusState.filtered.length) {
            notificarMenu("warning", "Sin información", "No hay registros para exportar.");
            return;
        }

        if (typeof pdfMake === "undefined") {
            notificarMenu("error", "PDF no disponible", "No se encontró pdfMake.");
            return;
        }

        if (typeof abrirModalPdfPublico !== "function") {
            notificarMenu("error", "Visor PDF no disponible", "No se encontró el modal público de PDF.");
            return;
        }

        const rows = menusState.filtered;
        const visibleCount = rows.filter(menusVisible).length;
        const hiddenCount = rows.length - visibleCount;
        const dependentCount = rows.filter(function(row) {
            return menusTipoInterno(row) !== "menu";
        }).length;

        const content = [
            {
                table: {
                    widths: [100, "*", 160],
                    body: [[
                        {stack: [menusPdfLogoPlate(imagen)], fillColor: "#17324D", margin: [8, 7, 8, 7]},
                        {
                            stack: [
                                {text: "REPORTE DE MENÚS", bold: true, fontSize: 16, color: "#FFFFFF"},
                                {text: "Administración de menús, submenús y visibilidad", fontSize: 7.5, color: "#D8E5F0"}
                            ],
                            fillColor: "#17324D",
                            margin: [0, 10, 0, 10]
                        },
                        {
                            stack: [
                                {text: "REPORTE EJECUTIVO", bold: true, fontSize: 6.5, color: "#72E2E5", alignment: "right"},
                                {text: new Date().toLocaleDateString("es-HN"), bold: true, fontSize: 9, color: "#FFFFFF", alignment: "right"},
                                {text: rows.length + " registro(s) filtrado(s)", fontSize: 6.5, color: "#D8E5F0", alignment: "right"}
                            ],
                            fillColor: "#17324D",
                            margin: [0, 10, 12, 10]
                        }
                    ]]
                },
                layout: "noBorders",
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*"],
                    body: [[{
                        text: "Filtros aplicados: Tipo " + ($("#menus_filtro_tipo option:selected").text() || "Todos") +
                              " | Estado " + ($("#menus_filtro_visible option:selected").text() || "Todos") +
                              " | Búsqueda " + ($.trim($("#menus_buscar").val()) || "Sin búsqueda"),
                        fillColor: "#F7F9FC",
                        color: "#52627A",
                        fontSize: 6.8,
                        margin: [10, 7, 10, 7]
                    }]]
                },
                layout: {
                    hLineColor: function() { return "#DDE3EA"; },
                    vLineColor: function() { return "#DDE3EA"; },
                    hLineWidth: function() { return .6; },
                    vLineWidth: function() { return .6; }
                },
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*", "*", "*", "*"],
                    body: [[
                        {stack: [{text: "REGISTROS", fontSize: 6.3, bold: true, color: "#6B778C"}, {text: String(rows.length), fontSize: 13, bold: true, color: "#172B4D"}], fillColor: "#F7F9FC", margin: [8,7,8,7]},
                        {stack: [{text: "VISIBLES", fontSize: 6.3, bold: true, color: "#6B778C"}, {text: String(visibleCount), fontSize: 13, bold: true, color: "#14804A"}], fillColor: "#F7F9FC", margin: [8,7,8,7]},
                        {stack: [{text: "OCULTOS", fontSize: 6.3, bold: true, color: "#6B778C"}, {text: String(hiddenCount), fontSize: 13, bold: true, color: "#C9372C"}], fillColor: "#F7F9FC", margin: [8,7,8,7]},
                        {stack: [{text: "DEPENDIENTES", fontSize: 6.3, bold: true, color: "#6B778C"}, {text: String(dependentCount), fontSize: 13, bold: true, color: "#172B4D"}], fillColor: "#F7F9FC", margin: [8,7,8,7]}
                    ]]
                },
                layout: {
                    hLineColor: function() { return "#DDE3EA"; },
                    vLineColor: function() { return "#DDE3EA"; },
                    hLineWidth: function() { return .6; },
                    vLineWidth: function() { return .6; }
                },
                margin: [0, 0, 0, 12]
            }
        ];

        if (menusState.view === "miniatura") {
            content.push({text: "VISTA MINIATURA", bold: true, fontSize: 7, color: "#17324D", margin: [0,0,0,7]});

            for (let i = 0; i < rows.length; i += 2) {
                const card = function(row) {
                    return {
                        table: {
                            widths: ["*"],
                            body: [[{
                                stack: [
                                    {text: row.descripcion || row.name || "Sin nombre", bold: true, fontSize: 10, color: "#172B4D"},
                                    {text: row.name || "", fontSize: 7, color: "#6B778C", margin: [0,2,0,6]},
                                    {text: "Tipo: " + menusTipoTexto(row), fontSize: 7, color: "#253858"},
                                    {text: "Dependencia: " + (row.dependency || "Sin dependencia"), fontSize: 7, color: "#253858", margin: [0,3,0,0]},
                                    {text: "Orden: " + (row.orden || 0), fontSize: 7, color: "#253858", margin: [0,3,0,0]},
                                    {text: menusVisible(row) ? "Visible" : "Oculto", fontSize: 7, bold: true, color: menusVisible(row) ? "#14804A" : "#C9372C", margin: [0,5,0,0]}
                                ],
                                margin: [10,9,10,9]
                            }]]
                        },
                        layout: {
                            hLineColor: function() { return "#DDE3EA"; },
                            vLineColor: function() { return "#DDE3EA"; },
                            hLineWidth: function() { return .7; },
                            vLineWidth: function() { return .7; }
                        }
                    };
                };

                content.push({
                    columns: [
                        {width: "*", stack: [card(rows[i])]},
                        {width: 10, text: ""},
                        rows[i + 1] ? {width: "*", stack: [card(rows[i + 1])]} : {width: "*", text: ""}
                    ],
                    margin: [0,0,0,9]
                });
            }
        } else {
            content.push({text: "VISTA DETALLE", bold: true, fontSize: 7, color: "#17324D", margin: [0,0,0,7]});

            const body = [[
                {text: "TIPO", style: "th", fillColor: "#17324D"},
                {text: "NOMBRE", style: "th", fillColor: "#17324D"},
                {text: "DESCRIPCIÓN", style: "th", fillColor: "#17324D"},
                {text: "DEPENDENCIA", style: "th", fillColor: "#17324D"},
                {text: "ORDEN", style: "th", fillColor: "#17324D"},
                {text: "ESTADO", style: "th", fillColor: "#17324D"}
            ]];

            rows.forEach(function(row, index) {
                const fill = index % 2 === 0 ? "#FFFFFF" : "#F7F9FC";
                body.push([
                    {text: menusTipoTexto(row), style: "td", fillColor: fill},
                    {text: row.name || "", style: "td", fillColor: fill},
                    {text: row.descripcion || "", style: "td", fillColor: fill},
                    {text: row.dependency || "Sin dependencia", style: "td", fillColor: fill},
                    {text: String(row.orden || 0), style: "tdCenter", fillColor: fill},
                    {text: menusVisible(row) ? "Visible" : "Oculto", style: "tdCenter", bold: true, color: menusVisible(row) ? "#14804A" : "#C9372C", fillColor: fill}
                ]);
            });

            content.push({
                table: {
                    headerRows: 1,
                    widths: [85, 100, "*", 130, 50, 60],
                    body: body
                },
                layout: {
                    hLineColor: function() { return "#DDE3EA"; },
                    vLineColor: function() { return "#DDE3EA"; },
                    hLineWidth: function() { return .55; },
                    vLineWidth: function() { return .55; },
                    paddingLeft: function() { return 5; },
                    paddingRight: function() { return 5; },
                    paddingTop: function() { return 6; },
                    paddingBottom: function() { return 6; }
                }
            });
        }

        const doc = {
            pageSize: "LETTER",
            pageOrientation: "landscape",
            pageMargins: [28,28,28,34],
            header: function() {
                return {
                    margin: [28,12,28,0],
                    canvas: [{type: "line", x1: 0, y1: 0, x2: 736, y2: 0, lineWidth: 2, lineColor: "#0EA5A8"}]
                };
            },
            footer: function(currentPage, pageCount) {
                return {
                    margin: [28,8,28,0],
                    columns: [
                        {text: "IZZY • Administrar Menús", fontSize: 7, color: "#7A869A"},
                        {text: "Página " + currentPage + " de " + pageCount, fontSize: 7, color: "#7A869A", alignment: "right"}
                    ]
                };
            },
            content: content,
            styles: {
                th: {fontSize: 6.2, bold: true, color: "#FFFFFF", alignment: "center"},
                td: {fontSize: 6.3, color: "#253858"},
                tdCenter: {fontSize: 6.3, color: "#253858", alignment: "center"}
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url) {
            abrirModalPdfPublico(url, "Reporte de Menús", "Reporte_Menus.pdf");
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
                            cargarListadoMenus(true);
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
        $(document)
            .off("click.menusAdminEditar", "#menus_listado .menus-edit-action")
            .on("click.menusAdminEditar", "#menus_listado .menus-edit-action", function() {
                const id = $(this).data("id");
                const type = $(this).data("tipo");

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
                        }, 300, function() {
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

    function eventoEliminarMenu() {
        $(document)
            .off("click.menusAdminEliminar", "#menus_listado .menus-delete-action")
            .on("click.menusAdminEliminar", "#menus_listado .menus-delete-action", function() {
                const id = $(this).data("id");
                const type = $(this).data("tipo");
                const nombre = $(this).data("nombre") || "Elemento";

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

                                    cargarListadoMenus(true);
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

    function inicializarModuloMenus() {
        eventoVistaPreviaIcono();
        eventoTipoMenu();
        eventoSubmitFormularioMenu();
        eventoCancelarEdicion();
        eventoEditarMenu();
        eventoEliminarMenu();

        inicializarVistaMenus();
        inicializarEventosListadoMenus();
        cargarListadoMenus(false);
        resetForm();
    }

    esperarDependenciasMenus();

})();
</script>