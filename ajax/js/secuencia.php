<script>
(function ($) {
    'use strict';

    var secuenciaState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 5,
        pageSizeDetalle: 5,
        pageSizeMiniatura: 6,
        loading: false,
        view: 'detalle'
    };

    var documentosState = {
        rows: [],
        loading: false
    };

    var reabrirSecuenciaTrasDocumentos = false;

    var SECUENCIA_STORAGE_FILTROS = 'izzy_secuencia_filtros_visible';
    var SECUENCIA_STORAGE_KPIS = 'izzy_secuencia_kpis_visible';
    var SECUENCIA_STORAGE_VISTA = 'izzy_secuencia_tipo_vista';


    $(document).ready(function () {
        inicializarEstadoPersistenteSecuencia();
        inicializarVistaSecuencia();
        inicializarEventosSecuencia();
        inicializarDropdownAdaptativoSecuencia();
        cargarDocumentosSecuencia(true);
        listar_secuencia_facturacion();
    });

    function inicializarEventosSecuencia() {
        $('#form_main_secuencia').off('submit.secuencia').on('submit.secuencia', function (e) {
            e.preventDefault();
            secuenciaState.page = 1;
            aplicarFiltrosYRender();
        });

        $('#form_main_secuencia').off('reset.secuencia').on('reset.secuencia', function () {
            var form = this;
            setTimeout(function () {
                $(form).find('.selectpicker').val('').selectpicker('refresh');
                $('#filtro_secuencia_general, #secuencia_buscar_listado').val('');
                secuenciaState.page = 1;
                aplicarFiltrosYRender();
            }, 50);
        });

        $('#filtro_secuencia_general').off('input.secuencia').on('input.secuencia', secuenciaDebounce(function () {
            var valor = $(this).val();
            $('#secuencia_buscar_listado').val(valor);
            secuenciaState.page = 1;
            aplicarFiltrosYRender();
        }, 180));

        $('#secuencia_buscar_listado').off('input.secuencia').on('input.secuencia', secuenciaDebounce(function () {
            var valor = $(this).val();
            $('#filtro_secuencia_general').val(valor);
            secuenciaState.page = 1;
            aplicarFiltrosYRender();
        }, 180));

        $('#btn_toggle_secuencia_filtros').off('click.secuenciaPanel').on('click.secuenciaPanel', function () {
            alternarPanelSecuencia('#secuencia_filtros_body', $(this), SECUENCIA_STORAGE_FILTROS);
        });

        $('#btn_toggle_secuencia_kpis').off('click.secuenciaPanel').on('click.secuenciaPanel', function () {
            alternarPanelSecuencia('#secuencia_kpis_body', $(this), SECUENCIA_STORAGE_KPIS);
        });

        $('#estado_secuencia_main, #documento_secuencia_main, #vencimiento_secuencia_main')
            .off('changed.bs.select.secuencia change.secuencia')
            .on('changed.bs.select.secuencia change.secuencia', function () {
                secuenciaState.page = 1;
                aplicarFiltrosYRender();
            });

        $('#secuencia_page_size').off('change.secuencia').on('change.secuencia', function () {
            var valor = parseInt($(this).val(), 10);
            secuenciaState.pageSize = isNaN(valor) || valor <= 0
                ? (secuenciaState.view === 'miniatura' ? 6 : 5)
                : valor;

            if (secuenciaState.view === 'miniatura') {
                secuenciaState.pageSizeMiniatura = secuenciaState.pageSize;
            } else {
                secuenciaState.pageSizeDetalle = secuenciaState.pageSize;
            }

            secuenciaState.page = 1;
            renderSecuencias();
        });

        $('.secuencia-view-btn').off('click.secuenciaVista').on('click.secuenciaVista', function () {
            cambiarVistaSecuencia($(this).data('view'));
        });

        $('#btn_actualizar_secuencias').off('click.secuencia').on('click.secuencia', function () {
            listar_secuencia_facturacion();
        });

        $('#btn_nueva_secuencia').off('click.secuencia').on('click.secuencia', function () {
            modal_secuencia_facturacion();
        });

        $('#btn_documentos_secuencia').off('click.secuencia').on('click.secuencia', function () {
            abrirModalDocumentosSecuencia(false);
        });

        $('#btn_administrar_documentos_desde_modal').off('click.secuencia').on('click.secuencia', function () {
            abrirModalDocumentosSecuencia(true);
        });

        $('#btn_exportar_secuencia_excel').off('click.secuencia').on('click.secuencia', exportarSecuenciasExcel);
        $('#btn_exportar_secuencia_pdf').off('click.secuencia').on('click.secuencia', exportarSecuenciasPDF);

        $('#secuencia_listado').off('click.secuenciaAcciones')
            .on('click.secuenciaAcciones', '.js-secuencia-editar', function (e) {
                e.preventDefault();
                editarSecuenciaPorId($(this).data('id'));
            })
            .on('click.secuenciaAcciones', '.js-secuencia-eliminar', function (e) {
                e.preventDefault();
                eliminarSecuenciaPorId($(this).data('id'));
            });

        $('#secuencia_paginacion').off('click.secuencia').on('click.secuencia', 'button[data-page]', function () {
            if ($(this).prop('disabled')) {
                return;
            }
            var nuevaPagina = parseInt($(this).data('page'), 10);
            if (!isNaN(nuevaPagina)) {
                secuenciaState.page = nuevaPagina;
                renderSecuencias();
                secuenciaScrollToList();
            }
        });

        $('#formSecuencia #estado_secuencia').off('change.secuencia').on('change.secuencia', actualizarLabelEstadoSecuencia);

        $('#modal_registrar_secuencias').off('shown.bs.modal.secuencia').on('shown.bs.modal.secuencia', function () {
            var $target = $('#formSecuencia #empresa_secuencia:enabled');
            if ($target.length) {
                $target.trigger('focus');
            } else {
                $('#formSecuencia #siguiente_secuencia').trigger('focus').select();
            }
        });

        $('#modal_documentos_secuencia')
            .off('shown.bs.modal.documentos hidden.bs.modal.documentos')
            .on('shown.bs.modal.documentos', function () {
                $('#documento_nombre_secuencia').trigger('focus');
            })
            .on('hidden.bs.modal.documentos', function () {
                if (reabrirSecuenciaTrasDocumentos) {
                    reabrirSecuenciaTrasDocumentos = false;
                    cargarDocumentosSecuencia(false).always(function () {
                        $('#modal_registrar_secuencias').modal({show: true, keyboard: false, backdrop: 'static'});
                    });
                }
            });

        $('#formDocumentoSecuencia').off('submit.documentos').on('submit.documentos', function (e) {
            e.preventDefault();
            guardarDocumentoSecuencia();
        });

        $('#btn_cancelar_edicion_documento').off('click.documentos').on('click.documentos', resetFormularioDocumentoSecuencia);
        $('#btn_refrescar_documentos_secuencia').off('click.documentos').on('click.documentos', function () {
            cargarDocumentosSecuencia(true);
        });

        $('#documentos_secuencia_listado').off('click.documentos')
            .on('click.documentos', '.js-documento-editar', function () {
                editarDocumentoSecuencia($(this).data('id'));
            })
            .on('click.documentos', '.js-documento-estado', function () {
                cambiarEstadoDocumentoSecuencia($(this).data('id'), $(this).data('estado'));
            });

    }

    function obtenerEstadoPanelSecuencia(clave, valorPorDefecto) {
        try {
            var valor = window.localStorage.getItem(clave);
            if (valor === null) {
                return valorPorDefecto;
            }
            return valor === '1';
        } catch (error) {
            return valorPorDefecto;
        }
    }

    function guardarEstadoPanelSecuencia(clave, visible) {
        try {
            window.localStorage.setItem(clave, visible ? '1' : '0');
        } catch (error) {
            // Si el navegador bloquea storage, el módulo sigue funcionando sin persistencia.
        }
    }

    function actualizarBotonPanelSecuencia($boton, visible) {
        $boton.html(
            visible
                ? '<i class="fas fa-chevron-up mr-1"></i> Ocultar'
                : '<i class="fas fa-chevron-down mr-1"></i> Mostrar'
        );
        $boton.attr('aria-expanded', visible ? 'true' : 'false');
    }

    function aplicarEstadoPanelSecuencia(selector, $boton, visible) {
        var $panel = $(selector);
        $panel.toggle(visible);
        actualizarBotonPanelSecuencia($boton, visible);
    }

    function alternarPanelSecuencia(selector, $boton, claveStorage) {
        var $panel = $(selector);
        var visible = !$panel.is(':visible');

        $panel.stop(true, true).slideToggle(180);
        actualizarBotonPanelSecuencia($boton, visible);
        guardarEstadoPanelSecuencia(claveStorage, visible);
    }

    function inicializarEstadoPersistenteSecuencia() {
        var filtrosVisibles = obtenerEstadoPanelSecuencia(SECUENCIA_STORAGE_FILTROS, false);
        var kpisVisibles = obtenerEstadoPanelSecuencia(SECUENCIA_STORAGE_KPIS, true);

        aplicarEstadoPanelSecuencia(
            '#secuencia_filtros_body',
            $('#btn_toggle_secuencia_filtros'),
            filtrosVisibles
        );

        aplicarEstadoPanelSecuencia(
            '#secuencia_kpis_body',
            $('#btn_toggle_secuencia_kpis'),
            kpisVisibles
        );
    }

    function secuenciaDebounce(fn, wait) {
        var timeout;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                fn.apply(context, args);
            }, wait);
        };
    }

    function secuenciaValor(valor, textoDefault) {
        if (valor === null || valor === undefined || String(valor).trim() === '') {
            return textoDefault !== undefined ? textoDefault : 'No registrado';
        }
        return String(valor).trim();
    }

    function secuenciaEscape(valor) {
        return secuenciaValor(valor, '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function secuenciaNumero(valor) {
        var numero = parseInt(String(valor || '0').replace(/[^0-9]/g, ''), 10);
        return isNaN(numero) ? 0 : numero;
    }

    function secuenciaFechaToDate(fecha) {
        fecha = secuenciaValor(fecha, '');
        if (!fecha) {
            return null;
        }

        var iso = /^\d{4}-\d{2}-\d{2}$/;
        var latam = /^\d{2}\/\d{2}\/\d{4}$/;
        var partes;

        if (iso.test(fecha)) {
            partes = fecha.split('-');
            return new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
        }

        if (latam.test(fecha)) {
            partes = fecha.split('/');
            return new Date(parseInt(partes[2], 10), parseInt(partes[1], 10) - 1, parseInt(partes[0], 10));
        }

        return null;
    }

    function secuenciaDiasRestantes(fechaLimite) {
        var fecha = secuenciaFechaToDate(fechaLimite);
        if (!fecha) {
            return null;
        }
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        fecha.setHours(0, 0, 0, 0);
        return Math.ceil((fecha.getTime() - hoy.getTime()) / 86400000);
    }

    function secuenciaEstadoBadge(estado) {
        if (parseInt(estado, 10) === 1) {
            return '<span class="secuencia-status-badge secuencia-status-active"><i class="fas fa-check-circle"></i> Activo</span>';
        }
        return '<span class="secuencia-status-badge secuencia-status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
    }

    function secuenciaDocumentoBadge(documento) {
        var doc = secuenciaValor(documento, 'Documento');
        var lower = doc.toLowerCase();
        var icon = 'fa-file';
        var cls = 'secuencia-doc-normal';

        if (lower.indexOf('nota de credito') !== -1 || lower.indexOf('nota de crédito') !== -1) {
            icon = 'fa-file-invoice-dollar';
            cls = 'secuencia-doc-credito';
        } else if (lower.indexOf('nota de debito') !== -1 || lower.indexOf('nota de débito') !== -1) {
            icon = 'fa-file-invoice';
            cls = 'secuencia-doc-debito';
        } else if (lower.indexOf('proforma') !== -1) {
            icon = 'fa-file-alt';
            cls = 'secuencia-doc-proforma';
        } else if (lower.indexOf('factura') !== -1) {
            icon = 'fa-file-invoice-dollar';
            cls = 'secuencia-doc-factura';
        }

        return '<span class="secuencia-doc-badge ' + cls + '"><i class="fas ' + icon + '"></i> ' + secuenciaEscape(doc) + '</span>';
    }

    function secuenciaVencimientoBadge(fechaLimite) {
        var dias = secuenciaDiasRestantes(fechaLimite);
        if (dias === null) {
            return '<span class="secuencia-vencimiento-badge secuencia-vencimiento-normal"><i class="fas fa-calendar-alt"></i> Sin fecha</span>';
        }
        if (dias < 0) {
            return '<span class="secuencia-vencimiento-badge secuencia-vencimiento-vencida"><i class="fas fa-times-circle"></i> Vencida</span>';
        }
        if (dias <= 30) {
            return '<span class="secuencia-vencimiento-badge secuencia-vencimiento-alerta"><i class="fas fa-exclamation-triangle"></i> ' + dias + ' días</span>';
        }
        return '<span class="secuencia-vencimiento-badge secuencia-vencimiento-ok"><i class="fas fa-check-circle"></i> Vigente</span>';
    }

    function secuenciaDisponibles(row) {
        var rangoFinal = secuenciaNumero(row.rango_final);
        var siguiente = secuenciaNumero(row.siguiente);
        var disponibles = rangoFinal - siguiente + 1;
        return disponibles < 0 ? 0 : disponibles;
    }

    function secuenciaPorcentajeUsado(row) {
        var inicial = secuenciaNumero(row.rango_inicial);
        var final = secuenciaNumero(row.rango_final);
        var siguiente = secuenciaNumero(row.siguiente);
        var total = final - inicial + 1;
        var usado = siguiente - inicial;

        if (total <= 0) {
            return 0;
        }
        usado = Math.max(0, usado);
        return Math.min(100, Math.max(0, Math.round((usado / total) * 100)));
    }

    function secuenciaDiasTexto(fechaLimite) {
        var dias = secuenciaDiasRestantes(fechaLimite);
        if (dias === null) {
            return 'Sin información';
        }
        if (dias < 0) {
            return 'Venció hace ' + Math.abs(dias) + ' días';
        }
        if (dias === 0) {
            return 'Vence hoy';
        }
        return 'Faltan ' + dias + ' días';
    }

    window.listar_secuencia_facturacion = function () {
        if (secuenciaState.loading) {
            return;
        }

        secuenciaState.loading = true;
        mostrarEstadoCargaSecuencias(true);

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTableSecuenciaFacturacion.php',
            dataType: 'json',
            data: {
                estado: ''
            }
        }).done(function (response) {
            var data = response && Array.isArray(response.data) ? response.data : [];
            secuenciaState.rows = data;
            secuenciaState.page = 1;
            aplicarFiltrosYRender();
        }).fail(function () {
            secuenciaState.rows = [];
            secuenciaState.filtered = [];
            renderSecuencias();
            showNotify('error', 'Error', 'No se pudieron cargar las secuencias de facturación.');
        }).always(function () {
            secuenciaState.loading = false;
            mostrarEstadoCargaSecuencias(false);
        });
    };

    function aplicarFiltrosYRender() {
        var texto = String($('#filtro_secuencia_general').val() || '').trim().toLowerCase();
        var estado = String($('#estado_secuencia_main').val() || '').trim();
        var documento = String($('#documento_secuencia_main').val() || '').trim().toLowerCase();
        var vencimiento = String($('#vencimiento_secuencia_main').val() || '').trim().toLowerCase();

        secuenciaState.filtered = secuenciaState.rows.filter(function (item) {
            var itemEstado = String(item.estado !== undefined ? item.estado : item.activo);
            var itemDocumento = String(item.documento || '').toLowerCase();
            var dias = secuenciaDiasRestantes(item.fecha_limite);
            var base = [
                item.empresa,
                item.documento,
                item.cai,
                item.prefijo,
                item.siguiente,
                item.rango_inicial,
                item.rango_final,
                item.fecha_activacion,
                item.fecha_limite,
                itemEstado === '1' ? 'activo' : 'inactivo'
            ].join(' ').toLowerCase();

            if (texto && base.indexOf(texto) === -1) {
                return false;
            }
            if (estado !== '' && itemEstado !== estado) {
                return false;
            }
            if (documento && itemDocumento !== documento) {
                return false;
            }
            if (vencimiento === 'vencida' && !(dias !== null && dias < 0)) {
                return false;
            }
            if (vencimiento === 'por_vencer' && !(dias !== null && dias >= 0 && dias <= 30)) {
                return false;
            }
            if (vencimiento === 'vigente' && !(dias !== null && dias > 30)) {
                return false;
            }
            return true;
        });

        actualizarResumenSecuencia(secuenciaState.filtered);
        renderSecuencias();
    }

    function actualizarResumenSecuencia(rows) {
        var activas = 0;
        var conCai = 0;
        var disponibles = 0;
        var porVencer = 0;

        rows.forEach(function (item) {
            var estado = parseInt(item.estado !== undefined ? item.estado : item.activo, 10);
            var dias = secuenciaDiasRestantes(item.fecha_limite);
            if (estado === 1) {
                activas++;
            }
            if (secuenciaValor(item.cai, '') !== '') {
                conCai++;
            }
            disponibles += secuenciaDisponibles(item);
            if (dias !== null && dias >= 0 && dias <= 30) {
                porVencer++;
            }
        });

        $('#secuencia_total_activas').text(activas);
        $('#secuencia_total_cai').text(conCai);
        $('#secuencia_total_disponibles').text(disponibles);
        $('#secuencia_total_por_vencer').text(porVencer);
    }

    function inicializarVistaSecuencia() {
        var vistaGuardada = 'detalle';

        try {
            vistaGuardada = localStorage.getItem(SECUENCIA_STORAGE_VISTA) || 'detalle';
        } catch (e) {
            vistaGuardada = 'detalle';
        }

        if (vistaGuardada !== 'miniatura') {
            vistaGuardada = 'detalle';
        }

        secuenciaState.view = vistaGuardada;
        actualizarBotonesVistaSecuencia();
        sincronizarTamanoPaginaSecuencia();
    }

    function cambiarVistaSecuencia(vista) {
        secuenciaState.view = vista === 'miniatura' ? 'miniatura' : 'detalle';

        try {
            localStorage.setItem(SECUENCIA_STORAGE_VISTA, secuenciaState.view);
        } catch (e) {
            // La vista sigue funcionando aunque localStorage no esté disponible.
        }

        actualizarBotonesVistaSecuencia();
        sincronizarTamanoPaginaSecuencia();
        secuenciaState.page = 1;
        renderSecuencias();
    }

    function sincronizarTamanoPaginaSecuencia() {
        var $select = $('#secuencia_page_size');
        var esMiniatura = secuenciaState.view === 'miniatura';
        var opciones = esMiniatura ? [6, 12, 18, 30] : [5, 10, 20, 50];
        var seleccionado = esMiniatura
            ? secuenciaState.pageSizeMiniatura
            : secuenciaState.pageSizeDetalle;

        $select.empty();

        opciones.forEach(function(valor) {
            $select.append('<option value="' + valor + '">' + valor + '</option>');
        });

        secuenciaState.pageSize = opciones.indexOf(seleccionado) !== -1
            ? seleccionado
            : opciones[0];

        $select.val(String(secuenciaState.pageSize));
    }

    function actualizarBotonesVistaSecuencia() {
        $('.secuencia-view-btn').removeClass('active').attr('aria-pressed', 'false');

        $('.secuencia-view-btn[data-view="' + secuenciaState.view + '"]')
            .addClass('active')
            .attr('aria-pressed', 'true');
    }

    function renderSecuenciaHeaderDetalle() {
        return '' +
            '<div class="secuencia-detail-header" role="row">' +
                '<div class="secuencia-detail-header-cell" role="columnheader"><i class="fas fa-file-invoice"></i><span>Documento</span></div>' +
                '<div class="secuencia-detail-header-cell" role="columnheader"><i class="fas fa-key"></i><span>Autorización / CAI</span></div>' +
                '<div class="secuencia-detail-header-cell" role="columnheader"><i class="fas fa-list-ol"></i><span>Numeración</span></div>' +
                '<div class="secuencia-detail-header-cell" role="columnheader"><i class="fas fa-calendar-alt"></i><span>Vigencia</span></div>' +
                '<div class="secuencia-detail-header-cell" role="columnheader"><i class="fas fa-cog"></i><span>Acciones</span></div>' +
            '</div>';
    }

    function renderSecuencias() {
        var $listado = $('#secuencia_listado');
        var total = secuenciaState.filtered.length;
        var pageSize = secuenciaState.pageSize;
        var totalPages = Math.max(1, Math.ceil(total / pageSize));

        if (secuenciaState.page > totalPages) {
            secuenciaState.page = totalPages;
        }

        var start = (secuenciaState.page - 1) * pageSize;
        var end = Math.min(start + pageSize, total);
        var rows = secuenciaState.filtered.slice(start, end);

        $listado.empty();
        $('#secuencia_empty').toggleClass('d-none', total !== 0 || secuenciaState.loading);

        if (!total) {
            $('#secuencia_resultado_info').text('Mostrando 0 registros');
            $('#secuencia_paginacion').empty();
            aplicarPermisosSecuencia();
            return;
        }

        $listado
            .toggleClass('vista-detalle', secuenciaState.view === 'detalle')
            .toggleClass('vista-miniatura', secuenciaState.view === 'miniatura');

        var html = '';

        if (secuenciaState.view === 'detalle') {
            html += renderSecuenciaHeaderDetalle();
            html += rows.map(renderSecuenciaCard).join('');
        } else {
            html += rows.map(renderSecuenciaMiniatura).join('');
        }

        $listado.html(html);

        $('#secuencia_resultado_info').text('Mostrando registros del ' + (start + 1) + ' al ' + end + ' de un total de ' + total + ' registros');
        renderPaginacion(totalPages);
        aplicarPermisosSecuencia();
    }

    function renderSecuenciaCard(row) {
        var id = secuenciaEscape(row.secuencia_facturacion_id);
        var empresa = secuenciaEscape(secuenciaValor(row.empresa, 'Empresa no registrada'));
        var documento = secuenciaEscape(secuenciaValor(row.documento, 'Documento'));
        var estado = row.estado !== undefined ? row.estado : row.activo;
        var cai = secuenciaEscape(secuenciaValor(row.cai, 'Sin CAI'));
        var prefijo = secuenciaEscape(secuenciaValor(row.prefijo, 'Sin prefijo'));
        var relleno = secuenciaEscape(secuenciaValor(row.relleno, '0'));
        var incremento = secuenciaEscape(secuenciaValor(row.incremento, '1'));
        var siguiente = secuenciaEscape(secuenciaValor(row.siguiente, '0'));
        var inicial = secuenciaEscape(secuenciaValor(row.rango_inicial, '0'));
        var final = secuenciaEscape(secuenciaValor(row.rango_final, '0'));
        var activacion = secuenciaEscape(secuenciaValor(row.fecha_activacion, 'No registrada'));
        var limite = secuenciaEscape(secuenciaValor(row.fecha_limite, 'No registrada'));
        var registro = secuenciaEscape(secuenciaValor(row.fecha_registro, 'No registrada'));
        var disponibles = secuenciaDisponibles(row);
        var porcentaje = secuenciaPorcentajeUsado(row);

        return '' +
            '<article class="secuencia-record-card" data-id="' + id + '">' +
                '<div class="secuencia-record-topline"></div>' +
                '<div class="secuencia-record-grid">' +
                    '<section class="secuencia-record-section secuencia-record-main">' +
                        '<div class="secuencia-main-box">' +
                            '<div class="secuencia-main-icon"><i class="fas fa-file-invoice"></i></div>' +
                            '<div class="secuencia-main-info">' +
                                '<div class="secuencia-title-row"><h6 class="secuencia-empresa">' + empresa + '</h6>' + secuenciaEstadoBadge(estado) + '</div>' +
                                '<div class="secuencia-documento-row">' + secuenciaDocumentoBadge(documento) + '</div>' +
                                '<div class="secuencia-id-text"><i class="fas fa-hashtag mr-1"></i>ID: ' + id + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="secuencia-record-section">' +
                        '<h6 class="secuencia-section-title"><i class="fas fa-key"></i> Autorización / CAI</h6>' +
                        '<div class="secuencia-detail-list">' +
                            '<div class="secuencia-detail-item"><span class="secuencia-detail-icon secuencia-icon-cai"><i class="fas fa-key"></i></span><span><strong>CAI:</strong> <span class="secuencia-cai-text">' + cai + '</span></span></div>' +
                            '<div class="secuencia-detail-item"><span class="secuencia-detail-icon secuencia-icon-prefijo"><i class="fas fa-barcode"></i></span><span><strong>Prefijo:</strong> ' + prefijo + '</span></div>' +
                            '<div class="secuencia-mini-row"><span><i class="fas fa-fill-drip mr-1"></i>Relleno: <strong>' + relleno + '</strong></span><span><i class="fas fa-plus mr-1"></i>Incremento: <strong>' + incremento + '</strong></span></div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="secuencia-record-section">' +
                        '<h6 class="secuencia-section-title"><i class="fas fa-list-ol"></i> Numeración</h6>' +
                        '<div class="secuencia-number-box">' +
                            '<div class="secuencia-next-number"><span>Siguiente</span><strong>' + siguiente + '</strong></div>' +
                            '<div class="secuencia-range-text"><i class="fas fa-arrows-alt-h mr-1"></i>' + inicial + ' - ' + final + '</div>' +
                            '<div class="secuencia-progress-box">' +
                                '<div class="secuencia-progress-line"><span style="width:' + porcentaje + '%"></span></div>' +
                                '<div class="secuencia-progress-meta"><span>' + porcentaje + '% usado</span><strong>' + disponibles + ' disponibles</strong></div>' +
                            '</div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="secuencia-record-section">' +
                        '<h6 class="secuencia-section-title"><i class="fas fa-calendar-alt"></i> Vigencia</h6>' +
                        '<div class="secuencia-detail-list">' +
                            '<div class="secuencia-detail-item"><span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-calendar-check"></i></span><span><strong>Activación:</strong> ' + activacion + '</span></div>' +
                            '<div class="secuencia-detail-item"><span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-calendar-times"></i></span><span><strong>Límite:</strong> ' + limite + '</span></div>' +
                            '<div class="secuencia-detail-item"><span class="secuencia-detail-icon secuencia-icon-date"><i class="fas fa-clock"></i></span><span><strong>Registro:</strong> ' + registro + '</span></div>' +
                            '<div class="secuencia-vencimiento-row">' + secuenciaVencimientoBadge(row.fecha_limite) + '<small>' + secuenciaEscape(secuenciaDiasTexto(row.fecha_limite)) + '</small></div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="secuencia-record-section secuencia-record-actions">' +
                        '<h6 class="secuencia-section-title"><i class="fas fa-cog"></i> Acciones</h6>' +
                        '<div class="dropdown secuencia-actions">' +
                            '<button type="button" class="btn btn-primary dropdown-toggle" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Acciones</button>' +
                            '<div class="dropdown-menu dropdown-menu-right">' +
                                '<button type="button" class="dropdown-item js-secuencia-editar table_editar ocultar" data-id="' + id + '"><i class="fas fa-edit mr-2"></i>Editar</button>' +
                                '<button type="button" class="dropdown-item text-danger js-secuencia-eliminar table_eliminar ocultar" data-id="' + id + '"><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>' +
                            '</div>' +
                        '</div>' +
                    '</section>' +
                '</div>' +
            '</article>';
    }

    function renderSecuenciaMiniatura(row) {
        var id = secuenciaEscape(row.secuencia_facturacion_id);
        var empresa = secuenciaEscape(secuenciaValor(row.empresa, 'Empresa no registrada'));
        var documento = secuenciaEscape(secuenciaValor(row.documento, 'Documento'));
        var estado = row.estado !== undefined ? row.estado : row.activo;
        var cai = secuenciaEscape(secuenciaValor(row.cai, 'Sin CAI'));
        var prefijo = secuenciaEscape(secuenciaValor(row.prefijo, 'Sin prefijo'));
        var siguiente = secuenciaEscape(secuenciaValor(row.siguiente, '0'));
        var disponibles = secuenciaDisponibles(row);
        var porcentaje = secuenciaPorcentajeUsado(row);
        var limite = secuenciaEscape(secuenciaValor(row.fecha_limite, 'No registrada'));

        return '' +
            '<article class="secuencia-mini-card" data-id="' + id + '">' +
                '<div class="secuencia-mini-topline"></div>' +

                '<div class="secuencia-mini-head">' +
                    '<div class="secuencia-main-icon"><i class="fas fa-file-invoice"></i></div>' +
                    '<div class="secuencia-mini-title">' +
                        '<div class="secuencia-title-row"><h6 class="secuencia-empresa">' + empresa + '</h6>' + secuenciaEstadoBadge(estado) + '</div>' +
                        '<div class="secuencia-documento-row">' + secuenciaDocumentoBadge(documento) + '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="secuencia-mini-body">' +
                    '<div class="secuencia-mini-field">' +
                        '<span><i class="fas fa-key"></i> CAI</span>' +
                        '<strong class="secuencia-mini-wrap">' + cai + '</strong>' +
                    '</div>' +
                    '<div class="secuencia-mini-field">' +
                        '<span><i class="fas fa-barcode"></i> Prefijo</span>' +
                        '<strong>' + prefijo + '</strong>' +
                    '</div>' +
                    '<div class="secuencia-mini-field">' +
                        '<span><i class="fas fa-list-ol"></i> Siguiente</span>' +
                        '<strong class="secuencia-mini-number">' + siguiente + '</strong>' +
                    '</div>' +
                    '<div class="secuencia-mini-field">' +
                        '<span><i class="fas fa-calendar-times"></i> Límite</span>' +
                        '<strong>' + limite + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="secuencia-mini-progress">' +
                    '<div class="secuencia-progress-line"><span style="width:' + porcentaje + '%"></span></div>' +
                    '<div class="secuencia-progress-meta"><span>' + porcentaje + '% usado</span><strong>' + disponibles + ' disponibles</strong></div>' +
                '</div>' +

                '<div class="secuencia-mini-footer">' +
                    '<span class="secuencia-id-text"><i class="fas fa-hashtag mr-1"></i>ID: ' + id + '</span>' +
                    '<div class="dropdown secuencia-actions">' +
                        '<button type="button" class="btn btn-primary dropdown-toggle" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Acciones</button>' +
                        '<div class="dropdown-menu dropdown-menu-right">' +
                            '<button type="button" class="dropdown-item js-secuencia-editar table_editar ocultar" data-id="' + id + '"><i class="fas fa-edit mr-2"></i>Editar</button>' +
                            '<button type="button" class="dropdown-item text-danger js-secuencia-eliminar table_eliminar ocultar" data-id="' + id + '"><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function renderPaginacion(totalPages) {
        var $paginacion = $('#secuencia_paginacion');
        var current = secuenciaState.page;
        var html = [];

        html.push(paginaButton('Inicio', 1, current === 1, 'fa-angle-double-left'));
        html.push(paginaButton('Anterior', Math.max(1, current - 1), current === 1, 'fa-angle-left'));

        var inicio = Math.max(1, current - 2);
        var fin = Math.min(totalPages, inicio + 4);
        inicio = Math.max(1, fin - 4);

        for (var p = inicio; p <= fin; p++) {
            html.push('<button type="button" class="btn secuencia-page-btn ' + (p === current ? 'active' : '') + '" data-page="' + p + '" aria-label="Página ' + p + '">' + p + '</button>');
        }

        html.push(paginaButton('Siguiente', Math.min(totalPages, current + 1), current === totalPages, 'fa-angle-right', true));
        html.push(paginaButton('Final', totalPages, current === totalPages, 'fa-angle-double-right', true));

        $paginacion.html(html.join(''));
    }

    function paginaButton(texto, pagina, disabled, icono, iconRight) {
        return '<button type="button" class="btn secuencia-page-btn secuencia-page-nav" data-page="' + pagina + '" ' + (disabled ? 'disabled' : '') + '>' +
            (iconRight ? '' : '<i class="fas ' + icono + '"></i>') +
            '<span>' + texto + '</span>' +
            (iconRight ? '<i class="fas ' + icono + '"></i>' : '') +
        '</button>';
    }

    function mostrarEstadoCargaSecuencias(mostrar) {
        $('#secuencia_loading').toggleClass('d-none', !mostrar);
        if (mostrar) {
            $('#secuencia_empty').addClass('d-none');
        }
    }

    function aplicarPermisosSecuencia() {
        if (typeof getPermisosTipoUsuarioAccesosTable === 'function' && typeof getPrivilegioTipoUsuario === 'function') {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        } else if (typeof aplicarPermisosDataTablesAsync === 'function') {
            aplicarPermisosDataTablesAsync();
        }
    }

    function secuenciaScrollToList() {
        var $card = $('.secuencia-list-card');
        if ($card.length && window.innerWidth < 992) {
            $('html, body').animate({ scrollTop: Math.max(0, $card.offset().top - 80) }, 200);
        }
    }

    function getSecuenciaRowById(id) {
        id = String(id);
        return secuenciaState.rows.find(function (row) {
            return String(row.secuencia_facturacion_id) === id;
        }) || null;
    }

    window.modal_secuencia_facturacion = function () {
        var $form = $('#formSecuencia');
        $form.attr({'data-form': 'save', 'action': '<?php echo SERVERURL;?>ajax/agregarSecuenciaFacturacionAjax.php'});
        if ($form.length && $form[0]) {
            $form[0].reset();
        }

        $('#secuencia_facturacion_id').val('');
        $('#reg_secuencia').show();
        $('#edi_secuencia').hide();

        habilitarCamposSecuenciaNueva();
        actualizarLabelEstadoSecuencia();

        $.when(cargarEmpresasSecuencia(), cargarDocumentosSecuencia(false)).always(function () {
            $('#modal_registrar_secuencias').modal({show: true, keyboard: false, backdrop: 'static'});
        });
    };

    function habilitarCamposSecuenciaNueva() {
        $('#empresa_secuencia, #documento_secuencia').prop('disabled', false).selectpicker('refresh');
        $('#cai_secuencia, #prefijo_secuencia, #relleno_secuencia, #incremento_secuencia, #siguiente_secuencia, #rango_inicial_secuencia, #rango_final_secuencia, #fecha_activacion_secuencia, #fecha_limite_secuencia').prop('readonly', false);
        $('#estado_secuencia').prop('disabled', false).prop('checked', true);
        $('#estado_secuencia_container').show();
    }

    function editarSecuenciaPorId(id) {
        var row = getSecuenciaRowById(id);
        if (!row) {
            showNotify('error', 'Error', 'No se encontró la secuencia seleccionada.');
            return;
        }

        $('#secuencia_facturacion_id').val(id);

        $.when(cargarEmpresasSecuencia(), cargarDocumentosSecuencia(false)).done(function () {
            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>core/editarSecuenciaFacturacion.php',
                data: {secuencia_facturacion_id: id}
            }).done(function (registro) {
                var valores;
                try {
                    valores = typeof registro === 'string' ? JSON.parse(registro) : registro;
                } catch (e) {
                    try {
                        valores = eval(registro); // Compatibilidad con endpoint legado existente.
                    } catch (errorEval) {
                        valores = null;
                    }
                }

                if (!valores || !Array.isArray(valores)) {
                    showNotify('error', 'Error', 'No se pudo interpretar la información de la secuencia.');
                    return;
                }

                var $form = $('#formSecuencia');
                $form.attr({'data-form': 'update', 'action': '<?php echo SERVERURL;?>ajax/modificarSecuenciaFacturacionAjax.php'});
                if ($form.length && $form[0]) {
                    $form[0].reset();
                }

                $('#secuencia_facturacion_id').val(id);
                $('#reg_secuencia').hide();
                $('#edi_secuencia').show();

                $('#empresa_secuencia').val(valores[0]).prop('disabled', true).selectpicker('refresh');
                $('#cai_secuencia').val(valores[1]);
                $('#prefijo_secuencia').val(valores[2]);
                $('#relleno_secuencia').val(valores[3]);
                $('#incremento_secuencia').val(valores[4]);
                $('#siguiente_secuencia').val(valores[5]);
                $('#rango_inicial_secuencia').val(valores[6]);
                $('#rango_final_secuencia').val(valores[7]);
                $('#fecha_activacion_secuencia').val(valores[8]);
                $('#fecha_limite_secuencia').val(valores[9]);
                $('#estado_secuencia').prop('checked', parseInt(valores[10], 10) === 1).prop('disabled', false);
                $('#documento_secuencia').val(valores[11]).prop('disabled', true).selectpicker('refresh');

                $('#cai_secuencia, #prefijo_secuencia, #relleno_secuencia, #incremento_secuencia, #rango_inicial_secuencia, #rango_final_secuencia, #fecha_activacion_secuencia, #fecha_limite_secuencia').prop('readonly', true);
                $('#siguiente_secuencia').prop('readonly', false);
                $('#estado_secuencia_container').show();
                actualizarLabelEstadoSecuencia();

                $('#modal_registrar_secuencias').modal({show: true, keyboard: false, backdrop: 'static'});
            }).fail(function () {
                showNotify('error', 'Error', 'No se pudo cargar la secuencia para editar.');
            });
        });
    }

    function eliminarSecuenciaPorId(id) {
        var row = getSecuenciaRowById(id);
        if (!row) {
            showNotify('error', 'Error', 'No se encontró la secuencia seleccionada.');
            return;
        }

        var mensajeHTML = '¿Desea eliminar permanentemente la secuencia de facturación?<br><br>' +
            '<strong>Empresa:</strong> ' + secuenciaEscape(row.empresa) + '<br>' +
            '<strong>Documento:</strong> ' + secuenciaEscape(row.documento);

        swal({
            title: 'Confirmar eliminación',
            content: {element: 'span', attributes: {innerHTML: mensajeHTML}},
            icon: 'warning',
            buttons: {
                cancel: {text: 'Cancelar', value: null, visible: true, className: 'btn-secondary'},
                confirm: {text: 'Sí, eliminar', value: true, className: 'btn-danger', closeModal: false}
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then(function (confirmar) {
            if (!confirmar) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>ajax/eliminarSecuenciaFacturacionAjax.php',
                dataType: 'json',
                data: {secuencia_id: id},
                beforeSend: function () {
                    if (typeof showLoading === 'function') {
                        showLoading('Eliminando registro...');
                    }
                }
            }).done(function (response) {
                swal.close();
                if (response && response.status === 'success') {
                    showNotify('success', response.title || 'Eliminado', response.message || 'Secuencia eliminada correctamente.');
                    listar_secuencia_facturacion();
                } else {
                    showNotify('error', response && response.title ? response.title : 'Error', response && response.message ? response.message : 'No se pudo eliminar la secuencia.');
                }
            }).fail(function () {
                swal.close();
                showNotify('error', 'Error', 'Ocurrió un error al procesar la solicitud.');
            });
        });
    }

    function cargarEmpresasSecuencia() {
        return $.ajax({
            url: '<?php echo SERVERURL; ?>core/getEmpresa.php',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            var $select = $('#empresa_secuencia');
            var valorActual = $select.val();
            $select.empty().append('<option value="">Seleccione</option>');

            if (response && response.success && Array.isArray(response.data)) {
                response.data.forEach(function (empresa) {
                    $select.append('<option value="' + secuenciaEscape(empresa.empresa_id) + '">' + secuenciaEscape(empresa.nombre) + '</option>');
                });
                if (valorActual && $select.find('option[value="' + valorActual + '"]').length) {
                    $select.val(valorActual);
                } else if ($select.find('option[value="1"]').length) {
                    $select.val('1');
                } else if (response.data.length) {
                    $select.val(String(response.data[0].empresa_id));
                }
            } else {
                $select.append('<option value="">No hay empresas disponibles</option>');
            }
            $select.selectpicker('refresh');
        }).fail(function () {
            showNotify('error', 'Error', 'Error de conexión al cargar empresas.');
        });
    }

    window.getEmpresaSecuencia = cargarEmpresasSecuencia;

    function cargarDocumentosSecuencia(refrescarCatalogo) {
        documentosState.loading = true;
        if (refrescarCatalogo) {
            $('#documentos_secuencia_loading').removeClass('d-none');
        }

        return $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/secuencia/listarDocumentosSecuencia.php',
            dataType: 'json'
        }).done(function (response) {
            documentosState.rows = response && response.success && Array.isArray(response.data) ? response.data : [];
            poblarSelectDocumentos();
            renderDocumentosSecuencia();
        }).fail(function () {
            documentosState.rows = [];
            poblarSelectDocumentos();
            renderDocumentosSecuencia();
            showNotify('error', 'Error', 'No se pudo cargar el catálogo de documentos.');
        }).always(function () {
            documentosState.loading = false;
            $('#documentos_secuencia_loading').addClass('d-none');
        });
    }

    window.getDocumentoSecuencia = function () {
        return cargarDocumentosSecuencia(false);
    };

    function poblarSelectDocumentos() {
        var $modalSelect = $('#documento_secuencia');
        var $filterSelect = $('#documento_secuencia_main');
        var valorModal = $modalSelect.val();
        var valorFiltro = $filterSelect.val();

        $modalSelect.empty().append('<option value="">Seleccione</option>');
        $filterSelect.empty().append('<option value="">Todos</option>');

        documentosState.rows.forEach(function (doc) {
            var nombre = secuenciaEscape(doc.nombre);
            var id = secuenciaEscape(doc.documento_id);
            var estado = parseInt(doc.estado, 10);

            $filterSelect.append('<option value="' + secuenciaEscape(String(doc.nombre).toLowerCase()) + '">' + nombre + (estado === 1 ? '' : ' (Inactivo)') + '</option>');
            if (estado === 1) {
                $modalSelect.append('<option value="' + id + '">' + nombre + '</option>');
            }
        });

        if (valorModal && $modalSelect.find('option[value="' + valorModal + '"]').length) {
            $modalSelect.val(valorModal);
        }
        if (valorFiltro && $filterSelect.find('option[value="' + valorFiltro + '"]').length) {
            $filterSelect.val(valorFiltro);
        }

        $modalSelect.selectpicker('refresh');
        $filterSelect.selectpicker('refresh');
    }

    function abrirModalDocumentosSecuencia(desdeSecuencia) {
        resetFormularioDocumentoSecuencia();
        cargarDocumentosSecuencia(true);

        if (desdeSecuencia && $('#modal_registrar_secuencias').hasClass('show')) {
            reabrirSecuenciaTrasDocumentos = true;
            $('#modal_registrar_secuencias').one('hidden.bs.modal.documentosPuente', function () {
                $('#modal_documentos_secuencia').modal({show: true, keyboard: false, backdrop: 'static'});
            }).modal('hide');
            return;
        }

        reabrirSecuenciaTrasDocumentos = false;
        $('#modal_documentos_secuencia').modal({show: true, keyboard: false, backdrop: 'static'});
    }

    function renderDocumentosSecuencia() {
        var $list = $('#documentos_secuencia_listado');
        $list.empty();
        $('#documentos_secuencia_empty').toggleClass('d-none', documentosState.rows.length > 0 || documentosState.loading);

        if (!documentosState.rows.length) {
            return;
        }

        var html = documentosState.rows.map(function (doc) {
            var id = secuenciaEscape(doc.documento_id);
            var nombre = secuenciaEscape(doc.nombre);
            var estado = parseInt(doc.estado, 10);
            var activas = parseInt(doc.secuencias_activas || 0, 10);
            var total = parseInt(doc.secuencias_total || 0, 10);
            var activo = estado === 1;

            return '' +
                '<article class="documento-secuencia-card">' +
                    '<div class="documento-secuencia-icon"><i class="fas fa-file-alt"></i></div>' +
                    '<div class="documento-secuencia-info">' +
                        '<div class="documento-secuencia-title-row"><h6>' + nombre + '</h6>' +
                            '<span class="documento-status ' + (activo ? 'activo' : 'inactivo') + '"><i class="fas ' + (activo ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (activo ? 'Activo' : 'Inactivo') + '</span>' +
                        '</div>' +
                        '<div class="documento-secuencia-meta"><span><i class="fas fa-hashtag"></i>ID: ' + id + '</span><span><i class="fas fa-sliders-h"></i>' + total + ' secuencia(s)</span><span><i class="fas fa-check"></i>' + activas + ' activa(s)</span></div>' +
                    '</div>' +
                    '<div class="documento-secuencia-actions">' +
                        '<button type="button" class="btn btn-primary btn-sm js-documento-editar" data-id="' + id + '"><i class="fas fa-edit mr-1"></i>Editar</button>' +
                        '<button type="button" class="btn ' + (activo ? 'btn-secondary' : 'btn-success') + ' btn-sm js-documento-estado" data-id="' + id + '" data-estado="' + (activo ? 2 : 1) + '"><i class="fas ' + (activo ? 'fa-pause' : 'fa-play') + ' mr-1"></i>' + (activo ? 'Desactivar' : 'Activar') + '</button>' +
                    '</div>' +
                '</article>';
        }).join('');

        $list.html(html);
    }

    function resetFormularioDocumentoSecuencia() {
        var form = $('#formDocumentoSecuencia')[0];
        if (form) {
            form.reset();
        }
        $('#documento_id_secuencia').val('0');
        $('#documento_form_titulo').text('Nuevo Documento');
        $('#btn_guardar_documento_secuencia').html('<i class="fas fa-save mr-1"></i> Guardar documento');
        $('#btn_cancelar_edicion_documento').addClass('d-none');
    }

    function editarDocumentoSecuencia(id) {
        var doc = documentosState.rows.find(function (item) {
            return String(item.documento_id) === String(id);
        });
        if (!doc) {
            showNotify('error', 'Error', 'No se encontró el documento seleccionado.');
            return;
        }

        $('#documento_id_secuencia').val(doc.documento_id);
        $('#documento_nombre_secuencia').val(doc.nombre).trigger('focus').select();
        $('#documento_form_titulo').text('Editar Documento');
        $('#btn_guardar_documento_secuencia').html('<i class="fas fa-save mr-1"></i> Guardar cambios');
        $('#btn_cancelar_edicion_documento').removeClass('d-none');
    }

    function guardarDocumentoSecuencia() {
        var nombre = String($('#documento_nombre_secuencia').val() || '').trim();
        if (!nombre) {
            showNotify('warning', 'Dato requerido', 'Ingrese el nombre del documento.');
            $('#documento_nombre_secuencia').trigger('focus');
            return;
        }

        var $btn = $('#btn_guardar_documento_secuencia');
        $btn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/secuencia/guardarDocumentoSecuenciaAjax.php',
            dataType: 'json',
            data: $('#formDocumentoSecuencia').serialize()
        }).done(function (response) {
            if (response && response.status === 'success') {
                showNotify('success', response.title || 'Guardado', response.message || 'Documento guardado correctamente.');
                resetFormularioDocumentoSecuencia();
                cargarDocumentosSecuencia(true).done(function () {
                    aplicarFiltrosYRender();
                });
            } else {
                showNotify('error', response && response.title ? response.title : 'Error', response && response.message ? response.message : 'No se pudo guardar el documento.');
            }
        }).fail(function () {
            showNotify('error', 'Error', 'No se pudo guardar el documento.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function cambiarEstadoDocumentoSecuencia(id, nuevoEstado) {
        var doc = documentosState.rows.find(function (item) {
            return String(item.documento_id) === String(id);
        });
        if (!doc) {
            return;
        }

        var activar = parseInt(nuevoEstado, 10) === 1;
        swal({
            title: activar ? 'Activar documento' : 'Desactivar documento',
            text: activar ? 'El documento quedará disponible al crear nuevas secuencias.' : 'El documento dejará de aparecer al crear nuevas secuencias.',
            icon: 'warning',
            buttons: {
                cancel: {text: 'Cancelar', value: null, visible: true, className: 'btn-secondary'},
                confirm: {text: activar ? 'Sí, activar' : 'Sí, desactivar', value: true, className: activar ? 'btn-success' : 'btn-danger'}
            },
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then(function (confirmar) {
            if (!confirmar) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>core/secuencia/cambiarEstadoDocumentoSecuenciaAjax.php',
                dataType: 'json',
                data: {documento_id: id, estado: nuevoEstado}
            }).done(function (response) {
                if (response && response.status === 'success') {
                    showNotify('success', response.title || 'Actualizado', response.message || 'Estado actualizado correctamente.');
                    cargarDocumentosSecuencia(true);
                } else {
                    showNotify('error', response && response.title ? response.title : 'Error', response && response.message ? response.message : 'No se pudo actualizar el documento.');
                }
            }).fail(function () {
                showNotify('error', 'Error', 'No se pudo actualizar el estado del documento.');
            });
        });
    }

    function actualizarLabelEstadoSecuencia() {
        var activo = $('#estado_secuencia').is(':checked');
        $('#label_estado_secuencia').text(activo ? 'Activo' : 'Inactivo');
    }

    function secuenciaExportRows() {
        return secuenciaState.filtered.map(function (row) {
            var estado = parseInt(row.estado !== undefined ? row.estado : row.activo, 10) === 1 ? 'Activo' : 'Inactivo';
            return {
                empresa: secuenciaValor(row.empresa, ''),
                documento: secuenciaValor(row.documento, ''),
                estado: estado,
                cai: secuenciaValor(row.cai, 'Sin CAI'),
                prefijo: secuenciaValor(row.prefijo, 'Sin prefijo'),
                relleno: secuenciaValor(row.relleno, '0'),
                incremento: secuenciaValor(row.incremento, '1'),
                siguiente: secuenciaValor(row.siguiente, '0'),
                rango_inicial: secuenciaValor(row.rango_inicial, '0'),
                rango_final: secuenciaValor(row.rango_final, '0'),
                disponibles: secuenciaDisponibles(row),
                porcentaje: secuenciaPorcentajeUsado(row),
                fecha_activacion: secuenciaValor(row.fecha_activacion, ''),
                fecha_limite: secuenciaValor(row.fecha_limite, ''),
                vigencia: secuenciaDiasTexto(row.fecha_limite)
            };
        });
    }

    function secuenciaFechaReporte() {
        if (typeof convertDateFormat === 'function' && typeof today === 'function') {
            return convertDateFormat(today());
        }
        return new Date().toLocaleDateString('es-HN');
    }

    function secuenciaXmlEscape(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function secuenciaExcelColName(index) {
        var name = '';
        var n = index + 1;
        while (n > 0) {
            var mod = (n - 1) % 26;
            name = String.fromCharCode(65 + mod) + name;
            n = Math.floor((n - 1) / 26);
        }
        return name;
    }

    function secuenciaExcelCell(ref, value, styleId, numeric) {
        if (numeric) {
            var numero = Number(value);
            if (!isNaN(numero)) {
                return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
            }
        }

        var texto = secuenciaXmlEscape(value);
        var preserve = /^\s|\s$/.test(String(value === null || value === undefined ? '' : value)) ? ' xml:space="preserve"' : '';
        return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' + texto + '</t></is></c>';
    }

    function secuenciaGenerarXlsx(rows) {
        if (typeof JSZip === 'undefined') {
            return null;
        }

        var headers = [
            'Empresa', 'Documento', 'Estado', 'CAI', 'Prefijo', 'Relleno',
            'Incremento', 'Siguiente', 'Rango Inicial', 'Rango Final',
            'Disponibles', '% Usado', 'Activación', 'Límite', 'Vigencia'
        ];

        var totalActivas = rows.filter(function (row) {
            return String(row.estado || '').toLowerCase() === 'activo';
        }).length;
        var totalCai = rows.filter(function (row) {
            return row.cai && String(row.cai).trim() !== '' && String(row.cai).toLowerCase() !== 'sin cai';
        }).length;
        var totalDisponibles = rows.reduce(function (acc, row) {
            return acc + (parseInt(row.disponibles, 10) || 0);
        }, 0);
        var totalPorVencer = rows.filter(function (row) {
            var texto = String(row.vigencia || '').toLowerCase();
            return texto.indexOf('faltan') !== -1 && (function () {
                var m = texto.match(/(\d+)/);
                return m && parseInt(m[1], 10) <= 30;
            })();
        }).length;

        var dataRows = rows.map(function (row) {
            return [
                row.empresa, row.documento, row.estado, row.cai, row.prefijo,
                row.relleno, row.incremento, row.siguiente, row.rango_inicial,
                row.rango_final, row.disponibles, row.porcentaje,
                row.fecha_activacion, row.fecha_limite, row.vigencia
            ];
        });

        var lastCol = secuenciaExcelColName(headers.length - 1);
        var headerRow = 7;
        var firstDataRow = headerRow + 1;
        var lastRow = Math.max(headerRow, headerRow + dataRows.length);
        var sheetRows = [];

        sheetRows.push('<row r="1" ht="30" customHeight="1">' +
            secuenciaExcelCell('A1', 'IZZY • SECUENCIA DE FACTURACIÓN', 1, false) + '</row>');
        sheetRows.push('<row r="2" ht="20" customHeight="1">' +
            secuenciaExcelCell('A2', 'Control de documentos fiscales, correlativos y vigencia • Generado: ' + secuenciaFechaReporte(), 2, false) + '</row>');

        sheetRows.push('<row r="3" ht="18" customHeight="1">' +
            secuenciaExcelCell('A3', 'REGISTROS', 6, false) +
            secuenciaExcelCell('D3', 'ACTIVAS', 6, false) +
            secuenciaExcelCell('G3', 'CON CAI', 6, false) +
            secuenciaExcelCell('J3', 'DISPONIBLES', 6, false) +
            secuenciaExcelCell('M3', 'POR VENCER', 6, false) +
        '</row>');
        sheetRows.push('<row r="4" ht="26" customHeight="1">' +
            secuenciaExcelCell('A4', rows.length, 7, true) +
            secuenciaExcelCell('D4', totalActivas, 7, true) +
            secuenciaExcelCell('G4', totalCai, 7, true) +
            secuenciaExcelCell('J4', totalDisponibles, 7, true) +
            secuenciaExcelCell('M4', totalPorVencer, 7, true) +
        '</row>');
        sheetRows.push('<row r="5"></row>');
        sheetRows.push('<row r="6" ht="18" customHeight="1">' +
            secuenciaExcelCell('A6', 'Detalle de secuencias filtradas', 8, false) + '</row>');

        var headerCells = headers.map(function (header, index) {
            return secuenciaExcelCell(secuenciaExcelColName(index) + headerRow, header, 3, false);
        }).join('');
        sheetRows.push('<row r="' + headerRow + '" ht="26" customHeight="1">' + headerCells + '</row>');

        dataRows.forEach(function (row, rowIndex) {
            var excelRow = firstDataRow + rowIndex;
            var cells = row.map(function (value, colIndex) {
                var numeric = colIndex === 5 || colIndex === 6 || colIndex === 10 || colIndex === 11;
                var style;

                if (colIndex === 2) {
                    style = String(value || '').toLowerCase() === 'activo' ? 9 : 10;
                } else if (colIndex === 10 || colIndex === 11 || colIndex === 5 || colIndex === 6) {
                    style = 5;
                } else {
                    style = 4;
                }

                return secuenciaExcelCell(secuenciaExcelColName(colIndex) + excelRow, value, style, numeric);
            }).join('');
            sheetRows.push('<row r="' + excelRow + '" ht="22" customHeight="1">' + cells + '</row>');
        });

        var sheetXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
                '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView></sheetViews>' +
                '<sheetFormatPr defaultRowHeight="15"/>' +
                '<cols>' +
                    '<col min="1" max="1" width="25" customWidth="1"/>' +
                    '<col min="2" max="2" width="23" customWidth="1"/>' +
                    '<col min="3" max="3" width="12" customWidth="1"/>' +
                    '<col min="4" max="4" width="42" customWidth="1"/>' +
                    '<col min="5" max="5" width="20" customWidth="1"/>' +
                    '<col min="6" max="7" width="12" customWidth="1"/>' +
                    '<col min="8" max="10" width="17" customWidth="1"/>' +
                    '<col min="11" max="12" width="14" customWidth="1"/>' +
                    '<col min="13" max="14" width="16" customWidth="1"/>' +
                    '<col min="15" max="15" width="24" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join('') + '</sheetData>' +
                '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
                '<mergeCells count="12">' +
                    '<mergeCell ref="A1:' + lastCol + '1"/>' +
                    '<mergeCell ref="A2:' + lastCol + '2"/>' +
                    '<mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/>' +
                    '<mergeCell ref="D3:F3"/><mergeCell ref="D4:F4"/>' +
                    '<mergeCell ref="G3:I3"/><mergeCell ref="G4:I4"/>' +
                    '<mergeCell ref="J3:L3"/><mergeCell ref="J4:L4"/>' +
                    '<mergeCell ref="M3:O3"/><mergeCell ref="M4:O4"/>' +
                '</mergeCells>' +
                '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
                '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
            '</worksheet>';

        var stylesXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
                '<fills count="7">' +
                    '<fill><patternFill patternType="none"/></fill>' +
                    '<fill><patternFill patternType="gray125"/></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/><bgColor indexed="64"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/><bgColor indexed="64"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/><bgColor indexed="64"/></patternFill></fill>' +
                '</fills>' +
                '<borders count="2">' +
                    '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                    '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
                '</borders>' +
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
                    '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>';

        var workbookXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<bookViews><workbookView activeTab="0"/></bookViews>' +
                '<sheets><sheet name="Secuencias" sheetId="1" r:id="rId1"/></sheets>' +
            '</workbook>';

        var workbookRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
            '</Relationships>';

        var rootRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
            '</Relationships>';

        var contentTypes =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
                '<Default Extension="xml" ContentType="application/xml"/>' +
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
            '</Types>';

        var zip = new JSZip();
        zip.file('[Content_Types].xml', contentTypes);
        zip.folder('_rels').file('.rels', rootRels);
        zip.folder('xl').file('workbook.xml', workbookXml);
        zip.folder('xl').file('styles.xml', stylesXml);
        zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
        zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

        var opcionesZip = {
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            compression: 'DEFLATE'
        };

        if (typeof zip.generateAsync === 'function') {
            return zip.generateAsync(opcionesZip);
        }

        if (typeof zip.generate === 'function') {
            try {
                return Promise.resolve(zip.generate(opcionesZip));
            } catch (errorGenerate) {
                console.error('Error al generar XLSX con JSZip legado:', errorGenerate);
                return Promise.reject(errorGenerate);
            }
        }

        return Promise.reject(new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().'));
    }

    function exportarSecuenciasExcel() {
        if (!secuenciaState.filtered.length) {
            showNotify('warning', 'Sin información', 'No hay secuencias para exportar.');
            return;
        }

        var rows = secuenciaExportRows();
        var promesaXlsx = secuenciaGenerarXlsx(rows);

        if (!promesaXlsx) {
            var headersCsv = [
                'Empresa', 'Documento', 'Estado', 'CAI', 'Prefijo', 'Relleno',
                'Incremento', 'Siguiente', 'Rango Inicial', 'Rango Final',
                'Disponibles', '% Usado', 'Activación', 'Límite', 'Vigencia'
            ];

            var csv = [headersCsv].concat(rows.map(function (row) {
                return [
                    row.empresa, row.documento, row.estado, row.cai, row.prefijo,
                    row.relleno, row.incremento, row.siguiente, row.rango_inicial,
                    row.rango_final, row.disponibles, row.porcentaje,
                    row.fecha_activacion, row.fecha_limite, row.vigencia
                ];
            })).map(function (line) {
                return line.map(function (value) {
                    var texto = String(value === null || value === undefined ? '' : value).replace(/"/g, '""');
                    return '"' + texto + '"';
                }).join(',');
            }).join('\r\n');

            descargarBlob('\ufeff' + csv, 'Reporte_Secuencia_Facturacion.csv', 'text/csv;charset=utf-8;');
            showNotify('warning', 'Excel compatible', 'JSZip no está disponible; se generó un CSV compatible con Excel sin advertencias de formato.');
            return;
        }

        promesaXlsx.then(function (blob) {
            descargarBlob(blob, 'Reporte_Secuencia_Facturacion.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', true);
        }).catch(function (error) {
            console.error('Error al generar XLSX:', error);
            showNotify('error', 'Error', 'No se pudo generar el archivo Excel.');
        });
    }

    function exportarSecuenciasPDF() {
        if (!secuenciaState.filtered.length) {
            showNotify('warning', 'Sin información', 'No hay secuencias para exportar.');
            return;
        }

        if (typeof pdfMake === 'undefined') {
            showNotify('warning', 'PDF no disponible', 'La librería PDF no está cargada en esta pantalla.');
            return;
        }

        var rows = secuenciaExportRows();
        var totalActivas = rows.filter(function (row) { return row.estado === 'Activo'; }).length;
        var totalCai = rows.filter(function (row) { return row.cai && row.cai !== 'Sin CAI'; }).length;
        var totalDisponibles = rows.reduce(function (acc, row) {
            return acc + Number(row.disponibles || 0);
        }, 0);

        function secuenciaPdfEstadoColor(estado) {
            return estado === 'Activo' ? '#14804A' : '#6B778C';
        }

        function secuenciaPdfVigenciaColor(texto) {
            texto = String(texto || '').toLowerCase();
            if (texto.indexOf('venció') !== -1 || texto.indexOf('vencida') !== -1) {
                return '#C9372C';
            }

            var match = texto.match(/faltan\s+(\d+)\s+días?/i);
            if (match && parseInt(match[1], 10) <= 30) {
                return '#B65C02';
            }

            if (texto.indexOf('vence hoy') !== -1) {
                return '#B65C02';
            }

            return '#14804A';
        }

        function secuenciaPdfDato(label, valor, opciones) {
            opciones = opciones || {};
            return {
                stack: [
                    {
                        text: String(label || '').toUpperCase(),
                        fontSize: 6.6,
                        bold: true,
                        color: '#6B778C',
                        margin: [0, 0, 0, 2]
                    },
                    {
                        text: String(valor === null || valor === undefined || valor === '' ? '—' : valor),
                        fontSize: opciones.fontSize || 8.4,
                        bold: !!opciones.bold,
                        color: opciones.color || '#172B4D'
                    }
                ]
            };
        }

        function secuenciaPdfTarjeta(row) {
            var estadoColor = secuenciaPdfEstadoColor(row.estado);
            var vigenciaColor = secuenciaPdfVigenciaColor(row.vigencia);
            var porcentaje = Number(row.porcentaje || 0);

            if (porcentaje < 0) porcentaje = 0;
            if (porcentaje > 100) porcentaje = 100;

            return {
                table: {
                    widths: ['*'],
                    body: [[{
                        margin: [11, 10, 11, 10],
                        stack: [
                            {
                                columns: [
                                    {
                                        width: '*',
                                        stack: [
                                            {
                                                text: row.empresa || 'Empresa no registrada',
                                                fontSize: 11,
                                                bold: true,
                                                color: '#172B4D'
                                            },
                                            {
                                                text: row.documento || 'Documento',
                                                fontSize: 8,
                                                bold: true,
                                                color: '#0EA5A8',
                                                margin: [0, 2, 0, 0]
                                            }
                                        ]
                                    },
                                    {
                                        width: 'auto',
                                        stack: [
                                            {
                                                text: row.estado,
                                                fontSize: 7.6,
                                                bold: true,
                                                color: estadoColor,
                                                alignment: 'right'
                                            },
                                            {
                                                text: row.vigencia,
                                                fontSize: 6.8,
                                                bold: true,
                                                color: vigenciaColor,
                                                alignment: 'right',
                                                margin: [0, 3, 0, 0]
                                            }
                                        ]
                                    }
                                ]
                            },
                            {
                                canvas: [
                                    {
                                        type: 'line',
                                        x1: 0,
                                        y1: 0,
                                        x2: 325,
                                        y2: 0,
                                        lineWidth: 0.6,
                                        lineColor: '#DDE3EA'
                                    }
                                ],
                                margin: [0, 8, 0, 8]
                            },
                            {
                                columns: [
                                    {
                                        width: '55%',
                                        stack: [
                                            secuenciaPdfDato('CAI / Autorización', row.cai, {fontSize: 7.5}),
                                            {
                                                columns: [
                                                    {
                                                        width: '58%',
                                                        margin: [0, 8, 4, 0],
                                                        stack: [secuenciaPdfDato('Prefijo', row.prefijo, {bold: true})]
                                                    },
                                                    {
                                                        width: '21%',
                                                        margin: [4, 8, 4, 0],
                                                        stack: [secuenciaPdfDato('Relleno', row.relleno)]
                                                    },
                                                    {
                                                        width: '21%',
                                                        margin: [4, 8, 0, 0],
                                                        stack: [secuenciaPdfDato('Incremento', row.incremento)]
                                                    }
                                                ]
                                            }
                                        ]
                                    },
                                    {
                                        width: '45%',
                                        margin: [10, 0, 0, 0],
                                        stack: [
                                            {
                                                columns: [
                                                    {
                                                        width: '*',
                                                        stack: [secuenciaPdfDato('Siguiente', row.siguiente, {bold: true, fontSize: 11})]
                                                    },
                                                    {
                                                        width: 'auto',
                                                        alignment: 'right',
                                                        stack: [secuenciaPdfDato('Disponibles', row.disponibles, {bold: true, color: '#14804A', fontSize: 10})]
                                                    }
                                                ]
                                            },
                                            {
                                                text: 'Rango: ' + row.rango_inicial + ' - ' + row.rango_final,
                                                fontSize: 7.2,
                                                color: '#5E6C84',
                                                margin: [0, 5, 0, 4]
                                            },
                                            {
                                                canvas: [
                                                    {
                                                        type: 'rect',
                                                        x: 0,
                                                        y: 0,
                                                        w: 135,
                                                        h: 5,
                                                        color: '#E8ECF2'
                                                    },
                                                    {
                                                        type: 'rect',
                                                        x: 0,
                                                        y: 0,
                                                        w: Math.max(2, 135 * (porcentaje / 100)),
                                                        h: 5,
                                                        color: '#0EA5A8'
                                                    }
                                                ]
                                            },
                                            {
                                                columns: [
                                                    {
                                                        text: porcentaje + '% usado',
                                                        fontSize: 6.8,
                                                        color: '#6B778C',
                                                        margin: [0, 3, 0, 0]
                                                    },
                                                    {
                                                        text: row.disponibles + ' disponibles',
                                                        fontSize: 6.8,
                                                        bold: true,
                                                        color: '#14804A',
                                                        alignment: 'right',
                                                        margin: [0, 3, 0, 0]
                                                    }
                                                ]
                                            }
                                        ]
                                    }
                                ]
                            },
                            {
                                table: {
                                    widths: ['33.33%', '33.33%', '33.34%'],
                                    body: [[
                                        {
                                            margin: [5, 5, 5, 5],
                                            stack: [secuenciaPdfDato('Activación', row.fecha_activacion)]
                                        },
                                        {
                                            margin: [5, 5, 5, 5],
                                            stack: [secuenciaPdfDato('Fecha límite', row.fecha_limite)]
                                        },
                                        {
                                            margin: [5, 5, 5, 5],
                                            stack: [secuenciaPdfDato('Vigencia', row.vigencia, {bold: true, color: vigenciaColor})]
                                        }
                                    ]]
                                },
                                layout: {
                                    fillColor: function () { return '#F7F9FC'; },
                                    hLineColor: function () { return '#E3E7ED'; },
                                    vLineColor: function () { return '#E3E7ED'; },
                                    hLineWidth: function () { return 0.5; },
                                    vLineWidth: function () { return 0.5; }
                                },
                                margin: [0, 9, 0, 0]
                            }
                        ]
                    }]]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return 0.7; },
                    vLineWidth: function () { return 0.7; }
                }
            };
        }


        function secuenciaPdfDetalle(rows) {
            var body = [[
                {text: 'Empresa', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Documento', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Estado', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'CAI', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Prefijo', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Siguiente', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Rango', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Disponibles', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
                {text: 'Vigencia', bold: true, color: '#FFFFFF', fillColor: '#17324D'}
            ]];

            rows.forEach(function (row, index) {
                var fill = index % 2 === 0 ? '#F7F9FC' : '#FFFFFF';
                body.push([
                    {text: row.empresa || 'No registrada', fillColor: fill},
                    {text: row.documento || 'Documento', fillColor: fill},
                    {text: row.estado || '', fillColor: fill, color: secuenciaPdfEstadoColor(row.estado), bold: true},
                    {text: row.cai || 'Sin CAI', fillColor: fill},
                    {text: row.prefijo || '—', fillColor: fill},
                    {text: String(row.siguiente || '0'), fillColor: fill, alignment: 'center', bold: true},
                    {text: String(row.rango_inicial || '') + ' - ' + String(row.rango_final || ''), fillColor: fill},
                    {text: String(row.disponibles || '0'), fillColor: fill, alignment: 'center', color: '#14804A', bold: true},
                    {text: row.vigencia || '—', fillColor: fill, color: secuenciaPdfVigenciaColor(row.vigencia), bold: true}
                ]);
            });

            return {
                table: {
                    headerRows: 1,
                    widths: [82, 74, 48, 112, 62, 46, 92, 52, '*'],
                    body: body
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return 0.6; },
                    vLineWidth: function () { return 0.6; },
                    paddingLeft: function () { return 4; },
                    paddingRight: function () { return 4; },
                    paddingTop: function () { return 5; },
                    paddingBottom: function () { return 5; }
                },
                fontSize: 6.8
            };
        }

        var tarjetas = [];
        for (var i = 0; i < rows.length; i += 2) {
            var columnas = [
                {
                    width: '*',
                    stack: [secuenciaPdfTarjeta(rows[i])]
                }
            ];

            if (rows[i + 1]) {
                columnas.push({width: 12, text: ''});
                columnas.push({
                    width: '*',
                    stack: [secuenciaPdfTarjeta(rows[i + 1])]
                });
            } else {
                columnas.push({width: 12, text: ''});
                columnas.push({width: '*', text: ''});
            }

            tarjetas.push({
                columns: columnas,
                columnGap: 0,
                margin: [0, i === 0 ? 0 : 10, 0, 0]
            });
        }

        var filtroEstado = $('#estado_secuencia_main option:selected').text() || 'Todos';
        var filtroDocumento = $('#documento_secuencia_main option:selected').text() || 'Todos';
        var filtroVencimiento = $('#vencimiento_secuencia_main option:selected').text() || 'Todos';
        var logoSecuencia = (typeof imagen !== 'undefined' && imagen)
            ? {image: imagen, width: 50, height: 24, alignment: 'center', margin: [0, 1, 0, 0]}
            : {text: 'IZZY', fontSize: 16, bold: true, color: '#FFFFFF', alignment: 'center', margin: [0, 4, 0, 0]};

        var encabezado = {
            table: {
                widths: [70, '*', 150],
                body: [[
                    {border:[false,false,false,false], fillColor:'#17324D', margin:[12,10,0,10], stack:[logoSecuencia]},
                    {border:[false,false,false,false], fillColor:'#17324D', margin:[0,10,0,10], stack:[
                        {text:'SECUENCIA DE FACTURACIÓN', fontSize:16, bold:true, color:'#FFFFFF'},
                        {text:'Control de documentos fiscales, correlativos y vigencia', fontSize:7.5, color:'#D8E5F0', margin:[0,2,0,0]}
                    ]},
                    {border:[false,false,false,false], fillColor:'#17324D', margin:[0,10,12,10], stack:[
                        {text:'REPORTE EJECUTIVO', fontSize:6.5, bold:true, color:'#72E2E5', alignment:'right'},
                        {text:secuenciaFechaReporte(), fontSize:9, bold:true, color:'#FFFFFF', alignment:'right', margin:[0,3,0,0]},
                        {text:rows.length + ' registro(s) filtrado(s)', fontSize:6.5, color:'#D8E5F0', alignment:'right', margin:[0,2,0,0]}
                    ]}
                ]]
            },
            layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},
            margin:[0,0,0,10]
        };

        var filtrosPdfSecuencia = {
            table:{
                widths:['*'],
                body:[[{text:'Filtros aplicados: Estado: ' + filtroEstado + '   |   Documento: ' + filtroDocumento + '   |   Vencimiento: ' + filtroVencimiento, fontSize:6.8, color:'#52627A', margin:[10,7,10,7], fillColor:'#F7F9FC'}]]
            },
            layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},
            margin:[0,0,0,10]
        };

        var resumen = {
            table: {
                widths: ['*', '*', '*', '*'],
                body: [[
                    {
                        margin: [9, 7, 9, 7],
                        stack: [
                            {text: 'REGISTROS', fontSize: 6.8, bold: true, color: '#6B778C'},
                            {text: String(rows.length), fontSize: 14, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                        ]
                    },
                    {
                        margin: [9, 7, 9, 7],
                        stack: [
                            {text: 'ACTIVAS', fontSize: 6.8, bold: true, color: '#6B778C'},
                            {text: String(totalActivas), fontSize: 14, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                        ]
                    },
                    {
                        margin: [9, 7, 9, 7],
                        stack: [
                            {text: 'CON CAI', fontSize: 6.8, bold: true, color: '#6B778C'},
                            {text: String(totalCai), fontSize: 14, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                        ]
                    },
                    {
                        margin: [9, 7, 9, 7],
                        stack: [
                            {text: 'DISPONIBLES', fontSize: 6.8, bold: true, color: '#6B778C'},
                            {text: String(totalDisponibles), fontSize: 14, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                        ]
                    }
                ]]
            },
            layout: {
                fillColor: function () { return '#F7F9FC'; },
                hLineColor: function () { return '#DDE3EA'; },
                vLineColor: function () { return '#DDE3EA'; },
                hLineWidth: function () { return 0.6; },
                vLineWidth: function () { return 0.6; }
            },
            margin: [0, 0, 0, 12]
        };

        var docDefinition = {
            pageSize: 'LETTER',
            pageOrientation: 'landscape',
            pageMargins: [34, 32, 34, 34],
            header: function () {
                return {
                    margin: [34, 14, 34, 0],
                    canvas: [
                        {
                            type: 'line',
                            x1: 0,
                            y1: 0,
                            x2: 724,
                            y2: 0,
                            lineWidth: 2,
                            lineColor: '#0EA5A8'
                        }
                    ]
                };
            },
            footer: function (currentPage, pageCount) {
                return {
                    margin: [34, 8, 34, 0],
                    columns: [
                        {
                            text: 'IZZY • Secuencia de Facturación',
                            fontSize: 7,
                            color: '#7A869A'
                        },
                        {
                            text: 'Página ' + currentPage + ' de ' + pageCount,
                            fontSize: 7,
                            color: '#7A869A',
                            alignment: 'right'
                        }
                    ]
                };
            },
            content: [
                encabezado,
                filtrosPdfSecuencia,
                resumen,
                {
                    text: secuenciaState.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE',
                    fontSize: 7,
                    bold: true,
                    color: '#17324D',
                    margin: [0, 0, 0, 7]
                }
            ].concat(
                secuenciaState.view === 'miniatura'
                    ? tarjetas
                    : [secuenciaPdfDetalle(rows)]
            ),
            defaultStyle: {
                fontSize: 8,
                color: '#253858'
            }
        };

        var pdfSecuencia = pdfMake.createPdf(docDefinition);
        var nombrePdfSecuencia = 'Reporte_Secuencia_Facturacion.pdf';

        if (typeof abrirModalPdfPublico !== 'function') {
            showNotify(
                'error',
                'Visor PDF no disponible',
                'No se encontró el modal PDF público. Verifique que vistasModals.php y main.js estén cargados.'
            );
            return;
        }

        if (typeof pdfSecuencia.getDataUrl === 'function') {
            pdfSecuencia.getDataUrl(function (dataUrl) {
                abrirModalPdfPublico(
                    dataUrl,
                    'Secuencia de Facturación',
                    nombrePdfSecuencia
                );
            });
            return;
        }

        if (typeof pdfSecuencia.getBase64 === 'function') {
            pdfSecuencia.getBase64(function (base64) {
                abrirModalPdfPublico(
                    'data:application/pdf;base64,' + base64,
                    'Secuencia de Facturación',
                    nombrePdfSecuencia
                );
            });
            return;
        }

        showNotify(
            'error',
            'PDF no disponible',
            'La versión actual de pdfMake no permite generar una vista previa compatible.'
        );
    }

    function descargarBlob(contenido, nombre, tipo, yaEsBlob) {
        var blob = yaEsBlob ? contenido : new Blob([contenido], {type: tipo});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = nombre;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }


    /* =========================================================
       SECUENCIA | DROPDOWN DE ACCIONES ADAPTATIVO
       Funciona en vista detalle y miniatura.
       ========================================================= */
    function limpiarDireccionDropdownSecuencia($dropdown) {
        if (!$dropdown || !$dropdown.length) {
            return;
        }

        $dropdown.removeClass('dropup dropright dropleft');
        $dropdown.children('.dropdown-menu')
            .removeClass('dropdown-menu-right')
            .removeAttr('x-placement data-popper-placement')
            .css({ top: '', left: '', right: '', bottom: '', transform: '' });
    }

    function medirMenuDropdownSecuencia($menu) {
        var menu = $menu && $menu.length ? $menu[0] : null;

        if (!menu) {
            return { width: 200, height: 130 };
        }

        var estilos = {
            display: menu.style.display,
            visibility: menu.style.visibility,
            position: menu.style.position,
            top: menu.style.top,
            left: menu.style.left,
            right: menu.style.right,
            bottom: menu.style.bottom,
            transform: menu.style.transform
        };
        var teniaShow = $menu.hasClass('show');

        $menu.addClass('show').css({
            display: 'block',
            visibility: 'hidden',
            position: 'fixed',
            top: '0',
            left: '0',
            right: 'auto',
            bottom: 'auto',
            transform: 'none'
        });

        var rect = menu.getBoundingClientRect();

        if (!teniaShow) {
            $menu.removeClass('show');
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
            width: Math.max(rect.width || 0, 200),
            height: Math.max(rect.height || 0, 1)
        };
    }

    function prepararDireccionDropdownSecuencia($dropdown) {
        if (!$dropdown || !$dropdown.length) {
            return;
        }

        var $button = $dropdown.children('.dropdown-toggle');
        var $menu = $dropdown.children('.dropdown-menu');
        var button = $button.length ? $button[0] : null;

        if (!button || !$menu.length) {
            return;
        }

        limpiarDireccionDropdownSecuencia($dropdown);

        var rect = button.getBoundingClientRect();
        var menuSize = medirMenuDropdownSecuencia($menu);
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        var margin = 12;
        var gap = 8;

        var abajo = viewportHeight - rect.bottom - margin;
        var arriba = rect.top - margin;
        var derecha = viewportWidth - rect.right - margin;
        var izquierda = rect.left - margin;

        if (abajo >= menuSize.height + gap) {
            // Posición normal: abajo.
        } else if (arriba >= menuSize.height + gap) {
            $dropdown.addClass('dropup');
        } else if (derecha >= menuSize.width + gap) {
            $dropdown.addClass('dropright');
        } else if (izquierda >= menuSize.width + gap) {
            $dropdown.addClass('dropleft');
        } else if (arriba > abajo) {
            $dropdown.addClass('dropup');
        }

        if (!$dropdown.hasClass('dropright') && !$dropdown.hasClass('dropleft')) {
            var desbordaDerecha = rect.left + menuSize.width > viewportWidth - margin;
            var puedeAlinearDerecha = rect.right - menuSize.width >= margin;

            if (desbordaDerecha && puedeAlinearDerecha) {
                $menu.addClass('dropdown-menu-right');
            }
        }
    }

    function cerrarDropdownsSecuenciaExcepto($actual) {
        $('#secuencia_listado .secuencia-actions').each(function () {
            var $dropdown = $(this);
            var $btn = $dropdown.children('.dropdown-toggle');
            var $menu = $dropdown.children('.dropdown-menu');

            if ($actual && $actual.length && $dropdown.is($actual)) {
                return;
            }

            try {
                if (typeof $.fn.dropdown === 'function' && $menu.hasClass('show')) {
                    $btn.dropdown('hide');
                }
            } catch (error) {
                // Respaldo manual abajo.
            }

            $btn.attr('aria-expanded', 'false');
            $dropdown.removeClass('show');
            $menu.removeClass('show');
            limpiarDireccionDropdownSecuencia($dropdown);
            $dropdown.closest('.secuencia-record-card, .secuencia-mini-card').removeClass('secuencia-dropdown-open');
        });
    }

    function inicializarDropdownAdaptativoSecuencia() {
        $('#secuencia_listado')
            .off('click.secuenciaDropdownAdaptativo', '.secuencia-actions .dropdown-toggle')
            .on('click.secuenciaDropdownAdaptativo', '.secuencia-actions .dropdown-toggle', function (event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                var $button = $(this);
                var $dropdown = $button.closest('.secuencia-actions');
                var $menu = $dropdown.children('.dropdown-menu');
                var estabaAbierto = $menu.hasClass('show');

                if (typeof $.fn.dropdown !== 'function') {
                    return;
                }

                cerrarDropdownsSecuenciaExcepto($dropdown);

                if (estabaAbierto) {
                    try {
                        $button.dropdown('hide');
                    } catch (error) {
                        $dropdown.removeClass('show');
                        $menu.removeClass('show');
                    }

                    $button.attr('aria-expanded', 'false');
                    limpiarDireccionDropdownSecuencia($dropdown);
                    $dropdown.closest('.secuencia-record-card, .secuencia-mini-card').removeClass('secuencia-dropdown-open');
                    return;
                }

                try {
                    prepararDireccionDropdownSecuencia($dropdown);

                    $button.dropdown({
                        boundary: 'viewport',
                        flip: true,
                        offset: '0,6'
                    });

                    $button.dropdown('show');
                    $dropdown.closest('.secuencia-record-card, .secuencia-mini-card').addClass('secuencia-dropdown-open');
                } catch (error) {
                    console.error('No se pudo abrir el dropdown de acciones de Secuencia:', error);
                }
            });

        $(document)
            .off('shown.bs.dropdown.secuenciaDropdownAdaptativo', '#secuencia_listado .secuencia-actions')
            .on('shown.bs.dropdown.secuenciaDropdownAdaptativo', '#secuencia_listado .secuencia-actions', function () {
                var $dropdown = $(this);
                cerrarDropdownsSecuenciaExcepto($dropdown);
                $dropdown.closest('.secuencia-record-card, .secuencia-mini-card').addClass('secuencia-dropdown-open');
            })
            .off('hidden.bs.dropdown.secuenciaDropdownAdaptativo', '#secuencia_listado .secuencia-actions')
            .on('hidden.bs.dropdown.secuenciaDropdownAdaptativo', '#secuencia_listado .secuencia-actions', function () {
                var $dropdown = $(this);
                limpiarDireccionDropdownSecuencia($dropdown);
                $dropdown.closest('.secuencia-record-card, .secuencia-mini-card').removeClass('secuencia-dropdown-open');
            });
    }

})(jQuery);
</script>
