<script>
$(document).ready(function() {
    listar_privilegio(); 
});

//INICIO ACCIONES FROMULARIO PRIVILEGIOS
var listar_privilegio = function(){
	var table_privilegio  = $("#dataTablePrivilegio").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTablePrivilegio.php"
		},
		"columns":[
			{
				"data":"nombre"
			},
			{
				data: null,
				render: (data, type, row) => {
					const count = row.menus_asignados || 0;
					return `
						<button class="btn btn-sm btn-dark table_accesos menu" 
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
						<button class="btn btn-sm btn-dark table_accesos submenu" 
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
					const count = row.submenus1_asignados || 0;
					return `
						<button class="btn btn-sm btn-dark table_accesos submenu1" 
								data-plan-id="${row.planes_id}" 
								data-plan-nombre="${row.nombre}">
							<i class="fas fa-link"></i> Asignar
						</button>
						<div class="mt-1 small" id="contador-submenus1-${row.planes_id}">${count} asignados</div>
					`;
				}
			},
			{
				"defaultContent":"<button class='table_editar btn btn-dark'><span class='fas fa-edit fa-lg'></span>Editar</button>"

			},
			{
				"defaultContent":"<button class='table_eliminar1 table_eliminar btn btn-dark'><span class='fa fa-trash fa-lg'></span>Eliminar</button>"
			}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
		"columnDefs": [
		  { width: "89.33%", targets: 0 },
		  { width: "5.33%", targets: 1 },
		  { width: "5.33%", targets: 2 }
		],		
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Privilegios',
				className: 'btn btn-secondary',
				action: 	function(){
					listar_privilegio();
				}
			},
			{
				text:      '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
				titleAttr: 'Agregar Privilegios',
				className: 'btn btn-primary',
				action: 	function(){
					modal_privilegios();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Privilegios',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'btn btn-success',
				exportOptions: {
						columns: [0]
				},
			},
			{
				extend:    'pdf',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
				title: 'Reporte Privilegios',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'btn btn-danger',
				exportOptions: {
						columns: [0]
				},
                customize: function(doc) {
                    if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
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
		"drawCallback": function( settings ) {
        	getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    	}
	});
	table_privilegio.search('').draw();
	$('#buscar').focus();

	accesos_privilegio_menu_dataTable("#dataTablePrivilegio tbody", table_privilegio);
	accesos_privilegio_submenu_dataTable("#dataTablePrivilegio tbody", table_privilegio);
	accesos_privilegio_submenu1_dataTable("#dataTablePrivilegio tbody", table_privilegio);
	editar_privilegio_dataTable("#dataTablePrivilegio tbody", table_privilegio);
	eliminar_privilegio_dataTable("#dataTablePrivilegio tbody", table_privilegio);
}

var accesos_privilegio_menu_dataTable = function(tbody, table){
	$(tbody).off("click", "button.menu");
	$(tbody).on("click", "button.menu", function(){
		var data = table.row( $(this).parents("tr") ).data();		
		$('#formMenuAccesos #privilegio_id_accesos').val(data.privilegio_id);
		$('#modal_registrar_menuaccesos .modal-title').text(`Privilegios - Menús: ${data.nombre}`);
		listar_menuaccesos();

		$('#formMenuAccesos').attr({ 'data-form': 'save' });
		$('#formMenuAccesos').attr({ 'action': '<?php echo SERVERURL;?>ajax/addMenuAccesosAjax.php' });

		$('#modal_registrar_menuaccesos').modal({
			show:true,
			keyboard: false,
			backdrop:'static'
		});	
	});
}

var accesos_privilegio_submenu_dataTable = function(tbody, table){
	$(tbody).off("click", "button.submenu");
	$(tbody).on("click", "button.submenu", function(){
		var data = table.row( $(this).parents("tr") ).data();
		$('#formSubMenuAccesos #privilegio_id_accesos').val(data.privilegio_id);
		$('#modal_registrar_submenuaccesos  .modal-title').text(`Privilegios - Submenus de nivel 1: ${data.nombre}`);
			
		listar_submenuaccesos();

		$('#formSubMenuAccesos').attr({ 'data-form': 'save' });
		$('#formSubMenuAccesos').attr({ 'action': '<?php echo SERVERURL;?>ajax/addSubMenuAccesosAjax.php' });

		$('#modal_registrar_submenuaccesos').modal({
			show:true,
			keyboard: false,
			backdrop:'static'
		});	
	});
}

var accesos_privilegio_submenu1_dataTable = function(tbody, table){
	$(tbody).off("click", "button.submenu1");
	$(tbody).on("click", "button.submenu1", function(){
		var data = table.row( $(this).parents("tr") ).data();
		$('#formSubMenu1Accesos #privilegio_id_accesos').val(data.privilegio_id);
		$('#modal_registrar_submenu1accesos  .modal-title').text(`Privilegios - Menús: ${data.nombre}`);		
		listar_submenu1accesos();

		$('#formSubMenu1Accesos').attr({ 'data-form': 'save' });
		$('#formSubMenu1Accesos').attr({ 'action': '<?php echo SERVERURL;?>ajax/addSubMenu1AccesosAjax.php' });

		$('#modal_registrar_submenu1accesos').modal({
			show:true,
			keyboard: false,
			backdrop:'static'
		});	
	});
}

var listar_menuaccesos = function(){
	var privilegio_id_accesos = $("#formMenuAccesos #privilegio_id_accesos").val();

	var table_menuaccesos = $("#dataTableMenuAccesos").DataTable({
		destroy: true,
		ajax: {
			method: "POST",
			url: "<?php echo SERVERURL;?>core/llenarDataTableMenuAccesos.php",
			data: { 
				privilegio_id_accesos: privilegio_id_accesos 
			},
			dataSrc: function(json) {
				let contador = 0;
				json.data.forEach(d => { if(d.asignado) contador++; });
				$(`#contador-menuaccesos-${privilegio_id_accesos}`).text(`${contador} asignados`);

				// Verifica si la tabla está vacía
				if (json.data.length === 0) {
					showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');

					return; // Detener el proceso, no cargar la tabla
				}

				return json.data.map((menu, index) => ({
					"#": index + 1,
					"menu": menu.descripcion,
					"asignado": menu.asignado 
						? '<span class="badge badge-success">Asignado</span>' 
						: '<span class="badge badge-secondary">No asignado</span>',
					"acciones": `<button class="btn btn-sm ${menu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-menuacceso"
						data-menu-id="${menu.menu_id}" data-asignado="${menu.asignado}">
						${menu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
					</button>`
				}));
			}
		},
		columns: [
			{ data: "#" },
			{ data: "menu" },
			{ data: "asignado" },
			{ data: "acciones" }
		],
		lengthMenu: lengthMenu20,
		stateSave: true,
		language: idioma_español,
		dom: dom,
		buttons: []
	});
};

