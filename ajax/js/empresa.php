<script>
$(() => {
    listar_empresa();
    GetEstadoBotonFirma();

    $('#form_main_empresa #search').on("click", function (e) {
        e.preventDefault();
        listar_empresa();
    });

    $('#form_main_empresa').on('reset', function () {
        $(this).find('.selectpicker')
            .val('')
            .selectpicker('refresh');

        $('#filtro_empresa_general').val('');

        setTimeout(function () {
            listar_empresa();
        }, 100);
    });

    $(document).on('keyup', '#filtro_empresa_general', function () {
        if ($.fn.DataTable.isDataTable("#dataTableEmpresa")) {
            $("#dataTableEmpresa").DataTable().search($(this).val()).draw();
        }
    });

    const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';

    const cfgs = [
        { drop: '#logoDropArea',  input: '#logotipo',        preview: '#logoPreview',  info: '#logoInfo',  maxMB: 2 },
        { drop: '#firmaDropArea', input: '#firma_documento', preview: '#firmaPreview', info: '#firmaInfo', maxMB: 2 }
    ];

    let lastAreaCtx = null;

    cfgs.forEach(setupUploader);

    document.addEventListener('paste', function (e) {
        const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
        let file = null;

        for (let i = 0; i < items.length; i++) {
            if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                file = items[i].getAsFile();
                break;
            }
        }

        if (!file) return;

        e.preventDefault();

        const ctx = lastAreaCtx || getFirstAvailableCtx();

        if (!ctx) return;

        const dt = new DataTransfer();
        dt.items.add(file);

        handleFiles(dt.files, ctx);
    });

    function getFirstAvailableCtx() {
        for (const c of cfgs) {
            const drop = document.querySelector(c.drop);
            const input = document.querySelector(c.input);
            const preview = document.querySelector(c.preview);
            const info = document.querySelector(c.info);

            if (drop && input && preview && info) {
                return { drop, input, preview, info, maxMB: c.maxMB };
            }
        }

        return null;
    }

    function setupUploader({ drop, input, preview, info, maxMB }) {
        const dropArea = document.querySelector(drop);
        const fileInput = document.querySelector(input);
        const previewEl = document.querySelector(preview);
        const infoEl = document.querySelector(info);

        if (!dropArea || !fileInput || !previewEl || !infoEl) return;

        if (fileInput.dataset.initialized) return;

        fileInput.dataset.initialized = 'true';

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev =>
            dropArea.addEventListener(ev, preventDefaults, false)
        );

        ['dragenter', 'dragover'].forEach(ev =>
            dropArea.addEventListener(ev, () => dropArea.classList.add('drag-over'), false)
        );

        ['dragleave', 'drop'].forEach(ev =>
            dropArea.addEventListener(ev, () => dropArea.classList.remove('drag-over'), false)
        );

        dropArea.addEventListener('drop', e => {
            const files = e.dataTransfer?.files || [];

            if (files.length) {
                handleFiles(files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
            }
        });

        ['mouseenter', 'focusin'].forEach(ev => {
            dropArea.addEventListener(ev, () => {
                lastAreaCtx = { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB };
            });
        });

        fileInput.addEventListener('change', e => {
            handleFiles(e.target.files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
        });

        const chooseBtn = dropArea.querySelector('.btn-file-chooser');
        const selectLink = dropArea.querySelector('.select-file-text');

        const openChooser = (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        };

        if (chooseBtn) {
            chooseBtn.addEventListener('click', openChooser);
        }

        if (selectLink) {
            selectLink.addEventListener('click', openChooser);
            selectLink.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    openChooser(e);
                }
            });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    function handleFiles(fileList, ctx) {
        if (!ctx || !fileList || !fileList.length) return;

        const { input, preview, info, maxMB } = ctx;
        const file = fileList[0];

        if (!file.type.startsWith('image/')) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Error', 'El archivo debe ser una imagen (JPG, PNG, GIF)');
            } else {
                alert('El archivo debe ser una imagen (JPG, PNG, GIF)');
            }

            resetField(ctx);
            return;
        }

        if (file.size > maxMB * 1024 * 1024) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Error', 'La imagen no debe exceder ' + maxMB + 'MB');
            } else {
                alert('La imagen no debe exceder ' + maxMB + 'MB');
            }

            resetField(ctx);
            return;
        }

        info.textContent = `${file.name} (${formatFileSize(file.size)})`;

        const reader = new FileReader();

        reader.onload = function (e) {
            preview.innerHTML = '';

            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            img.className = 'img-thumbnail';
            img.style.maxWidth = '200px';
            img.style.maxHeight = '200px';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-image';
            removeBtn.title = 'Eliminar imagen';
            removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
            removeBtn.style.position = 'absolute';
            removeBtn.style.top = '5px';
            removeBtn.style.right = '5px';
            removeBtn.style.background = 'rgba(220,53,69,.95)';
            removeBtn.style.color = '#fff';
            removeBtn.style.border = 'none';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.width = '32px';
            removeBtn.style.height = '32px';
            removeBtn.style.display = 'flex';
            removeBtn.style.alignItems = 'center';
            removeBtn.style.justifyContent = 'center';
            removeBtn.style.boxShadow = '0 2px 6px rgba(0,0,0,.18)';

            removeBtn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                resetField(ctx);
            });

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);

            preview.appendChild(wrapper);
            preview.style.display = 'block';
        };

        reader.readAsDataURL(file);
    }

    function resetField(ctx) {
        const { input, preview, info } = ctx;

        input.value = '';
        preview.innerHTML = '';
        preview.style.display = 'none';
        info.textContent = 'Ningún archivo seleccionado';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
});

