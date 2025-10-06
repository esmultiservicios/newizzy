<!-- Botón/Link de salir -->
<a href="#" class="btn-exit-system" 
   data-token="<?php echo htmlspecialchars($_SESSION['server_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
   Salir
</a>

<script>
// Token embebido como respaldo por si el <a> no tiene data-token
window.SERVER_TOKEN = '<?php echo addslashes($_SESSION["server_token"] ?? ""); ?>';

$('.btn-exit-system').on('click', (e) => {
    e.preventDefault();

    // 1) Intenta data-token del elemento
    const el = e.currentTarget;
    let token = (el.dataset && el.dataset.token) ? el.dataset.token.trim() : '';

    // 2) Respaldo: token global embebido
    if (!token) token = (window.SERVER_TOKEN || '').trim();

    if (!token) {
        // No hay token: muestra error claro
        swal({
            title: "❌ No se pudo cerrar sesión",
            text: "Falta el token de seguridad. Actualiza la página e inténtalo de nuevo.",
            icon: "error"
        });
        return;
    }

    swal({
        content: {
            element: "div",
            attributes: {
                innerHTML: `
                    <h2 style="color:#f39c12;font-size:22px;margin-bottom:15px;">⚠️ ¿Está seguro?</h2>
                    <p style="font-size:16px;color:#555;">Está a punto de salir del sistema. ¿Desea continuar?</p>
                `
            }
        },
        icon: "warning",
        buttons: true,
        dangerMode: true,
        closeOnClickOutside: false,
        closeOnEsc: false
    }).then((willExit) => {
        if (willExit) {
            salir(token);
        }
    });
});

function salir(token){
    $.ajax({
        url: '<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, "UTF-8");?>ajax/loginAjax.php',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: { token: token }
    })
    .done(function(res){
        if (res && res.ok) {
            window.location.href = res.redirect || "<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8');?>login/";
        } else {
            const msg = (res && res.message) ? res.message : 'Algo salió mal. Por favor, intente de nuevo.';
            swal({ title: "❌ Error", text: msg, icon: "error" });
        }
    })
    .fail(function(){
        swal({
            title: "❌ Error",
            text: "No fue posible comunicarse con el servidor. Intenta nuevamente.",
            icon: "error",
            closeOnEsc: false,
            closeOnClickOutside: false
        });
    });
}
</script>