jQuery(function($) {

    $(function () {
        $('[data-toggle="tooltip"]').tooltip({
            trigger: "hover"
        });
    });
        // Configuración inicial
    const $forms = {
        signin: $('#logreg-forms .form-signin'),
        reset: $('#logreg-forms .form-reset'),
        signup: $('#logreg-forms .form-signup')
    };

    // Inicialización - Solo mostrar signin al cargar
    $forms.reset.hide();
    $forms.signup.hide();
    $forms.signin.show();

    // Función para cambiar formularios (sin animaciones)
    function showForm(formToShow, e) {
        if (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
        
        // Oculta todos los formularios
        $forms.signin.hide();
        $forms.reset.hide();
        $forms.signup.hide();
        
        // Muestra el formulario seleccionado
        formToShow.show();
        
        // Enfoca el primer campo
        formToShow.find('input:visible:first').focus();
    }

    // Manejadores de eventos
    $('#forgot_pswd').off('click').on('click', function(e) {
        showForm($forms.reset, e);
    });

    $('#btn-signup').off('click').on('click', function(e) {
        showForm($forms.signup, e);
    });

    $('#cancel_reset, #cancel_signup').off('click').on('click', function(e) {
        showForm($forms.signin, e);
    });

    // Bloqueo de submits
    $forms.signin.off('submit').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    $forms.reset.off('submit').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    $forms.signup.off('submit').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    // Control de contraseñas
    $('[id^="show_password"]').off('click').on('click', function(e) {
        e.preventDefault();
        const $input = $(this).closest('.input-group').find('input');
        const $icon = $(this).find('i');
        $input.attr('type', $input.attr('type') === 'password' ? 'text' : 'password');
        $icon.toggleClass('fa-eye-slash fa-eye');
    });
});