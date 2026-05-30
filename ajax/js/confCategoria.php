<script>
$(document).ready(function() {
    listar_categoria_productos();

	$('#form_main_categorias #search').on("click", function (e) {
            e.preventDefault();
            listar_categoria_productos();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_categorias').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('.selectpicker') // Usa `this` para referenciar el formulario actual
			.val('')
			.selectpicker('refresh');

			listar_categoria_productos();
	});    
});

/* =========================================================
   HEADER DINÁMICO - CATEGORÍA PRODUCTOS
   ========================================================= */
   function construirHeaderDataTableConfCategorias() {
    var $tabla = $("#dataTableConfCategorias");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Categoría</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO CONF CATEGORIAS
var listar_categoria_productos = function() {
    var estado = $('#form_main_categorias #estado_categorias').val();

    if ($.fn.DataTable.isDataTable("#dataTableConfCategorias")) {
        $("#dataTableConfCategorias").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfCategorias();

    var table_categoria_productos = $("#dataTableConfCategorias").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableCategoriaProductos.php",
            "data": {
                "estado": estado
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
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
                "data": "nombre"
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';
                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ?
                            'badge badge-pill badge-success' :
                            'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }

                    return data;
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "12%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "73%",
                targets: 1
            },
            {
                width: "15%",
                targets: 2,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Categoria Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_categoria_productos();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Categoria Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalCategoriaProductos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Categoria Productos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Categoria Productos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1]
                },
                customize: function(doc) {
                    if (imagen) {
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_categoria_productos.search('').draw();
    $('#buscar').focus();

    edit_categorias_productos_dataTable("#dataTableConfCategorias tbody", table_categoria_productos);
    delete_categorias_productos_dataTable("#dataTableConfCategorias tbody", table_categoria_productos);
}

var edit_categorias_productos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarCategoriaProductos.php';
        $('#formCategoriaProductos #categoria_id').val(data.categoria_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formCategoriaProductos').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formCategoriaProductos').attr({
                    'data-form': 'update'
                });
                $('#formCategoriaProductos').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarCategoriaProductosAjax.php'
                });
                $('#formCategoriaProductos')[0].reset();
                $('#reg_catProd').hide();
                $('#edi_catProd').show();
                $('#delete_catProd').hide();
                $('#formCategoriaProductos #pro_categoria_productos').val("Editar");
                $('#formCategoriaProductos #categoria_productos').val(valores[1]);

                if (valores[2] == 1) {
                    $('#formCategoriaProductos #categoria_producto_activo').attr('checked',
                        true);
                } else {
                    $('#formCategoriaProductos #categoria_producto_activo').attr('checked',
                        false);
                }

                //HABILITAR OBJETOS
                $('#formCategoriaProductos #categoria_productos').attr('readonly', false);
                $('#formCategoriaProductos #categoria_producto_activo').attr('disabled', false);
                $('#formCategoriaProductos #estado_categoria_productos').show();

                $('#modalcategoria_productos').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var delete_categorias_productos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var categoria_id = data.categoria_id;
        var nombreCategoria = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la categoría de producto?<br><br>
                        <strong>Nombre:</strong> ${nombreCategoria}`;
        
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
            if (confirmar) {
               
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarCategoriaProductosAjax.php',
                    data: {
                        categoria_id: categoria_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    before: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false); // Recargar tabla sin resetear paginación
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
    });
}
//FIN CONF CATEGORIAS

//INICIO FORMULARIO CATEGORIA PRODUCTOS
function modalCategoriaProductos() {
    $('#formCategoriaProductos').attr({
        'data-form': 'save'
    });
    $('#formCategoriaProductos').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addCategoriaProductosAjax.php'
    });
    $('#formCategoriaProductos')[0].reset();
    $('#formCategoriaProductos #pro_categoria_productos').val("Registro");
    $('#reg_catProd').show();
    $('#edi_catProd').hide();
    $('#delete_catProd').hide();

    //HABILITAR OBJETOS
    $('#formCategoriaProductos #categoria_productos').attr('readonly', false);
    $('#formCategoriaProductos #categoria_producto_activo').attr('disabled', false);
    $('#formCategoriaProductos #estado_categoria_productos').hide();

    $('#modalcategoria_productos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN FORMULARIO CATEGORIA PRODUCTOS

$(document).ready(function() {
    $("#modalcategoria_productos").on('shown.bs.modal', function() {
        $(this).find('#formCategoriaProductos #categoria_productos').focus();
    });
});

$('#formCategoriaProductos #label_categoria_producto_activo').html("Activo");

$('#formCategoriaProductos .switch').change(function() {
    if ($('input[name=categoria_producto_activo]').is(':checked')) {
        $('#formCategoriaProductos #label_categoria_producto_activo').html("Activo");
        return true;
    } else {
        $('#formCategoriaProductos #label_categoria_producto_activo').html("Inactivo");
        return false;
    }
});
</script>