/* =========================================================
   HEADER DINÁMICO - EMPRESA
   ========================================================= */
function construirHeaderDataTableEmpresa() {
    var $tabla = $("#dataTableEmpresa");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Empresa</th>' +
                '<th>Contacto</th>' +
                '<th>Información fiscal</th>' +
                '<th>Ubicación</th>' +
                '<th>Digital / Horario</th>' +
                '<th>Firma</th>' +
            '</tr>' +
        '</thead>'
    );
}

/* =========================================================
   HELPERS EMPRESA
   ========================================================= */
function empresaValor(valor, textoDefault) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        return textoDefault || 'No registrado';
    }

    return String(valor).trim();
}

function empresaEscape(valor) {
    return empresaValor(valor, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function empresaTelefonoFormateado(valor) {
    valor = empresaValor(valor, '');

    if (valor === '') {
        return 'No registrado';
    }

    return valor;
}

function empresaFecha(valor) {
    valor = empresaValor(valor, '');

    if (valor === '') {
        return 'No registrada';
    }

    return valor;
}

function empresaUrlLimpia(url) {
    url = empresaValor(url, '');

    if (url === '') {
        return '';
    }

    return url;
}

function empresaTextoCorto(texto, limite) {
    texto = empresaValor(texto, '');

    if (texto === '') {
        return 'No registrado';
    }

    if (texto.length <= limite) {
        return texto;
    }

    return texto.substring(0, limite) + '...';
}

function empresaEstadoBadge(estado) {
    if (parseInt(estado) === 1) {
        return '' +
            '<span class="empresa-status-badge empresa-status-active">' +
                '<i class="fas fa-check-circle"></i> Activo' +
            '</span>';
    }

    return '' +
        '<span class="empresa-status-badge empresa-status-inactive">' +
            '<i class="fas fa-times-circle"></i> Inactivo' +
        '</span>';
}

function actualizarResumenEmpresa(rows) {
    rows = rows || [];

    var totalActivas = 0;
    var totalContacto = 0;
    var totalWeb = 0;
    var totalFirma = 0;

    rows.forEach(function (item) {
        if (parseInt(item.estado) === 1) {
            totalActivas++;
        }

        if (
            empresaValor(item.telefono, '') !== '' ||
            empresaValor(item.celular, '') !== '' ||
            empresaValor(item.correo, '') !== ''
        ) {
            totalContacto++;
        }

        if (
            empresaValor(item.facebook, '') !== '' ||
            empresaValor(item.sitioweb, '') !== ''
        ) {
            totalWeb++;
        }

        if (parseInt(item.MostrarFirma) === 1) {
            totalFirma++;
        }
    });

    $('#empresa_total_activas').text(totalActivas);
    $('#empresa_total_contacto').text(totalContacto);
    $('#empresa_total_web').text(totalWeb);
    $('#empresa_total_firma').text(totalFirma);
}

/* =========================================================
   LISTAR EMPRESA
   ========================================================= */
var listar_empresa = function () {
    var estado = $('#form_main_empresa #estado_empresa').val();
    var ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';

    if ($.fn.DataTable.isDataTable("#dataTableEmpresa")) {
        $("#dataTableEmpresa").DataTable().clear().destroy();
    }

    construirHeaderDataTableEmpresa();

    var table_empresa = $("#dataTableEmpresa").DataTable({
        "destroy": true,
        "autoWidth": false,
        "scrollX": false,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableEmpresa.php",
            "data": {
                "estado": estado
            },
            "dataSrc": function (json) {
                var rows = [];

                if (json && json.data) {
                    rows = json.data;
                }

                actualizarResumenEmpresa(rows);

                return rows;
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle empresa-acciones-cell",
                "render": function (data, type, row) {
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
                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +
                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
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
                "data": null,
                "className": "align-middle empresa-info-cell",
                "render": function (data, type, row) {
                    var defaultLogoUrl = ENTERPRISE_URL + 'image_preview.png';
                    var imageUrl = row.image ? (ENTERPRISE_URL + row.image) : defaultLogoUrl;

                    var nombre = empresaEscape(row.nombre);
                    var razonSocial = empresaEscape(row.razon_social);
                    var eslogan = empresaEscape(row.eslogan);
                    var otraInfo = empresaEscape(row.otra_informacion);
                    var estadoBadge = empresaEstadoBadge(row.estado);

                    if (type !== "display") {
                        return nombre + ' ' + razonSocial + ' ' + eslogan + ' ' + otraInfo + ' ' + (parseInt(row.estado) === 1 ? 'Activo' : 'Inactivo');
                    }

                    return '' +
                        '<div class="empresa-main-box">' +
                            '<div class="empresa-logo-box">' +
                                '<a href="#" class="iv-trigger empresa-zoom-trigger" ' +
                                    'data-iv-src="' + imageUrl + '" ' +
                                    'data-iv-fallback="' + defaultLogoUrl + '" ' +
                                    'data-iv-title="' + nombre + '">' +
                                    '<img class="empresa-logo-img table-image" src="' + imageUrl + '" alt="' + nombre + '">' +
                                '</a>' +
                            '</div>' +
                            '<div class="empresa-main-info">' +
                                '<div class="empresa-title-row">' +
                                    '<h6 class="empresa-nombre">' + nombre + '</h6>' +
                                    estadoBadge +
                                '</div>' +
                                '<div class="empresa-razon">' + razonSocial + '</div>' +
                                '<div class="empresa-eslogan">' +
                                    '<i class="fas fa-quote-left mr-1"></i>' + empresaValor(eslogan, 'Sin eslogan registrado') +
                                '</div>' +
                                '<div class="empresa-extra-info">' + empresaValor(otraInfo, 'Sin información adicional') + '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle empresa-contacto-cell",
                "render": function (data, type, row) {
                    var telefono = empresaEscape(empresaTelefonoFormateado(row.telefono));
                    var celular = empresaEscape(empresaTelefonoFormateado(row.celular));
                    var correo = empresaEscape(empresaValor(row.correo, 'No registrado'));

                    if (type !== "display") {
                        return telefono + ' ' + celular + ' ' + correo;
                    }

                    return '' +
                        '<div class="empresa-detail-list">' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon"><i class="fas fa-phone-alt"></i></span>' +
                                '<span><strong>Teléfono:</strong> ' + telefono + '</span>' +
                            '</div>' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon"><i class="fas fa-mobile-alt"></i></span>' +
                                '<span><strong>Celular:</strong> ' + celular + '</span>' +
                            '</div>' +
                            '<div class="empresa-detail-item empresa-email-item">' +
                                '<span class="empresa-detail-icon"><i class="fas fa-envelope"></i></span>' +
                                '<span><strong>Correo:</strong> ' + correo + '</span>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle empresa-fiscal-cell",
                "render": function (data, type, row) {
                    var rtn = empresaEscape(empresaValor(row.rtn, 'No registrado'));
                    var fecha = empresaEscape(empresaFecha(row.fecha_registro));

                    if (type !== "display") {
                        return rtn + ' ' + fecha;
                    }

                    return '' +
                        '<div class="empresa-detail-list">' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon empresa-icon-fiscal"><i class="fas fa-id-card"></i></span>' +
                                '<span><strong>RTN:</strong> ' + rtn + '</span>' +
                            '</div>' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon empresa-icon-fiscal"><i class="fas fa-calendar-alt"></i></span>' +
                                '<span><strong>Registro:</strong> ' + fecha + '</span>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": "ubicacion",
                "className": "align-middle empresa-ubicacion-cell",
                "render": function (data, type, row) {
                    var ubicacion = empresaEscape(empresaValor(data, 'No registrada'));

                    if (type !== "display") {
                        return ubicacion;
                    }

                    return '' +
                        '<div class="empresa-location-box">' +
                            '<span class="empresa-location-icon"><i class="fas fa-map-marker-alt"></i></span>' +
                            '<span>' + ubicacion + '</span>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "align-middle empresa-digital-cell",
                "render": function (data, type, row) {
                    var facebook = empresaUrlLimpia(row.facebook);
                    var sitioweb = empresaUrlLimpia(row.sitioweb);
                    var horario = empresaEscape(empresaValor(row.horario, 'No registrado'));

                    if (type !== "display") {
                        return facebook + ' ' + sitioweb + ' ' + horario;
                    }

                    return '' +
                        '<div class="empresa-detail-list">' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon empresa-icon-web"><i class="fab fa-facebook-f"></i></span>' +
                                '<span><strong>Facebook:</strong> ' +
                                    (
                                        facebook !== ''
                                            ? '<a href="' + empresaEscape(facebook) + '" target="_blank">Ver página</a>'
                                            : 'No registrado'
                                    ) +
                                '</span>' +
                            '</div>' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon empresa-icon-web"><i class="fas fa-globe"></i></span>' +
                                '<span><strong>Sitio web:</strong> ' +
                                    (
                                        sitioweb !== ''
                                            ? '<a href="' + empresaEscape(sitioweb) + '" target="_blank">Abrir sitio</a>'
                                            : 'No registrado'
                                    ) +
                                '</span>' +
                            '</div>' +
                            '<div class="empresa-detail-item">' +
                                '<span class="empresa-detail-icon empresa-icon-web"><i class="fas fa-clock"></i></span>' +
                                '<span><strong>Horario:</strong> ' + horario + '</span>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": null,
                "className": "text-center align-middle empresa-firma-cell",
                "render": function (data, type, row) {
                    var defaultLogoUrl = ENTERPRISE_URL + 'image_preview.png';
                    var firmaUrl = row.firma_documento ? (ENTERPRISE_URL + row.firma_documento) : defaultLogoUrl;
                    var mostrarFirma = parseInt(row.MostrarFirma) === 1;

                    if (type !== "display") {
                        return mostrarFirma ? 'Firma visible' : 'Firma oculta';
                    }

                    return '' +
                        '<div class="empresa-firma-box">' +
                            '<a href="#" class="iv-trigger empresa-zoom-trigger" ' +
                                'data-iv-src="' + firmaUrl + '" ' +
                                'data-iv-fallback="' + defaultLogoUrl + '" ' +
                                'data-iv-title="Firma / Sello">' +
                                '<img class="empresa-firma-img" src="' + firmaUrl + '" alt="Firma">' +
                            '</a>' +
                            '<div class="mt-2">' +
                                '<span class="empresa-firma-badge ' + (mostrarFirma ? 'firma-visible' : 'firma-oculta') + '">' +
                                    '<i class="fas ' + (mostrarFirma ? 'fa-eye' : 'fa-eye-slash') + ' mr-1"></i>' +
                                    (mostrarFirma ? 'Visible' : 'Oculta') +
                                '</span>' +
                            '</div>' +
                        '</div>';
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "order": [[1, "asc"]],
        "columnDefs": [
            {
                width: "8%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle empresa-acciones-cell"
            },
            {
                width: "25%",
                targets: 1,
                className: "align-middle empresa-info-cell"
            },
            {
                width: "15%",
                targets: 2,
                className: "align-middle empresa-contacto-cell"
            },
            {
                width: "14%",
                targets: 3,
                className: "align-middle empresa-fiscal-cell"
            },
            {
                width: "18%",
                targets: 4,
                className: "align-middle empresa-ubicacion-cell"
            },
            {
                width: "15%",
                targets: 5,
                className: "align-middle empresa-digital-cell"
            },
            {
                width: "5%",
                targets: 6,
                orderable: false,
                searchable: false,
                className: "text-center align-middle empresa-firma-cell"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Empresa',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function () {
                    listar_empresa();
                }
            },
            {
                text: '<i class="fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Empresa',
                className: 'table_crear btn btn-primary ocultar',
                action: function () {
                    modal_empresa();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Empresa',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Empresa',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                },
                customize: function (doc) {
                    if (typeof imagen !== 'undefined' && imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],
        "drawCallback": function (settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    var filtroGeneral = $('#filtro_empresa_general').val();

    if (filtroGeneral !== '') {
        table_empresa.search(filtroGeneral).draw();
    }

    $('#filtro_empresa_general').focus();

    editar_empresa_dataTable("#dataTableEmpresa tbody", table_empresa);
    eliminar_empresa_dataTable("#dataTableEmpresa tbody", table_empresa);
};

var editar_empresa_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_editar");

    $(tbody).on("click", "button.table_editar", function () {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarEmpresa.php';

        $('#formEmpresa #empresa_id').val(data.empresa_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formEmpresa').serialize(),
            success: function (registro) {
                var valores = eval(registro);

                $('#formEmpresa').attr({
                    'data-form': 'update'
                });

                $('#formEmpresa').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarEmpreasAjax.php'
                });

                $('#formEmpresa')[0].reset();

                $('#reg_empresa').hide();
                $('#edi_empresa').show();
                $('#delete_empresa').hide();

                $('#formEmpresa #empresa_empresa').val(valores[0]);
                $('#formEmpresa #telefono_empresa').val(valores[1]);
                $('#formEmpresa #correo_empresa').val(valores[2]);
                $('#formEmpresa #rtn_empresa').val(valores[3]);
                $('#formEmpresa #direccion_empresa').val(valores[4]);
                $('#formEmpresa #empresa_razon_social').val(valores[6]);
                $('#formEmpresa #empresa_otra_informacion').val(valores[7]);
                $('#formEmpresa #empresa_eslogan').val(valores[8]);
                $('#formEmpresa #empresa_celular').val(valores[9]);
                $('#formEmpresa #facebook_empresa').val(valores[10]);
                $('#formEmpresa #sitioweb_empresa').val(valores[11]);
                $('#formEmpresa #horario_empresa').val(valores[12]);

                if (valores[5] == 1) {
                    $('#formEmpresa #empresa_activo').attr('checked', true);
                } else {
                    $('#formEmpresa #empresa_activo').attr('checked', false);
                }

                if (valores[13] && valores[13] !== 'image_preview.png') {
                    cargarImagenExistente('logo', valores[13]);
                } else {
                    $('#logoPreview').html('').hide();
                    $('#logoInfo').text('Ningún archivo seleccionado');
                }

                if (valores[14] && valores[14] !== '') {
                    cargarImagenExistente('firma', valores[14]);
                } else {
                    $('#firmaPreview').html('').hide();
                    $('#firmaInfo').text('Ningún archivo seleccionado');
                }

                $('#formEmpresa #empresa_empresa').attr('readonly', false);
                $('#formEmpresa #rtn_empresa').attr('readonly', false);
                $('#formEmpresa #telefono_empresa').attr('readonly', false);
                $('#formEmpresa #correo_empresa').attr('readonly', false);
                $('#formEmpresa #direccion_empresa').attr('readonly', false);
                $('#formEmpresa #empresa_activo').attr('disabled', false);
                $('#formEmpresa #empresa_razon_social').attr('readonly', false);
                $('#formEmpresa #empresa_otra_informacion').attr('readonly', false);
                $('#formEmpresa #empresa_eslogan').attr('disabled', false);
                $('#formEmpresa #empresa_celular').attr('disabled', false);

                $('#formEmpresa #proceso_empresa').val("Editar");

                $('#modal_registrar_empresa').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var eliminar_empresa_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_eliminar");

    $(tbody).on("click", "button.table_eliminar", function () {
        var data = table.row($(this).parents("tr")).data();

        var empresa_id = data.empresa_id;
        var nombreEmpresa = data.nombre;

        var mensajeHTML = `¿Desea eliminar permanentemente la empresa?<br><br>
                           <strong>Nombre:</strong> ${nombreEmpresa}`;

        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
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
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (!confirmar) return;

            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>ajax/eliminarEmpresaAjax.php',
                data: {
                    empresa_id: empresa_id
                },
                dataType: 'json',
                beforeSend: function () {
                    if (typeof showLoading === 'function') {
                        showLoading("Eliminando registro...");
                    }
                },
                success: function (response) {
                    swal.close();

                    if (response && response.status === "success") {
                        if (typeof showNotify === 'function') {
                            showNotify("success", response.title || "Eliminación exitosa", response.message || "Empresa eliminada correctamente");
                        }

                        table.ajax.reload(null, false);
                        table.search('').draw();
                    } else {
                        if (typeof showNotify === 'function') {
                            showNotify("error", (response && response.title) || "Error", (response && response.message) || "No se pudo eliminar la empresa");
                        }
                    }
                },
                error: function (xhr) {
                    swal.close();

                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : "Ocurrió un error al procesar la solicitud";

                    if (typeof showNotify === 'function') {
                        showNotify("error", "Error", msg);
                    }
                }
            });
        });
    });
};

/* =========================================================
   MODAL EMPRESA
   ========================================================= */
function modal_empresa() {
    $('#formEmpresa').attr({
        'data-form': 'save'
    });

    $('#formEmpresa').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarEmpresaAjax.php'
    });

    $('#formEmpresa')[0].reset();

    $('#reg_empresa').show();
    $('#edi_empresa').hide();
    $('#delete_empresa').hide();

    CleanEnterpriseImage();

    $('#formEmpresa #empresa_empresa').attr('readonly', false);
    $('#formEmpresa #rtn_empresa').attr('readonly', false);
    $('#formEmpresa #telefono_empresa').attr('readonly', false);
    $('#formEmpresa #correo_empresa').attr('readonly', false);
    $('#formEmpresa #direccion_empresa').attr('readonly', false);
    $('#formEmpresa #empresa_activo').attr('disabled', false);
    $('#formEmpresa #empresa_razon_social').attr('readonly', false);
    $('#formEmpresa #empresa_otra_informacion').attr('readonly', false);
    $('#formEmpresa #empresa_eslogan').attr('disabled', false);
    $('#formEmpresa #empresa_celular').attr('disabled', false);

    $('#formEmpresa #proceso_empresa').val("Registro");

    $('#modal_registrar_empresa').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cargarImagenExistente(tipo, rutaImagen) {
    const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';

    const preview = tipo === 'logo' ? $('#logoPreview') : $('#firmaPreview');
    const info = tipo === 'logo' ? $('#logoInfo') : $('#firmaInfo');
    const input = tipo === 'logo' ? $('#logotipo') : $('#firma_documento');

    if (rutaImagen && rutaImagen !== 'image_preview.png' && rutaImagen !== '') {
        const rutaCompleta = ENTERPRISE_URL + rutaImagen;

        preview.html(`
            <div style="position: relative; display: inline-block;">
                <img src="${rutaCompleta}" alt="Imagen existente" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                <button type="button" class="btn-remove-image" title="Eliminar imagen" style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.7); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).show();

        info.text(rutaImagen);

        preview.find('.btn-remove-image').on('click', function (e) {
            e.stopPropagation();
            preview.html('').hide();
            info.text('Ningún archivo seleccionado');
            input.val('');
        });
    } else {
        preview.html('').hide();
        info.text('Ningún archivo seleccionado');
    }
}

$(document).ready(function () {
    $("#modal_registrar_empresa").on('shown.bs.modal', function () {
        $(this).find('#formEmpresa #empresa_razon_social').focus();
    });
});

$('#formEmpresa #label_empresa_activo').html("Activo");

$('#formEmpresa .switch').change(function () {
    if ($('input[name=empresa_activo]').is(':checked')) {
        $('#formEmpresa #label_empresa_activo').html("Activo");
        return true;
    } else {
        $('#formEmpresa #label_empresa_activo').html("Inactivo");
        return false;
    }
});

$('#toggle-firma').on('click', function (e) {
    e.preventDefault();

    const $toggleButton = $(this);
    const estado = $toggleButton.text().includes('Ocultar Firma') ? 0 : 1;

    $.ajax({
        url: '<?php echo SERVERURL;?>core/SaveEstadoFirma.php',
        type: 'POST',
        data: {
            estado: estado
        },
        success: function (response) {
            try {
                const jsonResponse = JSON.parse(response);

                showNotify(jsonResponse.type, jsonResponse.title, jsonResponse.text);

                GetEstadoBotonFirma();
            } catch (error) {
                console.error('Error al analizar la respuesta JSON:', error);
            }
        },
        error: function () {
            $('.RespuestaAjax').html(
                '<p class="text-center text-danger">Hubo un problema al procesar la solicitud. Por favor, inténtelo de nuevo.</p>'
            );
        }
    });
});

function GetEstadoBotonFirma() {
    $.ajax({
        url: '<?php echo SERVERURL;?>core/GetEstadoBotonFirma.php',
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                console.error('Error al obtener el estado de la firma:', response.error);
                return;
            }

            const isFirmaVisible = response.estado === 'visible';
            const $toggleButton = $('#toggle-firma');

            if (isFirmaVisible) {
                $toggleButton.html('<i class="fas fa-eye-slash"></i> Ocultar Firma');
            } else {
                $toggleButton.html('<i class="fas fa-eye"></i> Mostrar Firma');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error al obtener el estado de la firma:', error);
        }
    });
}
</script>