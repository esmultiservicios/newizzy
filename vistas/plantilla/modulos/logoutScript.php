<script>	
$('.btn-exit-system').on('click', (e) => {
    e.preventDefault();
    const token = e.currentTarget.getAttribute('href');

	swal({
		content: {
			element: "div",
			attributes: {
				innerHTML: `
					<h2 style="color: #f39c12; font-size: 22px; margin-bottom: 15px;">
						⚠️ ¿Está seguro?
					</h2>
					<p style="font-size: 16px; color: #555;">
						Está a punto de salir del sistema. ¿Seguro que desea continuar? 😟
					</p>
				`
			}
		},
		icon: "warning",
		buttons: true,
		dangerMode: true,
	}).then((willExit) => {
		if (willExit) {
			swal({
				content: {
					element: "div",
					attributes: {
						innerHTML: `
							<h2 style="color: #28a745; font-size: 22px; margin-bottom: 15px;">
								✔️ ¡Has salido del sistema!
							</h2>
							<p style="font-size: 16px; color: #555;">
								Salió con éxito. ¡Hasta pronto! 👋
							</p>
						`
					}
				},
				icon: "success",
			}).then(() => {
				salir(token);  // Llamada a la función salir()
			});
		}
	});
});

function salir(token){
	$.ajax({
		url: '<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8');?>ajax/loginAjax.php?token='+token,
		success: function(data){
			if(data==1){
				window.location.href = "<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8');?>login/";
			}else{
				swal({
					content: {
						element: "div",
						attributes: {
							innerHTML: `
								<h2 style="color: #e74c3c; font-size: 22px; margin-bottom: 15px;">
									❌ Ocurrió un error inesperado
								</h2>
								<p style="font-size: 16px; color: #555;">
									Algo salió mal. ¡No se preocupe! Por favor, intente de nuevo. ⚠️
								</p>
							`
						}
					},
					icon: "error",
					dangerMode: true,
					closeOnEsc: false, // Desactiva el cierre con la tecla Esc
					closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
				});
			}
		},
		error: function(){
			swal({
				content: {
					element: "div",
					attributes: {
						innerHTML: `
							<h2 style="color: #e74c3c; font-size: 22px; margin-bottom: 15px;">
								❌ Ocurrió un error inesperado
							</h2>
							<p style="font-size: 16px; color: #555;">
								Por favor, intente de nuevo. ⚠️
							</p>
						`
					}
				},
				icon: "error",
				dangerMode: true,
				closeOnEsc: false, // Desactiva el cierre con la tecla Esc
				closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
			});		
		}
	});	
}
</script>