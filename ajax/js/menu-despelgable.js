$(function () {
    $(".dropdown").hover(
        function () {
            // Verificar que no sea un elemento de bootstrap-select
            if (!$(this).hasClass('bootstrap-select') && !$(this).parents('.bootstrap-select').length) {
                $('.dropdown-menu', this).stop(true, true).fadeIn("fast");
                $(this).toggleClass('open');
                $('b', this).toggleClass("caret caret-up");
            }
        },
        function () {
            // Verificar que no sea un elemento de bootstrap-select
            if (!$(this).hasClass('bootstrap-select') && !$(this).parents('.bootstrap-select').length) {
                $('.dropdown-menu', this).stop(true, true).fadeOut("fast");
                $(this).toggleClass('open');
                $('b', this).toggleClass("caret caret-up");
            }
        }
    );
});