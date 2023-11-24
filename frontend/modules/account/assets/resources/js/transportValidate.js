var blockTransportSubmit = false;
var reCaptchaState = false;
$('#addTransport').on('beforeSubmit', function(event){
    //чтобы предотвратить множественное нажатие
    if( blockTransportSubmit ) return false;

    $('#submitBtn')
        .attr('disabled', true)
        .addClass('form-disable-button');

    blockTransportSubmit = true;

    var file_data = $('#upload-img').prop('files')[0];
    var form_data = new FormData($(this)[0]);

    var $yiiform = $(this);
    $.post({
            type: $yiiform.attr('method'),
            url: $yiiform.attr('action'),
            data: form_data,
            contentType: false,
            processData: false
        }
    )
    .done(function(data){
        $yiiform.yiiActiveForm('updateMessages', data, true);
        reCaptchaState = data.reCaptcha;
        if( reCaptchaState ) {
            grecaptcha.reset();
            $('.field-loginform-recaptcha').show();
        }

        blockTransportSubmit = false;

        $('#submitBtn')
            .attr('disabled', false)
            .removeClass('form-disable-button');
    });

    return false;
});

var typeList = 20;

if( $('#transport-cargocategoryids li').length > typeList ){
    hidenews = '<span class=\"more-btn\"><i class=\"fa fa-chevron-up\" aria-hidden=\"true\"></i> <span class=\"text\">Скрыть</span></span>';
    shownews = '<span class=\"more-btn\"><i class=\"fa fa-chevron-down\" aria-hidden=\"true\"></i> <span class=\"text\">Еще</span></span>';

    $('.checkbox-list__more').html( shownews );
    $('.checkbox-list.more .checkbox-list__item').show();
    $('.checkbox-list.more .checkbox-list__item:not(:lt('+typeList+'))').hide();

    $('.checkbox-list__more').click(function (e){
        e.preventDefault();
        if( $('.checkbox-list.more .checkbox-list__item:eq('+typeList+')').is(':hidden') )
        {
            $('.checkbox-list.more .checkbox-list__item:hidden').fadeIn('slow');
            $('.checkbox-list__more').html( hidenews );
        }
        else
        {
            $('.checkbox-list.more .checkbox-list__item:not(:lt('+typeList+'))').fadeOut('slow');
            $('.checkbox-list__more').html( shownews );
        }
    });
}

$('.form2').on('afterValidateAttribute', function(event, attribute, messages){
    if ($('.form2').find('.has-error').length)
        $('#btn-error').show();
    else
        $('#btn-error').hide();
});