$(document).ready(function(){
	$("#modal_registrar_menuaccesos").on('shown.bs.modal', function(){
		$(this).find('#formMenuAccesos #buscar').focus();
	});
});	
/*FIN MENU ACCESOS*/

/*INCIO SUBMENU ACCESOS*/
var listar_submenuaccesos = function(){
	var privilegio_id_accesos = $("#formSubMenuAccesos #privilegio_id_accesos").val();

	var table_submenuaccesos = $("#dataTableSubMenuAccesos").DataTable({
		destroy: true,
		ajax: {
			method: "POST",
			url: "<?php echo SERVERURL;?>core/llenarDataTableSubMenuAccesos.php",
			data: { privilegio_id_accesos: privilegio_id_accesos },
			dataSrc: function(json) {
				let contador = 0;
				json.data.forEach(d => { if(d.asignado) contador++; });
				$(`#contador-submenuaccesos-${privilegio_id_accesos}`).text(`${contador} asignados`);

				// Verifica si la tabla está vacía
				if (json.data.length === 0) {
					showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');

					return; // Detener el proceso, no cargar la tabla
				}

				return json.data.map((submenu, index) => ({
					"#": index + 1,
					"menu": submenu.descripcion_padre,
					"submenu": submenu.descripcion,
					"asignado": submenu.asignado 
						? '<span class="badge badge-success">Asignado</span>' 
						: '<span class="badge badge-secondary">No asignado</span>',
					"acciones": `<button class="btn btn-sm ${submenu.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenuacceso"
						data-submenu-id="${submenu.submenu_id}" data-asignado="${submenu.asignado}">
						${submenu.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
					</button>`
				}));
			}
		},
		columns: [
			{ data: "#" },
			{ data: "menu" },
			{ data: "submenu" },
			{ data: "asignado" },
			{ data: "acciones" }
		],
		lengthMenu: lengthMenu20,
		stateSave: true,
		language: idioma_español,
		dom: dom,
		buttons: []
	});
};

$(document).ready(function(){
	$("#modal_registrar_submenuaccesos").on('shown.bs.modal', function(){
		$(this).find('#formSubMenuAccesos #buscar').focus();
	});
});	
/*FIN SUBMENU ACCESOS*/

