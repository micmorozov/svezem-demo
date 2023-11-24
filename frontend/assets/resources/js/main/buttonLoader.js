(function ($) {
    $.fn.buttonLoader = function (action) {
        var self = $(this);

        if (action === 'start') {

            if ($(self).attr("disabled") === "disabled") {
                e.preventDefault();
            }

            $(self).attr('disabled', true);

            $('.has-spinner').attr("disabled", "disabled");
            $(self).attr('data-btn-text', JSON.stringify( $(self).html()));
            $(self).html(JSON.parse($(self).attr('data-btn-text')) + ' <span class="spinner"><i class="fa fa-spinner fa-spin"></i></span>');
            $(self).addClass('active');
        }

        if (action === 'stop') {
            $(self).html(JSON.parse($(self).attr('data-btn-text')));
            $(self).removeClass('active');
            $('.has-spinner').removeAttr("disabled");
            $(self).removeAttr("disabled");
            $(self).removeAttr("data-btn-text");
        }
    }
})(jQuery);
