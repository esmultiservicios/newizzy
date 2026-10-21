/* IZZY | API visual única para avisos. Conserva Notyf como motor. */
(function (window, document) {
    'use strict';

    var typeIcons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-bell',
        info: 'fa-info-circle',
        loading: 'fa-spinner fa-spin'
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;', '<': '&lt;', '>': '&gt;',
                '"': '&quot;', "'": '&#39;'
            }[character];
        });
    }

    function normalizeType(type) {
        return Object.prototype.hasOwnProperty.call(typeIcons, type) ? type : 'info';
    }

    var notificationSequence = 0;
    var activeLoading = null;
    var loadingSafetyTimer = null;

    function dismissLoading() {
        if (loadingSafetyTimer !== null) {
            clearTimeout(loadingSafetyTimer);
            loadingSafetyTimer = null;
        }

        if (activeLoading) {
            var current = activeLoading;
            activeLoading = null;
            (window.notyf || notyf).dismiss(current);
        }
    }

    function show(type, title, message, options) {
        if (!window.notyf && typeof notyf === 'undefined') {
            console.error('IZZY: Notyf no está disponible.');
            return null;
        }

        var engine = window.notyf || notyf;
        var kind = normalizeType(type);
        // Un resultado sustituye siempre al indicador de espera previo.
        dismissLoading();
        var config = options && typeof options === 'object' ? options : {};
        var duration = typeof config.duration === 'number' && config.duration > 0 ? config.duration : (kind === 'loading' ? 0 : kind === 'error' ? 8500 : 6500);
        var heading = escapeHtml(title || (kind === 'loading' ? 'Procesando' : 'Notificación'));
        var description = escapeHtml(message || '').replace(/\n/g, '<br>');
        var closeId = 'izzy-notify-close-' + (++notificationSequence);
        var html = '<div class="izzy-notify-card izzy-notify-' + kind + '">' +
            '<span class="izzy-notify-symbol"><i class="fas ' + typeIcons[kind] + '" aria-hidden="true"></i></span>' +
            '<div class="izzy-notify-body"><strong>' + heading + '</strong>' +
            (description ? '<span class="izzy-notify-description">' + description + '</span>' : '') +
            (config.buttonText && config.buttonUrl ? '<a class="izzy-notify-action" href="' + escapeHtml(config.buttonUrl) + '">' + escapeHtml(config.buttonText) + '</a>' : '') +
            '</div><button type="button" class="izzy-notify-close" id="' + closeId + '" aria-label="Cerrar notificación" title="Cerrar">' +
            '<i class="fas fa-times" aria-hidden="true"></i></button></div>';

        // Se usa un tipo único: la tarjeta controla los colores y no el fondo de Notyf.
        var toast = engine.open({
            type: kind,
            message: html,
            duration: duration,
            dismissible: true,
            ripple: false
        });

        var closeButton = document.getElementById(closeId);
        if (closeButton) {
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                engine.dismiss(toast);
            });
        }

        if (kind === 'loading' && toast) {
            activeLoading = toast;
            // Respaldo por si la petición no termina o un error impide cerrarla.
            loadingSafetyTimer = setTimeout(dismissLoading, 30000);
        }

        if (toast && toast.on && typeof config.onClick === 'function') {
            toast.on('click', config.onClick);
        }

        return toast;
    }

    window.izzyNotifyPremium = {
        show: show,
        dismissLoading: dismissLoading,
        escapeHtml: escapeHtml
    };
})(window, document);