/*INCIO SUBMENU1 ACCESOS*/
var listar_submenu1accesos = function(){
	var privilegio_id_accesos = $("#formSubMenu1Accesos #privilegio_id_accesos").val();

	var table_submenu1accesos = $("#dataTableSubMenu1Accesos").DataTable({
		destroy: true,
		ajax: {
			method: "POST",
			url: "<?php echo SERVERURL;?>core/llenarDataTableSubMenu1Accesos.php",
			data: { privilegio_id_accesos: privilegio_id_accesos },
			dataSrc: function(json) {
				console.log(json);
				let contador = 0;
				json.data.forEach(d => { if(d.asignado) contador++; });
				$(`#contador-submenu1accesos-${privilegio_id_accesos}`).text(`${contador} asignados`);

				// Verifica si la tabla está vacía
				if (json.data.length === 0) {
					showNotify('warning', 'Sin Plan Asignado', 'No tiene un plan asignado');

					return; // Detener el proceso, no cargar la tabla
				}

				return json.data.map((s1, index) => ({
					"#": index + 1,
					"submenu": s1.descripcion,
					"submenu1": s1.submenu_descripcion,
					"asignado": s1.asignado 
						? '<span class="badge badge-success">Asignado</span>' 
						: '<span class="badge badge-secondary">No asignado</span>',
					"acciones": `<button class="btn btn-sm ${s1.asignado ? 'btn-danger' : 'btn-success'} btn-toggle-submenu1acceso"
						data-submenu1-id="${s1.submenu_id}" data-asignado="${s1.asignado}">
						${s1.asignado ? '<i class="fas fa-times"></i> Quitar' : '<i class="fas fa-plus"></i> Asignar'}
					</button>`
				}));
			}
		},
		columns: [
			{ data: "#" },
			{ data: "submenu" },
			{ data: "submenu1" },
			{ data: "asignado" },
			{ data: "acciones" }
		],
		lengthMenu: lengthMenu20,
		stateSave: true,
		language: idioma_español,
		dom: dom,
		buttons: []
	});
};

$(document).ready(function(){
	$("#modal_registrar_submenu1accesos").on('shown.bs.modal', function(){
		$(this).find('#formSubMenu1Accesos #buscar').focus();
	});
});	
/*FIN SUBMENU1 ACCESOS*/

