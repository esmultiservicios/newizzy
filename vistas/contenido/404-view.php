<div class="container-fluid">
	<div id="layoutError">
		<div id="layoutError_content">
			<main>
				<div class="container">
					<div class="row justify-content-center">
						<div class="col-lg-6">
							<div class="text-center mt-4">
								<img class="mb-4 img-error" src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/error-404.svg" />
								<p class="lead">Esta URL solicitada no se encontró en este servidor..</p>
								<a class="link-return" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>dashboard/">
									<i class="fas fa-arrow-left fa-lg mr-1"></i>
									Regresar al Dashboard
								</a>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>
		<?php
			require_once "./vistas/plantilla/modulos/footer_layoutError.php";
		?>
	</div>
</div>