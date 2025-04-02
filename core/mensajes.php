<?php
function generarMensajeError($titulo, $missingFieldsText) {
    return "
    <script>
        swal({
            title: '$titulo', 
            content: {
                element: 'span',
                attributes: {
                    innerHTML: 'Faltan los siguientes campos: <b>$missingFieldsText</b>. Por favor, corrígelos.'
                }
            },
            icon: 'error', 
            buttons: {
                confirm: {
                    text: 'Entendido',
                    className: 'btn-danger'
                }
            },
            closeOnEsc: false,
            closeOnClickOutside: false
        });
    </script>";
}