var editar_privilegio_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarPrivilegios.php';
		$('#formPrivilegios #privilegio_id_').val(data.privilegio_id);
		$('#formPrivilegios #privilegio_nombre').val(data.nombre);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formPrivilegios').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formPrivilegios').attr({ 'data-form': 'update' });
				$('#formPrivilegios').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarPrivilegioAjax.php' });
				$('#formPrivilegios')[0].reset();
				$('#reg_privilegios').hide();
				$('#edi_privilegios').show();
				$('#delete_privilegios').hide();
				$('#formPrivilegios #privilegios_nombre').val(valores[0]);

				if(valores[1] == 1){
					$('#formPrivilegios #privilegio_activo').attr('checked', true);
				}else{
					$('#formPrivilegios #privilegio_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formPrivilegios #privilegios_nombre').attr('readonly', false);
				$('#formPrivilegios #privilegio_activo').attr('disabled', false);
				$('#formPrivilegios #estado_privilegios').show();

				$('#formPrivilegios #proceso_privilegios').val("Editar");
				$('#modal_registrar_privilegios').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_privilegio_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar1");
	$(tbody).on("click", "button.table_eliminar1", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var privilegio_id = data.privilegio_id;
        var nombrePrivilegio = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el privilegio?<br><br>
                        <strong>Nombre:</strong> ${nombrePrivilegio}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarPrivilegiosAjax.php',
                    data: {
                        privilegio_id: privilegio_id
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
//FIN ACCIONES FROMULARIO PRIVILEGIOS

/*INICIO FORMULARIO PRIVILEGIOS*/
function modal_privilegios(){
	$('#formPrivilegios').attr({ 'data-form': 'save' });
	$('#formPrivilegios').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarPrivilegiosAjax.php' });
	$('#formPrivilegios')[0].reset();
	$('#reg_privilegios').show();
	$('#edi_privilegios').hide();
	$('#delete_privilegios').hide();

	//HABILITAR OBJETOS
	$('#formPrivilegios #privilegios_nombre').attr('readonly', false);
	$('#formPrivilegios #privilegio_activo').attr('disabled', false);
	$('#formPrivilegios #estado_privilegios').hide();

	$('#formPrivilegios #proceso_privilegios').val("Registro");
	$('#modal_registrar_privilegios').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}

$(document).ready(function(){
	$("#modal_registrar_privilegios").on('shown.bs.modal', function(){
		$(this).find('#formPrivilegios #privilegios_nombre').focus();
	});
});	
/*FIN FORMULARIO PRIVILEGIOS*/

$('#formPrivilegios #label_privilegio_activo').html("Activo");

$('#formPrivilegios .switch').change(function() {
	if ($('input[name=privilegio_activo]').is(':checked')) {
		$('#formPrivilegios #label_privilegio_activo').html("Activo");
		return true;
	} else {
		$('#formPrivilegios #label_privilegio_activo').html("Inactivo");
		return false;
	}
});

$(document).on('click', '.btn-toggle-menuacceso', function(e) {
    e.preventDefault();
    const btn = $(this);
    const menu_id = btn.data('menu-id');
    const asignado = btn.data('asignado');
    const privilegio_id = $("#formMenuAccesos #privilegio_id_accesos").val();
    const nuevoEstado = asignado ? 0 : 1;

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/asignarMenuAcceso.php',
        data: {
            menu_id: menu_id,
            privilegio_id: privilegio_id,
            estado: nuevoEstado
        },
        success: function(response) {
            const res = JSON.parse(response);
            showNotify(res.type, res.title, res.message);
            
            // Actualización suave sin recargar toda la tabla
            btn.data('asignado', !asignado);
            btn.toggleClass('btn-success btn-danger');
            btn.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
            
            // Actualizar el badge
            const badge = btn.closest('tr').find('span.badge');
            badge.toggleClass('badge-success badge-secondary');
            badge.text(asignado ? 'No asignado' : 'Asignado');
            
            // Actualizar contador
            const currentCount = parseInt($(`#contador-menuaccesos-${privilegio_id}`).text());
            $(`#contador-menuaccesos-${privilegio_id}`).text(asignado ? currentCount - 1 : currentCount + 1);
            
            // Actualizar contador en la tabla principal
            const currentMainCount = parseInt($(`#contador-menus-${privilegio_id}`).text());
            $(`#contador-menus-${privilegio_id}`).text(asignado ? currentMainCount - 1 : currentMainCount + 1);
        }
    });
});

$(document).on('click', '.btn-toggle-submenuacceso', function(e) {
    e.preventDefault();
    const btn = $(this);
    const submenu_id = btn.data('submenu-id');
    const asignado = btn.data('asignado');
    const privilegio_id = $("#formSubMenuAccesos #privilegio_id_accesos").val();
    const nuevoEstado = asignado ? 0 : 1;

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/asignarSubMenuAcceso.php',
        data: {
            submenu_id: submenu_id,
            privilegio_id: privilegio_id,
            estado: nuevoEstado
        },
        success: function(response) {
            const res = JSON.parse(response);
            showNotify(res.type, res.title, res.message);
            
            // Actualización suave
            btn.data('asignado', !asignado);
            btn.toggleClass('btn-success btn-danger');
            btn.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
            
            const badge = btn.closest('tr').find('span.badge');
            badge.toggleClass('badge-success badge-secondary');
            badge.text(asignado ? 'No asignado' : 'Asignado');
            
            // Actualizar contadores
            const currentCount = parseInt($(`#contador-submenuaccesos-${privilegio_id}`).text());
            $(`#contador-submenuaccesos-${privilegio_id}`).text(asignado ? currentCount - 1 : currentCount + 1);
            
            const currentMainCount = parseInt($(`#contador-submenus-${privilegio_id}`).text());
            $(`#contador-submenus-${privilegio_id}`).text(asignado ? currentMainCount - 1 : currentMainCount + 1);
        }
    });
});

$(document).on('click', '.btn-toggle-submenu1acceso', function(e) {
    e.preventDefault();
    const btn = $(this);
    const submenu1_id = btn.data('submenu1-id');
    const asignado = btn.data('asignado');
    const privilegio_id = $("#formSubMenu1Accesos #privilegio_id_accesos").val();
    const nuevoEstado = asignado ? 0 : 1;

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/asignarSubMenu1Acceso.php',
        data: {
            submenu1_id: submenu1_id,
            privilegio_id: privilegio_id,
            estado: nuevoEstado
        },
        success: function(response) {
            const res = JSON.parse(response);
            showNotify(res.type, res.title, res.message);
            
            // Actualización suave
            btn.data('asignado', !asignado);
            btn.toggleClass('btn-success btn-danger');
            btn.html(asignado ? '<i class="fas fa-plus"></i> Asignar' : '<i class="fas fa-times"></i> Quitar');
            
            const badge = btn.closest('tr').find('span.badge');
            badge.toggleClass('badge-success badge-secondary');
            badge.text(asignado ? 'No asignado' : 'Asignado');
            
            // Actualizar contadores
            const currentCount = parseInt($(`#contador-submenu1accesos-${privilegio_id}`).text());
            $(`#contador-submenu1accesos-${privilegio_id}`).text(asignado ? currentCount - 1 : currentCount + 1);
            
            const currentMainCount = parseInt($(`#contador-submenus1-${privilegio_id}`).text());
            $(`#contador-submenus1-${privilegio_id}`).text(asignado ? currentMainCount - 1 : currentMainCount + 1);
        }
    });
});
</script>