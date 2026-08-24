<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Cocina - IZZY</title>
    <meta name="theme-color" content="#2f465b">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232f465b'/%3E%3Cpath fill='white' d='M18 11h4v16c0 5-2 8-6 10v16h-5V37c-4-2-6-5-6-10V11h4v13h2V11h4v13h3V11zm26 0c7 0 12 7 12 16 0 7-3 12-8 14v12h-5V11z'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>fontawesome/css/all.min.css">
    <?php $cocinaCssVersion = @filemtime(dirname(__DIR__) . '/plantilla/css/cocina.css') ?: time(); ?>
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/cocina.css?v=<?php echo (int)$cocinaCssVersion; ?>">
</head>
<body>
    <script>
        var SERVERURL = '<?php echo SERVERURL; ?>';
        window.COCINA_URL_TOKEN = <?php echo json_encode($tokenCocina ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <div class="vista-cocina-container">
        <header class="header-cocina">
            <h1><i class="fas fa-utensils"></i> Pantalla de Cocina</h1>
            <p id="hora-actual" aria-live="off"></p>
        </header>

        <section id="cocina-pairing" class="cocina-pairing" hidden>
            <div class="cocina-pairing-card">
                <div class="cocina-pairing-icon"><i class="fas fa-tv"></i></div>
                <h2>Vincular esta pantalla</h2>
                <p class="cocina-pairing-help">Esta pantalla está lista para vincularse. En IZZY abra <strong>Restaurante → Gestionar → Centro de configuración → Cocina</strong> y escriba el código temporal.</p>
                <div id="cocina-pair-code" class="cocina-pair-code" aria-live="polite">------</div>
                <div id="cocina-pair-status" class="cocina-pair-status"><i class="fas fa-spinner fa-spin"></i> Generando código seguro…</div>
                <p id="cocina-pair-expire" class="cocina-pair-expire"></p>
                <button type="button" id="btn-nuevo-codigo-cocina" class="cocina-pair-new">
                    <i class="fas fa-rotate"></i> Generar otro código
                </button>
                <small>El código es temporal y solo sirve para vincular esta pantalla.</small>
            </div>
        </section>

        <main class="comandas-container" id="comandas-container" aria-live="polite" aria-busy="false" hidden>
            <div class="no-comandas" role="status">
                <div class="no-comandas-icon"><i class="fas fa-spinner fa-spin"></i></div>
                <strong>Preparando Cocina…</strong>
                <p>Validando el acceso de esta pantalla.</p>
            </div>
        </main>

        <div class="refresh-button" id="btn-refresh" title="Actualizar comandas">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>

    <?php $cocinaJsVersion = @filemtime(dirname(__DIR__, 2) . '/ajax/js/cocina.js') ?: time(); ?>
    <script src="<?php echo SERVERURL; ?>ajax/js/cocina.js?v=<?php echo (int)$cocinaJsVersion; ?>"></script>
    <script>
      window.setTimeout(function(){
        if(window.COCINA_APP_BOOTED) return;
        var box=document.getElementById('comandas-container');
        if(!box) return;
        box.style.display='grid';
        box.innerHTML='<div class="no-comandas" role="alert"><div class="no-comandas-icon"><i class="fas fa-triangle-exclamation"></i></div><strong>No se pudo iniciar Cocina</strong><p>El archivo de funcionamiento no cargó correctamente. Actualice la pantalla con Ctrl + F5.</p></div>';
      },4000);
    </script>
</body>
</html>
