var errFormMsg = {
    add: function (form, attr) {
        var $form = $('#' + form);

        $form.on('afterValidateAttribute', function (event, attribute, messages) {
            if (attribute.name != attr) return;

            var $containerErr = $form.find(attribute.container).find('.tooltip');
            var $containerInp = $form.find(attribute.input);

            if (messages.length == 0) {
                $containerErr.hide();
                $containerInp.removeClass('error');
                $containerInp.trigger('removeErrorClass');
            }
            else {
                $containerErr.find('.tooltip-inner').text(messages[0]);
                $containerErr.show();
                //не добавлять класс ошибки
                if (!$containerInp.data('noerror')) {
                    $containerInp.addClass('error');
                    $containerInp.trigger('addErrorClass');

                    var $containerButton = $(attribute.container).find('button.ms-choice');

                    if ($containerButton.length) {
                        $containerButton.addClass('error');
                    }
                }
            }
        });
    }
};