<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Cocina</title>
    <!-- Estilos -->
    <link rel="stylesheet" href="<?php echo SERVERURL; ?>vistas/plantilla/css/cocina.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <script>
        // Definir SERVERURL antes de cargar cualquier script
        var SERVERURL = '<?php echo SERVERURL; ?>';
    </script>

    <!-- Contenedor principal con clase específica -->
    <div class="vista-cocina-container">
        <div class="header-cocina">
            <h1><i class="fas fa-utensils"></i> Pantalla de Cocina</h1>
            <p id="hora-actual"></p>
        </div>
        
        <div class="comandas-container" id="comandas-container">
            <div class="no-comandas">
                <i class="fas fa-info-circle"></i>
                <p>No hay comandas pendientes</p>
            </div>
        </div>

        <div class="refresh-button" id="btn-refresh">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>

    <!-- JS específico para la vista de cocina -->
    <script src="<?php echo SERVERURL; ?>ajax/js/cocina.js"></script>
</body>
</html>