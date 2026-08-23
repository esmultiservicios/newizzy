<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Cocina</title>
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/cocina.css">
</head>
<body>
    <script>
        var SERVERURL = '<?php echo SERVERURL; ?>';
    </script>

    <div class="vista-cocina-container">
        <header class="header-cocina">
            <h1><i class="fas fa-utensils"></i> Pantalla de Cocina</h1>
            <p id="hora-actual" aria-live="off"></p>
        </header>

        <main class="comandas-container" id="comandas-container" aria-live="polite" aria-busy="false">
            <div class="no-comandas" role="status">
                <div class="no-comandas-icon"><i class="fas fa-clipboard-check"></i></div>
                <strong>Sin comandas pendientes</strong>
                <p>Las nuevas órdenes de Cocina aparecerán aquí automáticamente.</p>
            </div>
        </main>

        <div class="refresh-button" id="btn-refresh" title="Actualizar comandas">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>

    <script src="<?php echo SERVERURL; ?>ajax/js/cocina.js"></script>
</body>
</html>
